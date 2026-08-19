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

class ElementorGetPageTool extends AbstractTool {

	private ContextManager $context;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->context = $context;
	}

	public function get_name(): string {
		return 'elementor_get_page';
	}

	public function get_description(): string {
		return 'Returns structured representation of an Elementor page layout tree and content widgets.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id' ),
			'properties' => array(
				'page_id' => array( 'type' => 'integer', 'description' => 'Target Elementor Page ID.' ),
				'verbose' => array( 'type' => 'boolean', 'description' => 'If true, returns full raw Elementor JSON string.' ),
			),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		$page_id = (int) $arguments['page_id'];
		$verbose = (bool) ( $arguments['verbose'] ?? false );

		$page_data = $this->context->get_page_context( $page_id, $verbose );

		return array(
			'success' => true,
			'data'    => $page_data,
		);
	}
}
