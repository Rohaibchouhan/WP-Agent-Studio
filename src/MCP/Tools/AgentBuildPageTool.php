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

class AgentBuildPageTool extends AbstractTool {

	private AgentEngine $engine;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->engine = $engine;
	}

	public function get_name(): string {
		return 'agent_build_page';
	}

	public function get_description(): string {
		return 'High-level orchestration tool: compiles complete Agent DSL document payload into a new Elementor page with layout, containers, widgets, typography, and colors.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'dsl' ),
			'properties' => array(
				'dsl'     => array( 'type' => 'object', 'description' => 'Full Agent DSL object containing page title, status, and section containers tree.' ),
				'dry_run' => array( 'type' => 'boolean', 'description' => 'If true, returns compiled layout tree without creating post.' ),
			),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		return $this->engine->build_page_from_dsl(
			$arguments['dsl'],
			(bool) ( $arguments['dry_run'] ?? false ),
			$context['client_id'],
			$context['user_id']
		);
	}
}
