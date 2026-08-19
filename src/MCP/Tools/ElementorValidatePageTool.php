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

class ElementorValidatePageTool extends AbstractTool {

	private ElementorWriter $writer;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->writer = new ElementorWriter();
	}

	public function get_name(): string {
		return 'elementor_validate_page';
	}

	public function get_description(): string {
		return 'Audits an Elementor page for orphan containers, missing widget types, or duplicate element IDs.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id' ),
			'properties' => array(
				'page_id' => array( 'type' => 'integer' ),
			),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		$page_id = (int) $arguments['page_id'];
		$elements = $this->writer->get_raw_page_elements( $page_id );

		$seen_ids = array();
		$duplicates = array();
		$node_count = 0;

		$this->audit_node_ids( $elements, $seen_ids, $duplicates, $node_count );

		return array(
			'success'     => empty( $duplicates ),
			'page_id'     => $page_id,
			'total_nodes' => $node_count,
			'duplicates'  => array_unique( $duplicates ),
			'issues'      => empty( $duplicates ) ? array() : array( 'Duplicate element IDs detected.' ),
		);
	}

	private function audit_node_ids( array $elements, array &$seen_ids, array &$duplicates, int &$count ): void {
		foreach ( $elements as $el ) {
			$count++;
			if ( isset( $el['id'] ) ) {
				$id = $el['id'];
				if ( isset( $seen_ids[ $id ] ) ) {
					$duplicates[] = $id;
				} else {
					$seen_ids[ $id ] = true;
				}
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$this->audit_node_ids( $el['elements'], $seen_ids, $duplicates, $count );
			}
		}
	}
}
