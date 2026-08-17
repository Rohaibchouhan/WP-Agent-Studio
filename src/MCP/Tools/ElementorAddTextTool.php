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

class ElementorAddTextTool extends AbstractTool {

	private AgentEngine $engine;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->engine = $engine;
	}

	public function get_name(): string {
		return 'elementor_add_text';
	}

	public function get_description(): string {
		return 'Adds a text editor widget to an Elementor page container.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id', 'content' ),
			'properties' => array(
				'page_id'   => array( 'type' => 'integer' ),
				'content'   => array( 'type' => 'string', 'description' => 'HTML or text content.' ),
				'parent_id' => array( 'type' => 'string' ),
				'color'     => array( 'type' => 'string' ),
				'dry_run'   => array( 'type' => 'boolean' ),
			),
		);
	}

	public function execute( array $arguments, array $context ): array {
		$settings = array(
			'editor' => wp_kses_post( $arguments['content'] ),
		);
		if ( ! empty( $arguments['color'] ) ) {
			$settings['text_color'] = $arguments['color'];
		}

		return $this->engine->add_element(
			(int) $arguments['page_id'],
			'text-editor',
			$settings,
			$arguments['parent_id'] ?? null,
			-1,
			(bool) ( $arguments['dry_run'] ?? false ),
			$context['client_id'],
			$context['user_id']
		);
	}
}
