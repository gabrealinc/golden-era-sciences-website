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
			<p class="ge-kicker ge-kicker--gold"><?php esc_html_e( 'Documentation First', 'golden-era' ); ?></p>

			<h2 class="ge-quality__title"><?php esc_html_e( 'Clear Product Records', 'golden-era' ); ?></h2>

			<hr class="ge-hairline ge-hairline--left">

			<p class="ge-quality__body">
				<?php esc_html_e( 'Review objective product specifications and available batch documentation before placing an order. Certificates of Analysis are organized by product SKU and lot so the applicable record is easy to locate.', 'golden-era' ); ?>
			</p>

			<div class="ge-stats">
				<div class="ge-stat">
					<p class="ge-stat__num"><?php esc_html_e( 'SKU', 'golden-era' ); ?></p>
					<p class="ge-stat__label"><?php esc_html_e( 'Product Matching', 'golden-era' ); ?></p>
				</div>
				<div class="ge-stat">
					<p class="ge-stat__num"><?php esc_html_e( 'LOT', 'golden-era' ); ?></p>
					<p class="ge-stat__label"><?php esc_html_e( 'Batch Traceability', 'golden-era' ); ?></p>
				</div>
			</div>

			<p class="ge-pillars">
				<?php esc_html_e( 'identity | specifications | documentation | handling | traceability', 'golden-era' ); ?>
			</p>

			<div class="ge-btn-row">
				<a class="ge-btn ge-btn--outline" href="<?php echo esc_url( ge_shop_url() ); ?>">
					<?php esc_html_e( 'View All Peptides', 'golden-era' ); ?>
				</a>
				<a class="ge-btn ge-btn--shimmer" href="<?php echo esc_url( ge_coa_library_url() ); ?>" target="_blank" rel="noopener noreferrer">
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
