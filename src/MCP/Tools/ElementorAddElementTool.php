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

class ElementorAddElementTool extends AbstractTool {

	private AgentEngine $engine;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->engine = $engine;
	}

	public function get_name(): string {
		return 'elementor_add_element';
	}

	public function get_description(): string {
		return 'Generic tool to add any permitted Elementor widget type with custom settings.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id', 'widget_type' ),
			'properties' => array(
				'page_id'     => array( 'type' => 'integer' ),
				'widget_type' => array( 'type' => 'string', 'description' => 'Target widget type (e.g. heading, button, icon, divider, spacer, form).' ),
				'settings'    => array( 'type' => 'object', 'description' => 'Key-value map of widget settings.' ),
				'parent_id'   => array( 'type' => 'string' ),
				'position'    => array( 'type' => 'integer' ),
				'dry_run'     => array( 'type' => 'boolean' ),
			),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		return $this->engine->add_element(
			(int) $arguments['page_id'],
			$arguments['widget_type'],
			$arguments['settings'] ?? array(),
			$arguments['parent_id'] ?? null,
			isset( $arguments['position'] ) ? (int) $arguments['position'] : -1,
			(bool) ( $arguments['dry_run'] ?? false ),
			$context['client_id'],
			$context['user_id']
		);
	}
}
