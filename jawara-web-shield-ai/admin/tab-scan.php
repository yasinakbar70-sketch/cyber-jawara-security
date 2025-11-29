<?php
/**
 * Tab Scan AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="postbox">
	<h2><?php esc_html_e( 'Manual Security Scan', 'jawara-web-shield-ai' ); ?></h2>
	<div class="inside">
		<p><?php esc_html_e( 'Run a comprehensive security scan of your WordPress installation using AI analysis.', 'jawara-web-shield-ai' ); ?></p>

		<div style="margin: 20px 0;">
			<button type="button" class="button button-large button-primary" id="jwsai-scan-button">
				<span class="dashicons dashicons-shield" style="vertical-align: middle;"></span>
				<?php esc_html_e( 'Start Manual Scan', 'jawara-web-shield-ai' ); ?>
			</button>
		</div>

		<div id="jwsai-scan-progress" style="display: none; margin: 20px 0;">
			<p><?php esc_html_e( 'Scanning...', 'jawara-web-shield-ai' ); ?></p>
			<div style="width: 100%; background: #f0f0f0; height: 20px; border-radius: 5px; overflow: hidden;">
				<div id="jwsai-progress-bar" style="width: 0%; background: #4CAF50; height: 100%; transition: width 0.3s;"></div>
			</div>
		</div>

		<div id="jwsai-scan-results" style="margin-top: 20px;"></div>

		<hr style="margin: 30px 0;" />

		<h3><?php esc_html_e( 'About This Scan', 'jawara-web-shield-ai' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Scans all PHP files in wp-content directory', 'jawara-web-shield-ai' ); ?></li>
			<li><?php esc_html_e( 'Checks file integrity against baseline hashes', 'jawara-web-shield-ai' ); ?></li>
			<li><?php esc_html_e( 'Detects malware signature patterns', 'jawara-web-shield-ai' ); ?></li>
			<li><?php esc_html_e( 'Uses Gemini AI for advanced analysis', 'jawara-web-shield-ai' ); ?></li>
			<li><?php esc_html_e( 'Generates detailed security report', 'jawara-web-shield-ai' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Scan Results Legend', 'jawara-web-shield-ai' ); ?></h3>
		<table class="widefat">
			<tr>
				<th style="width: 80px;">
					<span style="color: green; font-weight: bold;">✓ New</span>
				</th>
				<td><?php esc_html_e( 'File is new or not in baseline yet', 'jawara-web-shield-ai' ); ?></td>
			</tr>
			<tr>
				<th>
					<span style="color: green; font-weight: bold;">✓ Unchanged</span>
				</th>
				<td><?php esc_html_e( 'File matches baseline hash - safe', 'jawara-web-shield-ai' ); ?></td>
			</tr>
			<tr>
				<th>
					<span style="color: red; font-weight: bold;">✗ Changed</span>
				</th>
				<td><?php esc_html_e( 'File has been modified - analyzed by AI', 'jawara-web-shield-ai' ); ?></td>
			</tr>
		</table>
	</div>
</div>
