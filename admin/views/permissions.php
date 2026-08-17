<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pm = \AiElementorAgent\Core\Plugin::get_instance()->get_permission_manager();
$perms = $pm->get_all_permissions();
?>

<div class="wrap aiea-admin-wrap">
	<h1>Agent Permissions & Capability Controls</h1>
	<p>Manage granular operational permissions for connected MCP AI Agents.</p>

	<div class="aiea-card">
		<form id="aiea-permissions-form">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 250px;">Permission Name</th>
						<th>Description</th>
						<th style="width: 120px;">Status</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>read_site</strong></td>
						<td>Allows agent to query site overview, themes, PHP, and active plugin metadata.</td>
						<td><input type="checkbox" name="read_site" value="1" <?php checked( $perms['read_site'] ); ?>></td>
					</tr>
					<tr>
						<td><strong>read_pages</strong></td>
						<td>Allows agent to list pages and query page structural trees.</td>
						<td><input type="checkbox" name="read_pages" value="1" <?php checked( $perms['read_pages'] ); ?>></td>
					</tr>
					<tr>
						<td><strong>read_elementor</strong></td>
						<td>Allows agent to inspect Elementor widgets and global kit settings.</td>
						<td><input type="checkbox" name="read_elementor" value="1" <?php checked( $perms['read_elementor'] ); ?>></td>
					</tr>
					<tr>
						<td><strong>create_pages</strong></td>
						<td>Allows agent to create new pages and initialize Elementor editor mode.</td>
						<td><input type="checkbox" name="create_pages" value="1" <?php checked( $perms['create_pages'] ); ?>></td>
					</tr>
					<tr>
						<td><strong>modify_pages</strong></td>
						<td>Allows agent to add, edit, style, re-order, and duplicate Elementor widgets.</td>
						<td><input type="checkbox" name="modify_pages" value="1" <?php checked( $perms['modify_pages'] ); ?>></td>
					</tr>
					<tr>
						<td><strong>delete_pages</strong></td>
						<td>Allows agent to delete widgets or containers from page trees.</td>
						<td><input type="checkbox" name="delete_pages" value="1" <?php checked( $perms['delete_pages'] ); ?>></td>
					</tr>
					<tr>
						<td><strong>upload_media</strong></td>
						<td>Allows agent to upload images into WordPress Media Library.</td>
						<td><input type="checkbox" name="upload_media" value="1" <?php checked( $perms['upload_media'] ); ?>></td>
					</tr>
					<tr>
						<td><strong>modify_global_styles</strong></td>
						<td>Allows agent to update global kit colors and typography design system tokens.</td>
						<td><input type="checkbox" name="modify_global_styles" value="1" <?php checked( $perms['modify_global_styles'] ); ?>></td>
					</tr>
					<tr>
						<td><strong>publish_pages</strong></td>
						<td>Allows agent to set post status to "publish" directly. (Keep disabled for mandatory human review).</td>
						<td><input type="checkbox" name="publish_pages" value="1" <?php checked( $perms['publish_pages'] ); ?>></td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<button type="button" class="button button-primary" id="aiea-save-permissions-btn">Save Permission Policy</button>
			</p>
		</form>
	</div>
</div>
