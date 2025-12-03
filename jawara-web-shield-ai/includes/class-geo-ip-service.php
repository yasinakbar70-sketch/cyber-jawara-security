<?php
/**
 * Class Jawara_Geo_IP_Service
 * IP Geolocation service untuk country detection dan geo-blocking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Geo_IP_Service {

	const CACHE_DURATION = 86400; // 24 hours
	const API_ENDPOINT = 'https://ipapi.co/{ip}/json/';

	/**
	 * Get location data untuk IP address
	 *
	 * @param string $ip IP address
	 * @return array|WP_Error Location data
	 */
	public static function get_location( $ip ) {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return new WP_Error( 'invalid_ip', 'Invalid IP address' );
		}

		// Check cache first
		$cache_key = 'jwsai_geoip_' . md5( $ip );
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		// Call API
		$url = str_replace( '{ip}', $ip, self::API_ENDPOINT );
		
		$response = wp_remote_get( $url, array(
			'timeout' => 5,
			'headers' => array(
				'User-Agent' => 'Jawara-Web-Shield-AI/1.0',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return new WP_Error( 'api_error', 'Geo IP API returned error: ' . $status_code );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data ) || isset( $data['error'] ) ) {
			return new WP_Error( 'parse_error', 'Failed to parse geo IP data' );
		}

		$location = array(
			'ip' => $ip,
			'country_code' => $data['country_code'] ?? 'UNKNOWN',
			'country_name' => $data['country_name'] ?? 'Unknown',
			'city' => $data['city'] ?? '',
			'region' => $data['region'] ?? '',
			'latitude' => $data['latitude'] ?? 0,
			'longitude' => $data['longitude'] ?? 0,
			'org' => $data['org'] ?? '',
			'timezone' => $data['timezone'] ?? '',
		);

		// Cache result
		set_transient( $cache_key, $location, self::CACHE_DURATION );

		return $location;
	}

	/**
	 * Check if IP adalah dari country yang diblokir
	 *
	 * @param string $ip IP address
	 * @return bool True if blocked
	 */
	public static function is_country_blocked( $ip ) {
		$blocked_countries = get_option( 'jwsai_blocked_countries', array() );
		
		if ( empty( $blocked_countries ) ) {
			return false;
		}

		$location = self::get_location( $ip );
		
		if ( is_wp_error( $location ) ) {
			// If error, don't block (fail open)
			return false;
		}

		return in_array( $location['country_code'], $blocked_countries, true );
	}

	/**
	 * Get country list untuk dropdown
	 *
	 * @return array Country list
	 */
	public static function get_country_list() {
		return array(
			'AF' => 'Afghanistan',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AR' => 'Argentina',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'BD' => 'Bangladesh',
			'BE' => 'Belgium',
			'BR' => 'Brazil',
			'BG' => 'Bulgaria',
			'CA' => 'Canada',
			'CL' => 'Chile',
			'CN' => 'China',
			'CO' => 'Colombia',
			'HR' => 'Croatia',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'EG' => 'Egypt',
			'FI' => 'Finland',
			'FR' => 'France',
			'DE' => 'Germany',
			'GR' => 'Greece',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JP' => 'Japan',
			'KE' => 'Kenya',
			'KR' => 'South Korea',
			'KP' => 'North Korea',
			'MY' => 'Malaysia',
			'MX' => 'Mexico',
			'NL' => 'Netherlands',
			'NZ' => 'New Zealand',
			'NG' => 'Nigeria',
			'NO' => 'Norway',
			'PK' => 'Pakistan',
			'PH' => 'Philippines',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'RO' => 'Romania',
			'RU' => 'Russia',
			'SA' => 'Saudi Arabia',
			'RS' => 'Serbia',
			'SG' => 'Singapore',
			'ZA' => 'South Africa',
			'ES' => 'Spain',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'TW' => 'Taiwan',
			'TH' => 'Thailand',
			'TR' => 'Turkey',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'GB' => 'United Kingdom',
			'US' => 'United States',
			'VN' => 'Vietnam',
		);
	}
}
