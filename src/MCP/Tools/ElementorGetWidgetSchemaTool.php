<?php
namespace AiElementorAgent\MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiElementorAgent\MCP\AbstractTool;
use AiElementorAgent\Agent\AgentEngine;
use AiElementorAgent\Agent\ContextManager;
use AiElementorAgent\Security\TokenManager;
use AiElementorAgent\Elementor\GlobalStylesManager;
use AiElementorAgent\Elementor\WidgetSchemaExtractor;

class ElementorGetWidgetSchemaTool extends AbstractTool {

	private WidgetSchemaExtractor $extractor;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->extractor = new WidgetSchemaExtractor();
	}

	public function get_name(): string {
		return 'elementor_get_widget_schema';
	}

	public function get_description(): string {
		return 'Inspects registered Elementor Core, Pro, and 3rd-party widgets. Returns full control schemas, control types, default values, options, and responsive flags.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'widget_type' => array(
					'type'        => 'string',
					'description' => 'Optional specific widget type identifier (e.g. "heading", "button", "container"). Omit to list all registered widgets.',
				),
			),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		$widget_type = ! empty( $arguments['widget_type'] ) ? sanitize_text_field( $arguments['widget_type'] ) : '';

		if ( ! empty( $widget_type ) ) {
			$schema = $this->extractor->get_widget_schema( $widget_type );
			return array(
				'success' => true,
				'data'    => $schema,
			);
		}

		$widgets = $this->extractor->get_registered_widgets();
		return array(
			'success' => true,
			'data'    => array(
				'total'   => count( $widgets ),
				'widgets' => $widgets,
			),
		);
	}
}
