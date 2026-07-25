<?php
/**
 * Site footer.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<footer class="ge-footer">
	<div class="ge-footer__inner">

		<div class="ge-footer__signup">
			<div>
				<h3><?php esc_html_e( 'Join the Golden Era list', 'golden-era' ); ?></h3>
				<p><?php esc_html_e( 'Get new compound drops, restock alerts, and research updates by email.', 'golden-era' ); ?></p>
			</div>
			<?php get_template_part( 'template-parts/subscribe' ); ?>
		</div>

		<div class="ge-footer__cols">

			<div class="ge-footer__brand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<img src="<?php echo esc_url( ge_logo_url() ); ?>"
					     alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
					     width="320" height="320" loading="lazy">
					<span class="ge-footer__wordmark">
						<span><?php esc_html_e( 'GOLDEN ERA', 'golden-era' ); ?></span>
						<span><?php esc_html_e( 'SCIENCES', 'golden-era' ); ?></span>
					</span>
				</a>
				<p class="ge-footer__tagline">
					<?php esc_html_e( 'Precision-crafted, rigorously tested peptides for the researchers who refuse to compromise.', 'golden-era' ); ?>
				</p>

				<?php
				$socials = array(
					'instagram' => get_theme_mod( 'ge_instagram' ),
					'tiktok'    => get_theme_mod( 'ge_tiktok' ),
				);
				$socials = array_filter( $socials );
				if ( $socials ) :
					?>
					<div class="ge-social">
						<?php foreach ( $socials as $network => $url ) : ?>
							<a href="<?php echo esc_url( $url ); ?>"
							   target="_blank" rel="noopener noreferrer">
								<span class="ge-screen-reader-text"><?php echo esc_html( ucfirst( $network ) ); ?></span>
								<?php echo ge_social_icon( $network ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="ge-footer__col">
				<h4><?php esc_html_e( 'Explore', 'golden-era' ); ?></h4>
				<?php ge_nav( 'footer_explore' ); ?>
			</div>

			<div class="ge-footer__col">
				<h4><?php esc_html_e( 'Legal', 'golden-era' ); ?></h4>
				<?php ge_nav( 'footer_legal' ); ?>
			</div>

		</div>

		<div class="ge-footer__legal">
			<p>
				<?php
				esc_html_e(
					'For Research Use Only. Not for Human or Veterinary Use. All products are intended strictly for laboratory research use only. Not for human consumption.',
					'golden-era'
				);
				?>
			</p>
			<p class="ge-footer__copy">
				<?php
				printf(
					/* translators: 1: year, 2: site name */
					esc_html__( '© %1$s %2$s. All rights reserved.', 'golden-era' ),
					esc_html( gmdate( 'Y' ) ),
					esc_html( get_bloginfo( 'name' ) )
				);
				?>
			</p>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
