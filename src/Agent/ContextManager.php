<?php
namespace AiElementorAgent\Agent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiElementorAgent\Elementor\ElementorAdapter;
use AiElementorAgent\Elementor\GlobalStylesManager;
use AiElementorAgent\Elementor\WidgetAllowlist;
use AiElementorAgent\Elementor\ElementorReader;

/**
 * Builds concise context information frames for AI agent consumption.
 */
class ContextManager {

	private ElementorAdapter $adapter;
	private GlobalStylesManager $global_styles;
	private ElementorReader $reader;

	public function __construct( ElementorAdapter $adapter, GlobalStylesManager $global_styles ) {
		$this->adapter       = $adapter;
		$this->global_styles = $global_styles;
		$this->reader        = new ElementorReader();
	}

	/**
	 * Get site technical context summary.
	 */
	public function get_site_info(): array {
		$theme = wp_get_theme();
		$active_plugins = get_option( 'active_plugins', array() );

		// Custom logo from WP Theme Mod
		$custom_logo_id  = (int) get_theme_mod( 'custom_logo' );
		$custom_logo_url = $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'full' ) : '';

		// Site Icon / Favicon
		$site_icon_url = function_exists( 'get_site_icon_url' ) ? get_site_icon_url() : '';

		// Elementor Kit Site Logo
		$kit_settings = $this->global_styles->get_kit_settings();
		$elementor_site_logo = array();
		if ( ! empty( $kit_settings['site_logo'] ) && is_array( $kit_settings['site_logo'] ) ) {
			$logo_setting = $kit_settings['site_logo'];
			$elementor_site_logo = array(
				'id'  => isset( $logo_setting['id'] ) ? (int) $logo_setting['id'] : 0,
				'url' => isset( $logo_setting['url'] ) ? esc_url_raw( $logo_setting['url'] ) : '',
			);
		}

		return array(
			'site_name'         => get_bloginfo( 'name' ),
			'site_description'  => get_bloginfo( 'description' ),
			'site_url'          => get_site_url(),
			'wordpress_version' => get_bloginfo( 'version' ),
			'php_version'       => PHP_VERSION,
			'branding'          => array(
				'custom_logo' => array(
					'id'  => $custom_logo_id,
					'url' => $custom_logo_url ? $custom_logo_url : '',
				),
				'site_icon_url'       => $site_icon_url ? $site_icon_url : '',
				'elementor_site_logo' => $elementor_site_logo,
			),
			'active_theme'      => array(
				'name'    => $theme->get( 'Name' ),
				'version' => $theme->get( 'Version' ),
			),
			'elementor'         => array(
				'installed'  => $this->adapter->is_installed(),
				'active'     => $this->adapter->is_active(),
				'version'    => $this->adapter->get_version(),
				'pro_active' => $this->adapter->is_pro_active(),
				'pro_version'=> $this->adapter->get_pro_version(),
			),
			'language'          => get_locale(),
			'timezone'          => wp_timezone_string(),
			'active_plugins'    => count( $active_plugins ),
		);
	}

	/**
	 * Get pages list with Elementor metadata.
	 */
	public function get_pages( string $search = '', int $limit = 50 ): array {
		$args = array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
			'posts_per_page' => min( $limit, 100 ),
			's'              => $search,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		);

		$query = new \WP_Query( $args );
		$pages = array();

		foreach ( $query->posts as $post ) {
			$is_elementor = $this->adapter->is_elementor_page( $post->ID );
			$pages[] = array(
				'page_id'          => $post->ID,
				'title'            => $post->post_title,
				'slug'             => $post->post_name,
				'status'           => $post->post_status,
				'url'              => get_permalink( $post->ID ),
				'is_elementor'     => $is_elementor,
				'modified'         => $post->post_modified,
			);
		}

		return $pages;
	}

	/**
	 * Get design system global tokens context.
	 */
	public function get_design_system(): array {
		return array(
			'colors' => $this->global_styles->get_global_colors(),
			'fonts'  => $this->global_styles->get_global_fonts(),
		);
	}

	/**
	 * Get page layout context tree.
	 */
	public function get_page_context( int $page_id, bool $verbose = false ): array {
		return $this->reader->get_page_structure( $page_id, $verbose );
	}

	/**
	 * Get list of available widgets.
	 */
	public function get_available_widgets(): array {
		$extractor = new \AiElementorAgent\Elementor\WidgetSchemaExtractor();
		return $extractor->get_registered_widgets();
	}
}
