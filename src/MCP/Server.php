<?php
namespace AiElementorAgent\MCP;

if (!defined('ABSPATH')) {
	exit;
}

use AiElementorAgent\Logging\AuditLogger;
use AiElementorAgent\Security\PermissionManager;
use AiElementorAgent\Security\RateLimiter;
use AiElementorAgent\Security\TokenManager;

/**
 * MCP JSON-RPC 2.0 Protocol Handler & Transport Server.
 */
class Server
{
	private ToolRegistry $registry;
	private TokenManager $token_manager;
	private PermissionManager $permission_manager;
	private RateLimiter $rate_limiter;
	private AuditLogger $audit_logger;

	public function __construct(
		ToolRegistry $registry,
		TokenManager $token_manager,
		PermissionManager $permission_manager,
		RateLimiter $rate_limiter,
		AuditLogger $audit_logger
	) {
		$this->registry = $registry;
		$this->token_manager = $token_manager;
		$this->permission_manager = $permission_manager;
		$this->rate_limiter = $rate_limiter;
		$this->audit_logger = $audit_logger;
	}

	/**
	 * Process incoming MCP JSON-RPC 2.0 payload.
	 *
	 * @param array  $payload Decoded JSON-RPC request.
	 * @param string $auth_header HTTP Authorization Bearer token header.
	 * @return array JSON-RPC 2.0 response array.
	 */
	public function handle_request(array $payload, string $auth_header): array
	{
		$id = $payload['id'] ?? null;
		$method = $payload['method'] ?? '';
		$params = $payload['params'] ?? array();

		// 1. Multi-Method Authentication Handler
		$token_data = false;

		if ( empty( $auth_header ) ) {
			$auth_header = \AiElementorAgent\Security\AuthExtractor::extract();
		}

		// Method A: Plugin Bearer Token
		if ( 0 === strpos( $auth_header, 'Bearer ' ) ) {
			$token_str = trim( substr( $auth_header, 7 ) );
			$token_data = $this->token_manager->authenticate_token( $token_str );
		}

		// Method B: Basic Auth / WordPress Application Passwords
		if (!$token_data) {
			$basic_auth_str = '';
			if (0 === strpos($auth_header, 'Basic ')) {
				$basic_auth_str = trim(substr($auth_header, 6));
			} elseif (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
				$basic_auth_str = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
			}

			if (!empty($basic_auth_str)) {
				$decoded = base64_decode($basic_auth_str);
				if (false !== strpos($decoded, ':')) {
					list($username, $password) = explode(':', $decoded, 2);
					$user = wp_authenticate($username, $password);
					if (!is_wp_error($user) && $user && $user->exists() && user_can($user->ID, 'edit_pages')) {
						$token_data = array(
							'id' => 'app_pass_' . $user->ID,
							'label' => 'WP Application Password (' . $user->user_login . ')',
							'user_id' => $user->ID,
							'created_at' => current_time('mysql'),
							'last_used' => current_time('mysql'),
							'masked' => 'AppPassword',
						);
					}
				}
			}
		}

		// Method C: Logged-in WordPress Cookie Session
		if (!$token_data && is_user_logged_in()) {
			$user_id = get_current_user_id();
			$user = get_userdata($user_id);
			if ($user && user_can($user_id, 'edit_pages')) {
				$token_data = array(
					'id' => 'wp_session_' . $user_id,
					'label' => 'WP Logged-in Session (' . $user->user_login . ')',
					'user_id' => $user_id,
					'created_at' => current_time('mysql'),
					'last_used' => current_time('mysql'),
					'masked' => 'SessionCookie',
				);
			}
		}

		if (!$token_data) {
			return $this->rpc_error($id, -32001, 'Unauthorized: Invalid authentication credentials provided.');
		}

		// 2. Rate Limiting Check — handshake methods (initialize, tools/list) are exempt
		if (!$this->rate_limiter->is_allowed($token_data['id'], $method)) {
			$status = $this->rate_limiter->get_status($token_data['id']);
			return $this->rpc_error($id, -32002,
				sprintf(
					'Rate Limit Exceeded: %d/%d requests used. Resets in %d seconds.',
					$status['count'],
					$status['max'],
					$status['resets_in_seconds']
				));
		}

		$user_id = $token_data['user_id'];
		wp_set_current_user($user_id);

		$context = array(
			'token_id' => $token_data['id'],
			'client_id' => $token_data['label'],
			'user_id' => $user_id,
		);

		// 3. Dispatch Methods
		switch ($method) {
			case 'initialize':
				$requested_version = $params['protocolVersion'] ?? '2024-11-05';
				$supported_versions = array('2024-11-05', '2024-10-07', '1.0.0');
				$negotiated_version = in_array($requested_version, $supported_versions, true) ? $requested_version : '2024-11-05';

				return $this->rpc_success($id, array(
					'protocolVersion' => $negotiated_version,
					'capabilities' => array(
						'tools' => array('listChanged' => true),
						'resources' => array('subscribe' => false, 'listChanged' => false),
					),
					'serverInfo' => array(
						'name' => 'WP Agent Studio Server',
						'version' => AI_ELEMENTOR_AGENT_VERSION,
					),
				));

			case 'notifications/initialized':
				return array('jsonrpc' => '2.0');

			case 'tools/list':
				return $this->rpc_success($id, array(
					'tools' => $this->registry->list_tools(),
				));

			case 'tools/call':
				$name = $params['name'] ?? '';
				$arguments = $params['arguments'] ?? array();

				$tool = $this->registry->get_tool($name);
				if (!$tool) {
					$this->audit_logger->log(
						$context['client_id'] ?? 'mcp-client',
						$context['user_id'] ?? 0,
						$name ?: 'unknown_tool',
						'execute_tool',
						'error',
						null,
						null,
						0,
						$arguments,
						sprintf('Method/Tool "%s" not found.', $name)
					);
					return $this->rpc_error($id, -32601, sprintf('Method/Tool "%s" not found.', $name));
				}

				$tool_start_time = microtime(true);
				try {
					$result = $tool->execute($arguments, $context);
					$duration = (int) ((microtime(true) - $tool_start_time) * 1000);

					$page_id = isset($arguments['page_id']) ? (int) $arguments['page_id'] : null;
					$element_id = isset($arguments['element_id']) ? (string) $arguments['element_id'] : null;
					$is_error = isset($result['success']) && !$result['success'];
					$status_str = $is_error ? 'error' : 'success';
					$error_str = $is_error ? (is_array($result['error'] ?? null) ? wp_json_encode($result['error']) : (string)($result['error'] ?? 'Execution failed')) : null;

					$this->audit_logger->log(
						$context['client_id'] ?? 'mcp-client',
						$context['user_id'] ?? 0,
						$name,
						'execute_tool',
						$status_str,
						$page_id,
						$element_id,
						$duration,
						$arguments,
						$error_str
					);

					// Format response as standard MCP Tool result
					return $this->rpc_success($id, array(
						'content' => array(
							array(
								'type' => 'text',
								'text' => wp_json_encode($result, JSON_PRETTY_PRINT),
							),
						),
						'isError' => $is_error,
					));
				} catch (\Throwable $e) {
					$duration = (int) ((microtime(true) - $tool_start_time) * 1000);
					$page_id = isset($arguments['page_id']) ? (int) $arguments['page_id'] : null;
					$element_id = isset($arguments['element_id']) ? (string) $arguments['element_id'] : null;

					$this->audit_logger->log(
						$context['client_id'] ?? 'mcp-client',
						$context['user_id'] ?? 0,
						$name,
						'execute_tool',
						'error',
						$page_id,
						$element_id,
						$duration,
						$arguments,
						'Internal Tool Error: ' . $e->getMessage()
					);

					return $this->rpc_error($id, -32603, 'Internal Tool Error: ' . $e->getMessage());
				}

			case 'resources/list':
				return $this->rpc_success($id, array(
					'resources' => array(
						array('uri' => 'site://info', 'name' => 'Site Overview', 'mimeType' => 'application/json'),
						array('uri' => 'site://pages', 'name' => 'Site Pages List', 'mimeType' => 'application/json'),
						array('uri' => 'site://elementor/global-styles', 'name' => 'Global Design System', 'mimeType' => 'application/json'),
					),
				));

			case 'resources/read':
				$uri = $params['uri'] ?? '';
				$res_data = $this->read_resource($uri);
				if (null === $res_data) {
					return $this->rpc_error($id, -32602, sprintf('Resource URI "%s" not found.', $uri));
				}
				return $this->rpc_success($id, array(
					'contents' => array(
						array(
							'uri' => $uri,
							'mimeType' => 'application/json',
							'text' => wp_json_encode($res_data, JSON_PRETTY_PRINT),
						),
					),
				));

			default:
				return $this->rpc_error($id, -32601, sprintf('Unknown method "%s".', $method));
		}
	}

	private function read_resource(string $uri)
	{
		switch ($uri) {
			case 'site://info':
				$context_mgr = new \AiElementorAgent\Agent\ContextManager(
					\AiElementorAgent\Core\Plugin::get_instance()->get_elementor_adapter(),
					\AiElementorAgent\Core\Plugin::get_instance()->get_global_styles()
				);
				return $context_mgr->get_site_info();
			case 'site://pages':
				$context_mgr = new \AiElementorAgent\Agent\ContextManager(
					\AiElementorAgent\Core\Plugin::get_instance()->get_elementor_adapter(),
					\AiElementorAgent\Core\Plugin::get_instance()->get_global_styles()
				);
				return $context_mgr->get_pages();
			case 'site://elementor/global-styles':
				return \AiElementorAgent\Core\Plugin::get_instance()->get_global_styles()->get_global_colors();
			default:
				return null;
		}
	}

	private function rpc_success($id, array $result): array
	{
		return array(
			'jsonrpc' => '2.0',
			'id' => $id,
			'result' => $result,
		);
	}

	private function rpc_error($id, int $code, string $message): array
	{
		return array(
			'jsonrpc' => '2.0',
			'id' => $id,
			'error' => array(
				'code' => $code,
				'message' => $message,
			),
		);
	}
}
