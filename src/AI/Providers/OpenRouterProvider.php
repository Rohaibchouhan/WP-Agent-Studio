<?php
namespace AiElementorAgent\AI\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiElementorAgent\AI\Contracts\AIProviderInterface;

class OpenRouterProvider implements AIProviderInterface {

	private string $api_key;
	private string $model;

	public function __construct( string $api_key = '', string $model = 'anthropic/claude-3.5-sonnet' ) {
		$this->api_key = $api_key;
		$this->model   = $model;
	}

	public function get_name(): string {
		return 'openrouter';
	}

	public function generate( string $system_prompt, string $user_prompt, array $context = array() ): array {
		if ( empty( $this->api_key ) ) {
			return array( 'success' => false, 'error' => 'OpenRouter API key is missing.' );
		}

		$model = $context['model'] ?? $this->model;
		$body = array(
			'model'    => $model,
			'messages' => array(
				array( 'role' => 'system', 'content' => $system_prompt ),
				array( 'role' => 'user', 'content' => $user_prompt ),
			),
		);

		$response = wp_remote_post( 'https://openrouter.ai/api/v1/chat/completions', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'HTTP-Referer'  => get_site_url(),
				'X-Title'       => 'AI Elementor Agent',
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 60,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'error' => $response->get_error_message() );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! empty( $data['error'] ) ) {
			return array( 'success' => false, 'error' => $data['error']['message'] ?? 'OpenRouter Error' );
		}

		return array(
			'success' => true,
			'content' => $data['choices'][0]['message']['content'] ?? '',
		);
	}

	public function test_connection(): array {
		$res = $this->generate( 'Test helper', 'Say ok.' );
		if ( $res['success'] ) {
			return array( 'success' => true, 'message' => 'Connected to OpenRouter successfully.' );
		}
		return array( 'success' => false, 'message' => $res['error'] ?? 'Connection failed.' );
	}
}
