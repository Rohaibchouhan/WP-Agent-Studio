<?php

namespace WP\AgentStudio\MCP\Tools;

use WP\AgentStudio\MCP\AbstractTool;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WordPressManagePluginsTool
 * Allows AI agents to search, install, activate, and deactivate plugins.
 */
class WordPressManagePluginsTool extends AbstractTool
{
    public function get_name(): string
    {
        return 'wordpress_manage_plugins';
    }

    public function get_description(): string
    {
        return 'Search WordPress.org plugins, install plugins, activate, deactivate, or check plugin status.';
    }

    public function get_schema(): array
    {
        return [
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'action' => [
                        'type'        => 'string',
                        'enum'        => ['search', 'install', 'activate', 'deactivate', 'list'],
                        'description' => 'Plugin action to perform.',
                    ],
                    'slug' => [
                        'type'        => 'string',
                        'description' => 'Plugin slug for search/install/activate/deactivate (e.g., "elementor", "woocommerce").',
                    ],
                    'plugin_file' => [
                        'type'        => 'string',
                        'description' => 'Relative path to main plugin file for activate/deactivate (e.g. "elementor/elementor.php").',
                    ],
                    'query' => [
                        'type'        => 'string',
                        'description' => 'Search query for plugins on WordPress.org.',
                    ],
                ],
                'required'   => ['action'],
            ],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $action = $params['action'] ?? '';

        if (!current_user_can('install_plugins') || !current_user_can('activate_plugins')) {
            return $this->error('Permission denied. Capabilities install_plugins and activate_plugins required.');
        }

        switch ($action) {
            case 'list':
                return $this->list_plugins();

            case 'search':
                $query = $params['query'] ?? $params['slug'] ?? '';
                return $this->search_plugins($query);

            case 'activate':
                $file = $params['plugin_file'] ?? '';
                if (empty($file) && !empty($params['slug'])) {
                    $file = $this->find_plugin_file_by_slug($params['slug']);
                }
                return $this->activate_plugin_file($file);

            case 'deactivate':
                $file = $params['plugin_file'] ?? '';
                if (empty($file) && !empty($params['slug'])) {
                    $file = $this->find_plugin_file_by_slug($params['slug']);
                }
                return $this->deactivate_plugin_file($file);

            case 'install':
                $slug = $params['slug'] ?? '';
                return $this->install_plugin_by_slug($slug);

            default:
                return $this->error('Invalid plugin action.');
        }
    }

    private function list_plugins(): array
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all = get_plugins();
        $active = get_option('active_plugins', []);

        $list = [];
        foreach ($all as $file => $data) {
            $list[] = [
                'plugin_file' => $file,
                'slug'        => dirname($file),
                'name'        => $data['Name'],
                'version'     => $data['Version'],
                'active'      => in_array($file, $active, true),
            ];
        }
        return $this->success(['plugins' => $list]);
    }

    private function search_plugins(string $query): array
    {
        if (empty($query)) {
            return $this->error('Search query cannot be empty.');
        }

        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        $api = plugins_api('query_plugins', [
            'search'   => $query,
            'page'     => 1,
            'per_page' => 10,
            'fields'   => [
                'short_description' => true,
                'icons'             => false,
                'sections'          => false,
            ],
        ]);

        if (is_wp_error($api)) {
            return $this->error($api->get_error_message());
        }

        $results = [];
        if (!empty($api->plugins)) {
            foreach ($api->plugins as $p) {
                $results[] = [
                    'name'              => $p->name,
                    'slug'              => $p->slug,
                    'version'           => $p->version,
                    'rating'            => $p->rating,
                    'downloaded'        => $p->downloaded,
                    'short_description' => strip_tags($p->short_description),
                ];
            }
        }

        return $this->success(['query' => $query, 'results' => $results]);
    }

    private function activate_plugin_file(string $file): array
    {
        if (empty($file)) {
            return $this->error('Plugin file path or slug required for activation.');
        }
        if (!function_exists('activate_plugin')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $result = activate_plugin($file);
        if (is_wp_error($result)) {
            return $this->error($result->get_error_message());
        }

        return $this->success([
            'message'     => 'Plugin activated successfully.',
            'plugin_file' => $file,
        ]);
    }

    private function deactivate_plugin_file(string $file): array
    {
        if (empty($file)) {
            return $this->error('Plugin file path or slug required for deactivation.');
        }
        if (!function_exists('deactivate_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        deactivate_plugins($file);
        return $this->success([
            'message'     => 'Plugin deactivated successfully.',
            'plugin_file' => $file,
        ]);
    }

    private function install_plugin_by_slug(string $slug): array
    {
        if (empty($slug)) {
            return $this->error('Plugin slug required for installation.');
        }

        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $api = plugins_api('plugin_information', ['slug' => $slug, 'fields' => ['sections' => false]]);
        if (is_wp_error($api)) {
            return $this->error($api->get_error_message());
        }

        $skin     = new \Automatic_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader($skin);
        $result   = $upgrader->install($api->download_link);

        if (is_wp_error($result)) {
            return $this->error($result->get_error_message());
        }

        $installed_file = $this->find_plugin_file_by_slug($slug);

        return $this->success([
            'message'     => 'Plugin installed successfully.',
            'slug'        => $slug,
            'plugin_file' => $installed_file,
        ]);
    }

    private function find_plugin_file_by_slug(string $slug): string
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        foreach ($plugins as $file => $data) {
            if (dirname($file) === $slug || $file === $slug || strpos($file, $slug . '/') === 0) {
                return $file;
            }
        }
        return $slug . '/' . $slug . '.php';
    }
}
