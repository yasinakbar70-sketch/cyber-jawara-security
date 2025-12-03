<?php
/**
 * Class Jawara_Gemini_API
 * Mengelola komunikasi dengan Google Gemini API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Gemini_API {

	const API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

	/**
	 * Dapatkan API key
	 */
	private function get_api_key() {
		$api_key = get_option( 'jwsai_gemini_api_key' );
		return ! empty( $api_key ) ? $api_key : false;
	}

	/**
	 * Analisis file untuk malware
	 *
	 * @param string $file_path Path ke file
	 * @param string $file_content Konten file (optional, jika kosong akan dibaca dari disk)
	 * @return array|WP_Error
	 */
	public function analyze_file_for_malware( $file_path, $file_content = '' ) {
		if ( empty( $file_content ) ) {
			if ( ! file_exists( $file_path ) ) {
				return new WP_Error( 'file_not_found', 'File not found: ' . $file_path );
			}
			$file_content = file_get_contents( $file_path );
		}

		if ( strlen( $file_content ) > 100000 ) {
			$file_content = substr( $file_content, 0, 100000 ) . "\n... [File truncated for analysis]";
		}

		$prompt = "Analyze the following PHP file for potential malware, security issues, and suspicious patterns:\n\n" .
			"File: " . basename( $file_path ) . "\n" .
			"---\n" .
			$file_content . "\n" .
			"---\n\n" .
			"Please provide:\n" .
			"1. Is this file malicious? (Yes/No/Suspicious)\n" .
			"2. Risk Level (Low/Medium/High)\n" .
			"3. Suspicious patterns found (if any)\n" .
			"4. Recommended action (Delete/Edit/Monitor/Safe)\n" .
			"5. Brief explanation\n\n" .
			"Format your response as:\n" .
			"MALICIOUS: [Yes/No/Suspicious]\n" .
			"RISK_LEVEL: [Low/Medium/High]\n" .
			"PATTERNS: [list of patterns]\n" .
			"ACTION: [Delete/Edit/Monitor/Safe]\n" .
			"EXPLANATION: [brief explanation]";

		return $this->send_request( $prompt );
	}

	/**
	 * Analisis log serangan
	 *
	 * @param array $logs Array dari log entries
	 * @return array|WP_Error
	 */
	public function analyze_attack_patterns( $logs ) {
		if ( empty( $logs ) ) {
			return new WP_Error( 'empty_logs', 'No logs provided for analysis' );
		}

		$logs_text = '';
		foreach ( $logs as $log ) {
			$logs_text .= sprintf(
				"[%s] %s (Severity: %s) - %s\n",
				$log['timestamp'],
				$log['event_type'],
				$log['severity'],
				$log['message']
			);
		}

		$prompt = "Analyze the following security logs for attack patterns and threats:\n\n" .
			"---\n" .
			$logs_text . "\n" .
			"---\n\n" .
			"Please provide:\n" .
			"1. Overall threat level (Low/Medium/High/Critical)\n" .
			"2. Identified attack patterns\n" .
			"3. Potential attack vectors\n" .
			"4. Recommended security measures\n" .
			"5. Priority of actions (immediate/urgent/important)\n\n" .
			"Format your response as:\n" .
			"THREAT_LEVEL: [Level]\n" .
			"PATTERNS: [patterns]\n" .
			"VECTORS: [attack vectors]\n" .
			"RECOMMENDATIONS: [recommendations]\n" .
			"PRIORITY: [priority]";

		return $this->send_request( $prompt );
	}

	/**
	 * Kirim request ke Gemini API
	 *
	 * @param string $prompt Text prompt untuk AI
	 * @return array|WP_Error
	 */
	public function send_request( $prompt ) {
		$api_key = $this->get_api_key();

		if ( ! $api_key ) {
			return new WP_Error( 'no_api_key', 'Gemini API key not configured' );
		}

		$url = self::API_ENDPOINT . '?key=' . urlencode( $api_key );

		$request_body = array(
			'contents' => array(
				array(
					'parts' => array(
						array(
							'text' => $prompt,
						),
					),
				),
			),
			'generationConfig' => array(
				'temperature'     => 0.7,
				'topK'            => 40,
				'topP'            => 0.95,
				'maxOutputTokens' => 1024,
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers'     => array(
					'Content-Type' => 'application/json',
				),
				'body'        => wp_json_encode( $request_body ),
				'timeout'     => 30,
				'sslverify'   => true,
				'data_format' => 'body',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {
			$error_body = wp_remote_retrieve_body( $response );
			return new WP_Error( 'api_error', 'API Error: ' . $status_code . ' - ' . $error_body );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return new WP_Error( 'empty_response', 'Empty response from Gemini API' );
		}

		return array(
			'success' => true,
			'analysis' => $data['candidates'][0]['content']['parts'][0]['text'],
			'timestamp' => current_time( 'mysql' ),
		);
	}

	/**
	 * Parse analisis response
	 *
	 * @param string $analysis Text analisis dari AI
	 * @return array
	 */
	public static function parse_analysis( $analysis ) {
		$result = array(
			'malicious'   => 'unknown',
			'risk_level'  => 'unknown',
			'patterns'    => array(),
			'action'      => 'monitor',
			'explanation' => $analysis,
		);

		// Extract MALICIOUS status
		if ( preg_match( '/MALICIOUS:\s*(\w+)/i', $analysis, $match ) ) {
			$result['malicious'] = strtolower( $match[1] );
		}

		// Extract RISK_LEVEL
		if ( preg_match( '/RISK_LEVEL:\s*(\w+)/i', $analysis, $match ) ) {
			$result['risk_level'] = strtolower( $match[1] );
		}

		// Extract PATTERNS
		if ( preg_match( '/PATTERNS:\s*(.+?)(?:ACTION:|$)/is', $analysis, $match ) ) {
			$patterns_text = trim( $match[1] );
			$result['patterns'] = array_map( 'trim', array_filter( explode( "\n", $patterns_text ) ) );
		}

		// Extract ACTION
		if ( preg_match( '/ACTION:\s*(\w+)/i', $analysis, $match ) ) {
			$result['action'] = strtolower( $match[1] );
		}

		return $result;
	}
}
