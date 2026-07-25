<?php
/**
 * Product loop wrapper — opening tag.
 *
 * Overrides WooCommerce's default <ul class="products"> with the theme's
 * CSS grid. content-product.php emits a bare <a> so the cards become direct
 * grid children.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ge-grid">
