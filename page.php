<?php
/**
 * Single page.
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
			<p class="ge-kicker ge-kicker--gold"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<h1 class="ge-page-head__title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="ge-page-head__sub"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</div>
	</header>

	<main id="main" tabindex="-1" class="ge-section">
		<div class="ge-container">
			<div class="ge-prose">
				<?php
				the_content();
				wp_link_pages( array( 'before' => '<nav class="ge-center">', 'after' => '</nav>' ) );
				?>
			</div>
		</div>
	</main>

	<?php
endwhile;

get_footer();
