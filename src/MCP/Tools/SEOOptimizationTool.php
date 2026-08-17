<?php

namespace WP\AgentStudio\MCP\Tools;

use WP\AgentStudio\MCP\AbstractTool;
use WP\AgentStudio\Integrations\SEO\SEOAdapter;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SEOOptimizationTool
 * Sets SEO meta titles, descriptions, focus keywords, and generates JSON-LD schema markup.
 */
class SEOOptimizationTool extends AbstractTool
{
    public function get_name(): string
    {
        return 'seo_optimization';
    }

    public function get_description(): string
    {
        return 'Optimize post/page SEO (meta titles, meta descriptions, focus keywords) and inject structured JSON-LD schema markup (RankMath, Yoast, AIOSEO).';
    }

    public function get_schema(): array
    {
        return [
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'action' => [
                        'type'        => 'string',
                        'enum'        => ['set_meta', 'generate_schema'],
                        'description' => 'Operation to perform.',
                    ],
                    'post_id' => [
                        'type'        => 'integer',
                        'description' => 'Target post or page ID.',
                    ],
                    'title' => [
                        'type'        => 'string',
                        'description' => 'SEO Meta Title.',
                    ],
                    'description' => [
                        'type'        => 'string',
                        'description' => 'SEO Meta Description.',
                    ],
                    'focus_keyword' => [
                        'type'        => 'string',
                        'description' => 'Primary Focus Keyword.',
                    ],
                    'schema_type' => [
                        'type'        => 'string',
                        'enum'        => ['Article', 'Product', 'Organization', 'LocalBusiness', 'FAQPage'],
                        'description' => 'JSON-LD Schema type.',
                    ],
                    'schema_data' => [
                        'type'        => 'object',
                        'description' => 'Schema properties object.',
                    ],
                ],
                'required'   => ['action'],
            ],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $action  = sanitize_text_field($params['action'] ?? '');
        $adapter = new SEOAdapter();

        if (!current_user_can('edit_pages')) {
            return $this->error('Permission denied. Capability edit_pages required.');
        }

        if ($action === 'set_meta') {
            $post_id = (int) ($params['post_id'] ?? 0);
            if (!$post_id || !get_post($post_id)) {
                return $this->error('Invalid target post_id.');
            }

            $res = $adapter->set_post_seo($post_id, [
                'title'         => $params['title'] ?? '',
                'description'   => $params['description'] ?? '',
                'focus_keyword' => $params['focus_keyword'] ?? '',
            ]);

            return $this->success($res);
        }

        if ($action === 'generate_schema') {
            $type = sanitize_text_field($params['schema_type'] ?? 'Organization');
            $data = $params['schema_data'] ?? [];

            $json_ld = $adapter->generate_json_ld_schema($type, $data);

            $post_id = (int) ($params['post_id'] ?? 0);
            if ($post_id && get_post($post_id)) {
                update_post_meta($post_id, '_wp_agent_json_ld_schema', $json_ld);
            }

            return $this->success([
                'schema_type' => $type,
                'json_ld'     => $json_ld,
                'saved_to'    => $post_id ?: null,
            ]);
        }

        return $this->error('Invalid SEO action.');
    }
}
