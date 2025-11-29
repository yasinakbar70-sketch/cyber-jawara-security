<?php
// Tes koneksi Telegram Bot dari plugin Jawara Web Shield AI
require_once __DIR__ . '/jawara-web-shield-ai.php';

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', true );
}

// Pastikan class sudah dimuat
if ( class_exists( 'Jawara_Telegram_Notifier' ) ) {
	$result = Jawara_Telegram_Notifier::send_security_alert(
		'Test Alert',
		'This is a test notification from Jawara Web Shield AI.',
		'low'
	);

	if ( is_wp_error( $result ) ) {
		echo 'Error: ' . $result->get_error_message();
	} else {
		echo 'Message sent successfully!';
	}
} else {
	echo 'Class Jawara_Telegram_Notifier not found.';
}
