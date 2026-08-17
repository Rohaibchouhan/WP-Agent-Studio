<?php

namespace WP\AgentStudio\MCP\Tools;

use WP\AgentStudio\MCP\AbstractTool;
use WP\AgentStudio\Integrations\WooCommerce\WooCommerceAdapter;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WooCommerceProductTool
 * MCP tool for searching, creating, and updating WooCommerce store products.
 */
class WooCommerceProductTool extends AbstractTool
{
    public function get_name(): string
    {
        return 'woocommerce_manage_products';
    }

    public function get_description(): string
    {
        return 'Search, create, or update WooCommerce products (prices, SKUs, inventory, descriptions, images).';
    }

    public function get_schema(): array
    {
        return [
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'action' => [
                        'type'        => 'string',
                        'enum'        => ['status', 'list', 'create', 'update'],
                        'description' => 'Operation to perform.',
                    ],
                    'id' => [
                        'type'        => 'integer',
                        'description' => 'Target product ID for update.',
                    ],
                    'name' => [
                        'type'        => 'string',
                        'description' => 'Product title.',
                    ],
                    'regular_price' => [
                        'type'        => 'string',
                        'description' => 'Regular price (e.g. "49.99").',
                    ],
                    'sale_price' => [
                        'type'        => 'string',
                        'description' => 'Sale price (e.g. "39.99").',
                    ],
                    'sku' => [
                        'type'        => 'string',
                        'description' => 'Product SKU.',
                    ],
                    'description' => [
                        'type'        => 'string',
                        'description' => 'Product description HTML.',
                    ],
                    'search' => [
                        'type'        => 'string',
                        'description' => 'Search keywords for listing products.',
                    ],
                ],
                'required'   => ['action'],
            ],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $action  = sanitize_text_field($params['action'] ?? '');
        $adapter = new WooCommerceAdapter();

        if (!$adapter->is_active()) {
            return $this->error('WooCommerce is not active on this site. Use wordpress_manage_plugins to install and activate WooCommerce.');
        }

        if (!current_user_can('edit_products') && !current_user_can('manage_options')) {
            return $this->error('Permission denied. Capability edit_products required.');
        }

        switch ($action) {
            case 'status':
                return $this->success($adapter->get_store_status());

            case 'list':
                $products = $adapter->list_products(['search' => $params['search'] ?? '', 'limit' => 15]);
                return $this->success(['count' => count($products), 'products' => $products]);

            case 'create':
            case 'update':
                $res = $adapter->save_product($params);
                return $this->success($res);

            default:
                return $this->error('Invalid action.');
        }
    }
}
