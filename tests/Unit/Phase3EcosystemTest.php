<?php

namespace WP\AgentStudio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP\AgentStudio\Integrations\WooCommerce\WooCommerceAdapter;
use WP\AgentStudio\Integrations\ACF\ACFAdapter;
use WP\AgentStudio\Integrations\Forms\FormsAdapter;
use WP\AgentStudio\Integrations\SEO\SEOAdapter;

class Phase3EcosystemTest extends TestCase
{
    public function test_woocommerce_adapter_methods_exist()
    {
        $wc = new WooCommerceAdapter();
        $this->assertIsBool($wc->is_active());
        $status = $wc->get_store_status();
        $this->assertArrayHasKey('active', $status);
    }

    public function test_acf_adapter_methods_exist()
    {
        $acf = new ACFAdapter();
        $this->assertIsBool($acf->is_active());
    }

    public function test_forms_adapter_detection()
    {
        $forms = new FormsAdapter();
        $detected = $forms->detect_form_plugins();

        $this->assertArrayHasKey('elementor_pro_forms', $detected);
        $this->assertArrayHasKey('fluent_forms', $detected);
        $this->assertArrayHasKey('wpforms', $detected);
        $this->assertArrayHasKey('gravity_forms', $detected);
    }

    public function test_seo_json_ld_generation()
    {
        $seo = new SEOAdapter();
        $json = $seo->generate_json_ld_schema('Organization', ['name' => 'WP Agent Studio', 'url' => 'https://example.com']);

        $this->assertStringContainsString('Organization', $json);
        $this->assertStringContainsString('WP Agent Studio', $json);
    }
}
