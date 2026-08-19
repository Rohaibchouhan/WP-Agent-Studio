<?php

namespace AiElementorAgent\Integrations\Forms;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FormsAdapter
 * Universal adapter supporting Elementor Forms, Fluent Forms, WPForms, and Gravity Forms.
 */
class FormsAdapter
{
    /**
     * Discover installed and active form builders on the site.
     */
    public function detect_form_plugins(): array
    {
        return [
            'elementor_pro_forms' => defined('ELEMENTOR_PRO_VERSION'),
            'fluent_forms'        => defined('FLUENTFORM'),
            'wpforms'             => class_exists('WPForms'),
            'gravity_forms'       => class_exists('GFForms'),
        ];
    }

    /**
     * Generate an Elementor Form widget structure for insertion into an Elementor page.
     */
    public function build_elementor_form_widget(string $form_name, array $fields): array
    {
        $form_fields = [];
        foreach ($fields as $idx => $f) {
            $form_fields[] = [
                '_id'         => 'field_' . substr(md5($f['name'] . $idx), 0, 7),
                'field_type'  => sanitize_text_field($f['type'] ?? 'text'),
                'field_label' => sanitize_text_field($f['label'] ?? $f['name']),
                'placeholder' => sanitize_text_field($f['placeholder'] ?? ''),
                'required'    => !empty($f['required']),
                'width'       => sanitize_text_field($f['width'] ?? '100'),
            ];
        }

        return [
            'elType'     => 'widget',
            'widgetType' => 'form',
            'settings'   => [
                'form_name'   => sanitize_text_field($form_name),
                'form_fields' => $form_fields,
                'button_text' => __('Submit Inquiry', 'wp-agent-studio'),
                'button_type' => 'success',
            ],
            'elements'   => [],
        ];
    }
}
