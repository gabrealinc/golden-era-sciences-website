<?php
/**
 * Comments.
 *
 * Exists so comments_template() never falls through to
 * wp-includes/theme-compat/comments.php, which calls _deprecated_file()
 * and renders unstyled 2010-era markup.
 *
 * Comments are closed on products (see inc/woocommerce.php); this is for
 * blog posts only.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( post_password_required() ) {
	return;
}
?>

<section class="ge-comments">

	<?php if ( have_comments() ) : ?>
		<h2 class="ge-section-title" style="font-size:1.5rem">
			<?php
			printf(
				/* translators: %s: comment count */
				esc_html( _n( '%s Comment', '%s Comments', get_comments_number(), 'golden-era' ) ),
				esc_html( number_format_i18n( get_comments_number() ) )
			);
			?>
		</h2>

		<ol class="ge-comments__list" style="margin-top:1.5rem">
			<?php
			wp_list_comments( array(
				'style'       => 'ol',
				'short_ping'  => true,
				'avatar_size' => 48,
			) );
			?>
		</ol>

		<?php the_comments_pagination(); ?>
	<?php endif; ?>

	<?php
	comment_form( array(
		'class_submit'  => 'ge-btn ge-btn--gold',
		'title_reply'   => __( 'Leave a comment', 'golden-era' ),
	) );
	?>

</section>
