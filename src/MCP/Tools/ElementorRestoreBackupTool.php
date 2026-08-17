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

class ElementorRestoreBackupTool extends AbstractTool {

	private RevisionManager $revision_manager;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->revision_manager = new RevisionManager();
	}

	public function get_name(): string {
		return 'elementor_restore_backup';
	}

	public function get_description(): string {
		return 'Restores an Elementor page to a previous revision/backup snapshot state.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'page_id', 'backup_id' ),
			'properties' => array(
				'page_id'   => array( 'type' => 'integer' ),
				'backup_id' => array( 'type' => 'string' ),
			),
		);
	}

	public function execute( array $arguments, array $context ): array {
		$restored = $this->revision_manager->restore_backup(
			(int) $arguments['page_id'],
			$arguments['backup_id']
		);

		return array(
			'success'   => $restored,
			'page_id'   => (int) $arguments['page_id'],
			'backup_id' => $arguments['backup_id'],
		);
	}
}
