<?php
namespace AiElementorAgent\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles creation, hashing, validation, and revocation of MCP Bearer Tokens.
 *
 * Performance improvements:
 * - SHA-256 fast lookup index stored alongside bcrypt hash
 * - Prefix-match eliminates bcrypt check on non-matching tokens
 * - Timing-safe comparison used throughout
 */
class TokenManager {

	private const OPTION_KEY = 'ai_elementor_agent_mcp_tokens';

	/**
	 * Generate a new secret token and save its hash.
	 *
	 * @param string   $label Description or client name.
	 * @param int      $user_id WordPress User ID bound to this token.
	 * @param int|null $expires_in_seconds Optional expiration window in seconds (default: 90 days).
	 * @param array    $scopes Array of scopes granted to token (default: ['read', 'write']).
	 * @return array Array containing the plain secret token (shown ONCE) and token metadata.
	 */
	public function create_token( string $label, int $user_id = 0, ?int $expires_in_seconds = 7776000, array $scopes = array( 'read', 'write' ) ): array {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		$token_id = 'tok_' . wp_generate_password( 12, false );
		$plain_secret = 'aiea_live_' . bin2hex( random_bytes( 16 ) );

		// Store both a fast SHA-256 lookup key and the full bcrypt hash
		$sha256_lookup = hash( 'sha256', $plain_secret );
		$bcrypt_hash = wp_hash_password( $plain_secret );

		$tokens = get_option( self::OPTION_KEY, array() );

		$expires_at = $expires_in_seconds ? time() + $expires_in_seconds : null;

		$token_data = array(
			'id'            => $token_id,
			'label'         => sanitize_text_field( $label ),
			'hash'          => $bcrypt_hash,
			'sha256_lookup' => $sha256_lookup,
			'user_id'       => $user_id,
			'scopes'        => $scopes,
			'expires_at'    => $expires_at,
			'created_at'    => current_time( 'mysql' ),
			'last_used'     => null,
			'masked'        => substr( $plain_secret, 0, 12 ) . '...' . substr( $plain_secret, -4 ),
		);

		$tokens[ $token_id ] = $token_data;
		update_option( self::OPTION_KEY, $tokens );

		return array(
			'token_id'     => $token_id,
			'plain_secret' => $plain_secret,
			'data'         => $token_data,
		);
	}

	/**
	 * Authenticate a raw bearer token string.
	 * Uses fast SHA-256 pre-check to avoid running bcrypt on non-matching tokens.
	 * Checks expiration status before approving.
	 *
	 * @param string $raw_token Bearer token provided in Authorization header.
	 * @return array|false Token metadata array if valid, false if invalid/expired.
	 */
	public function authenticate_token( string $raw_token ) {
		$raw_token = trim( $raw_token );
		if ( empty( $raw_token ) ) {
			return false;
		}

		$tokens = get_option( self::OPTION_KEY, array() );
		if ( empty( $tokens ) || ! is_array( $tokens ) ) {
			return false;
		}

		// Fast SHA-256 pre-scan — avoids bcrypt on every token for every request
		$input_sha256 = hash( 'sha256', $raw_token );
		$candidate_id = null;

		foreach ( $tokens as $token_id => $data ) {
			if ( isset( $data['sha256_lookup'] ) && hash_equals( $data['sha256_lookup'], $input_sha256 ) ) {
				$candidate_id = $token_id;
				break;
			}
		}

		// If fast match found via 256-bit SHA-256 lookup index, verify expiry & approve immediately
		if ( null !== $candidate_id ) {
			$data = $tokens[ $candidate_id ];

			// Expiry check
			if ( ! empty( $data['expires_at'] ) && time() > (int) $data['expires_at'] ) {
				return false;
			}

			$tokens[ $candidate_id ]['last_used'] = current_time( 'mysql' );
			update_option( self::OPTION_KEY, $tokens );
			return $tokens[ $candidate_id ];
		}

		// Legacy fallback: scan tokens created before SHA-256 index using native wp_check_password()
		foreach ( $tokens as $token_id => $data ) {
			if ( ! isset( $data['hash'] ) ) {
				continue;
			}

			if ( ! empty( $data['expires_at'] ) && time() > (int) $data['expires_at'] ) {
				continue;
			}

			if ( wp_check_password( $raw_token, $data['hash'] ) ) {
				// Upgrade this token entry with SHA-256 index on first match
				$tokens[ $token_id ]['sha256_lookup'] = $input_sha256;
				$tokens[ $token_id ]['last_used'] = current_time( 'mysql' );
				update_option( self::OPTION_KEY, $tokens );
				return $tokens[ $token_id ];
			}
		}

		return false;
	}

	/**
	 * Rotate a token: generates a fresh plain secret and re-hashes it under the same ID.
	 *
	 * @param string $token_id Token ID to rotate.
	 * @return array|false New plain secret and updated data, or false if token not found.
	 */
	public function rotate_token( string $token_id ) {
		$tokens = get_option( self::OPTION_KEY, array() );
		if ( ! isset( $tokens[ $token_id ] ) ) {
			return false;
		}

		$plain_secret = 'aiea_live_' . bin2hex( random_bytes( 16 ) );
		$sha256_lookup = hash( 'sha256', $plain_secret );
		$bcrypt_hash = wp_hash_password( $plain_secret );

		$tokens[ $token_id ]['hash']          = $bcrypt_hash;
		$tokens[ $token_id ]['sha256_lookup'] = $sha256_lookup;
		$tokens[ $token_id ]['masked']        = substr( $plain_secret, 0, 12 ) . '...' . substr( $plain_secret, -4 );
		$tokens[ $token_id ]['created_at']    = current_time( 'mysql' );

		update_option( self::OPTION_KEY, $tokens );

		return array(
			'token_id'     => $token_id,
			'plain_secret' => $plain_secret,
			'data'         => $tokens[ $token_id ],
		);
	}

	/**
	 * Revoke a token by ID.
	 *
	 * @param string $token_id Token ID to remove.
	 * @return bool True if found and removed.
	 */
	public function revoke_token( string $token_id ): bool {
		$tokens = get_option( self::OPTION_KEY, array() );
		if ( isset( $tokens[ $token_id ] ) ) {
			unset( $tokens[ $token_id ] );
			update_option( self::OPTION_KEY, $tokens );
			return true;
		}
		return false;
	}

	/**
	 * List all active tokens (without hashes).
	 *
	 * @return array Active tokens metadata.
	 */
	public function list_tokens(): array {
		$tokens = get_option( self::OPTION_KEY, array() );
		$list = array();
		foreach ( $tokens as $id => $data ) {
			$user_info = get_userdata( $data['user_id'] );
			$is_expired = ! empty( $data['expires_at'] ) && time() > (int) $data['expires_at'];
			$list[] = array(
				'id'         => $data['id'],
				'label'      => $data['label'],
				'masked'     => $data['masked'],
				'user_id'    => $data['user_id'],
				'user_name'  => $user_info ? $user_info->user_login : 'Unknown',
				'scopes'     => $data['scopes'] ?? array( 'read', 'write' ),
				'expires_at' => ! empty( $data['expires_at'] ) ? date( 'Y-m-d H:i:s', $data['expires_at'] ) : 'Never',
				'is_expired' => $is_expired,
				'created_at' => $data['created_at'],
				'last_used'  => $data['last_used'],
			);
		}
		return $list;
	}
}
