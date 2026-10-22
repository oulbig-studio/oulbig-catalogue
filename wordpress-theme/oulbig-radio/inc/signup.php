<?php
/**
 * OULBIG Radio — inscription sans e-mail : l'auditeur choisit son mot de passe
 * dans le formulaire, son compte est créé (rôle Abonné) et il est connecté
 * directement à la radio. Aucun e-mail n'est nécessaire pour s'inscrire.
 *
 * @package OULBIG_Radio
 */

defined( 'ABSPATH' ) || exit;

/** Petits libellés selon la langue choisie sur la page de connexion. */
function orad_signup_txt( $key ) {
	$l = substr( function_exists( 'determine_locale' ) ? determine_locale() : get_locale(), 0, 2 );
	$t = array(
		'fr' => array( 'pass' => 'Mot de passe', 'pass2' => 'Confirmer le mot de passe', 'hint' => 'Au moins 8 caractères. Vous serez connecté à la radio dès l\'inscription.', 'short' => 'Le mot de passe doit contenir au moins 8 caractères.', 'diff' => 'Les deux mots de passe ne sont pas identiques.' ),
		'en' => array( 'pass' => 'Password', 'pass2' => 'Confirm password', 'hint' => 'At least 8 characters. You will be signed in to the radio right away.', 'short' => 'The password must be at least 8 characters long.', 'diff' => 'The two passwords do not match.' ),
		'es' => array( 'pass' => 'Contraseña', 'pass2' => 'Confirmar la contraseña', 'hint' => 'Al menos 8 caracteres. Entrarás en la radio al instante.', 'short' => 'La contraseña debe tener al menos 8 caracteres.', 'diff' => 'Las dos contraseñas no coinciden.' ),
		'ar' => array( 'pass' => 'كلمة المرور', 'pass2' => 'تأكيد كلمة المرور', 'hint' => '8 أحرف على الأقل. ستدخل إلى الإذاعة مباشرة بعد التسجيل.', 'short' => 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.', 'diff' => 'كلمتا المرور غير متطابقتين.' ),
	);
	$set = isset( $t[ $l ] ) ? $t[ $l ] : $t['fr'];
	return $set[ $key ];
}

/** Champs mot de passe dans le formulaire d'inscription. */
add_action( 'register_form', function () {
	?>
	<p>
		<label for="orad_pass1"><?php echo esc_html( orad_signup_txt( 'pass' ) ); ?></label>
		<input type="password" name="orad_pass1" id="orad_pass1" class="input" size="25" autocomplete="new-password" minlength="8" required>
	</p>
	<p>
		<label for="orad_pass2"><?php echo esc_html( orad_signup_txt( 'pass2' ) ); ?></label>
		<input type="password" name="orad_pass2" id="orad_pass2" class="input" size="25" autocomplete="new-password" minlength="8" required>
	</p>
	<p class="orad-hint"><?php echo esc_html( orad_signup_txt( 'hint' ) ); ?></p>
	<style>#reg_passmail{display:none!important}.orad-hint{margin:0 0 16px!important;font-size:13px;opacity:.85}</style>
	<?php
} );

/** Vérification du mot de passe. */
add_filter( 'registration_errors', function ( $errors ) {
	// phpcs:disable WordPress.Security.NonceVerification
	$p1 = isset( $_POST['orad_pass1'] ) ? (string) wp_unslash( $_POST['orad_pass1'] ) : '';
	$p2 = isset( $_POST['orad_pass2'] ) ? (string) wp_unslash( $_POST['orad_pass2'] ) : '';
	// phpcs:enable
	if ( strlen( $p1 ) < 8 ) {
		$errors->add( 'orad_pass_short', '<strong>' . esc_html__( 'Erreur' ) . '</strong> : ' . esc_html( orad_signup_txt( 'short' ) ) );
	} elseif ( $p1 !== $p2 ) {
		$errors->add( 'orad_pass_diff', '<strong>' . esc_html__( 'Erreur' ) . '</strong> : ' . esc_html( orad_signup_txt( 'diff' ) ) );
	}
	return $errors;
} );

/**
 * Après la création du compte depuis le formulaire public : on enregistre le mot de passe
 * choisi, on prévient seulement l'administrateur, on connecte l'auditeur et on le renvoie à la radio.
 */
remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
add_action( 'register_new_user', function ( $user_id ) {
	// phpcs:ignore WordPress.Security.NonceVerification
	$p1 = isset( $_POST['orad_pass1'] ) ? (string) wp_unslash( $_POST['orad_pass1'] ) : '';
	if ( strlen( $p1 ) < 8 ) {
		wp_send_new_user_notifications( $user_id ); // formulaire inattendu : comportement WordPress normal
		return;
	}
	wp_set_password( $p1, $user_id );
	wp_new_user_notification( $user_id, null, 'admin' );
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true, is_ssl() );
	wp_safe_redirect( add_query_arg( 'bienvenue', '1', home_url( '/' ) ) );
	exit;
} );

/** Après connexion, les auditeurs reviennent sur la radio (pas sur le tableau de bord). */
add_filter( 'login_redirect', function ( $to, $requested, $user ) {
	if ( $user instanceof WP_User && ! user_can( $user, 'edit_posts' ) && ( '' === $requested || false !== strpos( $to, '/wp-admin' ) ) ) {
		return home_url( '/' );
	}
	return $to;
}, 10, 3 );
