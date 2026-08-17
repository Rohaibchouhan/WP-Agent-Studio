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
use AiElementorAgent\Core\Plugin;

class ElementorSetGlobalFontTool extends AbstractTool {

	private GlobalStylesManager $global_styles;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->global_styles = $global_styles;
	}

	public function get_name(): string {
		return 'elementor_set_global_font';
	}

	public function get_description(): string {
		return 'Updates or creates a global typography token in Elementor Active Kit settings.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'id', 'font_family' ),
			'properties' => array(
				'id'          => array( 'type' => 'string' ),
				'font_family' => array( 'type' => 'string' ),
				'font_weight' => array( 'type' => 'string' ),
				'title'       => array( 'type' => 'string' ),
			),
		);
	}

	public function execute( array $arguments, array $context ): array {
		$pm = Plugin::get_instance()->get_permission_manager();
		if ( ! $pm->can( 'modify_global_styles', $context['user_id'] ) ) {
			return array(
				'success' => false,
				'error'   => array( 'code' => 'PERMISSION_DENIED', 'message' => 'User lacks modify_global_styles permission.' ),
			);
		}

		$font_settings = array(
			'typography_font_family' => sanitize_text_field( $arguments['font_family'] ),
		);
		if ( ! empty( $arguments['font_weight'] ) ) {
			$font_settings['typography_font_weight'] = sanitize_text_field( $arguments['font_weight'] );
		}

		$success = $this->global_styles->set_global_font(
			$arguments['id'],
			$font_settings,
			$arguments['title'] ?? ''
		);

		return array(
			'success'     => $success,
			'id'          => $arguments['id'],
			'font_family' => $arguments['font_family'],
		);
	}
}
