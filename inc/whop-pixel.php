<?php
/**
 * Whop advertising pixel for the verified WooCommerce experience.
 *
 * The base pixel records consented page views. A purchase event is emitted on
 * the WooCommerce confirmation page with the real order number, total and
 * currency so refreshes can be deduplicated by both Whop and WordPress.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GE_WHOP_BUSINESS_ID = 'biz_rMfkTNDkKza2rw';

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
