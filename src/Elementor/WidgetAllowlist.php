<?php
namespace AiElementorAgent\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allowlist of supported Elementor widgets and layout structures.
 */
class WidgetAllowlist {

	/**
	 * Core permitted widget types and metadata.
	 */
	private static array $allowed_widgets = array(
		// Layout
		'container' => array( 'pro' => false, 'category' => 'layout', 'el_type' => 'container' ),

		// Core Content
		'heading'               => array( 'pro' => false, 'category' => 'basic', 'el_type' => 'widget', 'widget_type' => 'heading' ),
		'text-editor'           => array( 'pro' => false, 'category' => 'basic', 'el_type' => 'widget', 'widget_type' => 'text-editor' ),
		'button'                => array( 'pro' => false, 'category' => 'basic', 'el_type' => 'widget', 'widget_type' => 'button' ),
		'image'                 => array( 'pro' => false, 'category' => 'basic', 'el_type' => 'widget', 'widget_type' => 'image' ),
		'icon'                  => array( 'pro' => false, 'category' => 'basic', 'el_type' => 'widget', 'widget_type' => 'icon' ),
		'divider'               => array( 'pro' => false, 'category' => 'basic', 'el_type' => 'widget', 'widget_type' => 'divider' ),
		'spacer'                => array( 'pro' => false, 'category' => 'basic', 'el_type' => 'widget', 'widget_type' => 'spacer' ),

		// Advanced General
		'icon-list'             => array( 'pro' => false, 'category' => 'general', 'el_type' => 'widget', 'widget_type' => 'icon-list' ),
		'image-box'             => array( 'pro' => false, 'category' => 'general', 'el_type' => 'widget', 'widget_type' => 'image-box' ),
		'testimonial'           => array( 'pro' => false, 'category' => 'general', 'el_type' => 'widget', 'widget_type' => 'testimonial' ),
		'testimonial-carousel'  => array( 'pro' => true,  'category' => 'pro',     'el_type' => 'widget', 'widget_type' => 'testimonial-carousel' ),
		'pricing-table'         => array( 'pro' => true,  'category' => 'pro',     'el_type' => 'widget', 'widget_type' => 'pricing-table' ),
		'tabs'                  => array( 'pro' => false, 'category' => 'general', 'el_type' => 'widget', 'widget_type' => 'tabs' ),
		'accordion'             => array( 'pro' => false, 'category' => 'general', 'el_type' => 'widget', 'widget_type' => 'accordion' ),
		'toggle'                => array( 'pro' => false, 'category' => 'general', 'el_type' => 'widget', 'widget_type' => 'toggle' ),
		'social-icons'          => array( 'pro' => false, 'category' => 'general', 'el_type' => 'widget', 'widget_type' => 'social-icons' ),
		'form'                  => array( 'pro' => true,  'category' => 'pro',     'el_type' => 'widget', 'widget_type' => 'form' ),
		'html'                  => array( 'pro' => false, 'category' => 'general', 'el_type' => 'widget', 'widget_type' => 'html' ),
	);

	/**
	 * Validate if a widget type is allowed and supported.
	 *
	 * @param string $type Widget type identifier.
	 * @param bool   $is_pro_active Whether Elementor Pro is active.
	 * @return array Result array ['valid' => bool, 'reason' => string].
	 */
	public static function validate_widget( string $type, bool $is_pro_active = false ): array {
		$type = strtolower( trim( $type ) );

		if ( ! isset( self::$allowed_widgets[ $type ] ) ) {
			return array(
				'valid'  => false,
				'reason' => sprintf( 'Widget type "%s" is not in the allowed widget list.', $type ),
			);
		}

		$meta = self::$allowed_widgets[ $type ];
		if ( $meta['pro'] && ! $is_pro_active ) {
			return array(
				'valid'  => false,
				'reason' => sprintf( 'Widget type "%s" requires Elementor Pro.', $type ),
			);
		}

		return array(
			'valid'  => true,
			'meta'   => $meta,
			'reason' => 'Widget valid.',
		);
	}

	/**
	 * Get list of available widgets.
	 *
	 * @param bool $is_pro_active Whether Elementor Pro is active.
	 * @return array Available widget identifiers.
	 */
	public static function get_available_widgets( bool $is_pro_active = false ): array {
		$available = array();
		foreach ( self::$allowed_widgets as $type => $meta ) {
			if ( $meta['pro'] && ! $is_pro_active ) {
				continue;
			}
			$available[] = array(
				'type'     => $type,
				'category' => $meta['category'],
				'pro_only' => $meta['pro'],
			);
		}
		return $available;
	}
}
