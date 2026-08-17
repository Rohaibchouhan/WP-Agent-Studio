<?php
namespace AiElementorAgent\Agent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiElementorAgent\Elementor\WidgetAllowlist;

/**
 * Validates incoming payloads and operations against rules and allowed widgets.
 */
class SchemaValidator {

	/**
	 * Validate element creation parameters.
	 */
	public static function validate_element_params( string $widget_type, array $settings, bool $is_pro_active = false ): array {
		$allowlist_check = WidgetAllowlist::validate_widget( $widget_type, $is_pro_active );
		if ( ! $allowlist_check['valid'] ) {
			return array(
				'valid' => false,
				'error' => array(
					'code'    => 'WIDGET_NOT_AVAILABLE',
					'message' => $allowlist_check['reason'],
				),
			);
		}

		return array( 'valid' => true );
	}

	/**
	 * Validate DSL Page payload structure.
	 */
	public static function validate_dsl_page( array $payload, bool $is_pro_active = false ): array {
		if ( empty( $payload['page'] ) || ! is_array( $payload['page'] ) ) {
			return array(
				'valid' => false,
				'error' => array(
					'code'    => 'INVALID_DSL_SCHEMA',
					'message' => 'Missing "page" root object in DSL payload.',
				),
			);
		}

		$page = $payload['page'];
		if ( empty( $page['title'] ) ) {
			return array(
				'valid' => false,
				'error' => array(
					'code'    => 'MISSING_PAGE_TITLE',
					'message' => 'DSL page object requires a non-empty "title".',
				),
			);
		}

		if ( isset( $page['sections'] ) && is_array( $page['sections'] ) ) {
			foreach ( $page['sections'] as $index => $section ) {
				$check = self::validate_dsl_node( $section, "sections[{$index}]", $is_pro_active );
				if ( ! $check['valid'] ) {
					return $check;
				}
			}
		}

		return array( 'valid' => true );
	}

	private static function validate_dsl_node( array $node, string $path, bool $is_pro_active ): array {
		$type = $node['type'] ?? 'container';
		$check = WidgetAllowlist::validate_widget( $type, $is_pro_active );
		if ( ! $check['valid'] ) {
			return array(
				'valid' => false,
				'error' => array(
					'code'    => 'WIDGET_NOT_AVAILABLE',
					'message' => "Invalid widget at {$path}: " . $check['reason'],
				),
			);
		}

		if ( ! empty( $node['children'] ) && is_array( $node['children'] ) ) {
			foreach ( $node['children'] as $index => $child ) {
				$sub = self::validate_dsl_node( $child, "{$path}.children[{$index}]", $is_pro_active );
				if ( ! $sub['valid'] ) {
					return $sub;
				}
			}
		}

		return array( 'valid' => true );
	}
}
