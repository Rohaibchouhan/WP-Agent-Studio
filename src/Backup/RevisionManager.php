<?php
namespace AiElementorAgent\Backup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles page state backup snapshots before destructive updates.
 */
class RevisionManager {

	private const META_KEY = '_ai_elementor_backups';

	/**
	 * Create a backup snapshot of current Elementor post data.
	 *
	 * @param int    $page_id Target page ID.
	 * @param string $reason Brief note on why backup was created.
	 * @return string Backup ID.
	 */
	public function create_backup( int $page_id, string $reason = 'Pre-update snapshot' ): string {
		$backup_id = 'bk_' . current_time( 'Ymd_His' ) . '_' . wp_generate_password( 6, false );
		$raw_data = get_post_meta( $page_id, '_elementor_data', true );
		$page_title = get_the_title( $page_id );

		$backups = get_post_meta( $page_id, self::META_KEY, true );
		if ( ! is_array( $backups ) ) {
			$backups = array();
		}

		$settings = get_option( 'ai_elementor_agent_settings', array() );
		$max_backups = (int) ( $settings['max_backups_per_page'] ?? 10 );

		$backups[ $backup_id ] = array(
			'id'         => $backup_id,
			'timestamp'  => current_time( 'mysql' ),
			'reason'     => sanitize_text_field( $reason ),
			'title'      => $page_title,
			'data'       => $raw_data,
		);

		// Maintain snapshot cap
		if ( count( $backups ) > $max_backups ) {
			$backups = array_slice( $backups, -$max_backups, null, true );
		}

		update_post_meta( $page_id, self::META_KEY, $backups );

		// Also trigger standard WP revision save if post revisions enabled
		try {
			if ( function_exists( 'wp_save_post_revision' ) ) {
				@wp_save_post_revision( $page_id );
			}
		} catch ( \Throwable $e ) {
			// Ignore WP revision errors gracefully
		}

		return $backup_id;
	}

	/**
	 * Restore a backup snapshot for a page.
	 *
	 * @param int    $page_id Page ID.
	 * @param string $backup_id Backup snapshot ID.
	 * @return bool Success.
	 */
	public function restore_backup( int $page_id, string $backup_id ): bool {
		$backups = get_post_meta( $page_id, self::META_KEY, true );
		if ( ! is_array( $backups ) || ! isset( $backups[ $backup_id ] ) ) {
			return false;
		}

		// First take a safety snapshot of current state before reverting
		$this->create_backup( $page_id, 'Snapshot before restoring ' . $backup_id );

		$target_backup = $backups[ $backup_id ];
		update_post_meta( $page_id, '_elementor_data', $target_backup['data'] );

		// Clear Elementor CSS cache if Elementor active
		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_stack();
		}

		return true;
	}

	/**
	 * List all backups for a page.
	 *
	 * @param int $page_id Page ID.
	 * @return array List of backups.
	 */
	public function list_backups( int $page_id ): array {
		$backups = get_post_meta( $page_id, self::META_KEY, true );
		if ( ! is_array( $backups ) ) {
			return array();
		}

		$list = array();
		foreach ( $backups as $id => $b ) {
			$list[] = array(
				'id'        => $b['id'],
				'timestamp' => $b['timestamp'],
				'reason'    => $b['reason'],
				'title'     => $b['title'],
			);
		}
		return array_reverse( $list );
	}
}
