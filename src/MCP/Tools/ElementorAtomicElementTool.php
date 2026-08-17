<?php

namespace WP\AgentStudio\MCP\Tools;

use WP\AgentStudio\MCP\AbstractTool;
use WP\AgentStudio\Elementor\ElementorWriter;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ElementorAtomicElementTool
 * Handles Elementor 4.0+ typed-props atomic elements with automated `$$type` structural wrapping.
 */
class ElementorAtomicElementTool extends AbstractTool
{
    public function get_name(): string
    {
        return 'elementor_atomic_element';
    }

    public function get_description(): string
    {
        return 'Create or update Elementor 4.0+ typed-props atomic elements (heading, text, button, image, container) with automatic $$type wrapping.';
    }

    public function get_schema(): array
    {
        return [
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'page_id' => [
                        'type'        => 'integer',
                        'description' => 'Target Elementor page ID.',
                    ],
                    'parent_id' => [
                        'type'        => 'string',
                        'description' => 'Parent container ID. Omit to append at page root level.',
                    ],
                    'atomic_type' => [
                        'type'        => 'string',
                        'enum'        => ['atomic_heading', 'atomic_text', 'atomic_button', 'atomic_image', 'atomic_container'],
                        'description' => 'Elementor 4.0 atomic element type.',
                    ],
                    'props' => [
                        'type'        => 'object',
                        'description' => 'Atomic props dictionary (e.g. title, title_tag, content, image_url, link, align).',
                    ],
                ],
                'required'   => ['page_id', 'atomic_type', 'props'],
            ],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $page_id     = (int) ($params['page_id'] ?? 0);
        $parent_id   = sanitize_text_field($params['parent_id'] ?? '');
        $atomic_type = sanitize_text_field($params['atomic_type'] ?? '');
        $props       = $params['props'] ?? [];

        if (!$page_id || !get_post($page_id)) {
            return $this->error('Invalid or missing target page_id.');
        }

        if (!current_user_can('edit_pages')) {
            return $this->error('Permission denied. Capability edit_pages required.');
        }

        // Build Elementor 4.0 Atomic Element structure with $$type wrappers
        $element_data = $this->build_atomic_structure($atomic_type, $props);

        $writer = new ElementorWriter();
        $new_id = $writer->insert_element($page_id, $parent_id ?: null, $element_data);

        if (!$new_id) {
            return $this->error('Failed to insert atomic element into Elementor data tree.');
        }

        return $this->success([
            'message'      => 'Elementor 4.0 atomic element inserted successfully.',
            'element_id'   => $new_id,
            'page_id'      => $page_id,
            'atomic_type'  => $atomic_type,
            'element_url'  => get_permalink($page_id),
        ]);
    }

    /**
     * Wrap atomic props into Elementor 4.0 typed-props structure (`$$type`).
     */
    private function build_atomic_structure(string $type, array $props): array
    {
        $widget_type = str_replace('atomic_', '', $type);
        if ($widget_type === 'container') {
            $widget_type = 'container';
        }

        $wrapped_settings = [];
        foreach ($props as $key => $val) {
            if (is_array($val)) {
                $wrapped_settings[$key] = $val;
            } elseif (is_numeric($val)) {
                $wrapped_settings[$key] = [
                    '$$type' => 'number',
                    'value'  => (float) $val,
                ];
            } elseif (is_bool($val)) {
                $wrapped_settings[$key] = [
                    '$$type' => 'boolean',
                    'value'  => (bool) $val,
                ];
            } else {
                $wrapped_settings[$key] = [
                    '$$type' => 'string',
                    'value'  => (string) $val,
                ];
            }
        }

        if ($widget_type === 'container') {
            return [
                'id'         => ElementorWriter::generate_id(),
                'elType'     => 'container',
                'isInner'    => false,
                'settings'   => array_merge(['content_width' => [ '$$type' => 'string', 'value' => 'full' ]], $wrapped_settings),
                'elements'   => [],
            ];
        }

        return [
            'id'         => ElementorWriter::generate_id(),
            'elType'     => 'widget',
            'widgetType' => $widget_type,
            'isInner'    => false,
            'settings'   => $wrapped_settings,
            'elements'   => [],
        ];
    }
}
