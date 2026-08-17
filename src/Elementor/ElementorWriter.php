<?php
namespace AiElementorAgent\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Safely writes, updates, moves, duplicates, and serializes Elementor post data.
 */
class ElementorWriter {

	/**
	 * Generate a unique 7-character hex Elementor element ID.
	 */
	public function generate_element_id(): string {
		return substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 7 );
	}

	/**
	 * Read raw element array from post meta.
	 */
	public function get_raw_page_elements( int $page_id ): array {
		$raw_data = get_post_meta( $page_id, '_elementor_data', true );
		if ( empty( $raw_data ) ) {
			return array();
		}
		$decoded = is_string( $raw_data ) ? json_decode( $raw_data, true ) : $raw_data;
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Save updated element array back to post meta.
	 * Uses full cache invalidation to ensure Elementor renders the new data immediately.
	 */
	public function save_page_elements( int $page_id, array $elements ): bool {
		$json_data = wp_json_encode( $elements );

		// Use update_post_meta with wp_slash to protect backslashes in JSON
		update_post_meta( $page_id, '_elementor_data', wp_slash( $json_data ) );

		// Store version hash to help detect stale renders
		update_post_meta( $page_id, '_elementor_data_version', md5( $json_data ) );

		// --- Full Elementor CSS Cache Invalidation ---
		try {
			// 1. Delete Elementor generated CSS files for this page via files_manager
			if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
				\Elementor\Plugin::$instance->files_manager->clear_stack();

				// Also delete the specific post CSS file
				if ( method_exists( '\Elementor\Core\Files\CSS\Post', 'create' ) ) {
					$post_css = \Elementor\Core\Files\CSS\Post::create( $page_id );
					if ( is_object( $post_css ) && method_exists( $post_css, 'delete' ) ) {
						$post_css->delete();
					}
				}
			}
		} catch ( \Throwable $e ) {
			// Ignore CSS cache clear exceptions gracefully
		}

		// 2. Delete stored CSS post meta flags Elementor uses to track cached CSS
		delete_post_meta( $page_id, '_elementor_css' );
		delete_post_meta( $page_id, '_elementor_inline_svg' );

		// 3. Clear WordPress object cache for this post
		clean_post_cache( $page_id );

		// 4. Touch the post modified date so WordPress CDNs/caches know content changed
		wp_update_post( array(
			'ID'                => $page_id,
			'post_modified'     => current_time( 'mysql' ),
			'post_modified_gmt' => current_time( 'mysql', true ),
		) );

		// 5. Purge LiteSpeed, WP Rocket, W3TC, and WP Cache plugins for this page
		try {
			if ( function_exists( 'litespeed_purge_single_post' ) ) {
				litespeed_purge_single_post( $page_id );
			}
			do_action( 'litespeed_purge_post', $page_id );
			if ( class_exists( '\LiteSpeed\Purge' ) && method_exists( '\LiteSpeed\Purge', 'purge_post' ) ) {
				\LiteSpeed\Purge::purge_post( $page_id );
			}
			if ( function_exists( 'w3tc_flush_post' ) ) {
				w3tc_flush_post( $page_id );
			}
			if ( function_exists( 'rocket_clean_post' ) ) {
				rocket_clean_post( $page_id );
			}
		} catch ( \Throwable $e ) {
			// Ignore cache plugin purge errors gracefully
		}

		return true;
	}

	/**
	 * Build a standardized raw Elementor element node.
	 */
	public function create_element_node( string $widget_type, array $settings = array(), ?string $custom_id = null ): array {
		$id = $custom_id ?: $this->generate_element_id();

		if ( 'container' === $widget_type ) {
			return array(
				'id'       => $id,
				'elType'   => 'container',
				'settings' => array_merge( array(
					'flex_direction' => 'column',
					'content_width'  => 'boxed',
				), $settings ),
				'elements' => array(),
				'isInner'  => false,
			);
		}

		return array(
			'id'         => $id,
			'elType'     => 'widget',
			'widgetType' => $widget_type,
			'settings'   => $settings,
			'elements'   => array(),
		);
	}

	/**
	 * Add an element node to a page or parent element.
	 */
	public function add_element( int $page_id, array $new_node, ?string $parent_id = null, int $position = -1 ): array {
		$elements = $this->get_raw_page_elements( $page_id );

		if ( empty( $parent_id ) ) {
			if ( $position >= 0 && $position < count( $elements ) ) {
				array_splice( $elements, $position, 0, array( $new_node ) );
			} else {
				$elements[] = $new_node;
			}
		} else {
			$elements = $this->insert_into_parent( $elements, $parent_id, $new_node, $position );
		}

		$this->save_page_elements( $page_id, $elements );

		return array(
			'success'    => true,
			'element_id' => $new_node['id'],
			'parent_id'  => $parent_id,
			'node'       => $new_node,
		);
	}

	/**
	 * Recursive insert node into parent.
	 */
	private function insert_into_parent( array $elements, string $parent_id, array $new_node, int $position ): array {
		foreach ( $elements as &$el ) {
			if ( isset( $el['id'] ) && $el['id'] === $parent_id ) {
				if ( ! isset( $el['elements'] ) || ! is_array( $el['elements'] ) ) {
					$el['elements'] = array();
				}
				if ( $position >= 0 && $position < count( $el['elements'] ) ) {
					array_splice( $el['elements'], $position, 0, array( $new_node ) );
				} else {
					$el['elements'][] = $new_node;
				}
				return $elements;
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$el['elements'] = $this->insert_into_parent( $el['elements'], $parent_id, $new_node, $position );
			}
		}
		return $elements;
	}

	/**
	 * Update settings of an existing element node.
	 */
	public function update_element( int $page_id, string $element_id, array $new_settings ): bool {
		$elements = $this->get_raw_page_elements( $page_id );
		$updated = false;

		$elements = $this->update_in_tree( $elements, $element_id, $new_settings, $updated );
		if ( $updated ) {
			$this->save_page_elements( $page_id, $elements );
			return true;
		}
		return false;
	}

	private function update_in_tree( array $elements, string $element_id, array $new_settings, bool &$updated ): array {
		foreach ( $elements as &$el ) {
			if ( isset( $el['id'] ) && $el['id'] === $element_id ) {
				if ( ! isset( $el['settings'] ) || ! is_array( $el['settings'] ) ) {
					$el['settings'] = array();
				}
				$el['settings'] = array_merge( $el['settings'], $new_settings );
				$updated = true;
				return $elements;
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$el['elements'] = $this->update_in_tree( $el['elements'], $element_id, $new_settings, $updated );
			}
		}
		return $elements;
	}

	/**
	 * Delete an element node from page tree.
	 */
	public function delete_element( int $page_id, string $element_id ): bool {
		$elements = $this->get_raw_page_elements( $page_id );
		$deleted = false;

		$elements = $this->delete_from_tree( $elements, $element_id, $deleted );
		if ( $deleted ) {
			$this->save_page_elements( $page_id, $elements );
			return true;
		}
		return false;
	}

	private function delete_from_tree( array $elements, string $element_id, bool &$deleted ): array {
		$filtered = array();
		foreach ( $elements as $el ) {
			if ( isset( $el['id'] ) && $el['id'] === $element_id ) {
				$deleted = true;
				continue;
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$el['elements'] = $this->delete_from_tree( $el['elements'], $element_id, $deleted );
			}
			$filtered[] = $el;
		}
		return $filtered;
	}

	/**
	 * Duplicate an element node recursively with new unique IDs.
	 */
	public function duplicate_element( int $page_id, string $element_id ): ?array {
		$elements = $this->get_raw_page_elements( $page_id );
		$target_node = $this->find_raw_node( $elements, $element_id );
		if ( ! $target_node ) {
			return null;
		}

		$cloned_node = $this->reassign_element_ids_recursively( $target_node );
		$this->add_element( $page_id, $cloned_node );

		return $cloned_node;
	}

	private function find_raw_node( array $elements, string $element_id ): ?array {
		foreach ( $elements as $el ) {
			if ( isset( $el['id'] ) && $el['id'] === $element_id ) {
				return $el;
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				$found = $this->find_raw_node( $el['elements'], $element_id );
				if ( $found ) {
					return $found;
				}
			}
		}
		return null;
	}

	private function reassign_element_ids_recursively( array $node ): array {
		$node['id'] = $this->generate_element_id();
		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			$new_children = array();
			foreach ( $node['elements'] as $child ) {
				$new_children[] = $this->reassign_element_ids_recursively( $child );
			}
			$node['elements'] = $new_children;
		}
		return $node;
	}

	/**
	 * Move an element to a new parent or new position index.
	 */
	public function move_element( int $page_id, string $element_id, ?string $new_parent_id, int $position = -1 ): bool {
		$elements = $this->get_raw_page_elements( $page_id );
		$target_node = $this->find_raw_node( $elements, $element_id );
		if ( ! $target_node ) {
			return false;
		}

		// Remove from old position
		$deleted = false;
		$elements = $this->delete_from_tree( $elements, $element_id, $deleted );
		if ( ! $deleted ) {
			return false;
		}

		// Insert into new position
		if ( empty( $new_parent_id ) ) {
			if ( $position >= 0 && $position < count( $elements ) ) {
				array_splice( $elements, $position, 0, array( $target_node ) );
			} else {
				$elements[] = $target_node;
			}
		} else {
			$elements = $this->insert_into_parent( $elements, $new_parent_id, $target_node, $position );
		}

		$this->save_page_elements( $page_id, $elements );
		return true;
	}
}
