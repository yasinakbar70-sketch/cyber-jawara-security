<?php
/**
 * Tab Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gemini_api_key = get_option( 'jwsai_gemini_api_key' );
$telegram_token = get_option( 'jwsai_telegram_token' );
$telegram_chat_id = get_option( 'jwsai_telegram_chat_id' );
$telegram_enabled = get_option( 'jwsai_telegram_enabled' );
$login_attempts_limit = get_option( 'jwsai_login_attempts_limit', 5 );
$lockout_duration = get_option( 'jwsai_lockout_duration', 30 );
?>

<div class="postbox">
	<h2><?php esc_html_e( 'Gemini API Configuration', 'jawara-web-shield-ai' ); ?></h2>
	<div class="inside">
		<form method="post" action="options.php">
			<?php settings_fields( 'jwsai_settings' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="jwsai_gemini_api_key">
							<?php esc_html_e( 'Gemini API Key', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="password" 
							   id="jwsai_gemini_api_key" 
							   name="jwsai_gemini_api_key" 
							   value="<?php echo esc_attr( $gemini_api_key ); ?>" 
							   class="regular-text" />
						<p class="description">
							<?php esc_html_e( 'Get your API key from https://makersuite.google.com/app/apikey', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Login Protection', 'jawara-web-shield-ai' ); ?></h3>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="jwsai_login_attempts_limit">
							<?php esc_html_e( 'Login Attempts Limit', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="number" 
							   id="jwsai_login_attempts_limit" 
							   name="jwsai_login_attempts_limit" 
							   value="<?php echo esc_attr( $login_attempts_limit ); ?>" 
							   class="small-text" 
							   min="1"
							   max="100" />
						<p class="description">
							<?php esc_html_e( 'Maximum login attempts before lockout', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_lockout_duration">
							<?php esc_html_e( 'Lockout Duration (minutes)', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="number" 
							   id="jwsai_lockout_duration" 
							   name="jwsai_lockout_duration" 
							   value="<?php echo esc_attr( $lockout_duration ); ?>" 
							   class="small-text" 
							   min="1"
							   max="1440" />
						<p class="description">
							<?php esc_html_e( 'How long to lock out an IP after too many failed attempts', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Telegram Notifications', 'jawara-web-shield-ai' ); ?></h3>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="jwsai_telegram_enabled">
							<?php esc_html_e( 'Enable Telegram Alerts', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_telegram_enabled" 
							   name="jwsai_telegram_enabled" 
							   value="1" 
							   <?php checked( $telegram_enabled, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Enable security alerts via Telegram', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_telegram_token">
							<?php esc_html_e( 'Telegram Bot Token', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="password" 
							   id="jwsai_telegram_token" 
							   name="jwsai_telegram_token" 
							   value="<?php echo esc_attr( $telegram_token ); ?>" 
							   class="regular-text" />
						<p class="description">
							<?php esc_html_e( 'Get from @BotFather on Telegram', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_telegram_chat_id">
							<?php esc_html_e( 'Telegram Chat ID', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="text" 
							   id="jwsai_telegram_chat_id" 
							   name="jwsai_telegram_chat_id" 
							   value="<?php echo esc_attr( $telegram_chat_id ); ?>" 
							   class="regular-text" />
						<p class="description">
							<?php esc_html_e( 'Your Telegram Chat ID (get from @userinfobot)', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
</div>
