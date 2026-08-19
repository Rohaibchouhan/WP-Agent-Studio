<?php

namespace AiElementorAgent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use AiElementorAgent\Security\AccessControlManager;

class AccessControlTest extends TestCase
{
    public function test_default_permissions_have_all_modules()
    {
        $defaults = AccessControlManager::get_default_permissions();

        $this->assertArrayHasKey('elementor_widgets', $defaults);
        $this->assertArrayHasKey('elementor_atomic', $defaults);
        $this->assertArrayHasKey('plugin_management', $defaults);
        $this->assertArrayHasKey('direct_wp_posts', $defaults);
        $this->assertArrayHasKey('custom_code', $defaults);
        $this->assertArrayHasKey('stock_images', $defaults);
        $this->assertArrayHasKey('site_inspection', $defaults);

        $this->assertTrue($defaults['elementor_widgets']);
        $this->assertTrue($defaults['stock_images']);
    }
}
