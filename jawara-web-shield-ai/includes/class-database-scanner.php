<?php
/**
 * Class Jawara_Database_Scanner
 * Scans database for malicious content and integrity issues
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Jawara_Database_Scanner {

	/**
	 * Malicious patterns to look for in database content
	 */
	private static $malicious_patterns = array(
		'base64_decode',
		'eval\(',
		'<script',
		'<iframe',
		'display:\s*none',
		'visibility:\s*hidden',
		'position:\s*absolute;\s*top:\s*-\d+px',
		'document\.write',
		'window\.location',
		'src=["\']http:\/\/.*\.js["\']',
	);

	/**
	 * Run database scan
	 *
	 * @return array Scan results
	 */
	public static function scan() {
		global $wpdb;
		$results = array(
			'malicious_content' => array(),
			'orphaned_data' => array(),
			'integrity_issues' => array(),
			'timestamp' => current_time( 'mysql' ),
		);

		// 1. Scan Posts & Comments for malicious content
		$results['malicious_content'] = self::scan_content();

		// 2. Check for orphaned metadata
		$results['orphaned_data'] = self::check_orphaned_data();

		// 3. Check table integrity
		$results['integrity_issues'] = self::check_table_integrity();

		return $results;
	}

	/**
	 * Scan posts and comments content
	 */
	private static function scan_content() {
		global $wpdb;
		$issues = array();

		// Prepare regex pattern
		$pattern = implode( '|', self::$malicious_patterns );

		// Scan Posts
		$posts = $wpdb->get_results( 
			"SELECT ID, post_title, post_content FROM {$wpdb->posts} 
			WHERE post_status = 'publish' 
			AND (post_content REGEXP '$pattern' OR post_title REGEXP '$pattern')
			LIMIT 100" 
		);

		foreach ( $posts as $post ) {
			$issues[] = array(
				'type' => 'post',
				'id' => $post->ID,
				'title' => $post->post_title,
				'match' => 'Suspicious content detected',
				'location' => 'wp_posts',
			);
		}

		// Scan Comments
		$comments = $wpdb->get_results( 
			"SELECT comment_ID, comment_author, comment_content FROM {$wpdb->comments} 
			WHERE comment_approved = '1' 
			AND (comment_content REGEXP '$pattern' OR comment_author REGEXP '$pattern')
			LIMIT 100" 
		);

		foreach ( $comments as $comment ) {
			$issues[] = array(
				'type' => 'comment',
				'id' => $comment->comment_ID,
				'title' => 'Comment by ' . $comment->comment_author,
				'match' => 'Suspicious content detected',
				'location' => 'wp_comments',
			);
		}

		return $issues;
	}

	/**
	 * Check for orphaned metadata
	 */
	private static function check_orphaned_data() {
		global $wpdb;
		$orphaned = array();

		// Orphaned Post Meta
		$post_meta_count = $wpdb->get_var( 
			"SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
			LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
			WHERE p.ID IS NULL" 
		);

		if ( $post_meta_count > 0 ) {
			$orphaned[] = array(
				'type' => 'post_meta',
				'count' => $post_meta_count,
				'message' => sprintf( '%d orphaned post meta entries found', $post_meta_count ),
			);
		}

		// Orphaned Comment Meta
		$comment_meta_count = $wpdb->get_var( 
			"SELECT COUNT(*) FROM {$wpdb->commentmeta} cm 
			LEFT JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID 
			WHERE c.comment_ID IS NULL" 
		);

		if ( $comment_meta_count > 0 ) {
			$orphaned[] = array(
				'type' => 'comment_meta',
				'count' => $comment_meta_count,
				'message' => sprintf( '%d orphaned comment meta entries found', $comment_meta_count ),
			);
		}

		return $orphaned;
	}

	/**
	 * Check table integrity
	 */
	private static function check_table_integrity() {
		global $wpdb;
		$issues = array();

		$tables = $wpdb->get_results( "SHOW TABLE STATUS", ARRAY_A );

		foreach ( $tables as $table ) {
			if ( ! empty( $table['Comment'] ) && strpos( $table['Comment'], 'crashed' ) !== false ) {
				$issues[] = array(
					'table' => $table['Name'],
					'status' => 'Crashed',
					'message' => 'Table is marked as crashed',
				);
			}
		}

		return $issues;
	}

	/**
	 * Clean orphaned data
	 */
	public static function clean_orphaned_data() {
		global $wpdb;
		
		// Clean Post Meta
		$wpdb->query( 
			"DELETE pm FROM {$wpdb->postmeta} pm 
			LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
			WHERE p.ID IS NULL" 
		);

		// Clean Comment Meta
		$wpdb->query( 
			"DELETE cm FROM {$wpdb->commentmeta} cm 
			LEFT JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID 
			WHERE c.comment_ID IS NULL" 
		);

		// Clean Transients
		$wpdb->query( 
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_%' 
			OR option_name LIKE '_site_transient_%'" 
		);

		return true;
	}
}
