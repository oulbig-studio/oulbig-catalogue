<?php
/**
 * Musicology — thème WordPress de chansons, clips, paroles, hit-parade
 * et demandes de musique personnalisée (FR / EN / ES / AR).
 */
defined( 'ABSPATH' ) || exit;

define( 'MCL_VERSION', '1.4.1' );
define( 'MCL_DIR', get_template_directory() );
define( 'MCL_URI', get_template_directory_uri() );

require MCL_DIR . '/inc/settings.php';
require MCL_DIR . '/inc/i18n.php';
require MCL_DIR . '/inc/setup.php';
require MCL_DIR . '/inc/media.php';
require MCL_DIR . '/inc/plays.php';
require MCL_DIR . '/inc/song-admin.php';
require MCL_DIR . '/inc/requests.php';
require MCL_DIR . '/inc/requests-admin.php';
require MCL_DIR . '/inc/services.php';
require MCL_DIR . '/inc/blog.php';
require MCL_DIR . '/inc/audiobooks.php';
require MCL_DIR . '/inc/sync.php';
require MCL_DIR . '/inc/seed.php';
