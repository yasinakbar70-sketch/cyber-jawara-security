/**
 * Cyber Jawara Security - DEVELOPER GUIDE
 * 
 * Panduan lengkap untuk developers yang ingin extend atau customize plugin
 */

// ===================================================================
// 1. HOOKS & FILTERS
// ===================================================================

/**
 * Filter: Scan hasil file integrity
 * 
 * @param array $files Array of files scanned
 * @return array Modified files array
 */
apply_filters( 'jwsai_file_scan_results', $files );

/**
 * Filter: Gemini API response
 * 
 * @param array $response API response
 * @param string $prompt Original prompt
 * @return array Modified response
 */
apply_filters( 'jwsai_gemini_response', $response, $prompt );

/**
 * Filter: Security log message
 * 
 * @param string $message Log message
 * @param string $event_type Event type
 * @param string $severity Severity level
 * @return string Modified message
 */
apply_filters( 'jwsai_log_message', $message, $event_type, $severity );

/**
 * Action: After file scan complete
 * 
 * @param array $results Scan results
 * @param array $suspicious_files Found suspicious files
 */
do_action( 'jwsai_scan_complete', $results, $suspicious_files );

/**
 * Action: Before Telegram notification
 * 
 * @param string $message Message to send
 * @param string $severity Severity level
 */
do_action( 'jwsai_before_telegram_notification', $message, $severity );

// ===================================================================
// 2. CLASS USAGE EXAMPLES
// ===================================================================

// Gemini API Analysis
$gemini = new Jawara_Gemini_API();
$result = $gemini->analyze_file_for_malware( '/path/to/file.php' );

if ( ! is_wp_error( $result ) ) {
	$analysis = Jawara_Gemini_API::parse_analysis( $result['analysis'] );
	echo 'Risk Level: ' . $analysis['risk_level'];
}

// Security Logging
Jawara_Security_Logger::log(
	'event_type',      // Event type
	'high',            // Severity: low, medium, high, critical
	'Event message',   // Message
	'/path/to/file',   // File path (optional)
	'192.168.1.1',     // IP address (optional)
	123,               // User ID (optional)
	$ai_analysis_array // AI analysis (optional, array)
);

// Get logs
$logs = Jawara_Security_Logger::get_logs(
	50,           // Limit
	0,            // Offset
	'malware',    // Event type filter (optional)
	'critical'    // Severity filter (optional)
);

// Telegram Notifications
Jawara_Telegram_Notifier::send_security_alert(
	'Alert Title',
	'Alert message body',
	'high' // Severity: low, medium, high, critical
);

Jawara_Telegram_Notifier::notify_malware_detected(
	'/path/to/file.php',
	'high',
	'AI analysis text'
);

// File Scanner
$file_scanner = new Jawara_File_Scanner();
$scan_results = $file_scanner->scan_all_files();

$file_scanner->check_integrity(); // Run integrity check

// Malware Detector
$malware_detector = new Jawara_Malware_Detector();
$suspicious_files = $malware_detector->scan_all_files();

$patterns = $malware_detector->scan_file( '/path/to/file.php' );

// Login Protector
$login_protector = new Jawara_Login_Protector();

// Record failed attempt
$login_protector->record_failed_login( 'username' );

// Check if locked out
$result = $login_protector->check_login_attempt( 'username' );
if ( is_wp_error( $result ) ) {
	echo $result->get_error_message();
}

// Get status
$lockout_status = $login_protector->get_lockout_status( '192.168.1.1' );
if ( $lockout_status ) {
	echo 'Remaining lockout: ' . $lockout_status . ' seconds';
}

// ===================================================================
// 3. OPTIONS MANAGEMENT
// ===================================================================

// Get API Key
$api_key = get_option( 'jwsai_gemini_api_key' );

// Get settings
$login_limit = get_option( 'jwsai_login_attempts_limit', 5 );
$lockout_duration = get_option( 'jwsai_lockout_duration', 30 );
$telegram_enabled = get_option( 'jwsai_telegram_enabled' );
$telegram_token = get_option( 'jwsai_telegram_token' );
$telegram_chat_id = get_option( 'jwsai_telegram_chat_id' );

// Get IP lists
$blacklist = get_option( 'jwsai_blacklist_ips', array() );
$whitelist = get_option( 'jwsai_whitelist_ips', array() );

// Update options
update_option( 'jwsai_gemini_api_key', 'new_key_here' );
update_option( 'jwsai_login_attempts_limit', 10 );

// ===================================================================
// 4. DATABASE QUERIES
// ===================================================================

global $wpdb;

$table_name = $wpdb->prefix . 'jwsai_logs';

// Get all logs
$logs = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 50" );

// Get critical events last 24 hours
$critical_logs = $wpdb->get_results( $wpdb->prepare(
	"SELECT * FROM $table_name 
	 WHERE severity IN ('high', 'critical') 
	 AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
	 ORDER BY timestamp DESC"
) );

// Count by event type
$event_counts = $wpdb->get_results(
	"SELECT event_type, COUNT(*) as count FROM $table_name GROUP BY event_type"
);

// Delete old logs
$wpdb->query(
	"DELETE FROM $table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)"
);

// ===================================================================
// 5. EXTENDING FUNCTIONALITY
// ===================================================================

/**
 * Example: Custom malware detector
 */
class My_Custom_Malware_Detector extends Jawara_Malware_Detector {
	public function scan_file( $file_path ) {
		// Call parent
		$patterns = parent::scan_file( $file_path );
		
		// Add custom patterns
		$content = file_get_contents( $file_path );
		if ( strpos( $content, 'my_custom_malware_pattern' ) !== false ) {
			$patterns[] = 'my_custom_malware_pattern';
		}
		
		return $patterns;
	}
}

/**
 * Example: Custom logger
 */
class My_Custom_Logger {
	public static function log_to_external_service( $event_type, $severity, $message ) {
		// Send to external logging service
		wp_remote_post( 'https://logs.example.com/api', array(
			'body' => array(
				'event' => $event_type,
				'severity' => $severity,
				'message' => $message,
			),
		) );
	}
}

// Hook ke logging
add_action( 'jwsai_security_event', function( $event_type, $severity, $message ) {
	My_Custom_Logger::log_to_external_service( $event_type, $severity, $message );
}, 10, 3 );

/**
 * Example: Custom notification
 */
add_action( 'jwsai_before_telegram_notification', function( $message, $severity ) {
	// Send to custom webhook
	wp_remote_post( 'https://webhook.example.com/security', array(
		'body' => array(
			'message' => $message,
			'severity' => $severity,
		),
	) );
}, 10, 2 );

// ===================================================================
// 6. TESTING EXAMPLES
// ===================================================================

/**
 * Test Gemini API connection
 */
function test_gemini_connection() {
	$gemini = new Jawara_Gemini_API();
	
	$result = $gemini->send_request( 'Test prompt: What is WordPress?' );
	
	if ( is_wp_error( $result ) ) {
		echo 'Error: ' . $result->get_error_message();
	} else {
		echo 'Success! Response: ' . $result['analysis'];
	}
}

/**
 * Test Telegram connection
 */
function test_telegram_connection() {
	$result = Jawara_Telegram_Notifier::send_security_alert(
		'Test Alert',
		'This is a test notification',
		'low'
	);
	
	if ( is_wp_error( $result ) ) {
		echo 'Error: ' . $result->get_error_message();
	} else {
		echo 'Message sent successfully!';
	}
}

/**
 * Test file scanning
 */
function test_file_scanning() {
	$file_scanner = new Jawara_File_Scanner();
	$results = $file_scanner->scan_all_files();
	
	echo 'Scanned: ' . $results['scanned'] . ' files';
	echo '<pre>' . print_r( $results['files'], true ) . '</pre>';
}

// ===================================================================
// 7. PERFORMANCE TIPS
// ===================================================================

/*
 * 1. File Scanning Optimization
 *    - Scan berjalan di background via cron, tidak blocking
 *    - Limit file size untuk AI analysis (50KB default)
 *    - Cache scan results dengan transient
 *
 * 2. Gemini API Optimization
 *    - Use wp_remote_post() dengan timeout 30s
 *    - Cache AI responses untuk file yang sama
 *    - Batch requests jika possible
 *    - Monitor API quota usage
 *
 * 3. Database Optimization
 *    - Index pada columns: event_type, timestamp, severity
 *    - Auto-cleanup logs older than 30 days
 *    - Use prepared statements untuk query security
 *
 * 4. Telegram Notification
 *    - Queue notifications jika API rate limit reached
 *    - Aggregate multiple alerts dalam single message
 *    - Use scheduled action untuk batch sending
 */

// ===================================================================
// 8. SECURITY BEST PRACTICES
// ===================================================================

/*
 * 1. Input Validation
 *    - Always use sanitize_text_field() untuk text input
 *    - Use filter_var( $ip, FILTER_VALIDATE_IP ) untuk IP
 *    - Never trust $_GET, $_POST, $_REQUEST
 *
 * 2. Output Escaping
 *    - esc_html() untuk HTML context
 *    - esc_attr() untuk HTML attributes
 *    - esc_url() untuk URLs
 *    - wp_json_encode() untuk JSON
 *
 * 3. Database Security
 *    - Always use prepared statements: $wpdb->prepare()
 *    - Never concatenate user input ke SQL query
 *    - Use $wpdb->get_var/get_row untuk single results
 *
 * 4. Nonce Verification
 *    - check_ajax_referer() untuk AJAX
 *    - wp_verify_nonce() untuk forms
 *    - wp_create_nonce() saat membuat forms
 *
 * 5. Capabilities Checking
 *    - current_user_can( 'manage_options' ) untuk admin
 *    - current_user_can( 'edit_posts' ) untuk posts
 *    - Custom capabilities untuk custom features
 */

// ===================================================================
// 9. DEBUGGING
// ===================================================================

// Enable debug logging
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

// Check logs
tail -f wp-content/debug.log

// Debug Gemini API
$gemini = new Jawara_Gemini_API();
$response = wp_remote_post( $url, $args );
error_log( 'Gemini Response: ' . print_r( $response, true ) );

// Debug file scanning
$file_scanner = new Jawara_File_Scanner();
$files = $file_scanner->scan_all_files();
error_log( 'Files scanned: ' . count( $files['files'] ) );

// ===================================================================
// 10. COMMON ISSUES & SOLUTIONS
// ===================================================================

/*
 * Issue: "API rate limit exceeded"
 * Solution: Implement request throttling, use transients for caching
 *
 * Issue: "Scan timeout"
 * Solution: Increase PHP timeout, use background cron jobs
 *
 * Issue: "Database size growing too large"
 * Solution: Run cleanup, archive old logs, use log retention policy
 *
 * Issue: "Telegram notifications not received"
 * Solution: Verify Bot Token & Chat ID, test API connection
 *
 * Issue: "File scan not detecting changes"
 * Solution: Verify baseline hashes, run manual scan, check file permissions
 */
