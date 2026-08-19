<?php
namespace AiElementorAgent\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiElementorAgent\Security\TokenManager;
use AiElementorAgent\Security\PermissionManager;
use AiElementorAgent\Security\RateLimiter;
use AiElementorAgent\Logging\AuditLogger;
use AiElementorAgent\Backup\RevisionManager;
use AiElementorAgent\Elementor\ElementorAdapter;
use AiElementorAgent\Elementor\GlobalStylesManager;
use AiElementorAgent\Agent\ContextManager;
use AiElementorAgent\Agent\AgentEngine;
use AiElementorAgent\Skills\SkillLoader;
use AiElementorAgent\Skills\SkillRegistry;
use AiElementorAgent\Animation\GsapLoader;
use AiElementorAgent\MCP\Server as MCPServer;
use AiElementorAgent\MCP\ToolRegistry;

/**
 * Singleton Plugin Container
 */
class Plugin {

	private static ?Plugin $instance = null;

	private TokenManager $token_manager;
	private PermissionManager $permission_manager;
	private RateLimiter $rate_limiter;
	private AuditLogger $audit_logger;
	private RevisionManager $revision_manager;
	private ElementorAdapter $elementor_adapter;
	private GlobalStylesManager $global_styles;
	private ContextManager $context_manager;
	private AgentEngine $agent_engine;
	private ToolRegistry $tool_registry;
	private SkillLoader $skill_loader;
	private SkillRegistry $skill_registry;
	private GsapLoader $gsap_loader;
	private MCPServer $mcp_server;
	private RestApi $rest_api;
	private Admin $admin;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->token_manager      = new TokenManager();
		$this->permission_manager  = new PermissionManager();
		$this->rate_limiter        = new RateLimiter();
		$this->audit_logger        = new AuditLogger();
		$this->revision_manager    = new RevisionManager();
		$this->elementor_adapter   = new ElementorAdapter();
		$this->global_styles       = new GlobalStylesManager();
		$this->context_manager     = new ContextManager( $this->elementor_adapter, $this->global_styles );
		$this->agent_engine        = new AgentEngine(
			$this->elementor_adapter,
			$this->context_manager,
			$this->revision_manager,
			$this->audit_logger,
			$this->permission_manager
		);

		$this->skill_loader        = new SkillLoader();
		$this->skill_registry      = new SkillRegistry( $this->skill_loader );
		$this->gsap_loader        = new GsapLoader();

		$this->tool_registry       = new ToolRegistry(
			$this->agent_engine,
			$this->context_manager,
			$this->token_manager,
			$this->global_styles
		);

		$this->mcp_server          = new MCPServer(
			$this->tool_registry,
			$this->token_manager,
			$this->permission_manager,
			$this->rate_limiter,
			$this->audit_logger
		);

		$this->rest_api            = new RestApi( $this->mcp_server );
		$this->admin               = new Admin( $this->token_manager, $this->audit_logger );
	}

	public function init(): void {
		$this->rest_api->register_routes();
		$this->admin->init();
		$this->gsap_loader->init();

		try {
			$ability_bridge = new \AiElementorAgent\Abilities\AbilityBridge( $this->tool_registry );
			$ability_bridge->register();
		} catch ( \Throwable $e ) {
			// Silent fallback if Abilities API is unavailable
		}
	}

	public function get_gsap_loader(): GsapLoader {
		return $this->gsap_loader;
	}

	public function get_skill_registry(): SkillRegistry {
		return $this->skill_registry;
	}

	public function get_token_manager(): TokenManager {
		return $this->token_manager;
	}

	public function get_permission_manager(): PermissionManager {
		return $this->permission_manager;
	}

	public function get_audit_logger(): AuditLogger {
		return $this->audit_logger;
	}

	public function get_elementor_adapter(): ElementorAdapter {
		return $this->elementor_adapter;
	}

	public function get_global_styles(): GlobalStylesManager {
		return $this->global_styles;
	}

	public function get_context_manager(): ContextManager {
		return $this->context_manager;
	}

	public function get_tool_registry(): ToolRegistry {
		return $this->tool_registry;
	}

	public function get_revision_manager(): RevisionManager {
		return $this->revision_manager;
	}

	public function get_agent_engine(): AgentEngine {
		return $this->agent_engine;
	}

	public function get_mcp_server(): MCPServer {
		return $this->mcp_server;
	}
}
