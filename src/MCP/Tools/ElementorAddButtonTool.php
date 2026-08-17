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

class ElementorAddButtonTool extends AbstractTool {

	private AgentEngine $engine;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->engine = $engine;
	}

	public function get_name(): string {
		return 'elementor_add_button';
	}

	public function get_description(): string {
		return 'Adds a call-to-action (CTA) button widget to an Elementor page.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id', 'text' ),
			'properties' => array(
				'page_id'          => array( 'type' => 'integer' ),
				'text'             => array( 'type' => 'string' ),
				'url'              => array( 'type' => 'string' ),
				'parent_id'        => array( 'type' => 'string' ),
				'background_color' => array( 'type' => 'string' ),
				'text_color'       => array( 'type' => 'string' ),
				'align'            => array( 'type' => 'string', 'enum' => array( 'left', 'center', 'right', 'justify' ) ),
				'dry_run'          => array( 'type' => 'boolean' ),
			),
		);
	}

	public function execute( array $arguments, array $context ): array {
		$settings = array(
			'text' => sanitize_text_field( $arguments['text'] ),
			'link' => array(
				'url'         => esc_url_raw( $arguments['url'] ?? '#' ),
				'is_external' => false,
			),
		);

		if ( ! empty( $arguments['background_color'] ) ) {
			$settings['background_color'] = $arguments['background_color'];
		}
		if ( ! empty( $arguments['text_color'] ) ) {
			$settings['button_text_color'] = $arguments['text_color'];
		}
		if ( ! empty( $arguments['align'] ) ) {
			$settings['align'] = $arguments['align'];
		}

		return $this->engine->add_element(
			(int) $arguments['page_id'],
			'button',
			$settings,
			$arguments['parent_id'] ?? null,
			-1,
			(bool) ( $arguments['dry_run'] ?? false ),
			$context['client_id'],
			$context['user_id']
		);
	}
}
