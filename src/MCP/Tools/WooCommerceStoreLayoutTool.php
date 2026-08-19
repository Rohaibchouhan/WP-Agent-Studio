<?php

namespace AiElementorAgent\MCP\Tools;

use AiElementorAgent\MCP\AbstractTool;
use AiElementorAgent\Elementor\ElementorWriter;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WooCommerceStoreLayoutTool
 * Generates Elementor Shop Grids, Product Carousels, and Checkout sections.
 */
class WooCommerceStoreLayoutTool extends AbstractTool
{
    public function get_name(): string
    {
        return 'woocommerce_store_layout';
    }

    public function get_description(): string
    {
        return 'Build Elementor shop pages, product grids, product carousels, and store layouts.';
    }

    public function get_schema(): array
    {
        return [
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'page_id' => [
                        'type'        => 'integer',
                        'description' => 'Target page ID for store section insertion.',
                    ],
                    'layout_type' => [
                        'type'        => 'string',
                        'enum'        => ['product_grid', 'product_carousel', 'category_grid'],
                        'description' => 'E-Commerce store layout block type.',
                    ],
                    'columns' => [
                        'type'        => 'integer',
                        'description' => 'Grid column count (2, 3, 4, 6). Default: 4.',
                    ],
                    'posts_per_page' => [
                        'type'        => 'integer',
                        'description' => 'Number of products to display.',
                    ],
                ],
                'required'   => ['page_id', 'layout_type'],
            ],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $page_id     = (int) ($params['page_id'] ?? 0);
        $layout_type = sanitize_text_field($params['layout_type'] ?? 'product_grid');
        $columns     = (int) ($params['columns'] ?? 4);
        $limit       = (int) ($params['posts_per_page'] ?? 8);

        if (!$page_id || !get_post($page_id)) {
            return $this->error('Invalid target page_id.');
        }

        if (!current_user_can('edit_pages')) {
            return $this->error('Permission denied. Capability edit_pages required.');
        }

        $writer = new ElementorWriter();
        $element_data = [
            'id'         => ElementorWriter::generate_id(),
            'elType'     => 'widget',
            'widgetType' => 'woocommerce-products',
            'settings'   => [
                'columns'        => (string) $columns,
                'posts_per_page' => (string) $limit,
                'query_post_type' => 'by_id',
            ],
            'elements'   => [],
        ];

        $new_id = $writer->insert_element($page_id, null, $element_data);

        return $this->success([
            'message'     => 'WooCommerce store layout widget inserted into Elementor page.',
            'element_id'  => $new_id,
            'page_id'     => $page_id,
            'layout_type' => $layout_type,
        ]);
    }
}
