/**
 * Jawara Web Shield AI - Admin JavaScript
 * Version: 2.0.0
 */

(function ($) {
    'use strict';

    // Document ready
    $(document).ready(function () {
        // Manual scan functionality
        initManualScan();

        // Firewall IP management
        initFirewallIPManagement();

        // Telegram test
        initTelegramTest();
    });

    /**
     * Initialize manual scan functionality
     */
    function initManualScan() {
        $('#jwsai-start-scan').on('click', function (e) {
            e.preventDefault();

            var $button = $(this);
            var $progress = $('.jwsai-scan-progress');
            var $progressFill = $('.jwsai-progress-fill');
            var $status = $('.jwsai-scan-status');

            // Disable button
            $button.prop('disabled', true);
            $button.text(jwsaiL10n.scanning);

            // Show progress bar
            $progress.addClass('active');
            $progressFill.css('width', '0%').text('0%');
            $status.text('Initializing scan...');

            // Simulate progress (in real scenario, you'd get this from server)
            var progress = 0;
            var progressInterval = setInterval(function () {
                progress += Math.random() * 15;
                if (progress > 95) progress = 95;

                $progressFill.css('width', progress + '%').text(Math.round(progress) + '%');
            }, 500);

            // Send AJAX request
            $.ajax({
                url: jwsaiL10n.ajaxurl,
                type: 'POST',
                data: {
                    action: 'jwsai_scan_manual',
                    nonce: jwsaiL10n.nonce
                },
                success: function (response) {
                    clearInterval(progressInterval);
                    $progressFill.css('width', '100%').text('100%');

                    if (response.success) {
                        $status.html('<span class="text-success">✓ ' + jwsaiL10n.scanComplete + '</span>');

                        // Show results
                        if (response.data.scanned) {
                            setTimeout(function () {
                                alert('Scan complete!\\nFiles scanned: ' + response.data.scanned);
                                location.reload();
                            }, 1000);
                        }
                    } else {
                        $status.html('<span class="text-danger">✗ Error: ' + response.data + '</span>');
                    }
                },
                error: function (xhr, status, error) {
                    clearInterval(progressInterval);
                    $status.html('<span class="text-danger">✗ AJAX Error: ' + error + '</span>');
                },
                complete: function () {
                    // Re-enable button
                    setTimeout(function () {
                        $button.prop('disabled', false);
                        $button.text('Start Manual Scan');
                    }, 2000);
                }
            });
        });
    }

    /**
     * Initialize Firewall IP Management
     */
    function initFirewallIPManagement() {
        // Add to blacklist
        $('#jwsai-add-blacklist').on('click', function (e) {
            e.preventDefault();
            var ip = $('#jwsai-blacklist-input').val().trim();

            if (!ip) {
                alert('Please enter an IP address');
                return;
            }

            if (!isValidIP(ip)) {
                alert('Invalid IP address format');
                return;
            }

            manageIP('add', 'blacklist', ip);
        });

        // Add to whitelist
        $('#jwsai-add-whitelist').on('click', function (e) {
            e.preventDefault();
            var ip = $('#jwsai-whitelist-input').val().trim();

            if (!ip) {
                alert('Please enter an IP address');
                return;
            }

            if (!isValidIP(ip)) {
                alert('Invalid IP address format');
                return;
            }

            manageIP('add', 'whitelist', ip);
        });

        // Remove from blacklist
        $(document).on('click', '.jwsai-remove-blacklist', function (e) {
            e.preventDefault();
            var ip = $(this).data('ip');
            if (confirm('Remove ' + ip + ' from blacklist?')) {
                manageIP('remove', 'blacklist', ip);
            }
        });

        // Remove from whitelist
        $(document).on('click', '.jwsai-remove-whitelist', function (e) {
            e.preventDefault();
            var ip = $(this).data('ip');
            if (confirm('Remove ' + ip + ' from whitelist?')) {
                manageIP('remove', 'whitelist', ip);
            }
        });
    }

    /**
     * Manage IP (add/remove from blacklist/whitelist)
     */
    function manageIP(action, listType, ip) {
        var ajaxAction = 'jwsai_' + action + '_' + listType + '_ip';

        $.ajax({
            url: jwsaiL10n.ajaxurl,
            type: 'POST',
            data: {
                action: ajaxAction,
                nonce: jwsaiL10n.nonce,
                ip: ip
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                alert('AJAX Error: ' + error);
            }
        });
    }

    /**
     * Initialize Telegram Test
     */
    function initTelegramTest() {
        $('#jwsai-test-telegram').on('click', function (e) {
            e.preventDefault();

            var $button = $(this);
            var $result = $('#jwsai-test-telegram-result');

            // Disable button and show loading
            $button.prop('disabled', true);
            $result.html('<span class="jwsai-spinner"></span>');

            var token = $('#jwsai_telegram_token').val().trim();
            var chatId = $('#jwsai_telegram_chat_id').val().trim();

            if (!chatId) {
                alert('Please enter your Telegram Chat ID.');
                $button.prop('disabled', false);
                $result.html('');
                return;
            }

            $.ajax({
                url: jwsaiL10n.ajaxurl,
                type: 'POST',
                data: {
                    action: 'jwsai_test_telegram',
                    nonce: jwsaiL10n.nonce,
                    token: token,
                    chat_id: chatId
                },
                success: function (response) {
                    if (response.success) {
                        $result.html('<span class="text-success">✓ ' + response.data + '</span>');
                    } else {
                        $result.html('<span class="text-danger">✗ ' + response.data + '</span>');
                    }
                },
                error: function (xhr, status, error) {
                    $result.html('<span class="text-danger">✗ Error: ' + error + '</span>');
                },
                complete: function () {
                    $button.prop('disabled', false);

                    // Clear result after 5 seconds
                    setTimeout(function () {
                        $result.fadeOut(function () {
                            $(this).html('').show();
                        });
                    }, 5000);
                }
            });
        });
    }

    /**
     * Validate IP address format
     */
    function isValidIP(ip) {
        var ipPattern = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        return ipPattern.test(ip);
    }

    /**
     * Show notification
     */
    function showNotification(message, type) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible jwsai-notice"><p>' + message + '</p></div>');
        $('.jwsai-admin-wrap').prepend($notice);

        // Auto dismiss after 5 seconds
        setTimeout(function () {
            $notice.fadeOut(function () {
                $(this).remove();
            });
        }, 5000);
    }

})(jQuery);
