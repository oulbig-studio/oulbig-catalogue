<?php
/**
 * Back-office des chansons : liens audio/vidéo, paroles (originale + 4 traductions),
 * titres traduits, compteurs.
 */
defined( 'ABSPATH' ) || exit;

function mcl_song_fields() {
	$f = array( '_mcl_artist', '_mcl_duration', '_mcl_audio_url', '_mcl_video_url', '_mcl_lyrics', '_mcl_lyrics_lang' );
	foreach ( array_keys( mcl_langs() ) as $l ) {
		$f[] = '_mcl_lyrics_' . $l;
		$f[] = '_mcl_title_' . $l;
	}
	return $f;
}

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'mcl_song_media', '🎵 Médias (liens audio & vidéo)', 'mcl_box_media', 'mcl_song', 'normal', 'high' );
	add_meta_box( 'mcl_song_lyrics', '📝 Paroles', 'mcl_box_lyrics', 'mcl_song', 'normal', 'high' );
	add_meta_box( 'mcl_song_i18n', '🌍 Titre traduit (facultatif)', 'mcl_box_i18n', 'mcl_song', 'normal', 'default' );
	add_meta_box( 'mcl_song_stats', '🏆 Hit-parade', 'mcl_box_stats', 'mcl_song', 'side', 'default' );
} );

function mcl_box_media( $post ) {
	wp_nonce_field( 'mcl_song_save', 'mcl_song_nonce' );
	$g = function ( $k ) use ( $post ) {
		return get_post_meta( $post->ID, $k, true );
	};
	?>
	<div class="mcl-fields">
		<p class="mcl-row2">
			<label>Artiste / interprète<input type="text" name="_mcl_artist" value="<?php echo esc_attr( $g( '_mcl_artist' ) ); ?>" placeholder="Ex. : OULBIG"></label>
			<label>Durée<input type="text" name="_mcl_duration" value="<?php echo esc_attr( $g( '_mcl_duration' ) ); ?>" placeholder="3:45"></label>
		</p>
		<p>
			<label>🎧 Lien audio<span class="mcl-inline">
				<input type="url" name="_mcl_audio_url" id="mcl_audio_url" value="<?php echo esc_attr( $g( '_mcl_audio_url' ) ); ?>" placeholder="https://… (MP3, SoundCloud, Spotify, Deezer, Audiomack, YouTube…)">
				<button type="button" class="button mcl-pick" data-target="mcl_audio_url" data-type="audio">Médiathèque</button></span></label>
			<small>Un fichier MP3 / M4A / WAV affiche le lecteur maison Musicology. Les autres liens s'intègrent automatiquement.</small>
		</p>
		<p>
			<label>🎬 Lien vidéo / clip<span class="mcl-inline">
				<input type="url" name="_mcl_video_url" id="mcl_video_url" value="<?php echo esc_attr( $g( '_mcl_video_url' ) ); ?>" placeholder="https://youtube.com/watch?v=… (YouTube, Shorts, Vimeo, TikTok, Dailymotion, Google Drive, MP4…)">
				<button type="button" class="button mcl-pick" data-target="mcl_video_url" data-type="video">Médiathèque</button></span></label>
			<small>Un titre avec un lien vidéo apparaît dans « Clips » et dans le Top clips. L'image mise en avant sert de pochette (sinon la miniature YouTube).</small>
		</p>
	</div>
	<?php
}

function mcl_box_lyrics( $post ) {
	$orig_lang = get_post_meta( $post->ID, '_mcl_lyrics_lang', true );
	?>
	<div class="mcl-fields">
		<p class="mcl-row2">
			<label>Langue des paroles originales
				<select name="_mcl_lyrics_lang">
					<option value="">—</option>
					<?php foreach ( mcl_options( 'song_lang' ) as $k => $labels ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $orig_lang, $k ); ?>><?php echo esc_html( $labels[0] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</p>
		<div class="mcl-tabs-admin">
			<nav>
				<a href="#" class="is-active" data-tab="orig">Originales</a>
				<?php foreach ( mcl_langs() as $c => $l ) : ?>
					<a href="#" data-tab="<?php echo esc_attr( $c ); ?>">Traduction <?php echo esc_html( $l['short'] ); ?></a>
				<?php endforeach; ?>
			</nav>
			<div class="mcl-tab is-active" data-tab="orig"><textarea name="_mcl_lyrics" rows="14" placeholder="Paroles dans la langue chantée. Laissez une ligne vide entre les couplets. [Refrain] est mis en valeur."><?php echo esc_textarea( get_post_meta( $post->ID, '_mcl_lyrics', true ) ); ?></textarea></div>
			<?php foreach ( mcl_langs() as $c => $l ) : ?>
				<div class="mcl-tab" data-tab="<?php echo esc_attr( $c ); ?>"><textarea name="_mcl_lyrics_<?php echo esc_attr( $c ); ?>" rows="14" <?php echo 'ar' === $c ? 'dir="rtl"' : ''; ?> placeholder="Traduction <?php echo esc_attr( $l['label'] ); ?> (facultatif)"><?php echo esc_textarea( get_post_meta( $post->ID, '_mcl_lyrics_' . $c, true ) ); ?></textarea></div>
			<?php endforeach; ?>
		</div>
		<small>Sur le site, les visiteurs basculent entre l'original et les traductions remplies. La traduction dans la langue du visiteur s'ouvre en premier si elle existe.</small>
	</div>
	<?php
}

function mcl_box_i18n( $post ) {
	echo '<div class="mcl-fields"><p class="mcl-row4">';
	foreach ( mcl_langs() as $c => $l ) {
		printf(
			'<label>%1$s<input type="text" name="_mcl_title_%2$s" value="%3$s" %4$s></label>',
			esc_html( $l['label'] ),
			esc_attr( $c ),
			esc_attr( get_post_meta( $post->ID, '_mcl_title_' . $c, true ) ),
			'ar' === $c ? 'dir="rtl"' : ''
		);
	}
	echo '</p><small>Vide = le titre principal est utilisé.</small></div>';
}

function mcl_box_stats( $post ) {
	?>
	<p><label>🎧 Écoutes audio (total)<br><input type="number" min="0" name="_mcl_plays_audio" value="<?php echo (int) get_post_meta( $post->ID, '_mcl_plays_audio', true ); ?>" class="widefat"></label></p>
	<p><label>🎬 Vues clip (total)<br><input type="number" min="0" name="_mcl_plays_video" value="<?php echo (int) get_post_meta( $post->ID, '_mcl_plays_video', true ); ?>" class="widefat"></label></p>
	<p class="description">Ce mois-ci : <?php echo (int) mcl_plays( $post->ID, 'audio', 'month' ); ?> écoutes · <?php echo (int) mcl_plays( $post->ID, 'video', 'month' ); ?> vues.<br>Les compteurs montent automatiquement à chaque lecture.</p>
	<?php
}

add_action( 'save_post_mcl_song', function ( $post_id ) {
	if ( ! isset( $_POST['mcl_song_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mcl_song_nonce'] ) ), 'mcl_song_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( mcl_song_fields() as $k ) {
		if ( ! isset( $_POST[ $k ] ) ) {
			continue;
		}
		$v = wp_unslash( $_POST[ $k ] );
		if ( false !== strpos( $k, '_url' ) ) {
			$v = esc_url_raw( trim( $v ) );
		} elseif ( 0 === strpos( $k, '_mcl_lyrics' ) && '_mcl_lyrics_lang' !== $k ) {
			$v = sanitize_textarea_field( $v );
		} else {
			$v = sanitize_text_field( $v );
		}
		update_post_meta( $post_id, $k, $v );
	}
	foreach ( array( '_mcl_plays_audio', '_mcl_plays_video' ) as $k ) {
		if ( isset( $_POST[ $k ] ) ) {
			update_post_meta( $post_id, $k, absint( $_POST[ $k ] ) );
		}
	}
} );

/* Colonnes de la liste */
add_filter( 'manage_mcl_song_posts_columns', function ( $c ) {
	$new = array();
	foreach ( $c as $k => $v ) {
		if ( 'title' === $k ) {
			$new['mcl_cover'] = '';
		}
		$new[ $k ] = $v;
		if ( 'title' === $k ) {
			$new['mcl_artist'] = 'Artiste';
			$new['mcl_media']  = 'Médias';
			$new['mcl_plays']  = 'Écoutes / Vues';
		}
	}
	return $new;
} );

add_action( 'manage_mcl_song_posts_custom_column', function ( $col, $id ) {
	switch ( $col ) {
		case 'mcl_cover':
			$u = mcl_cover_url( $id, 'thumbnail' );
			echo $u ? '<img src="' . esc_url( $u ) . '" style="width:44px;height:44px;object-fit:cover;border-radius:8px">' : '<span style="display:inline-block;width:44px;height:44px;border-radius:8px;background:#2a211c"></span>';
			break;
		case 'mcl_artist':
			echo esc_html( get_post_meta( $id, '_mcl_artist', true ) );
			break;
		case 'mcl_media':
			echo mcl_has_audio( $id ) ? '🎧 ' : '';
			echo mcl_has_video( $id ) ? '🎬 ' : '';
			echo get_post_meta( $id, '_mcl_lyrics', true ) ? '📝' : '';
			break;
		case 'mcl_plays':
			echo (int) get_post_meta( $id, '_mcl_plays_audio', true ) . ' / ' . (int) get_post_meta( $id, '_mcl_plays_video', true );
			break;
	}
}, 10, 2 );

add_filter( 'manage_edit-mcl_song_sortable_columns', function ( $c ) {
	$c['mcl_plays'] = 'mcl_plays';
	return $c;
} );
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && 'mcl_plays' === $q->get( 'orderby' ) ) {
		$q->set( 'meta_key', '_mcl_plays_audio' );
		$q->set( 'orderby', 'meta_value_num' );
	}
} );

/* Styles + scripts admin */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array( 'mcl_song', 'mcl_request' ), true ) ) {
		return;
	}
	if ( 'mcl_song' === $screen->post_type ) {
		wp_enqueue_media();
	}
	wp_enqueue_style( 'mcl-admin', MCL_URI . '/assets/admin.css', array(), MCL_VERSION );
	wp_enqueue_script( 'mcl-admin', MCL_URI . '/assets/js/admin.js', array( 'jquery' ), MCL_VERSION, true );
} );
