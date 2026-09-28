<?php
/**
 * Low-risk front-end security hardening that is safe on WordPress.com.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Add browser protections without interfering with checkout or hosted assets. */
function ge_security_headers() {
	if ( is_admin() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff', true );
	header( 'X-Frame-Options: SAMEORIGIN', true );
	header( 'Referrer-Policy: strict-origin-when-cross-origin', true );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()', true );
}
add_action( 'send_headers', 'ge_security_headers', 20 );

/** Do not expose administrator identities and account metadata to visitors. */
function ge_security_restrict_user_api( $result, $server, $request ) {
	if ( is_user_logged_in() ) {
		return $result;
	}
	$route = $request instanceof WP_REST_Request ? $request->get_route() : '';
	if ( preg_match( '#^/wp/v2/users(?:/|$)#', $route ) ) {
		return new WP_Error( 'rest_not_found', __( 'No route was found matching the URL and request method.', 'golden-era' ), array( 'status' => 404 ) );
	}
	return $result;
}
add_filter( 'rest_pre_dispatch', 'ge_security_restrict_user_api', 100, 3 );

/** Public author archives are not used and disclose account identifiers. */
function ge_security_disable_author_archives() {
	if ( is_author() && ! is_user_logged_in() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'ge_security_disable_author_archives', 5 );

/** Avoid confirming whether a submitted login name exists. */
add_filter( 'login_errors', function () {
	return __( 'The login details were not accepted.', 'golden-era' );
} );
