<?php
namespace AiElementorAgent\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base Abstract class for all MCP tools.
 */
abstract class AbstractTool {

	/**
	 * Unique tool identifier (e.g., 'wordpress_get_site_info').
	 */
	abstract public function get_name(): string;

	/**
	 * Human-readable tool description for MCP client discovery.
	 */
	abstract public function get_description(): string;

	/**
	 * Parameter input JSON schema for tool argument validation.
	 */
	abstract public function get_schema(): array;

	/**
	 * Execute tool logic.
	 *
	 * @param array $arguments Validated tool input parameters.
	 * @param array $context Execution context (authenticated user, token, client_id).
	 * @return array Tool response array.
	 */
	abstract public function execute( array $arguments, array $context ): array;
}
