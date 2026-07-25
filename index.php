<?php
/**
 * Fallback template: blog index, archives, search results.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( is_search() ) {
	// get_search_query() esc_attr's by default; passing false avoids a second
	// pass through esc_html() below rendering "A&B" as "A&amp;B".
	$title  = sprintf( __( 'Search results for "%s"', 'golden-era' ), get_search_query( false ) );
	$kicker = __( 'Search', 'golden-era' );
} elseif ( is_archive() ) {
	$title  = get_the_archive_title();
	$kicker = __( 'Archive', 'golden-era' );
} else {
	// get_option() returns '0' when no Posts page is set, which get_post()
	// treats as empty and falls back to the global $post — i.e. the first post
	// of the loop. Guard explicitly so the fallback title actually applies.
	$blog_id = (int) get_option( 'page_for_posts' );
	$title   = $blog_id ? get_the_title( $blog_id ) : __( 'Journal', 'golden-era' );
	$kicker = __( 'Research Notes', 'golden-era' );
}
?>

<header class="ge-page-head">
	<div class="ge-container">
		<p class="ge-kicker ge-kicker--gold"><?php echo esc_html( $kicker ); ?></p>
		<h1 class="ge-page-head__title"><?php echo esc_html( wp_strip_all_tags( $title ) ); ?></h1>
	</div>
</header>

<main id="main" tabindex="-1" class="ge-section">
	<div class="ge-container">

		<?php if ( have_posts() ) : ?>
			<div class="ge-grid">
				<?php while ( have_posts() ) : the_post(); ?>
					<a class="ge-card" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="ge-card__media">
								<?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?>
							</div>
						<?php endif; ?>
						<div class="ge-card__body">
							<h2 class="ge-card__title"><?php the_title(); ?></h2>
							<p class="ge-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
							<div class="ge-card__foot">
								<span class="ge-card__price" style="font-size:.75rem;font-weight:400">
									<?php echo esc_html( get_the_date() ); ?>
								</span>
								<span class="ge-card__cta"><?php esc_html_e( 'Read →', 'golden-era' ); ?></span>
							</div>
						</div>
					</a>
				<?php endwhile; ?>
			</div>

			<div class="ge-center" style="margin-top:3rem">
				<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
			</div>

		<?php else : ?>
			<p class="ge-lede ge-center"><?php esc_html_e( 'Nothing found.', 'golden-era' ); ?></p>
			<div class="ge-center" style="margin-top:1.5rem"><?php get_search_form(); ?></div>
		<?php endif; ?>

	</div>
</main>

<?php
get_footer();
