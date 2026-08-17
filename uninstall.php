<?php
/**
 * Uninstall Handler
 *
 * Runs when the plugin is deleted from WordPress Admin.
 *
 * @package AiElementorAgent
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Optionally remove plugin settings if user configured cleanup on uninstall
$settings = get_option( 'ai_elementor_agent_settings', array() );

if ( ! empty( $settings['remove_data_on_uninstall'] ) ) {
	delete_option( 'ai_elementor_agent_settings' );
	delete_option( 'ai_elementor_agent_mcp_tokens' );
	delete_option( 'ai_elementor_agent_permissions' );
	delete_option( 'ai_elementor_agent_ai_keys' );
	delete_option( 'ai_elementor_agent_db_version' );

	global $wpdb;
	$table_name = $wpdb->prefix . 'ai_elementor_audit_logs';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
}
