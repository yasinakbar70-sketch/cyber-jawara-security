<?php
/**
 * Class Jawara_Advanced_Firewall
 * Advanced firewall dengan SQL injection, XSS protection, rate limiting, dan geo-blocking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Advanced_Firewall {

	/**
	 * SQL Injection patterns
	 */
	private static $sql_patterns = array(
		'union.*select',
		'select.*from.*information_schema',
		'select.*from.*mysql',
		'extractvalue\s*\(',
		'updatexml\s*\(',
		'benchmark\s*\(',
		'sleep\s*\(\s*\d+\s*\)',
		'concat\s*\(.*char\(',
		'group_concat\s*\(',
		'load_file\s*\(',
		'outfile\s*["\']',
		'dumpfile\s*["\']',
		'into.*outfile',
		'procedure.*analyse',
		'waitfor.*delay',
		'pg_sleep\s*\(',
		'dbms_pipe\.receive_message',
		'declare.*@',
		'exec\s*\(',
		'execute\s*\(',
		'0x[0-9a-f]+',
		'char\(\d+\)',
		'--\s*$',
		'#\s*$',
		'\/\*.*\*\/',
	);

	/**
	 * XSS attack patterns
	 */
	private static $xss_patterns = array(
		'<script',
		'javascript:',
		'onerror\s*=',
		'onload\s*=',
		'onclick\s*=',
		'onmouseover\s*=',
		'onfocus\s*=',
		'<iframe',
		'<embed',
		'<object',
		'<applet',
		'<meta.*http-equiv',
		'<link.*stylesheet',
		'<img.*src\s*=\s*["\']?javascript:',
		'<svg.*onload',
		'<body.*onload',
		'document\.cookie',
		'document\.write',
		'window\.location',
		'eval\(',
		'expression\(',
		'vbscript:',
		'data:text\/html',
		'&#x',
		'&#',
	);

	/**
	 * Bad bot User-Agents
	 */
	private static $bad_bots = array(
		'semrush',
		'mj12bot',
		'ahrefsbot',
		'dotbot',
		'rogerbot',
		'exabot',
		'facebot',
		'ia_archiver',
		'scrapy',
		'curl',
		'wget',
		'python',
		'nikto',
		'sqlmap',
		'nmap',
		'masscan',
		'ZmEu',
		'webshag',
	);

	/**
	 * Initialize firewall
	 */
	public static function init() {
		// Run firewall checks early
		add_action( 'plugins_loaded', array( __CLASS__, 'run_firewall_checks' ), 1 );
	}

	/**
	 * Run all firewall checks
	 */
	public static function run_firewall_checks() {
		$client_ip = Jawara_Web_Shield_AI::get_client_ip_static();

		// 1. Geo-blocking check
		if ( self::check_geo_blocking( $client_ip ) ) {
			self::block_request( 'geo_blocked', 'Access denied: Your country is blocked', $client_ip );
		}

		// 2. Rate limiting check
		if ( self::check_rate_limit( $client_ip ) ) {
			self::block_request( 'rate_limit', 'Too many requests. Please slow down.', $client_ip );
		}

		// 3. Bad bot check
		if ( self::check_bad_bot() ) {
			self::block_request( 'bad_bot', 'Access denied: Bad bot detected', $client_ip );
		}

		// 4. SQL Injection check
		if ( self::check_sql_injection() ) {
			self::block_request( 'sql_injection', 'Access denied: SQL injection attempt detected', $client_ip );
		}

		// 5. XSS attack check
		if ( self::check_xss_attack() ) {
			self::block_request( 'xss_attack', 'Access denied: XSS attack detected', $client_ip );
		}
	}

	/**
	 * Check geo-blocking
	 *
	 * @param string $ip IP address
	 * @return bool True if should block
	 */
	private static function check_geo_blocking( $ip ) {
		if ( ! get_option( 'jwsai_geo_blocking_enabled' ) ) {
			return false;
		}

		return Jawara_Geo_IP_Service::is_country_blocked( $ip );
	}

	/**
	 * Check rate limiting
	 *
	 * @param string $ip IP address
	 * @return bool True if should block
	 */
	private static function check_rate_limit( $ip ) {
		if ( ! get_option( 'jwsai_rate_limiting_enabled' ) ) {
			return false;
		}

		$limit = get_option( 'jwsai_rate_limit_requests', 60 ); // requests per minute
		$window = 60; // seconds

		$transient_key = 'jwsai_rate_' . md5( $ip );
		$requests = get_transient( $transient_key );

		if ( false === $requests ) {
			set_transient( $transient_key, 1, $window );
			return false;
		}

		if ( $requests >= $limit ) {
			return true;
		}

		set_transient( $transient_key, $requests + 1, $window );
		return false;
	}

	/**
	 * Check for bad bots
	 *
	 * @return bool True if bad bot
	 */
	private static function check_bad_bot() {
		if ( ! get_option( 'jwsai_block_bad_bots' ) ) {
			return false;
		}

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';

		if ( empty( $user_agent ) ) {
			// Block empty user agents
			return true;
		}

		foreach ( self::$bad_bots as $bot ) {
			if ( false !== strpos( $user_agent, $bot ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check for SQL injection attempts
	 *
	 * @return bool True if SQL injection detected
	 */
	private static function check_sql_injection() {
		if ( ! get_option( 'jwsai_sql_injection_protection' ) ) {
			return false;
		}

		// Check GET parameters
		foreach ( $_GET as $key => $value ) {
			if ( self::contains_sql_pattern( $value ) ) {
				return true;
			}
		}

		// Check POST parameters
		foreach ( $_POST as $key => $value ) {
			if ( is_string( $value ) && self::contains_sql_pattern( $value ) ) {
				return true;
			}
		}

		// Check REQUEST_URI
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			if ( self::contains_sql_pattern( $request_uri ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check for XSS attacks
	 *
	 * @return bool True if XSS detected
	 */
	private static function check_xss_attack() {
		if ( ! get_option( 'jwsai_xss_protection' ) ) {
			return false;
		}

		// Check GET parameters
		foreach ( $_GET as $key => $value ) {
			if ( self::contains_xss_pattern( $value ) ) {
				return true;
			}
		}

		// Check POST parameters
		foreach ( $_POST as $key => $value ) {
			if ( is_string( $value ) && self::contains_xss_pattern( $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if string contains SQL injection pattern
	 *
	 * @param string $string String to check
	 * @return bool
	 */
	private static function contains_sql_pattern( $string ) {
		if ( ! is_string( $string ) ) {
			return false;
		}

		$string = strtolower( urldecode( $string ) );

		foreach ( self::$sql_patterns as $pattern ) {
			if ( preg_match( '/' . $pattern . '/i', $string ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if string contains XSS pattern
	 *
	 * @param string $string String to check
	 * @return bool
	 */
	private static function contains_xss_pattern( $string ) {
		if ( ! is_string( $string ) ) {
			return false;
		}

		$string = strtolower( html_entity_decode( $string ) );

		foreach ( self::$xss_patterns as $pattern ) {
			if ( false !== strpos( $string, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Block request and exit
	 *
	 * @param string $reason Reason for blocking
	 * @param string $message Message to display
	 * @param string $ip IP address
	 */
	private static function block_request( $reason, $message, $ip ) {
		// Log the block
		Jawara_Security_Logger::log(
			'firewall_block',
			'high',
			sprintf( '[%s] %s from IP: %s', $reason, $message, $ip ),
			$ip
		);

		// Send notification if enabled
		if ( get_option( 'jwsai_telegram_enabled' ) ) {
			Jawara_Telegram_Notifier::send_security_alert(
				'Firewall Block',
				sprintf( "Reason: %s\nIP: %s\nUser-Agent: %s", $reason, $ip, $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown' ),
				'high'
			);
		}

		// Return 403 Forbidden
		status_header( 403 );
		wp_die(
			esc_html( $message ),
			'Access Denied',
			array(
				'response' => 403,
				'back_link' => false,
			)
		);
	}

	/**
	 * Add custom firewall rule
	 *
	 * @param array $rule Rule configuration
	 * @return bool
	 */
	public static function add_custom_rule( $rule ) {
		$rules = get_option( 'jwsai_custom_firewall_rules', array() );
		$rules[] = array(
			'id' => uniqid(),
			'name' => $rule['name'],
			'pattern' => $rule['pattern'],
			'action' => $rule['action'],
			'enabled' => true,
			'created' => current_time( 'mysql' ),
		);
		return update_option( 'jwsai_custom_firewall_rules', $rules );
	}

	/**
	 * Get custom rules
	 *
	 * @return array
	 */
	public static function get_custom_rules() {
		return get_option( 'jwsai_custom_firewall_rules', array() );
	}
}

// Initialize firewall
Jawara_Advanced_Firewall::init();
