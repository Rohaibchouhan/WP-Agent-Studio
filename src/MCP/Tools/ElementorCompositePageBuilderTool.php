<?php

namespace AiElementorAgent\MCP\Tools;

use AiElementorAgent\MCP\AbstractTool;
use AiElementorAgent\Elementor\ElementorWriter;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ElementorCompositePageBuilderTool
 * One-call composite page builder creating full multi-section landing pages in a single LLM round-trip.
 */
class ElementorCompositePageBuilderTool extends AbstractTool
{
    public function get_name(): string
    {
        return 'elementor_composite_page_builder';
    }

    public function get_description(): string
    {
        return 'Construct a complete multi-section Elementor page (Hero, Features, Testimonials, Pricing, CTA) in a single tool execution to save 80% tokens.';
    }

    public function get_schema(): array
    {
        return [
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'title' => [
                        'type'        => 'string',
                        'description' => 'Page title (e.g. "SaaS Product Landing Page").',
                    ],
                    'sections' => [
                        'type'        => 'array',
                        'description' => 'List of section definitions to compile.',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'type' => [
                                    'type' => 'string',
                                    'enum' => ['hero', 'features', 'pricing', 'testimonials', 'cta', 'custom_container'],
                                ],
                                'title'       => ['type' => 'string'],
                                'subtitle'    => ['type' => 'string'],
                                'cta_text'    => ['type' => 'string'],
                                'cta_url'     => ['type' => 'string'],
                                'bg_color'    => ['type' => 'string'],
                                'items'       => [
                                    'type'  => 'array',
                                    'items' => ['type' => 'object'],
                                ],
                            ],
                            'required'   => ['type'],
                        ],
                    ],
                ],
                'required'   => ['title', 'sections'],
            ],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $title    = sanitize_text_field($params['title'] ?? 'AI Composite Landing Page');
        $sections = $params['sections'] ?? [];

        if (!current_user_can('edit_pages')) {
            return $this->error('Permission denied. Capability edit_pages required.');
        }

        // Create target WP Page
        $page_id = wp_insert_post([
            'post_title'  => $title,
            'post_status' => 'publish',
            'post_type'   => 'page',
        ]);

        if (is_wp_error($page_id)) {
            return $this->error($page_id->get_error_message());
        }

        update_post_meta($page_id, '_elementor_edit_mode', 'builder');
        update_post_meta($page_id, '_elementor_template_type', 'wp-page');

        $containers = [];
        foreach ($sections as $section) {
            $containers[] = $this->build_section_container($section);
        }

        $writer = new ElementorWriter();
        $saved  = $writer->save_page_elements($page_id, $containers);

        if (!$saved) {
            return $this->error('Failed to write composite sections to Elementor post meta.');
        }

        return $this->success([
            'message'        => sprintf('Successfully compiled composite Elementor page with %d sections in 1 tool execution.', count($containers)),
            'page_id'        => $page_id,
            'title'          => $title,
            'permalink'      => get_permalink($page_id),
            'elementor_edit' => admin_url("post.php?post={$page_id}&action=elementor"),
        ]);
    }

    private function build_section_container(array $spec): array
    {
        $type = $spec['type'] ?? 'custom_container';
        $cid  = ElementorWriter::generate_id();

        $container = [
            'id'       => $cid,
            'elType'   => 'container',
            'isInner'  => false,
            'settings' => [
                'content_width' => 'full',
                'flex_direction' => 'column',
                'padding'       => [
                    'unit'     => 'px',
                    'top'      => '60',
                    'bottom'   => '60',
                    'left'     => '20',
                    'right'    => '20',
                    'isLinked' => false,
                ],
            ],
            'elements' => [],
        ];

        if (!empty($spec['bg_color'])) {
            $container['settings']['background_background'] = 'classic';
            $container['settings']['background_color']      = sanitize_text_field($spec['bg_color']);
        }

        // Add Heading
        if (!empty($spec['title'])) {
            $container['elements'][] = [
                'id'         => ElementorWriter::generate_id(),
                'elType'     => 'widget',
                'widgetType' => 'heading',
                'settings'   => [
                    'title'      => sanitize_text_field($spec['title']),
                    'header_size' => $type === 'hero' ? 'h1' : 'h2',
                    'align'      => 'center',
                ],
                'elements'   => [],
            ];
        }

        // Add Subtitle / Text
        if (!empty($spec['subtitle'])) {
            $container['elements'][] = [
                'id'         => ElementorWriter::generate_id(),
                'elType'     => 'widget',
                'widgetType' => 'text-editor',
                'settings'   => [
                    'editor' => wp_kses_post($spec['subtitle']),
                    'align'  => 'center',
                ],
                'elements'   => [],
            ];
        }

        // Add Button / CTA
        if (!empty($spec['cta_text'])) {
            $container['elements'][] = [
                'id'         => ElementorWriter::generate_id(),
                'elType'     => 'widget',
                'widgetType' => 'button',
                'settings'   => [
                    'text'  => sanitize_text_field($spec['cta_text']),
                    'link'  => ['url' => sanitize_url($spec['cta_url'] ?? '#'), 'is_external' => false],
                    'align' => 'center',
                    'button_type' => 'success',
                ],
                'elements'   => [],
            ];
        }

        return $container;
    }
}
