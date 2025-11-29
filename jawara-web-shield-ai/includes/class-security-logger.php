<?php
/**
 * Class Jawara_Security_Logger
 * Mengelola logging semua aktivitas keamanan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Security_Logger {

	/**
	 * Log sebuah event keamanan
	 *
	 * @param string $event_type Tipe event
	 * @param string $severity Tingkat keparahan
	 * @param string $message Pesan log
	 * @param string $file_path Path file (optional)
	 * @param string $ip_address IP address (optional)
	 * @param int $user_id User ID (optional)
	 * @param string $ai_analysis Analisis AI (optional)
	 */
	public static function log( $event_type, $severity, $message, $file_path = null, $ip_address = null, $user_id = null, $ai_analysis = null ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'jwsai_logs';

		if ( empty( $ip_address ) ) {
			$ip_address = Jawara_Web_Shield_AI::get_client_ip_static();
		}

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$wpdb->insert(
			$table_name,
			array(
				'event_type'   => sanitize_text_field( $event_type ),
				'severity'     => sanitize_text_field( $severity ),
				'message'      => sanitize_textarea_field( $message ),
				'file_path'    => ! empty( $file_path ) ? sanitize_text_field( $file_path ) : null,
				'ip_address'   => ! empty( $ip_address ) ? sanitize_text_field( $ip_address ) : null,
				'user_id'      => intval( $user_id ) ? intval( $user_id ) : null,
				'ai_analysis'  => ! empty( $ai_analysis ) ? wp_json_encode( $ai_analysis ) : null,
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
			)
		);
	}

	/**
	 * Dapatkan semua logs
	 *
	 * @param int $limit Limit jumlah log
	 * @param int $offset Offset
	 * @param string $event_type Filter by event type (optional)
	 * @param string $severity Filter by severity (optional)
	 * @return array
	 */
	public static function get_logs( $limit = 50, $offset = 0, $event_type = '', $severity = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'jwsai_logs';
		$query      = "SELECT * FROM $table_name WHERE 1=1";

		if ( ! empty( $event_type ) ) {
			$query .= $wpdb->prepare( ' AND event_type = %s', $event_type );
		}

		if ( ! empty( $severity ) ) {
			$query .= $wpdb->prepare( ' AND severity = %s', $severity );
		}

		$query .= ' ORDER BY timestamp DESC LIMIT %d OFFSET %d';

		$results = $wpdb->get_results( $wpdb->prepare( $query, $limit, $offset ) ); // phpcs:ignore

		$logs = array();
		foreach ( $results as $log ) {
			$logs[] = array(
				'id'           => intval( $log->id ),
				'timestamp'    => $log->timestamp,
				'event_type'   => $log->event_type,
				'severity'     => $log->severity,
				'message'      => $log->message,
				'file_path'    => $log->file_path,
				'ip_address'   => $log->ip_address,
				'user_id'      => intval( $log->user_id ),
				'ai_analysis'  => ! empty( $log->ai_analysis ) ? json_decode( $log->ai_analysis, true ) : null,
			);
		}

		return $logs;
	}

	/**
	 * Dapatkan log statistics
	 *
	 * @return array
	 */
	public static function get_statistics() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'jwsai_logs';

		$total_logs = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

		$event_types = $wpdb->get_results( "SELECT event_type, COUNT(*) as count FROM $table_name GROUP BY event_type ORDER BY count DESC LIMIT 10" );

		$severity_distribution = $wpdb->get_results( "SELECT severity, COUNT(*) as count FROM $table_name GROUP BY severity" );

		$recent_high_severity = $wpdb->get_results( "SELECT COUNT(*) as count FROM $table_name WHERE severity IN ('high', 'critical') AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)" );

		return array(
			'total_logs'           => intval( $total_logs ),
			'event_types'          => $event_types,
			'severity_distribution' => $severity_distribution,
			'high_severity_24h'    => intval( $recent_high_severity[0]->count ?? 0 ),
		);
	}

	/**
	 * Clear logs lebih tua dari X hari
	 *
	 * @param int $days Jumlah hari
	 */
	public static function cleanup_old_logs( $days = 30 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'jwsai_logs';

		$wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)", $days ) );
	}
}
