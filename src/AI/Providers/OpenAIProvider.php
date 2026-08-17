<?php
namespace AiElementorAgent\AI\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiElementorAgent\AI\Contracts\AIProviderInterface;

class OpenAIProvider implements AIProviderInterface {

	private string $api_key;
	private string $model;

	public function __construct( string $api_key = '', string $model = 'gpt-4o' ) {
		$this->api_key = $api_key;
		$this->model   = $model;
	}

	public function get_name(): string {
		return 'openai';
	}

	public function generate( string $system_prompt, string $user_prompt, array $context = array() ): array {
		if ( empty( $this->api_key ) ) {
			return array( 'success' => false, 'error' => 'OpenAI API key is missing.' );
		}

		$model = $context['model'] ?? $this->model;
		$body = array(
			'model'       => $model,
			'messages'    => array(
				array( 'role' => 'system', 'content' => $system_prompt ),
				array( 'role' => 'user', 'content' => $user_prompt ),
			),
			'temperature' => (float) ( $context['temperature'] ?? 0.7 ),
		);

		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
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
			return array( 'success' => false, 'error' => $data['error']['message'] ?? 'OpenAI API Error' );
		}

		$content = $data['choices'][0]['message']['content'] ?? '';
		return array(
			'success' => true,
			'content' => $content,
			'usage'   => $data['usage'] ?? array(),
		);
	}

	public function test_connection(): array {
		$res = $this->generate( 'You are a test helper.', 'Say ok.' );
		if ( $res['success'] ) {
			return array( 'success' => true, 'message' => 'Connected to OpenAI successfully.' );
		}
		return array( 'success' => false, 'message' => $res['error'] ?? 'Connection test failed.' );
	}
}
