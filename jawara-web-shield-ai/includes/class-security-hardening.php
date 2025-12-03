<?php
/**
 * Class Jawara_Security_Hardening
 * Applies WordPress security best practices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Security_Hardening {

	/**
	 * Initialize hardening rules
	 */
	public static function init() {
		// 1. Disable XML-RPC
		if ( get_option( 'jwsai_disable_xmlrpc' ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			add_filter( 'xmlrpc_methods', array( __CLASS__, 'disable_xmlrpc_pingback' ) );
		}

		// 2. Hide WordPress Version
		if ( get_option( 'jwsai_hide_wp_version' ) ) {
			remove_action( 'wp_head', 'wp_generator' );
			add_filter( 'the_generator', '__return_empty_string' );
		}

		// 3. Disable File Editing
		if ( get_option( 'jwsai_disable_file_editing' ) ) {
			if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
				define( 'DISALLOW_FILE_EDIT', true );
			}
		}

		// 4. Security Headers
		if ( get_option( 'jwsai_enable_security_headers' ) ) {
			add_action( 'send_headers', array( __CLASS__, 'add_security_headers' ) );
		}

		// 5. Disable Directory Browsing (via .htaccess - handled on activation/save)
		
		// 6. Block User Enumeration
		if ( get_option( 'jwsai_block_user_enumeration' ) ) {
			if ( ! is_admin() && isset( $_REQUEST['author'] ) && preg_match( '/\d/', $_REQUEST['author'] ) ) {
				wp_die( 'Access Denied', 'Jawara Web Shield AI', array( 'response' => 403 ) );
			}
		}
	}

	/**
	 * Disable XML-RPC Pingback
	 */
	public static function disable_xmlrpc_pingback( $methods ) {
		unset( $methods['pingback.ping'] );
		return $methods;
	}

	/**
	 * Add Security Headers
	 */
	public static function add_security_headers() {
		if ( headers_sent() ) {
			return;
		}

		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-XSS-Protection: 1; mode=block' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		
		// Strict Transport Security (HSTS) - only if SSL is detected
		if ( is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
		}
	}

	/**
	 * Apply .htaccess rules
	 */
	public static function apply_htaccess_rules() {
		$htaccess_file = ABSPATH . '.htaccess';
		
		if ( ! file_exists( $htaccess_file ) || ! is_writable( $htaccess_file ) ) {
			return new WP_Error( 'file_error', 'Cannot write to .htaccess file' );
		}

		$rules = "\n# BEGIN Jawara Web Shield AI Hardening\n";
		
		// Disable Directory Browsing
		if ( get_option( 'jwsai_disable_directory_browsing' ) ) {
			$rules .= "Options -Indexes\n";
		}

		// Protect System Files
		$rules .= "<FilesMatch \"^\.\">\nOrder allow,deny\nDeny from all\n</FilesMatch>\n";
		$rules .= "<FilesMatch \"^(wp-config\.php|readme\.html|license\.txt)\">\nOrder allow,deny\nDeny from all\n</FilesMatch>\n";

		$rules .= "# END Jawara Web Shield AI Hardening\n";

		// Read existing content
		$content = file_get_contents( $htaccess_file );
		
		// Remove old rules
		$content = preg_replace( '/# BEGIN Jawara Web Shield AI Hardening(.*)# END Jawara Web Shield AI Hardening\s*/s', '', $content );
		
		// Add new rules
		if ( get_option( 'jwsai_disable_directory_browsing' ) ) {
			$content = $rules . $content;
		}

		file_put_contents( $htaccess_file, $content );
		return true;
	}
}

// Initialize
Jawara_Security_Hardening::init();
