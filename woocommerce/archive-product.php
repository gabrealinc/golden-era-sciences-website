<?php
/**
 * Product archive: the shop, category, and tag pages.
 *
 * Overrides woocommerce/templates/archive-product.php.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' );

$is_tax     = is_product_taxonomy();
$page_title = $is_tax ? single_term_title( '', false ) : __( 'All Peptides', 'golden-era' );
$page_desc  = $is_tax
	? wp_strip_all_tags( term_description() )
	: __( 'Every compound is independently tested, fully traceable, and backed by science.', 'golden-era' );
?>

<header class="ge-page-head">
	<div class="ge-container">
		<?php ge_breadcrumb(); ?>
		<p class="ge-kicker ge-kicker--gold"><?php esc_html_e( 'Research-Grade Peptide Catalog', 'golden-era' ); ?></p>
		<h1 class="ge-page-head__title"><?php echo esc_html( $page_title ); ?></h1>
		<?php if ( $page_desc ) : ?>
			<p class="ge-page-head__sub"><?php echo esc_html( $page_desc ); ?></p>
		<?php endif; ?>
	</div>
</header>

<?php get_template_part( 'template-parts/marquee' ); ?>

<main id="main" tabindex="-1" class="ge-section ge-bg-parchment">
	<div class="ge-container">

		<?php woocommerce_output_all_notices(); ?>

		<div class="ge-shop__toolbar">

			<div class="ge-shop__filters">
				<?php
				$shop_url    = ge_shop_url();
				$current_cat = $is_tax && is_product_category() ? get_queried_object_id() : 0;
				?>
				<a class="ge-chip <?php echo $current_cat ? '' : 'is-active'; ?>"
				   href="<?php echo esc_url( $shop_url ); ?>">
					<?php esc_html_e( 'All Products', 'golden-era' ); ?>
				</a>

				<?php
				$categories = get_terms( array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'orderby'    => 'name',
				) );

				if ( ! is_wp_error( $categories ) ) :
					foreach ( $categories as $category ) :
						if ( 'uncategorized' === $category->slug ) {
							continue;
						}
						?>
						<a class="ge-chip <?php echo ( $current_cat === $category->term_id ) ? 'is-active' : ''; ?>"
						   href="<?php echo esc_url( get_term_link( $category ) ); ?>">
							<?php echo esc_html( $category->name ); ?>
						</a>
						<?php
					endforeach;
				endif;
				?>
			</div>

			<div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
				<?php woocommerce_result_count(); ?>
				<?php woocommerce_catalog_ordering(); ?>
			</div>

		</div>

		<?php if ( woocommerce_product_loop() ) : ?>

			<?php
			/**
			 * woocommerce_before_shop_loop hook. Fired so extensions that
			 * render above the grid keep working.
			 */
			do_action( 'woocommerce_before_shop_loop' );

			woocommerce_product_loop_start();

			if ( wc_get_loop_prop( 'total' ) ) {
				while ( have_posts() ) {
					the_post();
					do_action( 'woocommerce_shop_loop' );
					wc_get_template_part( 'content', 'product' );
				}
			}

			woocommerce_product_loop_end();

			do_action( 'woocommerce_after_shop_loop' );
			?>

		<?php else : ?>

			<?php do_action( 'woocommerce_no_products_found' ); ?>

		<?php endif; ?>

	</div>
</main>

<?php
get_footer( 'shop' );
