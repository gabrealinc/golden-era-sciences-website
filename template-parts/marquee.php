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
	__( '99%+ VERIFIED PURITY', 'golden-era' ),
	__( 'MANUFACTURED IN THE USA', 'golden-era' ),
	__( 'RESEARCH FIRST APPROACH', 'golden-era' ),
	__( 'COA FOR EVERY COMPOUND', 'golden-era' ),
	__( 'RESEARCH GRADE', 'golden-era' ),
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
