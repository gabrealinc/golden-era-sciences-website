<?php
/**
 * Homepage.
 *
 * Featured compounds are pulled live from WooCommerce. Products marked
 * "Featured" in the product editor come first; if fewer than four are
 * flagged, the grid is topped up with the most popular published products
 * so the section is never empty.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="main" tabindex="-1">

	<?php get_template_part( 'template-parts/hero' ); ?>
	<?php get_template_part( 'template-parts/marquee' ); ?>

	<?php if ( class_exists( 'WooCommerce' ) ) : ?>
		<section class="ge-section ge-bg-parchment">
			<div class="ge-container">

				<div class="ge-center">
					<p class="ge-kicker"><?php esc_html_e( 'Featured Research Products', 'golden-era' ); ?></p>
					<h2 class="ge-section-title"><?php esc_html_e( 'Research-Grade Peptide Catalog', 'golden-era' ); ?></h2>
					<hr class="ge-hairline">
				</div>

				<?php
				$products = ge_featured_products( 4 );

				if ( $products->have_posts() ) :
					?>
					<div class="ge-grid">
						<?php
						while ( $products->have_posts() ) :
							$products->the_post();
							wc_get_template_part( 'content', 'product' );
						endwhile;
						?>
					</div>
					<?php
					wp_reset_postdata();
				else :
					?>
					<p class="ge-center ge-lede" style="margin-top:2rem">
						<?php esc_html_e( 'No products published yet.', 'golden-era' ); ?>
					</p>
				<?php endif; ?>

				<div class="ge-center" style="margin-top:3rem">
					<a class="ge-btn ge-btn--dark" href="<?php echo esc_url( ge_shop_url() ); ?>">
						<?php esc_html_e( 'View All Peptides', 'golden-era' ); ?>
					</a>
				</div>

			</div>
		</section>
	<?php endif; ?>

	<?php get_template_part( 'template-parts/quality' ); ?>

	<?php
	get_template_part( 'template-parts/faq', null, array(
		'limit'    => 5,
		'show_all' => true,
	) );
	?>

</main>

<?php
get_footer();
