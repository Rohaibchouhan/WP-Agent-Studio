<?php
namespace AiElementorAgent\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiElementorAgent\Agent\AgentEngine;
use AiElementorAgent\Agent\ContextManager;
use AiElementorAgent\Security\TokenManager;
use AiElementorAgent\Elementor\GlobalStylesManager;

/**
 * Registry maintaining available MCP tools.
 */
class ToolRegistry {

	/**
	 * @var AbstractTool[]
	 */
	private array $tools = array();

	public function __construct(
		AgentEngine $engine,
		ContextManager $context_manager,
		TokenManager $token_manager,
		GlobalStylesManager $global_styles
	) {
		// Register all MCP tools
		$this->register_default_tools( $engine, $context_manager, $token_manager, $global_styles );
	}

	public function register_tool( AbstractTool $tool ): void {
		$this->tools[ $tool->get_name() ] = $tool;
	}

	public function get_tool( string $name ): ?AbstractTool {
		return $this->tools[ $name ] ?? null;
	}

	/**
	 * Get all registered tool objects.
	 *
	 * @return AbstractTool[]
	 */
	public function get_all_tools(): array {
		return array_values( $this->tools );
	}

	public function list_tools(): array {
		$list = array();
		foreach ( $this->tools as $tool ) {
			$list[] = array(
				'name'        => $tool->get_name(),
				'description' => $tool->get_description(),
				'inputSchema' => $tool->get_schema(),
			);
		}
		return $list;
	}

	private function register_default_tools(
		AgentEngine $engine,
		ContextManager $context_manager,
		TokenManager $token_manager,
		GlobalStylesManager $global_styles
	): void {
		// Dynamic instantiation of tools in Tools directory
		$tool_classes = array(
			\AiElementorAgent\MCP\Tools\WordPressGetSiteInfoTool::class,
			\AiElementorAgent\MCP\Tools\WordPressGetPagesTool::class,
			\AiElementorAgent\MCP\Tools\WordPressGetMediaTool::class,
			\AiElementorAgent\MCP\Tools\WordPressUploadMediaTool::class,
			\AiElementorAgent\MCP\Tools\ElementorGetPageTool::class,
			\AiElementorAgent\MCP\Tools\ElementorCreatePageTool::class,
			\AiElementorAgent\MCP\Tools\ElementorAddContainerTool::class,
			\AiElementorAgent\MCP\Tools\ElementorAddHeadingTool::class,
			\AiElementorAgent\MCP\Tools\ElementorAddTextTool::class,
			\AiElementorAgent\MCP\Tools\ElementorAddButtonTool::class,
			\AiElementorAgent\MCP\Tools\ElementorAddImageTool::class,
			\AiElementorAgent\MCP\Tools\ElementorAddElementTool::class,
			\AiElementorAgent\MCP\Tools\ElementorUpdateElementTool::class,
			\AiElementorAgent\MCP\Tools\ElementorDeleteElementTool::class,
			\AiElementorAgent\MCP\Tools\ElementorMoveElementTool::class,
			\AiElementorAgent\MCP\Tools\ElementorDuplicateElementTool::class,
			\AiElementorAgent\MCP\Tools\ElementorGetGlobalColorsTool::class,
			\AiElementorAgent\MCP\Tools\ElementorSetGlobalColorTool::class,
			\AiElementorAgent\MCP\Tools\ElementorGetGlobalFontsTool::class,
			\AiElementorAgent\MCP\Tools\ElementorSetGlobalFontTool::class,
			\AiElementorAgent\MCP\Tools\ElementorCreateBackupTool::class,
			\AiElementorAgent\MCP\Tools\ElementorRestoreBackupTool::class,
			\AiElementorAgent\MCP\Tools\ElementorValidatePageTool::class,
			\AiElementorAgent\MCP\Tools\ElementorGetWidgetSchemaTool::class,
			\AiElementorAgent\MCP\Tools\AgentBuildPageTool::class,
			\AiElementorAgent\MCP\Tools\WordPressInspectSiteTool::class,
			\AiElementorAgent\MCP\Tools\WordPressManagePluginsTool::class,
			\AiElementorAgent\MCP\Tools\WordPressDirectPostTool::class,
			\AiElementorAgent\MCP\Tools\ElementorAtomicElementTool::class,
			\AiElementorAgent\MCP\Tools\ElementorCompositePageBuilderTool::class,
			\AiElementorAgent\MCP\Tools\ElementorCustomCodeTool::class,
			\AiElementorAgent\MCP\Tools\StockImageSideloadTool::class,
			\AiElementorAgent\MCP\Tools\WooCommerceProductTool::class,
			\AiElementorAgent\MCP\Tools\WooCommerceStoreLayoutTool::class,
			\AiElementorAgent\MCP\Tools\ACFDynamicContentTool::class,
			\AiElementorAgent\MCP\Tools\FormsBuilderTool::class,
			\AiElementorAgent\MCP\Tools\SEOOptimizationTool::class,
		);

		foreach ( $tool_classes as $class_name ) {
			if ( class_exists( $class_name ) ) {
				try {
					$this->register_tool( new $class_name( $engine, $context_manager, $token_manager, $global_styles ) );
				} catch ( \Throwable $e ) {
					$this->register_tool( new $class_name() );
				}
			}
		}
	}
}
