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

class WordPressGetSiteInfoTool extends AbstractTool {

	private ContextManager $context;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->context = $context;
	}

	public function get_name(): string {
		return 'wordpress_get_site_info';
	}

	public function get_description(): string {
		return 'Returns essential site metadata: WordPress version, PHP version, active theme, plugins, Elementor status, and site branding assets (logos, favicon, tagline).';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => new \stdClass(),
		);
	}

	public function execute( array $arguments, array $context ): array {
		return array(
			'success' => true,
			'data'    => $this->context->get_site_info(),
		);
	}
}
