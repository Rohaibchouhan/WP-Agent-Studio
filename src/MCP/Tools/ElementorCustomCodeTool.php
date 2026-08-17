<?php

namespace WP\AgentStudio\MCP\Tools;

use WP\AgentStudio\MCP\AbstractTool;
use WP\AgentStudio\Elementor\ElementorWriter;
use WP\AgentStudio\Elementor\ElementorReader;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ElementorCustomCodeTool
 * Handles per-element custom CSS, per-page custom CSS, and site-wide custom HTML/JS code snippets.
 */
class ElementorCustomCodeTool extends AbstractTool
{
    public function get_name(): string
    {
        return 'elementor_custom_code';
    }

    public function get_description(): string
    {
        return 'Inject or update custom CSS for specific Elementor widgets, page-level custom CSS, or site-wide custom HTML/JS code snippets.';
    }

    public function get_schema(): array
    {
        return [
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'target_type' => [
                        'type'        => 'string',
                        'enum'        => ['element_css', 'page_css', 'html_snippet'],
                        'description' => 'Target code injection location.',
                    ],
                    'page_id' => [
                        'type'        => 'integer',
                        'description' => 'Target Elementor page ID (required for element_css and page_css).',
                    ],
                    'element_id' => [
                        'type'        => 'string',
                        'description' => 'Elementor widget or container ID (required for element_css).',
                    ],
                    'css_code' => [
                        'type'        => 'string',
                        'description' => 'Custom CSS code string.',
                    ],
                    'html_code' => [
                        'type'        => 'string',
                        'description' => 'Custom HTML/JS code for HTML widget injection.',
                    ],
                ],
                'required'   => ['target_type'],
            ],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $target_type = sanitize_text_field($params['target_type'] ?? '');

        if (!current_user_can('unfiltered_html') && !current_user_can('edit_pages')) {
            return $this->error('Permission denied. Capability edit_pages required.');
        }

        switch ($target_type) {
            case 'page_css':
                return $this->update_page_css($params);

            case 'element_css':
                return $this->update_element_css($params);

            case 'html_snippet':
                return $this->inject_html_snippet($params);

            default:
                return $this->error('Invalid target_type.');
        }
    }

    private function update_page_css(array $params): array
    {
        $page_id  = (int) ($params['page_id'] ?? 0);
        $css_code = sanitize_textarea_field($params['css_code'] ?? '');

        if (!$page_id || !get_post($page_id)) {
            return $this->error('Invalid target page_id.');
        }

        update_post_meta($page_id, '_elementor_page_settings', [
            'custom_css' => $css_code,
        ]);

        return $this->success([
            'message'  => 'Updated page-level custom CSS.',
            'page_id'  => $page_id,
            'css_size' => strlen($css_code),
        ]);
    }

    private function update_element_css(array $params): array
    {
        $page_id    = (int) ($params['page_id'] ?? 0);
        $element_id = sanitize_text_field($params['element_id'] ?? '');
        $css_code   = sanitize_textarea_field($params['css_code'] ?? '');

        if (!$page_id || empty($element_id)) {
            return $this->error('page_id and element_id are required.');
        }

        $writer = new ElementorWriter();
        $updated = $writer->update_element($page_id, $element_id, [
            'custom_css' => $css_code,
        ]);

        if (!$updated) {
            return $this->error('Failed to find or update target element_id.');
        }

        return $this->success([
            'message'    => 'Custom CSS injected into Elementor element.',
            'element_id' => $element_id,
            'page_id'    => $page_id,
        ]);
    }

    private function inject_html_snippet(array $params): array
    {
        $page_id   = (int) ($params['page_id'] ?? 0);
        $html_code = $params['html_code'] ?? '';

        if (!$page_id || empty($html_code)) {
            return $this->error('page_id and html_code are required.');
        }

        $writer = new ElementorWriter();
        $element_data = [
            'id'         => ElementorWriter::generate_id(),
            'elType'     => 'widget',
            'widgetType' => 'html',
            'settings'   => [
                'html' => $html_code,
            ],
            'elements'   => [],
        ];

        $new_id = $writer->insert_element($page_id, null, $element_data);

        return $this->success([
            'message'    => 'HTML/JS snippet injected as an Elementor HTML widget.',
            'element_id' => $new_id,
            'page_id'    => $page_id,
        ]);
    }
}
