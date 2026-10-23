<?php
/**
 * Compteur d'écoutes (audio) et de vues (clips) + requêtes du hit-parade.
 * Méta : _mcl_plays_audio, _mcl_plays_video (total) et _mcl_pa_AAAAMM / _mcl_pv_AAAAMM (mois).
 */
defined( 'ABSPATH' ) || exit;

function mcl_month_key( $kind ) {
	return ( 'audio' === $kind ? '_mcl_pa_' : '_mcl_pv_' ) . gmdate( 'Ym' );
}

function mcl_plays( $post_id, $kind, $period = 'all' ) {
	$key = 'month' === $period ? mcl_month_key( $kind ) : '_mcl_plays_' . $kind;
	return (int) get_post_meta( $post_id, $key, true );
}

function mcl_ajax_play() {
	check_ajax_referer( 'mcl_play', 'nonce' );
	$id   = isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0;
	$kind = isset( $_POST['kind'] ) && 'audio' === $_POST['kind'] ? 'audio' : 'video';
	if ( ! $id || 'mcl_song' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
		wp_send_json_error( null, 400 );
	}
	// Anti-gonflage : 1 écoute comptée par visiteur / titre / type toutes les 30 min.
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$ua  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 120 ) : '';
	$key = 'mcl_p_' . md5( $ip . $ua . $id . $kind );
	if ( get_transient( $key ) ) {
		wp_send_json_success( array( 'counted' => false, 'plays' => mcl_plays( $id, $kind ) ) );
	}
	set_transient( $key, 1, 30 * MINUTE_IN_SECONDS );

	$total = mcl_plays( $id, $kind ) + 1;
	update_post_meta( $id, '_mcl_plays_' . $kind, $total );
	$mk = mcl_month_key( $kind );
	update_post_meta( $id, $mk, (int) get_post_meta( $id, $mk, true ) + 1 );
	wp_send_json_success( array( 'counted' => true, 'plays' => $total ) );
}
add_action( 'wp_ajax_mcl_play', 'mcl_ajax_play' );
add_action( 'wp_ajax_nopriv_mcl_play', 'mcl_ajax_play' );

/**
 * Classement.
 * @param string $kind   audio|video
 * @param string $period all|month
 */
function mcl_chart( $kind = 'audio', $period = 'all', $limit = 20 ) {
	$key = 'month' === $period ? mcl_month_key( $kind ) : '_mcl_plays_' . $kind;
	return new WP_Query( array(
		'post_type'           => 'mcl_song',
		'posts_per_page'      => $limit,
		'meta_key'            => $key,
		'orderby'             => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'meta_query'          => array(
			'relation' => 'AND',
			array( 'key' => $key, 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC' ),
			array( 'key' => '_mcl_' . $kind . '_url', 'value' => '', 'compare' => '!=' ),
		),
	) );
}

/** Affiche une liste de classement. */
function mcl_render_chart( $kind, $period, $limit = 20, $compact = false ) {
	$q = mcl_chart( $kind, $period, $limit );
	if ( ! $q->have_posts() ) {
		echo '<p class="mcl-empty">' . esc_html( mcl_t( 'hit_empty' ) ) . '</p>';
		return;
	}
	echo '<ol class="mcl-chart' . ( $compact ? ' is-compact' : '' ) . '">';
	$rank = 0;
	while ( $q->have_posts() ) {
		$q->the_post();
		$rank++;
		$id    = get_the_ID();
		$cover = mcl_cover_url( $id );
		$n     = mcl_plays( $id, $kind, $period );
		?>
		<li class="mcl-chart__row<?php echo $rank <= 3 ? ' is-top' : ''; ?>">
			<span class="mcl-chart__rank"><?php echo (int) $rank; ?></span>
			<a class="mcl-chart__cover" href="<?php the_permalink(); ?>#<?php echo esc_attr( $kind ); ?>">
				<?php echo $cover ? '<img src="' . esc_url( $cover ) . '" alt="" loading="lazy">' : mcl_placeholder_cover( $id ); // phpcs:ignore ?>
				<span class="mcl-chart__play"><?php echo mcl_icon( 'play' ); // phpcs:ignore ?></span>
			</a>
			<span class="mcl-chart__info">
				<a href="<?php the_permalink(); ?>" class="mcl-chart__title"><?php echo esc_html( mcl_song_title( $id ) ); ?></a>
				<span class="mcl-chart__artist"><?php echo esc_html( mcl_song_artist( $id ) ); ?><?php $g = mcl_song_genre( $id ); echo $g ? ' · ' . esc_html( $g ) : ''; ?></span>
			</span>
			<span class="mcl-chart__count"><?php echo mcl_icon( 'audio' === $kind ? 'audio' : 'video' ); // phpcs:ignore ?><?php echo esc_html( mcl_num( $n ) ); ?></span>
		</li>
		<?php
	}
	echo '</ol>';
	wp_reset_postdata();
}
