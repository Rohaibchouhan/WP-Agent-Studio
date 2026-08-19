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

class ElementorGetGlobalFontsTool extends AbstractTool {

	private GlobalStylesManager $global_styles;

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {
		$this->global_styles = $global_styles;
	}

	public function get_name(): string {
		return 'elementor_get_global_fonts';
	}

	public function get_description(): string {
		return 'Lists global typography font tokens (primary, secondary, text, accent).';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => new \stdClass(),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		return array(
			'success' => true,
			'fonts'   => $this->global_styles->get_global_fonts(),
		);
	}
}
