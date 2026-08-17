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

class ElementorSetGlobalColorTool extends AbstractTool {

	private GlobalStylesManager $global_styles;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->global_styles = $global_styles;
	}

	public function get_name(): string {
		return 'elementor_set_global_color';
	}

	public function get_description(): string {
		return 'Updates or creates a global color token in Elementor Active Kit settings.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'id', 'color' ),
			'properties' => array(
				'id'    => array( 'type' => 'string', 'description' => 'Global color token ID (e.g. primary, secondary, text, accent).' ),
				'color' => array( 'type' => 'string', 'description' => 'Hex color value (e.g. #6C63FF).' ),
				'title' => array( 'type' => 'string', 'description' => 'Optional label title.' ),
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

		$success = $this->global_styles->set_global_color(
			$arguments['id'],
			$arguments['color'],
			$arguments['title'] ?? ''
		);

		return array(
			'success' => $success,
			'id'      => $arguments['id'],
			'color'   => $arguments['color'],
		);
	}
}
