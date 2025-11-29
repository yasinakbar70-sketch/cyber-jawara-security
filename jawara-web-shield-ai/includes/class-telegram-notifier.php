<?php
/**
 * Class Jawara_Telegram_Notifier
 * Mengelola notifikasi keamanan via Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Telegram_Notifier {

	const TELEGRAM_API_URL = 'https://api.telegram.org/bot';

	/**
	 * Kirim notifikasi keamanan
	 *
	 * @param string $title Judul notifikasi
	 * @param string $message Pesan detail
	 * @param string $severity Tingkat keparahan (low/medium/high/critical)
	 * @return bool|WP_Error
	 */
	public static function send_security_alert( $title, $message, $severity = 'medium' ) {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$token   = get_option( 'jwsai_telegram_token' );
		$chat_id = get_option( 'jwsai_telegram_chat_id' );

		if ( empty( $token ) || empty( $chat_id ) ) {
			return new WP_Error( 'telegram_not_configured', 'Telegram not configured' );
		}

		$severity_icon = self::get_severity_icon( $severity );

		$full_message = sprintf(
			"%s *%s*\n\n%s\n\nWebsite: %s\nTime: %s",
			$severity_icon,
			$title,
			$message,
			site_url(),
			current_time( 'Y-m-d H:i:s' )
		);

		return self::send_message( $token, $chat_id, $full_message );
	}

	/**
	 * Kirim notifikasi file integrity
	 *
	 * @param string $file_path Path ke file
	 * @param string $change_type Tipe perubahan (modified/deleted/added)
	 * @return bool|WP_Error
	 */
	public static function notify_file_change( $file_path, $change_type = 'modified' ) {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$message = sprintf(
			"🚨 *File %s Detected*\n\nFile: `%s`\nChange Type: %s",
			strtoupper( $change_type ),
			$file_path,
			$change_type
		);

		return self::send_security_alert( 'File Integrity Alert', $message, 'high' );
	}

	/**
	 * Kirim notifikasi malware
	 *
	 * @param string $file_path Path ke file
	 * @param string $risk_level Tingkat risiko
	 * @param string $analysis Hasil analisis AI
	 * @return bool|WP_Error
	 */
	public static function notify_malware_detected( $file_path, $risk_level, $analysis ) {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$short_analysis = substr( $analysis, 0, 200 ) . '...';

		$message = sprintf(
			"🛑 *Potential Malware Detected*\n\nFile: `%s`\nRisk Level: *%s*\n\nAnalysis:\n%s",
			$file_path,
			strtoupper( $risk_level ),
			$short_analysis
		);

		return self::send_security_alert( 'Malware Alert', $message, 'critical' );
	}

	/**
	 * Kirim notifikasi brute force attack
	 *
	 * @param string $username Username yang di-attack
	 * @param string $ip IP address
	 * @return bool|WP_Error
	 */
	public static function notify_brute_force_attack( $username, $ip ) {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$message = sprintf(
			"⚠️ *Brute Force Attack Detected*\n\nUsername: `%s`\nIP: `%s`\nAction: IP has been locked out",
			$username,
			$ip
		);

		return self::send_security_alert( 'Brute Force Alert', $message, 'high' );
	}

	/**
	 * Kirim notifikasi IP blacklist
	 *
	 * @param string $ip IP address
	 * @return bool|WP_Error
	 */
	public static function notify_ip_blocked( $ip ) {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$message = sprintf(
			"🚫 *IP Blocked by Firewall*\n\nIP: `%s`\nReason: IP is in blacklist",
			$ip
		);

		return self::send_security_alert( 'Firewall Alert', $message, 'medium' );
	}

	/**
	 * Check apakah Telegram notifikasi enabled
	 */
	private static function is_enabled() {
		return (bool) get_option( 'jwsai_telegram_enabled' );
	}

	/**
	 * Dapatkan severity icon
	 */
	private static function get_severity_icon( $severity ) {
		$icons = array(
			'low'      => '✅',
			'medium'   => '⚠️',
			'high'     => '🔴',
			'critical' => '🛑',
		);

		return isset( $icons[ $severity ] ) ? $icons[ $severity ] : '❓';
	}

	/**
	 * Kirim pesan ke Telegram
	 */
	private static function send_message( $token, $chat_id, $message ) {
		$url = self::TELEGRAM_API_URL . $token . '/sendMessage';

		$response = wp_remote_post(
			$url,
			array(
				'body'      => array(
					'chat_id'    => $chat_id,
					'text'       => $message,
					'parse_mode' => 'Markdown',
				),
				'timeout'   => 10,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		return 200 === $status_code;
	}
}
