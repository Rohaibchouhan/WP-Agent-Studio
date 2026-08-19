<?php

use AiElementorAgent\Security\AccessControlManager;

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'wp-agent-studio'));
}

// Handle Form Submission
if (isset($_POST['wp_agent_access_control_nonce']) && wp_verify_nonce($_POST['wp_agent_access_control_nonce'], 'wp_agent_save_access_control')) {
    $permissions_input = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];
    AccessControlManager::save_permissions($permissions_input);

    $api_keys_input = isset($_POST['api_keys']) && is_array($_POST['api_keys']) ? $_POST['api_keys'] : [];
    AccessControlManager::save_api_keys($api_keys_input);

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('MCP Access permissions and API keys updated successfully!', 'wp-agent-studio') . '</p></div>';
}

$permissions = AccessControlManager::get_permissions();
$api_keys    = AccessControlManager::get_api_keys();

$modules = [
    'site_inspection' => [
        'label' => __('Site Inspection & System Diagnostics', 'wp-agent-studio'),
        'desc'  => __('Allows AI agents to discover theme, WP version, installed plugins, and CPT structures in <300 tokens.', 'wp-agent-studio'),
        'badge' => 'Free'
    ],
    'plugin_management' => [
        'label' => __('Plugins & Themes Management', 'wp-agent-studio'),
        'desc'  => __('Allows AI agents to search, install, activate, and deactivate WordPress plugins.', 'wp-agent-studio'),
        'badge' => 'High Capability Required'
    ],
    'direct_wp_posts' => [
        'label' => __('Direct WP Post & Page CRUD', 'wp-agent-studio'),
        'desc'  => __('Direct post, page, and custom post type creation, updating, and trash management.', 'wp-agent-studio'),
        'badge' => 'Free'
    ],
    'elementor_widgets' => [
        'label' => __('Elementor Curated Widgets (60+ Widgets)', 'wp-agent-studio'),
        'desc'  => __('Inspect, discover, and manipulate 60+ native & pro Elementor widgets with control validation.', 'wp-agent-studio'),
        'badge' => 'Free + Pro'
    ],
    'elementor_atomic' => [
        'label' => __('Elementor 4.0 Atomic Elements ($$type)', 'wp-agent-studio'),
        'desc'  => __('Typed-props element system for Elementor 4.0+ with automatic structure resolution.', 'wp-agent-studio'),
        'badge' => 'Elementor 4.0+'
    ],
    'elementor_layout' => [
        'label' => __('Flexbox Layout & Containers', 'wp-agent-studio'),
        'desc'  => __('Nested flexbox container creation, row/column alignment, and structure optimization.', 'wp-agent-studio'),
        'badge' => 'Free'
    ],
    'elementor_composite' => [
        'label' => __('Pages & Composite Builder', 'wp-agent-studio'),
        'desc'  => __('Full multi-section composite page generation in a single tool call to save 80% tokens.', 'wp-agent-studio'),
        'badge' => 'Token Saver'
    ],
    'elementor_global_styles' => [
        'label' => __('Global Styles & Kit Typography/Colors', 'wp-agent-studio'),
        'desc'  => __('Site-wide global palette management, font styles, and active Kit controls.', 'wp-agent-studio'),
        'badge' => 'Free'
    ],
    'custom_code' => [
        'label' => __('Custom CSS & JS Code Snippets', 'wp-agent-studio'),
        'desc'  => __('Inject widget-level CSS, page CSS, and site-wide custom HTML/JS snippets.', 'wp-agent-studio'),
        'badge' => 'Admin Only'
    ],
    'stock_images' => [
        'label' => __('Stock Image Sideloading (Unsplash / Pexels / Pixabay)', 'wp-agent-studio'),
        'desc'  => __('Search stock photos and import them directly into the WP Media Library.', 'wp-agent-studio'),
        'badge' => 'Media'
    ],
    'woocommerce_integration' => [
        'label' => __('WooCommerce Store & Products Integration', 'wp-agent-studio'),
        'desc'  => __('AI management of WooCommerce products, pricing, categories, shop grids, and checkout templates.', 'wp-agent-studio'),
        'badge' => 'E-Commerce'
    ],
    'acf_integration' => [
        'label' => __('ACF & Dynamic Content Tags', 'wp-agent-studio'),
        'desc'  => __('Dynamic content field discovery, ACF field group registration, and widget tag binding.', 'wp-agent-studio'),
        'badge' => 'Dynamic Content'
    ],
    'forms_integration' => [
        'label' => __('Universal Forms Builder Adapter', 'wp-agent-studio'),
        'desc'  => __('Form generation and styling for Elementor Forms, Fluent Forms, WPForms, and Gravity Forms.', 'wp-agent-studio'),
        'badge' => 'Forms'
    ],
    'seo_integration' => [
        'label' => __('SEO Optimization & JSON-LD Schema Engine', 'wp-agent-studio'),
        'desc'  => __('Meta tags, focus keywords, OpenGraph, and JSON-LD structured schema for Yoast, RankMath & AIOSEO.', 'wp-agent-studio'),
        'badge' => 'SEO'
    ],
];
?>

<div class="wrap wp-agent-studio-access-wrap" style="max-width: 1100px; margin: 20px 0;">
    <h1 style="display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 24px;">
        <span class="dashicons dashicons-shield" style="font-size: 30px; width: 30px; height: 30px; color: #3858e9;"></span>
        <?php esc_html_e('MCP Access & Permissions Dashboard', 'wp-agent-studio'); ?>
    </h1>
    <p style="font-size: 14px; color: #50575e; margin-bottom: 25px;">
        <?php esc_html_e('Control what capabilities and modules external AI agents (Claude, Cursor, Antigravity, WP Abilities API) are granted on this WordPress site.', 'wp-agent-studio'); ?>
    </p>

    <form method="post" action="">
        <?php wp_nonce_field('wp_agent_save_access_control', 'wp_agent_access_control_nonce'); ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <?php foreach ($modules as $key => $mod): ?>
                <div style="background: #ffffff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 18px 20px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong style="font-size: 15px; color: #1d2327;"><?php echo esc_html($mod['label']); ?></strong>
                            <span style="background: #f0f0f1; border-radius: 12px; padding: 2px 10px; font-size: 11px; font-weight: 600; color: #646970;">
                                <?php echo esc_html($mod['badge']); ?>
                            </span>
                        </div>
                        <p style="font-size: 13px; color: #646970; margin: 0 0 15px 0; line-height: 1.4;">
                            <?php echo esc_html($mod['desc']); ?>
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; border-top: 1px solid #f0f0f1; padding-top: 12px;">
                        <label class="switch" style="position: relative; display: inline-block; width: 44px; height: 24px;">
                            <input type="checkbox" name="permissions[<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($permissions[$key])); ?> style="opacity: 0; width: 0; height: 0;">
                            <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .3s; border-radius: 24px;"></span>
                        </label>
                        <span style="font-size: 13px; font-weight: 600; color: <?php echo !empty($permissions[$key]) ? '#008a20' : '#d63638'; ?>;">
                            <?php echo !empty($permissions[$key]) ? esc_html__('Access Allowed', 'wp-agent-studio') : esc_html__('Access Restricted', 'wp-agent-studio'); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="background: #ffffff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 20px; margin-bottom: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
            <h2 style="font-size: 18px; margin-top: 0; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-format-image" style="color: #3858e9;"></span>
                <?php esc_html_e('Stock Photo Provider API Keys', 'wp-agent-studio'); ?>
            </h2>
            <p style="font-size: 13px; color: #50575e; margin-bottom: 20px;">
                <?php esc_html_e('Provide API keys to allow AI agents to search and sideload stock photos directly into the WordPress Media Library.', 'wp-agent-studio'); ?>
            </p>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div>
                    <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;" for="unsplash_key">
                        <?php esc_html_e('Unsplash Access Key', 'wp-agent-studio'); ?>
                    </label>
                    <input type="password" id="unsplash_key" name="api_keys[unsplash]" value="<?php echo esc_attr($api_keys['unsplash']); ?>" class="regular-text" style="width: 100%;" placeholder="e.g. Access Key...">
                </div>

                <div>
                    <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;" for="pexels_key">
                        <?php esc_html_e('Pexels API Key', 'wp-agent-studio'); ?>
                    </label>
                    <input type="password" id="pexels_key" name="api_keys[pexels]" value="<?php echo esc_attr($api_keys['pexels']); ?>" class="regular-text" style="width: 100%;" placeholder="e.g. API Key...">
                </div>

                <div>
                    <label style="display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px;" for="pixabay_key">
                        <?php esc_html_e('Pixabay API Key', 'wp-agent-studio'); ?>
                    </label>
                    <input type="password" id="pixabay_key" name="api_keys[pixabay]" value="<?php echo esc_attr($api_keys['pixabay']); ?>" class="regular-text" style="width: 100%;" placeholder="e.g. Key...">
                </div>
            </div>
        </div>

        <p class="submit" style="margin-top: 20px;">
            <input type="submit" name="submit" id="submit" class="button button-primary button-large" value="<?php esc_attr_e('Save MCP Access Controls & Keys', 'wp-agent-studio'); ?>">
        </p>
    </form>
</div>

<style>
.switch input:checked + .slider {
    background-color: #3858e9 !important;
}
.switch input:focus + .slider {
    box-shadow: 0 0 1px #3858e9;
}
.switch input:checked + .slider:before {
    transform: translateX(20px);
}
.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}
</style>
