<?php

namespace AiElementorAgent\Abilities;

use AiElementorAgent\Security\AccessControlManager;
use AiElementorAgent\MCP\ToolRegistry;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AbilityBridge
 * Bridges WP Agent Studio MCP tools with the official WordPress Abilities API (`WordPress/mcp-adapter`).
 */
class AbilityBridge
{
    private ToolRegistry $tool_registry;

    public function __construct(ToolRegistry $tool_registry)
    {
        $this->tool_registry = $tool_registry;
    }

    /**
     * Register hooks for WordPress Abilities API compatibility.
     */
    public function register(): void
    {
        add_action('wp_abilities_api_categories_init', [$this, 'register_categories']);
        add_action('wp_abilities_api_init', [$this, 'register_abilities']);
    }

    /**
     * Register ability categories.
     */
    public function register_categories(): void
    {
        if (!function_exists('wp_register_ability_category')) {
            return;
        }

        $categories = [
            'wp-agent-site'     => [
                'label'       => __('WP Agent Site Diagnostics', 'wp-agent-studio'),
                'description' => __('Low-token site diagnostic and structural abilities.', 'wp-agent-studio'),
            ],
            'wp-agent-plugins'  => [
                'label'       => __('WP Agent Plugin Management', 'wp-agent-studio'),
                'description' => __('Plugin search, installation, activation, and deactivation abilities.', 'wp-agent-studio'),
            ],
            'wp-agent-posts'    => [
                'label'       => __('WP Agent Direct Content Control', 'wp-agent-studio'),
                'description' => __('Direct CRUD abilities for standard WordPress posts and pages.', 'wp-agent-studio'),
            ],
            'wp-agent-elementor' => [
                'label'       => __('WP Agent Elementor & Atomic Suite', 'wp-agent-studio'),
                'description' => __('Elementor Flexbox, Widget, and Atomic 4.0 elements building abilities.', 'wp-agent-studio'),
            ],
            'wp-agent-code'     => [
                'label'       => __('WP Agent Custom Code & Snippets', 'wp-agent-studio'),
                'description' => __('Per-widget CSS, page CSS, and custom code injection abilities.', 'wp-agent-studio'),
            ],
            'wp-agent-stock'    => [
                'label'       => __('WP Agent Stock Media Sideloading', 'wp-agent-studio'),
                'description' => __('Unsplash, Pexels, and Pixabay stock media abilities.', 'wp-agent-studio'),
            ],
            'wp-agent-woocommerce' => [
                'label'       => __('WP Agent WooCommerce Ecosystem', 'wp-agent-studio'),
                'description' => __('WooCommerce product and store layout management abilities.', 'wp-agent-studio'),
            ],
            'wp-agent-acf'        => [
                'label'       => __('WP Agent ACF & Dynamic Content', 'wp-agent-studio'),
                'description' => __('ACF field group registration and dynamic tag binding abilities.', 'wp-agent-studio'),
            ],
            'wp-agent-forms'      => [
                'label'       => __('WP Agent Universal Forms Adapter', 'wp-agent-studio'),
                'description' => __('Elementor Forms, Fluent, WPForms, and Gravity Forms building abilities.', 'wp-agent-studio'),
            ],
            'wp-agent-seo'        => [
                'label'       => __('WP Agent SEO & JSON-LD Schema', 'wp-agent-studio'),
                'description' => __('Meta titles, descriptions, and structured JSON-LD schema abilities.', 'wp-agent-studio'),
            ],
        ];

        foreach ($categories as $slug => $args) {
            wp_register_ability_category($slug, $args);
        }
    }

    /**
     * Register all enabled tools as official WordPress Abilities.
     */
    public function register_abilities(): void
    {
        if (!function_exists('wp_register_ability')) {
            return;
        }

        $all_tools = $this->tool_registry->get_all_tools();

        foreach ($all_tools as $tool) {
            $name        = $tool->get_name();
            $category    = $this->map_tool_category($name);
            $perm_module = $this->map_tool_permission_module($name);

            // Honor WP Admin Access Control toggles
            if ($perm_module && !AccessControlManager::is_enabled($perm_module)) {
                continue;
            }

            $schema = $tool->get_schema();

            wp_register_ability('wp-agent-studio/' . strtolower(str_replace('_', '-', $name)), [
                'label'               => $name,
                'description'         => $tool->get_description(),
                'category'            => $category,
                'input_schema'        => $schema['input_schema'] ?? ['type' => 'object', 'properties' => (object)[]],
                'execute_callback'    => function ($params) use ($tool) {
                    return $tool->execute(is_array($params) ? $params : [], []);
                },
                'permission_callback' => function () use ($name) {
                    if (strpos($name, 'plugin') !== false) {
                        return current_user_can('install_plugins') && current_user_can('activate_plugins');
                    }
                    if (strpos($name, 'custom_code') !== false) {
                        return current_user_can('unfiltered_html');
                    }
                    if (strpos($name, 'woocommerce') !== false) {
                        return current_user_can('edit_products') || current_user_can('manage_options');
                    }
                    return current_user_can('edit_pages');
                },
                'meta' => [
                    'public' => true,
                    'mcp'    => [
                        'type' => 'tool',
                    ],
                ],
            ]);
        }
    }

    /**
     * Map tool name to category slug.
     */
    private function map_tool_category(string $name): string
    {
        if (strpos($name, 'inspect') !== false || strpos($name, 'site_info') !== false) {
            return 'wp-agent-site';
        }
        if (strpos($name, 'plugin') !== false) {
            return 'wp-agent-plugins';
        }
        if (strpos($name, 'post') !== false || strpos($name, 'media') !== false) {
            return 'wp-agent-posts';
        }
        if (strpos($name, 'custom_code') !== false) {
            return 'wp-agent-code';
        }
        if (strpos($name, 'stock') !== false) {
            return 'wp-agent-stock';
        }
        if (strpos($name, 'woocommerce') !== false) {
            return 'wp-agent-woocommerce';
        }
        if (strpos($name, 'acf') !== false) {
            return 'wp-agent-acf';
        }
        if (strpos($name, 'forms') !== false) {
            return 'wp-agent-forms';
        }
        if (strpos($name, 'seo') !== false) {
            return 'wp-agent-seo';
        }
        return 'wp-agent-elementor';
    }

    /**
     * Map tool name to permission module in AccessControlManager.
     */
    private function map_tool_permission_module(string $name): string
    {
        if (strpos($name, 'inspect') !== false) return 'site_inspection';
        if (strpos($name, 'plugin') !== false) return 'plugin_management';
        if (strpos($name, 'wordpress_') !== false || strpos($name, 'direct_post') !== false) return 'direct_wp_posts';
        if (strpos($name, 'atomic') !== false) return 'elementor_atomic';
        if (strpos($name, 'composite') !== false) return 'elementor_composite';
        if (strpos($name, 'custom_code') !== false) return 'custom_code';
        if (strpos($name, 'stock') !== false) return 'stock_images';
        if (strpos($name, 'global_') !== false) return 'elementor_global_styles';
        if (strpos($name, 'woocommerce') !== false) return 'woocommerce_integration';
        if (strpos($name, 'acf') !== false) return 'acf_integration';
        if (strpos($name, 'forms') !== false) return 'forms_integration';
        if (strpos($name, 'seo') !== false) return 'seo_integration';
        return 'elementor_widgets';
    }
}
