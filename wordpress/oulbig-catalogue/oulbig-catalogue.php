<?php
/**
 * Plugin Name: OULBIG Catalogue
 * Description: Affiche automatiquement la musique, les podcasts et le Hit Parade OULBIG (YouTube + Spotify) à partir du fichier catalogue.json. Shortcodes : [oulbig_musique], [oulbig_podcasts], [oulbig_audiolivres], [oulbig_hitparade], [oulbig_lien], [oulbig_radio].
 * Version: 1.1.0
 * Plugin URI: https://radio.almoqayada.com
 * Author: OULBIG
 * Text Domain: oulbig-catalogue
 */

if (!defined('ABSPATH')) exit;

class OULBIG_Catalogue {
    const OPT = 'oulbig_catalogue_options';
    const CACHE = 'oulbig_catalogue_cache';

    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'settings']);
        add_action('wp_head', [$this, 'styles']);
        add_shortcode('oulbig_musique', [$this, 'sc_musique']);
        add_shortcode('oulbig_podcasts', [$this, 'sc_podcasts']);
        add_shortcode('oulbig_audiolivres', [$this, 'sc_audiolivres']);
        add_shortcode('oulbig_hitparade', [$this, 'sc_hitparade']);
        add_shortcode('oulbig_lien', [$this, 'sc_lien']);
        add_shortcode('oulbig_radio', [$this, 'sc_radio']);
    }

    /* ---------- Réglages ---------- */
    private function opt($k, $d = '') {
        $o = get_option(self::OPT, []);
        return isset($o[$k]) && $o[$k] !== '' ? $o[$k] : $d;
    }
    public function menu() {
        add_options_page('OULBIG Catalogue', 'OULBIG Catalogue', 'manage_options', 'oulbig-catalogue', [$this, 'page']);
    }
    public function settings() {
        register_setting('oulbig_catalogue', self::OPT, ['sanitize_callback' => function ($in) {
            delete_transient(self::CACHE);
            return [
                'url'   => esc_url_raw($in['url'] ?? ''),
                'ttl'   => max(5, intval($in['ttl'] ?? 60)),
                'radio' => esc_url_raw($in['radio'] ?? ''),
            ];
        }]);
    }
    public function page() {
        if (isset($_POST['oulbig_refresh']) && check_admin_referer('oulbig_refresh')) {
            delete_transient(self::CACHE);
            echo '<div class="updated"><p>Catalogue rechargé.</p></div>';
        }
        $cat = $this->catalogue();
        ?>
        <div class="wrap">
            <h1>OULBIG Catalogue</h1>
            <form method="post" action="options.php">
                <?php settings_fields('oulbig_catalogue'); ?>
                <table class="form-table">
                    <tr><th>Adresse du catalogue.json</th><td><input type="url" class="regular-text" name="<?php echo self::OPT; ?>[url]" value="<?php echo esc_attr($this->opt('url', 'https://oulbig-studio.github.io/oulbig-catalogue/catalogue.json')); ?>"></td></tr>
                    <tr><th>Rafraîchir toutes les (minutes)</th><td><input type="number" min="5" name="<?php echo self::OPT; ?>[ttl]" value="<?php echo esc_attr($this->opt('ttl', 60)); ?>"></td></tr>
                    <tr><th>Adresse d'OULBIG Radio</th><td><input type="url" class="regular-text" name="<?php echo self::OPT; ?>[radio]" value="<?php echo esc_attr($this->opt('radio', 'https://radio.almoqayada.com')); ?>"></td></tr>
                </table>
                <?php submit_button('Enregistrer'); ?>
            </form>
            <form method="post"><?php wp_nonce_field('oulbig_refresh'); ?><input type="hidden" name="oulbig_refresh" value="1"><?php submit_button('Recharger maintenant', 'secondary'); ?></form>
            <h2>État</h2>
            <?php if ($cat): ?>
                <p>Dernière synchro : <strong><?php echo esc_html($cat['generated'] ?? '?'); ?></strong></p>
                <table class="widefat striped" style="max-width:520px"><thead><tr><th>Langue</th><th>Titres</th><th>Podcasts</th></tr></thead><tbody>
                <?php foreach (($cat['counts'] ?? []) as $l => $c): ?>
                    <tr><td><?php echo esc_html(strtoupper($l)); ?></td><td><?php echo intval($c['songs']); ?></td><td><?php echo intval($c['podcasts']); ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php else: ?>
                <p style="color:#b32d2e">Catalogue introuvable. Vérifie l'adresse ci-dessus.</p>
            <?php endif; ?>
            <h2>Shortcodes</h2>
            <p><code>[oulbig_musique lang="fr" limite="12"]</code> · <code>[oulbig_podcasts lang="es"]</code> · <code>[oulbig_audiolivres lang="all" livres="fr:https://…,en:https://…"]</code> ·<code>[oulbig_hitparade lang="ma"]</code> · <code>[oulbig_lien titre="L'Étincelle" plateforme="spotify"]</code> · <code>[oulbig_radio]</code></p>
            <p><code>lang</code> : fr, en, es, ar (Fusha), ma (Darija) ou all.</p>
        </div>
        <?php
    }

    /* ---------- Données ---------- */
    private function catalogue() {
        $cat = get_transient(self::CACHE);
        if ($cat !== false) return $cat;
        $url = $this->opt('url', 'https://oulbig-studio.github.io/oulbig-catalogue/catalogue.json');
        if (!$url) return null;
        $res = wp_remote_get($url, ['timeout' => 15]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
            return get_option(self::CACHE . '_last', null); // dernière version connue
        }
        $cat = json_decode(wp_remote_retrieve_body($res), true);
        if (!is_array($cat) || empty($cat['items'])) return get_option(self::CACHE . '_last', null);
        set_transient(self::CACHE, $cat, 60 * intval($this->opt('ttl', 60)));
        update_option(self::CACHE . '_last', $cat, false);
        return $cat;
    }
    private function items($lang, $type, $limit, $order = 'recent') {
        $cat = $this->catalogue();
        if (!$cat) return [];
        $list = array_filter($cat['items'], function ($i) use ($lang, $type) {
            return $i['type'] === $type && ($lang === 'all' || $i['lang'] === $lang);
        });
        usort($list, function ($a, $b) use ($order) {
            if ($order === 'rank') return ($a['rank'] ?: 99) <=> ($b['rank'] ?: 99);
            if ($order === 'ancien') return strcmp($a['published'], $b['published']);
            return strcmp($b['published'], $a['published']);
        });
        if ($order === 'rank') $list = array_filter($list, function ($i) { return !empty($i['rank']); });
        return array_slice(array_values($list), 0, max(1, intval($limit)));
    }
    private function rtl($lang) { return in_array($lang, ['ar', 'ma'], true) ? ' dir="rtl"' : ''; }
    private function buttons($i) {
        $h = '';
        if (!empty($i['youtube']['url'])) $h .= '<a class="ob-btn ob-yt" href="' . esc_url($i['youtube']['url']) . '" target="_blank" rel="noopener">YouTube</a>';
        if (!empty($i['spotify']['url'])) $h .= '<a class="ob-btn ob-sp" href="' . esc_url($i['spotify']['url']) . '" target="_blank" rel="noopener">Spotify</a>';
        if (!empty($i['rss']['url']))     $h .= '<a class="ob-btn" href="' . esc_url($i['rss']['url']) . '" target="_blank" rel="noopener">Podcast</a>';
        return $h;
    }
    private function dur($s) { $s = intval($s); return floor($s / 60) . ':' . str_pad($s % 60, 2, '0', STR_PAD_LEFT); }

    /* ---------- Shortcodes ---------- */
    public function sc_musique($a) {
        $a = shortcode_atts(['lang' => 'all', 'limite' => 12, 'ordre' => 'recent'], $a);
        $list = $this->items($a['lang'], 'song', $a['limite'], $a['ordre']);
        if (!$list) return '';
        $h = '<div class="ob-grid"' . $this->rtl($a['lang']) . '>';
        foreach ($list as $i) {
            $img = $i['thumb'] ? '<img src="' . esc_url($i['thumb']) . '" alt="" loading="lazy">' : '<span class="ob-ph">' . esc_html(mb_substr($i['title'], 0, 1)) . '</span>';
            $h .= '<article class="ob-card"><div class="ob-art">' . $img . '</div><h3>' . esc_html($i['title']) . '</h3><p class="ob-meta">OULBIG · ' . esc_html(strtoupper($i['lang'])) . '</p><div class="ob-btns">' . $this->buttons($i) . '</div></article>';
        }
        return $h . '</div>';
    }
    public function sc_podcasts($a) {
        $a = shortcode_atts(['lang' => 'all', 'limite' => 20, 'ordre' => 'recent'], $a);
        $list = $this->items($a['lang'], 'podcast', $a['limite'], $a['ordre']);
        if (!$list) return '';
        $h = '<ol class="ob-list"' . $this->rtl($a['lang']) . '>';
        foreach ($list as $i) {
            $h .= '<li><div><strong>' . esc_html($i['title']) . '</strong><span class="ob-meta">' . esc_html($i['published']) . ' · ' . $this->dur($i['duration']) . '</span></div><div class="ob-btns">' . $this->buttons($i) . '</div></li>';
        }
        return $h . '</ol>';
    }
    /**
     * Épisodes de podcast présentés comme extraits gratuits de livres audio.
     * [oulbig_audiolivres lang="all" limite="100" ordre="ancien" livres="fr:URL,en:URL" filtres="1"]
     * - livres : lien « Le livre complet » par langue (fr, en, es, ar, ma).
     * - filtres : affiche les boutons de langue quand lang="all".
     */
    public function sc_audiolivres($a) {
        $a = shortcode_atts(['lang' => 'all', 'limite' => 100, 'ordre' => 'ancien', 'livres' => '', 'filtres' => '1'], $a);
        $list = $this->items($a['lang'], 'podcast', $a['limite'], $a['ordre']);
        if (!$list) return '';
        $names = ['fr' => 'Français', 'en' => 'English', 'es' => 'Español', 'ar' => 'العربية', 'ma' => 'الدارجة'];
        $books = [];
        foreach (array_filter(array_map('trim', explode(',', $a['livres']))) as $pair) {
            $p = explode(':', $pair, 2);
            if (count($p) === 2 && isset($names[$p[0]])) $books[$p[0]] = esc_url($p[1]);
        }
        $langs = array_values(array_unique(array_map(function ($i) { return $i['lang']; }, $list)));
        $h = '<div class="ob-al">';
        if ($a['lang'] === 'all' && $a['filtres'] && count($langs) > 1) {
            $h .= '<div class="ob-al-filters"><button type="button" class="is-active" data-l="">Toutes les langues</button>';
            foreach ($names as $code => $n) {
                if (in_array($code, $langs, true)) $h .= '<button type="button" data-l="' . esc_attr($code) . '">' . esc_html($n) . '</button>';
            }
            $h .= '</div>';
        }
        $h .= '<div class="ob-al-grid">';
        foreach ($list as $i) {
            $yt   = $i['youtube'] ?? null;
            $ok   = $yt && !empty($yt['id']) && ($yt['embeddable'] ?? true);
            $url  = $yt['url'] ?? ($i['spotify']['url'] ?? ($i['rss']['url'] ?? ''));
            $img  = $i['thumb'] ? '<img src="' . esc_url($i['thumb']) . '" alt="" loading="lazy">' : '<span class="ob-ph">' . esc_html(mb_substr($i['title'], 0, 1)) . '</span>';
            $play = $ok
                ? '<button type="button" class="ob-al-play" data-yt="' . esc_attr($yt['id']) . '" aria-label="Écouter : ' . esc_attr($i['title']) . '">' . $img . '<span class="ob-al-icon">▶</span></button>'
                : '<a class="ob-al-play" href="' . esc_url($url) . '" target="_blank" rel="noopener">' . $img . '<span class="ob-al-icon">▶</span></a>';
            $h .= '<article class="ob-al-card" data-l="' . esc_attr($i['lang']) . '"' . $this->rtl($i['lang']) . '>';
            $h .= '<div class="ob-al-media">' . $play . '</div>';
            $h .= '<p class="ob-al-kicker">Livre audio · extrait gratuit · ' . esc_html($names[$i['lang']] ?? strtoupper($i['lang'])) . ($i['duration'] ? ' · ' . $this->dur($i['duration']) : '') . '</p>';
            $h .= '<h3>' . esc_html($i['title']) . '</h3>';
            $h .= '<div class="ob-btns">' . $this->buttons($i);
            if (!empty($books[$i['lang']])) $h .= '<a class="ob-btn ob-al-book" href="' . $books[$i['lang']] . '">Le livre complet</a>';
            $h .= '</div></article>';
        }
        $h .= '</div></div>';
        static $js = false;
        if (!$js) {
            $js = true;
            $h .= '<script>(function(){document.addEventListener("click",function(e){var b=e.target.closest(".ob-al-play[data-yt]");if(b){var f=document.createElement("iframe");f.src="https://www.youtube-nocookie.com/embed/"+encodeURIComponent(b.dataset.yt)+"?autoplay=1&rel=0";f.title=b.getAttribute("aria-label")||"";f.allow="autoplay; encrypted-media; picture-in-picture";f.allowFullscreen=true;b.parentNode.replaceChild(f,b);return;}var t=e.target.closest(".ob-al-filters button");if(!t)return;var w=t.closest(".ob-al");w.querySelectorAll(".ob-al-filters button").forEach(function(x){x.classList.remove("is-active")});t.classList.add("is-active");w.querySelectorAll(".ob-al-card").forEach(function(c){c.hidden=!!t.dataset.l&&c.dataset.l!==t.dataset.l;});});})();</script>';
        }
        return $h;
    }
    public function sc_hitparade($a) {
        $a = shortcode_atts(['lang' => 'fr', 'limite' => 10], $a);
        $list = $this->items($a['lang'], 'song', $a['limite'], 'rank');
        if (!$list) return '';
        $arrow = ['up' => '▲', 'down' => '▼', 'same' => '=', 'new' => 'NEW'];
        $h = '<ol class="ob-list ob-hits"' . $this->rtl($a['lang']) . '>';
        foreach ($list as $i) {
            $t = $i['trend'] ?? '';
            $h .= '<li><span class="ob-rank">' . intval($i['rank']) . '</span><div><strong>' . esc_html($i['title']) . '</strong><span class="ob-meta">OULBIG</span></div><span class="ob-trend ob-' . esc_attr($t) . '">' . esc_html($arrow[$t] ?? '') . '</span><div class="ob-btns">' . $this->buttons($i) . '</div></li>';
        }
        return $h . '</ol>';
    }
    public function sc_lien($a) {
        $a = shortcode_atts(['titre' => '', 'plateforme' => 'youtube', 'texte' => ''], $a);
        $cat = $this->catalogue();
        if (!$cat || !$a['titre']) return '';
        $needle = mb_strtolower(trim($a['titre']));
        foreach ($cat['items'] as $i) {
            if (mb_strtolower($i['title']) !== $needle) continue;
            $url = $i[$a['plateforme']]['url'] ?? '';
            if ($url) return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($a['texte'] ?: $i['title']) . '</a>';
        }
        return esc_html($a['texte'] ?: $a['titre']);
    }
    public function sc_radio($a) {
        $a = shortcode_atts(['texte' => 'Écouter OULBIG Radio en direct'], $a);
        $url = $this->opt('radio', 'https://radio.almoqayada.com') ?: (($this->catalogue()['links']['radio'] ?? ''));
        return $url ? '<a class="ob-btn ob-radio" href="' . esc_url($url) . '" target="_blank" rel="noopener">● ' . esc_html($a['texte']) . '</a>' : '';
    }

    public function styles() { ?>
<style id="oulbig-catalogue">
.ob-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:18px;margin:1.5em 0}
.ob-card{display:flex;flex-direction:column;gap:6px}
.ob-art{aspect-ratio:16/9;border-radius:12px;overflow:hidden;background:linear-gradient(135deg,#FF3D6E,#FFB347);display:grid;place-items:center}
.ob-art img{width:100%;height:100%;object-fit:cover;display:block}
.ob-ph{font-size:48px;font-weight:800;color:#fff}
.ob-card h3{font-size:1rem;margin:4px 0 0;line-height:1.3}
.ob-meta{display:block;font-size:.8rem;opacity:.65}
.ob-btns{display:flex;gap:6px;flex-wrap:wrap}
.ob-btn{display:inline-block;padding:5px 12px;border-radius:999px;font-size:.8rem;font-weight:600;text-decoration:none!important;border:1px solid currentColor;opacity:.9}
.ob-btn:hover{opacity:1}
.ob-yt{color:#E3134B!important}.ob-sp{color:#17A94F!important}
.ob-radio{background:#E3134B;color:#fff!important;border-color:#E3134B;padding:9px 18px;font-size:.9rem}
.ob-list{list-style:none;margin:1.5em 0;padding:0}
.ob-list li{display:flex;align-items:center;gap:14px;padding:12px 0;border-top:1px solid rgba(127,127,127,.25)}
.ob-list li>div:first-of-type{flex:1;min-width:0}
.ob-rank{font-size:1.5rem;font-weight:900;width:36px;text-align:center;opacity:.8}
.ob-trend{font-size:.75rem;font-weight:700;width:36px;text-align:center}
.ob-al-filters{display:flex;flex-wrap:wrap;gap:8px;margin:1em 0}
.ob-al-filters button{border:1px solid currentColor;background:none;color:inherit;border-radius:999px;padding:6px 14px;cursor:pointer;opacity:.7;font:inherit;font-size:.9em}
.ob-al-filters button.is-active{background:#E3134B;border-color:#E3134B;color:#fff;opacity:1}
.ob-al-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;margin:1em 0 2em}
.ob-al-card{display:flex;flex-direction:column;gap:6px}
.ob-al-card[hidden]{display:none}
.ob-al-media{aspect-ratio:16/9;border-radius:12px;overflow:hidden;background:#111}
.ob-al-media iframe{width:100%;height:100%;border:0;display:block}
.ob-al-play{position:relative;display:block;width:100%;height:100%;padding:0;border:0;background:none;cursor:pointer}
.ob-al-play img{width:100%;height:100%;object-fit:cover;display:block}
.ob-al-icon{position:absolute;inset:0;margin:auto;width:56px;height:56px;border-radius:50%;background:rgba(0,0,0,.6);color:#fff;display:grid;place-items:center;font-size:22px}
.ob-al-play:hover .ob-al-icon{background:#E3134B}
.ob-al-kicker{font-size:.78rem;opacity:.7;margin:4px 0 0}
.ob-al-card h3{font-size:1rem;margin:0;line-height:1.3}
.ob-al-book{background:#E3134B;color:#fff!important;border-color:#E3134B}
.ob-up{color:#0E9C66}.ob-down{color:#D63636}.ob-new{color:#EE8A00}.ob-same{opacity:.5}
</style>
<?php }
}
new OULBIG_Catalogue();
