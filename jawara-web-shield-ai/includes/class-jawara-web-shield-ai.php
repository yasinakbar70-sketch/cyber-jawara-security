<?php
/**
 * Class Jawara_Web_Shield_AI
 * Class utama untuk mengelola semua fitur keamanan plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Web_Shield_AI {

	/**
	 * Instance singleton
	 */
	private static $instance = null;

	/**
	 * Dapatkan instance singleton
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Load text domain untuk i18n
		add_action( 'init', array( $this, 'load_text_domain' ) );

		// Inisialisasi admin menu dan settings
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Enqueue scripts dan styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX handlers
		add_action( 'wp_ajax_jwsai_scan_manual', array( $this, 'ajax_scan_manual' ) );
		add_action( 'wp_ajax_jwsai_add_blacklist_ip', array( $this, 'ajax_add_blacklist_ip' ) );
		add_action( 'wp_ajax_jwsai_remove_blacklist_ip', array( $this, 'ajax_remove_blacklist_ip' ) );
		add_action( 'wp_ajax_jwsai_add_whitelist_ip', array( $this, 'ajax_add_whitelist_ip' ) );
		add_action( 'wp_ajax_jwsai_remove_whitelist_ip', array( $this, 'ajax_remove_whitelist_ip' ) );

        // AJAX test telegram
        add_action( 'wp_ajax_jwsai_test_telegram', array( $this, 'ajax_test_telegram' ) );

		// Proteksi login
		add_action( 'wp_login_failed', array( $this, 'handle_login_failure' ) );
		add_filter( 'authenticate', array( $this, 'check_login_lockout' ), 30, 3 );

		// Firewall IP - prioritas tinggi untuk mengecek sebelum WordPress load
		add_action( 'plugins_loaded', array( $this, 'check_firewall_ip' ), 1 );

		// File integrity check - jalankan setiap jam
		add_action( 'init', array( $this, 'schedule_file_check' ) );
		add_action( 'jwsai_hourly_file_check', array( $this, 'run_file_integrity_check' ) );

		// Malware signature scan
		add_action( 'init', array( $this, 'schedule_signature_scan' ) );
		add_action( 'jwsai_daily_signature_scan', array( $this, 'run_signature_scan' ) );

		// Load helper classes
		require_once JWSAI_PLUGIN_DIR . 'includes/class-gemini-api.php';
		require_once JWSAI_PLUGIN_DIR . 'includes/class-telegram-notifier.php';
		require_once JWSAI_PLUGIN_DIR . 'includes/class-security-logger.php';
		require_once JWSAI_PLUGIN_DIR . 'includes/class-file-scanner.php';
		require_once JWSAI_PLUGIN_DIR . 'includes/class-malware-detector.php';
		require_once JWSAI_PLUGIN_DIR . 'includes/class-login-protector.php';

        // Admin notice
        add_action( 'admin_notices', array( $this, 'admin_activation_notice' ) );
	}

    /**
     * Notifikasi admin saat plugin aktif
     */
    public function admin_activation_notice() {
        if ( get_transient( 'jwsai_activation_notice' ) ) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Jawara Web Shield AI</strong> aktif dan siap melindungi website Anda!</p></div>';
            delete_transient( 'jwsai_activation_notice' );
        }
    }

    /**
     * AJAX: Tes koneksi Telegram
     */
    public function ajax_test_telegram() {
        check_ajax_referer( 'jwsai_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'jawara-web-shield-ai' ) );
        }
        $result = Jawara_Telegram_Notifier::send_security_alert(
            'Tes Koneksi Telegram',
            'Notifikasi ini dikirim otomatis untuk menguji koneksi bot dari Jawara Web Shield AI.',
            'low'
        );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }
        wp_send_json_success( __( 'Pesan berhasil dikirim ke Telegram!', 'jawara-web-shield-ai' ) );
    }

	/**
	 * Aktifkan plugin
	 */
	public static function activate() {
		// Buat tabel untuk log keamanan
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'jwsai_logs';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			event_type varchar(50) NOT NULL,
			severity varchar(20) NOT NULL,
			message longtext NOT NULL,
			file_path varchar(255),
			ip_address varchar(45),
			user_id bigint(20),
			ai_analysis longtext,
			PRIMARY KEY (id),
			KEY event_type (event_type),
			KEY timestamp (timestamp)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Set default options
		add_option( 'jwsai_login_attempts_limit', 5 );
		add_option( 'jwsai_lockout_duration', 30 );
		add_option( 'jwsai_telegram_enabled', 0 );
		add_option( 'jwsai_blacklist_ips', array() );
		add_option( 'jwsai_whitelist_ips', array() );

		// Schedule cron jobs
		if ( ! wp_next_scheduled( 'jwsai_hourly_file_check' ) ) {
			wp_schedule_event( time(), 'hourly', 'jwsai_hourly_file_check' );
		}

		if ( ! wp_next_scheduled( 'jwsai_daily_signature_scan' ) ) {
			wp_schedule_event( time() + 3600, 'daily', 'jwsai_daily_signature_scan' );
		}

        // Set admin notice
        set_transient( 'jwsai_activation_notice', 1, 60 );
	}

	/**
	 * Deaktifkan plugin
	 */
	public static function deactivate() {
		// Hapus scheduled events
		wp_clear_scheduled_hook( 'jwsai_hourly_file_check' );
		wp_clear_scheduled_hook( 'jwsai_daily_signature_scan' );
	}

	/**
	 * Load text domain
	 */
	public function load_text_domain() {
		load_plugin_textdomain( 'jawara-web-shield-ai', false, dirname( JWSAI_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Tambahkan admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Jawara Web Shield AI', 'jawara-web-shield-ai' ),
			__( 'Jawara Shield AI', 'jawara-web-shield-ai' ),
			'manage_options',
			'jawara-shield-ai',
			array( $this, 'render_admin_page' ),
			'dashicons-shield-alt',
			80
		);

		// Sub-menu akan ditambahkan di halaman admin
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'jwsai_settings', 'jwsai_gemini_api_key', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		register_setting( 'jwsai_settings', 'jwsai_telegram_token', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		register_setting( 'jwsai_settings', 'jwsai_telegram_chat_id', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );

		register_setting( 'jwsai_settings', 'jwsai_login_attempts_limit', array(
			'sanitize_callback' => 'intval',
		) );

		register_setting( 'jwsai_settings', 'jwsai_lockout_duration', array(
			'sanitize_callback' => 'intval',
		) );

		register_setting( 'jwsai_settings', 'jwsai_telegram_enabled', array(
			'sanitize_callback' => 'intval',
		) );

		register_setting( 'jwsai_settings', 'jwsai_blacklist_ips', array(
			'sanitize_callback' => array( $this, 'sanitize_ip_list' ),
			'type'              => 'array',
		) );

		register_setting( 'jwsai_settings', 'jwsai_whitelist_ips', array(
			'sanitize_callback' => array( $this, 'sanitize_ip_list' ),
			'type'              => 'array',
		) );
	}

	/**
	 * Sanitize IP list
	 */
	public function sanitize_ip_list( $ips ) {
		if ( is_string( $ips ) ) {
			$ips = array_filter( array_map( 'trim', explode( "\n", $ips ) ) );
		} elseif ( ! is_array( $ips ) ) {
			$ips = array();
		}

		return array_map( 'sanitize_text_field', $ips );
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'jawara-shield-ai' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'jwsai-admin-css',
			JWSAI_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			JWSAI_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'jwsai-admin-js',
			JWSAI_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			JWSAI_PLUGIN_VERSION,
			true
		);

		wp_localize_script( 'jwsai-admin-js', 'jwsaiL10n', array(
			'ajaxurl'               => admin_url( 'admin-ajax.php' ),
			'nonce'                 => wp_create_nonce( 'jwsai_nonce' ),
			'scanning'              => __( 'Scanning...', 'jawara-web-shield-ai' ),
			'scanComplete'          => __( 'Scan Complete', 'jawara-web-shield-ai' ),
			'error'                 => __( 'Error', 'jawara-web-shield-ai' ),
			'success'               => __( 'Success', 'jawara-web-shield-ai' ),
		) );
	}

	/**
	 * Render halaman admin utama
	 */
	public function render_admin_page() {
		require_once JWSAI_PLUGIN_DIR . 'admin/page-main.php';
	}

	/**
	 * AJAX: Scan manual
	 */
	public function ajax_scan_manual() {
		check_ajax_referer( 'jwsai_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'jawara-web-shield-ai' ) );
		}

		$file_scanner = new Jawara_File_Scanner();
		$results      = $file_scanner->scan_all_files();

		wp_send_json_success( $results );
	}

	/**
	 * AJAX: Tambah IP blacklist
	 */
	public function ajax_add_blacklist_ip() {
		check_ajax_referer( 'jwsai_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'jawara-web-shield-ai' ) );
		}

		$ip = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';

		if ( ! $this->is_valid_ip( $ip ) ) {
			wp_send_json_error( __( 'Invalid IP address', 'jawara-web-shield-ai' ) );
		}

		$blacklist = get_option( 'jwsai_blacklist_ips', array() );

		if ( ! in_array( $ip, $blacklist, true ) ) {
			$blacklist[] = $ip;
			update_option( 'jwsai_blacklist_ips', $blacklist );

			Jawara_Security_Logger::log( 'firewall', 'info', "IP $ip added to blacklist" );
			wp_send_json_success( __( 'IP added to blacklist', 'jawara-web-shield-ai' ) );
		} else {
			wp_send_json_error( __( 'IP already in blacklist', 'jawara-web-shield-ai' ) );
		}
	}

	/**
	 * AJAX: Hapus IP blacklist
	 */
	public function ajax_remove_blacklist_ip() {
		check_ajax_referer( 'jwsai_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'jawara-web-shield-ai' ) );
		}

		$ip        = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';
		$blacklist = get_option( 'jwsai_blacklist_ips', array() );
		$key       = array_search( $ip, $blacklist, true );

		if ( false !== $key ) {
			unset( $blacklist[ $key ] );
			update_option( 'jwsai_blacklist_ips', array_values( $blacklist ) );

			Jawara_Security_Logger::log( 'firewall', 'info', "IP $ip removed from blacklist" );
			wp_send_json_success( __( 'IP removed from blacklist', 'jawara-web-shield-ai' ) );
		} else {
			wp_send_json_error( __( 'IP not found in blacklist', 'jawara-web-shield-ai' ) );
		}
	}

	/**
	 * AJAX: Tambah IP whitelist
	 */
	public function ajax_add_whitelist_ip() {
		check_ajax_referer( 'jwsai_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'jawara-web-shield-ai' ) );
		}

		$ip = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';

		if ( ! $this->is_valid_ip( $ip ) ) {
			wp_send_json_error( __( 'Invalid IP address', 'jawara-web-shield-ai' ) );
		}

		$whitelist = get_option( 'jwsai_whitelist_ips', array() );

		if ( ! in_array( $ip, $whitelist, true ) ) {
			$whitelist[] = $ip;
			update_option( 'jwsai_whitelist_ips', $whitelist );

			Jawara_Security_Logger::log( 'firewall', 'info', "IP $ip added to whitelist" );
			wp_send_json_success( __( 'IP added to whitelist', 'jawara-web-shield-ai' ) );
		} else {
			wp_send_json_error( __( 'IP already in whitelist', 'jawara-web-shield-ai' ) );
		}
	}

	/**
	 * AJAX: Hapus IP whitelist
	 */
	public function ajax_remove_whitelist_ip() {
		check_ajax_referer( 'jwsai_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'jawara-web-shield-ai' ) );
		}

		$ip        = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';
		$whitelist = get_option( 'jwsai_whitelist_ips', array() );
		$key       = array_search( $ip, $whitelist, true );

		if ( false !== $key ) {
			unset( $whitelist[ $key ] );
			update_option( 'jwsai_whitelist_ips', array_values( $whitelist ) );

			Jawara_Security_Logger::log( 'firewall', 'info', "IP $ip removed from whitelist" );
			wp_send_json_success( __( 'IP removed from whitelist', 'jawara-web-shield-ai' ) );
		} else {
			wp_send_json_error( __( 'IP not found in whitelist', 'jawara-web-shield-ai' ) );
		}
	}

	/**
	 * Check login lockout
	 */
	public function check_login_lockout( $user, $username, $password ) {
		if ( empty( $username ) || empty( $password ) ) {
			return $user;
		}

		$login_protector = new Jawara_Login_Protector();
		$result          = $login_protector->check_login_attempt( $username );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $user;
	}

	/**
	 * Handle login failure
	 */
	public function handle_login_failure( $username ) {
		$login_protector = new Jawara_Login_Protector();
		$login_protector->record_failed_login( $username );
	}

	/**
	 * Check firewall IP
	 */
	public function check_firewall_ip() {
		$client_ip = $this->get_client_ip();
		$whitelist = get_option( 'jwsai_whitelist_ips', array() );
		$blacklist = get_option( 'jwsai_blacklist_ips', array() );

		// Jika IP di whitelist, izinkan
		if ( ! empty( $whitelist ) && in_array( $client_ip, $whitelist, true ) ) {
			return;
		}

		// Jika IP di blacklist, blokir
		if ( ! empty( $blacklist ) && in_array( $client_ip, $blacklist, true ) ) {
			Jawara_Security_Logger::log( 'firewall', 'high', "Blocked IP: $client_ip", $client_ip );
			wp_die( 'Access Denied (403): Your IP has been blocked by the firewall.', 'Forbidden', array( 'response' => 403 ) );
		}
	}

	/**
	 * Schedule file check
	 */
	public function schedule_file_check() {
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		if ( ! wp_next_scheduled( 'jwsai_hourly_file_check' ) ) {
			wp_schedule_event( time(), 'hourly', 'jwsai_hourly_file_check' );
		}
	}

	/**
	 * Run file integrity check
	 */
	public function run_file_integrity_check() {
		$file_scanner = new Jawara_File_Scanner();
		$file_scanner->check_integrity();
	}

	/**
	 * Schedule signature scan
	 */
	public function schedule_signature_scan() {
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		if ( ! wp_next_scheduled( 'jwsai_daily_signature_scan' ) ) {
			wp_schedule_event( time() + 3600, 'daily', 'jwsai_daily_signature_scan' );
		}
	}

	/**
	 * Run signature scan
	 */
	public function run_signature_scan() {
		$malware_detector = new Jawara_Malware_Detector();
		$malware_detector->scan_all_files();
	}

	/**
	 * Validasi IP address
	 */
	private function is_valid_ip( $ip ) {
		return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
	}

	/**
	 * Dapatkan client IP
	 */
	private function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwarded_for = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip            = trim( $forwarded_for[0] );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_FORWARDED'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_FORWARDED'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}

	/**
	 * Dapatkan client IP (helper static)
	 */
	public static function get_client_ip_static() {
		$instance = self::instance();
		return $instance->get_client_ip();
	}
}
