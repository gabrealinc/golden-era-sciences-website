<?php
/**
 * Newsletter subscribe handler.
 *
 * Stores subscribers in a private custom post type so nothing is lost, then
 * fires `ge_new_subscriber` for a mail plugin or CRM to hook into.
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
 * Example, push new subscribers to WooCommerce as customers:
 *
 *   add_action( 'ge_new_subscriber', function ( $data ) {
 *       if ( ! email_exists( $data['email'] ) ) {
 *           wc_create_new_customer( $data['email'], '', '', array(
 *               'first_name' => $data['first_name'],
 *               'last_name'  => $data['last_name'],
 *           ) );
 *       }
 *   } );
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Max signups accepted from one IP per hour. */
const GE_SUBSCRIBE_RATE_LIMIT = 5;

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
	);

	// A repeat signup is not a failure; skip the insert and report success.
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
	}

	/**
	 * Fires after a successful newsletter signup.
	 *
	 * @param array $data email, first_name, last_name.
	 */
	do_action( 'ge_new_subscriber', $data );

	wp_send_json_success( array( 'message' => __( 'You are on the list. Watch your inbox.', 'golden-era' ) ) );
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
