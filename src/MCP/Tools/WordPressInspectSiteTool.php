<?php

namespace WP\AgentStudio\MCP\Tools;

use WP\AgentStudio\MCP\AbstractTool;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WordPressInspectSiteTool
 * Compact site diagnostic returning WP version, active theme, plugins, Elementor status, CPTs, and post counts in < 300 tokens.
 */
class WordPressInspectSiteTool extends AbstractTool
{
    public function get_name(): string
    {
        return 'wordpress_inspect_site';
    }

    public function get_description(): string
    {
        return 'Get a compact structural overview of the WordPress site (theme, plugins, Elementor status, post counts, CPTs) in minimal tokens.';
    }

    public function get_schema(): array
    {
        return [
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'include_plugins' => [
                        'type'        => 'boolean',
                        'description' => 'Include active/inactive plugin details (default: true)',
                    ],
                ],
            ],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        if (!current_user_can('edit_pages')) {
            return $this->error('Permission denied. Capability edit_pages required.');
        }

        $include_plugins = $params['include_plugins'] ?? true;
        $theme = wp_get_theme();

        $active_plugins = get_option('active_plugins', []);
        $all_plugins = function_exists('get_plugins') ? get_plugins() : [];

        $plugin_summary = [];
        if ($include_plugins) {
            foreach ($all_plugins as $file => $info) {
                $is_active = in_array($file, $active_plugins, true);
                $plugin_summary[] = [
                    'file'    => $file,
                    'name'    => $info['Name'] ?? $file,
                    'version' => $info['Version'] ?? 'unknown',
                    'active'  => $is_active,
                ];
            }
        }

        $cpts = get_post_types(['public' => true], 'names');
        $counts = [
            'pages' => wp_count_posts('page')->publish ?? 0,
            'posts' => wp_count_posts('post')->publish ?? 0,
        ];

        $elementor_active = defined('ELEMENTOR_VERSION');
        $elementor_pro    = defined('ELEMENTOR_PRO_VERSION');

        return $this->success([
            'site_name'         => get_bloginfo('name'),
            'site_url'          => get_site_url(),
            'wp_version'        => get_bloginfo('version'),
            'theme'             => [
                'name'    => $theme->get('Name'),
                'version' => $theme->get('Version'),
            ],
            'elementor'         => [
                'active'  => $elementor_active,
                'version' => $elementor_active ? ELEMENTOR_VERSION : null,
                'pro'     => $elementor_pro ? ELEMENTOR_PRO_VERSION : false,
            ],
            'public_post_types' => array_values($cpts),
            'post_counts'       => $counts,
            'plugins_count'     => [
                'active' => count($active_plugins),
                'total'  => count($all_plugins),
            ],
            'plugins'           => $plugin_summary,
        ]);
    }
}
