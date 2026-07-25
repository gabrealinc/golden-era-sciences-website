<?php
/**
 * "The Golden Standard" proof section.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$vials = ge_asset( 'images/vials.jpg' );
?>

<section class="ge-quality">

	<div class="ge-quality__bg" style="background-image:url('<?php echo $vials; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>')"></div>
	<div class="ge-quality__scrim"></div>

	<div class="ge-quality__inner">

		<div>
			<p class="ge-kicker ge-kicker--gold"><?php esc_html_e( 'Built Different', 'golden-era' ); ?></p>

			<h2 class="ge-quality__title"><?php esc_html_e( 'The Golden Standard', 'golden-era' ); ?></h2>

			<hr class="ge-hairline ge-hairline--left">

			<p class="ge-quality__body">
				<?php esc_html_e( 'At Golden Era Sciences, we do not guess, we verify. Every batch is synthesized under strict GMP-aligned conditions and sent to independent U.S. laboratories for HPLC and MS testing. You get the data. You get the purity. No compromises.', 'golden-era' ); ?>
			</p>

			<div class="ge-stats">
				<div class="ge-stat">
					<p class="ge-stat__num">99%+</p>
					<p class="ge-stat__label"><?php esc_html_e( 'Purity Guaranteed', 'golden-era' ); ?></p>
				</div>
				<div class="ge-stat">
					<p class="ge-stat__num">100%</p>
					<p class="ge-stat__label"><?php esc_html_e( 'U.S. Manufactured', 'golden-era' ); ?></p>
				</div>
			</div>

			<p class="ge-pillars">
				<?php esc_html_e( 'potency | purity | stability | safety | consistency', 'golden-era' ); ?>
			</p>

			<div class="ge-btn-row">
				<a class="ge-btn ge-btn--outline" href="<?php echo esc_url( ge_shop_url() ); ?>">
					<?php esc_html_e( 'Shop All', 'golden-era' ); ?>
				</a>
				<a class="ge-btn ge-btn--shimmer" href="<?php echo esc_url( ge_shop_url() ); ?>">
					<?php esc_html_e( 'View COAs', 'golden-era' ); ?>
				</a>
			</div>
		</div>

		<div class="ge-quality__figure">
			<img src="<?php echo $vials; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
			     alt="<?php esc_attr_e( 'Golden Era Sciences research-grade peptide vials', 'golden-era' ); ?>"
			     width="1400" height="1400" loading="lazy">
		</div>

	</div>

</section>
