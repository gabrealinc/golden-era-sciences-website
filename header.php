<?php
/**
 * Site header.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="ge-skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'golden-era' ); ?></a>

<?php get_template_part( 'template-parts/age-gate' ); ?>

<header class="ge-header">

	<a class="ge-promo" href="<?php echo esc_url( ge_shop_url() ); ?>">
		<?php echo esc_html( apply_filters( 'ge_promo_text', '✦ SHOP NOW →' ) ); ?>
	</a>

	<nav class="ge-nav" aria-label="<?php esc_attr_e( 'Primary', 'golden-era' ); ?>">
		<div class="ge-nav__inner">

			<a class="ge-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo esc_url( ge_logo_url() ); ?>"
				     alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
				     width="320" height="320">
				<span class="ge-brand__name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</a>

			<div class="ge-menu">
				<?php ge_nav( 'primary' ); ?>
			</div>

			<div class="ge-nav__actions">
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<a class="ge-cart-link"
					   href="<?php echo esc_url( wc_get_cart_url() ); ?>"
					   aria-label="<?php esc_attr_e( 'View cart', 'golden-era' ); ?>">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none"
						     stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
						     stroke-linejoin="round" aria-hidden="true" focusable="false">
							<circle cx="9" cy="21" r="1"></circle>
							<circle cx="20" cy="21" r="1"></circle>
							<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
						</svg>
						<?php ge_cart_count_markup(); ?>
					</a>
				<?php endif; ?>

				<button class="ge-burger" type="button"
				        aria-expanded="false" aria-controls="ge-mobile-menu"
				        aria-label="<?php esc_attr_e( 'Toggle menu', 'golden-era' ); ?>">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none"
					     stroke="currentColor" stroke-width="2" stroke-linecap="round"
					     aria-hidden="true" focusable="false">
						<line x1="3" y1="6" x2="21" y2="6"></line>
						<line x1="3" y1="12" x2="21" y2="12"></line>
						<line x1="3" y1="18" x2="21" y2="18"></line>
					</svg>
				</button>
			</div>

		</div>

		<div class="ge-mobile-menu" id="ge-mobile-menu">
			<?php ge_nav( 'primary' ); ?>
		</div>
	</nav>

</header>
