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

class ElementorCreatePageTool extends AbstractTool {

	private AgentEngine $engine;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->engine = $engine;
	}

	public function get_name(): string {
		return 'elementor_create_page';
	}

	public function get_description(): string {
		return 'Creates a new WordPress page and initializes it for Elementor builder editing.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'title' ),
			'properties' => array(
				'title'   => array( 'type' => 'string', 'description' => 'Page title.' ),
				'slug'    => array( 'type' => 'string', 'description' => 'Optional page URL slug.' ),
				'status'  => array( 'type' => 'string', 'enum' => array( 'draft', 'publish', 'private' ), 'description' => 'Post status (default draft).' ),
				'dry_run' => array( 'type' => 'boolean', 'description' => 'Simulate creation without saving.' ),
			),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		return $this->engine->create_page(
			$arguments['title'],
			$arguments['slug'] ?? '',
			$arguments['status'] ?? 'draft',
			(bool) ( $arguments['dry_run'] ?? false ),
			$context['client_id'],
			$context['user_id']
		);
	}
}
