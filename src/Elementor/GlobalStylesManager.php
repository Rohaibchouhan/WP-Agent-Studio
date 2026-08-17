<?php
namespace AiElementorAgent\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages Elementor Global Kit Design Tokens (Colors & Typography).
 */
class GlobalStylesManager {

	/**
	 * Retrieve the active Elementor Kit post ID.
	 */
	public function get_active_kit_id(): int {
		$active_kit = get_option( 'elementor_active_kit' );
		if ( $active_kit ) {
			return (int) $active_kit;
		}

		// Search for published kit posts if option not set
		$kits = get_posts( array(
			'post_type'   => 'elementor_library',
			'post_status' => 'publish',
			'meta_key'    => '_elementor_template_type',
			'meta_value'  => 'kit',
			'numberposts' => 1,
		) );

		if ( ! empty( $kits ) ) {
			return (int) $kits[0]->ID;
		}

		return 0;
	}

	/**
	 * Get Kit Settings array.
	 */
	public function get_kit_settings(): array {
		$kit_id = $this->get_active_kit_id();
		if ( ! $kit_id ) {
			return array();
		}

		$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Update Kit Settings array.
	 */
	public function save_kit_settings( array $settings ): bool {
		$kit_id = $this->get_active_kit_id();
		if ( ! $kit_id ) {
			return false;
		}

		update_post_meta( $kit_id, '_elementor_page_settings', $settings );

		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_stack();
		}

		return true;
	}

	/**
	 * Get list of global colors.
	 */
	public function get_global_colors(): array {
		$settings = $this->get_kit_settings();
		$custom = $settings['system_colors'] ?? array(
			array( '_id' => 'primary', 'title' => 'Primary', 'color' => '#6C63FF' ),
			array( '_id' => 'secondary', 'title' => 'Secondary', 'color' => '#54595F' ),
			array( '_id' => 'text', 'title' => 'Text', 'color' => '#7A7A7A' ),
			array( '_id' => 'accent', 'title' => 'Accent', 'color' => '#61CE70' ),
		);

		return $custom;
	}

	/**
	 * Set or update a global color token.
	 *
	 * @param string $id Color token ID (e.g. 'primary' or custom ID).
	 * @param string $hex_color Hex color string (e.g. '#6C63FF').
	 * @param string $title Human readable label.
	 * @return bool Success.
	 */
	public function set_global_color( string $id, string $hex_color, string $title = '' ): bool {
		$settings = $this->get_kit_settings();
		$system_colors = $settings['system_colors'] ?? array(
			array( '_id' => 'primary', 'title' => 'Primary', 'color' => '#6C63FF' ),
			array( '_id' => 'secondary', 'title' => 'Secondary', 'color' => '#54595F' ),
			array( '_id' => 'text', 'title' => 'Text', 'color' => '#7A7A7A' ),
			array( '_id' => 'accent', 'title' => 'Accent', 'color' => '#61CE70' ),
		);

		$found = false;
		foreach ( $system_colors as &$c ) {
			if ( $c['_id'] === $id ) {
				$c['color'] = $hex_color;
				if ( ! empty( $title ) ) {
					$c['title'] = $title;
				}
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			$system_colors[] = array(
				'_id'   => $id,
				'title' => ! empty( $title ) ? $title : ucfirst( $id ),
				'color' => $hex_color,
			);
		}

		$settings['system_colors'] = $system_colors;
		return $this->save_kit_settings( $settings );
	}

	/**
	 * Get list of global typography tokens.
	 */
	public function get_global_fonts(): array {
		$settings = $this->get_kit_settings();
		return $settings['system_typography'] ?? array(
			array( '_id' => 'primary', 'title' => 'Primary', 'typography_font_family' => 'Inter', 'typography_font_weight' => '600' ),
			array( '_id' => 'secondary', 'title' => 'Secondary', 'typography_font_family' => 'Inter', 'typography_font_weight' => '400' ),
			array( '_id' => 'text', 'title' => 'Text', 'typography_font_family' => 'Inter', 'typography_font_weight' => '400' ),
			array( '_id' => 'accent', 'title' => 'Accent', 'typography_font_family' => 'Inter', 'typography_font_weight' => '500' ),
		);
	}

	/**
	 * Set or update a global font token.
	 */
	public function set_global_font( string $id, array $font_settings, string $title = '' ): bool {
		$settings = $this->get_kit_settings();
		$system_fonts = $settings['system_typography'] ?? array();

		$found = false;
		foreach ( $system_fonts as &$f ) {
			if ( $f['_id'] === $id ) {
				$f = array_merge( $f, $font_settings );
				if ( ! empty( $title ) ) {
					$f['title'] = $title;
				}
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			$new_font = array_merge( array( '_id' => $id, 'title' => $title ?: ucfirst( $id ) ), $font_settings );
			$system_fonts[] = $new_font;
		}

		$settings['system_typography'] = $system_fonts;
		return $this->save_kit_settings( $settings );
	}
}
