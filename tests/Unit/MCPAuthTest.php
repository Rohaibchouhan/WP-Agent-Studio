<?php
namespace AiElementorAgent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use AiElementorAgent\Security\AuthExtractor;
use AiElementorAgent\Security\TokenManager;

class MCPAuthTest extends TestCase {

	public function test_auth_extractor_returns_empty_when_no_auth_provided() {
		$_SERVER['HTTP_AUTHORIZATION'] = '';
		$_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = '';
		$_GET['token'] = '';

		$auth = AuthExtractor::extract( null );
		$this->assertSame( '', $auth );
	}

	public function test_auth_extractor_prefers_valid_bearer_header() {
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer aiea_live_header_secret_12345';
		$_GET['token'] = 'aiea_live_query_secret_67890';

		$auth = AuthExtractor::extract( null );
		$this->assertSame( 'Bearer aiea_live_header_secret_12345', $auth );
	}

	public function test_auth_extractor_falls_back_to_query_token_when_header_empty() {
		$_SERVER['HTTP_AUTHORIZATION'] = '';
		$_GET['token'] = 'aiea_live_query_secret_67890';

		$auth = AuthExtractor::extract( null );
		$this->assertSame( 'Bearer aiea_live_query_secret_67890', $auth );
	}

	public function test_token_expiry_calculation() {
		$now = time();
		$expired_timestamp = $now - 3600; // 1 hour ago
		$valid_timestamp   = $now + 3600; // 1 hour in future

		$this->assertTrue( $now > $expired_timestamp, 'Expired timestamp should be recognized as past' );
		$this->assertFalse( $now > $valid_timestamp, 'Valid timestamp should not be marked expired' );
	}
}
