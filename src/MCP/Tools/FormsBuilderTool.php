<?php

namespace AiElementorAgent\MCP\Tools;

use AiElementorAgent\MCP\AbstractTool;
use AiElementorAgent\Integrations\Forms\FormsAdapter;
use AiElementorAgent\Elementor\ElementorWriter;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FormsBuilderTool
 * Create forms, customize fields, and embed form widgets into Elementor pages.
 */
class FormsBuilderTool extends AbstractTool
{
    public function get_name(): string
    {
        return 'forms_builder';
    }

    public function get_description(): string
    {
        return 'Create forms, customize form fields (Text, Email, Select, Checkbox, Textarea), and embed form widgets into Elementor pages.';
    }

    public function get_schema(): array
    {
        return [
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'action' => [
                        'type'        => 'string',
                        'enum'        => ['detect', 'build_elementor_form'],
                        'description' => 'Operation to perform.',
                    ],
                    'page_id' => [
                        'type'        => 'integer',
                        'description' => 'Target Elementor page ID for form embedding.',
                    ],
                    'form_name' => [
                        'type'        => 'string',
                        'description' => 'Name of form (e.g. "Contact Us Form", "Lead Capture").',
                    ],
                    'fields' => [
                        'type'        => 'array',
                        'description' => 'Form fields definition array.',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'name'        => ['type' => 'string'],
                                'label'       => ['type' => 'string'],
                                'type'        => ['type' => 'string', 'enum' => ['text', 'email', 'textarea', 'select', 'checkbox', 'radio', 'tel']],
                                'placeholder' => ['type' => 'string'],
                                'required'    => ['type' => 'boolean'],
                            ],
                            'required'   => ['name'],
                        ],
                    ],
                ],
                'required'   => ['action'],
            ],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $action  = sanitize_text_field($params['action'] ?? '');
        $adapter = new FormsAdapter();

        if ($action === 'detect') {
            return $this->success([
                'detected_plugins' => $adapter->detect_form_plugins(),
            ]);
        }

        if (!current_user_can('edit_pages')) {
            return $this->error('Permission denied. Capability edit_pages required.');
        }

        if ($action === 'build_elementor_form') {
            $page_id   = (int) ($params['page_id'] ?? 0);
            $form_name = sanitize_text_field($params['form_name'] ?? 'Inquiry Form');
            $fields    = $params['fields'] ?? [];

            if (!$page_id || !get_post($page_id)) {
                return $this->error('Invalid target page_id.');
            }

            $widget_data = $adapter->build_elementor_form_widget($form_name, $fields);

            $writer = new ElementorWriter();
            $new_id = $writer->insert_element($page_id, null, $widget_data);

            return $this->success([
                'message'    => 'Elementor Form widget built and inserted into page.',
                'element_id' => $new_id,
                'page_id'    => $page_id,
                'form_name'  => $form_name,
            ]);
        }

        return $this->error('Invalid form builder action.');
    }
}
