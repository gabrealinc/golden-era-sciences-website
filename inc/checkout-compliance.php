<?php
/**
 * Required research-use acknowledgment at checkout.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Return the processor-required acknowledgment with the applicable policy links. */
function ge_checkout_research_acknowledgment_text() {
	return sprintf(
		/* translators: 1: terms URL, 2: privacy policy URL */
		__( 'I acknowledge that all products purchased from Golden Era Sciences are strictly for laboratory research purposes only and are not intended for human or veterinary use, consumption, or administration. By placing this order, I confirm that I understand and agree to these restrictions, as well as the <a href="%1$s" target="_blank" rel="noopener noreferrer">Terms &amp; Conditions</a> and <a href="%2$s" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.', 'golden-era' ),
		esc_url( home_url( '/terms-conditions/' ) ),
		esc_url( home_url( '/privacy-policy/' ) )
	);
}

/** Render the required field for WooCommerce's traditional checkout. */
function ge_checkout_research_acknowledgment_field() {
	if ( ! function_exists( 'woocommerce_form_field' ) ) {
		return;
	}

	woocommerce_form_field(
		'ge_research_acknowledgment',
		array(
			'type'              => 'checkbox',
			'class'             => array( 'form-row', 'ge-checkout-acknowledgment' ),
			'label'             => ge_checkout_research_acknowledgment_text(),
			'required'          => true,
			'input_class'       => array( 'input-checkbox' ),
			'label_class'       => array( 'woocommerce-form__label', 'woocommerce-form__label-for-checkbox', 'checkbox' ),
			'custom_attributes' => array( 'required' => 'required' ),
		),
		WC()->checkout()->get_value( 'ge_research_acknowledgment' )
	);
}
add_action( 'woocommerce_review_order_before_submit', 'ge_checkout_research_acknowledgment_field', 10 );

/** Prevent a traditional checkout from being submitted without an acknowledgment. */
function ge_validate_checkout_research_acknowledgment() {
	if ( empty( $_POST['ge_research_acknowledgment'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		wc_add_notice( __( 'Please acknowledge the research-use restrictions before placing your order.', 'golden-era' ), 'error' );
	}
}
add_action( 'woocommerce_checkout_process', 'ge_validate_checkout_research_acknowledgment' );

/** Retain the traditional-checkout acknowledgment with the completed order. */
function ge_save_checkout_research_acknowledgment( $order ) {
	if ( empty( $_POST['ge_research_acknowledgment'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	$order->update_meta_data( '_ge_research_acknowledgment', 'yes' );
	$order->update_meta_data( '_ge_research_acknowledgment_at', gmdate( 'c' ) );
}
add_action( 'woocommerce_checkout_create_order', 'ge_save_checkout_research_acknowledgment', 10 );

/** Register the same required checkbox for WooCommerce's Checkout Block. */
function ge_register_checkout_block_research_acknowledgment() {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	woocommerce_register_additional_checkout_field(
		array(
			'id'            => 'golden-era/research-acknowledgment',
			'label'         => __( 'I acknowledge the research-use restrictions for this order.', 'golden-era' ),
			'location'      => 'order',
			'type'          => 'checkbox',
			'required'      => true,
			'error_message' => __( 'Please acknowledge the research-use restrictions before placing your order.', 'golden-era' ),
		)
	);
}
add_action( 'woocommerce_init', 'ge_register_checkout_block_research_acknowledgment' );

/** Place the complete linked statement immediately above the Checkout Block's order button. */
function ge_checkout_block_research_acknowledgment_statement( $content, $block ) {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || empty( $block['blockName'] ) || 'woocommerce/checkout-actions-block' !== $block['blockName'] ) {
		return $content;
	}

	return '<p class="ge-checkout-acknowledgment__statement">' . wp_kses_post( ge_checkout_research_acknowledgment_text() ) . '</p>' . $content;
}
add_filter( 'render_block', 'ge_checkout_block_research_acknowledgment_statement', 10, 2 );
