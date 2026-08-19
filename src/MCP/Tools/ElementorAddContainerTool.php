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

class ElementorAddContainerTool extends AbstractTool {

	private AgentEngine $engine;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->engine = $engine;
	}

	public function get_name(): string {
		return 'elementor_add_container';
	}

	public function get_description(): string {
		return 'Adds a Flexbox layout container to a page or parent container.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id' ),
			'properties' => array(
				'page_id'   => array( 'type' => 'integer', 'description' => 'Target Page ID.' ),
				'parent_id' => array( 'type' => 'string', 'description' => 'Optional parent container ID.' ),
				'direction' => array( 'type' => 'string', 'enum' => array( 'column', 'row' ), 'description' => 'Flex direction.' ),
				'width'     => array( 'type' => 'string', 'enum' => array( 'boxed', 'full' ), 'description' => 'Content width.' ),
				'position'  => array( 'type' => 'integer', 'description' => 'Insertion index position.' ),
				'dry_run'   => array( 'type' => 'boolean', 'description' => 'Dry run flag.' ),
			),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		$settings = array(
			'flex_direction' => $arguments['direction'] ?? 'column',
			'content_width'  => $arguments['width'] ?? 'boxed',
		);

		return $this->engine->add_element(
			(int) $arguments['page_id'],
			'container',
			$settings,
			$arguments['parent_id'] ?? null,
			isset( $arguments['position'] ) ? (int) $arguments['position'] : -1,
			(bool) ( $arguments['dry_run'] ?? false ),
			$context['client_id'],
			$context['user_id']
		);
	}
}
