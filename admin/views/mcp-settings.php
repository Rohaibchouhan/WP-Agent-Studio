<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin = \AiElementorAgent\Core\Plugin::get_instance();
$token_manager = $plugin->get_token_manager();
$tokens = $token_manager->list_tokens();
$mcp_url = get_rest_url( null, 'ai-elementor/v1/mcp' );
?>

<div class="wrap aiea-admin-wrap">
	<h1>Model Context Protocol (MCP) Server Settings</h1>
	<p>Configure access for external AI coding agents (such as Antigravity IDE) to control Elementor.</p>

	<div class="aiea-card">
		<h3>MCP Endpoint Configuration</h3>
		<table class="form-table">
			<tr>
				<th scope="row">Server REST URL</th>
				<td>
					<input type="text" class="large-text" readonly value="<?php echo esc_url( $mcp_url ); ?>" id="aiea-mcp-url-field">
					<p class="description">Copy this URL into your Antigravity IDE or MCP Client config.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">Supported Auth Methods</th>
				<td>
					<ul>
						<li><strong>1. Plugin Bearer Token:</strong> <code>Authorization: Bearer aiea_live_...</code> (Recommended)</li>
						<li><strong>2. WP Application Password:</strong> <code>Authorization: Basic base64(username:app_password)</code></li>
						<li><strong>3. HTTP Basic Auth:</strong> <code>Authorization: Basic base64(username:password)</code></li>
						<li><strong>4. WP Cookie Session:</strong> Active logged-in WP Admin browser session.</li>
					</ul>
				</td>
			</tr>
		</table>
	</div>

	<div class="aiea-card aiea-margin-top">
		<h3>Server Compatibility & Header Health</h3>
		<p>Hostinger, LiteSpeed, and Apache servers sometimes strip the <code>Authorization: Bearer</code> header. Use this diagnostic to verify header delivery.</p>
		<div class="notice notice-warning inline" style="margin:10px 0;padding:10px;">
			<p><strong>SECURITY NOTICE:</strong> Always configure external MCP clients to send the <code>Authorization: Bearer &lt;token&gt;</code> HTTP header. URL query tokens (<code>?token=...</code>) are for emergency troubleshooting only because URLs may be recorded in proxy/server access logs.</p>
		</div>
		<div style="display:flex;gap:12px;align-items:center;margin-top:10px;">
			<button class="button" id="aiea-btn-fix-htaccess">
				<span class="dashicons dashicons-admin-tools"></span> Add .htaccess Authorization Fix
			</button>
			<span id="aiea-htaccess-fix-status" style="font-weight:600;"></span>
		</div>
	</div>

	<div class="aiea-card aiea-margin-top">
		<div class="aiea-flex-between">
			<h3>Active Bearer Tokens</h3>
			<div style="display:flex;gap:8px;">
				<button class="button" id="aiea-btn-reset-all-limits" title="Clear all rate limit counters">
					<span class="dashicons dashicons-update"></span> Reset All Rate Limits
				</button>
				<button class="button button-primary" id="aiea-btn-generate-token">
					<span class="dashicons dashicons-plus-alt"></span> Generate New Token
				</button>
			</div>
		</div>

		<table class="wp-list-table widefat fixed striped aiea-margin-top">
			<thead>
				<tr>
					<th>Client Label</th>
					<th>Masked Secret</th>
					<th>User</th>
					<th>Expires</th>
					<th>Created</th>
					<th>Last Used</th>
					<th>Rate Limit</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody id="aiea-token-table-body">
				<?php
				$rate_limiter = new \AiElementorAgent\Security\RateLimiter();
				?>
				<?php if ( empty( $tokens ) ) : ?>
					<tr><td colspan="8">No tokens generated yet. Click "Generate New Token" above.</td></tr>
				<?php else : ?>
					<?php foreach ( $tokens as $tok ) :
						$rl_status = $rate_limiter->get_status( $tok['id'] );
						$rl_pct = $rl_status['max'] > 0 ? round( ( $rl_status['count'] / $rl_status['max'] ) * 100 ) : 0;
						$rl_color = $rl_pct >= 80 ? '#d63638' : ( $rl_pct >= 50 ? '#dba617' : '#00a32a' );
					?>
						<tr id="token-row-<?php echo esc_attr( $tok['id'] ); ?>">
							<td><strong><?php echo esc_html( $tok['label'] ); ?></strong><br><code style="font-size:10px;color:#888;"><?php echo esc_html( $tok['id'] ); ?></code></td>
							<td><code><?php echo esc_html( $tok['masked'] ); ?></code></td>
							<td><?php echo esc_html( $tok['user_name'] ); ?></td>
							<td>
								<?php if ( ! empty( $tok['is_expired'] ) ) : ?>
									<span style="color:#d63638;font-weight:600;">EXPIRED</span>
								<?php else : ?>
									<?php echo esc_html( $tok['expires_at'] ?? 'Never' ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $tok['created_at'] ); ?></td>
							<td><?php echo esc_html( $tok['last_used'] ?: 'Never' ); ?></td>
							<td>
								<span style="color:<?php echo esc_attr( $rl_color ); ?>;font-weight:600;">
									<?php echo esc_html( $rl_status['count'] ); ?>/<?php echo esc_html( $rl_status['max'] ); ?>
								</span>
								<?php if ( $rl_status['count'] > 0 ) : ?>
								<br><small style="color:#888;">Resets in <?php echo esc_html( $rl_status['resets_in_seconds'] ); ?>s</small>
								<?php endif; ?>
							</td>
							<td style="display:flex;flex-direction:column;gap:4px;">
								<button class="button button-small aiea-rotate-token-btn" data-id="<?php echo esc_attr( $tok['id'] ); ?>">
									<span class="dashicons dashicons-update" style="font-size:14px;line-height:1.6;"></span> Rotate Secret
								</button>
								<button class="button button-small aiea-reset-limit-btn" data-id="<?php echo esc_attr( $tok['id'] ); ?>">
									Reset Limit
								</button>
								<button class="button button-link-delete aiea-revoke-token-btn" data-id="<?php echo esc_attr( $tok['id'] ); ?>">
									Revoke Token
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<!-- Modal for New Secret Token Display & Client Config Snippets -->
<div id="aiea-token-modal" class="aiea-modal" style="display:none;">
	<div class="aiea-modal-content" style="max-width:650px;">
		<h2>New MCP Bearer Token Generated</h2>
		<p class="aiea-text-warning">
			<strong>IMPORTANT:</strong> Copy this secret key immediately. It will <u>never</u> be displayed again!
		</p>
		<div class="aiea-secret-box" style="margin-bottom:15px;">
			<code id="aiea-new-plain-secret" style="font-size:14px;font-weight:bold;"></code>
			<button class="button button-primary" id="aiea-copy-secret-btn">Copy Secret</button>
		</div>

		<h3>Client Setup Configurations</h3>
		<p class="description">Copy a configuration snippet directly into your AI client settings:</p>

		<div style="margin-top:10px;">
			<strong>Cursor / Claude Desktop Config (<code>mcpServers</code>):</strong>
			<textarea id="aiea-config-cursor" class="large-text code" rows="5" readonly style="font-size:12px;margin-top:4px;"></textarea>
			<button class="button button-small" id="aiea-copy-cursor-btn" style="margin-top:4px;">Copy Cursor Config</button>
		</div>

		<div style="margin-top:15px;">
			<strong>cURL Test Snippet:</strong>
			<textarea id="aiea-config-curl" class="large-text code" rows="3" readonly style="font-size:12px;margin-top:4px;"></textarea>
			<button class="button button-small" id="aiea-copy-curl-btn" style="margin-top:4px;">Copy cURL Command</button>
		</div>

		<button class="button button-primary aiea-margin-top" id="aiea-close-modal-btn">Done</button>
	</div>
</div>
