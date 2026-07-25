<?php
/**
 * Product card in a loop.
 *
 * Overrides woocommerce/templates/content-product.php.
 * Mirrors the PeptideCard component from the original design: square image,
 * name, two-line excerpt, price, and a "View" affordance.
 *
 * Structure note: the card is a <div>, not an <a>. The product title carries
 * the only link, stretched over the whole card with a ::after overlay (see
 * .ge-card__link in theme.css). That keeps the whole card clickable while
 * leaving the standard WooCommerce loop hooks below usable — anything they
 * render (quick view, wishlist, swatches, add-to-cart) sits above the overlay
 * instead of being illegally nested inside an anchor.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

$excerpt = $product->get_short_description();
if ( ! $excerpt ) {
	$excerpt = $product->get_description();
}
$excerpt = wp_strip_all_tags( $excerpt );
?>

<div <?php wc_product_class( 'ge-card', $product ); ?>>

	<?php
	/**
	 * woocommerce_before_shop_loop_item hook.
	 *
	 * The theme removes the core callback (which opened an <a>); this fires
	 * so third-party extensions still have a landing point.
	 */
	do_action( 'woocommerce_before_shop_loop_item' );
	?>

	<div class="ge-card__media">
		<?php
		do_action( 'woocommerce_before_shop_loop_item_title' );

		echo $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>

		<?php if ( ! $product->is_in_stock() ) : ?>
			<span class="ge-card__badge"><?php esc_html_e( 'Out of stock', 'golden-era' ); ?></span>
		<?php elseif ( $product->is_featured() ) : ?>
			<span class="ge-card__badge"><?php esc_html_e( 'Best Seller', 'golden-era' ); ?></span>
		<?php elseif ( $product->is_on_sale() ) : ?>
			<span class="ge-card__badge"><?php esc_html_e( 'Sale', 'golden-era' ); ?></span>
		<?php endif; ?>
	</div>

	<div class="ge-card__body">

		<h3 class="ge-card__title">
			<a class="ge-card__link" href="<?php the_permalink(); ?>">
				<?php echo esc_html( $product->get_name() ); ?>
			</a>
		</h3>

		<?php do_action( 'woocommerce_shop_loop_item_title' ); ?>

		<?php if ( $excerpt ) : ?>
			<p class="ge-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
		<?php endif; ?>

		<div class="ge-card__foot">
			<?php if ( $product->get_price_html() ) : ?>
				<span class="ge-card__price">
					<?php echo wp_kses_post( $product->get_price_html() ); ?>
				</span>
			<?php endif; ?>

			<span class="ge-card__cta" aria-hidden="true"><?php esc_html_e( 'View →', 'golden-era' ); ?></span>
		</div>

		<?php do_action( 'woocommerce_after_shop_loop_item_title' ); ?>

	</div>

	<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>

</div>
