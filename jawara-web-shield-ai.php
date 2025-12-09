<?php
/**
 * Plugin Name: Cyber Jawara Security
 * Plugin URI: https://github.com/yasinakbar70-sketch/cyber-jawara-security
 * Description: Plugin keamanan WordPress tingkat lanjut dengan AI (Gemini API) untuk deteksi malware, proteksi login, firewall IP, dan notifikasi Telegram
 * Version: 2.1.6
 * Author: Moh Yasin Akbar
 * Author URI: git clone https://github.com/instanwaofficial-glitch
 * Copyright: 2025 Cyber Jawara Security
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jawara-web-shield-ai
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 *
 * GitHub Plugin URI: https://github.com/yasinakbar70-sketch/cyber-jawara-security
 * GitHub Branch: main
 *
 * ==================================================
 * INSTALASI:
 * ==================================================
 * 1. Download/Extract folder plugin ke wp-content/plugins/
 * 2. Aktifkan plugin dari Dashboard WordPress
 * 3. Pergi ke menu "Cyber Jawara Security" di admin dashboard
 * 4. Isi API Key Gemini dari https://makersuite.google.com/app/apikey
 * 5. Isi Token Telegram & Chat ID (opsional)
 * 6. Lakukan manual scan untuk baseline
 * 7. Plugin siap melindungi website!
 *
 * ==================================================
 * FITUR UTAMA:
 * ==================================================
 * - Sistem AI Gemini untuk analisis file & malware
 * - Proteksi login dengan rate limiting & lockout
 * - Firewall IP dengan blacklist/whitelist
 * - File integrity scanner dengan hash SHA1
 * - Malware signature scanner
 * - Notifikasi Telegram real-time
 * - Dashboard lengkap dengan logging
 *
 * ==================================================
 */

// Cegah akses langsung ke file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Definisikan konstanta plugin
define( 'JWSAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'JWSAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'JWSAI_PLUGIN_VERSION', '2.1.6' );
define( 'JWSAI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Muat class utama
require_once JWSAI_PLUGIN_DIR . 'includes/class-jawara-web-shield-ai.php';

// Muat GitHub Updater untuk auto-update dari repository
require_once JWSAI_PLUGIN_DIR . 'includes/class-github-updater.php';

// Inisialisasi plugin
Jawara_Web_Shield_AI::instance();

/**
 * Hook aktivasi plugin
 */
register_activation_hook( __FILE__, array( 'Jawara_Web_Shield_AI', 'activate' ) );

/**
 * Hook deaktivasi plugin
 */
register_deactivation_hook( __FILE__, array( 'Jawara_Web_Shield_AI', 'deactivate' ) );
