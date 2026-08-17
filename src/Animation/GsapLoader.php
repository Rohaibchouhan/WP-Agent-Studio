<?php
namespace AiElementorAgent\Animation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles conditional loading and execution of GSAP (GreenSock Animation Platform) libraries.
 * Official Library URL: https://gsap.com/
 */
class GsapLoader {

	private const GSAP_CDN_URL = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js';
	private const SCROLLTRIGGER_CDN_URL = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js';
	private const OPTION_KEY = 'ai_elementor_agent_gsap_settings';

	public function init(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_gsap' ) );
	}

	/**
	 * Get global GSAP settings option array.
	 */
	public function get_settings(): array {
		return get_option( self::OPTION_KEY, array(
			'enabled'               => true,
			'enable_scrolltrigger'  => true,
			'respect_reduced_motion' => true,
		) );
	}

	/**
	 * Save GSAP settings option array.
	 */
	public function update_settings( array $settings ): bool {
		return update_option( self::OPTION_KEY, $settings );
	}

	/**
	 * Enable GSAP for a specific page via post meta.
	 */
	public function enable_gsap_for_page( int $page_id ): void {
		update_post_meta( $page_id, '_aiea_enable_gsap', '1' );
	}

	/**
	 * Disable GSAP for a specific page via post meta.
	 */
	public function disable_gsap_for_page( int $page_id ): void {
		delete_post_meta( $page_id, '_aiea_enable_gsap' );
	}

	/**
	 * Check if GSAP is enabled for a specific page.
	 */
	public function is_gsap_enabled_for_page( int $page_id ): bool {
		$global_settings = $this->get_settings();
		if ( empty( $global_settings['enabled'] ) ) {
			return false;
		}

		$page_meta = get_post_meta( $page_id, '_aiea_enable_gsap', true );
		return '1' === $page_meta;
	}

	/**
	 * Enqueue GSAP scripts on the frontend conditionally.
	 */
	public function enqueue_frontend_gsap(): void {
		if ( ! is_singular( 'page' ) ) {
			return;
		}

		$page_id = get_the_ID();
		if ( ! $this->is_gsap_enabled_for_page( $page_id ) ) {
			return;
		}

		$settings = $this->get_settings();

		// Enqueue GSAP Core (https://gsap.com/)
		wp_enqueue_script(
			'aiea-gsap-core',
			self::GSAP_CDN_URL,
			array(),
			'3.12.5',
			true
		);

		// Enqueue ScrollTrigger Plugin if enabled
		if ( ! empty( $settings['enable_scrolltrigger'] ) ) {
			wp_enqueue_script(
				'aiea-gsap-scrolltrigger',
				self::SCROLLTRIGGER_CDN_URL,
				array( 'aiea-gsap-core' ),
				'3.12.5',
				true
			);
		}

		// Inject initialization script after all GSAP libraries are loaded
		$inline_js = 'document.addEventListener("DOMContentLoaded", function() {
			if (typeof gsap !== "undefined") {
				if (typeof ScrollTrigger !== "undefined") {
					gsap.registerPlugin(ScrollTrigger);
				}
				// 1. Continuous Floating Motion for Hero Showcase Card
				gsap.to(".elementor-element-hero_right_2026 img", {
					y: 14,
					repeat: -1,
					yoyo: true,
					duration: 2.2,
					ease: "power1.inOut"
				});
				// 2. ScrollTrigger Staggered Reveal for Cards Grid & Inspection Grid
				var scrollItems = document.querySelectorAll(".elementor-element-cards_grid_2026 .e-child, .elementor-element-inspection_matrix_2026 .e-child");
				if (scrollItems.length > 0) {
					scrollItems.forEach(function(el, idx) {
						gsap.from(el, {
							scrollTrigger: {
								trigger: el,
								start: "top 85%"
							},
							opacity: 0,
							y: 40,
							duration: 0.8,
							delay: (idx % 4) * 0.15,
							ease: "power2.out"
						});
					});
				}
			}
		});';

		wp_add_inline_script( $handle, $inline_js, 'after' );
	}
}
