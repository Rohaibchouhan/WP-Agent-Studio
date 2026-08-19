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

class ElementorDeleteElementTool extends AbstractTool {

	private AgentEngine $engine;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->engine = $engine;
	}

	public function get_name(): string {
		return 'elementor_delete_element';
	}

	public function get_description(): string {
		return 'Deletes an element and its children from an Elementor page tree.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id', 'element_id' ),
			'properties' => array(
				'page_id'    => array( 'type' => 'integer' ),
				'element_id' => array( 'type' => 'string' ),
				'dry_run'    => array( 'type' => 'boolean' ),
			),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		return $this->engine->delete_element(
			(int) $arguments['page_id'],
			$arguments['element_id'],
			(bool) ( $arguments['dry_run'] ?? false ),
			$context['client_id'],
			$context['user_id']
		);
	}
}
