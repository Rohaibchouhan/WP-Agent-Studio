<?php
namespace AiElementorAgent\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses raw Elementor _elementor_data into simplified trees for AI analysis.
 */
class ElementorReader {

	/**
	 * Get simplified page structure.
	 *
	 * @param int  $page_id Target Page ID.
	 * @param bool $raw Whether to return raw full Elementor JSON data.
	 * @return array Page tree representation.
	 */
	public function get_page_structure( int $page_id, bool $raw = false ): array {
		$raw_data = get_post_meta( $page_id, '_elementor_data', true );
		if ( empty( $raw_data ) ) {
			return array(
				'page_id'  => $page_id,
				'title'    => get_the_title( $page_id ),
				'status'   => get_post_status( $page_id ),
				'elements' => array(),
			);
		}

		$data = is_string( $raw_data ) ? json_decode( $raw_data, true ) : $raw_data;
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		if ( $raw ) {
			return array(
				'page_id'  => $page_id,
				'title'    => get_the_title( $page_id ),
				'status'   => get_post_status( $page_id ),
				'raw_data' => $data,
			);
		}

		return array(
			'page_id'  => $page_id,
			'title'    => get_the_title( $page_id ),
			'status'   => get_post_status( $page_id ),
			'url'      => get_permalink( $page_id ),
			'elements' => $this->parse_elements( $data ),
		);
	}

	/**
	 * Recursively parse raw element nodes into simplified AST node representations.
	 *
	 * @param array $elements Raw element nodes.
	 * @return array Simplified tree nodes.
	 */
	private function parse_elements( array $elements ): array {
		$parsed = array();

		foreach ( $elements as $el ) {
			if ( ! is_array( $el ) || ! isset( $el['id'] ) ) {
				continue;
			}

			$el_type = $el['elType'] ?? 'widget';
			$widget_type = $el['widgetType'] ?? $el_type;

			$node = array(
				'id'          => $el['id'],
				'type'        => $widget_type,
				'el_type'     => $el_type,
				'settings'    => $this->extract_key_settings( $el['settings'] ?? array() ),
			);

			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$node['children'] = $this->parse_elements( $el['elements'] );
			} else {
				$node['children'] = array();
			}

			$parsed[] = $node;
		}

		return $parsed;
	}

	/**
	 * Extract essential content & styling properties from raw widget settings.
	 *
	 * @param array $settings Raw Elementor settings array.
	 * @return array Cleaned summary properties.
	 */
	private function extract_key_settings( array $settings ): array {
		$key_fields = array(
			'title', 'header_size', 'editor', 'text', 'link', 'image', 'icon',
			'align', 'align_mobile', 'align_tablet',
			'title_color', 'background_color', 'typography_font_family',
			'typography_font_size', 'typography_font_weight',
			'margin', 'padding', 'width', 'flex_direction'
		);

		$summary = array();
		foreach ( $key_fields as $field ) {
			if ( isset( $settings[ $field ] ) && '' !== $settings[ $field ] ) {
				$summary[ $field ] = $settings[ $field ];
			}
		}

		return $summary;
	}

	/**
	 * Locate a specific element by ID in a page's element tree.
	 *
	 * @param array  $elements Element tree array.
	 * @param string $element_id Element ID to locate.
	 * @return array|null Node array if found, null otherwise.
	 */
	public function find_element_by_id( array $elements, string $element_id ): ?array {
		foreach ( $elements as $el ) {
			if ( isset( $el['id'] ) && $el['id'] === $element_id ) {
				return $el;
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$found = $this->find_element_by_id( $el['elements'], $element_id );
				if ( $found ) {
					return $found;
				}
			}
		}
		return null;
	}
}
