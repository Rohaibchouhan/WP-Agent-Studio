<?php
namespace AiElementorAgent\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized Authorization Header & Query Token Extractor.
 *
 * Consolidates auth extraction across all MCP endpoints and diagnostic handlers.
 * Follows security requirements:
 * - Primary: Bearer token in Authorization header
 * - Query fallback: Applied ONLY when there is no valid `Bearer ` header present.
 */
class AuthExtractor {

	/**
	 * Extract Bearer token string from request headers, server environment, or query fallback.
	 *
	 * @param \WP_REST_Request|null $request Optional WP_REST_Request object.
	 * @return string Extracted Authorization header value starting with 'Bearer ' or empty string.
	 */
	public static function extract( $request = null ): string {
		$auth_header = '';

		// 1. Extract from WP_REST_Request object if available
		if ( $request && method_exists( $request, 'get_header' ) ) {
			$auth_header = (string) ( $request->get_header( 'authorization' ) ?? '' );
		}

		// 2. Fallback to web server environment variables if empty
		if ( empty( $auth_header ) ) {
			if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
				$auth_header = (string) $_SERVER['HTTP_AUTHORIZATION'];
			} elseif ( ! empty( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
				$auth_header = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
			} elseif ( ! empty( $_SERVER['HTTP_X_AUTHORIZATION'] ) ) {
				$auth_header = (string) $_SERVER['HTTP_X_AUTHORIZATION'];
			} elseif ( function_exists( 'getallheaders' ) ) {
				$headers = getallheaders();
				if ( ! empty( $headers['Authorization'] ) ) {
					$auth_header = (string) $headers['Authorization'];
				} elseif ( ! empty( $headers['authorization'] ) ) {
					$auth_header = (string) $headers['authorization'];
				}
			} elseif ( function_exists( 'apache_request_headers' ) ) {
				$headers = apache_request_headers();
				if ( ! empty( $headers['Authorization'] ) ) {
					$auth_header = (string) $headers['Authorization'];
				} elseif ( ! empty( $headers['authorization'] ) ) {
					$auth_header = (string) $headers['authorization'];
				}
			}
		}

		$auth_header = trim( $auth_header );

		// 3. If a valid 'Bearer ' token header is present, use it immediately
		if ( 0 === strpos( $auth_header, 'Bearer ' ) && strlen( $auth_header ) > 7 ) {
			return $auth_header;
		}

		// 4. Query parameter fallback: apply ONLY when no valid Bearer header is present
		$url_token = '';
		if ( $request && method_exists( $request, 'get_param' ) ) {
			$url_token = (string) ( $request->get_param( 'token' ) ?: ( $request->get_param( '_auth' ) ?: $request->get_param( 'access_token' ) ) );
		}

		if ( empty( $url_token ) ) {
			$url_token = (string) ( $_GET['token'] ?? ( $_GET['_auth'] ?? ( $_GET['access_token'] ?? '' ) ) );
		}

		$url_token = trim( $url_token );
		if ( ! empty( $url_token ) ) {
			return 'Bearer ' . $url_token;
		}

		return '';
	}
}
