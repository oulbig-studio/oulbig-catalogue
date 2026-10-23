<?php
/**
 * Services (distribution, covers, production, studio, lyrics video…) :
 * une page par service + formulaire de commande enregistré dans « Demandes musique ».
 */
defined( 'ABSPATH' ) || exit;

require MCL_DIR . '/inc/services-data.php';

function mcl_service_fields() {
	// champ => [libellé admin, type]
	return array(
		'tagline'      => array( 'Accroche', 'text' ),
		'price_label'  => array( 'Prix affiché (remplace le prix, ex. « Sur devis », « 200 DH / an »)', 'text' ),
		'badge'        => array( 'Badge (ex. « Le plus demandé »)', 'text' ),
		'features'     => array( 'Ce qui est inclus (une ligne par point)', 'lines' ),
		'options'      => array( 'Formules au choix (une par ligne, ex. « Sans distribution — 700 DH »)', 'lines' ),
		'requirements' => array( 'À fournir pour l\'enregistrement à distance (une ligne par fichier)', 'lines' ),
	);
}

add_action( 'init', function () {
	register_post_type( 'mcl_service', array(
		'labels'        => array(
			'name'          => 'Services',
			'singular_name' => 'Service',
			'add_new'       => 'Ajouter un service',
			'add_new_item'  => 'Ajouter un service',
			'edit_item'     => 'Modifier le service',
			'all_items'     => 'Tous les services',
		),
		'public'        => true,
		'has_archive'   => true,
		'rewrite'       => array( 'slug' => 'services', 'with_front' => false ),
		'menu_icon'     => 'dashicons-store',
		'menu_position' => 6,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		'show_in_rest'  => true,
	) );
} );

add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() && $q->is_main_query() && $q->is_post_type_archive( 'mcl_service' ) ) {
		$q->set( 'posts_per_page', -1 );
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}
} );

/* ---------- Lecture traduite ---------- */
function mcl_sv( $id, $field, $lang = null ) {
	$lang = $lang ?: mcl_lang();
	$v    = get_post_meta( $id, '_mcl_s_' . $field . '_' . $lang, true );
	if ( '' === $v && 'fr' !== $lang ) {
		$v = get_post_meta( $id, '_mcl_s_' . $field . '_fr', true );
	}
	if ( '' === $v ) {
		if ( 'title' === $field ) {
			$v = get_the_title( $id );
		} elseif ( 'content' === $field ) {
			$v = get_post_field( 'post_content', $id );
		}
	}
	return (string) $v;
}

function mcl_sv_lines( $id, $field, $lang = null ) {
	return array_values( array_filter( array_map( 'trim', preg_split( "/\r?\n/", mcl_sv( $id, $field, $lang ) ) ) ) );
}

function mcl_service_price( $id ) {
	$label = mcl_sv( $id, 'price_label' );
	return $label ? $label : (string) get_post_meta( $id, '_mcl_price', true );
}

function mcl_service_icon( $id ) {
	$i = get_post_meta( $id, '_mcl_icon', true );
	return $i ? $i : '🎵';
}

function mcl_services_url() {
	return get_post_type_archive_link( 'mcl_service' );
}

function mcl_service_whatsapp( $id ) {
	return mcl_whatsapp_url( sprintf( mcl_t( 'wa_service_msg' ), mcl_sv( $id, 'title' ) ) );
}

/* ---------- Admin : métadonnées ---------- */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'mcl_service_main', '💰 Prix & icône', 'mcl_box_service_main', 'mcl_service', 'side', 'high' );
	add_meta_box( 'mcl_service_i18n', '🌍 Contenu du service (4 langues)', 'mcl_box_service_i18n', 'mcl_service', 'normal', 'high' );
} );

function mcl_box_service_main( $post ) {
	wp_nonce_field( 'mcl_service_save', 'mcl_service_nonce' );
	?>
	<p><label><strong>Prix</strong><br><input type="text" class="widefat" name="_mcl_price" value="<?php echo esc_attr( get_post_meta( $post->ID, '_mcl_price', true ) ); ?>" placeholder="500 DH"></label></p>
	<p><label><strong>Icône (emoji)</strong><br><input type="text" class="widefat" name="_mcl_icon" value="<?php echo esc_attr( get_post_meta( $post->ID, '_mcl_icon', true ) ); ?>" placeholder="🎙️"></label></p>
	<p class="description">L'ordre d'affichage se règle dans « Attributs → Ordre ».</p>
	<?php
}

function mcl_box_service_i18n( $post ) {
	?>
	<div class="mcl-fields mcl-tabs-admin">
		<nav>
			<?php $first = true; foreach ( mcl_langs() as $c => $l ) : ?>
				<a href="#" class="<?php echo $first ? 'is-active' : ''; ?>" data-tab="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $l['label'] ); ?></a>
			<?php $first = false; endforeach; ?>
		</nav>
		<?php $first = true; foreach ( mcl_langs() as $c => $l ) : $dir = 'ar' === $c ? ' dir="rtl"' : ''; ?>
			<div class="mcl-tab<?php echo $first ? ' is-active' : ''; ?>" data-tab="<?php echo esc_attr( $c ); ?>" style="padding-top:12px">
				<?php if ( 'fr' !== $c ) : ?>
					<p><label>Titre<input type="text" name="_mcl_s_title_<?php echo esc_attr( $c ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, '_mcl_s_title_' . $c, true ) ); ?>"<?php echo $dir; // phpcs:ignore ?>></label></p>
					<p><label>Description<textarea rows="3" name="_mcl_s_content_<?php echo esc_attr( $c ); ?>"<?php echo $dir; // phpcs:ignore ?>><?php echo esc_textarea( get_post_meta( $post->ID, '_mcl_s_content_' . $c, true ) ); ?></textarea></label></p>
				<?php else : ?>
					<p class="description">Français : le titre et la description sont ceux de l'éditeur WordPress ci-dessus.</p>
				<?php endif; ?>
				<?php foreach ( mcl_service_fields() as $f => $def ) : $k = '_mcl_s_' . $f . '_' . $c; ?>
					<p><label><?php echo esc_html( $def[0] ); ?>
						<?php if ( 'lines' === $def[1] ) : ?>
							<textarea rows="4" name="<?php echo esc_attr( $k ); ?>"<?php echo $dir; // phpcs:ignore ?>><?php echo esc_textarea( get_post_meta( $post->ID, $k, true ) ); ?></textarea>
						<?php else : ?>
							<input type="text" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, $k, true ) ); ?>"<?php echo $dir; // phpcs:ignore ?>>
						<?php endif; ?>
					</label></p>
				<?php endforeach; ?>
			</div>
		<?php $first = false; endforeach; ?>
		<small>Une langue laissée vide reprend automatiquement le texte français.</small>
	</div>
	<?php
}

add_action( 'save_post_mcl_service', function ( $post_id ) {
	if ( ! isset( $_POST['mcl_service_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mcl_service_nonce'] ) ), 'mcl_service_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array( '_mcl_price', '_mcl_icon' ) as $k ) {
		if ( isset( $_POST[ $k ] ) ) {
			update_post_meta( $post_id, $k, sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) );
		}
	}
	$fields = array_merge( array( 'title' => array( '', 'text' ), 'content' => array( '', 'lines' ) ), mcl_service_fields() );
	foreach ( array_keys( mcl_langs() ) as $c ) {
		foreach ( $fields as $f => $def ) {
			$k = '_mcl_s_' . $f . '_' . $c;
			if ( isset( $_POST[ $k ] ) ) {
				$v = wp_unslash( $_POST[ $k ] );
				update_post_meta( $post_id, $k, 'lines' === $def[1] ? sanitize_textarea_field( $v ) : sanitize_text_field( $v ) );
			}
		}
	}
} );

add_filter( 'manage_mcl_service_posts_columns', function ( $c ) {
	return array( 'cb' => $c['cb'], 'mcl_icon' => '', 'title' => 'Service', 'mcl_price' => 'Prix', 'menu_order' => 'Ordre', 'date' => $c['date'] );
} );
add_action( 'manage_mcl_service_posts_custom_column', function ( $col, $id ) {
	if ( 'mcl_icon' === $col ) {
		echo '<span style="font-size:24px">' . esc_html( mcl_service_icon( $id ) ) . '</span>';
	} elseif ( 'mcl_price' === $col ) {
		echo esc_html( mcl_service_price( $id ) );
	} elseif ( 'menu_order' === $col ) {
		echo (int) get_post_field( 'menu_order', $id );
	}
}, 10, 2 );
add_action( 'admin_enqueue_scripts', function () {
	$s = get_current_screen();
	if ( $s && in_array( $s->post_type, array( 'mcl_service', 'post' ), true ) ) {
		wp_enqueue_style( 'mcl-admin', MCL_URI . '/assets/admin.css', array(), MCL_VERSION );
		wp_enqueue_script( 'mcl-admin', MCL_URI . '/assets/js/admin.js', array( 'jquery' ), MCL_VERSION, true );
	}
} );

/* ---------- Commande d'un service (AJAX) ---------- */
function mcl_submit_order() {
	$lang = isset( $_POST['lang'] ) && mcl_valid_lang( sanitize_key( $_POST['lang'] ) ) ? sanitize_key( $_POST['lang'] ) : 'fr';
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'mcl_order' ) ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_server' ) . ' (nonce)' ), 403 );
	}
	if ( ! empty( $_POST['website'] ) ) {
		wp_send_json_success( array( 'ref' => 'MCL-00000' ) );
	}
	$started = isset( $_POST['started'] ) ? (int) $_POST['started'] : 0;
	if ( $started && ( time() - $started ) < 4 ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_server' ) ), 429 );
	}
	$p   = wp_unslash( $_POST );
	$sid = isset( $p['service'] ) ? absint( $p['service'] ) : 0;
	if ( ! $sid || 'mcl_service' !== get_post_type( $sid ) ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_server' ) ), 400 );
	}
	$opts_fr = mcl_sv_lines( $sid, 'options', 'fr' );
	$opt_i   = isset( $p['option'] ) && '' !== $p['option'] ? (int) $p['option'] : -1;
	$d = array(
		'type'      => 'service',
		'form_lang' => $lang,
		'service'   => get_the_title( $sid ),
		'option'    => isset( $opts_fr[ $opt_i ] ) ? $opts_fr[ $opt_i ] : '',
		'name'      => isset( $p['name'] ) ? mb_substr( sanitize_text_field( $p['name'] ), 0, 120 ) : '',
		'email'     => isset( $p['email'] ) ? sanitize_email( $p['email'] ) : '',
		'phone'     => isset( $p['phone'] ) ? mb_substr( sanitize_text_field( $p['phone'] ), 0, 40 ) : '',
		'city'      => isset( $p['city'] ) ? mb_substr( sanitize_text_field( $p['city'] ), 0, 120 ) : '',
		'files'     => isset( $p['files'] ) ? mb_substr( sanitize_textarea_field( $p['files'] ), 0, 1000 ) : '',
		'details'   => isset( $p['details'] ) ? mb_substr( sanitize_textarea_field( $p['details'] ), 0, 4000 ) : '',
	);
	if ( '' === $d['name'] ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_required' ), 'fields' => array( 'name' => 1 ) ), 422 );
	}
	if ( '' === $d['email'] && '' === $d['phone'] ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_contact' ), 'fields' => array( 'email' => 1 ) ), 422 );
	}
	if ( ! empty( $p['email'] ) && ! is_email( $d['email'] ) ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_email' ), 'fields' => array( 'email' => 1 ) ), 422 );
	}
	if ( $opts_fr && -1 === $opt_i ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_choose' ), 'fields' => array( 'option' => 1 ) ), 422 );
	}
	$post_id = wp_insert_post( array(
		'post_type'   => 'mcl_request',
		'post_status' => 'publish',
		'post_title'  => $d['name'] . ' — ' . $d['service'],
	), true );
	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_server' ) ), 500 );
	}
	foreach ( $d as $k => $v ) {
		update_post_meta( $post_id, '_mcl_r_' . $k, $v );
	}
	update_post_meta( $post_id, '_mcl_status', 'new' );
	mcl_mail_request( $post_id );
	wp_send_json_success( array( 'ref' => mcl_request_ref( $post_id ) ) );
}
add_action( 'wp_ajax_mcl_order', 'mcl_submit_order' );
add_action( 'wp_ajax_nopriv_mcl_order', 'mcl_submit_order' );

/* ---------- Création / mise à jour des services de départ ---------- */
function mcl_service_apply( $id, $s ) {
	$langs = array( 'fr', 'en', 'es', 'ar' );
	update_post_meta( $id, '_mcl_price', $s['price'] );
	update_post_meta( $id, '_mcl_icon', $s['icon'] );
	foreach ( array( 'title', 'content', 'tagline', 'price_label', 'badge', 'features', 'options', 'requirements' ) as $f ) {
		foreach ( $langs as $i => $l ) {
			if ( 'fr' === $l && in_array( $f, array( 'title', 'content' ), true ) ) {
				continue;
			}
			update_post_meta( $id, '_mcl_s_' . $f . '_' . $l, isset( $s[ $f ][ $i ] ) ? $s[ $f ][ $i ] : '' );
		}
	}
}

function mcl_seed_services() {
	foreach ( mcl_services_seed() as $s ) {
		if ( get_page_by_path( $s['slug'], OBJECT, 'mcl_service' ) ) {
			continue;
		}
		// Ancien service remplacé (ex. « Cover en studio » → « Cover à distance ») : mis à jour sur place.
		$old = ! empty( $s['old_slug'] ) ? get_page_by_path( $s['old_slug'], OBJECT, 'mcl_service' ) : null;
		$data = array(
			'post_type'    => 'mcl_service',
			'post_status'  => 'publish',
			'post_title'   => $s['title'][0],
			'post_name'    => $s['slug'],
			'post_content' => $s['content'][0],
			'menu_order'   => $s['order'],
		);
		if ( $old ) {
			$data['ID'] = $old->ID;
			$id         = wp_update_post( $data );
		} else {
			$id = wp_insert_post( $data );
		}
		if ( $id && ! is_wp_error( $id ) ) {
			mcl_service_apply( $id, $s );
		}
	}
}
