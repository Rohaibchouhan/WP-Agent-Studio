<?php

namespace AiElementorAgent\MCP\Tools;

use AiElementorAgent\MCP\AbstractTool;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WordPressDirectPostTool
 * Direct CRUD tool for WordPress posts and pages without requiring heavy Elementor data.
 */
class WordPressDirectPostTool extends AbstractTool
{
    public function get_name(): string
    {
        return 'wordpress_direct_post';
    }

    public function get_description(): string
    {
        return 'Direct low-token post, page, and CPT management (create, update, publish, draft, trash, list) without requiring heavy Elementor JSON overhead.';
    }

    public function get_schema(): array
    {
        return [
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'action' => [
                        'type'        => 'string',
                        'enum'        => ['create', 'update', 'delete', 'trash', 'get', 'list'],
                        'description' => 'Operation to perform.',
                    ],
                    'post_id' => [
                        'type'        => 'integer',
                        'description' => 'Target post ID (for update, delete, trash, get).',
                    ],
                    'title' => [
                        'type'        => 'string',
                        'description' => 'Post title.',
                    ],
                    'content' => [
                        'type'        => 'string',
                        'description' => 'Post HTML or plain text content.',
                    ],
                    'excerpt' => [
                        'type'        => 'string',
                        'description' => 'Post excerpt.',
                    ],
                    'post_type' => [
                        'type'        => 'string',
                        'description' => 'Post type slug (default: "post", or "page", or custom CPT).',
                    ],
                    'status' => [
                        'type'        => 'string',
                        'enum'        => ['publish', 'draft', 'pending', 'private', 'future'],
                        'description' => 'Post status (default: "publish").',
                    ],
                    'categories' => [
                        'type'        => 'array',
                        'items'       => ['type' => 'integer'],
                        'description' => 'Category IDs.',
                    ],
                    'tags' => [
                        'type'        => 'array',
                        'items'       => ['type' => 'string'],
                        'description' => 'Tag names.',
                    ],
                ],
                'required'   => ['action'],
            ],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $action = $params['action'] ?? '';

        if (!current_user_can('edit_posts')) {
            return $this->error('Permission denied. Capability edit_posts required.');
        }

        switch ($action) {
            case 'create':
                return $this->create_post($params);

            case 'update':
                return $this->update_post($params);

            case 'trash':
            case 'delete':
                return $this->trash_post($params);

            case 'get':
                return $this->get_single_post($params);

            case 'list':
                return $this->list_posts($params);

            default:
                return $this->error('Invalid action.');
        }
    }

    private function create_post(array $params): array
    {
        $title     = sanitize_text_field($params['title'] ?? 'Untitled Post');
        $content   = wp_kses_post($params['content'] ?? '');
        $excerpt   = sanitize_text_field($params['excerpt'] ?? '');
        $post_type = sanitize_key($params['post_type'] ?? 'post');
        $status    = sanitize_key($params['status'] ?? 'publish');

        $post_data = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_type'    => $post_type,
            'post_status'  => $status,
        ];

        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            return $this->error($post_id->get_error_message());
        }

        if (!empty($params['categories']) && is_array($params['categories'])) {
            wp_set_post_categories($post_id, array_map('intval', $params['categories']));
        }
        if (!empty($params['tags']) && is_array($params['tags'])) {
            wp_set_post_tags($post_id, array_map('sanitize_text_field', $params['tags']));
        }

        return $this->success([
            'message'   => 'Post created successfully.',
            'post_id'   => $post_id,
            'permalink' => get_permalink($post_id),
            'edit_url'  => get_edit_post_link($post_id, 'raw'),
        ]);
    }

    private function update_post(array $params): array
    {
        $post_id = (int) ($params['post_id'] ?? 0);
        if (!$post_id || !get_post($post_id)) {
            return $this->error('Invalid or missing post_id for update.');
        }

        $post_data = ['ID' => $post_id];
        if (isset($params['title']))   $post_data['post_title']   = sanitize_text_field($params['title']);
        if (isset($params['content'])) $post_data['post_content'] = wp_kses_post($params['content']);
        if (isset($params['excerpt'])) $post_data['post_excerpt'] = sanitize_text_field($params['excerpt']);
        if (isset($params['status']))  $post_data['post_status']  = sanitize_key($params['status']);

        $updated_id = wp_update_post($post_data);
        if (is_wp_error($updated_id)) {
            return $this->error($updated_id->get_error_message());
        }

        if (isset($params['categories']) && is_array($params['categories'])) {
            wp_set_post_categories($post_id, array_map('intval', $params['categories']));
        }
        if (isset($params['tags']) && is_array($params['tags'])) {
            wp_set_post_tags($post_id, array_map('sanitize_text_field', $params['tags']));
        }

        return $this->success([
            'message'   => 'Post updated successfully.',
            'post_id'   => $post_id,
            'permalink' => get_permalink($post_id),
        ]);
    }

    private function trash_post(array $params): array
    {
        $post_id = (int) ($params['post_id'] ?? 0);
        if (!$post_id || !get_post($post_id)) {
            return $this->error('Invalid or missing post_id.');
        }

        $force = !empty($params['force_delete']);
        $res   = $force ? wp_delete_post($post_id, true) : wp_trash_post($post_id);

        if (!$res) {
            return $this->error('Failed to trash/delete post ID ' . $post_id);
        }

        return $this->success([
            'message' => $force ? 'Post permanently deleted.' : 'Post moved to trash.',
            'post_id' => $post_id,
        ]);
    }

    private function get_single_post(array $params): array
    {
        $post_id = (int) ($params['post_id'] ?? 0);
        $post    = get_post($post_id);

        if (!$post) {
            return $this->error('Post not found with ID ' . $post_id);
        }

        return $this->success([
            'post' => [
                'ID'           => $post->ID,
                'title'        => $post->post_title,
                'content'      => $post->post_content,
                'excerpt'      => $post->post_excerpt,
                'status'       => $post->post_status,
                'type'         => $post->post_type,
                'date'         => $post->post_date,
                'permalink'    => get_permalink($post->ID),
                'author_id'    => $post->post_author,
            ],
        ]);
    }

    private function list_posts(array $params): array
    {
        $post_type = sanitize_key($params['post_type'] ?? 'post');
        $status    = sanitize_key($params['status'] ?? 'publish');

        $query = new \WP_Query([
            'post_type'      => $post_type,
            'post_status'    => $status,
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $posts = [];
        foreach ($query->posts as $p) {
            $posts[] = [
                'ID'        => $p->ID,
                'title'     => $p->post_title,
                'status'    => $p->post_status,
                'date'      => $p->post_date,
                'permalink' => get_permalink($p->ID),
            ];
        }

        return $this->success([
            'count' => count($posts),
            'posts' => $posts,
        ]);
    }
}
