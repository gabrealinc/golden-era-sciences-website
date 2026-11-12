<?php
/**
 * Customer login gate.
 *
 * WooCommerce already provides customer registration, password reset, and
 * authentication. This layer makes that account the prerequisite for any
 * storefront content, including direct product URLs and public REST routes.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Inbox for the single operational notification sent after an account is created. */
const GE_NEW_ACCOUNT_NOTIFICATION_EMAIL = 'info@goldenerasciences.com';

/** Pages that must remain reachable before a customer signs in. */
function ge_customer_gate_is_public_page() {
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return true;
	}

	$path = isset( $_SERVER['REQUEST_URI'] )
		? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH )
		: '';
	$path = trim( $path, '/' );

	return in_array(
		$path,
		array( 'privacy-policy', 'cookie-policy', 'terms-conditions', 'shipping-returns' ),
		true
	);
}

/** Keep cached responses from crossing the logged-in / logged-out boundary. */
function ge_customer_gate_no_cache() {
	if ( is_admin() ) {
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
	header( 'Pragma: no-cache', true );
	header( 'Vary: Cookie', false );
}
add_action( 'send_headers', 'ge_customer_gate_no_cache', 0 );

/** Route non-customers to WooCommerce's existing login and registration page. */
function ge_customer_gate_enforce() {
	if ( is_admin() || is_user_logged_in() || ge_customer_gate_is_public_page() ) {
		return;
	}

	$account_url = function_exists( 'wc_get_page_permalink' )
		? wc_get_page_permalink( 'myaccount' )
		: home_url( '/my-account/' );
	$return_url  = home_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );

	ge_customer_gate_no_cache();
	wp_safe_redirect( add_query_arg( 'redirect_to', $return_url, $account_url ), 302 );
	exit;
}
add_action( 'template_redirect', 'ge_customer_gate_enforce', -1100 );

/** Preserve the originally requested page after a successful WooCommerce login. */
function ge_customer_gate_login_redirect( $redirect, $user ) {
	$requested = isset( $_REQUEST['redirect_to'] )
		? wp_validate_redirect( wp_unslash( $_REQUEST['redirect_to'] ), '' )
		: '';

	return $requested ? $requested : $redirect;
}
add_filter( 'woocommerce_login_redirect', 'ge_customer_gate_login_redirect', 10, 2 );

/** Carry the destination through WooCommerce's standard login form. */
function ge_customer_gate_login_redirect_field() {
	if ( empty( $_GET['redirect_to'] ) ) {
		return;
	}

	$requested = wp_validate_redirect( wp_unslash( $_GET['redirect_to'] ), '' );
	if ( $requested ) {
		printf( '<input type="hidden" name="redirect_to" value="%s">', esc_attr( $requested ) );
	}
}
add_action( 'woocommerce_login_form_end', 'ge_customer_gate_login_redirect_field' );

/** Add the required age confirmation and optional marketing consent at signup. */
function ge_customer_gate_registration_fields() {
	$age_confirmed = ! empty( $_POST['ge_age_confirmed'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$marketing     = ! empty( $_POST['ge_marketing_opt_in'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	?>
	<p class="form-row form-row-wide ge-customer-gate__consent">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox">
			<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="ge_age_confirmed" type="checkbox" value="1" <?php checked( $age_confirmed ); ?> required>
			<span><?php esc_html_e( 'I am at least 21 years of age.', 'golden-era' ); ?></span>
		</label>
	</p>
	<p class="form-row form-row-wide ge-customer-gate__consent">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox">
			<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="ge_marketing_opt_in" type="checkbox" value="1" <?php checked( $marketing ); ?>>
			<span><?php esc_html_e( 'Subscribe to emails.', 'golden-era' ); ?></span>
		</label>
	</p>
	<?php
}
add_action( 'woocommerce_register_form', 'ge_customer_gate_registration_fields' );

/** A new customer cannot be created until they confirm they are of age. */
function ge_customer_gate_validate_registration( $errors, $username, $email ) {
	if ( empty( $_POST['ge_age_confirmed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$errors->add( 'ge_age_confirmation_required', __( 'Please confirm that you are at least 21 years old.', 'golden-era' ) );
	}
	return $errors;
}
add_filter( 'woocommerce_registration_errors', 'ge_customer_gate_validate_registration', 10, 3 );

/** Record age confirmation and marketing consent when WooCommerce creates a customer. */
function ge_customer_gate_customer_created( $customer_id ) {
	update_user_meta( $customer_id, 'ge_age_confirmed_at', current_time( 'mysql', true ) );
	ge_age_gate_set_verification_cookie();

	$user = get_userdata( $customer_id );
	if ( $user ) {
		wp_mail(
			GE_NEW_ACCOUNT_NOTIFICATION_EMAIL,
			__( 'New Golden Era Sciences account created', 'golden-era' ),
			sprintf(
				/* translators: %s: customer email address. */
				__( "A new customer account was created for %s.", 'golden-era' ),
				$user->user_email
			)
		);
	}

	if ( empty( $_POST['ge_marketing_opt_in'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	if ( ! $user ) {
		return;
	}

	$email = $user->user_email;
	update_user_meta( $customer_id, 'ge_marketing_opted_in_at', current_time( 'mysql', true ) );

	if ( function_exists( 'ge_sync_subscriber_to_sheet' ) ) {
		$result = ge_sync_subscriber_to_sheet( array(
			'email'          => $email,
			'first_name'     => '',
			'last_name'      => '',
			'phone_number'   => '',
			'email_opt_in'   => true,
			'sms_opt_in'     => false,
		) );
		if ( is_wp_error( $result ) ) {
			error_log( 'Golden Era account marketing opt-in sync failed: ' . $result->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
add_action( 'woocommerce_created_customer', 'ge_customer_gate_customer_created' );

/** Keep password-change notices with the affected customer, not the site admin. */
add_filter( 'wp_password_change_notification_email', function ( $email ) {
	$email['to'] = '';
	return $email;
} );

/** No public WooCommerce or WordPress API route may disclose catalog data. */
function ge_customer_gate_rest( $result ) {
	if ( is_user_logged_in() || ! empty( $result ) ) {
		return $result;
	}

	return new WP_Error(
		'ge_customer_login_required',
		__( 'Customer login is required.', 'golden-era' ),
		array( 'status' => 401 )
	);
}
add_filter( 'rest_pre_dispatch', 'ge_customer_gate_rest', 90 );

/** Do not publish direct catalog URLs through feeds, sitemaps, or XML-RPC. */
add_filter( 'wp_sitemaps_enabled', function () {
	return is_user_logged_in();
} );
add_filter( 'xmlrpc_enabled', '__return_false' );

function ge_customer_gate_feed() {
	if ( is_user_logged_in() ) {
		return;
	}

	wp_die(
		esc_html__( 'Customer login is required.', 'golden-era' ),
		esc_html__( 'Authentication required', 'golden-era' ),
		array( 'response' => 401 )
	);
}

foreach ( array( 'do_feed', 'do_feed_rdf', 'do_feed_rss', 'do_feed_rss2', 'do_feed_atom' ) as $ge_customer_gate_feed_hook ) {
	add_action( $ge_customer_gate_feed_hook, 'ge_customer_gate_feed', 0 );
}
