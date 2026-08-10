<?php
/**
 * Golden Era Sciences — theme setup.
 *
 * Deliberately build-free: no npm, no Sass, no bundler. WordPress.com
 * GitHub Deployments runs in "Simple" mode, which copies files verbatim
 * with no build step, so everything here must be runnable as committed.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GE_VERSION', '1.0.6' );
define( 'GE_DIR', get_template_directory() );
define( 'GE_URI', get_template_directory_uri() );

/* -------------------------------------------------------------------------
 * Theme supports
 * ---------------------------------------------------------------------- */

add_action( 'after_setup_theme', 'ge_setup' );
function ge_setup() {
	load_theme_textdomain( 'golden-era', GE_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array(
		'height'      => 320,
		'width'       => 320,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array(
		'search-form', 'comment-form', 'comment-list',
		'gallery', 'caption', 'style', 'script',
	) );

	// WooCommerce, including the built-in product gallery behaviours.
	add_theme_support( 'woocommerce', array(
		'thumbnail_image_width' => 800,
		'single_image_width'    => 1200,
		'product_grid'          => array(
			'default_columns' => 4,
			'min_columns'     => 2,
			'max_columns'     => 4,
		),
	) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus( array(
		'primary'       => __( 'Primary Navigation', 'golden-era' ),
		'footer_explore' => __( 'Footer — Explore', 'golden-era' ),
		'footer_legal'  => __( 'Footer — Legal', 'golden-era' ),
	) );
}

/* -------------------------------------------------------------------------
 * Assets
 * ---------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', 'ge_assets' );
function ge_assets() {
	// Google Fonts. Playfair Display for headings, Montserrat as the sans
	// fallback behind Copperplate for UI labels.
	wp_enqueue_style(
		'ge-fonts',
		'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;700;800&family=Montserrat:wght@300;400;500;600;700&display=swap',
		array(),
		null
	);

	// WordPress requires the root style.css to be registered; the real
	// styles live in assets/css/theme.css.
	wp_enqueue_style( 'ge-root', get_stylesheet_uri(), array(), GE_VERSION );
	wp_enqueue_style(
		'ge-theme',
		GE_URI . '/assets/css/theme.css',
		array( 'ge-root' ),
		ge_asset_version( '/assets/css/theme.css' )
	);

	wp_enqueue_script(
		'ge-theme',
		GE_URI . '/assets/js/theme.js',
		array(),
		ge_asset_version( '/assets/js/theme.js' ),
		true
	);

	// Front-end strings, so the JS stays translatable like the rest of the theme.
	wp_localize_script( 'ge-theme', 'geL10n', array(
		'sending'      => __( 'Sending…', 'golden-era' ),
		'subscribed'   => __( 'Subscribed.', 'golden-era' ),
		'genericError' => __( 'Something went wrong. Please try again.', 'golden-era' ),
		'networkError' => __( 'Network error. Please try again.', 'golden-era' ),
		'exitUrl'      => esc_url( apply_filters( 'ge_age_gate_exit_url', 'https://www.google.com' ) ),
	) );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

add_action( 'wp_head', 'ge_preconnect', 1 );
function ge_preconnect() {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

/**
 * Site logo URL, falling back to the bundled asset when no custom logo is set.
 */
function ge_logo_url() {
	$id = get_theme_mod( 'custom_logo' );
	if ( $id ) {
		$src = wp_get_attachment_image_src( $id, 'full' );
		if ( $src ) {
			return $src[0];
		}
	}
	return GE_URI . '/assets/images/logo.png';
}

/**
 * Escaped output of a theme asset URL.
 */
function ge_asset( $path ) {
	return esc_url( GE_URI . '/assets/' . ltrim( $path, '/' ) );
}

/**
 * Cache-busting version for a theme asset.
 *
 * Falls back to GE_VERSION if the file is missing, so a renamed or dropped
 * asset never emits a filemtime() warning on every front-end request.
 *
 * @param string $relative Path relative to the theme root, e.g. '/assets/css/theme.css'.
 */
function ge_asset_version( $relative ) {
	$path = GE_DIR . $relative;
	return file_exists( $path ) ? (string) filemtime( $path ) : GE_VERSION;
}

/**
 * URL of the shop. Falls back to the homepage if WooCommerce is inactive
 * or no shop page has been assigned yet.
 */
function ge_shop_url() {
	if ( class_exists( 'WooCommerce' ) ) {
		$shop_id = wc_get_page_id( 'shop' );
		// wc_get_page_id() returns -1 when unset. Also guard against the page
		// having been trashed, which makes get_permalink() return false and
		// would silently point every "Shop" button at the current page.
		if ( $shop_id > 0 && 'publish' === get_post_status( $shop_id ) ) {
			$url = get_permalink( $shop_id );
			if ( $url ) {
				return $url;
			}
		}
	}
	return home_url( '/' );
}

/**
 * Query for homepage "featured compounds".
 *
 * Products flagged Featured come first. If fewer than $count are flagged, the
 * remainder is topped up with other published products — the featured ones are
 * kept, never replaced.
 *
 * @param int $count How many products to return.
 * @return WP_Query
 */
function ge_featured_products( $count = 4 ) {
	$count = max( 1, (int) $count );

	$base = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'fields'              => 'ids',
		'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'exclude-from-catalog',
				'operator' => 'NOT IN',
			),
		),
	);

	// 1. Products explicitly flagged Featured.
	$featured                = $base;
	$featured['tax_query'][]  = array(
		'taxonomy' => 'product_visibility',
		'field'    => 'name',
		'terms'    => 'featured',
	);
	$ids = get_posts( $featured );

	// 2. Top up with the rest of the catalog, best sellers first.
	if ( count( $ids ) < $count ) {
		$fill                   = $base;
		$fill['posts_per_page'] = $count - count( $ids );
		$fill['post__not_in']   = $ids;
		$fill['orderby']        = array( 'meta_value_num' => 'DESC', 'date' => 'DESC' );
		// EXISTS/NOT EXISTS keeps products that have never sold — an INNER JOIN
		// on total_sales alone would drop imported products that lack the row.
		$fill['meta_query']     = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'OR',
			'total_sales' => array( 'key' => 'total_sales', 'compare' => 'EXISTS', 'type' => 'NUMERIC' ),
			array( 'key' => 'total_sales', 'compare' => 'NOT EXISTS' ),
		);

		$ids = array_merge( $ids, get_posts( $fill ) );
	}

	if ( ! $ids ) {
		// WP_Query with no post__in would return everything; force an empty set.
		return new WP_Query( array( 'post_type' => 'product', 'post__in' => array( 0 ) ) );
	}

	return new WP_Query( array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'post__in'            => $ids,
		'orderby'             => 'post__in',
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
}

/**
 * Inline SVG for a social network. Returns an empty string for unknown networks.
 */
function ge_social_icon( $network ) {
	$icons = array(
		'instagram' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg>',
		'tiktok'    => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 0 1-2.59 2.5 2.59 2.59 0 1 1 .77-5.06V9.7a5.68 5.68 0 0 0-.77-.05 5.66 5.66 0 1 0 5.66 5.66V9.01a7.35 7.35 0 0 0 4.28 1.37V7.3a4.28 4.28 0 0 1-3.2-1.48z"/></svg>',
	);

	return isset( $icons[ $network ] ) ? $icons[ $network ] : '';
}

/**
 * Render a nav menu, falling back to a plain page list if none is assigned
 * so the site is never left without navigation after a fresh deploy.
 */
function ge_nav( $location, $args = array() ) {
	if ( ! has_nav_menu( $location ) ) {
		echo '<ul>';
		wp_list_pages( array( 'title_li' => '', 'depth' => 1, 'number' => 6 ) );
		echo '</ul>';
		return;
	}
	wp_nav_menu( wp_parse_args( $args, array(
		'theme_location' => $location,
		'container'      => false,
		'depth'          => 1,
		'fallback_cb'    => false,
	) ) );
}

/* -------------------------------------------------------------------------
 * Includes
 * ---------------------------------------------------------------------- */

require_once GE_DIR . '/inc/faq-data.php';
require_once GE_DIR . '/inc/subscribe.php';
require_once GE_DIR . '/inc/customizer.php';
require_once GE_DIR . '/inc/coa.php';

if ( class_exists( 'WooCommerce' ) ) {
	require_once GE_DIR . '/inc/woocommerce.php';
}

/* -------------------------------------------------------------------------
 * Editor and misc
 * ---------------------------------------------------------------------- */

add_filter( 'excerpt_more', function () { return '…'; } );
add_filter( 'excerpt_length', function () { return 22; }, 999 );

// Strip the WordPress admin bar margin that offsets the sticky header.
add_action( 'wp_head', function () {
	if ( is_admin_bar_showing() ) {
		echo '<style>.ge-header{top:32px}@media(max-width:782px){.ge-header{top:46px}}</style>';
	}
} );

add_filter( 'body_class', function ( $classes ) {
	if ( ge_age_gate_enabled() ) {
		$classes[] = 'ge-has-agegate';
	}
	return $classes;
} );

/**
 * Age gate toggle. Filterable so it can be disabled without editing the theme:
 *   add_filter( 'ge_age_gate_enabled', '__return_false' );
 */
function ge_age_gate_enabled() {
	return (bool) apply_filters( 'ge_age_gate_enabled', true );
}
