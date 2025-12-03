<?php
/**
 * Class Jawara_Login_Protector
 * Mengelola proteksi login dengan rate limiting dan lockout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Login_Protector {

	const TRANSIENT_PREFIX = 'jwsai_login_attempt_';
	const TRANSIENT_LOCKOUT_PREFIX = 'jwsai_login_lockout_';

	/**
	 * Record percobaan login gagal
	 *
	 * @param string $username Username
	 */
	public function record_failed_login( $username ) {
		$ip_address = Jawara_Web_Shield_AI::get_client_ip_static();
		$key        = self::TRANSIENT_PREFIX . md5( $ip_address . $username );

		$attempts = get_transient( $key );
		$attempts = ! $attempts ? 0 : $attempts;
		$attempts++;

		$limit    = intval( get_option( 'jwsai_login_attempts_limit', 5 ) );
		$duration = intval( get_option( 'jwsai_lockout_duration', 30 ) );

		// Set transient untuk 1 jam
		set_transient( $key, $attempts, HOUR_IN_SECONDS );

		// Log percobaan
		Jawara_Security_Logger::log(
			'login_attempt',
			'low',
			"Failed login attempt for user: $username (Attempt #$attempts)",
			null,
			$ip_address,
			null
		);

		// Jika melebihi limit, lockout
		if ( $attempts >= $limit ) {
			$this->lockout_ip( $ip_address, $username );
		}
	}

	/**
	 * Check login attempt
	 *
	 * @param string $username Username
	 * @return bool|WP_Error
	 */
	public function check_login_attempt( $username ) {
		$ip_address = Jawara_Web_Shield_AI::get_client_ip_static();
		$lockout_key = self::TRANSIENT_LOCKOUT_PREFIX . md5( $ip_address );

		// Check apakah IP di-lockout
		$lockout_time = get_transient( $lockout_key );

		if ( $lockout_time ) {
			Jawara_Security_Logger::log(
				'login_attempt',
				'high',
				"Login blocked for locked-out IP: $ip_address",
				null,
				$ip_address,
				null
			);

			return new WP_Error(
				'login_locked',
				sprintf(
					__( 'Too many failed login attempts. Please try again after %d minutes.', 'jawara-web-shield-ai' ),
					ceil( $lockout_time / 60 )
				)
			);
		}

		return true;
	}

	/**
	 * Lockout IP address
	 *
	 * @param string $ip_address IP address
	 * @param string $username Username (optional)
	 */
	private function lockout_ip( $ip_address, $username = '' ) {
		$duration    = intval( get_option( 'jwsai_lockout_duration', 30 ) );
		$lockout_key = self::TRANSIENT_LOCKOUT_PREFIX . md5( $ip_address );

		// Set lockout transient
		set_transient( $lockout_key, time() + ( $duration * 60 ), $duration * 60 );

		// Add IP to permanent blacklist
		$blacklist = get_option( 'jwsai_blacklist_ips', array() );
		if ( ! in_array( $ip_address, $blacklist, true ) ) {
			$blacklist[] = $ip_address;
			update_option( 'jwsai_blacklist_ips', $blacklist );

			// Log permanent block
			Jawara_Security_Logger::log(
				'firewall',
				'critical',
				"IP permanently blocked due to brute force attack. Username: $username",
				null,
				$ip_address,
				null
			);
		}

		// Log lockout
		Jawara_Security_Logger::log(
			'login_attempt',
			'high',
			"IP locked out due to too many failed attempts. Username: $username",
			null,
			$ip_address,
			null
		);

		// Kirim notifikasi Telegram dengan info permanent block
		Jawara_Telegram_Notifier::notify_brute_force_attack( $username, $ip_address );
	}

	/**
	 * Reset lockout untuk IP
	 *
	 * @param string $ip_address IP address
	 */
	public function reset_lockout( $ip_address ) {
		$lockout_key = self::TRANSIENT_LOCKOUT_PREFIX . md5( $ip_address );
		delete_transient( $lockout_key );

		Jawara_Security_Logger::log(
			'login_attempt',
			'low',
			"Lockout reset for IP: $ip_address",
			null,
			$ip_address,
			null
		);
	}

	/**
	 * Dapatkan login attempts untuk IP
	 *
	 * @param string $ip_address IP address
	 * @return int
	 */
	public function get_login_attempts( $ip_address ) {
		global $wp_transients;

		$key = self::TRANSIENT_PREFIX . md5( $ip_address . '%' );
		$attempts = 0;

		// Cek semua transient yang dimulai dengan key
		$results = $GLOBALS['wpdb']->get_col(
			"SELECT option_value FROM {$GLOBALS['wpdb']->options} WHERE option_name LIKE '_transient_" . self::TRANSIENT_PREFIX . "%'"
		);

		foreach ( $results as $result ) {
			$attempts += intval( $result );
		}

		return $attempts;
	}

	/**
	 * Dapatkan lockout status untuk IP
	 *
	 * @param string $ip_address IP address
	 * @return bool|int Remaining lockout time in seconds, or false if not locked
	 */
	public function get_lockout_status( $ip_address ) {
		$lockout_key = self::TRANSIENT_LOCKOUT_PREFIX . md5( $ip_address );
		$lockout_time = get_transient( $lockout_key );

		if ( ! $lockout_time ) {
			return false;
		}

		$remaining = $lockout_time - time();

		if ( $remaining <= 0 ) {
			delete_transient( $lockout_key );
			return false;
		}

		return $remaining;
	}
}
