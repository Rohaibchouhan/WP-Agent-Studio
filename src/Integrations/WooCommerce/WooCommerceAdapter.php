<?php

namespace WP\AgentStudio\Integrations\WooCommerce;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WooCommerceAdapter
 * Handles WooCommerce store status, products management, and store section generation.
 */
class WooCommerceAdapter
{
    /**
     * Check if WooCommerce is installed and active.
     */
    public function is_active(): bool
    {
        return class_exists('WooCommerce');
    }

    /**
     * Get store status and metrics.
     */
    public function get_store_status(): array
    {
        if (!$this->is_active()) {
            return [
                'active'  => false,
                'message' => 'WooCommerce is not currently active on this WordPress site.',
            ];
        }

        $product_count = wp_count_posts('product')->publish ?? 0;
        $order_count   = wp_count_posts('shop_order')->wc_completed ?? 0;

        return [
            'active'         => true,
            'wc_version'     => WC()->version,
            'currency'       => get_woocommerce_currency(),
            'currency_symbol'=> get_woocommerce_currency_symbol(),
            'product_count'  => (int) $product_count,
            'order_count'    => (int) $order_count,
        ];
    }

    /**
     * Search or list products.
     */
    public function list_products(array $args = []): array
    {
        if (!$this->is_active()) {
            return [];
        }

        $query_args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $args['limit'] ?? 10,
            's'              => $args['search'] ?? '',
        ];

        $query = new \WP_Query($query_args);
        $products = [];

        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            if ($product) {
                $products[] = [
                    'id'          => $product->get_id(),
                    'name'        => $product->get_name(),
                    'slug'        => $product->get_slug(),
                    'price'       => $product->get_price(),
                    'regular_price' => $product->get_regular_price(),
                    'sale_price'  => $product->get_sale_price(),
                    'sku'         => $product->get_sku(),
                    'stock'       => $product->get_stock_quantity(),
                    'in_stock'    => $product->is_in_stock(),
                    'permalink'   => get_permalink($product->get_id()),
                    'image'       => wp_get_attachment_url($product->get_image_id()),
                ];
            }
        }

        return $products;
    }

    /**
     * Create or update a simple WooCommerce product.
     */
    public function save_product(array $data): array
    {
        if (!$this->is_active()) {
            return ['success' => false, 'message' => 'WooCommerce is not active.'];
        }

        $product_id = isset($data['id']) ? (int) $data['id'] : 0;
        $product = $product_id ? wc_get_product($product_id) : new \WC_Product_Simple();

        if (!$product) {
            $product = new \WC_Product_Simple();
        }

        if (isset($data['name']))          $product->set_name(sanitize_text_field($data['name']));
        if (isset($data['regular_price'])) $product->set_regular_price(sanitize_text_field($data['regular_price']));
        if (isset($data['sale_price']))    $product->set_sale_price(sanitize_text_field($data['sale_price']));
        if (isset($data['description']))   $product->set_description(wp_kses_post($data['description']));
        if (isset($data['short_desc']))     $product->set_short_description(wp_kses_post($data['short_desc']));
        if (isset($data['sku']))            $product->set_sku(sanitize_text_field($data['sku']));
        if (isset($data['image_id']))       $product->set_image_id((int) $data['image_id']);

        $product->set_status('publish');
        $saved_id = $product->save();

        return [
            'success'    => true,
            'product_id' => $saved_id,
            'name'       => $product->get_name(),
            'price'      => $product->get_price(),
            'permalink'  => get_permalink($saved_id),
        ];
    }
}
