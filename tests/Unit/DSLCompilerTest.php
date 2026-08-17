<?php
namespace AiElementorAgent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use AiElementorAgent\Agent\DSLCompiler;

class DSLCompilerTest extends TestCase {

	private DSLCompiler $compiler;

	protected function setUp(): void {
		$this->compiler = new DSLCompiler();
	}

	public function test_compile_simple_heading(): void {
		$dsl_node = array(
			'id'      => 'head123',
			'type'    => 'heading',
			'content' => array(
				'title'       => 'Welcome to AI Agency',
				'header_size' => 'h1',
			),
			'styles'  => array(
				'title_color' => '#FFFFFF',
			),
		);

		$node = $this->compiler->compile_element_node( $dsl_node );

		$this->assertEquals( 'head123', $node['id'] );
		$this->assertEquals( 'widget', $node['elType'] );
		$this->assertEquals( 'heading', $node['widgetType'] );
		$this->assertEquals( 'Welcome to AI Agency', $node['settings']['title'] );
		$this->assertEquals( 'h1', $node['settings']['header_size'] );
		$this->assertEquals( '#FFFFFF', $node['settings']['title_color'] );
	}

	public function test_compile_container_with_children(): void {
		$dsl_node = array(
			'id'       => 'parent_cont',
			'type'     => 'container',
			'layout'   => array(
				'direction' => 'column',
				'width'     => 'boxed',
			),
			'children' => array(
				array(
					'id'      => 'btn123',
					'type'    => 'button',
					'content' => array( 'text' => 'Click Me' ),
				),
			),
		);

		$node = $this->compiler->compile_element_node( $dsl_node );

		$this->assertEquals( 'container', $node['elType'] );
		$this->assertCount( 1, $node['elements'] );
		$this->assertEquals( 'btn123', $node['elements'][0]['id'] );
		$this->assertEquals( 'button', $node['elements'][0]['widgetType'] );
	}
}
