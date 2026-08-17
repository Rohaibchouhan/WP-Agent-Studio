<?php
namespace AiElementorAgent\AI\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AiElementorAgent\AI\Contracts\AIProviderInterface;

class GeminiProvider implements AIProviderInterface {

	private string $api_key;
	private string $model;

	public function __construct( string $api_key = '', string $model = 'gemini-1.5-pro' ) {
		$this->api_key = $api_key;
		$this->model   = $model;
	}

	public function get_name(): string {
		return 'gemini';
	}

	public function generate( string $system_prompt, string $user_prompt, array $context = array() ): array {
		if ( empty( $this->api_key ) ) {
			return array( 'success' => false, 'error' => 'Google Gemini API key is missing.' );
		}

		$model = $context['model'] ?? $this->model;
		$url = sprintf( 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s', $model, $this->api_key );

		$body = array(
			'systemInstruction' => array(
				'parts' => array( array( 'text' => $system_prompt ) ),
			),
			'contents' => array(
				array(
					'role'  => 'user',
					'parts' => array( array( 'text' => $user_prompt ) ),
				),
			),
		);

		$response = wp_remote_post( $url, array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $body ),
			'timeout' => 60,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'error' => $response->get_error_message() );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! empty( $data['error'] ) ) {
			return array( 'success' => false, 'error' => $data['error']['message'] ?? 'Gemini API Error' );
		}

		$content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
		return array(
			'success' => true,
			'content' => $content,
		);
	}

	public function test_connection(): array {
		$res = $this->generate( 'Test helper', 'Say ok.' );
		if ( $res['success'] ) {
			return array( 'success' => true, 'message' => 'Connected to Google Gemini successfully.' );
		}
		return array( 'success' => false, 'message' => $res['error'] ?? 'Connection test failed.' );
	}
}
