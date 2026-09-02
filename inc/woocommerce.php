<?php
/**
 * WooCommerce integration.
 *
 * Loaded from functions.php only when WooCommerce is active.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Layout
 *
 * The theme's own templates supply the page wrappers, so WooCommerce's
 * default wrapper callbacks are removed. Note the templates do not fire
 * `woocommerce_before_main_content` at all; breadcrumbs and the structured
 * data that hangs off them are handled explicitly by ge_breadcrumb() below.
 * ---------------------------------------------------------------------- */

remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

// No shop sidebar in this design.
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

// Default archive title is rendered by our own template header.
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );

/**
 * Breadcrumbs.
 *
 * Called directly from the theme's shop and product templates. Beyond
 * navigation, this is what emits WooCommerce's BreadcrumbList structured
 * data (WC_Structured_Data hooks generate_breadcrumblist_data to the
 * `woocommerce_breadcrumb` action), which Google uses for the breadcrumb
 * trail in Product rich results. Dropping it costs real search presence.
 */
function ge_breadcrumb() {
	woocommerce_breadcrumb( array(
		'delimiter'   => '<span class="ge-breadcrumb__sep">/</span>',
		'wrap_before' => '<nav class="ge-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'golden-era' ) . '">',
		'wrap_after'  => '</nav>',
		'before'      => '',
		'after'       => '',
	) );
}

// Product categories are intentionally not part of the public information
// architecture. Keep the breadcrumb useful without exposing "Uncategorized".
add_filter( 'woocommerce_get_breadcrumb', 'ge_product_breadcrumbs' );
function ge_product_breadcrumbs( $crumbs ) {
	if ( ! is_product() ) {
		return $crumbs;
	}
	$product = end( $crumbs );
	$home    = reset( $crumbs );
	return array(
		$home,
		array( __( 'All Peptides', 'golden-era' ), ge_shop_url() ),
		$product,
	);
}

/* -------------------------------------------------------------------------
 * Grid
 * ---------------------------------------------------------------------- */

add_filter( 'loop_shop_columns', function () { return 4; }, 20 );
add_filter( 'loop_shop_per_page', function () { return 24; }, 20 );

/*
 * archive-product.php renders the result count and the sort dropdown itself,
 * inside the theme's own toolbar. `woocommerce_before_shop_loop` is still
 * fired there so third-party extensions keep working, but its two default
 * callbacks have to come off or both controls render twice.
 */
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

// Related products: 4 across, one row.
add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;
	return $args;
}, 20 );

/* -------------------------------------------------------------------------
 * Loop item markup
 *
 * The default loop hooks are removed in favour of the markup in
 * woocommerce/content-product.php, which mirrors the PeptideCard component
 * from the original design.
 * ---------------------------------------------------------------------- */

remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

/**
 * Ratings and reviews are not part of this brand's design.
 *
 * Customer reviews on a research-chemical catalog invite use claims, which
 * is a compliance problem as much as a design one. Remove the filter below
 * if reviews are wanted later.
 */
add_filter( 'woocommerce_product_get_rating_html', '__return_empty_string' );
// woocommerce_enable_review_rating is an *option*, not a filter, so it has to
// be short-circuited through option_ rather than add_filter on the bare name.
add_filter( 'option_woocommerce_enable_review_rating', function () { return 'no'; } );
add_filter( 'comments_open', 'ge_close_product_reviews', 10, 2 );
function ge_close_product_reviews( $open, $post_id ) {
	if ( 'product' === get_post_type( $post_id ) ) {
		return false;
	}
	return $open;
}

add_filter( 'woocommerce_product_tabs', 'ge_product_tabs', 98 );
function ge_product_tabs( $tabs ) {
	unset( $tabs['reviews'] );

	if ( isset( $tabs['description'] ) ) {
		$tabs['description']['title'] = __( 'Details', 'golden-era' );
	}

	return $tabs;
}

/* -------------------------------------------------------------------------
 * Single product
 * ---------------------------------------------------------------------- */

// Woo's default meta block is replaced by ge_product_meta().
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );

add_action( 'woocommerce_single_product_summary', 'ge_product_meta', 41 );
function ge_product_meta() {
	global $product;
	if ( ! $product ) {
		return;
	}

	$rows = array();

	if ( $product->get_sku() ) {
		$rows[] = array( __( 'SKU', 'golden-era' ), $product->get_sku() );
	}

	$vial = ge_product_attribute( $product, array( 'vial', 'size' ) );
	if ( $vial ) {
		$rows[] = array( __( 'Vial Size', 'golden-era' ), $vial );
	}

	$purity = ge_product_attribute( $product, array( 'purity' ) );
	if ( $purity ) {
		$rows[] = array( __( 'Purity', 'golden-era' ), $purity );
	}

	$rows[] = array(
		__( 'Availability', 'golden-era' ),
		$product->is_in_stock() ? __( 'In stock', 'golden-era' ) : __( 'Out of stock', 'golden-era' ),
	);

	if ( ! $rows ) {
		return;
	}

	echo '<div class="ge-product__meta">';
	foreach ( $rows as $row ) {
		printf(
			'<span>%s <strong>%s</strong></span>',
			esc_html( $row[0] ),
			esc_html( $row[1] )
		);
	}
	echo '</div>';
}

/**
 * Find a product attribute value by partial name match.
 *
 * @param WC_Product $product  Product object.
 * @param string[]   $needles  Lowercase fragments to match against the attribute name.
 * @return string Empty string when no match.
 */
function ge_product_attribute( $product, $needles ) {
	foreach ( $product->get_attributes() as $name => $attribute ) {
		$haystack = strtolower( is_string( $name ) ? $name : '' );
		foreach ( $needles as $needle ) {
			if ( false !== strpos( $haystack, $needle ) ) {
				$value = $product->get_attribute( $name );
				if ( $value ) {
					return $value;
				}
			}
		}
	}
	return '';
}

/**
 * Batch report links.
 */
add_action( 'woocommerce_single_product_summary', 'ge_product_coa', 45 );
function ge_product_coa() {
	global $product;
	if ( ! $product ) {
		return;
	}

    $reports = ge_coa_reports_for_sku( $product->get_sku() );
    if ( empty( $reports['purity'] ) && empty( $reports['endotoxin'] ) ) {
        return;
    }

    echo '<div class="ge-coa-links">';
    if ( ! empty( $reports['purity'] ) ) {
        printf( '<a class="ge-coa" href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $reports['purity'] ), esc_html__( 'Purity Report', 'golden-era' ) );
    }
    if ( ! empty( $reports['endotoxin'] ) ) {
        printf( '<a class="ge-coa" href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $reports['endotoxin'] ), esc_html__( 'Endotoxin Report', 'golden-era' ) );
    }
    echo '</div>';
}

/**
 * Research-use-only notice on every product page.
 */
add_action( 'woocommerce_single_product_summary', 'ge_product_rou', 65 );
function ge_product_rou() {
	echo '<p class="ge-rou">';
	echo esc_html__(
		'For Research Use Only. Not for human or veterinary use. This product is supplied strictly for laboratory research by qualified professionals and is not intended to diagnose, treat, cure, or prevent any condition.',
		'golden-era'
	);
	echo '</p>';
}

/* -------------------------------------------------------------------------
 * Cart count in the header
 * ---------------------------------------------------------------------- */

add_filter( 'woocommerce_add_to_cart_fragments', 'ge_cart_count_fragment' );
function ge_cart_count_fragment( $fragments ) {
	ob_start();
	ge_cart_count_markup();
	$fragments['span.ge-cart-link__count'] = ob_get_clean();
	return $fragments;
}

function ge_cart_count_markup() {
	$count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
	printf(
		'<span class="ge-cart-link__count"%s>%s</span>',
		$count ? '' : ' hidden',
		esc_html( $count )
	);
}

/* -------------------------------------------------------------------------
 * Misc
 * ---------------------------------------------------------------------- */

/**
 * Placeholder image for products without a featured image.
 *
 * Two filters are needed: WooCommerce resolves the src through
 * `woocommerce_placeholder_img_src`, but when a placeholder *attachment*
 * exists it builds the markup itself and ignores that src. Filtering the
 * final HTML covers both paths.
 */
add_filter( 'woocommerce_placeholder_img_src', 'ge_placeholder_src' );
function ge_placeholder_src() {
	return GE_URI . '/assets/images/vial-placeholder.jpg';
}

add_filter( 'woocommerce_placeholder_img', 'ge_placeholder_img', 10, 4 );
function ge_placeholder_img( $image, $size, $dimensions, $attr = array() ) {
	$width  = is_array( $dimensions ) && isset( $dimensions['width'] ) ? (int) $dimensions['width'] : 800;
	$height = is_array( $dimensions ) && isset( $dimensions['height'] ) ? (int) $dimensions['height'] : 800;

	// Respect caller-supplied attributes (alt text, eager loading above the
	// fold) instead of hardcoding them and silently dropping the caller's.
	$attr = wp_parse_args( is_array( $attr ) ? $attr : array(), array(
		'class'    => 'woocommerce-placeholder wp-post-image',
		'alt'      => __( 'Research peptide vial', 'golden-era' ),
		'loading'  => 'lazy',
		'decoding' => 'async',
	) );

	$out = '';
	foreach ( $attr as $name => $value ) {
		if ( '' === $value || false === $value ) {
			continue;
		}
		$out .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
	}

	return sprintf(
		'<img src="%s" width="%d" height="%d"%s />',
		esc_url( ge_placeholder_src() ),
		$width,
		$height,
		$out
	);
}

// "Select options" reads better than "Read more" for variable products.
add_filter( 'woocommerce_product_add_to_cart_text', function ( $text, $product ) {
	if ( $product && $product->is_type( 'variable' ) ) {
		return __( 'Select Options', 'golden-era' );
	}
	return $text;
}, 10, 2 );
