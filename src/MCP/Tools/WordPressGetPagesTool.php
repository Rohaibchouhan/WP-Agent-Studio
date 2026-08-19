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

class WordPressGetPagesTool extends AbstractTool {

	private ContextManager $context;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->context = $context;
	}

	public function get_name(): string {
		return 'wordpress_get_pages';
	}

	public function get_description(): string {
		return 'Lists WordPress pages with post status, slug, permalink, and Elementor status.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'search' => array( 'type' => 'string', 'description' => 'Optional search query term.' ),
				'limit'  => array( 'type' => 'integer', 'description' => 'Maximum number of pages to return (default 50).' ),
			),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		$search = sanitize_text_field( $arguments['search'] ?? '' );
		$limit = isset( $arguments['limit'] ) ? (int) $arguments['limit'] : 50;

		$pages = $this->context->get_pages( $search, $limit );

		return array(
			'success' => true,
			'count'   => count( $pages ),
			'pages'   => $pages,
		);
	}
}
