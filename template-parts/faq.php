<?php
/**
 * FAQ accordion section.
 *
 * Args (via get_template_part third parameter):
 *   limit       int   Show only the first N questions.
 *   show_all    bool  Render a "See All Questions" button.
 *   faqs_url    string Destination for that button.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$limit    = isset( $args['limit'] ) ? (int) $args['limit'] : 0;
$show_all = ! empty( $args['show_all'] );
$faqs     = ge_faqs( $limit ?: null );

if ( ! $faqs ) {
	return;
}
?>

<section class="ge-section ge-bg-cream">
	<div class="ge-container">

		<div class="ge-center">
			<p class="ge-kicker"><?php esc_html_e( 'Research First', 'golden-era' ); ?></p>
			<h2 class="ge-section-title"><?php esc_html_e( 'Frequently Asked Questions', 'golden-era' ); ?></h2>
			<hr class="ge-hairline">
		</div>

		<div class="ge-faq">
			<?php foreach ( $faqs as $i => $faq ) : ?>
				<?php
				$qid = 'ge-faq-q-' . $i;
				$aid = 'ge-faq-a-' . $i;
				?>
				<div class="ge-faq__item">
					<h3>
						<button class="ge-faq__q" type="button"
						        id="<?php echo esc_attr( $qid ); ?>"
						        aria-expanded="false"
						        aria-controls="<?php echo esc_attr( $aid ); ?>">
							<span><?php echo esc_html( $faq['q'] ); ?></span>
							<span class="ge-faq__icon" aria-hidden="true"></span>
						</button>
					</h3>
					<div class="ge-faq__a" id="<?php echo esc_attr( $aid ); ?>"
					     role="region" aria-labelledby="<?php echo esc_attr( $qid ); ?>">
						<?php echo esc_html( $faq['a'] ); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( $show_all ) : ?>
			<?php $faqs_page = ! empty( $args['faqs_url'] ) ? $args['faqs_url'] : home_url( '/faqs/' ); ?>
			<div class="ge-center" style="margin-top:2.5rem">
				<a class="ge-btn ge-btn--outline" style="color:var(--ge-espresso)"
				   href="<?php echo esc_url( $faqs_page ); ?>">
					<?php esc_html_e( 'See All Questions', 'golden-era' ); ?>
				</a>
			</div>
		<?php endif; ?>

	</div>
</section>
