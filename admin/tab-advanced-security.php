<?php
/**
 * Tab Advanced Security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings
$geo_blocking_enabled = get_option( 'jwsai_geo_blocking_enabled' );
$blocked_countries = get_option( 'jwsai_blocked_countries', array() );
$rate_limiting_enabled = get_option( 'jwsai_rate_limiting_enabled' );
$rate_limit_requests = get_option( 'jwsai_rate_limit_requests', 60 );
$block_bad_bots = get_option( 'jwsai_block_bad_bots' );
$sql_injection_protection = get_option( 'jwsai_sql_injection_protection' );
$xss_protection = get_option( 'jwsai_xss_protection' );
$auto_blacklist_threats = get_option( 'jwsai_auto_blacklist_threats' );
$abuseipdb_api_key = get_option( 'jwsai_abuseipdb_api_key' );

// Get threat intelligence stats
$threat_stats = Jawara_Threat_Intelligence::get_statistics();
?>

<div class="postbox">
	<h2><?php esc_html_e( 'Threat Intelligence', 'jawara-web-shield-ai' ); ?></h2>
	<div class="inside">
		<div class="jwsai-stats">
			<div class="jwsai-stat-card success">
				<h3><?php esc_html_e( 'Total Checked', 'jawara-web-shield-ai' ); ?></h3>
				<p class="stat-value"><?php echo esc_html( number_format( $threat_stats['total_checked'] ) ); ?></p>
			</div>
			<div class="jwsai-stat-card warning">
				<h3><?php esc_html_e( 'Malicious IPs', 'jawara-web-shield-ai' ); ?></h3>
				<p class="stat-value"><?php echo esc_html( number_format( $threat_stats['malicious'] ) ); ?></p>
			</div>
			<div class="jwsai-stat-card info">
				<h3><?php esc_html_e( 'Clean IPs', 'jawara-web-shield-ai' ); ?></h3>
				<p class="stat-value"><?php echo esc_html( number_format( $threat_stats['clean'] ) ); ?></p>
			</div>
		</div>

		<form method="post" action="options.php">
			<?php settings_fields( 'jwsai_settings' ); ?>

			<h3><?php esc_html_e( 'AbuseIPDB Integration', 'jawara-web-shield-ai' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="jwsai_abuseipdb_api_key">
							<?php esc_html_e( 'AbuseIPDB API Key', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="password" 
							   id="jwsai_abuseipdb_api_key" 
							   name="jwsai_abuseipdb_api_key" 
							   value="<?php echo esc_attr( $abuseipdb_api_key ); ?>" 
							   class="regular-text" />
						<p class="description">
							<?php esc_html_e( 'Get free API key from https://www.abuseipdb.com/', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_auto_blacklist_threats">
							<?php esc_html_e( 'Auto-Blacklist Malicious IPs', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_auto_blacklist_threats" 
							   name="jwsai_auto_blacklist_threats" 
							   value="1" 
							   <?php checked( $auto_blacklist_threats, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Automatically add IPs with high abuse score to blacklist', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
</div>

<div class="postbox jwsai-mt-20">
	<h2><?php esc_html_e( 'Advanced Firewall', 'jawara-web-shield-ai' ); ?></h2>
	<div class="inside">
		<form method="post" action="options.php">
			<?php settings_fields( 'jwsai_settings' ); ?>

			<h3><?php esc_html_e( 'Attack Protection', 'jawara-web-shield-ai' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="jwsai_sql_injection_protection">
							<?php esc_html_e( 'SQL Injection Protection', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_sql_injection_protection" 
							   name="jwsai_sql_injection_protection" 
							   value="1" 
							   <?php checked( $sql_injection_protection, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Block SQL injection attempts', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_xss_protection">
							<?php esc_html_e( 'XSS Attack Protection', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_xss_protection" 
							   name="jwsai_xss_protection" 
							   value="1" 
							   <?php checked( $xss_protection, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Block XSS (Cross-Site Scripting) attacks', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_block_bad_bots">
							<?php esc_html_e( 'Block Bad Bots', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_block_bad_bots" 
							   name="jwsai_block_bad_bots" 
							   value="1" 
							   <?php checked( $block_bad_bots, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Block known malicious bots and scrapers', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Rate Limiting', 'jawara-web-shield-ai' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="jwsai_rate_limiting_enabled">
							<?php esc_html_e( 'Enable Rate Limiting', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_rate_limiting_enabled" 
							   name="jwsai_rate_limiting_enabled" 
							   value="1" 
							   <?php checked( $rate_limiting_enabled, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Limit requests per IP address', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_rate_limit_requests">
							<?php esc_html_e( 'Requests per Minute', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="number" 
							   id="jwsai_rate_limit_requests" 
							   name="jwsai_rate_limit_requests" 
							   value="<?php echo esc_attr( $rate_limit_requests ); ?>" 
							   class="small-text" 
							   min="10"
							   max="1000" />
						<p class="description">
							<?php esc_html_e( 'Maximum requests allowed per minute per IP', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Geo-Blocking', 'jawara-web-shield-ai' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="jwsai_geo_blocking_enabled">
							<?php esc_html_e( 'Enable Geo-Blocking', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="jwsai_geo_blocking_enabled" 
							   name="jwsai_geo_blocking_enabled" 
							   value="1" 
							   <?php checked( $geo_blocking_enabled, 1 ); ?> />
						<p class="description">
							<?php esc_html_e( 'Block access from specific countries', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="jwsai_blocked_countries">
							<?php esc_html_e( 'Blocked Countries', 'jawara-web-shield-ai' ); ?>
						</label>
					</th>
					<td>
						<select id="jwsai_blocked_countries" 
								name="jwsai_blocked_countries[]" 
								multiple 
								size="10" 
								style="width: 300px;">
							<?php
							$countries = Jawara_Geo_IP_Service::get_country_list();
							foreach ( $countries as $code => $name ) {
								$selected = in_array( $code, $blocked_countries, true ) ? 'selected' : '';
								echo '<option value="' . esc_attr( $code ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $name ) . ' (' . esc_html( $code ) . ')</option>';
							}
							?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Hold Ctrl/Cmd to select multiple countries', 'jawara-web-shield-ai' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
</div>
