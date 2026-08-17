<?php
namespace AiElementorAgent\Logging;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database Audit Logger for AI Elementor Agent activities.
 */
class AuditLogger {

	private string $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'ai_elementor_audit_logs';
	}

	/**
	 * Log an operation.
	 *
	 * @param string      $client_id Token label or client identifier.
	 * @param int         $user_id User ID.
	 * @param string      $tool_name Tool invoked.
	 * @param string      $action Operation action.
	 * @param string      $status 'success' or 'error'.
	 * @param int|null    $page_id Optional Page ID.
	 * @param string|null $element_id Optional Element ID.
	 * @param int         $duration_ms Duration in milliseconds.
	 * @param array|null  $request_details Sanitized request details.
	 * @param string|null $error_message Error string if failed.
	 */
	public function log(
		string $client_id,
		int $user_id,
		string $tool_name,
		string $action,
		string $status = 'success',
		?int $page_id = null,
		?string $element_id = null,
		int $duration_ms = 0,
		?array $request_details = null,
		?string $error_message = null
	): void {
		global $wpdb;

		// Scrub sensitive payload parameters
		if ( is_array( $request_details ) ) {
			unset( $request_details['api_key'], $request_details['token'], $request_details['secret'] );
		}

		try {
			$wpdb->insert(
				$this->table_name,
				array(
					'timestamp'       => current_time( 'mysql' ),
					'client_id'       => sanitize_text_field( $client_id ),
					'user_id'         => $user_id,
					'tool_name'       => sanitize_text_field( $tool_name ),
					'action'          => sanitize_text_field( $action ),
					'status'          => sanitize_text_field( $status ),
					'page_id'         => $page_id,
					'element_id'      => $element_id ? sanitize_text_field( $element_id ) : null,
					'duration_ms'     => $duration_ms,
					'request_details' => $request_details ? wp_json_encode( $request_details ) : null,
					'error_message'   => $error_message ? sanitize_text_field( $error_message ) : null,
				),
				array( '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' )
			);
		} catch ( \Throwable $e ) {
			// Ignore database audit log insertion errors cleanly
		}
	}

	/**
	 * Retrieve audit log records with pagination and optional search filter.
	 *
	 * @param int    $limit Limit per page.
	 * @param int    $offset Offset.
	 * @param string $search Search query.
	 * @return array Array of records.
	 */
	public function get_logs( int $limit = 50, int $offset = 0, string $search = '' ): array {
		global $wpdb;

		$where = '';
		$params = array();

		if ( ! empty( $search ) ) {
			$where = "WHERE tool_name LIKE %s OR action LIKE %s OR client_id LIKE %s OR error_message LIKE %s";
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$params = array( $like, $like, $like, $like );
		}

		$sql = "SELECT * FROM {$this->table_name} {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: array();
	}

	/**
	 * Total count of logs.
	 */
	public function get_total_count( string $search = '' ): int {
		global $wpdb;
		if ( ! empty( $search ) ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} WHERE tool_name LIKE %s OR action LIKE %s OR client_id LIKE %s", $like, $like, $like ) );
		}
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
	}
}
