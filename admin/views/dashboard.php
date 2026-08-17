<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin = \AiElementorAgent\Core\Plugin::get_instance();
$adapter = $plugin->get_elementor_adapter();
$tokens = $plugin->get_token_manager()->list_tokens();
$audit = $plugin->get_audit_logger();
$recent_logs = $audit->get_logs( 5 );
$rest_url = get_rest_url( null, 'ai-elementor/v1/mcp' );
?>

<div class="wrap aiea-admin-wrap">
	<h1 class="aiea-header-title">
		<span class="dashicons dashicons-superhero"></span> AI Elementor Agent Overview
	</h1>

	<div class="aiea-status-grid">
		<div class="aiea-card aiea-card-status">
			<h3>MCP Status</h3>
			<p class="aiea-status-badge aiea-status-active">
				<span class="aiea-indicator"></span> Active & Ready
			</p>
			<code class="aiea-code-block"><?php echo esc_html( $rest_url ); ?></code>
		</div>

		<div class="aiea-card">
			<h3>Elementor Environment</h3>
			<ul class="aiea-info-list">
				<li><strong>Elementor Core:</strong> <?php echo $adapter->is_active() ? 'v' . esc_html( $adapter->get_version() ) : '<span class="aiea-text-danger">Not Active</span>'; ?></li>
				<li><strong>Elementor Pro:</strong> <?php echo $adapter->is_pro_active() ? 'v' . esc_html( $adapter->get_pro_version() ) : 'Disabled'; ?></li>
				<li><strong>WordPress:</strong> v<?php echo esc_html( get_bloginfo( 'version' ) ); ?></li>
				<li><strong>PHP Version:</strong> <?php echo esc_html( PHP_VERSION ); ?></li>
			</ul>
		</div>

		<div class="aiea-card">
			<h3>Connected AI Clients</h3>
			<p class="aiea-big-stat"><?php echo esc_html( count( $tokens ) ); ?></p>
			<p class="aiea-stat-label">Active Bearer Tokens</p>
		</div>
	</div>

	<div class="aiea-card aiea-margin-top">
		<h3>Recent MCP Agent Operations</h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Timestamp</th>
					<th>Client</th>
					<th>Tool Invoked</th>
					<th>Action</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $recent_logs ) ) : ?>
					<tr><td colspan="5">No agent actions recorded yet.</td></tr>
				<?php else : ?>
					<?php foreach ( $recent_logs as $log ) : ?>
						<tr>
							<td><?php echo esc_html( $log['timestamp'] ); ?></td>
							<td><strong><?php echo esc_html( $log['client_id'] ); ?></strong></td>
							<td><code><?php echo esc_html( $log['tool_name'] ); ?></code></td>
							<td><?php echo esc_html( $log['action'] ); ?></td>
							<td>
								<span class="aiea-badge aiea-badge-<?php echo 'success' === $log['status'] ? 'success' : 'danger'; ?>">
									<?php echo esc_html( strtoupper( $log['status'] ) ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
