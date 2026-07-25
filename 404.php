<?php
/**
 * 404.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="main" tabindex="-1" class="ge-section ge-bg-espresso" style="min-height:60vh;display:grid;place-items:center">
	<div class="ge-container ge-center">
		<p class="ge-kicker ge-kicker--gold"><?php esc_html_e( 'Error 404', 'golden-era' ); ?></p>
		<h1 class="ge-page-head__title"><span class="ge-shimmer"><?php esc_html_e( 'Page Not Found', 'golden-era' ); ?></span></h1>
		<p class="ge-page-head__sub"><?php esc_html_e( 'The page you are looking for has moved or no longer exists.', 'golden-era' ); ?></p>
		<div class="ge-btn-row" style="justify-content:center">
			<a class="ge-btn ge-btn--gold" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back Home', 'golden-era' ); ?></a>
			<a class="ge-btn ge-btn--outline" href="<?php echo esc_url( ge_shop_url() ); ?>"><?php esc_html_e( 'Shop All Peptides', 'golden-era' ); ?></a>
		</div>
	</div>
</main>

<?php
get_footer();
