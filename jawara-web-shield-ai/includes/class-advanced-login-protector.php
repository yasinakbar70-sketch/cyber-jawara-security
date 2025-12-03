<?php
/**
 * Class Jawara_Advanced_Login_Protector
 * Advanced login protection dengan reCAPTCHA, device fingerprinting, dan session management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Advanced_Login_Protector {

	/**
	 * Initialize
	 */
	public static function init() {
		// reCAPTCHA
		add_action( 'login_enqueue_scripts', array( __CLASS__, 'enqueue_login_scripts' ) );
		add_action( 'login_form', array( __CLASS__, 'render_recaptcha' ) );
		add_filter( 'authenticate', array( __CLASS__, 'verify_login_security' ), 20, 3 );
		
		// Session & Device
		add_action( 'wp_login', array( __CLASS__, 'track_device_and_session' ), 10, 2 );
	}

	/**
	 * Enqueue login scripts
	 */
	public static function enqueue_login_scripts() {
		$recaptcha_site_key = get_option( 'jwsai_recaptcha_site_key' );
		
		if ( get_option( 'jwsai_recaptcha_enabled' ) && ! empty( $recaptcha_site_key ) ) {
			wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null );
		}
	}

	/**
	 * Render reCAPTCHA on login form
	 */
	public static function render_recaptcha() {
		$recaptcha_site_key = get_option( 'jwsai_recaptcha_site_key' );
		
		if ( get_option( 'jwsai_recaptcha_enabled' ) && ! empty( $recaptcha_site_key ) ) {
			echo '<div class="g-recaptcha" data-sitekey="' . esc_attr( $recaptcha_site_key ) . '" style="margin-bottom: 15px;"></div>';
		}
	}

	/**
	 * Verify login security (reCAPTCHA & Device)
	 */
	public static function verify_login_security( $user, $username, $password ) {
		if ( is_wp_error( $user ) || empty( $username ) || empty( $password ) ) {
			return $user;
		}

		// 1. Verify reCAPTCHA
		if ( get_option( 'jwsai_recaptcha_enabled' ) ) {
			$recaptcha_response = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : '';
			$recaptcha_secret = get_option( 'jwsai_recaptcha_secret_key' );

			if ( empty( $recaptcha_response ) ) {
				return new WP_Error( 'recaptcha_missing', __( 'Please complete the CAPTCHA.', 'jawara-web-shield-ai' ) );
			}

			$verify = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
				'body' => array(
					'secret' => $recaptcha_secret,
					'response' => $recaptcha_response,
					'remoteip' => Jawara_Web_Shield_AI::get_client_ip_static()
				)
			) );

			if ( is_wp_error( $verify ) ) {
				return $verify;
			}

			$response_body = wp_remote_retrieve_body( $verify );
			$result = json_decode( $response_body, true );

			if ( ! isset( $result['success'] ) || ! $result['success'] ) {
				return new WP_Error( 'recaptcha_failed', __( 'CAPTCHA verification failed. Please try again.', 'jawara-web-shield-ai' ) );
			}
		}

		return $user;
	}

	/**
	 * Track device and session after successful login
	 */
	public static function track_device_and_session( $user_login, $user ) {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : 'Unknown';
		$ip = Jawara_Web_Shield_AI::get_client_ip_static();
		
		// Simple device fingerprint
		$device_hash = md5( $user_agent . $ip );
		
		// Get known devices
		$known_devices = get_user_meta( $user->ID, 'jwsai_known_devices', true );
		if ( ! is_array( $known_devices ) ) {
			$known_devices = array();
		}

		// Check if new device
		if ( ! isset( $known_devices[ $device_hash ] ) ) {
			// Notify user about new device login
			if ( get_option( 'jwsai_notify_new_device' ) ) {
				self::send_new_device_notification( $user, $user_agent, $ip );
			}
			
			// Add to known devices
			$known_devices[ $device_hash ] = array(
				'agent' => $user_agent,
				'ip' => $ip,
				'last_login' => current_time( 'mysql' ),
				'added' => current_time( 'mysql' )
			);
		} else {
			// Update last login
			$known_devices[ $device_hash ]['last_login'] = current_time( 'mysql' );
			$known_devices[ $device_hash ]['ip'] = $ip; // Update IP
		}

		update_user_meta( $user->ID, 'jwsai_known_devices', $known_devices );
	}

	/**
	 * Send new device notification email
	 */
	private static function send_new_device_notification( $user, $user_agent, $ip ) {
		$subject = sprintf( '[%s] New Login from Unrecognized Device', get_bloginfo( 'name' ) );
		$message = sprintf(
			"Hello %s,\n\nWe detected a login to your account from a new device.\n\nTime: %s\nIP Address: %s\nDevice: %s\n\nIf this was you, you can ignore this email. If not, please change your password immediately.",
			$user->display_name,
			current_time( 'mysql' ),
			$ip,
			$user_agent
		);

		wp_mail( $user->user_email, $subject, $message );
	}
}

// Initialize
Jawara_Advanced_Login_Protector::init();
