<?php
/**
 * Plugin Name: Jawara Web Shield AI
 * Plugin URI: https://github.com/instanwaofficial-glitch/Plugin.git
 * Description: Plugin keamanan WordPress tingkat lanjut dengan AI (Gemini API) untuk deteksi malware, proteksi login, firewall IP, dan notifikasi Telegram
 * Version: 1.0.0
 * Author: Moh Yasin Akbar
 * Author URI: git clone https://github.com/instanwaofficial-glitch
 * Copyright: 2025 Jawara Web Shield AI
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jawara-web-shield-ai
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 *
 * GitHub Plugin URI: https://github.com/instanwaofficial-glitch/Plugin
 * GitHub Branch: main
 *
 * ==================================================
 * INSTALASI:
 * ==================================================
 * 1. Download/Extract folder plugin ke wp-content/plugins/
 * 2. Aktifkan plugin dari Dashboard WordPress
 * 3. Pergi ke menu "Jawara Web Shield AI" di admin dashboard
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
define( 'JWSAI_PLUGIN_VERSION', '1.0.0' );
define( 'JWSAI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Muat class utama
require_once JWSAI_PLUGIN_DIR . 'includes/class-jawara-web-shield-ai.php';

// Integrasi auto-update dari GitHub (Plugin Update Checker)
if ( file_exists( JWSAI_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php' ) ) {
    require_once JWSAI_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';
    if ( class_exists( 'Puc_v4_Factory' ) ) {
        $jwsai_update_checker = Puc_v4_Factory::buildUpdateChecker(
            'https://github.com/instanwaofficial-glitch/Plugin/',
            __FILE__,
            'jawara-web-shield-ai'
        );
    }
}

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

/**
 * GitHub Plugin URI: https://github.com/instanwaofficial-glitch/Plugin
 * GitHub Branch: main
 */

require_once __DIR__ . '/includes/plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/instanwaofficial-glitch/Plugin/',
    __FILE__,
    'jawara-web-shield-ai'
);
