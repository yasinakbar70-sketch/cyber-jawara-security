<?php
/**
 * Tab Security Logs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logs = Jawara_Security_Logger::get_logs( 100 );
$stats = Jawara_Security_Logger::get_statistics();
?>

<div class="postbox">
	<h2><?php esc_html_e( 'Security Statistics', 'jawara-web-shield-ai' ); ?></h2>
	<div class="inside">
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Total Logs', 'jawara-web-shield-ai' ); ?></th>
				<td>
					<strong><?php echo esc_html( number_format( $stats['total_logs'] ) ); ?></strong>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'High Severity (24h)', 'jawara-web-shield-ai' ); ?></th>
				<td>
					<strong style="color: <?php echo $stats['high_severity_24h'] > 0 ? 'red' : 'green'; ?>;">
						<?php echo esc_html( $stats['high_severity_24h'] ); ?>
					</strong>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Event Types', 'jawara-web-shield-ai' ); ?></th>
				<td>
					<?php if ( ! empty( $stats['event_types'] ) ) : ?>
						<ul style="margin: 0;">
							<?php foreach ( $stats['event_types'] as $event ) : ?>
								<li>
									<?php echo esc_html( $event->event_type ); ?>: 
									<strong><?php echo esc_html( $event->count ); ?></strong>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<?php esc_html_e( 'No events recorded', 'jawara-web-shield-ai' ); ?>
					<?php endif; ?>
				</td>
			</tr>
		</table>
	</div>
</div>

<div class="postbox">
	<h2><?php esc_html_e( 'Recent Security Events', 'jawara-web-shield-ai' ); ?></h2>
	<div class="inside">
		<?php if ( ! empty( $logs ) ) : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Timestamp', 'jawara-web-shield-ai' ); ?></th>
						<th><?php esc_html_e( 'Event Type', 'jawara-web-shield-ai' ); ?></th>
						<th><?php esc_html_e( 'Severity', 'jawara-web-shield-ai' ); ?></th>
						<th><?php esc_html_e( 'Message', 'jawara-web-shield-ai' ); ?></th>
						<th><?php esc_html_e( 'IP Address', 'jawara-web-shield-ai' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td><small><?php echo esc_html( $log['timestamp'] ); ?></small></td>
							<td>
								<span class="badge" style="background: #2271b1; color: white; padding: 3px 8px; border-radius: 3px;">
									<?php echo esc_html( $log['event_type'] ); ?>
								</span>
							</td>
							<td>
								<?php
								$severity_color = array(
									'low'      => '#2271b1',
									'medium'   => '#f8b500',
									'high'     => '#cc0000',
									'critical' => '#8b0000',
								);
								$color = isset( $severity_color[ $log['severity'] ] ) ? $severity_color[ $log['severity'] ] : '#999';
								?>
								<span style="background: <?php echo esc_attr( $color ); ?>; color: white; padding: 3px 8px; border-radius: 3px;">
									<?php echo esc_html( strtoupper( $log['severity'] ) ); ?>
								</span>
							</td>
							<td>
								<details>
									<summary style="cursor: pointer; color: #0073aa;">
										<?php echo esc_html( substr( $log['message'], 0, 60 ) ); ?>...
									</summary>
									<pre style="background: #f5f5f5; padding: 10px; border-radius: 3px; overflow: auto; max-height: 200px;">
<?php echo esc_html( $log['message'] ); ?>

<?php if ( ! empty( $log['ai_analysis'] ) ) : ?>
--- AI Analysis ---
<?php echo wp_json_encode( $log['ai_analysis'], JSON_PRETTY_PRINT ); // phpcs:ignore ?>
<?php endif; ?>
									</pre>
								</details>
							</td>
							<td>
								<?php if ( ! empty( $log['ip_address'] ) ) : ?>
									<code><?php echo esc_html( $log['ip_address'] ); ?></code>
								<?php else : ?>
									<span style="color: #999;">-</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p style="text-align: center; padding: 20px;">
				<?php esc_html_e( 'No security events recorded yet.', 'jawara-web-shield-ai' ); ?>
			</p>
		<?php endif; ?>
	</div>
</div>
