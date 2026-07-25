<?php
/**
 * Single product page.
 *
 * Overrides woocommerce/templates/single-product.php.
 *
 * The default WooCommerce summary hooks are kept so extensions continue to
 * work; only the surrounding layout is the theme's own.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' );
?>

<main id="main" tabindex="-1" class="ge-section">
	<div class="ge-container">

		<?php ge_breadcrumb(); ?>

		<?php woocommerce_output_all_notices(); ?>

		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<?php wc_get_template_part( 'content', 'single-product' ); ?>
		<?php endwhile; ?>

	</div>
</main>

<?php
get_footer( 'shop' );
