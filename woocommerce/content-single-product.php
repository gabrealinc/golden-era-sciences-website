<?php
/**
 * Single product content.
 *
 * Overrides woocommerce/templates/content-single-product.php.
 *
 * Standard WooCommerce hooks are preserved so plugins keep working. The
 * theme supplies the two-column layout and its own meta, COA link, and
 * research-use notice via inc/woocommerce.php.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

/**
 * Fires before the single product.
 * Used by WooCommerce to output notices.
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'ge-product', $product ); ?>>

	<div class="ge-product__gallery">
		<?php
		/**
		 * woocommerce_before_single_product_summary hook.
		 *
		 * @hooked woocommerce_show_product_sale_flash - 10
		 * @hooked woocommerce_show_product_images - 20
		 */
		do_action( 'woocommerce_before_single_product_summary' );
		?>
	</div>

	<div class="summary entry-summary">
		<p class="ge-kicker"><?php esc_html_e( 'Research Compound', 'golden-era' ); ?></p>
		<?php
		/**
		 * woocommerce_single_product_summary hook.
		 *
		 * @hooked woocommerce_template_single_title - 5
		 * @hooked woocommerce_template_single_price - 10
		 * @hooked woocommerce_template_single_excerpt - 20
		 * @hooked woocommerce_template_single_add_to_cart - 30
		 * @hooked ge_product_meta - 41
		 * @hooked ge_product_coa - 45
		 * @hooked ge_product_rou - 65
		 */
		do_action( 'woocommerce_single_product_summary' );
		?>
	</div>

</div>

<?php
/**
 * woocommerce_after_single_product_summary hook.
 *
 * @hooked woocommerce_output_product_data_tabs - 10
 * @hooked woocommerce_upsell_display - 15
 * @hooked woocommerce_output_related_products - 20
 */
do_action( 'woocommerce_after_single_product_summary' );

do_action( 'woocommerce_after_single_product' );
