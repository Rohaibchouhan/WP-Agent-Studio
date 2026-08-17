<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mcp_url = get_rest_url( null, 'ai-elementor/v1/mcp' );
$health_url = get_rest_url( null, 'ai-elementor/v1/health' );
$debug_url = get_rest_url( null, 'ai-elementor/v1/debug' );

$token_manager = \AiElementorAgent\Core\Plugin::get_instance()->get_token_manager();
$tokens = $token_manager->list_tokens();
?>

<div class="wrap aiea-admin-wrap">
	<h1 class="wp-heading-inline">MCP Server Connection Diagnostic Test</h1>
	<p>Use this tool to test and verify your WordPress site's MCP server endpoint and diagnose web server header issues.</p>

	<hr class="wp-header-end">

	<div class="aiea-card aiea-margin-top">
		<h3>1. Endpoint Configuration</h3>
		<table class="form-table">
			<tr>
				<th>MCP JSON-RPC Endpoint:</th>
				<td><code><?php echo esc_url( $mcp_url ); ?></code></td>
			</tr>
			<tr>
				<th>Health Check Endpoint:</th>
				<td><a href="<?php echo esc_url( $health_url ); ?>" target="_blank"><code><?php echo esc_url( $health_url ); ?></code></a></td>
			</tr>
			<tr>
				<th>Server Debug Endpoint:</th>
				<td><a href="<?php echo esc_url( $debug_url ); ?>" target="_blank"><code><?php echo esc_url( $debug_url ); ?></code></a></td>
			</tr>
		</table>
	</div>

	<div class="aiea-card aiea-margin-top">
		<h3>2. Web Server Header Check (Hostinger LiteSpeed Audit)</h3>
		<p>Hostinger and LiteSpeed servers often strip the <code>Authorization: Bearer ...</code> HTTP header unless configured in <code>.htaccess</code>.</p>
		
		<?php
		$http_auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ( $_SERVER['HTTP_X_AUTHORIZATION'] ?? '' ) );
		?>

		<div class="notice notice-info inline" style="margin:10px 0;padding:12px;">
			<p><strong>Raw Server Authorization Header Received by PHP:</strong></p>
			<code><?php echo ! empty( $http_auth ) ? esc_html( $http_auth ) : '<span style="color:#d63638;font-weight:bold;">NONE RECEIVED (Header is being stripped by server!)</span>'; ?></code>
		</div>

		<div style="background:#f0f6fc;border-left:4px solid #0969da;padding:12px;margin-top:15px;border-radius:3px;">
			<h4 style="margin:0 0 6px 0;">⚡ How to fix stripped Authorization headers on Hostinger:</h4>
			<p style="margin:0 0 8px 0;">Add these two lines to your WordPress root <code>.htaccess</code> file directly after <code># BEGIN WordPress</code>:</p>
			<pre style="background:#fff;padding:10px;border:1px solid #ccc;border-radius:4px;overflow-x:auto;">SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
CGIPassAuth On</pre>
		</div>
	</div>

	<div class="aiea-card aiea-margin-top">
		<h3>3. Live Connection Tester</h3>
		<p>Select a token or enter your secret Bearer token below to test the MCP <code>initialize</code> JSON-RPC payload directly from your browser.</p>

		<table class="form-table">
			<tr>
				<th>Select Active Token:</th>
				<td>
					<select id="aiea-test-token-select" style="min-width:300px;">
						<option value="">-- Manual Token Entry --</option>
						<?php foreach ( $tokens as $t ) : ?>
							<option value="<?php echo esc_attr( $t['id'] ); ?>"><?php echo esc_html( $t['label'] ); ?> (<?php echo esc_html( $t['masked'] ); ?>)</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th>Or Enter Secret Token:</th>
				<td>
					<input type="text" id="aiea-test-token-secret" class="regular-text" placeholder="aiea_live_..." />
				</td>
			</tr>
			<tr>
				<th>Transport Method:</th>
				<td>
					<label><input type="radio" name="aiea_test_transport" value="header" checked /> HTTP Authorization Header (Standard MCP)</label><br>
					<label><input type="radio" name="aiea_test_transport" value="query" /> URL Query Parameter Fallback (<code>?token=YOUR_TOKEN</code>)</label><br>
					<label><input type="radio" name="aiea_test_transport" value="session" /> Logged-in WP Admin Session</label>
				</td>
			</tr>
			<tr>
				<th>Action:</th>
				<td>
					<button class="button button-primary button-large" id="aiea-btn-run-mcp-test">
						<span class="dashicons dashicons-rest-api"></span> Run MCP Server Test
					</button>
				</td>
			</tr>
		</table>

		<div id="aiea-mcp-test-results" style="display:none;margin-top:20px;">
			<h4>Test Output:</h4>
			<pre id="aiea-mcp-test-output" style="background:#1e1e1e;color:#569cd6;padding:15px;border-radius:5px;max-height:400px;overflow:auto;font-family:monospace;"></pre>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	$('#aiea-btn-run-mcp-test').on('click', function(e) {
		e.preventDefault();
		const btn = $(this);
		const secret = $('#aiea-test-token-secret').val().trim();
		const transport = $('input[name="aiea_test_transport"]:checked').val();

		if (!secret && transport !== 'session') {
			alert('Please enter a secret Bearer token or select a token to test.');
			return;
		}

		btn.prop('disabled', true).text('Testing MCP Server...');
		$('#aiea-mcp-test-results').show();
		$('#aiea-mcp-test-output').text('Sending request to: ' + AIEA_Admin.mcp_url + ' ...').css('color', '#dcdcdc');

		let targetUrl = AIEA_Admin.mcp_url;
		const headers = {
			'Content-Type': 'application/json'
		};

		if (transport === 'query' && secret) {
			targetUrl += '?token=' + encodeURIComponent(secret);
		} else if (transport === 'header' && secret) {
			headers['Authorization'] = 'Bearer ' + secret;
		}

		const payload = {
			jsonrpc: '2.0',
			id: 1,
			method: 'initialize',
			params: {
				protocolVersion: '2024-11-05',
				capabilities: {},
				clientInfo: { name: 'WP Admin Diagnostics', version: '1.0' }
			}
		};

		$.ajax({
			url: targetUrl,
			type: 'POST',
			headers: headers,
			data: JSON.stringify(payload),
			contentType: 'application/json',
			success: function(res, status, xhr) {
				btn.prop('disabled', false).html('<span class="dashicons dashicons-rest-api"></span> Run MCP Server Test');
				let output = '=== DIAGNOSTIC RESULT: SUCCESS (HTTP 200) ===\n\n';
				output += 'MCP Initialization Succeeded!\n';
				output += 'Protocol Version: ' + (res.result ? res.result.protocolVersion : 'N/A') + '\n';
				output += 'Server Info: ' + (res.result && res.result.serverInfo ? res.result.serverInfo.name + ' v' + res.result.serverInfo.version : 'N/A') + '\n\n';
				output += JSON.stringify(res, null, 2);
				$('#aiea-mcp-test-output').css('color', '#4ec9b0').text(output);
			},
			error: function(xhr, status, error) {
				btn.prop('disabled', false).html('<span class="dashicons dashicons-rest-api"></span> Run MCP Server Test');
				let classification = '';
				let details = '';

				if (xhr.status === 0) {
					classification = '❌ FAILURE: Endpoint Unreachable / CORS Blocked / Network Timeout';
					details = 'The browser could not reach ' + AIEA_Admin.mcp_url + '. Check site URL and SSL certificate.';
				} else if (xhr.status === 404) {
					classification = '❌ FAILURE: Endpoint Not Found (404)';
					details = 'The REST API endpoint URL is incorrect or rewrite rules are not flushed.';
				} else if (xhr.status === 401) {
					try {
						const res = JSON.parse(xhr.responseText);
						if (res.error && res.error.code === -32001) {
							classification = '⚠️ DIAGNOSTIC: Invalid, Revoked, Expired, or Site-Mismatched Token (401)';
							details = 'The server reached PHP successfully, but rejected the token.\n' +
								'Tokens are database-specific. Please generate a BRAND-NEW token on THIS site in MCP Settings.';
						} else {
							classification = '⚠️ DIAGNOSTIC: Authorization Header Stripped or Missing';
							details = 'The web server (Hostinger/LiteSpeed/Apache) stripped the Authorization header before it reached PHP.\n' +
								'Run the ".htaccess Authorization Fix" in MCP Settings.';
						}
					} catch(e) {
						classification = '⚠️ DIAGNOSTIC: 401 Unauthorized Response';
						details = xhr.responseText;
					}
				} else {
					classification = '❌ FAILURE: HTTP ' + xhr.status + ' Error';
					details = xhr.responseText;
				}

				let output = '=== DIAGNOSTIC RESULT ===\n' + classification + '\n\n' + details + '\n\nRaw Response:\n' + xhr.responseText;
				$('#aiea-mcp-test-output').css('color', '#f44747').text(output);
			}
		});
	});
});
</script>
