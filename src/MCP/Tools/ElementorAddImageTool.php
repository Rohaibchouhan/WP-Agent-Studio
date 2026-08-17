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

class ElementorAddImageTool extends AbstractTool {

	private AgentEngine $engine;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->engine = $engine;
	}

	public function get_name(): string {
		return 'elementor_add_image';
	}

	public function get_description(): string {
		return 'Adds an image widget referencing an attachment ID or image URL.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id' ),
			'properties' => array(
				'page_id'       => array( 'type' => 'integer' ),
				'url'           => array( 'type' => 'string' ),
				'attachment_id' => array( 'type' => 'integer' ),
				'parent_id'     => array( 'type' => 'string' ),
				'align'         => array( 'type' => 'string', 'enum' => array( 'left', 'center', 'right' ) ),
				'dry_run'       => array( 'type' => 'boolean' ),
			),
		);
	}

	public function execute( array $arguments, array $context ): array {
		$image_setting = array();
		if ( ! empty( $arguments['attachment_id'] ) ) {
			$id = (int) $arguments['attachment_id'];
			$image_setting = array(
				'id'  => $id,
				'url' => wp_get_attachment_url( $id ),
			);
		} elseif ( ! empty( $arguments['url'] ) ) {
			$image_setting = array(
				'id'  => 0,
				'url' => esc_url_raw( $arguments['url'] ),
			);
		}

		$settings = array( 'image' => $image_setting );
		if ( ! empty( $arguments['align'] ) ) {
			$settings['align'] = $arguments['align'];
		}

		return $this->engine->add_element(
			(int) $arguments['page_id'],
			'image',
			$settings,
			$arguments['parent_id'] ?? null,
			-1,
			(bool) ( $arguments['dry_run'] ?? false ),
			$context['client_id'],
			$context['user_id']
		);
	}
}
