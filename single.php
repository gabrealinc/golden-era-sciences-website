<?php
/**
 * Single post.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<header class="ge-page-head">
		<div class="ge-container">
			<p class="ge-kicker ge-kicker--gold"><?php echo esc_html( get_the_date() ); ?></p>
			<h1 class="ge-page-head__title"><?php the_title(); ?></h1>
		</div>
	</header>

	<main id="main" tabindex="-1" class="ge-section">
		<div class="ge-container">
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="ge-prose" style="margin-bottom:2rem">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
			<?php endif; ?>
			<div class="ge-prose">
				<?php the_content(); ?>
			</div>
			<?php if ( ( comments_open() || get_comments_number() ) && locate_template( 'comments.php' ) ) : ?>
				<div class="ge-prose" style="margin-top:3rem"><?php comments_template(); ?></div>
			<?php endif; ?>
		</div>
	</main>

	<?php
endwhile;

get_footer();
