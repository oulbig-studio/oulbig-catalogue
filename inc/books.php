<?php
/** Liens d'achat officiels des livres OULBIG, par langue (Gumroad + Books2Read). */
if (!defined('ABSPATH')) exit;

function oulbig_book_stores() {
    return apply_filters('oulbig_book_stores', [
        'intro' => [
            'FR' => ['gumroad' => 'https://oulbig.gumroad.com/l/IntroductionLedbutduvoyagedelascension'],
            'EN' => ['gumroad' => 'https://oulbig.gumroad.com/l/IntroductionTheBeginningoftheJourneyofAscent?wanted=true', 'books2read' => 'https://books2read.com/u/boLM50'],
            'ES' => ['gumroad' => 'https://oulbig.gumroad.com/l/IntroduccinElcomienzodelviajedelaascensin', 'books2read' => 'https://books2read.com/u/4Andrk'],
            'AR' => ['gumroad' => 'https://oulbig.gumroad.com/l/Muqaddima_Bidayat_Rihlat_Al-Irtiqa'],
            'DA' => ['gumroad' => 'https://oulbig.gumroad.com/l/MuqaddimabidayadyalRihlatTlou3'],
            '*'  => ['books2read' => 'https://books2read.com/u/brDMxM'],
        ],
        'roman' => [
            'FR' => ['gumroad' => 'https://oulbig.gumroad.com/l/LEffetdAmour', 'books2read' => 'https://books2read.com/u/boLdZL'],
            'EN' => ['gumroad' => 'https://oulbig.gumroad.com/l/TheLoveEffect', 'books2read' => 'https://books2read.com/u/b61Yxp'],
            'ES' => ['books2read' => 'https://books2read.com/u/4jlGeD'],
            'AR' => ['books2read' => 'https://books2read.com/u/mgVD0R'],
            'DA' => ['books2read' => 'https://books2read.com/u/4NY5JJ'],
        ],
    ]);
}

/** Lien principal d'une édition : Gumroad d'abord, sinon Books2Read. */
function oulbig_book_link($book, $lang) {
    $s = oulbig_book_stores()[$book][$lang] ?? [];
    return $s['gumroad'] ?? ($s['books2read'] ?? '');
}

/** Liste HTML « Acheter » par langue, pour le contenu d'une fiche. */
function oulbig_book_buy_html($book) {
    $names = ['FR' => 'Français', 'EN' => 'English', 'ES' => 'Español', 'AR' => 'العربية', 'DA' => 'Darija', '*' => 'Autres boutiques'];
    $h = '<h3>Acheter le livre</h3><ul class="oulbig-buy">';
    foreach (oulbig_book_stores()[$book] as $lang => $links) {
        $a = [];
        if (!empty($links['gumroad']))    $a[] = '<a href="' . esc_url($links['gumroad']) . '" target="_blank" rel="noopener">Gumroad</a>';
        if (!empty($links['books2read'])) $a[] = '<a href="' . esc_url($links['books2read']) . '" target="_blank" rel="noopener">' . ($lang === '*' ? 'Books2Read (toutes les librairies)' : 'Books2Read (Apple Books, Kobo, Google Play…)') . '</a>';
        $h .= '<li><strong>' . esc_html($names[$lang]) . '</strong> : ' . implode(' · ', $a) . '</li>';
    }
    return $h . '</ul>';
}
