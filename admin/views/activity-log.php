<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$audit = \AiElementorAgent\Core\Plugin::get_instance()->get_audit_logger();
$search = sanitize_text_field( $_GET['s'] ?? '' );
$page = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
$limit = 20;
$offset = ( $page - 1 ) * $limit;

$logs = $audit->get_logs( $limit, $offset, $search );
$total = $audit->get_total_count( $search );
$total_pages = ceil( $total / $limit );
?>

<div class="wrap aiea-admin-wrap">
	<h1>MCP Agent Activity Audit Log</h1>
	<p>Complete execution history of actions invoked by remote AI agents.</p>

	<form method="get" class="aiea-search-form">
		<input type="hidden" name="page" value="ai-elementor-log">
		<p class="search-box">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search tool or action...">
			<input type="submit" class="button" value="Search Audit Log">
		</p>
	</form>

	<div class="aiea-card">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 160px;">Timestamp</th>
					<th style="width: 140px;">Client</th>
					<th>Tool Invoked</th>
					<th>Action</th>
					<th style="width: 80px;">Page ID</th>
					<th style="width: 80px;">Duration</th>
					<th style="width: 90px;">Status</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $logs ) ) : ?>
					<tr><td colspan="7">No audit logs found.</td></tr>
				<?php else : ?>
					<?php foreach ( $logs as $l ) : ?>
						<tr>
							<td><?php echo esc_html( $l['timestamp'] ); ?></td>
							<td><strong><?php echo esc_html( $l['client_id'] ); ?></strong></td>
							<td><code><?php echo esc_html( $l['tool_name'] ); ?></code></td>
							<td><?php echo esc_html( $l['action'] ); ?></td>
							<td><?php echo $l['page_id'] ? '#' . esc_html( $l['page_id'] ) : '-'; ?></td>
							<td><?php echo esc_html( $l['duration_ms'] ); ?> ms</td>
							<td>
								<span class="aiea-badge aiea-badge-<?php echo 'success' === $l['status'] ? 'success' : 'danger'; ?>">
									<?php echo esc_html( strtoupper( $l['status'] ) ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<span class="displaying-num"><?php echo esc_html( $total ); ?> items</span>
					<?php
					echo paginate_links( array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
						'total'     => $total_pages,
						'current'   => $page,
					) );
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
