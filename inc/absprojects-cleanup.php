<?php
/**
 * ABSProjects — nettoyage unique des contenus d'exemple :
 * - « Hello world! » (article par défaut de WordPress) → corbeille (récupérable) ;
 * - liens d'exemple (example.com, « exemple », EXAMPLE…) des Marques, Ebooks et Boutique
 *   → vrais liens (Educaplat, YouTube OULBIG, commande WhatsApp) ou supprimés s'il n'y en a pas ;
 * - ajout des deux vrais livres (Introduction : 37 étapes + 1, L'Effet d'Amour) dans « Ebooks ».
 * S'exécute une seule fois par version (option oulbig_absp_cleanup).
 */
if (!defined('ABSPATH')) exit;

const OULBIG_ABSP_CLEANUP = '2';

function oulbig_absp_is_fake($v) {
    return is_string($v) && preg_match('~example\.|exemple|EXAMPLE~i', $v);
}

function oulbig_absp_wa($text) {
    return 'https://wa.me/212661692067?text=' . rawurlencode($text);
}

function oulbig_absp_cleanup() {
    if (get_option('oulbig_absp_cleanup') === OULBIG_ABSP_CLEANUP) return;
    if (!post_type_exists('abs_brand') || !post_type_exists('abs_ebook')) return;
    $log = [];

    // 1. Article par défaut.
    foreach (get_posts(['post_type' => 'post', 'name' => 'hello-world', 'post_status' => 'any', 'posts_per_page' => 5]) as $p) {
        wp_trash_post($p->ID);
        $log[] = 'corbeille: ' . $p->post_title;
    }

    // 2. Liens d'exemple.
    $educaplat = 'https://educaplat.almoqayada.com/';
    $brands = [
        'educaplat' => $educaplat,
        'oulbig studio' => 'https://www.youtube.com/@OULBIG',
        'musicology' => 'https://musicology.almoqayada.com/',
        'radio' => 'https://radio.almoqayada.com/',
        'almoqayada' => 'https://almoqayada.com/',
    ];
    $posts = get_posts(['post_type' => ['abs_brand', 'abs_ebook', 'abs_shop', 'abs_artwork', 'abs_video', 'abs_audiobook'], 'post_status' => 'any', 'posts_per_page' => -1]);
    foreach ($posts as $p) {
        foreach (get_post_meta($p->ID) as $key => $vals) {
            if (strpos($key, 'abs_') !== 0 || !oulbig_absp_is_fake($vals[0])) continue;
            $new = '';
            $title = mb_strtolower($p->post_title);
            if ($p->post_type === 'abs_brand') {
                foreach ($brands as $needle => $url) if (strpos($title, $needle) !== false) { $new = $url; break; }
            } elseif ($p->post_type === 'abs_ebook') {
                if ($key === 'abs_educaplat' || $key === 'abs_preview') $new = $educaplat;
                // Amazon (compte KDP fermé), Kobo, Gumroad d'exemple : retirés.
            } elseif ($p->post_type === 'abs_shop') {
                $new = oulbig_absp_wa('Bonjour, je souhaite commander « ' . $p->post_title . ' ».');
                if ($key === 'abs_platform') $new = 'WhatsApp';
            }
            if ($new) update_post_meta($p->ID, $key, $new); else delete_post_meta($p->ID, $key);
            if ($p->post_type === 'abs_shop' && $key === 'abs_url') update_post_meta($p->ID, 'abs_platform', 'WhatsApp');
            $log[] = $p->post_type . ' « ' . $p->post_title . ' » ' . $key . ' → ' . ($new ?: 'retiré');
        }
        // Liens d'exemple écrits dans le texte.
        if (oulbig_absp_is_fake($p->post_content)) {
            $c = preg_replace('~https?://[^\s"\'<>]*(example\.|exemple|EXAMPLE)[^\s"\'<>]*~i', $educaplat, $p->post_content);
            wp_update_post(['ID' => $p->ID, 'post_content' => $c]);
            $log[] = $p->post_type . ' « ' . $p->post_title . ' » texte corrigé';
        }
        if ($p->post_type === 'abs_shop' && oulbig_absp_is_fake(get_post_meta($p->ID, 'abs_platform', true))) {
            update_post_meta($p->ID, 'abs_platform', 'WhatsApp');
        }
    }

    // 3. Les deux vrais livres.
    $books = [
        ['Introduction : 37 étapes + 1', 'OULBIG', "Tome 1 de la saga L'Étincelle : 37 étapes, plus une, pour changer de vie. Disponible en français, anglais, espagnol, arabe et darija — 100 MAD.", 100],
        ["L'Effet d'Amour", 'Ash Oulbig', "Roman — ce que Béa m'a appris. Disponible en français, anglais, espagnol, arabe et darija — 70 MAD.", 70],
    ];
    foreach ($books as $i => $b) {
        $exists = get_posts(['post_type' => 'abs_ebook', 'title' => $b[0], 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids']);
        if ($exists) continue;
        $id = wp_insert_post([
            'post_type' => 'abs_ebook', 'post_status' => 'publish', 'post_title' => $b[0],
            'post_content' => $b[2], 'post_excerpt' => $b[1] . ' · ' . $b[3] . ' MAD',
            'post_date' => current_time('mysql'),
        ]);
        if ($id && !is_wp_error($id)) {
            update_post_meta($id, 'abs_author', $b[1]);
            update_post_meta($id, 'abs_educaplat', $educaplat);
            update_post_meta($id, '_oulbig_book', 1);
            $log[] = 'ebook ajouté : ' . $b[0];
        }
    }

    // 4. Liens d'achat officiels (Gumroad + Books2Read) sur les deux livres.
    foreach (['intro' => 'Introduction : 37 étapes + 1', 'roman' => "L'Effet d'Amour"] as $key => $title) {
        $ids = get_posts(['post_type' => 'abs_ebook', 'title' => $title, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids']);
        if (!$ids || !function_exists('oulbig_book_buy_html')) continue;
        $id = $ids[0];
        update_post_meta($id, 'abs_gumroad', oulbig_book_link($key, 'FR'));
        update_post_meta($id, 'abs_author', 'OULBIG');
        $c = get_post_field('post_content', $id);
        $c = preg_replace('~<h3>Acheter le livre</h3><ul class="oulbig-buy">.*?</ul>~s', '', $c);
        wp_update_post(['ID' => $id, 'post_content' => trim($c) . "\n\n" . oulbig_book_buy_html($key)]);
        $log[] = 'liens d\'achat : ' . $title;
    }

    update_option('oulbig_absp_cleanup', OULBIG_ABSP_CLEANUP);
    update_option('oulbig_absp_cleanup_log', $log, false);
}
add_action('init', 'oulbig_absp_cleanup', 40);
