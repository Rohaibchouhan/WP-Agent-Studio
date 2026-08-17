<?php

namespace WP\AgentStudio\MCP\Tools;

use WP\AgentStudio\MCP\AbstractTool;
use WP\AgentStudio\Integrations\ACF\ACFAdapter;
use WP\AgentStudio\Elementor\ElementorWriter;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ACFDynamicContentTool
 * Dynamic content field group discovery, field group registration, and ACF tag binding for Elementor widgets.
 */
class ACFDynamicContentTool extends AbstractTool
{
    public function get_name(): string
    {
        return 'acf_dynamic_content';
    }

    public function get_description(): string
    {
        return 'Discover ACF custom fields, register field groups, and bind dynamic ACF tags into Elementor widgets.';
    }

    public function get_schema(): array
    {
        return [
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'action' => [
                        'type'        => 'string',
                        'enum'        => ['list_groups', 'register_group', 'bind_field'],
                        'description' => 'ACF dynamic content operation.',
                    ],
                    'title' => [
                        'type'        => 'string',
                        'description' => 'Field group title for register_group.',
                    ],
                    'fields' => [
                        'type'        => 'array',
                        'description' => 'List of fields for register_group.',
                        'items'       => ['type' => 'object'],
                    ],
                    'page_id' => [
                        'type'        => 'integer',
                        'description' => 'Target Elementor page ID for field binding.',
                    ],
                    'element_id' => [
                        'type'        => 'string',
                        'description' => 'Target Elementor widget ID.',
                    ],
                    'field_name' => [
                        'type'        => 'string',
                        'description' => 'ACF field slug/key to bind.',
                    ],
                ],
                'required'   => ['action'],
            ],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $action  = sanitize_text_field($params['action'] ?? '');
        $adapter = new ACFAdapter();

        if (!$adapter->is_active()) {
            return $this->error('Advanced Custom Fields (ACF) is not active. Use wordpress_manage_plugins to install and activate ACF.');
        }

        if (!current_user_can('edit_pages')) {
            return $this->error('Permission denied. Capability edit_pages required.');
        }

        switch ($action) {
            case 'list_groups':
                return $this->success($adapter->get_field_groups());

            case 'register_group':
                $title  = sanitize_text_field($params['title'] ?? 'Custom Agent Fields');
                $fields = $params['fields'] ?? [];
                $success = $adapter->register_field_group($title, $fields);
                return $this->success(['success' => $success, 'title' => $title]);

            case 'bind_field':
                return $this->bind_acf_to_widget($params);

            default:
                return $this->error('Invalid ACF action.');
        }
    }

    private function bind_acf_to_widget(array $params): array
    {
        $page_id    = (int) ($params['page_id'] ?? 0);
        $element_id = sanitize_text_field($params['element_id'] ?? '');
        $field_name = sanitize_key($params['field_name'] ?? '');

        if (!$page_id || empty($element_id) || empty($field_name)) {
            return $this->error('page_id, element_id, and field_name are required for binding.');
        }

        $writer = new ElementorWriter();
        $updated = $writer->update_element($page_id, $element_id, [
            'title' => '[acf field="' . $field_name . '"]',
            '__dynamic__' => [
                'title' => '[acf field="' . $field_name . '"]',
            ],
        ]);

        if (!$updated) {
            return $this->error('Failed to bind ACF field to element.');
        }

        return $this->success([
            'message'    => 'ACF dynamic tag bound to Elementor widget successfully.',
            'element_id' => $element_id,
            'field_name' => $field_name,
        ]);
    }
}
