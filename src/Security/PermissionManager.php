<?php
namespace AiElementorAgent\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles authorization checks for user capabilities & plugin permission flags.
 */
class PermissionManager {

	private const OPTION_KEY = 'ai_elementor_agent_permissions';

	/**
	 * Check if an operation is permitted for a given user.
	 *
	 * @param string $permission_name Permission key (e.g., 'modify_pages', 'delete_pages').
	 * @param int    $user_id User ID to evaluate.
	 * @return bool True if authorized.
	 */
	public function can( string $permission_name, int $user_id = 0 ): bool {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		// 1. User capability check
		if ( ! user_can( $user_id, 'edit_pages' ) ) {
			return false;
		}

		// 2. Administrator capability check
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		// 3. Plugin level toggles for non-administrator users
		$permissions = get_option( self::OPTION_KEY, array() );

		if ( isset( $permissions[ $permission_name ] ) ) {
			return (bool) $permissions[ $permission_name ];
		}

		return false;
	}

	/**
	 * Get all permission toggles.
	 *
	 * @return array Permission key-value pairs.
	 */
	public function get_all_permissions(): array {
		return get_option( self::OPTION_KEY, array(
			'read_site' => true,
			'read_pages' => true,
			'read_elementor' => true,
			'create_pages' => true,
			'modify_pages' => true,
			'delete_pages' => false,
			'upload_media' => true,
			'modify_global_styles' => true,
			'publish_pages' => false,
		) );
	}

	/**
	 * Update permission toggles.
	 *
	 * @param array $new_permissions Key-value pairs of permissions.
	 * @return bool Success.
	 */
	public function update_permissions( array $new_permissions ): bool {
		$current = $this->get_all_permissions();
		foreach ( $new_permissions as $key => $val ) {
			if ( array_key_exists( $key, $current ) ) {
				$current[ $key ] = (bool) $val;
			}
		}
		return update_option( self::OPTION_KEY, $current );
	}
}
