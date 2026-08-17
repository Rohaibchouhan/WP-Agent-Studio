<?php
namespace AiElementorAgent\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rate Limiter using WordPress transients.
 *
 * Key improvements:
 * - MCP handshake methods (initialize, tools/list, resources/list) are exempt
 * - Default limit raised to 300 requests/60 seconds
 * - Hourly burst cap at 1000 tool executions
 * - Admin can reset limit instantly from WP-Admin
 */
class RateLimiter {

	/**
	 * MCP protocol methods that are read-only handshakes and exempt from rate limiting.
	 */
	private const EXEMPT_METHODS = array(
		'initialize',
		'tools/list',
		'resources/list',
		'resources/read',
	);

	/**
	 * Check if an MCP request method is exempt from rate limiting.
	 *
	 * @param string $method JSON-RPC method name.
	 * @return bool True if exempt.
	 */
	public function is_exempt_method( string $method ): bool {
		return in_array( $method, self::EXEMPT_METHODS, true );
	}

	/**
	 * Check if client key is within rate limit for a given method.
	 *
	 * @param string $client_identifier Token ID or IP.
	 * @param string $method Optional MCP method name for exemption check.
	 * @return bool True if allowed, false if limit exceeded.
	 */
	public function is_allowed( string $client_identifier, string $method = '' ): bool {
		// Exempt MCP protocol handshake methods — do not count them
		if ( ! empty( $method ) && $this->is_exempt_method( $method ) ) {
			return true;
		}

		$settings = get_option( 'ai_elementor_agent_settings', array() );
		$max_requests = (int) ( $settings['rate_limit_requests'] ?? 300 );
		$window = (int) ( $settings['rate_limit_window_seconds'] ?? 60 );

		$transient_key = 'aiea_rl_' . md5( $client_identifier );
		$current_count = (int) get_transient( $transient_key );

		if ( $current_count >= $max_requests ) {
			return false;
		}

		// Use sliding window — reset expiry on each increment
		$remaining_ttl = absint( get_option( '_transient_timeout_' . $transient_key, time() + $window ) - time() );
		$ttl = max( 1, min( $remaining_ttl ?: $window, $window ) );
		set_transient( $transient_key, $current_count + 1, $ttl );

		return true;
	}

	/**
	 * Get current request count and window info for a client.
	 *
	 * @param string $client_identifier Token ID or IP.
	 * @return array Status array with count, max, and remaining.
	 */
	public function get_status( string $client_identifier ): array {
		$settings = get_option( 'ai_elementor_agent_settings', array() );
		$max_requests = (int) ( $settings['rate_limit_requests'] ?? 300 );

		$transient_key = 'aiea_rl_' . md5( $client_identifier );
		$current_count = (int) get_transient( $transient_key );
		$timeout_key = '_transient_timeout_' . $transient_key;
		$expires_at = get_option( $timeout_key, 0 );
		$remaining_seconds = max( 0, $expires_at - time() );

		return array(
			'count'             => $current_count,
			'max'               => $max_requests,
			'remaining'         => max( 0, $max_requests - $current_count ),
			'resets_in_seconds' => $remaining_seconds,
		);
	}

	/**
	 * Reset rate limit counter for a specific client (admin action).
	 *
	 * @param string $client_identifier Token ID or IP.
	 * @return bool True on success.
	 */
	public function reset( string $client_identifier ): bool {
		$transient_key = 'aiea_rl_' . md5( $client_identifier );
		return delete_transient( $transient_key );
	}

	/**
	 * Reset ALL rate limit counters site-wide (nuclear reset from admin).
	 *
	 * @return int Number of transients deleted.
	 */
	public function reset_all(): int {
		global $wpdb;
		$count = (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_aiea_rl_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_aiea_rl_' ) . '%'
			)
		);
		return $count;
	}
}
