<?php
namespace AiElementorAgent\AI\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiElementorAgent\AI\Contracts\AIProviderInterface;

class AnthropicProvider implements AIProviderInterface {

	private string $api_key;
	private string $model;

	public function __construct( string $api_key = '', string $model = 'claude-3-5-sonnet-20241022' ) {
		$this->api_key = $api_key;
		$this->model   = $model;
	}

	public function get_name(): string {
		return 'anthropic';
	}

	public function generate( string $system_prompt, string $user_prompt, array $context = array() ): array {
		if ( empty( $this->api_key ) ) {
			return array( 'success' => false, 'error' => 'Anthropic API key is missing.' );
		}

		$model = $context['model'] ?? $this->model;
		$body = array(
			'model'       => $model,
			'max_tokens'  => (int) ( $context['max_tokens'] ?? 4096 ),
			'system'      => $system_prompt,
			'messages'    => array(
				array( 'role' => 'user', 'content' => $user_prompt ),
			),
		);

		$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
			'headers' => array(
				'x-api-key'         => $this->api_key,
				'anthropic-version' => '2023-06-01',
				'Content-Type'      => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 60,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'error' => $response->get_error_message() );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! empty( $data['error'] ) ) {
			return array( 'success' => false, 'error' => $data['error']['message'] ?? 'Anthropic API Error' );
		}

		$content = $data['content'][0]['text'] ?? '';
		return array(
			'success' => true,
			'content' => $content,
			'usage'   => $data['usage'] ?? array(),
		);
	}

	public function test_connection(): array {
		$res = $this->generate( 'You are a test helper.', 'Say ok.' );
		if ( $res['success'] ) {
			return array( 'success' => true, 'message' => 'Connected to Anthropic successfully.' );
		}
		return array( 'success' => false, 'message' => $res['error'] ?? 'Connection test failed.' );
	}
}
