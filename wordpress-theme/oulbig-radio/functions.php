<?php
/**
 * OULBIG Radio — thème WordPress v2.0.
 *
 * @package OULBIG_Radio
 */

defined( 'ABSPATH' ) || exit;

define( 'ORAD_VERSION', '2.0.8' );

require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/rest.php';
require_once __DIR__ . '/inc/admin.php';
require_once __DIR__ . '/inc/mail.php';
require_once __DIR__ . '/inc/signup.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
} );

/* Manifeste d'application (installation sur téléphone) : /?oulbig_manifest=1 */
add_action( 'init', function () {
	if ( empty( $_GET['oulbig_manifest'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$clair = 'clair' === orad_opt( 'skin' );
	$bg    = $clair ? '#F6F4FB' : '#0B0916';
	header( 'Content-Type: application/manifest+json; charset=utf-8' );
	echo wp_json_encode( array(
		'name'             => 'OULBIG Radio',
		'short_name'       => 'OULBIG Radio',
		'description'      => "La web radio d'OULBIG en 5 langues.",
		'start_url'        => home_url( '/' ),
		'scope'            => home_url( '/' ),
		'display'          => 'standalone',
		'orientation'      => 'portrait',
		'background_color' => $bg,
		'theme_color'      => $bg,
		'id'               => home_url( '/' ),
		'lang'             => 'fr',
		'categories'       => array( 'music', 'entertainment' ),
		'icons'            => array(
			array( 'src' => get_template_directory_uri() . '/assets/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' ),
			array( 'src' => get_template_directory_uri() . '/assets/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' ),
			array( 'src' => get_template_directory_uri() . '/assets/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable' ),
			array( 'src' => get_template_directory_uri() . '/assets/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml' ),
		),
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
} );

/* Service worker (application installable, page disponible hors ligne) : /?oulbig_sw=<version>
 * Portée : tout le site. Ne touche ni à l'administration, ni à la connexion, ni à l'API. */
add_action( 'init', function () {
	if ( empty( $_GET['oulbig_sw'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	nocache_headers();
	header( 'Content-Type: application/javascript; charset=utf-8' );
	header( 'Service-Worker-Allowed: /' );
	$cache = 'orad-' . preg_replace( '/[^0-9.]/', '', ORAD_VERSION );
	echo "const C='" . esc_js( $cache ) . "',HOME='" . esc_js( home_url( '/' ) ) . "';\n";
	echo <<<'JS'
self.addEventListener('install',(e)=>{self.skipWaiting();});
self.addEventListener('activate',(e)=>{e.waitUntil(caches.keys().then((ks)=>Promise.all(ks.filter((k)=>k.startsWith('orad-')&&k!==C).map((k)=>caches.delete(k)))).then(()=>self.clients.claim()));});
self.addEventListener('fetch',(e)=>{
  const r=e.request,u=new URL(r.url);
  if(r.method!=='GET'||u.origin!==self.location.origin)return;
  if(/\/(wp-admin|wp-login\.php|wp-json|wp-cron\.php)/.test(u.pathname)||u.searchParams.has('rest_route')||u.searchParams.has('oulbig_sw'))return;
  if(r.mode==='navigate'){
    e.respondWith(fetch(r).then((res)=>{if(res.ok&&u.pathname==='/'){const cp=res.clone();caches.open(C).then((c)=>c.put(HOME,cp));}return res;})
      .catch(()=>caches.match(HOME).then((m)=>m||new Response('<h1>OULBIG Radio</h1><p>Hors ligne / Offline</p>',{headers:{'Content-Type':'text/html; charset=utf-8'}}))));
    return;
  }
  if(u.pathname.indexOf('/wp-content/themes/')!==-1){
    e.respondWith(caches.open(C).then((c)=>c.match(r).then((m)=>{const f=fetch(r).then((res)=>{if(res.ok)c.put(r,res.clone());return res;}).catch(()=>m);return m||f;})));
  }
});
JS;
	exit;
} );

/* La page radio n'a pas besoin des styles de blocs WordPress */
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 100 );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
