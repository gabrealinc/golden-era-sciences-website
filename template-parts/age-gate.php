<?php
/**
 * Age verification gate.
 *
 * Shown until the visitor confirms they are 21 or older. The choice is
 * stored in localStorage, so it does not vary the page cache.
 *
 * Disable with: add_filter( 'ge_age_gate_enabled', '__return_false' );
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) || ! ge_age_gate_enabled() ) {
	return;
}
?>

<div class="ge-agegate" id="ge-agegate" hidden
     role="dialog" aria-modal="true" aria-labelledby="ge-agegate-title">
	<div class="ge-agegate__panel">

		<img src="<?php echo esc_url( ge_logo_url() ); ?>"
		     alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
		     width="320" height="320">

		<h2 class="ge-agegate__title" id="ge-agegate-title">
			<span class="ge-shimmer"><?php esc_html_e( 'Age Verification', 'golden-era' ); ?></span>
		</h2>

		<p class="ge-agegate__body">
			<?php
			esc_html_e(
				'You must be 21 years or older to enter. All products are supplied for laboratory research use only and are not for human or veterinary consumption.',
				'golden-era'
			);
			?>
		</p>

		<div class="ge-agegate__actions">
			<button type="button" class="ge-btn ge-btn--gold" data-ge-agegate="accept">
				<?php esc_html_e( 'I am 21 or older', 'golden-era' ); ?>
			</button>
			<button type="button" class="ge-btn ge-btn--outline" data-ge-agegate="decline">
				<?php esc_html_e( 'Exit', 'golden-era' ); ?>
			</button>
		</div>

		<p class="ge-agegate__deny" data-ge-agegate-message aria-live="assertive" hidden>
			<?php esc_html_e( 'You must be 21 or older to view this site. Redirecting…', 'golden-era' ); ?>
		</p>

	</div>
</div>
