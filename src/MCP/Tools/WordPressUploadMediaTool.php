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
use AiElementorAgent\Core\Plugin;

class WordPressUploadMediaTool extends AbstractTool {

	public function __construct( AgentEngine $engine, ContextManager $context, TokenManager $token_manager, GlobalStylesManager $global_styles ) {}

	public function get_name(): string {
		return 'wordpress_upload_media';
	}

	public function get_description(): string {
		return 'Uploads an image/media item into WordPress Media Library via base64 encoded string or remote URL.';
	}

	public function get_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'filename' ),
			'properties' => array(
				'filename' => array( 'type' => 'string', 'description' => 'Target filename with valid extension (e.g. image.jpg).' ),
				'base64'   => array( 'type' => 'string', 'description' => 'Base64 encoded file contents.' ),
				'url'      => array( 'type' => 'string', 'description' => 'Remote file URL to download.' ),
			),
		);
	}

	public function execute( array $arguments, array $context = [] ): array {
		$pm = Plugin::get_instance()->get_permission_manager();
		if ( ! $pm->can( 'upload_media', $context['user_id'] ) ) {
			return array( 'success' => false, 'error' => array( 'code' => 'PERMISSION_DENIED', 'message' => 'User lacks upload_media permission.' ) );
		}

		$filename = sanitize_file_name( $arguments['filename'] );
		$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		$allowed_exts = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' );

		if ( ! in_array( $ext, $allowed_exts, true ) ) {
			return array( 'success' => false, 'error' => array( 'code' => 'INVALID_FILE_TYPE', 'message' => 'Extension not allowed.' ) );
		}

		$file_content = '';
		if ( ! empty( $arguments['base64'] ) ) {
			$file_content = base64_decode( preg_replace( '#^data:image/\w+;base64,#i', '', $arguments['base64'] ) );
		} elseif ( ! empty( $arguments['url'] ) ) {
			$response = wp_remote_get( esc_url_raw( $arguments['url'] ) );
			if ( is_wp_error( $response ) ) {
				return array( 'success' => false, 'error' => array( 'code' => 'DOWNLOAD_FAILED', 'message' => $response->get_error_message() ) );
			}
			$file_content = wp_remote_retrieve_body( $response );
		} else {
			return array( 'success' => false, 'error' => array( 'code' => 'MISSING_DATA', 'message' => 'Provide base64 or url parameter.' ) );
		}

		$upload = wp_upload_bits( $filename, null, $file_content );
		if ( ! empty( $upload['error'] ) ) {
			return array( 'success' => false, 'error' => array( 'code' => 'UPLOAD_ERROR', 'message' => $upload['error'] ) );
		}

		$attachment = array(
			'post_mime_type' => $upload['type'],
			'post_title'     => sanitize_text_field( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $upload['file'] );
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return array(
			'success'       => true,
			'attachment_id' => $attach_id,
			'url'           => $upload['url'],
		);
	}
}
