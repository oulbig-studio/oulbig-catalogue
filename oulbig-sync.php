<?php
/**
 * Plugin Name: OULBIG Sync — répertoire OULBIG & TALIM
 * Description: Importe et tient à jour les vraies chansons, clips et épisodes de podcast OULBIG / TALIM (catalogue YouTube + Spotify synchronisé chaque jour). Réglage automatique pour ABSProjects (Vidéos + Audiobooks) ; vitrine « Extraits & podcasts » pour ABS Records (E-Voice). Compatible avec le plugin OULBIG Catalogue v1 (shortcodes).
 * Version: 1.0.0
 * Author: OULBIG
 * Requires PHP: 7.4
 * Text Domain: oulbig-sync
 */
if (!defined('ABSPATH')) exit;

define('OULBIG_SYNC_VER', '1.0.0');

// Importeur (partagé avec OULBIG Catalogue v2 : si celui-ci est actif, il le fournit déjà).
add_action('plugins_loaded', function () {
    if (!class_exists('OULBIG_Catalogue_Importer')) require_once __DIR__ . '/inc/importer.php';
}, 5);

/* ------------------------------------------------------------------
 * Préréglages selon le thème du site (une seule fois, modifiables ensuite
 * dans Réglages › OULBIG Import).
 * ------------------------------------------------------------------ */
function oulbig_sync_preset() {
    if (get_option('oulbig_sync_preset_done') || !class_exists('OULBIG_Catalogue_Importer')) return;
    $o = OULBIG_Catalogue_Importer::opt();
    if (post_type_exists('abs_video') && post_type_exists('abs_audiobook')) {
        // ABSProjects : chansons & clips → Vidéos, épisodes de podcast → Audiobooks.
        $o['song'] = array_merge($o['song'], [
            'post_type' => 'abs_video', 'meta_url' => 'abs_video_url', 'max' => 24,
            'meta_extra' => "abs_youtube={url}\nabs_video_type=video\nabs_duration={duration}",
            'taxonomy' => taxonomy_exists('abs_collection') ? 'abs_collection' : '', 'term' => 'Musique OULBIG & TALIM',
        ]);
        $o['podcast'] = array_merge($o['podcast'], [
            'post_type' => 'abs_audiobook', 'meta_url' => 'abs_youtube', 'max' => 12,
            'meta_extra' => "abs_narrator={artist}\nabs_duration={duration}\nabs_spotify={spotify}",
            'taxonomy' => taxonomy_exists('abs_collection') ? 'abs_collection' : '', 'term' => 'Podcast OULBIG',
        ]);
        $o['auto'] = 1;
        update_option(OULBIG_Catalogue_Importer::OPT, $o);
    }
    // Autres thèmes (ABS Records…) : pas d'import en contenus, la vitrine lit le catalogue directement.
    update_option('oulbig_sync_preset_done', OULBIG_SYNC_VER);
    if (!wp_next_scheduled('oulbig_import_catchup')) wp_schedule_single_event(time() + 30, 'oulbig_import_catchup');
}
add_action('init', 'oulbig_sync_preset', 30);

/* ------------------------------------------------------------------
 * Catalogue (cache 1 h, puis dernière copie connue).
 * ------------------------------------------------------------------ */
function oulbig_sync_catalogue() {
    $cat = get_transient('oulbig_sync_cat');
    if (is_array($cat)) return $cat;
    $c   = get_option('oulbig_catalogue_options', []);
    $url = !empty($c['url']) ? $c['url'] : 'https://oulbig-studio.github.io/oulbig-catalogue/catalogue.json';
    $res = wp_remote_get($url, ['timeout' => 15]);
    if (!is_wp_error($res) && wp_remote_retrieve_response_code($res) === 200) {
        $cat = json_decode(wp_remote_retrieve_body($res), true);
        if (is_array($cat) && !empty($cat['items'])) {
            set_transient('oulbig_sync_cat', $cat, HOUR_IN_SECONDS);
            update_option('oulbig_catalogue_cache_last', $cat, false);
            return $cat;
        }
    }
    $last = get_option('oulbig_catalogue_cache_last');
    if (is_array($last) && !empty($last['items'])) return $last;
    // Dernier recours : copie du catalogue incluse dans l'extension.
    $snap = json_decode((string) @file_get_contents(__DIR__ . '/catalogue-snapshot.json'), true);
    return is_array($snap) && !empty($snap['items']) ? $snap : null;
}

function oulbig_sync_pick($type, $n, $filter = null) {
    $cat = oulbig_sync_catalogue();
    if (!$cat) return [];
    $seen = [];
    $out  = [];
    foreach ($cat['items'] as $i) {
        if ($i['type'] !== $type || empty($i['youtube']['id'])) continue;
        if ($filter && !$filter($i)) continue;
        $k = mb_strtolower(trim($i['title']));
        if (isset($seen[$k])) continue;
        $seen[$k] = 1;
        $out[] = $i;
    }
    usort($out, function ($a, $b) use ($type) {
        if ($type === 'podcast') return strcmp($b['published'], $a['published']);
        return ($b['youtube']['views'] ?? 0) <=> ($a['youtube']['views'] ?? 0);
    });
    return array_slice($out, 0, $n);
}

/* ------------------------------------------------------------------
 * Vitrine ABS Records (thème one-page E-Voice sans types de contenu).
 * ------------------------------------------------------------------ */
function oulbig_sync_showcase_enabled() {
    $on = strpos(get_template(), 'abs-evoice') === 0;
    return (bool) apply_filters('oulbig_sync_showcase', $on);
}

function oulbig_sync_card($i, $kind) {
    $id    = $i['youtube']['id'];
    $thumb = $i['thumb'] ?: 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg';
    $d     = (int) ($i['duration'] ?? 0);
    $dur   = $d ? floor($d / 60) . ':' . str_pad($d % 60, 2, '0', STR_PAD_LEFT) : '';
    $rtl   = in_array($i['lang'], ['ar', 'ma'], true) || preg_match('/\p{Arabic}/u', $i['title']);
    $langs = ['fr' => 'FR', 'en' => 'EN', 'es' => 'ES', 'ar' => 'AR', 'ma' => 'Darija'];
    $meta  = $i['artist'] . ' · ' . ($langs[$i['lang']] ?? strtoupper($i['lang'])) . ($dur ? ' · ' . $dur : '');
    ob_start(); ?>
    <article class="obx-card">
        <button type="button" class="obx-art" data-yt="<?php echo esc_attr($id); ?>" aria-label="<?php echo esc_attr($i['title']); ?>">
            <img src="<?php echo esc_url($thumb); ?>" alt="" loading="lazy">
            <span class="obx-badge"><?php echo $kind === 'podcast' ? 'Podcast' : 'Musique'; ?></span>
            <span class="obx-play"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
        </button>
        <h3<?php echo $rtl ? ' dir="rtl"' : ''; ?>><?php echo esc_html($i['title']); ?></h3>
        <p class="obx-meta"><?php echo esc_html($meta); ?></p>
        <p class="obx-links">
            <a href="https://www.youtube.com/watch?v=<?php echo esc_attr($id); ?>" target="_blank" rel="noopener">YouTube</a>
            <?php if (!empty($i['spotify']['url'])): ?><a href="<?php echo esc_url($i['spotify']['url']); ?>" target="_blank" rel="noopener">Spotify</a><?php endif; ?>
        </p>
    </article>
    <?php return ob_get_clean();
}

add_action('wp_footer', function () {
    if (!oulbig_sync_showcase_enabled() || !(is_front_page() || is_home())) return;
    $pods  = oulbig_sync_pick('podcast', 8);
    $songs = oulbig_sync_pick('song', 8, function ($i) { return in_array($i['artist'], ['OULBIG', 'TALIM'], true); });
    if (!$pods && !$songs) return;
    ?>
    <template id="obx-tpl">
    <section id="extraits" class="obx">
        <div class="wrap">
            <div class="sec-head">
                <div>
                    <span class="eyebrow" data-obx="eyebrow">Extraits &amp; podcasts</span>
                    <h2 data-obx="h2">Écoutez le studio</h2>
                    <p data-obx="p">Épisodes de podcast et titres originaux OULBIG &amp; TALIM — mis à jour automatiquement depuis YouTube et Spotify.</p>
                </div>
            </div>
            <?php if ($pods): ?>
                <h3 class="obx-sub" data-obx="pods">Derniers épisodes</h3>
                <div class="obx-grid"><?php foreach ($pods as $i) echo oulbig_sync_card($i, 'podcast'); // phpcs:ignore ?></div>
            <?php endif; ?>
            <?php if ($songs): ?>
                <h3 class="obx-sub" data-obx="songs">Musique OULBIG &amp; TALIM</h3>
                <div class="obx-grid"><?php foreach ($songs as $i) echo oulbig_sync_card($i, 'song'); // phpcs:ignore ?></div>
            <?php endif; ?>
        </div>
    </section>
    </template>
    <style id="obx-css">
    .obx{padding-top:0}
    .obx-sub{font-family:var(--display,serif);font-size:1.25rem;font-weight:600;margin:28px 0 14px;color:var(--cream,#f6efe4)}
    .obx-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:20px}
    .obx-card{background:var(--surface,#221d17);border:1px solid var(--line,rgba(234,210,170,.12));border-radius:var(--radius,18px);padding:12px;display:flex;flex-direction:column;gap:6px;min-width:0}
    .obx-art{position:relative;display:block;width:100%;aspect-ratio:16/9;border:0;padding:0;border-radius:12px;overflow:hidden;background:#000;cursor:pointer}
    .obx-art img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .4s}
    .obx-art:hover img{transform:scale(1.04)}
    .obx-art iframe{position:absolute;inset:0;width:100%;height:100%;border:0}
    .obx-badge{position:absolute;top:8px;left:8px;font-size:.66rem;letter-spacing:.14em;text-transform:uppercase;font-weight:700;background:rgba(20,17,13,.75);color:var(--gold,#e6a94e);padding:4px 9px;border-radius:999px}
    .obx-play{position:absolute;right:10px;bottom:10px;width:40px;height:40px;border-radius:50%;display:grid;place-items:center;background:var(--gold,#e6a94e);color:#14110d;box-shadow:0 8px 20px -6px rgba(0,0,0,.6)}
    .obx-card h3{font-size:.98rem;line-height:1.35;margin-top:6px;font-weight:700;overflow-wrap:anywhere}
    .obx-meta{font-size:.78rem;color:var(--muted,#a99c89)}
    .obx-links{display:flex;gap:12px;font-size:.78rem;font-weight:700;margin-top:auto}
    .obx-links a{color:var(--gold-soft,#f0c277)}
    .obx-links a:hover{text-decoration:underline}
    </style>
    <script id="obx-js">
    (function(){
      var tpl=document.getElementById('obx-tpl'); if(!tpl) return;
      var node=tpl.content.cloneNode(true);
      var anchor=document.getElementById('authors')||document.querySelector('footer');
      if(anchor&&anchor.parentNode){anchor.parentNode.insertBefore(node,anchor);}else{document.body.appendChild(node);}
      var nav=document.querySelector('header nav.links');
      if(nav&&!nav.querySelector('a[href="#extraits"]')){var a=document.createElement('a');a.href='#extraits';a.setAttribute('data-obx','nav');a.textContent='Extraits';var ref=nav.querySelector('a[href="#authors"]');nav.insertBefore(a,ref);}
      var T={fr:{nav:'Extraits',eyebrow:'Extraits & podcasts',h2:'Écoutez le studio',p:'Épisodes de podcast et titres originaux OULBIG & TALIM — mis à jour automatiquement depuis YouTube et Spotify.',pods:'Derniers épisodes',songs:'Musique OULBIG & TALIM'},
             en:{nav:'Samples',eyebrow:'Samples & podcasts',h2:'Listen to the studio',p:'Podcast episodes and original OULBIG & TALIM tracks — updated automatically from YouTube and Spotify.',pods:'Latest episodes',songs:'OULBIG & TALIM music'},
             ar:{nav:'مقتطفات',eyebrow:'مقتطفات وبودكاست',h2:'استمع إلى الاستوديو',p:'حلقات البودكاست وأغاني OULBIG و TALIM الأصلية — تُحدَّث تلقائياً من يوتيوب وسبوتيفاي.',pods:'أحدث الحلقات',songs:'موسيقى OULBIG و TALIM'}};
      function tr(l){var d=T[l]||T.fr;document.querySelectorAll('[data-obx]').forEach(function(el){var k=el.getAttribute('data-obx');if(d[k])el.textContent=d[k];});}
      document.querySelectorAll('.langs [data-lang]').forEach(function(b){b.addEventListener('click',function(){tr(b.getAttribute('data-lang'));});});
      var on=document.querySelector('.langs .on'); if(on) tr(on.getAttribute('data-lang'));
      document.addEventListener('click',function(e){
        var b=e.target.closest('.obx-art'); if(!b||b.querySelector('iframe')) return;
        var f=document.createElement('iframe');
        f.src='https://www.youtube-nocookie.com/embed/'+encodeURIComponent(b.getAttribute('data-yt'))+'?autoplay=1&rel=0';
        f.allow='autoplay; encrypted-media; picture-in-picture'; f.allowFullscreen=true; f.title=b.getAttribute('aria-label')||'YouTube';
        b.appendChild(f);
      });
    })();
    </script>
    <?php
}, 5);

/* Lien Réglages dans la liste des extensions. */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($l) {
    array_unshift($l, '<a href="' . esc_url(admin_url('options-general.php?page=oulbig-import')) . '">Réglages</a>');
    return $l;
});

register_activation_hook(__FILE__, function () {
    delete_option('oulbig_sync_preset_done');
    wp_schedule_single_event(time() + 30, 'oulbig_import_catchup');
});
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('oulbig_import_catchup');
});
