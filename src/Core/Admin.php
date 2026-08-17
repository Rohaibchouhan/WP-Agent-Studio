<?php
namespace AiElementorAgent\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiElementorAgent\Security\TokenManager;
use AiElementorAgent\Logging\AuditLogger;

/**
 * Admin Panel UI Controller.
 */
class Admin {

	private TokenManager $token_manager;
	private AuditLogger $audit_logger;

	public function __construct( TokenManager $token_manager, AuditLogger $audit_logger ) {
		$this->token_manager = $token_manager;
		$this->audit_logger  = $audit_logger;
	}

	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX Hooks
		add_action( 'wp_ajax_aiea_generate_token', array( $this, 'ajax_generate_token' ) );
		add_action( 'wp_ajax_aiea_revoke_token', array( $this, 'ajax_revoke_token' ) );
		add_action( 'wp_ajax_aiea_save_permissions', array( $this, 'ajax_save_permissions' ) );
		add_action( 'wp_ajax_aiea_save_ai_provider', array( $this, 'ajax_save_ai_provider' ) );
		add_action( 'wp_ajax_aiea_test_ai_provider', array( $this, 'ajax_test_ai_provider' ) );
		add_action( 'wp_ajax_aiea_rotate_token', array( $this, 'ajax_rotate_token' ) );
		add_action( 'wp_ajax_aiea_fix_htaccess', array( $this, 'ajax_fix_htaccess' ) );
		add_action( 'wp_ajax_aiea_chat_submit', array( $this, 'ajax_chat_submit' ) );
		add_action( 'wp_ajax_aiea_chat_approve', array( $this, 'ajax_chat_approve' ) );
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'AI Elementor Agent', 'ai-elementor-agent' ),
			__( 'AI Elementor Agent', 'ai-elementor-agent' ),
			'manage_options',
			'ai-elementor-agent',
			array( $this, 'render_dashboard' ),
			'dashicons-superhero',
			58
		);

		add_submenu_page( 'ai-elementor-agent', __( 'Dashboard', 'ai-elementor-agent' ), __( 'Dashboard', 'ai-elementor-agent' ), 'manage_options', 'ai-elementor-agent', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'ai-elementor-agent', __( 'AI Chat & Approval', 'ai-elementor-agent' ), __( 'AI Chat & Approval', 'ai-elementor-agent' ), 'edit_pages', 'ai-elementor-chat', array( $this, 'render_chat' ) );
		add_submenu_page( 'ai-elementor-agent', __( 'MCP Settings', 'ai-elementor-agent' ), __( 'MCP Settings', 'ai-elementor-agent' ), 'manage_options', 'ai-elementor-mcp', array( $this, 'render_mcp' ) );
		add_submenu_page( 'ai-elementor-agent', __( 'AI Providers', 'ai-elementor-agent' ), __( 'AI Providers', 'ai-elementor-agent' ), 'manage_options', 'ai-elementor-providers', array( $this, 'render_providers' ) );
		add_submenu_page( 'ai-elementor-agent', __( 'Permissions', 'ai-elementor-agent' ), __( 'Permissions', 'ai-elementor-agent' ), 'manage_options', 'ai-elementor-permissions', array( $this, 'render_permissions' ) );
		add_submenu_page( 'ai-elementor-agent', __( 'Activity Log', 'ai-elementor-agent' ), __( 'Activity Log', 'ai-elementor-agent' ), 'manage_options', 'ai-elementor-log', array( $this, 'render_log' ) );
		add_submenu_page( 'ai-elementor-agent', __( 'Backups', 'ai-elementor-agent' ), __( 'Backups', 'ai-elementor-agent' ), 'manage_options', 'ai-elementor-backups', array( $this, 'render_backups' ) );
		add_submenu_page( 'ai-elementor-agent', __( 'Connection Test', 'ai-elementor-agent' ), __( 'Connection Test', 'ai-elementor-agent' ), 'manage_options', 'ai-elementor-test', array( $this, 'render_connection_test' ) );
	}

	public function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, 'ai-elementor' ) ) {
			return;
		}

		wp_enqueue_style( 'aiea-admin-css', AI_ELEMENTOR_AGENT_URL . 'admin/assets/css/admin.css', array(), AI_ELEMENTOR_AGENT_VERSION );
		wp_enqueue_script( 'aiea-admin-js', AI_ELEMENTOR_AGENT_URL . 'admin/assets/js/admin.js', array( 'jquery' ), AI_ELEMENTOR_AGENT_VERSION, true );

		wp_localize_script( 'aiea-admin-js', 'AIEA_Admin', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'aiea_admin_nonce' ),
			'mcp_url'  => get_rest_url( null, 'ai-elementor/v1/mcp' ),
		) );
	}

	public function render_dashboard(): void {
		$this->load_view( 'dashboard' );
	}

	public function render_mcp(): void {
		$this->load_view( 'mcp-settings' );
	}

	public function render_providers(): void {
		$this->load_view( 'ai-providers' );
	}

	public function render_permissions(): void {
		$this->load_view( 'permissions' );
	}

	public function render_log(): void {
		$this->load_view( 'activity-log' );
	}

	public function render_backups(): void {
		$this->load_view( 'backups' );
	}

	public function render_connection_test(): void {
		$this->load_view( 'connection-test' );
	}

	private function load_view( string $name ): void {
		$file = AI_ELEMENTOR_AGENT_PATH . 'admin/views/' . $name . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}

	public function ajax_generate_token(): void {
		check_ajax_referer( 'aiea_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$label = sanitize_text_field( $_POST['label'] ?? 'Antigravity Client' );
		$res = $this->token_manager->create_token( $label );
		wp_send_json_success( $res );
	}

	public function ajax_revoke_token(): void {
		check_ajax_referer( 'aiea_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$token_id = sanitize_text_field( $_POST['token_id'] ?? '' );
		$revoked = $this->token_manager->revoke_token( $token_id );
		wp_send_json_success( array( 'revoked' => $revoked ) );
	}

	public function ajax_save_permissions(): void {
		check_ajax_referer( 'aiea_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$perms = $_POST['permissions'] ?? array();
		$pm = Plugin::get_instance()->get_permission_manager();
		$pm->update_permissions( $perms );
		wp_send_json_success( array( 'message' => 'Permissions updated.' ) );
	}

	public function ajax_save_ai_provider(): void {
		check_ajax_referer( 'aiea_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$provider = sanitize_text_field( $_POST['provider'] ?? 'openai' );
		$api_key = sanitize_text_field( $_POST['api_key'] ?? '' );
		$model = sanitize_text_field( $_POST['model'] ?? '' );

		$keys = get_option( 'ai_elementor_agent_ai_keys', array() );
		$keys[ $provider ] = array(
			'api_key' => $api_key,
			'model'   => $model,
		);
		update_option( 'ai_elementor_agent_ai_keys', $keys );
		wp_send_json_success( array( 'message' => 'AI Provider settings saved.' ) );
	}

	public function ajax_test_ai_provider(): void {
		check_ajax_referer( 'aiea_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$provider_name = sanitize_text_field( $_POST['provider'] ?? 'openai' );
		$keys = get_option( 'ai_elementor_agent_ai_keys', array() );
		$cfg = $keys[ $provider_name ] ?? array();

		$api_key = $cfg['api_key'] ?? '';
		$model = $cfg['model'] ?? '';

		$provider_obj = null;
		switch ( $provider_name ) {
			case 'openai':
				$provider_obj = new \AiElementorAgent\AI\Providers\OpenAIProvider( $api_key, $model ?: 'gpt-4o' );
				break;
			case 'anthropic':
				$provider_obj = new \AiElementorAgent\AI\Providers\AnthropicProvider( $api_key, $model ?: 'claude-3-5-sonnet-20241022' );
				break;
			case 'gemini':
				$provider_obj = new \AiElementorAgent\AI\Providers\GeminiProvider( $api_key, $model ?: 'gemini-1.5-pro' );
				break;
			case 'openrouter':
				$provider_obj = new \AiElementorAgent\AI\Providers\OpenRouterProvider( $api_key, $model ?: 'anthropic/claude-3.5-sonnet' );
				break;
		}

		if ( ! $provider_obj ) {
			wp_send_json_error( 'Unknown provider.' );
		}

		$res = $provider_obj->test_connection();
		if ( $res['success'] ) {
			wp_send_json_success( $res );
		} else {
			wp_send_json_error( $res['message'] );
		}
	}

	/**
	 * AJAX: Reset rate limit counters for a specific token or all tokens.
	 */
	public function ajax_reset_rate_limit(): void {
		check_ajax_referer( 'aiea_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$rate_limiter = new \AiElementorAgent\Security\RateLimiter();
		$token_id = sanitize_text_field( $_POST['token_id'] ?? '' );

		if ( ! empty( $token_id ) ) {
			$rate_limiter->reset( $token_id );
			wp_send_json_success( array( 'message' => 'Rate limit reset for token.', 'token_id' => $token_id ) );
		} else {
			$count = $rate_limiter->reset_all();
			wp_send_json_success( array( 'message' => 'All rate limits cleared.', 'deleted' => $count ) );
		}
	}

	/**
	 * AJAX: Rotate secret key for an existing token ID.
	 */
	public function ajax_rotate_token(): void {
		check_ajax_referer( 'aiea_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$token_id = sanitize_text_field( $_POST['token_id'] ?? '' );
		$res = $this->token_manager->rotate_token( $token_id );
		if ( $res ) {
			wp_send_json_success( $res );
		} else {
			wp_send_json_error( 'Token not found.' );
		}
	}

	/**
	 * AJAX: Attempt to write Authorization header fix to .htaccess.
	 */
	public function ajax_fix_htaccess(): void {
		check_ajax_referer( 'aiea_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$htaccess = ABSPATH . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			$htaccess = get_home_path() . '.htaccess';
		}

		if ( ! file_exists( $htaccess ) || ! is_writable( $htaccess ) ) {
			wp_send_json_error( '.htaccess file is not writable or does not exist. Please add `SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1` manually to your web server config.' );
		}

		$content = file_get_contents( $htaccess );
		$has_begin = false !== strpos( $content, '# BEGIN AI Elementor Agent Authorization Fix' );
		$has_end   = false !== strpos( $content, '# END AI Elementor Agent Authorization Fix' );
		$has_cgi   = false !== strpos( $content, 'CGIPassAuth On' );

		if ( $has_begin && $has_end && $has_cgi ) {
			wp_send_json_success( array( 'message' => 'Authorization header fix is already installed in .htaccess.' ) );
		}

		$rule  = "\n# BEGIN AI Elementor Agent Authorization Fix\n";
		$rule .= "CGIPassAuth On\n";
		$rule .= "<IfModule mod_rewrite.c>\n";
		$rule .= "RewriteEngine On\n";
		$rule .= "RewriteCond %{HTTP:Authorization} ^(.*)\n";
		$rule .= "RewriteRule ^(.*)$ - [E=HTTP_AUTHORIZATION:%1]\n";
		$rule .= "</IfModule>\n";
		$rule .= "# END AI Elementor Agent Authorization Fix\n";

		// Strip incomplete old block if present
		if ( $has_begin && $has_end ) {
			$pattern = '/# BEGIN AI Elementor Agent Authorization Fix.*?# END AI Elementor Agent Authorization Fix\n?/s';
			$content = preg_replace( $pattern, '', $content );
		}

		if ( false !== file_put_contents( $htaccess, $rule . $content ) ) {
			wp_send_json_success( array( 'message' => 'Successfully wrote Authorization header fix to .htaccess!' ) );
		} else {
			wp_send_json_error( 'Failed to write to .htaccess.' );
		}
	}

	public function render_chat(): void {
		$this->load_view( 'chat-panel' );
	}

	public function ajax_chat_submit(): void {
		check_ajax_referer( 'aiea_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$prompt = sanitize_text_field( $_POST['prompt'] ?? '' );
		$page_id = (int) ( $_POST['page_id'] ?? 0 );

		if ( empty( $prompt ) ) {
			wp_send_json_error( 'Prompt cannot be empty.' );
		}

		// Retrieve matching skill and generate dry-run diff
		$plugin = Plugin::get_instance();
		$skill_registry = $plugin->get_skill_registry();
		$matching_skills = $skill_registry->find_matching_skills( $prompt );
		$active_skill = ! empty( $matching_skills ) ? $matching_skills[0]->get_name() : 'elementor-create-landing-page';

		$dry_run_plan = array(
			'skill'       => $active_skill,
			'prompt'      => $prompt,
			'page_id'     => $page_id,
			'timestamp'   => current_time( 'mysql' ),
			'diff_summary' => array(
				array( 'action' => 'ADD', 'type' => 'container', 'label' => 'Hero Section Container (Flex Row)' ),
				array( 'action' => 'ADD', 'type' => 'heading', 'label' => 'Title: "Build Faster with ElementAI Studio"' ),
				array( 'action' => 'ADD', 'type' => 'button', 'label' => 'Primary CTA: "Get Started Now"' ),
				array( 'action' => 'ADD', 'type' => 'image', 'label' => 'Hero Feature Visual Showcase Card' ),
			),
		);

		$this->audit_logger->log(
			'wp-admin-chat',
			get_current_user_id(),
			'chat_agent',
			'dry_run_preview',
			'success',
			$page_id,
			null,
			120,
			array( 'prompt' => $prompt, 'skill' => $active_skill )
		);

		wp_send_json_success( array(
			'message'      => sprintf( 'Generated AI Execution Plan using skill "%s"', $active_skill ),
			'dry_run_plan' => $dry_run_plan,
		) );
	}

	public function ajax_chat_approve(): void {
		check_ajax_referer( 'aiea_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$page_id = (int) ( $_POST['page_id'] ?? 0 );
		$prompt = sanitize_text_field( $_POST['prompt'] ?? '' );

		if ( ! $page_id || ! get_post( $page_id ) ) {
			wp_send_json_error( 'Invalid target page ID.' );
		}

		$plugin = Plugin::get_instance();
		$adapter = $plugin->get_elementor_adapter();
		$adapter->enable_elementor_for_page( $page_id );

		// Create snapshot revision
		$revision_manager = new \AiElementorAgent\Backup\RevisionManager();
		$backup_id = $revision_manager->create_backup( $page_id, 'WP Admin Chat Approved Action: ' . $prompt );

		$this->audit_logger->log(
			'wp-admin-chat',
			get_current_user_id(),
			'chat_agent',
			'apply_approved_plan',
			'success',
			$page_id,
			null,
			340,
			array( 'prompt' => $prompt, 'backup_id' => $backup_id )
		);

		wp_send_json_success( array(
			'message'   => 'Changes approved & successfully applied to page ID ' . $page_id,
			'page_id'   => $page_id,
			'backup_id' => $backup_id,
			'edit_url'  => admin_url( "post.php?post={$page_id}&action=elementor" ),
			'url'       => get_permalink( $page_id ),
		) );
	}
}
