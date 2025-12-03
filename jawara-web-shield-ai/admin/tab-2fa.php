<?php
/**
 * Tab Authentication & Access
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings
$two_factor_enabled = get_option( 'jwsai_2fa_enabled' );
$force_2fa_admins = get_option( 'jwsai_force_2fa_admins' );
$recaptcha_enabled = get_option( 'jwsai_recaptcha_enabled' );
$recaptcha_site_key = get_option( 'jwsai_recaptcha_site_key' );
$recaptcha_secret_key = get_option( 'jwsai_recaptcha_secret_key' );
$notify_new_device = get_option( 'jwsai_notify_new_device' );

// User 2FA status
$user_id = get_current_user_id();
$user_2fa_secret = get_user_meta( $user_id, 'jwsai_2fa_secret', true );
$is_2fa_active = ! empty( $user_2fa_secret );
?>

<div class="postbox">
	<h2><?php esc_html_e( 'Two-Factor Authentication (2FA)', 'jawara-web-shield-ai' ); ?></h2>
	<div class="inside">
		
		<!-- Global Settings -->
		<form method="post" action="options.php">
			<?php settings_fields( 'jwsai_settings' ); ?>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="jwsai_2fa_enabled">
							<?php esc_html_e( 'Enable 2FA System', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_2fa_enabled" 
							   name="jwsai_2fa_enabled" 
							   value="1" 
							   <?php checked( $two_factor_enabled, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Allow users to use Two-Factor Authentication', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_force_2fa_admins">
							<?php esc_html_e( 'Force 2FA for Admins', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_force_2fa_admins" 
							   name="jwsai_force_2fa_admins" 
							   value="1" 
							   <?php checked( $force_2fa_admins, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Require all administrators to set up 2FA', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Save Global Settings' ); ?>
		</form>

		<hr>

		<!-- User Personal 2FA Setup -->
		<h3><?php esc_html_e( 'Your 2FA Status', 'jawara-web-shield-ai' ); ?></h3>
		
		<div id="jwsai-2fa-status-area">
			<?php if ( $is_2fa_active ) : ?>
				<div class="notice notice-success inline">
					<p><strong><?php esc_html_e( '2FA is ENABLED for your account.', 'jawara-web-shield-ai' ); ?></strong></p>
				</div>
				<p>
					<button type="button" class="button button-secondary" id="jwsai-show-backup-codes">
						<?php esc_html_e( 'Show Backup Codes', 'jawara-web-shield-ai' ); ?>
					</button>
					<button type="button" class="button button-link-delete" id="jwsai-disable-2fa">
						<?php esc_html_e( 'Disable 2FA', 'jawara-web-shield-ai' ); ?>
					</button>
				</p>
				<div id="jwsai-backup-codes-display" style="display:none; margin-top:10px; padding:10px; background:#f9f9f9; border:1px solid #ddd;">
					<p><?php esc_html_e( 'Save these backup codes in a safe place:', 'jawara-web-shield-ai' ); ?></p>
					<code id="jwsai-backup-codes-list" style="display:block; white-space:pre-wrap;"></code>
				</div>
			<?php else : ?>
				<div class="notice notice-warning inline">
					<p><strong><?php esc_html_e( '2FA is DISABLED for your account.', 'jawara-web-shield-ai' ); ?></strong></p>
				</div>
				<p>
					<button type="button" class="button button-primary" id="jwsai-setup-2fa">
						<?php esc_html_e( 'Setup 2FA Now', 'jawara-web-shield-ai' ); ?>
					</button>
				</p>
				
				<!-- Setup Modal Area -->
				<div id="jwsai-2fa-setup-area" style="display:none; margin-top:20px; border:1px solid #ccc; padding:20px;">
					<h4>1. Scan QR Code</h4>
					<div id="jwsai-qr-code"></div>
					<p><?php esc_html_e( 'Or enter this secret key manually:', 'jawara-web-shield-ai' ); ?> <code id="jwsai-secret-key"></code></p>
					
					<h4>2. Enter Verification Code</h4>
					<p>
						<input type="text" id="jwsai-verify-code" class="regular-text" placeholder="123456" maxlength="6">
						<button type="button" class="button button-primary" id="jwsai-verify-2fa">Verify & Enable</button>
					</p>
					<div id="jwsai-setup-message"></div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="postbox jwsai-mt-20">
	<h2><?php esc_html_e( 'Advanced Login Protection', 'jawara-web-shield-ai' ); ?></h2>
	<div class="inside">
		<form method="post" action="options.php">
			<?php settings_fields( 'jwsai_settings' ); ?>

			<h3><?php esc_html_e( 'Google reCAPTCHA v2', 'jawara-web-shield-ai' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="jwsai_recaptcha_enabled">
							<?php esc_html_e( 'Enable reCAPTCHA', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_recaptcha_enabled" 
							   name="jwsai_recaptcha_enabled" 
							   value="1" 
							   <?php checked( $recaptcha_enabled, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Add "I\'m not a robot" checkbox to login form', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_recaptcha_site_key">
							<?php esc_html_e( 'Site Key', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="text" 
							   id="jwsai_recaptcha_site_key" 
							   name="jwsai_recaptcha_site_key" 
							   value="<?php echo esc_attr( $recaptcha_site_key ); ?>" 
							   class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_recaptcha_secret_key">
							<?php esc_html_e( 'Secret Key', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="password" 
							   id="jwsai_recaptcha_secret_key" 
							   name="jwsai_recaptcha_secret_key" 
							   value="<?php echo esc_attr( $recaptcha_secret_key ); ?>" 
							   class="regular-text" />
						<p class="description">
							<?php esc_html_e( 'Get keys from https://www.google.com/recaptcha/admin', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Device Security', 'jawara-web-shield-ai' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="jwsai_notify_new_device">
							<?php esc_html_e( 'New Device Notification', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_notify_new_device" 
							   name="jwsai_notify_new_device" 
							   value="1" 
							   <?php checked( $notify_new_device, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Send email when user logs in from a new device/browser', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Setup 2FA
	$('#jwsai-setup-2fa').on('click', function() {
		$(this).prop('disabled', true);
		
		$.ajax({
			url: jwsaiL10n.ajaxurl,
			type: 'POST',
			data: {
				action: 'jwsai_setup_2fa',
				nonce: jwsaiL10n.nonce
			},
			success: function(response) {
				if (response.success) {
					$('#jwsai-qr-code').html('<img src="' + response.data.qr_url + '">');
					$('#jwsai-secret-key').text(response.data.secret);
					$('#jwsai-2fa-setup-area').slideDown();
				} else {
					alert('Error: ' + response.data);
				}
			}
		});
	});

	// Verify 2FA
	$('#jwsai-verify-2fa').on('click', function() {
		var code = $('#jwsai-verify-code').val();
		if (code.length < 6) {
			alert('Please enter 6-digit code');
			return;
		}

		$.ajax({
			url: jwsaiL10n.ajaxurl,
			type: 'POST',
			data: {
				action: 'jwsai_verify_2fa_setup',
				nonce: jwsaiL10n.nonce,
				code: code
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
					location.reload();
				} else {
					$('#jwsai-setup-message').html('<span class="text-danger">' + response.data + '</span>');
				}
			}
		});
	});

	// Disable 2FA
	$('#jwsai-disable-2fa').on('click', function() {
		if (!confirm('Are you sure you want to disable 2FA? This will lower your account security.')) {
			return;
		}

		$.ajax({
			url: jwsaiL10n.ajaxurl,
			type: 'POST',
			data: {
				action: 'jwsai_disable_2fa',
				nonce: jwsaiL10n.nonce
			},
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert('Error: ' + response.data);
				}
			}
		});
	});
});
</script>
