<?php
/**
 * Class Jawara_File_Scanner
 * Mengelola file integrity scanning dan baseline hashing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_File_Scanner {

	/**
	 * Scan semua file PHP di wp-content
	 *
	 * @return array
	 */
	public function scan_all_files() {
		$wp_content_dir = WP_CONTENT_DIR;
		$files          = $this->get_php_files( $wp_content_dir );
		$results        = array(
			'scanned'   => 0,
			'files'     => array(),
		);

		foreach ( $files as $file ) {
			$file_hash = sha1_file( $file );
			$baseline  = get_option( 'jwsai_file_baseline_' . md5( $file ) );

			if ( ! $baseline ) {
				// Pertama kali scan, simpan baseline
				update_option( 'jwsai_file_baseline_' . md5( $file ), $file_hash );
				$status = 'new';
			} elseif ( $baseline === $file_hash ) {
				$status = 'unchanged';
			} else {
				$status = 'changed';
			}

			$results['files'][] = array(
				'path'     => $file,
				'hash'     => $file_hash,
				'status'   => $status,
				'baseline' => $baseline,
			);

			$results['scanned']++;
		}

		return $results;
	}

	/**
	 * Check integrity semua file
	 */
	public function check_integrity() {
		$wp_content_dir = WP_CONTENT_DIR;
		$files          = $this->get_php_files( $wp_content_dir );

		foreach ( $files as $file ) {
			$file_hash = sha1_file( $file );
			$baseline  = get_option( 'jwsai_file_baseline_' . md5( $file ) );

			if ( ! $baseline ) {
				// Set baseline jika belum ada
				update_option( 'jwsai_file_baseline_' . md5( $file ), $file_hash );
				continue;
			}

			if ( $baseline !== $file_hash ) {
				// File berubah! Analisis dengan AI
				$this->handle_file_change( $file );
			}
		}
	}

	/**
	 * Handle perubahan file
	 *
	 * @param string $file_path Path ke file
	 */
	private function handle_file_change( $file_path ) {
		// Validasi file exists dan readable
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			Jawara_Security_Logger::log(
				'file_integrity',
				'medium',
				"Cannot read file: " . $file_path,
				$file_path
			);
			return;
		}

		$file_content = @file_get_contents( $file_path );
		if ( false === $file_content ) {
			Jawara_Security_Logger::log(
				'file_integrity',
				'medium',
				"Failed to read file content: " . $file_path,
				$file_path
			);
			return;
		}

		// Log the change
		Jawara_Security_Logger::log(
			'file_integrity',
			'high',
			"File modified: " . $file_path,
			$file_path
		);

		// Analisis dengan AI
		$gemini = new Jawara_Gemini_API();
		$result = $gemini->analyze_file_for_malware( $file_path, $file_content );

		if ( ! is_wp_error( $result ) ) {
			$analysis = Jawara_Gemini_API::parse_analysis( $result['analysis'] );

			// Log hasil analisis
			Jawara_Security_Logger::log(
				'file_integrity',
				'high',
				"AI Analysis for: " . $file_path,
				$file_path,
				null,
				null,
				$analysis
			);

			// Kirim notifikasi Telegram
			if ( $analysis['risk_level'] !== 'low' ) {
				Jawara_Telegram_Notifier::notify_file_change( $file_path, 'modified' );

				if ( 'yes' === $analysis['malicious'] || 'high' === $analysis['risk_level'] ) {
					Jawara_Telegram_Notifier::notify_malware_detected(
						$file_path,
						$analysis['risk_level'],
						$result['analysis']
					);
				}
			}

			// Update baseline
			update_option( 'jwsai_file_baseline_' . md5( $file_path ), sha1_file( $file_path ) );
		}
	}

	/**
	 * Get semua file PHP di direktori
	 *
	 * @param string $dir Direktori
	 * @return array
	 */
	private function get_php_files( $dir ) {
		$files = array();

		if ( ! is_dir( $dir ) ) {
			return $files;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && $file->getExtension() === 'php' ) {
				// Skip beberapa direktori
				$path = $file->getPathname();

				if ( $this->should_skip_file( $path ) ) {
					continue;
				}

				$files[] = $path;
			}
		}

		return $files;
	}

	/**
	 * Check apakah file harus di-skip
	 *
	 * @param string $file_path Path file
	 * @return bool
	 */
	private function should_skip_file( $file_path ) {
		$skip_patterns = array(
			'/node_modules/',
			'/vendor/',
			'/.git/',
			'/tmp/',
		);

		foreach ( $skip_patterns as $pattern ) {
			if ( strpos( $file_path, $pattern ) !== false ) {
				return true;
			}
		}

		return false;
	}
}
