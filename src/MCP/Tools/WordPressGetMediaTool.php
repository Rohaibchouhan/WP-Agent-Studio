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

class WordPressGetMediaTool extends AbstractTool {

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {}

	public function get_name(): string {
		return 'wordpress_get_media';
	}

	public function get_description(): string {
		return 'Queries media library attachment items safely.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'search' => array( 'type' => 'string', 'description' => 'Search keyword for media title or filename.' ),
				'limit'  => array( 'type' => 'integer', 'description' => 'Limit items returned (default 50).' ),
			),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		$search = sanitize_text_field( $arguments['search'] ?? '' );
		$limit = min( (int) ( $arguments['limit'] ?? 50 ), 100 );

		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => $limit,
			's'              => $search,
		);

		$query = new \WP_Query( $args );
		$items = array();

		foreach ( $query->posts as $post ) {
			$url = wp_get_attachment_url( $post->ID );
			$mime = get_post_mime_type( $post->ID );
			$items[] = array(
				'attachment_id' => $post->ID,
				'title'         => $post->post_title,
				'url'           => $url,
				'mime_type'     => $mime,
			);
		}

		return array(
			'success' => true,
			'count'   => count( $items ),
			'media'   => $items,
		);
	}
}
