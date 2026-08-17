<?php
namespace AiElementorAgent\Agent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiElementorAgent\Elementor\ElementorWriter;
use AiElementorAgent\Elementor\WidgetAllowlist;

/**
 * Compiles Agent DSL JSON structures into raw Elementor element trees.
 */
class DSLCompiler {

	private ElementorWriter $writer;

	public function __construct() {
		$this->writer = new ElementorWriter();
	}

	/**
	 * Compile full Agent DSL page object into raw Elementor elements array.
	 *
	 * @param array $dsl_page Agent DSL page structure array.
	 * @return array Array of raw Elementor nodes.
	 */
	public function compile( array $dsl_page ): array {
		$sections = $dsl_page['sections'] ?? array();
		$compiled_elements = array();

		foreach ( $sections as $section_dsl ) {
			$compiled_elements[] = $this->compile_element_node( $section_dsl );
		}

		return $compiled_elements;
	}

	/**
	 * Compile a single DSL element node (and its children) recursively.
	 */
	public function compile_element_node( array $dsl_node ): array {
		$type = $dsl_node['type'] ?? 'container';
		$custom_id = $dsl_node['id'] ?? null;

		// Merge content, layout, styles, and responsive settings into Elementor settings array
		$settings = array();

		if ( ! empty( $dsl_node['layout'] ) && is_array( $dsl_node['layout'] ) ) {
			$settings = array_merge( $settings, $this->map_layout_properties( $dsl_node['layout'] ) );
		}

		if ( ! empty( $dsl_node['content'] ) && is_array( $dsl_node['content'] ) ) {
			$settings = array_merge( $settings, $this->map_content_properties( $dsl_node['content'] ) );
		}

		if ( ! empty( $dsl_node['styles'] ) && is_array( $dsl_node['styles'] ) ) {
			$settings = array_merge( $settings, $this->map_style_properties( $dsl_node['styles'] ) );
		}

		if ( ! empty( $dsl_node['responsive'] ) && is_array( $dsl_node['responsive'] ) ) {
			$settings = array_merge( $settings, $this->map_responsive_properties( $dsl_node['responsive'] ) );
		}

		$node = $this->writer->create_element_node( $type, $settings, $custom_id );

		if ( ! empty( $dsl_node['children'] ) && is_array( $dsl_node['children'] ) ) {
			$compiled_children = array();
			foreach ( $dsl_node['children'] as $child_dsl ) {
				$compiled_children[] = $this->compile_element_node( $child_dsl );
			}
			$node['elements'] = $compiled_children;
		}

		return $node;
	}

	private function map_layout_properties( array $layout ): array {
		$mapped = array();
		if ( isset( $layout['direction'] ) ) {
			$mapped['flex_direction'] = $layout['direction'];
		}
		if ( isset( $layout['content_width'] ) ) {
			$mapped['content_width'] = $layout['content_width'];
		}
		if ( isset( $layout['width'] ) ) {
			$mapped['width'] = $layout['width'];
		}
		if ( isset( $layout['min_height'] ) ) {
			$mapped['min_height'] = $layout['min_height'];
		}
		if ( isset( $layout['justify_content'] ) ) {
			$mapped['justify_content'] = $layout['justify_content'];
		}
		if ( isset( $layout['align_items'] ) ) {
			$mapped['align_items'] = $layout['align_items'];
		}
		if ( isset( $layout['gap'] ) ) {
			$mapped['gap'] = $layout['gap'];
		}
		return array_merge( $mapped, $layout );
	}

	private function map_content_properties( array $content ): array {
		$mapped = array();
		if ( isset( $content['title'] ) ) {
			$mapped['title'] = $content['title'];
		}
		if ( isset( $content['editor'] ) ) {
			$mapped['editor'] = $content['editor'];
		}
		if ( isset( $content['text'] ) ) {
			$mapped['text'] = $content['text'];
		}
		if ( isset( $content['header_size'] ) ) {
			$mapped['header_size'] = $content['header_size'];
		}
		if ( isset( $content['link'] ) ) {
			$mapped['link'] = is_array( $content['link'] ) ? $content['link'] : array( 'url' => $content['link'] );
		}
		if ( isset( $content['image'] ) ) {
			$mapped['image'] = is_array( $content['image'] ) ? $content['image'] : array( 'url' => $content['image'] );
		}
		return array_merge( $mapped, $content );
	}

	private function map_style_properties( array $styles ): array {
		$mapped = array();
		if ( isset( $styles['title_color'] ) ) {
			$mapped['title_color'] = $styles['title_color'];
		}
		if ( isset( $styles['background_color'] ) ) {
			$mapped['background_color'] = $styles['background_color'];
		}
		if ( isset( $styles['text_color'] ) ) {
			$mapped['text_color'] = $styles['text_color'];
		}
		if ( isset( $styles['typography_font_family'] ) ) {
			$mapped['typography_font_family'] = $styles['typography_font_family'];
		}
		if ( isset( $styles['typography_font_size'] ) ) {
			if ( is_array( $styles['typography_font_size'] ) ) {
				$mapped['typography_font_size'] = array(
					'unit' => $styles['typography_font_size']['unit'] ?? 'px',
					'size' => $styles['typography_font_size']['size'] ?? 16,
				);
				if ( isset( $styles['typography_font_size']['tablet'] ) ) {
					$mapped['typography_font_size_tablet'] = array(
						'unit' => $styles['typography_font_size']['unit'] ?? 'px',
						'size' => $styles['typography_font_size']['tablet'],
					);
				}
				if ( isset( $styles['typography_font_size']['mobile'] ) ) {
					$mapped['typography_font_size_mobile'] = array(
						'unit' => $styles['typography_font_size']['unit'] ?? 'px',
						'size' => $styles['typography_font_size']['mobile'],
					);
				}
			} else {
				$mapped['typography_font_size'] = array( 'unit' => 'px', 'size' => (int) $styles['typography_font_size'] );
			}
		}
		return array_merge( $mapped, $styles );
	}

	private function map_responsive_properties( array $responsive ): array {
		$mapped = array();
		if ( isset( $responsive['desktop'] ) ) {
			foreach ( $responsive['desktop'] as $key => $val ) {
				$mapped[ $key ] = $val;
			}
		}
		if ( isset( $responsive['tablet'] ) ) {
			foreach ( $responsive['tablet'] as $key => $val ) {
				$mapped[ $key . '_tablet' ] = $val;
			}
		}
		if ( isset( $responsive['mobile'] ) ) {
			foreach ( $responsive['mobile'] as $key => $val ) {
				$mapped[ $key . '_mobile' ] = $val;
			}
		}
		return $mapped;
	}
}
