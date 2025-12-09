<?php
/**
 * Class Jawara_Threat_Intelligence
 * Integration dengan threat intelligence APIs untuk IP reputation checking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Threat_Intelligence {

	const ABUSEIPDB_API = 'https://api.abuseipdb.com/api/v2/check';
	const CACHE_DURATION = 86400; // 24 hours

	/**
	 * Check IP reputation menggunakan AbuseIPDB
	 *
	 * @param string $ip IP address
	 * @return array|WP_Error Reputation data
	 */
	public static function check_ip_reputation( $ip ) {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE ) ) {
			return new WP_Error( 'invalid_ip', 'Invalid or private IP address' );
		}

		// Check cache
		$cache_key = 'jwsai_threat_' . md5( $ip );
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		// Check database
		$db_result = self::get_from_database( $ip );
		if ( $db_result && ( time() - strtotime( $db_result['last_checked'] ) ) < self::CACHE_DURATION ) {
			set_transient( $cache_key, $db_result, self::CACHE_DURATION );
			return $db_result;
		}

		// Call API
		$api_key = get_option( 'jwsai_abuseipdb_api_key' );
		if ( empty( $api_key ) ) {
			return new WP_Error( 'no_api_key', 'AbuseIPDB API key not configured' );
		}

		$response = wp_remote_get(
			add_query_arg( array(
				'ipAddress' => $ip,
				'maxAgeInDays' => 90,
				'verbose' => '',
			), self::ABUSEIPDB_API ),
			array(
				'timeout' => 5,
				'headers' => array(
					'Key' => $api_key,
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return new WP_Error( 'api_error', 'AbuseIPDB API error: ' . $status_code );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['data'] ) ) {
			return new WP_Error( 'parse_error', 'Failed to parse AbuseIPDB response' );
		}

		$ip_data = $data['data'];
		
		$result = array(
			'ip_address' => $ip,
			'abuse_confidence_score' => $ip_data['abuseConfidenceScore'] ?? 0,
			'country_code' => $ip_data['countryCode'] ?? 'UNKNOWN',
			'usage_type' => $ip_data['usageType'] ?? 'Unknown',
			'isp' => $ip_data['isp'] ?? 'Unknown',
			'domain' => $ip_data['domain'] ?? '',
			'is_public' => $ip_data['isPublic'] ?? true,
			'is_whitelisted' => $ip_data['isWhitelisted'] ?? false,
			'total_reports' => $ip_data['totalReports'] ?? 0,
			'num_distinct_users' => $ip_data['numDistinctUsers'] ?? 0,
			'last_reported_at' => $ip_data['lastReportedAt'] ?? null,
			'is_malicious' => ( $ip_data['abuseConfidenceScore'] ?? 0 ) >= 50,
			'threat_level' => self::calculate_threat_level( $ip_data['abuseConfidenceScore'] ?? 0 ),
			'last_checked' => current_time( 'mysql' ),
		);

		// Save to database
		self::save_to_database( $result );

		// Cache result
		set_transient( $cache_key, $result, self::CACHE_DURATION );

		// Auto-blacklist if malicious
		if ( $result['is_malicious'] && get_option( 'jwsai_auto_blacklist_threats' ) ) {
			self::auto_blacklist_ip( $ip, $result );
		}

		return $result;
	}

	/**
	 * Calculate threat level dari abuse score
	 *
	 * @param int $score Abuse confidence score
	 * @return string Threat level
	 */
	private static function calculate_threat_level( $score ) {
		if ( $score >= 75 ) {
			return 'critical';
		} elseif ( $score >= 50 ) {
			return 'high';
		} elseif ( $score >= 25 ) {
			return 'medium';
		} elseif ( $score > 0 ) {
			return 'low';
		}
		return 'clean';
	}

	/**
	 * Save threat data to database
	 *
	 * @param array $data Threat data
	 * @return bool
	 */
	private static function save_to_database( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'jwsai_threat_intelligence';

		// Check if table exists, create if not
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			self::create_table();
		}

		$wpdb->replace(
			$table,
			array(
				'ip_address' => $data['ip_address'],
				'reputation_score' => $data['abuse_confidence_score'],
				'threat_level' => $data['threat_level'],
				'country_code' => $data['country_code'],
				'last_checked' => $data['last_checked'],
				'is_malicious' => $data['is_malicious'] ? 1 : 0,
				'threat_data' => wp_json_encode( $data ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d', '%s' )
		);

		return true;
	}

	/**
	 * Get threat data from database
	 *
	 * @param string $ip IP address
	 * @return array|null
	 */
	private static function get_from_database( $ip ) {
		global $wpdb;
		$table = $wpdb->prefix . 'jwsai_threat_intelligence';

		$row = $wpdb->get_row( 
			$wpdb->prepare( "SELECT * FROM $table WHERE ip_address = %s", $ip ),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		$data = json_decode( $row['threat_data'], true );
		return $data ? $data : null;
	}

	/**
	 * Auto-blacklist malicious IP
	 *
	 * @param string $ip IP address
	 * @param array $threat_data Threat data
	 */
	private static function auto_blacklist_ip( $ip, $threat_data ) {
		$blacklist = get_option( 'jwsai_blacklist_ips', array() );

		if ( ! in_array( $ip, $blacklist, true ) ) {
			$blacklist[] = $ip;
			update_option( 'jwsai_blacklist_ips', $blacklist );

			Jawara_Security_Logger::log(
				'auto_blacklist',
				'high',
				sprintf( 'IP %s auto-blacklisted (Abuse Score: %d)', $ip, $threat_data['abuse_confidence_score'] ),
				$ip,
				null,
				null,
				$threat_data
			);

			// Send notification
			if ( get_option( 'jwsai_telegram_enabled' ) ) {
				Jawara_Telegram_Notifier::send_security_alert(
					'Auto-Blacklist',
					sprintf( "IP: %s\nAbuse Score: %d\nCountry: %s\nReports: %d", 
						$ip, 
						$threat_data['abuse_confidence_score'],
						$threat_data['country_code'],
						$threat_data['total_reports']
					),
					'high'
				);
			}
		}
	}

	/**
	 * Create threat intelligence table
	 */
	public static function create_table() {
		global $wpdb;
		$table = $wpdb->prefix . 'jwsai_threat_intelligence';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			ip_address VARCHAR(45) NOT NULL,
			reputation_score INT NOT NULL DEFAULT 0,
			threat_level VARCHAR(20) NOT NULL DEFAULT 'unknown',
			country_code VARCHAR(2),
			last_checked DATETIME NOT NULL,
			is_malicious BOOLEAN DEFAULT 0,
			threat_data LONGTEXT,
			PRIMARY KEY (id),
			UNIQUE KEY ip_address (ip_address),
			KEY last_checked (last_checked),
			KEY is_malicious (is_malicious)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get malicious IPs dari database
	 *
	 * @param int $limit Limit results
	 * @return array
	 */
	public static function get_malicious_ips( $limit = 50 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'jwsai_threat_intelligence';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE is_malicious = 1 ORDER BY reputation_score DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return $results;
	}

	/**
	 * Get statistics
	 *
	 * @return array
	 */
	public static function get_statistics() {
		global $wpdb;
		$table = $wpdb->prefix . 'jwsai_threat_intelligence';

		$stats = array(
			'total_checked' => 0,
			'malicious' => 0,
			'clean' => 0,
			'by_threat_level' => array(
				'critical' => 0,
				'high' => 0,
				'medium' => 0,
				'low' => 0,
				'clean' => 0,
			),
		);

		$total = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
		$malicious = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE is_malicious = 1" );
		
		$stats['total_checked'] = (int) $total;
		$stats['malicious'] = (int) $malicious;
		$stats['clean'] = $stats['total_checked'] - $stats['malicious'];

		// Get by threat level
		$levels = $wpdb->get_results(
			"SELECT threat_level, COUNT(*) as count FROM $table GROUP BY threat_level",
			ARRAY_A
		);

		foreach ( $levels as $level ) {
			if ( isset( $stats['by_threat_level'][ $level['threat_level'] ] ) ) {
				$stats['by_threat_level'][ $level['threat_level'] ] = (int) $level['count'];
			}
		}

		return $stats;
	}
}
