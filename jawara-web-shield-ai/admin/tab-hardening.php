<?php
/**
 * Tab Security Hardening
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings
$disable_xmlrpc = get_option( 'jwsai_disable_xmlrpc' );
$hide_wp_version = get_option( 'jwsai_hide_wp_version' );
$disable_file_editing = get_option( 'jwsai_disable_file_editing' );
$enable_security_headers = get_option( 'jwsai_enable_security_headers' );
$block_user_enumeration = get_option( 'jwsai_block_user_enumeration' );
$disable_directory_browsing = get_option( 'jwsai_disable_directory_browsing' );

// Handle .htaccess update on save
if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
	Jawara_Security_Hardening::apply_htaccess_rules();
}
?>

<div class="postbox">
	<h2><?php esc_html_e( 'Security Hardening', 'jawara-web-shield-ai' ); ?></h2>
	<div class="inside">
		<p><?php esc_html_e( 'Apply WordPress security best practices to harden your website against attacks.', 'jawara-web-shield-ai' ); ?></p>
		
		<form method="post" action="options.php">
			<?php settings_fields( 'jwsai_settings' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="jwsai_disable_xmlrpc">
							<?php esc_html_e( 'Disable XML-RPC', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_disable_xmlrpc" 
							   name="jwsai_disable_xmlrpc" 
							   value="1" 
							   <?php checked( $disable_xmlrpc, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Prevents XML-RPC attacks (DDoS, Brute Force). Recommended if you don\'t use Jetpack or mobile app.', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_hide_wp_version">
							<?php esc_html_e( 'Hide WordPress Version', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_hide_wp_version" 
							   name="jwsai_hide_wp_version" 
							   value="1" 
							   <?php checked( $hide_wp_version, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Hides the WordPress version number from page source to prevent targeted exploits.', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_disable_file_editing">
							<?php esc_html_e( 'Disable File Editor', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_disable_file_editing" 
							   name="jwsai_disable_file_editing" 
							   value="1" 
							   <?php checked( $disable_file_editing, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Disables the built-in file editor for plugins and themes to prevent code injection if admin access is compromised.', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_enable_security_headers">
							<?php esc_html_e( 'Add Security Headers', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_enable_security_headers" 
							   name="jwsai_enable_security_headers" 
							   value="1" 
							   <?php checked( $enable_security_headers, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Adds X-Frame-Options, X-XSS-Protection, and other security headers.', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_block_user_enumeration">
							<?php esc_html_e( 'Block User Enumeration', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_block_user_enumeration" 
							   name="jwsai_block_user_enumeration" 
							   value="1" 
							   <?php checked( $block_user_enumeration, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Prevents hackers from finding usernames via ?author=N scans.', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_disable_directory_browsing">
							<?php esc_html_e( 'Disable Directory Browsing', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_disable_directory_browsing" 
							   name="jwsai_disable_directory_browsing" 
							   value="1" 
							   <?php checked( $disable_directory_browsing, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Prevents listing of directory contents (modifies .htaccess).', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
</div>
