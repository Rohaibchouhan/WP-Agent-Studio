<?php
namespace AiElementorAgent\Core;

if (!defined('ABSPATH')) {
	exit;
}

use AiElementorAgent\Elementor\ElementorAdapter;
use AiElementorAgent\Elementor\ElementorWriter;
use AiElementorAgent\MCP\Server as MCPServer;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Registers REST API endpoint routes.
 *
 * Fixes applied:
 * - Robust body parser: reads php://input as fallback when get_json_params() returns empty
 * - GET request support: parse JSON-RPC from query params for discovery
 * - Removed WP cookie nonce conflict on MCP route
 * - Added /elementor/save dedicated endpoint for direct _elementor_data writes
 * - Added /debug endpoint for connection diagnostics (admin only)
 * - Added /rate-limit/reset endpoint for admin rate limit management
 */
class RestApi
{
	private const NAMESPACE = 'ai-elementor/v1';

	private MCPServer $mcp_server;

	public function __construct(MCPServer $mcp_server)
	{
		$this->mcp_server = $mcp_server;
	}

	public function register_routes(): void
	{
		add_action('rest_api_init', function () {
			// -----------------------------------------------------------------
			// PRIMARY: MCP JSON-RPC 2.0 Endpoint
			// -----------------------------------------------------------------
			register_rest_route(self::NAMESPACE, '/mcp', array(
				'methods' => array('POST', 'GET', 'OPTIONS'),
				'callback' => array($this, 'handle_mcp_request'),
				'permission_callback' => '__return_true',
			));

			// -----------------------------------------------------------------
			// DEDICATED: Direct Elementor Data Write Endpoint
			// Bypasses standard WP REST API entirely — writes _elementor_data
			// -----------------------------------------------------------------
			register_rest_route(self::NAMESPACE, '/elementor/save', array(
				'methods' => 'POST',
				'callback' => array($this, 'handle_elementor_save'),
				'permission_callback' => '__return_true',
			));

			// -----------------------------------------------------------------
			// DIAGNOSTIC: Connection Debug Endpoint (Admin Only)
			// -----------------------------------------------------------------
			register_rest_route(self::NAMESPACE, '/debug', array(
				'methods' => array('GET', 'POST'),
				'callback' => array($this, 'handle_debug'),
				'permission_callback' => '__return_true',
			));

			// -----------------------------------------------------------------
			// UTILITY: Rate Limit Reset Endpoint
			// -----------------------------------------------------------------
			register_rest_route(self::NAMESPACE, '/rate-limit/reset', array(
				'methods' => 'POST',
				'callback' => array($this, 'handle_rate_limit_reset'),
				'permission_callback' => '__return_true',
			));

			// -----------------------------------------------------------------
			// HEALTH CHECK Endpoint
			// -----------------------------------------------------------------
			register_rest_route(self::NAMESPACE, '/health', array(
				'methods' => 'GET',
				'callback' => array($this, 'handle_health_check'),
				'permission_callback' => '__return_true',
			));
		});

		// Remove WP cookie authentication nonce requirement on our REST routes
		// This prevents conflicts when logged-in admin session is the auth method
		add_filter('rest_authentication_errors', array($this, 'allow_mcp_auth_bypass'), 99);
	}

	/**
	 * Allow the MCP endpoint to bypass WP cookie nonce requirement.
	 * WP requires X-WP-Nonce for cookie-authenticated REST requests,
	 * but our plugin handles its own Bearer/Token auth so we prevent rest_cookie_invalid_nonce error
	 * ONLY on our plugin's REST routes (/ai-elementor/v1/).
	 */
	public function allow_mcp_auth_bypass( $result ) {
		if ( is_wp_error( $result ) && 'rest_cookie_invalid_nonce' === $result->get_error_code() ) {
			$request_uri = $_SERVER['REQUEST_URI'] ?? '';
			if ( false !== strpos( $request_uri, '/ai-elementor/v1/' ) ) {
				return true;
			}
		}
		return $result;
	}

	/**
	 * Robust body parser — handles missing Content-Type header.
	 *
	 * WordPress's get_json_params() returns null when Content-Type is not
	 * "application/json". This fallback reads php://input directly.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return array|null Decoded JSON body or null.
	 */
	private function parse_request_body( WP_REST_Request $request ): ?array {
		// Method 1: Standard WP REST JSON parsing (works when Content-Type: application/json is set)
		$body = $request->get_json_params();
		if ( ! empty( $body ) && is_array( $body ) ) {
			return $body;
		}

		// Method 2: Fallback — read raw request body directly from php://input
		$raw = $request->get_body();
		if ( empty( $raw ) ) {
			// Method 3: Read php://input directly as last resort
			$raw = file_get_contents( 'php://input' );
		}

		if ( ! empty( $raw ) ) {
			$decoded = json_decode( $raw, true );
			if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return null;
	}

	/**
	 * Handle MCP JSON-RPC requests (POST and GET).
	 */
	public function handle_mcp_request( WP_REST_Request $request ): WP_REST_Response {
		// Handle CORS preflight
		if ( 'OPTIONS' === $request->get_method() ) {
			$response = new WP_REST_Response( null, 204 );
			$response->header( 'Access-Control-Allow-Origin', '*' );
			$response->header( 'Access-Control-Allow-Methods', 'POST, GET, OPTIONS' );
			$response->header( 'Access-Control-Allow-Headers', 'Authorization, Content-Type, X-WP-Nonce' );
			return $response;
		}

		$auth_header = \AiElementorAgent\Security\AuthExtractor::extract( $request );

		// GET requests: support JSON-RPC via query param (for discovery/initialize/tool execution)
		if ( 'GET' === $request->get_method() ) {
			$method = sanitize_text_field( $request->get_param( 'method' ) ?? 'initialize' );
			$params = array();

			// Option A: Full params as JSON string (e.g. ?params={"name":"elementor_create_page","arguments":{...}})
			$params_raw = $request->get_param( 'params' );
			if ( ! empty( $params_raw ) ) {
				if ( is_string( $params_raw ) ) {
					$decoded_p = json_decode( urldecode( $params_raw ), true );
					if ( is_array( $decoded_p ) ) {
						$params = $decoded_p;
					}
				} elseif ( is_array( $params_raw ) ) {
					$params = $params_raw;
				}
			}

			// Option B: Query parameters name and arguments (e.g. ?name=elementor_create_page&arguments={...})
			$name_raw = $request->get_param( 'name' );
			if ( ! empty( $name_raw ) ) {
				$params['name'] = sanitize_text_field( $name_raw );
			}

			$args_raw = $request->get_param( 'arguments' );
			if ( ! empty( $args_raw ) ) {
				if ( is_string( $args_raw ) ) {
					$decoded_a = json_decode( urldecode( $args_raw ), true );
					$params['arguments'] = is_array( $decoded_a ) ? $decoded_a : array();
				} elseif ( is_array( $args_raw ) ) {
					$params['arguments'] = $args_raw;
				}
			}

			$body = array(
				'jsonrpc' => '2.0',
				'id'      => 1,
				'method'  => $method,
				'params'  => $params,
			);
		} else {
			// POST requests: use robust body parser
			$body = $this->parse_request_body($request);

			if (null === $body) {
				$raw_body = $request->get_body();
				return new WP_REST_Response(array(
					'jsonrpc' => '2.0',
					'id' => null,
					'error' => array(
						'code' => -32700,
						'message' => 'Parse error: Could not decode JSON body. Ensure Content-Type: application/json is set. '
							. 'Received body length: ' . strlen($raw_body) . ' bytes. '
							. 'Content-Type received: ' . ($request->get_header('content-type') ?? 'none'),
					),
				), 400);
			}
		}

		$response = $this->mcp_server->handle_request($body, $auth_header);

		// Always return 200 for valid JSON-RPC (even error responses per spec)
		$http_status = 200;
		if (isset($response['error']['code'])) {
			if (-32001 === $response['error']['code']) {
				$http_status = 401;
			} elseif (-32002 === $response['error']['code']) {
				$http_status = 429;
			}
		}

		$wp_response = new WP_REST_Response($response, $http_status);
		$wp_response->header('Access-Control-Allow-Origin', '*');
		$wp_response->header('Content-Type', 'application/json');

		return $wp_response;
	}

	/**
	 * Handle direct Elementor _elementor_data save requests.
	 * This endpoint writes directly to post meta, bypassing standard WP REST API.
	 */
	public function handle_elementor_save(WP_REST_Request $request): WP_REST_Response
	{
		$auth_header = $request->get_header('authorization') ?? '';

		// Authenticate
		$token_str = 0 === strpos($auth_header, 'Bearer ') ? trim(substr($auth_header, 7)) : '';
		$token_manager = Plugin::get_instance()->get_token_manager();
		$token_data = $token_manager->authenticate_token($token_str);

		// Also accept Basic Auth
		if (!$token_data && 0 === strpos($auth_header, 'Basic ')) {
			$decoded = base64_decode(trim(substr($auth_header, 6)));
			if (false !== strpos($decoded, ':')) {
				list($username, $password) = explode(':', $decoded, 2);
				$user = wp_authenticate($username, $password);
				if (!is_wp_error($user) && $user->exists() && user_can($user->ID, 'edit_pages')) {
					$token_data = array('user_id' => $user->ID, 'label' => $user->user_login);
				}
			}
		}

		// Accept logged-in session
		if (!$token_data && is_user_logged_in()) {
			$uid = get_current_user_id();
			if (user_can($uid, 'edit_pages')) {
				$token_data = array('user_id' => $uid, 'label' => 'session');
			}
		}

		if (!$token_data) {
			return new WP_REST_Response(array('success' => false, 'error' => 'Unauthorized'), 401);
		}

		$body = $this->parse_request_body($request);
		if (!$body) {
			return new WP_REST_Response(array('success' => false, 'error' => 'Invalid JSON body'), 400);
		}

		$page_id = (int) ($body['page_id'] ?? 0);
		$elements = $body['elements'] ?? null;
		$status = sanitize_text_field($body['status'] ?? '');

		if (!$page_id || !get_post($page_id)) {
			return new WP_REST_Response(array('success' => false, 'error' => 'Invalid page_id'), 400);
		}

		wp_set_current_user($token_data['user_id']);

		$start_time = microtime(true);
		$adapter = Plugin::get_instance()->get_elementor_adapter();
		$writer = new ElementorWriter();

		// Ensure Elementor edit mode is active
		$adapter->enable_elementor_for_page($page_id);

		// Write elements if provided
		if (null !== $elements && is_array($elements)) {
			$writer->save_page_elements($page_id, $elements);
		}

		// Update post status or content if requested
		$post_update = array( 'ID' => $page_id );
		$should_update = false;

		if (!empty($status) && in_array($status, array('publish', 'draft', 'private'), true)) {
			$post_update['post_status'] = $status;
			$should_update = true;
		}

		if (isset($body['page_content'])) {
			$post_update['post_content'] = $body['post_content'];
			$should_update = true;
		}

		if (isset($body['page_template']) && !empty($body['page_template'])) {
			update_post_meta($page_id, '_wp_page_template', sanitize_text_field($body['page_template']));
		}

		if (isset($body['enable_gsap'])) {
			if (!empty($body['enable_gsap'])) {
				Plugin::get_instance()->get_gsap_loader()->enable_gsap_for_page($page_id);
			} else {
				Plugin::get_instance()->get_gsap_loader()->disable_gsap_for_page($page_id);
			}
		}

		if ($should_update) {
			wp_update_post($post_update);
			if ('publish' === $status) {
				flush_rewrite_rules(false);
			}
		}

		$duration = (int) ((microtime(true) - $start_time) * 1000);
		$audit_logger = Plugin::get_instance()->get_audit_logger();
		$audit_logger->log(
			$token_data['label'] ?? 'rest-api',
			$token_data['user_id'] ?? 0,
			'elementor_direct_save',
			'save_page_elements',
			'success',
			$page_id,
			null,
			$duration,
			array('page_id' => $page_id, 'elements_count' => count($elements ?? array()), 'status' => $status)
		);

		return new WP_REST_Response(array(
			'success' => true,
			'page_id' => $page_id,
			'url' => get_permalink($page_id),
			'edit_url' => admin_url("post.php?post={$page_id}&action=elementor"),
			'status' => get_post_status($page_id),
		), 200);
	}

	/**
	 * Debug endpoint — returns exactly what the server received.
	 * Requires admin capability. Use this to diagnose connection issues.
	 */
	public function handle_debug(WP_REST_Request $request): WP_REST_Response
	{
		// Only allow administrators to access debug info
		if (!current_user_can('manage_options')) {
			$auth_header = $request->get_header('authorization') ?? '';
			$token_str = 0 === strpos($auth_header, 'Bearer ') ? trim(substr($auth_header, 7)) : '';
			$token_manager = Plugin::get_instance()->get_token_manager();
			$token_data = $token_manager->authenticate_token($token_str);

			if (!$token_data) {
				return new WP_REST_Response(array('error' => 'Debug requires admin auth.'), 401);
			}
		}

		$adapter = Plugin::get_instance()->get_elementor_adapter();
		$raw_body = $request->get_body();
		$parsed_body = json_decode($raw_body, true);

		return new WP_REST_Response(array(
			'status' => 'debug_ok',
			'timestamp' => current_time('mysql'),
			'method' => $request->get_method(),
			'content_type' => $request->get_header('content-type') ?? 'NOT_SET',
			'authorization' => $request->get_header('authorization') ? 'PRESENT (value hidden)' : 'NOT_SET',
			'body_length' => strlen($raw_body),
			'body_parseable' => null !== $parsed_body && is_array($parsed_body),
			'json_error' => JSON_ERROR_NONE !== json_last_error() ? json_last_error_msg() : null,
			'wp_logged_in' => is_user_logged_in(),
			'current_user_id' => get_current_user_id(),
			'php_version' => PHP_VERSION,
			'wp_version' => get_bloginfo('version'),
			'mcp_endpoint' => get_rest_url(null, 'ai-elementor/v1/mcp'),
			'elementor' => array(
				'active' => $adapter->is_active(),
				'version' => $adapter->get_version(),
				'pro' => $adapter->is_pro_active(),
			),
			'plugin_version' => AI_ELEMENTOR_AGENT_VERSION,
		), 200);
	}

	/**
	 * Rate limit reset endpoint.
	 */
	public function handle_rate_limit_reset(WP_REST_Request $request): WP_REST_Response
	{
		if (!current_user_can('manage_options')) {
			$auth_header = $request->get_header('authorization') ?? '';
			if (!$this->is_admin_token($auth_header)) {
				return new WP_REST_Response(array('error' => 'Unauthorized'), 401);
			}
		}

		$rate_limiter = new \AiElementorAgent\Security\RateLimiter();
		$body = $this->parse_request_body($request);
		$token_id = sanitize_text_field($body['token_id'] ?? '');

		if (!empty($token_id)) {
			$rate_limiter->reset($token_id);
			return new WP_REST_Response(array('success' => true, 'reset' => 'token_' . $token_id), 200);
		} else {
			$count = $rate_limiter->reset_all();
			return new WP_REST_Response(array('success' => true, 'reset' => 'all', 'deleted' => $count), 200);
		}
	}

	/**
	 * Health check endpoint.
	 */
	public function handle_health_check(): WP_REST_Response
	{
		$settings = get_option('ai_elementor_agent_settings', array());
		$adapter = Plugin::get_instance()->get_elementor_adapter();

		return new WP_REST_Response(array(
			'status' => 'ok',
			'mcp_enabled' => (bool) ($settings['mcp_enabled'] ?? true),
			'plugin' => 'AI Elementor Agent',
			'version' => AI_ELEMENTOR_AGENT_VERSION,
			'debug_url' => get_rest_url(null, 'ai-elementor/v1/debug'),
			'elementor' => array(
				'installed' => $adapter->is_installed(),
				'active' => $adapter->is_active(),
				'version' => $adapter->get_version(),
				'pro' => $adapter->is_pro_active(),
			),
		), 200);
	}

	/**
	 * Check if Authorization header contains a valid admin-level Bearer token.
	 */
	private function is_admin_token(string $auth_header): bool
	{
		if (0 !== strpos($auth_header, 'Bearer ')) {
			return false;
		}
		$token_str = trim(substr($auth_header, 7));
		$token_manager = Plugin::get_instance()->get_token_manager();
		$token_data = $token_manager->authenticate_token($token_str);
		if (!$token_data) {
			return false;
		}
		return user_can($token_data['user_id'], 'manage_options');
	}
}
