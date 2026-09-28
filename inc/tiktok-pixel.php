<?php
/**
 * TikTok Pixel tracking for the verified WooCommerce experience.
 *
 * Tracking deliberately starts after age verification. The standalone age
 * gate does not call wp_head or wp_footer and this file also checks the gate
 * explicitly, so the pixel cannot load for an unverified request.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GE_TIKTOK_PIXEL_ID = 'DAQ5JTJC77UFPT8030N0';

/** Whether front-end TikTok tracking is allowed for this request. */
function ge_tiktok_tracking_allowed() {
	if ( is_admin() || wp_doing_ajax() ) {
		return false;
	}

	return function_exists( 'ge_age_gate_verified' ) && ge_age_gate_verified();
}

/** Load the base pixel and record a page view after age verification. */
function ge_tiktok_pixel_base() {
	if ( ! ge_tiktok_tracking_allowed() ) {
		return;
	}
	?>
	<!-- TikTok Pixel Code Start -->
	<script>
	!function (w, d, t) {
		w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js",o=n&&n.partner;ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};n=document.createElement("script");n.type="text/javascript",n.async=!0,n.src=r+"?sdkid="+e+"&lib="+t;e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};

		ttq.load('<?php echo esc_js( GE_TIKTOK_PIXEL_ID ); ?>');
		ttq.page();
	}(window, document, 'ttq');
	</script>
	<!-- TikTok Pixel Code End -->
	<?php
}
add_action( 'wp_head', 'ge_tiktok_pixel_base', 1 );

/** Save an add-to-cart event for the next verified page render. */
function ge_tiktok_capture_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id ) {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	$tracked_product_id = $variation_id ? $variation_id : $product_id;
	$product            = wc_get_product( $tracked_product_id );
	if ( ! $product ) {
		return;
	}

	WC()->session->set(
		'ge_ttq_add_to_cart',
		array(
			'content_id'   => (string) $tracked_product_id,
			'content_name' => $product->get_name(),
			'value'        => (float) $product->get_price() * (int) $quantity,
			'currency'     => get_woocommerce_currency(),
			'quantity'     => (int) $quantity,
		)
	);
}
add_action( 'woocommerce_add_to_cart', 'ge_tiktok_capture_add_to_cart', 10, 4 );

/** Emit a captured add-to-cart event exactly once. */
function ge_tiktok_fire_add_to_cart() {
	if ( ! ge_tiktok_tracking_allowed() || ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	$event = WC()->session->get( 'ge_ttq_add_to_cart' );
	if ( empty( $event ) ) {
		return;
	}

	WC()->session->set( 'ge_ttq_add_to_cart', null );
	?>
	<script>
	if (typeof ttq !== 'undefined') {
		ttq.track('AddToCart', <?php echo wp_json_encode( $event ); ?>);
	}
	</script>
	<?php
}
add_action( 'wp_footer', 'ge_tiktok_fire_add_to_cart', 99 );

/** Record checkout details when a verified visitor reaches checkout. */
function ge_tiktok_initiate_checkout() {
	if ( ! ge_tiktok_tracking_allowed() || ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}

	$contents = array();
	foreach ( WC()->cart->get_cart() as $item ) {
		$product = isset( $item['data'] ) ? $item['data'] : null;
		if ( ! $product ) {
			continue;
		}
		$contents[] = array(
			'content_id'   => (string) $product->get_id(),
			'content_name' => $product->get_name(),
			'quantity'     => (int) $item['quantity'],
			'price'        => (float) $product->get_price(),
		);
	}

	$event = array(
		'value'        => (float) WC()->cart->get_cart_contents_total(),
		'currency'     => get_woocommerce_currency(),
		'contents'     => $contents,
		'content_type' => 'product',
	);
	?>
	<script>
	if (typeof ttq !== 'undefined') {
		ttq.track('InitiateCheckout', <?php echo wp_json_encode( $event ); ?>);
	}
	</script>
	<?php
}
add_action( 'woocommerce_before_checkout_form', 'ge_tiktok_initiate_checkout', 10 );

/** Record a completed order once on the WooCommerce thank-you page. */
function ge_tiktok_complete_payment( $order_id ) {
	if ( ! ge_tiktok_tracking_allowed() || ! $order_id ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || get_post_meta( $order_id, '_ge_ttq_pixel_fired', true ) ) {
		return;
	}

	$contents = array();
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}
		$contents[] = array(
			'content_id'   => (string) $product->get_id(),
			'content_name' => $product->get_name(),
			'quantity'     => (int) $item->get_quantity(),
			'price'        => (float) $order->get_item_total( $item, false ),
		);
	}

	$event = array(
		'value'        => (float) $order->get_total(),
		'currency'     => $order->get_currency(),
		'content_id'   => (string) $order_id,
		'content_type' => 'product',
		'contents'     => $contents,
	);

	update_post_meta( $order_id, '_ge_ttq_pixel_fired', '1' );
	?>
	<script>
	if (typeof ttq !== 'undefined') {
		ttq.track('CompletePayment', <?php echo wp_json_encode( $event ); ?>);
	}
	</script>
	<?php
}
add_action( 'woocommerce_thankyou', 'ge_tiktok_complete_payment', 10 );
