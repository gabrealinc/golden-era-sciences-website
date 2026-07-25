<?php
/**
 * Search form.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form class="ge-form ge-form--light" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="ge-screen-reader-text" for="ge-search"><?php esc_html_e( 'Search', 'golden-era' ); ?></label>
	<input class="ge-input" id="ge-search" type="search" name="s"
	       value="<?php echo esc_attr( get_search_query() ); ?>"
	       placeholder="<?php esc_attr_e( 'Search…', 'golden-era' ); ?>">
	<button class="ge-btn ge-btn--gold" type="submit"><?php esc_html_e( 'Search', 'golden-era' ); ?></button>
</form>
