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

class ElementorUpdateElementTool extends AbstractTool {

	private AgentEngine $engine;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->engine = $engine;
	}

	public function get_name(): string {
		return 'elementor_update_element';
	}

	public function get_description(): string {
		return 'Updates settings, typography, colors, padding, or responsive parameters of an existing element by element ID.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id', 'element_id', 'settings' ),
			'properties' => array(
				'page_id'    => array( 'type' => 'integer' ),
				'element_id' => array( 'type' => 'string' ),
				'settings'   => array( 'type' => 'object' ),
				'dry_run'    => array( 'type' => 'boolean' ),
			),
		);
	}

	public function execute( array $arguments, array $context ): array {
		return $this->engine->update_element(
			(int) $arguments['page_id'],
			$arguments['element_id'],
			$arguments['settings'],
			(bool) ( $arguments['dry_run'] ?? false ),
			$context['client_id'],
			$context['user_id']
		);
	}
}
