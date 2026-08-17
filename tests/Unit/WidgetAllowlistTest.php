<?php
namespace AiElementorAgent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use AiElementorAgent\Elementor\WidgetAllowlist;

class WidgetAllowlistTest extends TestCase {

	public function test_valid_core_widgets(): void {
		$res = WidgetAllowlist::validate_widget( 'heading', false );
		$this->assertTrue( $res['valid'] );

		$res = WidgetAllowlist::validate_widget( 'button', false );
		$this->assertTrue( $res['valid'] );

		$res = WidgetAllowlist::validate_widget( 'container', false );
		$this->assertTrue( $res['valid'] );
	}

	public function test_pro_widget_rejection_when_pro_disabled(): void {
		$res = WidgetAllowlist::validate_widget( 'form', false );
		$this->assertFalse( $res['valid'] );
		$this->assertStringContainsString( 'requires Elementor Pro', $res['reason'] );
	}

	public function test_pro_widget_allowed_when_pro_active(): void {
		$res = WidgetAllowlist::validate_widget( 'form', true );
		$this->assertTrue( $res['valid'] );
	}

	public function test_invalid_widget_type_rejection(): void {
		$res = WidgetAllowlist::validate_widget( 'malicious_widget_type', true );
		$this->assertFalse( $res['valid'] );
		$this->assertStringContainsString( 'not in the allowed widget list', $res['reason'] );
	}
}
