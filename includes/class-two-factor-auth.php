<?php
/**
 * Class Jawara_Two_Factor_Auth
 * Two-Factor Authentication (TOTP) implementation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Two_Factor_Auth {

	/**
	 * Initialize 2FA hooks
	 */
	public static function init() {
		add_action( 'login_form_jwsai_2fa', array( __CLASS__, 'render_2fa_form' ) );
		add_action( 'login_form_validate_2fa', array( __CLASS__, 'validate_2fa_login' ) );
		add_filter( 'authenticate', array( __CLASS__, 'check_2fa_requirement' ), 50, 3 );
		
		// AJAX for setup
		add_action( 'wp_ajax_jwsai_setup_2fa', array( __CLASS__, 'ajax_setup_2fa' ) );
		add_action( 'wp_ajax_jwsai_verify_2fa_setup', array( __CLASS__, 'ajax_verify_2fa_setup' ) );
		add_action( 'wp_ajax_jwsai_disable_2fa', array( __CLASS__, 'ajax_disable_2fa' ) );
		add_action( 'wp_ajax_jwsai_get_backup_codes', array( __CLASS__, 'ajax_get_backup_codes' ) );	
	}

	/**
	 * Check if user needs 2FA
	 */
	public static function check_2fa_requirement( $user, $username, $password ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		// Check if 2FA is enabled globally
		if ( ! get_option( 'jwsai_2fa_enabled' ) ) {
			return $user;
		}

		// Check if user has 2FA enabled
		$user_secret = get_user_meta( $user->ID, 'jwsai_2fa_secret', true );
		
		// Force 2FA for admins if configured
		$force_admins = get_option( 'jwsai_force_2fa_admins' );
		if ( empty( $user_secret ) && $force_admins && user_can( $user, 'manage_options' ) ) {
			// Redirect to setup page after login (handled elsewhere)
			return $user;
		}

		if ( ! empty( $user_secret ) ) {
			// Store user ID in session/cookie for 2FA verification
			$token = md5( uniqid( rand(), true ) );
			set_transient( 'jwsai_2fa_pending_' . $token, $user->ID, 300 ); // 5 minutes

			// Redirect to 2FA form
			$login_url = site_url( 'wp-login.php?action=jwsai_2fa&token=' . $token );
			wp_redirect( $login_url );
			exit;
		}

		return $user;
	}

	/**
	 * Render 2FA verification form
	 */
	public static function render_2fa_form() {
		$token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';
		$user_id = get_transient( 'jwsai_2fa_pending_' . $token );

		if ( ! $user_id ) {
			wp_redirect( site_url( 'wp-login.php' ) );
			exit;
		}

		$error = isset( $_GET['error'] ) ? 'Invalid code. Please try again.' : '';

		login_header( 'Two-Factor Authentication', '', $error ? new WP_Error( 'invalid_code', $error ) : null );
		?>
		<form name="jwsai_2fa_form" id="loginform" action="<?php echo esc_url( site_url( 'wp-login.php?action=validate_2fa' ) ); ?>" method="post">
			<p>
				<label for="jwsai_2fa_code"><?php _e( 'Enter 6-digit code from your authenticator app', 'jawara-web-shield-ai' ); ?><br />
				<input type="text" name="jwsai_2fa_code" id="jwsai_2fa_code" class="input" value="" size="20" autocomplete="off" required /></label>
			</p>
			<input type="hidden" name="jwsai_token" value="<?php echo esc_attr( $token ); ?>" />
			<p class="submit">
				<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Verify', 'jawara-web-shield-ai' ); ?>" />
			</p>
		</form>
		<?php
		login_footer();
		exit;
	}

	/**
	 * Validate 2FA login
	 */
	public static function validate_2fa_login() {
		$token = isset( $_POST['jwsai_token'] ) ? sanitize_text_field( $_POST['jwsai_token'] ) : '';
		$code = isset( $_POST['jwsai_2fa_code'] ) ? sanitize_text_field( $_POST['jwsai_2fa_code'] ) : '';
		
		$user_id = get_transient( 'jwsai_2fa_pending_' . $token );

		if ( ! $user_id ) {
			wp_redirect( site_url( 'wp-login.php' ) );
			exit;
		}

		$secret = get_user_meta( $user_id, 'jwsai_2fa_secret', true );
		
		if ( self::verify_code( $secret, $code ) ) {
			// Success! Log the user in
			delete_transient( 'jwsai_2fa_pending_' . $token );
			wp_set_auth_cookie( $user_id, true );
			
			// Log event
			Jawara_Security_Logger::log( 'login', 'low', '2FA verification successful', '', '', $user_id );
			
			wp_redirect( admin_url() );
			exit;
		} else {
			// Check backup codes
			$backup_codes = get_user_meta( $user_id, 'jwsai_2fa_backup_codes', true );
			if ( is_array( $backup_codes ) && in_array( $code, $backup_codes ) ) {
				// Remove used backup code
				$backup_codes = array_diff( $backup_codes, array( $code ) );
				update_user_meta( $user_id, 'jwsai_2fa_backup_codes', $backup_codes );
				
				delete_transient( 'jwsai_2fa_pending_' . $token );
				wp_set_auth_cookie( $user_id, true );
				
				Jawara_Security_Logger::log( 'login', 'medium', '2FA backup code used', '', '', $user_id );
				
				wp_redirect( admin_url() );
				exit;
			}
			
			// Failed
			wp_redirect( site_url( 'wp-login.php?action=jwsai_2fa&token=' . $token . '&error=1' ) );
			exit;
		}
	}

	/**
	 * Generate new secret
	 */
	public static function generate_secret() {
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet
		$secret = '';
		for ( $i = 0; $i < 16; $i++ ) {
			$secret .= $chars[ rand( 0, 31 ) ];
		}
		return $secret;
	}

	/**
	 * Generate backup codes
	 */
	public static function generate_backup_codes( $count = 8 ) {
		$codes = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$codes[] = sprintf( '%08d', rand( 0, 99999999 ) );
		}
		return $codes;
	}

	/**
	 * Verify TOTP code
	 * Based on RFC 6238
	 */
	public static function verify_code( $secret, $code, $window = 1 ) {
		if ( empty( $secret ) || empty( $code ) ) {
			return false;
		}

		$timestamp = floor( time() / 30 );
		
		for ( $i = -$window; $i <= $window; $i++ ) {
			$calculated = self::calculate_code( $secret, $timestamp + $i );
			if ( hash_equals( (string)$calculated, (string)$code ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Calculate TOTP code
	 */
	private static function calculate_code( $secret, $timestamp ) {
		$secret = self::base32_decode( $secret );
		$time = pack( 'N*', 0 ) . pack( 'N*', $timestamp );
		$hash = hash_hmac( 'sha1', $time, $secret, true );
		$offset = ord( $hash[19] ) & 0xf;
		$code = (
			( ( ord( $hash[ $offset + 0 ] ) & 0x7f ) << 24 ) |
			( ( ord( $hash[ $offset + 1 ] ) & 0xff ) << 16 ) |
			( ( ord( $hash[ $offset + 2 ] ) & 0xff ) << 8 ) |
			( ( ord( $hash[ $offset + 3 ] ) & 0xff ) )
		) % 1000000;

		return str_pad( $code, 6, '0', STR_PAD_LEFT );
	}

	/**
	 * Base32 decode
	 */
	private static function base32_decode( $base32 ) {
		$base32 = strtoupper( $base32 );
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$binary = '';
		
		foreach ( str_split( $base32 ) as $char ) {
			if ( false === strpos( $chars, $char ) ) {
				continue;
			}
			$binary .= sprintf( '%05b', strpos( $chars, $char ) );
		}
		
		$binary = str_split( $binary, 8 );
		$result = '';
		
		foreach ( $binary as $bin ) {
			if ( strlen( $bin ) < 8 ) {
				$bin = str_pad( $bin, 8, '0', STR_PAD_RIGHT );
			}
			$result .= chr( bindec( $bin ) );
		}
		
		return $result;
	}

	/**
	 * Generate QR Code URL (using Google Charts API for simplicity, or local JS library)
	 * Note: For better privacy, we should use a local JS library in production.
	 * For this implementation, we'll use a placeholder that can be replaced by a JS library.
	 */
	public static function get_qr_code_url( $secret, $user_email ) {
		$company = get_bloginfo( 'name' );
		$otpauth = "otpauth://totp/" . rawurlencode( $company ) . ":" . rawurlencode( $user_email ) . "?secret=" . $secret . "&issuer=" . rawurlencode( $company );
		
		// Using Google Charts API (deprecated but still works) or similar service
		// Ideally, use a JS library like qrcode.js in the frontend
		return 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode( $otpauth );
	}

	/**
	 * AJAX: Setup 2FA
	 */
	public static function ajax_setup_2fa() {
		if ( ! check_ajax_referer( 'jwsai_nonce', false, false ) ) {
			wp_send_json_error( 'Session expired. Please refresh the page.' );
		}
		
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$secret = self::generate_secret();
		$user = wp_get_current_user();
		$qr_url = self::get_qr_code_url( $secret, $user->user_email );

		// Store temporarily
		set_transient( 'jwsai_2fa_setup_' . $user->ID, $secret, 600 );

		wp_send_json_success( array(
			'secret' => $secret,
			'qr_url' => $qr_url
		) );
	}

	/**
	 * AJAX: Verify Setup
	 */
	public static function ajax_verify_2fa_setup() {
		if ( ! check_ajax_referer( 'jwsai_nonce', false, false ) ) {
			wp_send_json_error( 'Session expired. Please refresh the page.' );
		}
		
		$code = isset( $_POST['code'] ) ? sanitize_text_field( $_POST['code'] ) : '';
		$user_id = get_current_user_id();
		$secret = get_transient( 'jwsai_2fa_setup_' . $user_id );

		if ( ! $secret ) {
			wp_send_json_error( 'Session expired. Please try again.' );
		}

		if ( self::verify_code( $secret, $code ) ) {
			// Save secret permanently
			update_user_meta( $user_id, 'jwsai_2fa_secret', $secret );
			
			// Generate backup codes
			$backup_codes = self::generate_backup_codes();
			update_user_meta( $user_id, 'jwsai_2fa_backup_codes', $backup_codes );
			
			delete_transient( 'jwsai_2fa_setup_' . $user_id );
			
			Jawara_Security_Logger::log( 'security_config', 'medium', '2FA enabled for user', '', '', $user_id );
			
			wp_send_json_success( array(
				'message' => '2FA enabled successfully!',
				'backup_codes' => $backup_codes
			) );
		} else {
			wp_send_json_error( 'Invalid code. Please try again.' );
		}
	}

	/**
	 * AJAX: Disable 2FA
	 */
	public static function ajax_disable_2fa() {
		if ( ! check_ajax_referer( 'jwsai_nonce', false, false ) ) {
			wp_send_json_error( 'Session expired. Please refresh the page.' );
		}
		
		$user_id = get_current_user_id();
		delete_user_meta( $user_id, 'jwsai_2fa_secret' );
		delete_user_meta( $user_id, 'jwsai_2fa_backup_codes' );
		
		Jawara_Security_Logger::log( 'security_config', 'medium', '2FA disabled for user', '', '', $user_id );
		
		wp_send_json_success( '2FA disabled successfully.' );
	}

	/**
	 * AJAX: Get Backup Codes
	 */
	public static function ajax_get_backup_codes() {
		if ( ! check_ajax_referer( 'jwsai_nonce', false, false ) ) {
			wp_send_json_error( 'Session expired. Please refresh the page.' );
		}
		
		$user_id = get_current_user_id();
		$codes = get_user_meta( $user_id, 'jwsai_2fa_backup_codes', true );
		
		if ( ! empty( $codes ) && is_array( $codes ) ) {
			wp_send_json_success( $codes );
		} else {
			// Generate new ones if missing but 2FA is on
			if ( get_user_meta( $user_id, 'jwsai_2fa_secret', true ) ) {
				$codes = self::generate_backup_codes();
				update_user_meta( $user_id, 'jwsai_2fa_backup_codes', $codes );
				wp_send_json_success( $codes );
			} else {
				wp_send_json_error( '2FA not enabled.' );
			}
		}
	}
}

// Initialize
Jawara_Two_Factor_Auth::init();
