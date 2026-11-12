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

	<div class="ge-hero__media<?php echo is_user_logged_in() ? '' : ' ge-hero__media--public'; ?>">
		<?php if ( is_user_logged_in() ) : ?>
			<video autoplay loop muted playsinline
			       poster="<?php echo ge_asset( 'images/hero-fallback.jpg' ); ?>">
				<source src="<?php echo ge_asset( 'video/hero-loop.mp4' ); ?>" type="video/mp4">
			</video>
		<?php endif; ?>
		<div class="ge-hero__scrim"></div>
	</div>

	<div class="ge-hero__inner">
		<div class="ge-hero__content">

			<p class="ge-kicker ge-kicker--gold">
				<?php echo is_user_logged_in() ? esc_html__( 'Premium Research Peptides', 'golden-era' ) : esc_html__( 'Golden Era Sciences', 'golden-era' ); ?>
			</p>

			<h1 class="ge-hero__title">
				<?php esc_html_e( 'THE', 'golden-era' ); ?>
				<span class="ge-shimmer"><?php esc_html_e( 'GOLDEN ERA', 'golden-era' ); ?></span><br>
				<?php esc_html_e( 'STARTS HERE', 'golden-era' ); ?>
			</h1>

			<p class="ge-hero__sub">
				<?php
				echo is_user_logged_in()
					? esc_html__( 'Research products presented with clear specifications and accessible batch documentation.', 'golden-era' )
					: esc_html__( 'Create an account or sign in to access the private research catalog.', 'golden-era' );
				?>
			</p>

			<div class="ge-btn-row">
				<a class="ge-btn ge-btn--gold" href="<?php echo esc_url( ge_shop_url() ); ?>">
					<?php echo is_user_logged_in() ? esc_html__( 'View Research Catalog', 'golden-era' ) : esc_html__( 'Customer Login or Register', 'golden-era' ); ?>
				</a>
				<?php if ( is_user_logged_in() ) : ?>
					<a class="ge-btn ge-btn--outline" href="<?php echo esc_url( ge_coa_library_url() ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'View COAs', 'golden-era' ); ?>
					</a>
				<?php endif; ?>
			</div>

		</div>
	</div>

</section>
