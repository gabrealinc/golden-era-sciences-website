<?php
/**
 * Newsletter subscribe handler.
 *
 * Stores subscribers in a private custom post type so nothing is lost, then
 * synchronizes them to the approved Google Sheet.
 *
 * ---------------------------------------------------------------------------
 * Why there is no nonce here
 * ---------------------------------------------------------------------------
 * The signup form sits in the footer of every page. WordPress.com full-page
 * caches anonymous HTML, so a nonce printed into that HTML gets served long
 * after it expires (nonces live 12-24h). Every submission would then fail with
 * "your session expired", and reloading would serve the same cached page with
 * the same dead nonce. The form would be permanently broken until a cache
 * purge, and it would fail on a delay, so it would pass testing.
 *
 * A nonce protects against CSRF, which matters when a request acts on behalf
 * of a logged-in user. This endpoint is anonymous, idempotent, and grants no
 * privilege: the worst a forged request achieves is adding an email address
 * someone could have typed in themselves. So the real threat is bots, and the
 * defences below are aimed at bots: a honeypot field and a per-IP rate limit.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Max signups accepted from one IP per hour. */
const GE_SUBSCRIBE_RATE_LIMIT = 5;

/** Google Apps Script endpoint for the approved subscriber spreadsheet. */
const GE_SUBSCRIBER_SHEET_ENDPOINT = 'https://script.google.com/macros/s/AKfycbwU_e-MPeAoATB6nFT6P-Iehv0mbIMz1QUciT9ALIi2p9khMZ9mCZYlyZbZmhq9Fhp3/exec';

add_action( 'init', 'ge_register_subscriber_cpt' );
function ge_register_subscriber_cpt() {
	register_post_type( 'ge_subscriber', array(
		'labels' => array(
			'name'          => __( 'Subscribers', 'golden-era' ),
			'singular_name' => __( 'Subscriber', 'golden-era' ),
		),
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => 'tools.php',
		'supports'     => array( 'title', 'custom-fields' ),
		'map_meta_cap' => true,
		/*
		 * This list is customer PII. Restrict it to administrators rather than
		 * inheriting 'post' caps, which would expose every subscriber email to
		 * Editors and to WooCommerce's shop_manager role.
		 */
		'capability_type'     => array( 'ge_subscriber', 'ge_subscribers' ),
		'capabilities'        => array(
			'create_posts'       => 'do_not_allow',
			'edit_post'          => 'manage_options',
			'edit_posts'         => 'manage_options',
			'edit_others_posts'  => 'manage_options',
			'read_post'          => 'manage_options',
			'read_private_posts' => 'manage_options',
			'delete_post'        => 'manage_options',
			'delete_posts'       => 'manage_options',
		),
		'exclude_from_search' => true,
	) );
}

add_action( 'wp_ajax_ge_subscribe', 'ge_handle_subscribe' );
add_action( 'wp_ajax_nopriv_ge_subscribe', 'ge_handle_subscribe' );

function ge_handle_subscribe() {

	// Honeypot: a field hidden from humans. Anything filling it is a bot.
	// Report success so the bot has nothing to tune against.
	if ( ! empty( $_POST['ge_website'] ) ) {
		wp_send_json_success( array( 'message' => __( 'You are on the list. Watch your inbox.', 'golden-era' ) ) );
	}

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'golden-era' ) ), 400 );
	}

	$phone = isset( $_POST['phone_number'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_number'] ) ) : '';
	if ( $phone ) {
		$phone  = preg_replace( '/[^0-9+(). -]/', '', $phone );
		$digits = preg_replace( '/\D/', '', $phone );
		if ( strlen( $digits ) < 7 || strlen( $digits ) > 15 ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid phone number or leave the phone field blank.', 'golden-era' ) ), 400 );
		}
	}

	// Per-IP rate limit. Prevents an anonymous endpoint from being used to
	// bloat the database with unlimited posts and postmeta rows.
	$bucket = 'ge_sub_' . md5( ge_client_ip() );
	$hits   = (int) get_transient( $bucket );
	if ( $hits >= GE_SUBSCRIBE_RATE_LIMIT ) {
		wp_send_json_error(
			array( 'message' => __( 'Too many signups from this connection. Please try again later.', 'golden-era' ) ),
			429
		);
	}
	set_transient( $bucket, $hits + 1, HOUR_IN_SECONDS );

	$data = array(
		'email'      => $email,
		'first_name' => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
		'last_name'  => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
		'phone_number' => $phone,
		'email_opt_in' => true,
		'sms_opt_in'   => (bool) $phone,
	);

	// A repeat signup is not a failure; update its stored consent details.
	$existing = get_posts( array(
		'post_type'      => 'ge_subscriber',
		'post_status'    => 'private',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_key'       => 'email',       // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'     => $email,        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	) );

	if ( ! $existing ) {
		$post_id = wp_insert_post( array(
			'post_type'   => 'ge_subscriber',
			'post_status' => 'private',
			'post_title'  => $email,
		), true );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Something went wrong. Please try again.', 'golden-era' ) ), 500 );
		}

		foreach ( $data as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	} else {
		foreach ( $data as $key => $value ) {
			if ( 'phone_number' === $key && ! $value ) {
				continue;
			}
			update_post_meta( $existing[0], $key, $value );
		}
	}

	$sheet_result = ge_sync_subscriber_to_sheet( $data );
	if ( is_wp_error( $sheet_result ) ) {
		error_log( 'Golden Era subscriber sync failed: ' . $sheet_result->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		wp_send_json_error(
			array( 'message' => __( 'We saved your details but could not finish the signup. Please try once more.', 'golden-era' ) ),
			503
		);
	}

	wp_send_json_success( array( 'message' => __( 'You are on the list. Watch your inbox.', 'golden-era' ) ) );
}

/**
 * Synchronize a subscriber to the approved Google Sheet.
 *
 * @param array $data Sanitized subscriber data.
 * @return true|WP_Error
 */
function ge_sync_subscriber_to_sheet( $data ) {
	$response = wp_remote_post(
		GE_SUBSCRIBER_SHEET_ENDPOINT,
		array(
			'timeout'     => 15,
			'redirection' => 5,
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'        => wp_json_encode( $data ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'ge_sheet_http_error', __( 'The subscriber sheet returned an unexpected response.', 'golden-era' ) );
	}

	$payload = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $payload['ok'] ) ) {
		$message = ! empty( $payload['error'] ) ? sanitize_text_field( $payload['error'] ) : __( 'The subscriber sheet rejected the signup.', 'golden-era' );
		return new WP_Error( 'ge_sheet_sync_error', $message );
	}

	return true;
}

/**
 * Best-effort client IP for rate limiting only.
 *
 * Deliberately not used for anything security-critical: proxy headers are
 * spoofable, and this only decides how fast one connection may submit a form.
 */
function ge_client_ip() {
	foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $key ) {
		if ( empty( $_SERVER[ $key ] ) ) {
			continue;
		}
		$value = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
		$first = trim( explode( ',', $value )[0] );
		if ( filter_var( $first, FILTER_VALIDATE_IP ) ) {
			return $first;
		}
	}
	return 'unknown';
}
