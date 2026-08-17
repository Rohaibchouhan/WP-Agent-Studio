<?php
namespace AiElementorAgent\AI\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for Direct AI Provider Adapters.
 */
interface AIProviderInterface {

	/**
	 * Unique provider identifier (e.g., 'openai', 'anthropic', 'gemini', 'openrouter', 'openai_compatible').
	 */
	public function get_name(): string;

	/**
	 * Send prompt request to LLM API and return structured array response.
	 *
	 * @param string $system_prompt System prompt instructions.
	 * @param string $user_prompt User message.
	 * @param array  $context Additional options (temperature, model, max_tokens).
	 * @return array Provider response ['success' => bool, 'content' => string, 'error' => ?string].
	 */
	public function generate( string $system_prompt, string $user_prompt, array $context = array() ): array;

	/**
	 * Test connection and API key validity.
	 *
	 * @return array Result ['success' => bool, 'message' => string].
	 */
	public function test_connection(): array;
}
