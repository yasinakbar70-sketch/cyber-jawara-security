<?php
/**
 * Halaman admin utama Jawara Web Shield AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings'; // phpcs:ignore

?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="?page=jawara-shield-ai&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Settings', 'jawara-web-shield-ai' ); ?>
		</a>
		<a href="?page=jawara-shield-ai&tab=firewall" class="nav-tab <?php echo $current_tab === 'firewall' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Firewall IP', 'jawara-web-shield-ai' ); ?>
		</a>
		<a href="?page=jawara-shield-ai&tab=scan" class="nav-tab <?php echo $current_tab === 'scan' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Scan AI', 'jawara-web-shield-ai' ); ?>
		</a>
		<a href="?page=jawara-shield-ai&tab=logs" class="nav-tab <?php echo $current_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Security Logs', 'jawara-web-shield-ai' ); ?>
		</a>
	</nav>

	<div class="tab-content">
		<?php
		switch ( $current_tab ) {
			case 'settings':
				require_once JWSAI_PLUGIN_DIR . 'admin/tab-settings.php';
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
				require_once JWSAI_PLUGIN_DIR . 'admin/tab-settings.php';
		}
		?>
	</div>
</div>
