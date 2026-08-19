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
use AiElementorAgent\Elementor\ElementorWriter;

class ElementorMoveElementTool extends AbstractTool {

	private ElementorWriter $writer;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->writer = new ElementorWriter();
	}

	public function get_name(): string {
		return 'elementor_move_element';
	}

	public function get_description(): string {
		return 'Changes the parent container or positional index of an element within page tree.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id', 'element_id' ),
			'properties' => array(
				'page_id'       => array( 'type' => 'integer' ),
				'element_id'    => array( 'type' => 'string' ),
				'new_parent_id' => array( 'type' => 'string' ),
				'position'      => array( 'type' => 'integer' ),
			),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		$moved = $this->writer->move_element(
			(int) $arguments['page_id'],
			$arguments['element_id'],
			$arguments['new_parent_id'] ?? null,
			isset( $arguments['position'] ) ? (int) $arguments['position'] : -1
		);

		return array(
			'success'    => $moved,
			'page_id'    => (int) $arguments['page_id'],
			'element_id' => $arguments['element_id'],
		);
	}
}
