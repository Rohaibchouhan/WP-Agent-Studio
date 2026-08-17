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

class ElementorAddHeadingTool extends AbstractTool {

	private AgentEngine $engine;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->engine = $engine;
	}

	public function get_name(): string {
		return 'elementor_add_heading';
	}

	public function get_description(): string {
		return 'Adds a heading widget to an Elementor page or parent container.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id', 'text' ),
			'properties' => array(
				'page_id'     => array( 'type' => 'integer', 'description' => 'Target Page ID.' ),
				'text'        => array( 'type' => 'string', 'description' => 'Heading text content.' ),
				'tag'         => array( 'type' => 'string', 'enum' => array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ), 'description' => 'HTML tag.' ),
				'parent_id'   => array( 'type' => 'string', 'description' => 'Parent container ID.' ),
				'color'       => array( 'type' => 'string', 'description' => 'Hex color code (e.g. #FFFFFF).' ),
				'align'       => array( 'type' => 'string', 'enum' => array( 'left', 'center', 'right', 'justify' ), 'description' => 'Alignment.' ),
				'dry_run'     => array( 'type' => 'boolean' ),
			),
		);
	}

	public function execute( array $arguments, array $context ): array {
		$settings = array(
			'title'       => $arguments['text'],
			'header_size' => $arguments['tag'] ?? 'h2',
		);

		if ( ! empty( $arguments['color'] ) ) {
			$settings['title_color'] = $arguments['color'];
		}
		if ( ! empty( $arguments['align'] ) ) {
			$settings['align'] = $arguments['align'];
		}

		return $this->engine->add_element(
			(int) $arguments['page_id'],
			'heading',
			$settings,
			$arguments['parent_id'] ?? null,
			-1,
			(bool) ( $arguments['dry_run'] ?? false ),
			$context['client_id'],
			$context['user_id']
		);
	}
}
