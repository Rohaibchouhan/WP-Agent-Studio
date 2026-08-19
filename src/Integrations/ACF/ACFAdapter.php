<?php

namespace AiElementorAgent\Integrations\ACF;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ACFAdapter
 * Discovers ACF Field Groups, registers custom fields, and binds dynamic ACF tags into Elementor widgets.
 */
class ACFAdapter
{
    /**
     * Check if Advanced Custom Fields is active.
     */
    public function is_active(): bool
    {
        return function_exists('acf_get_field_groups');
    }

    /**
     * Get list of all ACF Field Groups and their fields.
     */
    public function get_field_groups(): array
    {
        if (!$this->is_active()) {
            return [
                'active'  => false,
                'message' => 'Advanced Custom Fields (ACF) is not currently active.',
            ];
        }

        $groups = acf_get_field_groups();
        $result = [];

        foreach ($groups as $group) {
            $fields = acf_get_fields($group['key']);
            $field_list = [];
            if ($fields) {
                foreach ($fields as $f) {
                    $field_list[] = [
                        'key'   => $f['key'],
                        'name'  => $f['name'],
                        'label' => $f['label'],
                        'type'  => $f['type'],
                    ];
                }
            }

            $result[] = [
                'key'    => $group['key'],
                'title'  => $group['title'],
                'fields' => $field_list,
            ];
        }

        return [
            'active' => true,
            'groups' => $result,
        ];
    }

    /**
     * Register a new ACF field group dynamically.
     */
    public function register_field_group(string $title, array $fields, array $location_post_type = ['page']): bool
    {
        if (!function_exists('acf_add_local_field_group')) {
            return false;
        }

        $group_key = 'group_agent_' . sanitize_key($title);
        $acf_fields = [];

        foreach ($fields as $idx => $f) {
            $acf_fields[] = [
                'key'   => 'field_' . sanitize_key($title) . '_' . sanitize_key($f['name']),
                'label' => sanitize_text_field($f['label'] ?? $f['name']),
                'name'  => sanitize_key($f['name']),
                'type'  => sanitize_key($f['type'] ?? 'text'),
            ];
        }

        acf_add_local_field_group([
            'key'      => $group_key,
            'title'    => sanitize_text_field($title),
            'fields'   => $acf_fields,
            'location' => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => $location_post_type[0] ?? 'page',
                    ],
                ],
            ],
        ]);

        return true;
    }
}
