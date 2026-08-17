<?php
namespace AiElementorAgent\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adapter isolating Elementor plugin version checks and runtime capabilities.
 */
class ElementorAdapter {

	public function is_installed(): bool {
		return file_exists( WP_PLUGIN_DIR . '/elementor/elementor.php' );
	}

	public function is_active(): bool {
		return defined( 'ELEMENTOR_VERSION' ) || class_exists( '\Elementor\Plugin' );
	}

	public function get_version(): ?string {
		return defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : null;
	}

	public function is_pro_active(): bool {
		return defined( 'ELEMENTOR_PRO_VERSION' ) || class_exists( '\ElementorPro\Plugin' );
	}

	public function get_pro_version(): ?string {
		return defined( 'ELEMENTOR_PRO_VERSION' ) ? ELEMENTOR_PRO_VERSION : null;
	}

	public function is_elementor_page( int $page_id ): bool {
		if ( ! $this->is_active() ) {
			return false;
		}
		$edit_mode = get_post_meta( $page_id, '_elementor_edit_mode', true );
		return 'builder' === $edit_mode;
	}

	public function enable_elementor_for_page( int $page_id ): void {
		// Always force-set _elementor_edit_mode to 'builder' — required for Elementor to render
		update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );

		// Always initialize page settings — some hosts need this meta key to exist
		$existing_settings = get_post_meta( $page_id, '_elementor_page_settings', true );
		if ( empty( $existing_settings ) ) {
			update_post_meta( $page_id, '_elementor_page_settings', array() );
		}

		// Initialize _elementor_data to empty array if not set at all
		$existing_data = get_post_meta( $page_id, '_elementor_data', true );
		if ( '' === $existing_data || false === $existing_data ) {
			update_post_meta( $page_id, '_elementor_data', wp_slash( '[]' ) );
		}

		// Clear the CSS cache so Elementor regenerates styles
		delete_post_meta( $page_id, '_elementor_css' );

		// Clear WordPress object cache for this post
		clean_post_cache( $page_id );
	}
}
