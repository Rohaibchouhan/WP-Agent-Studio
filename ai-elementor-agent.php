<?php
/**
 * Plugin Name:       AI Elementor Agent
 * Plugin URI:        https://ai-elementor-agent.org
 * Description:       MCP-first WordPress plugin exposing an Model Context Protocol (MCP) server for AI agents to control WordPress & Elementor.
 * Version:           1.0.5
 * Author:            Rohaib Chouhan
 * Author URI:        https://ai-elementor-agent.org
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-elementor-agent
 * Domain Path:       /languages
 * Requires PHP:      8.1
 * Requires at least: 6.0
 *
 * @package AiElementorAgent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AI_ELEMENTOR_AGENT_VERSION', '1.0.5' );
define( 'AI_ELEMENTOR_AGENT_DB_VERSION', '1.0.0' );
define( 'AI_ELEMENTOR_AGENT_FILE', __FILE__ );
define( 'AI_ELEMENTOR_AGENT_PATH', plugin_dir_path( __FILE__ ) );
define( 'AI_ELEMENTOR_AGENT_URL', plugin_dir_url( __FILE__ ) );

// Setup Autoloader (Composer or Custom Fallback)
if ( file_exists( AI_ELEMENTOR_AGENT_PATH . 'vendor/autoload.php' ) ) {
	require_once AI_ELEMENTOR_AGENT_PATH . 'vendor/autoload.php';
} else {
	spl_autoload_register( function ( $class ) {
		$prefix = 'AiElementorAgent\\';
		$base_dir = AI_ELEMENTOR_AGENT_PATH . 'src/';
		$len = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	} );
}

/**
 * Activation Hook
 */
register_activation_hook( __FILE__, function () {
	// 1. Create database custom table for audit logs
	global $wpdb;
	$table_name = $wpdb->prefix . 'ai_elementor_audit_logs';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table_name} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		client_id VARCHAR(64) NOT NULL DEFAULT 'mcp-client',
		user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		tool_name VARCHAR(128) NOT NULL,
		page_id BIGINT(20) UNSIGNED DEFAULT NULL,
		element_id VARCHAR(64) DEFAULT NULL,
		action VARCHAR(64) NOT NULL,
		status VARCHAR(32) NOT NULL DEFAULT 'success',
		duration_ms INT(11) UNSIGNED DEFAULT 0,
		request_details LONGTEXT NULL,
		error_message TEXT NULL,
		PRIMARY KEY  (id),
		KEY idx_page_id (page_id),
		KEY idx_timestamp (timestamp)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	add_option( 'ai_elementor_agent_db_version', AI_ELEMENTOR_AGENT_DB_VERSION );

	// 2. Initialize default options
	if ( false === get_option( 'ai_elementor_agent_settings' ) ) {
		add_option( 'ai_elementor_agent_settings', array(
			'mcp_enabled' => true,
			'dry_run_mode' => false,
			'max_backups_per_page' => 10,
			'rate_limit_requests' => 60,
			'rate_limit_window_seconds' => 60,
			'log_retention_days' => 30,
			'remove_data_on_uninstall' => false,
		) );
	}

	if ( false === get_option( 'ai_elementor_agent_permissions' ) ) {
		add_option( 'ai_elementor_agent_permissions', array(
			'read_site' => true,
			'read_pages' => true,
			'read_elementor' => true,
			'create_pages' => true,
			'modify_pages' => true,
			'delete_pages' => false, // Disabled by default for safety
			'upload_media' => true,
			'modify_global_styles' => true,
			'publish_pages' => false, // Disabled by default for safety
		) );
	}

	// Flush rewrite rules for REST endpoints
	flush_rewrite_rules();
} );

/**
 * Deactivation Hook
 */
register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );

/**
 * Initialize Plugin
 */
add_action( 'plugins_loaded', function () {
	\AiElementorAgent\Core\Plugin::get_instance()->init();
} );
