<?php
namespace AiElementorAgent\AI\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiElementorAgent\AI\Contracts\AIProviderInterface;

class OpenAICompatibleProvider implements AIProviderInterface {

	private string $api_key;
	private string $endpoint;
	private string $model;

	public function __construct( string $api_key = '', string $endpoint = '', string $model = 'default' ) {
		$this->api_key  = $api_key;
		$this->endpoint = rtrim( $endpoint, '/' );
		$this->model    = $model;
	}

	public function get_name(): string {
		return 'openai_compatible';
	}

	public function generate( string $system_prompt, string $user_prompt, array $context = array() ): array {
		if ( empty( $this->endpoint ) ) {
			return array( 'success' => false, 'error' => 'Endpoint URL is missing.' );
		}

		$url = $this->endpoint . '/chat/completions';
		$model = $context['model'] ?? $this->model;

		$headers = array( 'Content-Type' => 'application/json' );
		if ( ! empty( $this->api_key ) ) {
			$headers['Authorization'] = 'Bearer ' . $this->api_key;
		}

		$body = array(
			'model'    => $model,
			'messages' => array(
				array( 'role' => 'system', 'content' => $system_prompt ),
				array( 'role' => 'user', 'content' => $user_prompt ),
			),
		);

		$response = wp_remote_post( $url, array(
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
			'timeout' => 60,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'error' => $response->get_error_message() );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! empty( $data['error'] ) ) {
			return array( 'success' => false, 'error' => $data['error']['message'] ?? 'API Error' );
		}

		return array(
			'success' => true,
			'content' => $data['choices'][0]['message']['content'] ?? '',
		);
	}

	public function test_connection(): array {
		$res = $this->generate( 'Test helper', 'Say ok.' );
		if ( $res['success'] ) {
			return array( 'success' => true, 'message' => 'Connected to API endpoint successfully.' );
		}
		return array( 'success' => false, 'message' => $res['error'] ?? 'Connection failed.' );
	}
}
