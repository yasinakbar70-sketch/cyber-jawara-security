/**
 * Jawara Web Shield AI - Admin JavaScript
 */

(function($) {
	'use strict';

	/**
	 * Scan Manual
	 */
	$(document).on('click', '#jwsai-scan-button', function(e) {
		e.preventDefault();

		var $button = $(this);
		var originalText = $button.text();

		$button.prop('disabled', true).html(jwsaiL10n.scanning);
		$('#jwsai-scan-progress').show();
		$('#jwsai-scan-results').html('');

		$.ajax({
			url: jwsaiL10n.ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'jwsai_scan_manual',
				nonce: jwsaiL10n.nonce
			},
			success: function(response) {
				if (response.success) {
					displayScanResults(response.data);
				} else {
					showError(response.data || 'Scan failed');
				}
			},
			error: function() {
				showError('AJAX request failed');
			},
			complete: function() {
				$button.prop('disabled', false).html(originalText);
				$('#jwsai-scan-progress').hide();
			}
		});
	});

	/**
	 * Display scan results
	 */
	function displayScanResults(data) {
		var html = '<div style="background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 15px;">';

		html += '<h3>Scan Results</h3>';
		html += '<p>Scanned: <strong>' + data.scanned + '</strong> files</p>';

		if (data.files && data.files.length > 0) {
			html += '<table class="widefat striped">';
			html += '<thead><tr><th>File</th><th>Status</th><th>Hash</th></tr></thead>';
			html += '<tbody>';

			$.each(data.files, function(i, file) {
				var statusClass = 'status-' + file.status;
				var statusIcon = '';

				if (file.status === 'unchanged') {
					statusIcon = '✓ ';
				} else if (file.status === 'changed') {
					statusIcon = '✗ ';
				}

				html += '<tr>';
				html += '<td><code style="word-break: break-all;">' + escapeHtml(file.path) + '</code></td>';
				html += '<td><span style="color: ' + getStatusColor(file.status) + '; font-weight: bold;">' + statusIcon + file.status.toUpperCase() + '</span></td>';
				html += '<td><code style="font-size: 11px;">' + file.hash.substring(0, 16) + '...</code></td>';
				html += '</tr>';
			});

			html += '</tbody></table>';
		}

		html += '</div>';

		$('#jwsai-scan-results').html(html);
	}

	/**
	 * Get status color
	 */
	function getStatusColor(status) {
		switch(status) {
			case 'unchanged':
				return '#2ecc71';
			case 'changed':
				return '#e74c3c';
			case 'new':
				return '#3498db';
			default:
				return '#95a5a6';
		}
	}

	/**
	 * Show error message
	 */
	function showError(message) {
		var html = '<div style="background: #fee; border: 1px solid #f88; border-radius: 4px; padding: 12px; color: #c33;">';
		html += '<strong>Error:</strong> ' + escapeHtml(message);
		html += '</div>';
		$('#jwsai-scan-results').html(html);
	}

	/**
	 * Escape HTML
	 */
	function escapeHtml(text) {
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}

	/**
	 * Blacklist IP functions
	 */
	$(document).on('click', '#jwsai-add-blacklist', function(e) {
		e.preventDefault();
		var ip = $('#jwsai-blacklist-input').val().trim();

		if (!ip) {
			showMessage('Please enter an IP address', 'error');
			return;
		}

		if (!isValidIP(ip)) {
			showMessage('Invalid IP address format', 'error');
			return;
		}

		$.ajax({
			url: jwsaiL10n.ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'jwsai_add_blacklist_ip',
				ip: ip,
				nonce: jwsaiL10n.nonce
			},
			success: function(response) {
				if (response.success) {
					$('#jwsai-blacklist-input').val('');
					location.reload();
				} else {
					showMessage(response.data, 'error');
				}
			},
			error: function() {
				showMessage('Request failed', 'error');
			}
		});
	});

	$(document).on('click', '.jwsai-remove-blacklist', function(e) {
		e.preventDefault();
		var ip = $(this).data('ip');

		if (!confirm('Remove ' + ip + ' from blacklist?')) {
			return;
		}

		$.ajax({
			url: jwsaiL10n.ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'jwsai_remove_blacklist_ip',
				ip: ip,
				nonce: jwsaiL10n.nonce
			},
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					showMessage(response.data, 'error');
				}
			}
		});
	});

	/**
	 * Whitelist IP functions
	 */
	$(document).on('click', '#jwsai-add-whitelist', function(e) {
		e.preventDefault();
		var ip = $('#jwsai-whitelist-input').val().trim();

		if (!ip) {
			showMessage('Please enter an IP address', 'error');
			return;
		}

		if (!isValidIP(ip)) {
			showMessage('Invalid IP address format', 'error');
			return;
		}

		$.ajax({
			url: jwsaiL10n.ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'jwsai_add_whitelist_ip',
				ip: ip,
				nonce: jwsaiL10n.nonce
			},
			success: function(response) {
				if (response.success) {
					$('#jwsai-whitelist-input').val('');
					location.reload();
				} else {
					showMessage(response.data, 'error');
				}
			},
			error: function() {
				showMessage('Request failed', 'error');
			}
		});
	});

	$(document).on('click', '.jwsai-remove-whitelist', function(e) {
		e.preventDefault();
		var ip = $(this).data('ip');

		if (!confirm('Remove ' + ip + ' from whitelist?')) {
			return;
		}

		$.ajax({
			url: jwsaiL10n.ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'jwsai_remove_whitelist_ip',
				ip: ip,
				nonce: jwsaiL10n.nonce
			},
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					showMessage(response.data, 'error');
				}
			}
		});
	});

	/**
	 * Validate IP address
	 */
	function isValidIP(ip) {
		var ipv4Pattern = /^(\d{1,3}\.){3}\d{1,3}$/;
		if (!ipv4Pattern.test(ip)) {
			return false;
		}

		var parts = ip.split('.');
		for (var i = 0; i < parts.length; i++) {
			var part = parseInt(parts[i], 10);
			if (part > 255) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Show message
	 */
	function showMessage(message, type) {
		var bgColor = type === 'error' ? '#fee' : '#efe';
		var borderColor = type === 'error' ? '#f88' : '#8f8';
		var textColor = type === 'error' ? '#c33' : '#3c3';

		var html = '<div style="background: ' + bgColor + '; border: 1px solid ' + borderColor + '; border-radius: 4px; padding: 12px; color: ' + textColor + '; margin: 10px 0;">';
		html += escapeHtml(message);
		html += '</div>';

		$('#jwsai-message').html(html);
	}

})(jQuery);

// Test Telegram Connection
(function($) {
	$(document).on('click', '#jwsai-test-telegram', function(e) {
		e.preventDefault();
		var $btn = $(this);
		var $result = $('#jwsai-test-telegram-result');
		$btn.prop('disabled', true);
		$result.text('Testing...');
		$.ajax({
			url: jwsaiL10n.ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'jwsai_test_telegram',
				nonce: jwsaiL10n.nonce
			},
			success: function(response) {
				if (response.success) {
					$result.html('<span style="color:green;">' + response.data + '</span>');
				} else {
					$result.html('<span style="color:red;">' + response.data + '</span>');
				}
			},
			error: function() {
				$result.html('<span style="color:red;">AJAX request failed</span>');
			},
			complete: function() {
				$btn.prop('disabled', false);
			}
		});
	});
})(jQuery);
