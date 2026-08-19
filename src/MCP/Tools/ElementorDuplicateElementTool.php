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

class ElementorDuplicateElementTool extends AbstractTool {

	private ElementorWriter $writer;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->writer = new ElementorWriter();
	}

	public function get_name(): string {
		return 'elementor_duplicate_element';
	}

	public function get_description(): string {
		return 'Duplicates an element subtree recursively with unique hex IDs.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id', 'element_id' ),
			'properties' => array(
				'page_id'    => array( 'type' => 'integer' ),
				'element_id' => array( 'type' => 'string' ),
			),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		$cloned = $this->writer->duplicate_element(
			(int) $arguments['page_id'],
			$arguments['element_id']
		);

		if ( ! $cloned ) {
			return array(
				'success' => false,
				'error'   => array( 'code' => 'ELEMENT_NOT_FOUND', 'message' => 'Element not found.' ),
			);
		}

		return array(
			'success'        => true,
			'page_id'        => (int) $arguments['page_id'],
			'new_element_id' => $cloned['id'],
			'cloned_node'    => $cloned,
		);
	}
}
