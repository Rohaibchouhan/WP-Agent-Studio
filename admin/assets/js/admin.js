jQuery(document).ready(function($) {
	// 1. Generate Token Modal Handler
	$('#aiea-btn-generate-token').on('click', function(e) {
		e.preventDefault();
		const label = prompt('Enter a label for this MCP token (e.g. Antigravity IDE):', 'Antigravity IDE');
		if (!label) return;

		$.post(AIEA_Admin.ajax_url, {
			action: 'aiea_generate_token',
			nonce: AIEA_Admin.nonce,
			label: label
		}, function(res) {
			if (res.success) {
				const secret = res.data.plain_secret;
				const mcpUrl = AIEA_Admin.mcp_url;

				$('#aiea-new-plain-secret').text(secret);

				// Build Cursor / Claude Desktop JSON
				const cursorConfig = {
					"mcpServers": {
						"ai-elementor-agent": {
							"url": mcpUrl,
							"headers": {
								"Authorization": "Bearer " + secret
							}
						}
					}
				};
				$('#aiea-config-cursor').val(JSON.stringify(cursorConfig, null, 2));

				// Build cURL command
				const curlCmd = 'curl -X POST "' + mcpUrl + '" \\\n' +
					'  -H "Authorization: Bearer ' + secret + '" \\\n' +
					'  -H "Content-Type: application/json" \\\n' +
					'  -d \'{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}\'';
				$('#aiea-config-curl').val(curlCmd);

				$('#aiea-token-modal').fadeIn();
			} else {
				alert('Error: ' + res.data);
			}
		});
	});

	$('#aiea-close-modal-btn').on('click', function() {
		$('#aiea-token-modal').fadeOut();
		location.reload();
	});

	$('#aiea-copy-secret-btn').on('click', function() {
		const text = $('#aiea-new-plain-secret').text();
		navigator.clipboard.writeText(text).then(function() {
			alert('Secret token copied to clipboard!');
		});
	});

	$('#aiea-copy-cursor-btn').on('click', function() {
		const text = $('#aiea-config-cursor').val();
		navigator.clipboard.writeText(text).then(function() {
			alert('Cursor configuration copied to clipboard!');
		});
	});

	$('#aiea-copy-curl-btn').on('click', function() {
		const text = $('#aiea-config-curl').val();
		navigator.clipboard.writeText(text).then(function() {
			alert('cURL command copied to clipboard!');
		});
	});

	// Rotate Secret Key
	$(document).on('click', '.aiea-rotate-token-btn', function(e) {
		e.preventDefault();
		const tokenId = $(this).data('id');
		if (!confirm('Rotate secret key for token ' + tokenId + '? The old secret will be revoked immediately.')) return;

		$.post(AIEA_Admin.ajax_url, {
			action: 'aiea_rotate_token',
			nonce: AIEA_Admin.nonce,
			token_id: tokenId
		}, function(res) {
			if (res.success) {
				const secret = res.data.plain_secret;
				const mcpUrl = AIEA_Admin.mcp_url;

				$('#aiea-new-plain-secret').text(secret);

				const cursorConfig = {
					"mcpServers": {
						"ai-elementor-agent": {
							"url": mcpUrl,
							"headers": {
								"Authorization": "Bearer " + secret
							}
						}
					}
				};
				$('#aiea-config-cursor').val(JSON.stringify(cursorConfig, null, 2));

				const curlCmd = 'curl -X POST "' + mcpUrl + '" \\\n' +
					'  -H "Authorization: Bearer ' + secret + '" \\\n' +
					'  -H "Content-Type: application/json" \\\n' +
					'  -d \'{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}\'';
				$('#aiea-config-curl').val(curlCmd);

				$('#aiea-token-modal').fadeIn();
			} else {
				alert('Failed to rotate token secret.');
			}
		});
	});

	// Add .htaccess Authorization Fix
	$('#aiea-btn-fix-htaccess').on('click', function(e) {
		e.preventDefault();
		const btn = $(this);
		btn.prop('disabled', true).text('Writing rule...');

		$.post(AIEA_Admin.ajax_url, {
			action: 'aiea_fix_htaccess',
			nonce: AIEA_Admin.nonce
		}, function(res) {
			if (res.success) {
				$('#aiea-htaccess-fix-status').html('<span style="color:green;">' + res.data.message + '</span>');
				btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-tools"></span> Add .htaccess Authorization Fix');
			} else {
				$('#aiea-htaccess-fix-status').html('<span style="color:red;">' + (res.data || 'Failed to apply rule.') + '</span>');
				btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-tools"></span> Add .htaccess Authorization Fix');
			}
		});
	});

	// 2. Revoke Token Handler
	$('.aiea-revoke-token-btn').on('click', function(e) {
		e.preventDefault();
		const tokenId = $(this).data('id');
		if (!confirm('Are you sure you want to revoke this Bearer Token?')) return;

		$.post(AIEA_Admin.ajax_url, {
			action: 'aiea_revoke_token',
			nonce: AIEA_Admin.nonce,
			token_id: tokenId
		}, function(res) {
			if (res.success) {
				$('#token-row-' + tokenId).fadeOut();
			} else {
				alert('Failed to revoke token.');
			}
		});
	});

	// 3. Save Permissions Handler
	$('#aiea-save-permissions-btn').on('click', function(e) {
		e.preventDefault();
		const perms = {};
		$('#aiea-permissions-form input[type="checkbox"]').each(function() {
			perms[$(this).attr('name')] = $(this).is(':checked') ? 1 : 0;
		});

		$.post(AIEA_Admin.ajax_url, {
			action: 'aiea_save_permissions',
			nonce: AIEA_Admin.nonce,
			permissions: perms
		}, function(res) {
			if (res.success) {
				alert('Permissions saved successfully!');
			} else {
				alert('Failed to save permissions.');
			}
		});
	});

	// 4. Save AI Provider Handler
	$('#aiea-save-provider-btn').on('click', function(e) {
		e.preventDefault();
		const provider = $('#aiea-select-provider').val();
		const apiKey = $('#aiea-provider-api-key').val();
		const model = $('#aiea-provider-model').val();

		$.post(AIEA_Admin.ajax_url, {
			action: 'aiea_save_ai_provider',
			nonce: AIEA_Admin.nonce,
			provider: provider,
			api_key: apiKey,
			model: model
		}, function(res) {
			if (res.success) {
				alert('AI Provider settings saved!');
			} else {
				alert('Failed to save provider settings.');
			}
		});
	});

	// 5. Test AI Provider Connection
	$('#aiea-test-provider-btn').on('click', function(e) {
		e.preventDefault();
		const provider = $('#aiea-select-provider').val();
		$('#aiea-provider-test-result').html('Testing connection...').css('color', '#666');

		$.post(AIEA_Admin.ajax_url, {
			action: 'aiea_test_ai_provider',
			nonce: AIEA_Admin.nonce,
			provider: provider
		}, function(res) {
			if (res.success) {
				$('#aiea-provider-test-result').html('<strong style="color:green;">' + res.data.message + '</strong>');
			} else {
				$('#aiea-provider-test-result').html('<strong style="color:red;">' + (res.data || 'Connection failed.') + '</strong>');
			}
		});
	});

	// 6. Reset Rate Limit — Per Token
	$(document).on('click', '.aiea-reset-limit-btn', function(e) {
		e.preventDefault();
		const tokenId = $(this).data('id');
		const btn = $(this);
		btn.prop('disabled', true).text('Resetting...');

		$.post(AIEA_Admin.ajax_url, {
			action: 'aiea_reset_rate_limit',
			nonce: AIEA_Admin.nonce,
			token_id: tokenId
		}, function(res) {
			if (res.success) {
				btn.closest('tr').find('td:nth-child(6)').html('<span style="color:#00a32a;font-weight:600;">0/' + res.data.max + '</span>');
				btn.prop('disabled', false).html('<span class="dashicons dashicons-update" style="font-size:14px;line-height:1.6;"></span> Reset Limit');
			} else {
				alert('Failed to reset rate limit.');
				btn.prop('disabled', false);
			}
		});
	});

	// 7. Reset All Rate Limits
	$('#aiea-btn-reset-all-limits').on('click', function(e) {
		e.preventDefault();
		if (!confirm('Reset ALL rate limit counters for ALL tokens?')) return;
		const btn = $(this);
		btn.prop('disabled', true).text('Resetting...');

		$.post(AIEA_Admin.ajax_url, {
			action: 'aiea_reset_rate_limit',
			nonce: AIEA_Admin.nonce,
			token_id: ''
		}, function(res) {
			if (res.success) {
				location.reload();
			} else {
				alert('Failed to reset rate limits.');
				btn.prop('disabled', false).text('Reset All Rate Limits');
			}
		});
	});
});
