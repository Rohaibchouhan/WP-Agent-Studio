<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$keys = get_option( 'ai_elementor_agent_ai_keys', array() );
$current_provider = $keys['active_provider'] ?? 'openai';
?>

<div class="wrap aiea-admin-wrap">
	<h1>Direct AI Provider Configuration (Secondary Mode)</h1>
	<p>Configure direct API connections for autonomous background tasks executed within WordPress.</p>

	<div class="aiea-card">
		<form id="aiea-provider-form">
			<table class="form-table">
				<tr>
					<th scope="row">Select AI Provider</th>
					<td>
						<select id="aiea-select-provider" class="regular-text">
							<option value="openai" <?php selected( $current_provider, 'openai' ); ?>>OpenAI (ChatGPT-4o)</option>
							<option value="anthropic" <?php selected( $current_provider, 'anthropic' ); ?>>Anthropic (Claude 3.5 Sonnet)</option>
							<option value="gemini" <?php selected( $current_provider, 'gemini' ); ?>>Google Gemini</option>
							<option value="openrouter" <?php selected( $current_provider, 'openrouter' ); ?>>OpenRouter</option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row">API Key</th>
					<td>
						<input type="password" id="aiea-provider-api-key" class="regular-text" value="<?php echo esc_attr( $keys[ $current_provider ]['api_key'] ?? '' ); ?>" placeholder="sk-...">
						<p class="description">API key stored securely in WordPress database.</p>
					</td>
				</tr>

				<tr>
					<th scope="row">Model Identifier</th>
					<td>
						<input type="text" id="aiea-provider-model" class="regular-text" value="<?php echo esc_attr( $keys[ $current_provider ]['model'] ?? '' ); ?>" placeholder="e.g. gpt-4o or claude-3-5-sonnet-20241022">
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="button" class="button button-primary" id="aiea-save-provider-btn">Save Provider Settings</button>
				<button type="button" class="button" id="aiea-test-provider-btn">Test Connection</button>
			</p>
		</form>
		<div id="aiea-provider-test-result" class="aiea-margin-top"></div>
	</div>
</div>
