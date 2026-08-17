<?php
namespace AiElementorAgent\Agent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiElementorAgent\Elementor\ElementorAdapter;
use AiElementorAgent\Elementor\ElementorWriter;
use AiElementorAgent\Elementor\ElementorReader;
use AiElementorAgent\Backup\RevisionManager;
use AiElementorAgent\Logging\AuditLogger;
use AiElementorAgent\Security\PermissionManager;

/**
 * Central Agent Engine orchestrating actions, safety checks, dry-runs, and execution.
 */
class AgentEngine {

	private ElementorAdapter $adapter;
	private ContextManager $context_manager;
	private RevisionManager $revision_manager;
	private AuditLogger $audit_logger;
	private PermissionManager $permission_manager;
	private ElementorWriter $writer;
	private ElementorReader $reader;
	private DSLCompiler $dsl_compiler;

	public function __construct(
		ElementorAdapter $adapter,
		ContextManager $context_manager,
		RevisionManager $revision_manager,
		AuditLogger $audit_logger,
		PermissionManager $permission_manager
	) {
		$this->adapter            = $adapter;
		$this->context_manager    = $context_manager;
		$this->revision_manager   = $revision_manager;
		$this->audit_logger       = $audit_logger;
		$this->permission_manager = $permission_manager;
		$this->writer             = new ElementorWriter();
		$this->reader             = new ElementorReader();
		$this->dsl_compiler       = new DSLCompiler();
	}

	/**
	 * Build a full page using Agent DSL payload.
	 */
	public function build_page_from_dsl( array $dsl_payload, bool $dry_run = false, string $client_id = 'mcp-client', int $user_id = 0 ): array {
		$start_time = microtime( true );

		// 1. Permission check
		if ( ! $this->permission_manager->can( 'create_pages', $user_id ) ) {
			return $this->error_response( 'PERMISSION_DENIED', 'User lacks create_pages permission.', 403 );
		}

		// 2. Validate payload schema
		$validation = SchemaValidator::validate_dsl_page( $dsl_payload, $this->adapter->is_pro_active() );
		if ( ! $validation['valid'] ) {
			return $this->error_response( $validation['error']['code'], $validation['error']['message'], 400 );
		}

		$page_data = $dsl_payload['page'];
		$title = sanitize_text_field( $page_data['title'] );
		$slug = ! empty( $page_data['slug'] ) ? sanitize_title( $page_data['slug'] ) : sanitize_title( $title );
		$status = $page_data['status'] ?? 'draft';

		if ( 'publish' === $status && ! $this->permission_manager->can( 'publish_pages', $user_id ) ) {
			$status = 'draft'; // Downgrade to draft if publish permission is not granted
		}

		// 3. Compile DSL to raw Elementor element tree
		$raw_elements = $this->dsl_compiler->compile( $page_data );

		if ( $dry_run ) {
			$duration = (int) ( ( microtime( true ) - $start_time ) * 1000 );
			$this->audit_logger->log( $client_id, $user_id, 'agent_build_page', 'dry_run_build_page', 'success', null, null, $duration );
			return array(
				'success'  => true,
				'dry_run'  => true,
				'action'   => 'CREATE page & BUILD layout',
				'title'    => $title,
				'status'   => $status,
				'elements' => $raw_elements,
			);
		}

		// 4. Create WordPress Page post
		$page_id = wp_insert_post( array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_status'  => $status,
			'post_type'    => 'page',
		) );

		if ( is_wp_error( $page_id ) ) {
			return $this->error_response( 'PAGE_CREATION_FAILED', $page_id->get_error_message(), 500 );
		}

		flush_rewrite_rules( false );

		// 5. Enable Elementor on post & save compiled elements
		$this->adapter->enable_elementor_for_page( $page_id );
		$this->writer->save_page_elements( $page_id, $raw_elements );

		// 6. Create revision snapshot
		$backup_id = $this->revision_manager->create_backup( $page_id, 'Initial AI Page Build' );

		$duration = (int) ( ( microtime( true ) - $start_time ) * 1000 );
		$this->audit_logger->log( $client_id, $user_id, 'agent_build_page', 'build_page', 'success', $page_id, null, $duration );

		return array(
			'success'    => true,
			'page_id'    => $page_id,
			'title'      => $title,
			'status'     => $status,
			'url'        => get_permalink( $page_id ),
			'edit_url'   => admin_url( "post.php?post={$page_id}&action=elementor" ),
			'backup_id'  => $backup_id,
			'elements'   => $raw_elements,
		);
	}

	/**
	 * Create a new blank page initialized for Elementor.
	 */
	public function create_page( string $title, string $slug = '', string $status = 'draft', bool $dry_run = false, string $client_id = 'mcp-client', int $user_id = 0 ): array {
		if ( ! $this->permission_manager->can( 'create_pages', $user_id ) ) {
			return $this->error_response( 'PERMISSION_DENIED', 'User lacks create_pages permission.', 403 );
		}

		$title = sanitize_text_field( $title );
		$slug = ! empty( $slug ) ? sanitize_title( $slug ) : sanitize_title( $title );

		if ( 'publish' === $status && ! $this->permission_manager->can( 'publish_pages', $user_id ) ) {
			$status = 'draft';
		}

		if ( $dry_run ) {
			return array(
				'success' => true,
				'dry_run' => true,
				'action'  => 'CREATE page',
				'title'   => $title,
				'status'  => $status,
			);
		}

		$page_id = wp_insert_post( array(
			'post_title'  => $title,
			'post_name'   => $slug,
			'post_status' => $status,
			'post_type'   => 'page',
		) );

		if ( is_wp_error( $page_id ) ) {
			return $this->error_response( 'PAGE_CREATION_FAILED', $page_id->get_error_message(), 500 );
		}

		flush_rewrite_rules( false );

		$this->adapter->enable_elementor_for_page( $page_id );
		$this->writer->save_page_elements( $page_id, array() );

		return array(
			'success'  => true,
			'page_id'  => $page_id,
			'title'    => $title,
			'status'   => $status,
			'url'      => get_permalink( $page_id ),
			'edit_url' => admin_url( "post.php?post={$page_id}&action=elementor" ),
		);
	}

	/**
	 * Add a widget/container element to a page.
	 */
	public function add_element( int $page_id, string $widget_type, array $settings = array(), ?string $parent_id = null, int $position = -1, bool $dry_run = false, string $client_id = 'mcp-client', int $user_id = 0 ): array {
		if ( ! $this->permission_manager->can( 'modify_pages', $user_id ) ) {
			return $this->error_response( 'PERMISSION_DENIED', 'User lacks modify_pages permission.', 403 );
		}

		// Validate widget type
		$val = SchemaValidator::validate_element_params( $widget_type, $settings, $this->adapter->is_pro_active() );
		if ( ! $val['valid'] ) {
			return $this->error_response( $val['error']['code'], $val['error']['message'], 400 );
		}

		$node = $this->writer->create_element_node( $widget_type, $settings );

		if ( $dry_run ) {
			return array(
				'success'    => true,
				'dry_run'    => true,
				'action'     => 'ADD element',
				'widget_type'=> $widget_type,
				'parent_id'  => $parent_id,
				'node'       => $node,
			);
		}

		// Snapshot backup
		$this->revision_manager->create_backup( $page_id, "Before adding widget {$widget_type}" );

		$res = $this->writer->add_element( $page_id, $node, $parent_id, $position );
		$this->audit_logger->log( $client_id, $user_id, 'elementor_add_element', 'add_element', 'success', $page_id, $node['id'] );

		return array(
			'success'    => true,
			'page_id'    => $page_id,
			'element_id' => $node['id'],
			'node'       => $node,
		);
	}

	/**
	 * Update settings of an existing element.
	 */
	public function update_element( int $page_id, string $element_id, array $settings, bool $dry_run = false, string $client_id = 'mcp-client', int $user_id = 0 ): array {
		if ( ! $this->permission_manager->can( 'modify_pages', $user_id ) ) {
			return $this->error_response( 'PERMISSION_DENIED', 'User lacks modify_pages permission.', 403 );
		}

		if ( $dry_run ) {
			return array(
				'success'    => true,
				'dry_run'    => true,
				'action'     => 'UPDATE element settings',
				'page_id'    => $page_id,
				'element_id' => $element_id,
				'settings'   => $settings,
			);
		}

		$this->revision_manager->create_backup( $page_id, "Before updating element {$element_id}" );
		$updated = $this->writer->update_element( $page_id, $element_id, $settings );

		if ( ! $updated ) {
			return $this->error_response( 'ELEMENT_NOT_FOUND', "Element {$element_id} was not found on page {$page_id}.", 404 );
		}

		$this->audit_logger->log( $client_id, $user_id, 'elementor_update_element', 'update_element', 'success', $page_id, $element_id );

		return array(
			'success'    => true,
			'page_id'    => $page_id,
			'element_id' => $element_id,
			'settings'   => $settings,
		);
	}

	/**
	 * Delete an element node from a page.
	 */
	public function delete_element( int $page_id, string $element_id, bool $dry_run = false, string $client_id = 'mcp-client', int $user_id = 0 ): array {
		if ( ! $this->permission_manager->can( 'delete_pages', $user_id ) && ! $this->permission_manager->can( 'modify_pages', $user_id ) ) {
			return $this->error_response( 'PERMISSION_DENIED', 'User lacks permission to delete elements.', 403 );
		}

		if ( $dry_run ) {
			return array(
				'success'    => true,
				'dry_run'    => true,
				'action'     => 'DELETE element',
				'page_id'    => $page_id,
				'element_id' => $element_id,
			);
		}

		$this->revision_manager->create_backup( $page_id, "Before deleting element {$element_id}" );
		$deleted = $this->writer->delete_element( $page_id, $element_id );

		if ( ! $deleted ) {
			return $this->error_response( 'ELEMENT_NOT_FOUND', "Element {$element_id} not found on page {$page_id}.", 404 );
		}

		$this->audit_logger->log( $client_id, $user_id, 'elementor_delete_element', 'delete_element', 'success', $page_id, $element_id );

		return array(
			'success'    => true,
			'page_id'    => $page_id,
			'element_id' => $element_id,
		);
	}

	/**
	 * Helper for error responses.
	 */
	private function error_response( string $code, string $message, int $status_code = 400 ): array {
		return array(
			'success' => false,
			'error'   => array(
				'code'    => $code,
				'message' => $message,
			),
		);
	}
}
