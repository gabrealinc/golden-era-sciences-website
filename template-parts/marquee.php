<?php
/**
 * Scrolling trust banner.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = apply_filters( 'ge_marquee_items', array(
	__( 'RESEARCH PRODUCT CATALOG', 'golden-era' ),
	__( 'BATCH DOCUMENTATION', 'golden-era' ),
	__( 'OBJECTIVE PRODUCT DETAILS', 'golden-era' ),
	__( 'LOT-BASED TRACEABILITY', 'golden-era' ),
	__( 'LABORATORY RESEARCH USE ONLY', 'golden-era' ),
) );

// Duplicated so the -50% translate loops seamlessly.
$loop = array_merge( $items, $items );
?>

<div class="ge-marquee" aria-hidden="true">
	<div class="ge-marquee__track">
		<?php foreach ( $loop as $item ) : ?>
			<span class="ge-marquee__item">
				<?php echo esc_html( $item ); ?>
				<span class="ge-marquee__sep">✦</span>
			</span>
		<?php endforeach; ?>
	</div>
</div>
