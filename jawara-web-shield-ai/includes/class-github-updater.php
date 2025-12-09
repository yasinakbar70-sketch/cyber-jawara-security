<?php
/**
 * Class Jawara_GitHub_Updater
 * Auto-update plugin dari GitHub repository
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_GitHub_Updater {

	/**
	 * GitHub repository owner
	 */
	private $github_username = 'instanwaofficial-glitch';

	/**
	 * GitHub repository name
	 */
	private $github_repo = 'Plugin';

	/**
	 * GitHub branch
	 */
	private $github_branch = 'main';

	/**
	 * Plugin slug
	 */
	private $plugin_slug;

	/**
	 * Plugin basename
	 */
	private $plugin_basename;

	/**
	 * Plugin data
	 */
	private $plugin_data;

	/**
	 * GitHub API response cache
	 */
	private $github_api_result;

	/**
	 * Initialize updater
	 */
	public function __construct() {
		$this->plugin_basename = JWSAI_PLUGIN_BASENAME;
		$this->plugin_slug = dirname( $this->plugin_basename );

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
		
		// Clear update cache when visiting plugins page
		add_action( 'admin_init', array( $this, 'maybe_clear_cache' ) );
	}

	/**
	 * Get plugin data
	 */
	private function get_plugin_data() {
		if ( empty( $this->plugin_data ) ) {
			$this->plugin_data = get_plugin_data( JWSAI_PLUGIN_DIR . 'jawara-web-shield-ai.php' );
		}
		return $this->plugin_data;
	}

	/**
	 * Get GitHub release info from API
	 */
	private function get_github_release_info() {
		if ( ! empty( $this->github_api_result ) ) {
			return $this->github_api_result;
		}

		// Check cache first
		$cached = get_transient( 'jwsai_github_update_check' );
		if ( false !== $cached ) {
			$this->github_api_result = $cached;
			return $cached;
		}

		// Try releases API first
		$release_url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			$this->github_username,
			$this->github_repo
		);

		$response = wp_remote_get( $release_url, array(
			'headers' => array(
				'Accept' => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
			),
			'timeout' => 10,
		) );

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$release = json_decode( wp_remote_retrieve_body( $response ), true );
			
			if ( ! empty( $release['tag_name'] ) ) {
				$this->github_api_result = array(
					'version' => ltrim( $release['tag_name'], 'v' ),
					'download_url' => ! empty( $release['zipball_url'] ) ? $release['zipball_url'] : sprintf(
						'https://github.com/%s/%s/archive/refs/tags/%s.zip',
						$this->github_username,
						$this->github_repo,
						$release['tag_name']
					),
					'description' => $release['body'] ?? '',
					'published_at' => $release['published_at'] ?? '',
					'html_url' => $release['html_url'] ?? '',
				);

				// Cache for 6 hours
				set_transient( 'jwsai_github_update_check', $this->github_api_result, 6 * HOUR_IN_SECONDS );
				return $this->github_api_result;
			}
		}

		// Fallback: Check main branch for version in plugin header
		$raw_url = sprintf(
			'https://raw.githubusercontent.com/%s/%s/%s/jawara-web-shield-ai/jawara-web-shield-ai.php',
			$this->github_username,
			$this->github_repo,
			$this->github_branch
		);

		$response = wp_remote_get( $raw_url, array(
			'timeout' => 10,
		) );

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$content = wp_remote_retrieve_body( $response );
			
			// Extract version from plugin header
			if ( preg_match( '/Version:\s*([0-9.]+)/i', $content, $matches ) ) {
				$this->github_api_result = array(
					'version' => $matches[1],
					'download_url' => sprintf(
						'https://github.com/%s/%s/archive/refs/heads/%s.zip',
						$this->github_username,
						$this->github_repo,
						$this->github_branch
					),
					'description' => '',
					'published_at' => '',
					'html_url' => sprintf(
						'https://github.com/%s/%s',
						$this->github_username,
						$this->github_repo
					),
				);

				// Cache for 6 hours
				set_transient( 'jwsai_github_update_check', $this->github_api_result, 6 * HOUR_IN_SECONDS );
				return $this->github_api_result;
			}
		}

		return false;
	}

	/**
	 * Check for updates
	 */
	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$github_info = $this->get_github_release_info();
		
		if ( false === $github_info ) {
			return $transient;
		}

		$plugin_data = $this->get_plugin_data();
		$current_version = $plugin_data['Version'];
		$github_version = $github_info['version'];

		// Compare versions
		if ( version_compare( $github_version, $current_version, '>' ) ) {
			$transient->response[ $this->plugin_basename ] = (object) array(
				'slug' => $this->plugin_slug,
				'plugin' => $this->plugin_basename,
				'new_version' => $github_version,
				'url' => $github_info['html_url'],
				'package' => $github_info['download_url'],
				'icons' => array(),
				'banners' => array(),
				'banners_rtl' => array(),
				'tested' => get_bloginfo( 'version' ),
				'requires_php' => '7.4',
				'compatibility' => new stdClass(),
			);
		}

		return $transient;
	}

	/**
	 * Plugin info for the "View Details" popup
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( $this->plugin_slug !== $args->slug ) {
			return $result;
		}

		$github_info = $this->get_github_release_info();
		
		if ( false === $github_info ) {
			return $result;
		}

		$plugin_data = $this->get_plugin_data();

		return (object) array(
			'name' => $plugin_data['Name'],
			'slug' => $this->plugin_slug,
			'version' => $github_info['version'],
			'author' => $plugin_data['AuthorName'],
			'author_profile' => $plugin_data['AuthorURI'],
			'requires' => '5.0',
			'tested' => get_bloginfo( 'version' ),
			'requires_php' => '7.4',
			'downloaded' => 0,
			'last_updated' => $github_info['published_at'],
			'sections' => array(
				'description' => $plugin_data['Description'],
				'changelog' => ! empty( $github_info['description'] ) 
					? nl2br( $github_info['description'] ) 
					: '<p>Lihat changelog lengkap di <a href="' . esc_url( $github_info['html_url'] ) . '" target="_blank">GitHub</a></p>',
			),
			'download_link' => $github_info['download_url'],
			'homepage' => $plugin_data['PluginURI'],
		);
	}

	/**
	 * After install - rename folder if needed
	 */
	public function after_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;

		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $result;
		}

		$install_directory = plugin_dir_path( JWSAI_PLUGIN_DIR );
		$proper_destination = $install_directory . $this->plugin_slug;

		// Move if directory name is different (GitHub creates folder with branch/tag name)
		if ( $result['destination'] !== $proper_destination ) {
			$wp_filesystem->move( $result['destination'], $proper_destination );
			$result['destination'] = $proper_destination;
		}

		// Activate plugin
		activate_plugin( $this->plugin_basename );

		return $result;
	}

	/**
	 * Clear cache when visiting plugins page with force-check
	 */
	public function maybe_clear_cache() {
		global $pagenow;
		
		if ( 'plugins.php' === $pagenow && isset( $_GET['force-check'] ) ) {
			delete_transient( 'jwsai_github_update_check' );
		}
	}
}

// Initialize updater
new Jawara_GitHub_Updater();
