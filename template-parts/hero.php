<?php
/**
 * Homepage hero.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="ge-hero">

	<div class="ge-hero__media">
		<video autoplay loop muted playsinline
		       poster="<?php echo ge_asset( 'images/hero.jpg' ); ?>">
			<source src="<?php echo ge_asset( 'video/hero-loop.mp4' ); ?>" type="video/mp4">
		</video>
		<div class="ge-hero__scrim"></div>
	</div>

	<div class="ge-hero__inner">
		<div class="ge-hero__content">

			<p class="ge-kicker ge-kicker--gold">
				<?php esc_html_e( 'Premium Research Peptides', 'golden-era' ); ?>
			</p>

			<h1 class="ge-hero__title">
				<?php esc_html_e( 'THE', 'golden-era' ); ?>
				<span class="ge-shimmer"><?php esc_html_e( 'GOLDEN ERA', 'golden-era' ); ?></span><br>
				<?php esc_html_e( 'STARTS HERE', 'golden-era' ); ?>
			</h1>

			<p class="ge-hero__sub">
				<?php esc_html_e( 'Precision-crafted, rigorously tested peptides for researchers who refuse to compromise on quality.', 'golden-era' ); ?>
			</p>

			<div class="ge-btn-row">
				<a class="ge-btn ge-btn--gold" href="<?php echo esc_url( ge_shop_url() ); ?>">
					<?php esc_html_e( 'Shop Now', 'golden-era' ); ?>
				</a>
				<a class="ge-btn ge-btn--outline" href="<?php echo esc_url( ge_shop_url() ); ?>">
					<?php esc_html_e( 'View COAs', 'golden-era' ); ?>
				</a>
			</div>

		</div>
	</div>

</section>
