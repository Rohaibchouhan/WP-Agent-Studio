<?php
namespace AiElementorAgent\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamically inspects registered Elementor Core, Pro, and 3rd-party widgets
 * and extracts AI-ready control schemas.
 */
class WidgetSchemaExtractor {

	/**
	 * Get summary of all registered widgets on the site.
	 */
	public function get_registered_widgets(): array {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance->widgets_manager ) ) {
			return WidgetAllowlist::get_available_widgets( false );
		}

		$widgets_manager = \Elementor\Plugin::$instance->widgets_manager;
		$widget_types    = $widgets_manager->get_widget_types();
		$result          = array();

		foreach ( $widget_types as $type => $widget_obj ) {
			/** @var \Elementor\Widget_Base $widget_obj */
			$categories = method_exists( $widget_obj, 'get_categories' ) ? $widget_obj->get_categories() : array();
			$title      = method_exists( $widget_obj, 'get_title' ) ? $widget_obj->get_title() : ucfirst( $type );
			$icon       = method_exists( $widget_obj, 'get_icon' ) ? $widget_obj->get_icon() : '';

			$result[] = array(
				'type'       => $type,
				'title'      => $title,
				'icon'       => $icon,
				'categories' => $categories,
				'is_pro'     => strpos( $type, 'pro-' ) === 0 || in_array( 'pro-elements', $categories, true ),
			);
		}

		return $result;
	}

	/**
	 * Get detailed schema for a specific widget type.
	 */
	public function get_widget_schema( string $widget_type ): array {
		$widget_type = strtolower( trim( $widget_type ) );

		if ( 'container' === $widget_type ) {
			return $this->get_container_schema();
		}

		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance->widgets_manager ) ) {
			return array(
				'type'        => $widget_type,
				'title'       => ucfirst( $widget_type ),
				'controls'    => array(),
				'fallback'    => true,
				'description' => 'Elementor plugin is not active in live context. Default fallback schema.',
			);
		}

		$widget_obj = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $widget_type );

		if ( ! $widget_obj ) {
			return array(
				'found'  => false,
				'error'  => sprintf( 'Widget type "%s" is not registered on this site.', $widget_type ),
			);
		}

		$controls_raw = method_exists( $widget_obj, 'get_controls' ) ? $widget_obj->get_controls() : array();
		$controls_parsed = array();

		foreach ( $controls_raw as $id => $control ) {
			$type = $control['type'] ?? 'text';
			if ( in_array( $type, array( 'section', 'tab', 'popover_toggle' ), true ) ) {
				continue;
			}

			$controls_parsed[ $id ] = array(
				'id'          => $id,
				'label'       => $control['label'] ?? $id,
				'type'        => $type,
				'default'     => $control['default'] ?? null,
				'options'     => $control['options'] ?? array(),
				'responsive'  => ! empty( $control['responsive'] ),
				'dynamic'     => ! empty( $control['dynamic'] ),
				'description' => $control['description'] ?? '',
			);
		}

		return array(
			'found'       => true,
			'type'        => $widget_type,
			'title'       => method_exists( $widget_obj, 'get_title' ) ? $widget_obj->get_title() : ucfirst( $widget_type ),
			'categories'  => method_exists( $widget_obj, 'get_categories' ) ? $widget_obj->get_categories() : array(),
			'controls'    => $controls_parsed,
		);
	}

	/**
	 * Schema definition for Flexbox Container element.
	 */
	public function get_container_schema(): array {
		return array(
			'found'       => true,
			'type'        => 'container',
			'title'       => 'Flexbox Container',
			'categories'  => array( 'layout' ),
			'controls'    => array(
				'container_type' => array(
					'id'      => 'container_type',
					'label'   => 'Container Type',
					'type'    => 'select',
					'default' => 'flex',
					'options' => array( 'flex' => 'Flexbox', 'grid' => 'Grid' ),
				),
				'flex_direction' => array(
					'id'         => 'flex_direction',
					'label'      => 'Direction',
					'type'       => 'select',
					'default'    => 'column',
					'options'    => array( 'row' => 'Row', 'column' => 'Column', 'row-reverse' => 'Row Reverse', 'column-reverse' => 'Column Reverse' ),
					'responsive' => true,
				),
				'flex_justify_content' => array(
					'id'         => 'flex_justify_content',
					'label'      => 'Justify Content',
					'type'       => 'select',
					'default'    => 'flex-start',
					'options'    => array( 'flex-start' => 'Start', 'center' => 'Center', 'flex-end' => 'End', 'space-between' => 'Space Between', 'space-around' => 'Space Around', 'space-evenly' => 'Space Evenly' ),
					'responsive' => true,
				),
				'flex_align_items' => array(
					'id'         => 'flex_align_items',
					'label'      => 'Align Items',
					'type'       => 'select',
					'default'    => 'stretch',
					'options'    => array( 'flex-start' => 'Start', 'center' => 'Center', 'flex-end' => 'End', 'stretch' => 'Stretch' ),
					'responsive' => true,
				),
				'gap' => array(
					'id'         => 'gap',
					'label'      => 'Gaps',
					'type'       => 'dimensions',
					'responsive' => true,
				),
				'padding' => array(
					'id'         => 'padding',
					'label'      => 'Padding',
					'type'       => 'dimensions',
					'responsive' => true,
				),
			),
		);
	}
}
