<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_id = (int) ( $_GET['page_id'] ?? 0 );
$rm = new \AiElementorAgent\Backup\RevisionManager();
$backups = $page_id ? $rm->list_backups( $page_id ) : array();

$context = \AiElementorAgent\Core\Plugin::get_instance()->get_context_manager();
$pages = $context->get_pages();
?>

<div class="wrap aiea-admin-wrap">
	<h1>Elementor Page Restore Points & Backups</h1>
	<p>View and manage pre-mutation snapshot revisions generated automatically before AI actions.</p>

	<div class="aiea-card">
		<form method="get" class="aiea-flex-between">
			<input type="hidden" name="page" value="ai-elementor-backups">
			<label><strong>Select Page to View Snapshots:</strong>
				<select name="page_id" onchange="this.form.submit()">
					<option value="0">-- Select a Page --</option>
					<?php foreach ( $pages as $p ) : ?>
						<option value="<?php echo esc_attr( $p['page_id'] ); ?>" <?php selected( $page_id, $p['page_id'] ); ?>>
							<?php echo esc_html( $p['title'] ); ?> (ID: <?php echo esc_html( $p['page_id'] ); ?>)
						</option>
					<?php endforeach; ?>
				</select>
			</label>
		</form>

		<?php if ( $page_id ) : ?>
			<table class="wp-list-table widefat fixed striped aiea-margin-top">
				<thead>
					<tr>
						<th>Snapshot ID</th>
						<th>Timestamp</th>
						<th>Revision Reason</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $backups ) ) : ?>
						<tr><td colspan="4">No snapshot backups recorded for this page.</td></tr>
					<?php else : ?>
						<?php foreach ( $backups as $b ) : ?>
							<tr>
								<td><code><?php echo esc_html( $b['id'] ); ?></code></td>
								<td><?php echo esc_html( $b['timestamp'] ); ?></td>
								<td><?php echo esc_html( $b['reason'] ); ?></td>
								<td>
									<button class="button button-small aiea-restore-btn" data-page="<?php echo esc_attr( $page_id ); ?>" data-id="<?php echo esc_attr( $b['id'] ); ?>">
										Restore Snapshot
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p class="aiea-margin-top">Select a page above to display available revision snapshots.</p>
		<?php endif; ?>
	</div>
</div>
