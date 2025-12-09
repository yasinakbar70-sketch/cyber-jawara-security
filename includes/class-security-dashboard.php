<?php
/**
 * Class Jawara_Security_Dashboard
 * Handles data aggregation and analytics for the security dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Security_Dashboard {

	/**
	 * Get aggregated statistics for the dashboard
	 */
	public static function get_stats() {
		global $wpdb;
		$log_table = $wpdb->prefix . 'jwsai_logs';
		$threat_table = $wpdb->prefix . 'jwsai_threat_intelligence';

		// Time ranges
		$now = current_time( 'mysql' );
		$today_start = date( 'Y-m-d 00:00:00', strtotime( $now ) );
		$week_start = date( 'Y-m-d 00:00:00', strtotime( '-7 days' ) );

		// 1. Total Attacks Blocked (All time & Today)
		$total_blocked = $wpdb->get_var( "SELECT COUNT(*) FROM $log_table WHERE severity IN ('high', 'critical') AND event_type LIKE '%block%'" );
		$today_blocked = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $log_table WHERE severity IN ('high', 'critical') AND event_type LIKE '%block%' AND timestamp >= %s", $today_start ) );

		// 2. Malware Detected
		$malware_count = $wpdb->get_var( "SELECT COUNT(*) FROM $log_table WHERE event_type = 'malware_detected'" );

		// 3. Login Attempts (Failed vs Success)
		$failed_logins = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $log_table WHERE event_type = 'login_failed' AND timestamp >= %s", $week_start ) );
		$success_logins = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $log_table WHERE event_type = 'login_success' AND timestamp >= %s", $week_start ) );

		// 4. Top Attacking Countries
		$top_countries = $wpdb->get_results( 
			"SELECT 
				CASE WHEN message LIKE '%Country: %' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(message, 'Country: ', -1), ' ', 1) ELSE 'Unknown' END as country,
				COUNT(*) as count 
			FROM $log_table 
			WHERE severity IN ('high', 'critical') 
			GROUP BY country 
			ORDER BY count DESC 
			LIMIT 5", 
			ARRAY_A 
		);

		// 5. Recent Threats
		$recent_threats = $wpdb->get_results( "SELECT * FROM $log_table WHERE severity IN ('high', 'critical') ORDER BY timestamp DESC LIMIT 5", ARRAY_A );

		return array(
			'total_blocked' => (int) $total_blocked,
			'today_blocked' => (int) $today_blocked,
			'malware_count' => (int) $malware_count,
			'login_stats' => array(
				'failed' => (int) $failed_logins,
				'success' => (int) $success_logins,
			),
			'top_countries' => $top_countries,
			'recent_threats' => $recent_threats,
		);
	}

	/**
	 * Get chart data for the last 7 days
	 */
	public static function get_chart_data() {
		global $wpdb;
		$log_table = $wpdb->prefix . 'jwsai_logs';

		$data = array(
			'labels' => array(),
			'blocked' => array(),
			'logins' => array(),
		);

		for ( $i = 6; $i >= 0; $i-- ) {
			$date = date( 'Y-m-d', strtotime( "-$i days" ) );
			$data['labels'][] = date( 'M j', strtotime( $date ) );

			// Blocked attacks
			$blocked = $wpdb->get_var( $wpdb->prepare( 
				"SELECT COUNT(*) FROM $log_table 
				WHERE severity IN ('high', 'critical') 
				AND event_type LIKE '%block%' 
				AND DATE(timestamp) = %s", 
				$date 
			) );
			$data['blocked'][] = (int) $blocked;

			// Failed logins
			$logins = $wpdb->get_var( $wpdb->prepare( 
				"SELECT COUNT(*) FROM $log_table 
				WHERE event_type = 'login_failed' 
				AND DATE(timestamp) = %s", 
				$date 
			) );
			$data['logins'][] = (int) $logins;
		}

		return $data;
	}

	/**
	 * Get system health status
	 */
	public static function get_system_health() {
		$health = array(
			'score' => 100,
			'issues' => array(),
		);

		// Check Firewall
		if ( ! get_option( 'jwsai_sql_injection_protection' ) ) {
			$health['score'] -= 10;
			$health['issues'][] = 'SQL Injection protection is disabled';
		}

		// Check 2FA
		if ( ! get_option( 'jwsai_2fa_enabled' ) ) {
			$health['score'] -= 10;
			$health['issues'][] = 'Two-Factor Authentication is disabled';
		}

		// Check Malware Scan
		$last_scan = wp_next_scheduled( 'jwsai_daily_signature_scan' );
		if ( ! $last_scan ) {
			$health['score'] -= 10;
			$health['issues'][] = 'Malware scan schedule is missing';
		}

		// Check Updates
		// (Placeholder for update check)

		return $health;
	}
}
