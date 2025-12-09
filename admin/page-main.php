<?php
/**
 * Halaman admin utama Cyber Jawara Security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore

?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="?page=jawara-shield-ai&tab=dashboard" class="nav-tab <?php echo $current_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Dashboard', 'jawara-web-shield-ai' ); ?>
		</a>
		<a href="?page=jawara-shield-ai&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Settings', 'jawara-web-shield-ai' ); ?>
		</a>
		<a href="?page=jawara-shield-ai&tab=advanced" class="nav-tab <?php echo $current_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Advanced Security', 'jawara-web-shield-ai' ); ?>
		</a>
		<a href="?page=jawara-shield-ai&tab=auth" class="nav-tab <?php echo $current_tab === 'auth' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Authentication', 'jawara-web-shield-ai' ); ?>
		</a>
		<a href="?page=jawara-shield-ai&tab=hardening" class="nav-tab <?php echo $current_tab === 'hardening' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Hardening', 'jawara-web-shield-ai' ); ?>
		</a>
		<a href="?page=jawara-shield-ai&tab=firewall" class="nav-tab <?php echo $current_tab === 'firewall' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Firewall IP', 'jawara-web-shield-ai' ); ?>
		</a>
		<a href="?page=jawara-shield-ai&tab=scan" class="nav-tab <?php echo $current_tab === 'scan' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Malware Scanner', 'jawara-web-shield-ai' ); ?>
		</a>
		<a href="?page=jawara-shield-ai&tab=logs" class="nav-tab <?php echo $current_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Security Logs', 'jawara-web-shield-ai' ); ?>
		</a>
	</nav>

	<div class="tab-content">
		<?php
		switch ( $current_tab ) {
			case 'dashboard':
				require_once JWSAI_PLUGIN_DIR . 'admin/tab-dashboard.php';
				break;
			case 'settings':
				require_once JWSAI_PLUGIN_DIR . 'admin/tab-settings.php';
				break;
			case 'advanced':
				require_once JWSAI_PLUGIN_DIR . 'admin/tab-advanced-security.php';
				break;
			case 'auth':
				require_once JWSAI_PLUGIN_DIR . 'admin/tab-2fa.php';
				break;
			case 'hardening':
				require_once JWSAI_PLUGIN_DIR . 'admin/tab-hardening.php';
				break;
			case 'firewall':
				require_once JWSAI_PLUGIN_DIR . 'admin/tab-firewall.php';
				break;
			case 'scan':
				require_once JWSAI_PLUGIN_DIR . 'admin/tab-scan.php';
				break;
			case 'logs':
				require_once JWSAI_PLUGIN_DIR . 'admin/tab-logs.php';
				break;
			default:
				require_once JWSAI_PLUGIN_DIR . 'admin/tab-dashboard.php';
		}
		?>
	</div>
</div>
