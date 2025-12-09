<?php
/**
 * Class Jawara_Traffic_Logger
 * Handles live traffic logging and monitoring
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Traffic_Logger {

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'log_request' ) );
		add_action( 'wp_ajax_jwsai_get_live_traffic', array( __CLASS__, 'ajax_get_live_traffic' ) );
	}

	/**
	 * Log current request
	 */
	public static function log_request() {
		// Only log if enabled and not an AJAX request from the plugin itself
		if ( ! get_option( 'jwsai_traffic_logging_enabled', 1 ) ) {
			return;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && strpos( $_REQUEST['action'], 'jwsai_' ) === 0 ) {
			return;
		}
		
		// Don't log admin pages to reduce noise, unless configured
		if ( is_admin() && ! get_option( 'jwsai_log_admin_traffic', 0 ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'jwsai_traffic_log';

		// Create table if not exists (should be done in activation, but safe check here)
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			self::create_table();
		}

		$ip = Jawara_Web_Shield_AI::get_client_ip_static();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ), 0, 255 ) : '';
		$url = substr( $_SERVER['REQUEST_URI'], 0, 255 );
		$method = $_SERVER['REQUEST_METHOD'];
		$status = http_response_code(); // Note: This might not be accurate at 'init' hook, usually 200. 
		// For blocked requests, the firewall kills execution before this, so we need to hook into firewall blocks separately or use 'shutdown' hook.
		// However, 'shutdown' is late.
		// Better approach: Log here as 'pending' or 'allowed', firewall updates it if blocked.
		// For simplicity in this phase, we log 'allowed' traffic here. Blocked traffic is logged by Security Logger.

		// Clean up old logs (keep last 24 hours or 1000 entries)
		// Optimization: Run cleanup randomly to avoid overhead on every request
		if ( rand( 1, 100 ) === 1 ) {
			$wpdb->query( "DELETE FROM $table WHERE timestamp < DATE_SUB(NOW(), INTERVAL 24 HOUR)" );
		}

		$wpdb->insert(
			$table,
			array(
				'ip_address' => $ip,
				'user_agent' => $user_agent,
				'url' => $url,
				'method' => $method,
				'status' => 'allowed', // Default, changed if blocked
				'timestamp' => current_time( 'mysql' ),
				'country_code' => self::get_country_code( $ip ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get country code helper
	 */
	private static function get_country_code( $ip ) {
		// Use Geo IP Service if available and cached
		// Avoid API call on every request! Only use if already cached.
		$cache_key = 'jwsai_geoip_' . md5( $ip );
		$cached = get_transient( $cache_key );
		
		if ( $cached && is_array( $cached ) ) {
			return $cached['country_code'];
		}
		return 'XX'; // Unknown/Not Cached
	}

	/**
	 * Create traffic log table
	 */
	public static function create_table() {
		global $wpdb;
		$table = $wpdb->prefix . 'jwsai_traffic_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			timestamp DATETIME NOT NULL,
			ip_address VARCHAR(45) NOT NULL,
			country_code VARCHAR(3) DEFAULT 'XX',
			url VARCHAR(255) NOT NULL,
			method VARCHAR(10) NOT NULL,
			user_agent VARCHAR(255),
			status VARCHAR(20) DEFAULT 'allowed',
			PRIMARY KEY (id),
			KEY timestamp (timestamp),
			KEY ip_address (ip_address)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * AJAX: Get live traffic data
	 */
	public static function ajax_get_live_traffic() {
		check_ajax_referer( 'jwsai_nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'jwsai_traffic_log';
		
		$last_id = isset( $_POST['last_id'] ) ? intval( $_POST['last_id'] ) : 0;

		$logs = $wpdb->get_results( 
			$wpdb->prepare( "SELECT * FROM $table WHERE id > %d ORDER BY id DESC LIMIT 50", $last_id )
		);

		wp_send_json_success( $logs );
	}
}

// Initialize
Jawara_Traffic_Logger::init();
