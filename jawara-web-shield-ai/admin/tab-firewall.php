<?php
/**
 * Tab Firewall IP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blacklist = get_option( 'jwsai_blacklist_ips', array() );
$whitelist = get_option( 'jwsai_whitelist_ips', array() );
?>

<div class="postbox">
	<h2><?php esc_html_e( 'IP Firewall Management', 'jawara-web-shield-ai' ); ?></h2>
	<div class="inside">
		<p><?php esc_html_e( 'Manage blacklist and whitelist IP addresses for your website.', 'jawara-web-shield-ai' ); ?></p>

		<h3><?php esc_html_e( 'Blacklist IP Addresses', 'jawara-web-shield-ai' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'IPs in the blacklist will be blocked from accessing your website.', 'jawara-web-shield-ai' ); ?>
		</p>

		<div id="jwsai-blacklist-container">
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'IP Address', 'jawara-web-shield-ai' ); ?></th>
						<th style="width: 100px;"><?php esc_html_e( 'Action', 'jawara-web-shield-ai' ); ?></th>
					</tr>
				</thead>
				<tbody id="jwsai-blacklist-list">
					<?php if ( ! empty( $blacklist ) ) : ?>
						<?php foreach ( $blacklist as $ip ) : ?>
							<tr>
								<td><code><?php echo esc_html( $ip ); ?></code></td>
								<td>
									<button class="button button-small jwsai-remove-blacklist" data-ip="<?php echo esc_attr( $ip ); ?>">
										<?php esc_html_e( 'Remove', 'jawara-web-shield-ai' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="2" style="text-align: center; padding: 20px;">
								<?php esc_html_e( 'No blacklisted IPs', 'jawara-web-shield-ai' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<br />
		<div class="jwsai-input-group">
			<input type="text" 
				   id="jwsai-blacklist-input" 
				   placeholder="<?php esc_attr_e( 'Enter IP address (e.g., 192.168.1.1)', 'jawara-web-shield-ai' ); ?>" 
				   class="regular-text" />
			<button type="button" class="button button-primary" id="jwsai-add-blacklist">
				<?php esc_html_e( 'Add to Blacklist', 'jawara-web-shield-ai' ); ?>
			</button>
		</div>

		<hr style="margin: 30px 0;" />

		<h3><?php esc_html_e( 'Whitelist IP Addresses', 'jawara-web-shield-ai' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'IPs in the whitelist will always be allowed, even if in blacklist.', 'jawara-web-shield-ai' ); ?>
		</p>

		<div id="jwsai-whitelist-container">
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'IP Address', 'jawara-web-shield-ai' ); ?></th>
						<th style="width: 100px;"><?php esc_html_e( 'Action', 'jawara-web-shield-ai' ); ?></th>
					</tr>
				</thead>
				<tbody id="jwsai-whitelist-list">
					<?php if ( ! empty( $whitelist ) ) : ?>
						<?php foreach ( $whitelist as $ip ) : ?>
							<tr>
								<td><code><?php echo esc_html( $ip ); ?></code></td>
								<td>
									<button class="button button-small jwsai-remove-whitelist" data-ip="<?php echo esc_attr( $ip ); ?>">
										<?php esc_html_e( 'Remove', 'jawara-web-shield-ai' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="2" style="text-align: center; padding: 20px;">
								<?php esc_html_e( 'No whitelisted IPs', 'jawara-web-shield-ai' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<br />
		<div class="jwsai-input-group">
			<input type="text" 
				   id="jwsai-whitelist-input" 
				   placeholder="<?php esc_attr_e( 'Enter IP address (e.g., 192.168.1.1)', 'jawara-web-shield-ai' ); ?>" 
				   class="regular-text" />
			<button type="button" class="button button-primary" id="jwsai-add-whitelist">
				<?php esc_html_e( 'Add to Whitelist', 'jawara-web-shield-ai' ); ?>
			</button>
		</div>

		<div id="jwsai-message" style="margin-top: 20px;"></div>
	</div>
</div>
