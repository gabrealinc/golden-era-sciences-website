<?php
/**
 * Whop advertising pixel for the verified WooCommerce experience.
 *
 * The base pixel records consented page views, product views and cart events.
 * A purchase event is emitted on the WooCommerce confirmation page with the
 * real order number, total and currency so refreshes can be deduplicated by
 * both Whop and WordPress.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GE_WHOP_BUSINESS_ID = 'biz_rMfKTNDkKza2rw';

/** Whether front-end Whop tracking is allowed for this request. */
function ge_whop_tracking_allowed() {
	if ( is_admin() || wp_doing_ajax() ) {
		return false;
	}

	return function_exists( 'ge_age_gate_verified' )
		&& ge_age_gate_verified()
		&& function_exists( 'ge_cookie_consent_allows' )
		&& ge_cookie_consent_allows( 'marketing' );
}

/** Load the Whop base pixel site-wide after age and marketing consent. */
function ge_whop_pixel_base() {
	if ( ! ge_whop_tracking_allowed() ) {
		return;
	}
	?>
	<!-- Whop Pixel Code Start -->
	<script>
	!function(w,d,s,u,n,a,b){if(w[n])return;a=w[n]={q:[],t:+new Date,s:[],o:u,track:function(){a.q.push([+new Date].concat([].slice.call(arguments)))},setScope:function(){a.s=[].slice.call(arguments).filter(function(x){return typeof x==="string"});a.q.push([+new Date,"setScope"].concat(a.s))},scope:function(){var c=[].slice.call(arguments);return{track:function(){a.q.push([+new Date].concat([].slice.call(arguments)).concat([{__scope:c}]))}}}};b=d.createElement(s);b.async=1;b.src=u+"/s.js";d.getElementsByTagName(s)[0].parentNode.insertBefore(b,d.getElementsByTagName(s)[0])}(window,document,"script","https://t.whop.tw","whop");
	whop.setScope(<?php echo wp_json_encode( GE_WHOP_BUSINESS_ID ); ?>);
	whop.track("page");
	</script>
	<!-- Whop Pixel Code End -->
	<?php
}
add_action( 'wp_head', 'ge_whop_pixel_base', 2 );

/** Record a consented view of a WooCommerce product. */
function ge_whop_view_content() {
	if ( ! ge_whop_tracking_allowed() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product ) {
		return;
	}

	$event = array(
		'value'    => (float) wc_get_price_to_display( $product ),
		'currency' => get_woocommerce_currency(),
	);
	?>
	<script>
	if (typeof whop !== 'undefined') {
		whop.track("view_content", <?php echo wp_json_encode( $event ); ?>);
	}
	</script>
	<?php
}
add_action( 'wp_head', 'ge_whop_view_content', 3 );

/** Save a newly added cart item's value until the next rendered page. */
function ge_whop_capture_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id ) {
	if ( ! ge_whop_tracking_allowed() || ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	$product = wc_get_product( $variation_id ? $variation_id : $product_id );
	if ( ! $product ) {
		return;
	}

	WC()->session->set(
		'ge_whop_add_to_cart',
		array(
			'value'    => (float) wc_get_price_to_display( $product ) * max( 1, (int) $quantity ),
			'currency' => get_woocommerce_currency(),
		)
	);
}
add_action( 'woocommerce_add_to_cart', 'ge_whop_capture_add_to_cart', 10, 4 );

/** Emit the saved cart event after the Whop base pixel has loaded. */
function ge_whop_fire_add_to_cart() {
	if ( ! ge_whop_tracking_allowed() || ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	$event = WC()->session->get( 'ge_whop_add_to_cart' );
	if ( empty( $event ) ) {
		return;
	}

	WC()->session->set( 'ge_whop_add_to_cart', null );
	?>
	<script>
	if (typeof whop !== 'undefined') {
		whop.track("add_to_cart", <?php echo wp_json_encode( $event ); ?>);
	}
	</script>
	<?php
}
add_action( 'wp_footer', 'ge_whop_fire_add_to_cart', 98 );

/** Record a completed WooCommerce order once on the confirmation page. */
function ge_whop_complete_purchase( $order_id ) {
	if ( ! ge_whop_tracking_allowed() || ! $order_id ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || $order->needs_payment() || get_post_meta( $order_id, '_ge_whop_pixel_fired', true ) ) {
		return;
	}

	$event = array(
		'value'    => (float) $order->get_total(),
		'currency' => $order->get_currency(),
		'event_id' => (string) $order->get_order_number(),
	);

	update_post_meta( $order_id, '_ge_whop_pixel_fired', '1' );
	?>
	<script>
	if (typeof whop !== 'undefined') {
		whop.track("whop_purchase", <?php echo wp_json_encode( $event ); ?>);
	}
	</script>
	<?php
}
add_action( 'woocommerce_thankyou', 'ge_whop_complete_purchase', 20 );
