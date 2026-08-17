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
use AiElementorAgent\Backup\RevisionManager;

class ElementorCreateBackupTool extends AbstractTool {

	private RevisionManager $revision_manager;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->revision_manager = new RevisionManager();
	}

	public function get_name(): string {
		return 'elementor_create_backup';
	}

	public function get_description(): string {
		return 'Explicitly creates a snapshot restore point backup of an Elementor page.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id' ),
			'properties' => array(
				'page_id' => array( 'type' => 'integer' ),
				'reason'  => array( 'type' => 'string' ),
			),
		);
	}

	public function execute( array $arguments, array $context ): array {
		$backup_id = $this->revision_manager->create_backup(
			(int) $arguments['page_id'],
			$arguments['reason'] ?? 'Manual MCP Backup'
		);

		return array(
			'success'   => true,
			'page_id'   => (int) $arguments['page_id'],
			'backup_id' => $backup_id,
		);
	}
}
