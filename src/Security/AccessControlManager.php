<?php

namespace AiElementorAgent\Security;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AccessControlManager
 * Manages granular MCP tool permissions and stock photo API keys.
 */
class AccessControlManager
{
    const OPTION_PERMISSIONS = 'wp_agent_mcp_permissions';
    const OPTION_API_KEYS    = 'wp_agent_stock_api_keys';

    /**
     * Default permissions matrix.
     */
    public static function get_default_permissions(): array
    {
        return [
            'elementor_widgets'       => true,
            'elementor_atomic'        => true,
            'elementor_layout'        => true,
            'elementor_composite'     => true,
            'elementor_global_styles' => true,
            'plugin_management'       => true,
            'direct_wp_posts'         => true,
            'custom_code'             => true,
            'stock_images'            => true,
            'site_inspection'         => true,
            'woocommerce_integration' => true,
            'acf_integration'         => true,
            'forms_integration'       => true,
            'seo_integration'         => true,
        ];
    }

    /**
     * Get stored permissions merged with defaults.
     */
    public static function get_permissions(): array
    {
        $stored = get_option(self::OPTION_PERMISSIONS, []);
        if (!is_array($stored)) {
            $stored = [];
        }
        return array_merge(self::get_default_permissions(), $stored);
    }

    /**
     * Check if a specific module permission is enabled.
     */
    public static function is_enabled(string $module): bool
    {
        $permissions = self::get_permissions();
        return !empty($permissions[$module]);
    }

    /**
     * Save updated permissions.
     */
    public static function save_permissions(array $permissions): void
    {
        $defaults = self::get_default_permissions();
        $clean = [];
        foreach ($defaults as $key => $default) {
            $clean[$key] = !empty($permissions[$key]);
        }
        update_option(self::OPTION_PERMISSIONS, $clean);
    }

    /**
     * Get stock API keys (Unsplash, Pexels, Pixabay).
     */
    public static function get_api_keys(): array
    {
        $keys = get_option(self::OPTION_API_KEYS, []);
        return [
            'unsplash' => isset($keys['unsplash']) ? sanitize_text_field($keys['unsplash']) : '',
            'pexels'   => isset($keys['pexels'])   ? sanitize_text_field($keys['pexels'])   : '',
            'pixabay'  => isset($keys['pixabay'])  ? sanitize_text_field($keys['pixabay'])  : '',
        ];
    }

    /**
     * Save stock API keys.
     */
    public static function save_api_keys(array $keys): void
    {
        $clean = [
            'unsplash' => isset($keys['unsplash']) ? sanitize_text_field($keys['unsplash']) : '',
            'pexels'   => isset($keys['pexels'])   ? sanitize_text_field($keys['pexels'])   : '',
            'pixabay'  => isset($keys['pixabay'])  ? sanitize_text_field($keys['pixabay'])  : '',
        ];
        update_option(self::OPTION_API_KEYS, $clean);
    }
}
