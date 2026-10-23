<?php
/**
 * OULBIG Catalogue — importeur (v2.0).
 * Crée et tient à jour de vrais contenus WordPress à partir du catalogue OULBIG / TALIM,
 * dans le type de contenu de votre choix (ABSProjects : « Vidéos », « Audiobooks »…,
 * ABS Records, ou simples Articles). Fonctionne avec n'importe quel thème :
 * le lien YouTube est placé seul sur une ligne dans le contenu (lecteur intégré automatique),
 * et peut aussi être copié dans un champ personnalisé (meta) du thème.
 */
if (!defined('ABSPATH')) exit;

class OULBIG_Catalogue_Importer {
    const OPT  = 'oulbig_import_options';
    const LAST = 'oulbig_import_last';

    public function __construct() {
        add_action('admin_menu', [$this, 'menu'], 20);
        add_action('admin_init', [$this, 'settings']);
        add_action('admin_post_oulbig_import_now', [$this, 'run_now']);
        add_action('oulbig_import_cron', [$this, 'cron']);
        add_action('init', function () {
            if (!wp_next_scheduled('oulbig_import_cron')) wp_schedule_event(time() + 120, 'hourly', 'oulbig_import_cron');
        });
    }

    public static function defaults() {
        $rule = ['post_type' => '', 'status' => 'publish', 'meta_url' => '', 'meta_thumb' => '', 'taxonomy' => '', 'term' => '', 'thumb' => 1, 'max' => 60, 'artists' => ['OULBIG', 'TALIM'], 'langs' => ['fr', 'en', 'es', 'ar', 'ma']];
        return ['song' => $rule, 'podcast' => array_merge($rule, ['max' => 40]), 'auto' => 1];
    }
    public static function opt() {
        $o = get_option(self::OPT, []);
        $d = self::defaults();
        foreach (['song', 'podcast'] as $t) $o[$t] = wp_parse_args($o[$t] ?? [], $d[$t]);
        $o['auto'] = isset($o['auto']) ? (int) $o['auto'] : 1;
        return $o;
    }

    /* ---------- Catalogue ---------- */
    private function catalogue() {
        $c = get_option('oulbig_catalogue_options', []);
        $url = !empty($c['url']) ? $c['url'] : 'https://oulbig-studio.github.io/oulbig-catalogue/catalogue.json';
        $res = wp_remote_get($url, ['timeout' => 20]);
        if (!is_wp_error($res) && wp_remote_retrieve_response_code($res) === 200) {
            $cat = json_decode(wp_remote_retrieve_body($res), true);
            if (is_array($cat) && !empty($cat['items'])) { update_option('oulbig_catalogue_cache_last', $cat, false); return $cat; }
        }
        return get_option('oulbig_catalogue_cache_last', null);
    }

    private function selection($cat, $type, $rule) {
        $out = [];
        foreach ($cat['items'] as $i) {
            if ($i['type'] !== $type || empty($i['youtube']['id'])) continue;
            if (!in_array($i['artist'], (array) $rule['artists'], true) || !in_array($i['lang'], (array) $rule['langs'], true)) continue;
            $k = $i['artist'] . '|' . $i['lang'] . '|' . mb_strtolower(trim($i['title']));
            if (!isset($out[$k]) || ($i['youtube']['views'] ?? 0) > ($out[$k]['youtube']['views'] ?? 0)) $out[$k] = $i;
        }
        $out = array_values($out);
        usort($out, function ($a, $b) use ($type) {
            if ($type === 'podcast') return strcmp($b['published'], $a['published']);
            $ta = $a['artist'] === 'TALIM'; $tb = $b['artist'] === 'TALIM';
            if ($ta !== $tb) return $tb <=> $ta;
            return ($b['youtube']['views'] ?? 0) <=> ($a['youtube']['views'] ?? 0);
        });
        return array_slice($out, 0, max(1, (int) $rule['max']));
    }

    private function find($pt, $id) {
        $q = get_posts(['post_type' => $pt, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_oulbig_id', 'meta_value' => $id]);
        return $q ? (int) $q[0] : 0;
    }

    private function dur($s) { $s = (int) $s; return $s ? floor($s / 60) . ':' . str_pad($s % 60, 2, '0', STR_PAD_LEFT) : ''; }

    /** Une passe : crée au plus $batch nouveaux contenus par type, met à jour les vues des autres. */
    public function run($batch = 25) {
        $stats = ['song' => [0, 0, 0], 'podcast' => [0, 0, 0], 'error' => ''];
        $cat = $this->catalogue();
        if (!$cat) { $stats['error'] = 'Catalogue introuvable.'; update_option(self::LAST, ['time' => time(), 'stats' => $stats], false); return $stats; }
        $o = self::opt();
        $langs = ['fr' => 'Français', 'en' => 'English', 'es' => 'Español', 'ar' => 'العربية', 'ma' => 'Darija'];
        foreach (['song', 'podcast'] as $type) {
            $r = $o[$type];
            if (!$r['post_type'] || !post_type_exists($r['post_type'])) continue;
            $created = 0;
            foreach ($this->selection($cat, $type, $r) as $i) {
                $yt  = $i['youtube'];
                $url = 'https://www.youtube.com/watch?v=' . $yt['id'];
                $pid = $this->find($r['post_type'], $i['id']);
                if (!$pid) {
                    if ($created >= $batch) { $stats[$type][2]++; continue; }
                    $meta = $i['artist'] . ' · ' . ($langs[$i['lang']] ?? strtoupper($i['lang'])) . ($i['duration'] ? ' · ' . $this->dur($i['duration']) : '');
                    $content = $url . "\n\n" . '<p class="oulbig-meta">' . esc_html($meta) . '</p>';
                    if (!empty($i['spotify']['url'])) $content .= "\n" . '<p><a href="' . esc_url($i['spotify']['url']) . '" target="_blank" rel="noopener">Spotify</a></p>';
                    $pid = wp_insert_post([
                        'post_type'    => $r['post_type'],
                        'post_status'  => $r['status'] === 'draft' ? 'draft' : 'publish',
                        'post_title'   => wp_strip_all_tags($i['title']),
                        'post_content' => $content,
                        'post_excerpt' => $meta,
                        'post_date'    => !empty($i['published']) ? $i['published'] . ' 12:00:00' : current_time('mysql'),
                    ]);
                    if (!$pid || is_wp_error($pid)) continue;
                    update_post_meta($pid, '_oulbig_id', $i['id']);
                    update_post_meta($pid, '_oulbig_artist', $i['artist']);
                    update_post_meta($pid, '_oulbig_lang', $i['lang']);
                    update_post_meta($pid, '_oulbig_youtube', $url);
                    if ($r['meta_url'])   update_post_meta($pid, sanitize_key($r['meta_url']), $url);
                    if ($r['meta_thumb']) update_post_meta($pid, sanitize_key($r['meta_thumb']), $i['thumb']);
                    if ($r['taxonomy'] && $r['term'] && taxonomy_exists($r['taxonomy'])) wp_set_object_terms($pid, $r['term'], $r['taxonomy'], true);
                    if ($r['thumb'] && !empty($i['thumb'])) {
                        require_once ABSPATH . 'wp-admin/includes/media.php';
                        require_once ABSPATH . 'wp-admin/includes/file.php';
                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        $img = str_replace('hqdefault', 'maxresdefault', $i['thumb']);
                        $aid = media_sideload_image($img, $pid, $i['title'], 'id');
                        if (is_wp_error($aid)) $aid = media_sideload_image($i['thumb'], $pid, $i['title'], 'id');
                        if (!is_wp_error($aid)) set_post_thumbnail($pid, $aid);
                    }
                    $created++;
                    $stats[$type][0]++;
                } else {
                    $stats[$type][1]++;
                }
                update_post_meta($pid, '_oulbig_views', (int) ($yt['views'] ?? 0));
            }
        }
        update_option(self::LAST, ['time' => time(), 'stats' => $stats], false);
        return $stats;
    }

    public function cron() { if (self::opt()['auto']) $this->run(25); }

    public function run_now() {
        check_admin_referer('oulbig_import_now');
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        $this->run(25);
        wp_safe_redirect(admin_url('options-general.php?page=oulbig-import&done=1'));
        exit;
    }

    /* ---------- Admin ---------- */
    public function menu() {
        add_options_page('OULBIG — Importer', 'OULBIG Import', 'manage_options', 'oulbig-import', [$this, 'page']);
    }
    public function settings() {
        register_setting('oulbig_import', self::OPT, ['sanitize_callback' => function ($in) {
            $out = ['auto' => empty($in['auto']) ? 0 : 1];
            foreach (['song', 'podcast'] as $t) {
                $r = $in[$t] ?? [];
                $out[$t] = [
                    'post_type'  => sanitize_key($r['post_type'] ?? ''),
                    'status'     => ($r['status'] ?? '') === 'draft' ? 'draft' : 'publish',
                    'meta_url'   => sanitize_key($r['meta_url'] ?? ''),
                    'meta_thumb' => sanitize_key($r['meta_thumb'] ?? ''),
                    'taxonomy'   => sanitize_key($r['taxonomy'] ?? ''),
                    'term'       => sanitize_text_field($r['term'] ?? ''),
                    'thumb'      => empty($r['thumb']) ? 0 : 1,
                    'max'        => max(1, min(1000, absint($r['max'] ?? 60))),
                    'artists'    => array_values(array_intersect((array) ($r['artists'] ?? []), ['OULBIG', 'TALIM'])),
                    'langs'      => array_values(array_intersect((array) ($r['langs'] ?? []), ['fr', 'en', 'es', 'ar', 'ma'])),
                ];
            }
            return $out;
        }]);
    }
    public function page() {
        $o = self::opt();
        $last = get_option(self::LAST);
        $pts = get_post_types(['public' => true], 'objects');
        unset($pts['attachment']);
        $labels = ['song' => '🎵 Chansons & clips', 'podcast' => '🎙️ Podcasts (Étincelle…)'];
        ?>
        <div class="wrap">
            <h1>OULBIG — Importer le répertoire</h1>
            <?php if (isset($_GET['done'])): ?><div class="notice notice-success"><p>Import effectué. S'il reste des titres en attente, cliquez à nouveau (ou laissez la synchro horaire continuer).</p></div><?php endif; ?>
            <p>Crée de vrais contenus sur ce site à partir du catalogue OULBIG / TALIM (YouTube @OULBIG, OULBIG – Topic, @TALIM4Art, Spotify), puis les tient à jour toutes les heures. Chaque contenu contient le lecteur YouTube, l'artiste, la langue et la durée ; l'image de la vidéo devient l'image mise en avant.</p>
            <?php if (post_type_exists('mcl_song')): ?><div class="notice notice-info inline"><p>Thème Musicology détecté : il synchronise déjà lui-même le répertoire (menu Musicology › Synchronisation). Inutile de configurer l'import ici.</p></div><?php endif; ?>
            <?php if ($last): $s = $last['stats']; ?>
                <p><strong>Dernier import :</strong> <?php echo esc_html(wp_date('d/m/Y H:i', $last['time'])); ?> —
                chansons +<?php echo (int) $s['song'][0]; ?> (<?php echo (int) $s['song'][2]; ?> en attente),
                podcasts +<?php echo (int) $s['podcast'][0]; ?> (<?php echo (int) $s['podcast'][2]; ?> en attente)
                <?php echo $s['error'] ? '<span style="color:#b32d2e">— ' . esc_html($s['error']) . '</span>' : ''; ?></p>
            <?php endif; ?>
            <p><a class="button button-primary button-hero" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=oulbig_import_now'), 'oulbig_import_now')); ?>">Importer maintenant</a></p>
            <form method="post" action="options.php">
                <?php settings_fields('oulbig_import'); ?>
                <p><label><input type="checkbox" name="<?php echo self::OPT; ?>[auto]" value="1" <?php checked($o['auto'], 1); ?>> Synchronisation automatique toutes les heures</label></p>
                <?php foreach (['song', 'podcast'] as $t): $r = $o[$t]; $n = self::OPT . "[$t]"; ?>
                    <h2><?php echo esc_html($labels[$t]); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr><th>Importer dans</th><td><select name="<?php echo $n; ?>[post_type]"><option value="">— Ne pas importer —</option>
                            <?php foreach ($pts as $pt): ?><option value="<?php echo esc_attr($pt->name); ?>" <?php selected($r['post_type'], $pt->name); ?>><?php echo esc_html($pt->labels->name . ' (' . $pt->name . ')'); ?></option><?php endforeach; ?>
                        </select><p class="description">ABSProjects : « Vidéos » pour les chansons, « Audiobooks » pour les podcasts. ABS Records / autre thème : « Articles » ou le type prévu par le thème.</p></td></tr>
                        <tr><th>Artistes</th><td><?php foreach (['OULBIG', 'TALIM'] as $a): ?><label style="margin-right:14px"><input type="checkbox" name="<?php echo $n; ?>[artists][]" value="<?php echo $a; ?>" <?php checked(in_array($a, (array) $r['artists'], true)); ?>> <?php echo $a; ?></label><?php endforeach; ?></td></tr>
                        <tr><th>Langues</th><td><?php foreach (['fr' => 'FR', 'en' => 'EN', 'es' => 'ES', 'ar' => 'AR', 'ma' => 'Darija'] as $l => $lab): ?><label style="margin-right:14px"><input type="checkbox" name="<?php echo $n; ?>[langs][]" value="<?php echo $l; ?>" <?php checked(in_array($l, (array) $r['langs'], true)); ?>> <?php echo $lab; ?></label><?php endforeach; ?></td></tr>
                        <tr><th>Nombre maximum</th><td><input type="number" min="1" max="1000" name="<?php echo $n; ?>[max]" value="<?php echo (int) $r['max']; ?>"> <span class="description"><?php echo $t === 'song' ? 'TALIM d\'abord, puis OULBIG par nombre de vues.' : 'Les plus récents.'; ?></span></td></tr>
                        <tr><th>Statut</th><td><select name="<?php echo $n; ?>[status]"><option value="publish" <?php selected($r['status'], 'publish'); ?>>Publié</option><option value="draft" <?php selected($r['status'], 'draft'); ?>>Brouillon (je vérifie avant)</option></select></td></tr>
                        <tr><th>Image mise en avant</th><td><label><input type="checkbox" name="<?php echo $n; ?>[thumb]" value="1" <?php checked($r['thumb'], 1); ?>> Télécharger la miniature YouTube</label></td></tr>
                        <tr><th>Champ du lien (facultatif)</th><td><input type="text" name="<?php echo $n; ?>[meta_url]" value="<?php echo esc_attr($r['meta_url']); ?>" placeholder="ex. _video_url"> <span class="description">Clé du champ personnalisé où le thème attend le lien vidéo/audio.</span></td></tr>
                        <tr><th>Champ de l'image (facultatif)</th><td><input type="text" name="<?php echo $n; ?>[meta_thumb]" value="<?php echo esc_attr($r['meta_thumb']); ?>"></td></tr>
                        <tr><th>Classer dans (facultatif)</th><td><input type="text" name="<?php echo $n; ?>[taxonomy]" value="<?php echo esc_attr($r['taxonomy']); ?>" placeholder="taxonomie, ex. category"> <input type="text" name="<?php echo $n; ?>[term]" value="<?php echo esc_attr($r['term']); ?>" placeholder="terme, ex. Musique OULBIG"></td></tr>
                    </table>
                <?php endforeach; ?>
                <?php submit_button(); ?>
            </form>
            <p class="description">Vos modifications sur un contenu importé sont conservées (seul le nombre de vues est mis à jour). Supprimer un contenu importé le fait revenir à la prochaine synchro : mettez-le plutôt en brouillon.</p>
        </div>
        <?php
    }
}
new OULBIG_Catalogue_Importer();
