<?php
/**
 * Tab Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get stats
$stats = Jawara_Security_Dashboard::get_stats();
$chart_data = Jawara_Security_Dashboard::get_chart_data();
$health = Jawara_Security_Dashboard::get_system_health();
?>

<!-- Dashboard Header -->
<div class="jwsai-dashboard-header">
	<div class="jwsai-health-score">
		<div class="score-circle <?php echo $health['score'] >= 80 ? 'good' : ( $health['score'] >= 50 ? 'warning' : 'critical' ); ?>">
			<span class="score"><?php echo esc_html( $health['score'] ); ?>%</span>
			<span class="label"><?php esc_html_e( 'Security Score', 'jawara-web-shield-ai' ); ?></span>
		</div>
	</div>
	<div class="jwsai-health-details">
		<h3><?php esc_html_e( 'System Status', 'jawara-web-shield-ai' ); ?></h3>
		<?php if ( empty( $health['issues'] ) ) : ?>
			<p class="text-success"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'All systems operational. No issues detected.', 'jawara-web-shield-ai' ); ?></p>
		<?php else : ?>
			<ul class="jwsai-issues-list">
				<?php foreach ( $health['issues'] as $issue ) : ?>
					<li><span class="dashicons dashicons-warning"></span> <?php echo esc_html( $issue ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</div>

<!-- Statistics Cards -->
<div class="jwsai-stats-grid">
	<div class="jwsai-stat-card primary">
		<div class="icon"><span class="dashicons dashicons-shield"></span></div>
		<div class="info">
			<h3><?php echo number_format( $stats['total_blocked'] ); ?></h3>
			<p><?php esc_html_e( 'Total Threats Blocked', 'jawara-web-shield-ai' ); ?></p>
			<small><?php printf( esc_html__( '%s today', 'jawara-web-shield-ai' ), number_format( $stats['today_blocked'] ) ); ?></small>
		</div>
	</div>
	<div class="jwsai-stat-card danger">
		<div class="icon"><span class="dashicons dashicons-buddicons-replies"></span></div>
		<div class="info">
			<h3><?php echo number_format( $stats['malware_count'] ); ?></h3>
			<p><?php esc_html_e( 'Malware Detected', 'jawara-web-shield-ai' ); ?></p>
		</div>
	</div>
	<div class="jwsai-stat-card warning">
		<div class="icon"><span class="dashicons dashicons-lock"></span></div>
		<div class="info">
			<h3><?php echo number_format( $stats['login_stats']['failed'] ); ?></h3>
			<p><?php esc_html_e( 'Failed Logins (7 Days)', 'jawara-web-shield-ai' ); ?></p>
		</div>
	</div>
	<div class="jwsai-stat-card success">
		<div class="icon"><span class="dashicons dashicons-admin-users"></span></div>
		<div class="info">
			<h3><?php echo number_format( $stats['login_stats']['success'] ); ?></h3>
			<p><?php esc_html_e( 'Successful Logins', 'jawara-web-shield-ai' ); ?></p>
		</div>
	</div>
</div>

<div class="jwsai-dashboard-columns">
	<!-- Left Column: Charts & Logs -->
	<div class="jwsai-col-main">
		<!-- Traffic Chart -->
		<div class="postbox">
			<h2 class="hndle"><?php esc_html_e( 'Threat Analytics (Last 7 Days)', 'jawara-web-shield-ai' ); ?></h2>
			<div class="inside">
				<canvas id="jwsaiThreatChart" height="100"></canvas>
			</div>
		</div>

		<!-- Live Traffic Monitor -->
		<div class="postbox">
			<h2 class="hndle">
				<?php esc_html_e( 'Live Traffic Monitor', 'jawara-web-shield-ai' ); ?>
				<span class="jwsai-live-indicator"><span class="pulse"></span> Live</span>
			</h2>
			<div class="inside">
				<div class="jwsai-traffic-table-wrapper">
					<table class="wp-list-table widefat fixed striped" id="jwsai-traffic-table">
						<thead>
							<tr>
								<th width="15%">Time</th>
								<th width="15%">IP Address</th>
								<th width="10%">Country</th>
								<th width="10%">Method</th>
								<th width="40%">URL</th>
								<th width="10%">Status</th>
							</tr>
						</thead>
						<tbody>
							<!-- Populated via AJAX -->
							<tr class="placeholder">
								<td colspan="6" align="center"><?php esc_html_e( 'Waiting for traffic...', 'jawara-web-shield-ai' ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<!-- Right Column: Top Countries & Recent Threats -->
	<div class="jwsai-col-side">
		<!-- Top Attacking Countries -->
		<div class="postbox">
			<h2 class="hndle"><?php esc_html_e( 'Top Attacking Countries', 'jawara-web-shield-ai' ); ?></h2>
			<div class="inside">
				<?php if ( ! empty( $stats['top_countries'] ) ) : ?>
					<ul class="jwsai-country-list">
						<?php foreach ( $stats['top_countries'] as $country ) : ?>
							<li>
								<span class="country-name"><?php echo esc_html( $country['country'] ); ?></span>
								<span class="count-badge"><?php echo number_format( $country['count'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p><?php esc_html_e( 'No data available yet.', 'jawara-web-shield-ai' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Recent Threats -->
		<div class="postbox">
			<h2 class="hndle"><?php esc_html_e( 'Recent Threats', 'jawara-web-shield-ai' ); ?></h2>
			<div class="inside">
				<?php if ( ! empty( $stats['recent_threats'] ) ) : ?>
					<ul class="jwsai-threat-list">
						<?php foreach ( $stats['recent_threats'] as $threat ) : ?>
							<li class="threat-item severity-<?php echo esc_attr( $threat['severity'] ); ?>">
								<div class="threat-header">
									<strong><?php echo esc_html( ucfirst( str_replace( '_', ' ', $threat['event_type'] ) ) ); ?></strong>
									<span class="time-ago"><?php echo human_time_diff( strtotime( $threat['timestamp'] ), current_time( 'timestamp' ) ); ?> ago</span>
								</div>
								<div class="threat-details">
									<?php echo esc_html( wp_trim_words( $threat['message'], 10 ) ); ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p><?php esc_html_e( 'No recent threats detected.', 'jawara-web-shield-ai' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<!-- Chart.js (Loaded from CDN for now, ideally local) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
	// Initialize Chart
	var ctx = document.getElementById('jwsaiThreatChart').getContext('2d');
	var chart = new Chart(ctx, {
		type: 'line',
		data: {
			labels: <?php echo json_encode( $chart_data['labels'] ); ?>,
			datasets: [{
				label: 'Blocked Attacks',
				data: <?php echo json_encode( $chart_data['blocked'] ); ?>,
				borderColor: '#dc3545',
				backgroundColor: 'rgba(220, 53, 69, 0.1)',
				borderWidth: 2,
				fill: true,
				tension: 0.4
			}, {
				label: 'Failed Logins',
				data: <?php echo json_encode( $chart_data['logins'] ); ?>,
				borderColor: '#ffc107',
				backgroundColor: 'rgba(255, 193, 7, 0.1)',
				borderWidth: 2,
				fill: true,
				tension: 0.4
			}]
		},
		options: {
			responsive: true,
			plugins: {
				legend: {
					position: 'bottom',
				}
			},
			scales: {
				y: {
					beginAtZero: true,
					ticks: {
						precision: 0
					}
				}
			}
		}
	});

	// Live Traffic Monitor
	var lastLogId = 0;
	var isUpdating = false;

	function updateTraffic() {
		if (isUpdating) return;
		isUpdating = true;

		$.ajax({
			url: jwsaiL10n.ajaxurl,
			type: 'POST',
			data: {
				action: 'jwsai_get_live_traffic',
				nonce: jwsaiL10n.nonce,
				last_id: lastLogId
			},
			success: function(response) {
				if (response.success && response.data.length > 0) {
					// Remove placeholder
					$('#jwsai-traffic-table tbody .placeholder').remove();

					// Add new rows
					var rows = '';
					// Reverse to show newest first, but we prepend so iterate normally (oldest to newest in response)
					// Actually response is DESC (newest first).
					// If we prepend, we want newest at top.
					
					$.each(response.data, function(i, log) {
						if (log.id > lastLogId) lastLogId = log.id;
						
						var statusClass = log.status === 'blocked' ? 'status-blocked' : 'status-allowed';
						var row = '<tr class="new-row ' + statusClass + '">' +
							'<td>' + log.timestamp.split(' ')[1] + '</td>' +
							'<td>' + log.ip_address + '</td>' +
							'<td>' + log.country_code + '</td>' +
							'<td>' + log.method + '</td>' +
							'<td><div class="url-cell" title="' + log.url + '">' + log.url + '</div></td>' +
							'<td><span class="badge ' + log.status + '">' + log.status + '</span></td>' +
							'</tr>';
						
						$('#jwsai-traffic-table tbody').prepend(row);
					});

					// Limit rows to 20
					$('#jwsai-traffic-table tbody tr').slice(20).remove();
				}
				isUpdating = false;
			},
			error: function() {
				isUpdating = false;
			}
		});
	}

	// Update every 3 seconds
	setInterval(updateTraffic, 3000);
	updateTraffic(); // Initial call
});
</script>


