<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pages = get_pages( array( 'post_status' => array( 'publish', 'draft' ) ) );
$skill_registry = \AiElementorAgent\Core\Plugin::get_instance()->get_skill_registry();
$registered_skills = $skill_registry->list_skills();
?>
<div class="wrap aiea-admin-wrap">
	<h1 class="wp-heading-inline">🤖 ElementAI Studio — Chat & Approval Panel</h1>
	<p class="description">Prompt the AI engine, preview layout dry-run diffs, and approve changes before applying to Elementor pages.</p>

	<div class="aiea-chat-grid" style="display: flex; gap: 20px; margin-top: 20px; align-items: flex-start;">
		<!-- Left: Chat Panel -->
		<div class="aiea-card aiea-chat-box" style="flex: 1; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
			<h2 style="margin-top: 0;">AI Conversation</h2>
			
			<div class="aiea-form-group" style="margin-bottom: 15px;">
				<label for="aiea_target_page" style="font-weight: 600; display: block; margin-bottom: 5px;">Target Elementor Page:</label>
				<select id="aiea_target_page" class="widefat" style="max-width: 400px;">
					<option value="0">-- Select a Page --</option>
					<?php foreach ( $pages as $p ) : ?>
						<option value="<?php echo esc_attr( $p->ID ); ?>">
							<?php echo esc_html( $p->post_title ); ?> (ID: <?php echo esc_html( $p->ID ); ?>) [<?php echo esc_html( $p->post_status ); ?>]
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div id="aiea_chat_history" style="min-height: 250px; max-height: 400px; overflow-y: auto; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
				<div class="aiea-chat-msg system" style="margin-bottom: 12px; padding: 10px; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 4px;">
					<strong>🤖 ElementAI Studio:</strong> Hello! Describe the layout or section you want to build (e.g. <em>"Build a hero section with two buttons and a logo grid"</em>). I will generate a dry-run preview for your approval before saving.
				</div>
			</div>

			<div class="aiea-chat-input-row" style="display: flex; gap: 10px;">
				<textarea id="aiea_chat_input" rows="3" class="widefat" placeholder="Describe the page layout or edits you want..." style="resize: vertical;"></textarea>
				<button type="button" id="aiea_chat_submit_btn" class="button button-primary button-hero" style="white-space: nowrap;">
					Generate Preview
				</button>
			</div>
		</div>

		<!-- Right: Active Skills & Status Panel -->
		<div class="aiea-card aiea-skills-sidebar" style="width: 320px; background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
			<h3 style="margin-top: 0;">⚡ Declarative Skills Engine</h3>
			<p style="font-size: 12px; color: #64748b;">Loaded active skills from <code>skills/</code> directory:</p>
			
			<ul style="list-style: none; padding: 0; margin: 0;">
				<?php foreach ( $registered_skills as $s ) : ?>
					<li style="background: #f1f5f9; padding: 10px; border-radius: 6px; margin-bottom: 8px; border-left: 3px solid #10b981;">
						<strong style="font-size: 13px; color: #0f172a; display: block;"><?php echo esc_html( $s->get_name() ); ?></strong>
						<span style="font-size: 11px; color: #64748b;"><?php echo esc_html( $s->get_description() ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	let currentPlan = null;

	$('#aiea_chat_submit_btn').on('click', function() {
		const prompt = $('#aiea_chat_input').val().trim();
		const pageId = $('#aiea_target_page').val();

		if (!prompt) {
			alert('Please enter a prompt.');
			return;
		}

		const $btn = $(this);
		$btn.prop('disabled', true).text('Generating Plan...');

		// Append user message
		$('#aiea_chat_history').append(
			'<div class="aiea-chat-msg user" style="margin-bottom: 12px; padding: 10px; background: #f1f5f9; border-left: 4px solid #64748b; border-radius: 4px;">' +
				'<strong>User:</strong> ' + $('<div>').text(prompt).html() +
			'</div>'
		);

		$.post(AIEA_Admin.ajax_url, {
			action: 'aiea_chat_submit',
			nonce: AIEA_Admin.nonce,
			prompt: prompt,
			page_id: pageId
		}, function(res) {
			$btn.prop('disabled', false).text('Generate Preview');

			if (res.success) {
				currentPlan = res.data.dry_run_plan;
				let diffHtml = '<div class="aiea-chat-msg ai" style="margin-bottom: 12px; padding: 12px; background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 4px;">' +
					'<strong>🤖 ElementAI Studio — Planned Changes (Skill: ' + res.data.dry_run_plan.skill + '):</strong>' +
					'<ul style="margin: 8px 0 12px 15px;">';

				res.data.dry_run_plan.diff_summary.forEach(function(item) {
					diffHtml += '<li style="font-size: 13px; color: #047857;">[+] ' + item.action + ' ' + item.type.toUpperCase() + ': ' + item.label + '</li>';
				});

				diffHtml += '</ul>' +
					'<div style="display: flex; gap: 10px;">' +
						'<button type="button" class="button button-primary aiea-approve-btn">Approve & Apply</button>' +
						'<button type="button" class="button aiea-reject-btn">Reject</button>' +
					'</div>' +
				'</div>';

				$('#aiea_chat_history').append(diffHtml);
				$('#aiea_chat_input').val('');
				$('#aiea_chat_history').scrollTop($('#aiea_chat_history')[0].scrollHeight);
			} else {
				alert(res.data || 'Error generating plan.');
			}
		});
	});

	$(document).on('click', '.aiea-approve-btn', function() {
		const $btn = $(this);
		const pageId = $('#aiea_target_page').val();

		if (!pageId || pageId === '0') {
			alert('Please select a target page from the dropdown before approving.');
			return;
		}

		$btn.prop('disabled', true).text('Applying...');

		$.post(AIEA_Admin.ajax_url, {
			action: 'aiea_chat_approve',
			nonce: AIEA_Admin.nonce,
			prompt: currentPlan ? currentPlan.prompt : 'Approved action',
			page_id: pageId
		}, function(res) {
			if (res.success) {
				$('#aiea_chat_history').append(
					'<div class="aiea-chat-msg system" style="margin-bottom: 12px; padding: 10px; background: #ecfdf5; border-left: 4px solid #059669; border-radius: 4px;">' +
						'<strong>✓ Applied Successfully!</strong> Revision backup created: <code>' + res.data.backup_id + '</code>. ' +
						'<a href="' + res.data.edit_url + '" target="_blank" class="button button-small" style="margin-left: 8px;">Edit in Elementor</a> ' +
						'<a href="' + res.data.url + '" target="_blank" class="button button-small">View Live Page</a>' +
					'</div>'
				);
				$btn.replaceWith('<span style="color: #059669; font-weight: 600;">✓ Approved & Applied</span>');
			} else {
				alert(res.data || 'Failed to apply changes.');
				$btn.prop('disabled', false).text('Approve & Apply');
			}
		});
	});

	$(document).on('click', '.aiea-reject-btn', function() {
		$(this).closest('.aiea-chat-msg').append('<div style="color: #dc2626; margin-top: 8px; font-weight: 600;">❌ Rejected</div>');
		$(this).siblings('.aiea-approve-btn').remove();
		$(this).remove();
	});
});
</script>
