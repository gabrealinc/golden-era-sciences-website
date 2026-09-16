<?php
/**
 * Google Drive Certificate of Analysis matching.
 *
 * Feed items use the permanent convention:
 * SKU__LOT-NUMBER__REPORT-TYPE.pdf
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ge_coa_library_url() {
	return home_url( '/coas/' );
}

function ge_coa_feed_url() {
    return get_theme_mod( 'ge_coa_feed_url', '' ) ?: 'https://script.google.com/macros/s/AKfycbwU_e-MPeAoATB6nFT6P-Iehv0mbIMz1QUciT9ALIi2p9khMZ9mCZYlyZbZmhq9Fhp3/exec';
}

function ge_coa_pdf_data( $report ) {
    if ( ! preg_match( '~^https://script\.google\.com/macros/s/[a-zA-Z0-9_-]+/exec$~', ge_coa_feed_url() ) ) { return false; }
    $url = add_query_arg( 'report', $report['id'], ge_coa_feed_url() );
    $response = wp_remote_get( $url, array( 'timeout' => 30, 'redirection' => 5, 'limit_response_size' => 12 * MB_IN_BYTES ) );
    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { return false; }
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( ! isset( $data['id'], $data['updated'], $data['pdf'] ) || $report['id'] !== $data['id'] || $report['updated'] !== $data['updated'] ) { return false; }
    $pdf = base64_decode( $data['pdf'], true );
    if ( ! $pdf || 0 !== strpos( $pdf, '%PDF-' ) ) { return false; }
    return array( 'pdf' => $pdf, 'preview' => isset( $data['preview'] ) ? base64_decode( $data['preview'], true ) : '' );
}

function ge_coa_feed() {
    static $memo = null;
    if ( null !== $memo ) { return $memo; }
    $memo = array();
	$GLOBALS['ge_coa_feed_valid'] = false;
	$default_feed_url = 'https://script.google.com/macros/s/AKfycbwU_e-MPeAoATB6nFT6P-Iehv0mbIMz1QUciT9ALIi2p9khMZ9mCZYlyZbZmhq9Fhp3/exec';
	$feed_url         = get_theme_mod( 'ge_coa_feed_url', '' );
	$using_default    = ! $feed_url;
	if ( ! $feed_url ) {
		$feed_url = $default_feed_url;
	}

	$cache_key = 'ge_coa_feed_v2_' . md5( $feed_url );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		$GLOBALS['ge_coa_feed_valid'] = true;
		return $memo = is_array( $cached ) ? $cached : array();
	}

	$request_args = array( 'timeout' => 20, 'redirection' => 5 );
	$response     = $using_default
		? wp_remote_get( $feed_url, $request_args )
		: wp_safe_remote_get( $feed_url, $request_args );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return array();
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! isset( $data['version'] ) || 2 !== (int) $data['version'] || ! isset( $data['files'] ) || ! is_array( $data['files'] ) ) { return array(); }
	$items = $data['files'];
	$GLOBALS['ge_coa_feed_valid'] = true;
	set_transient( $cache_key, $items, 15 * MINUTE_IN_SECONDS );
	return $memo = $items;
}

function ge_coa_feed_sku( $sku ) {
    $aliases = array(
        'GES-GHKCU-50MG'    => 'GHK-Cu-50mg',
        'GES-KPV-10MG'      => 'KPV-10mg',
        'GES-BPC-10MG'      => 'BPC-157-10mg',
        'GES-TB500-10MG'    => 'TB-500-10mg',
        'GES-MOTSC-10MG'    => 'MOTS-c-10mg',
        'GES-NAD-1000MG'    => 'NAD-1000mg',
        'GES-KISS-10MG'     => 'Kisspeptin-10mg',
        'GES-IPA-10MG'      => 'Ipamorelin-10mg',
        'GES-CJC-IPA-BLEND' => 'CJC-1295-Ipamorelin-5mg-5mg',
        'GES-BPC-TB-BLEND'  => 'Wolverine-20mg',
        'GES-GLOW-BLEND'    => 'GLOW-70mg',
        'GES-KLOW-BLEND'    => 'Klow-55mg',
        'GES-GLPT-10MG'     => 'GLP2-T-10mg',
    );

    $normalized = strtoupper( trim( (string) $sku ) );
    return isset( $aliases[ $normalized ] ) ? $aliases[ $normalized ] : trim( (string) $sku );
}

/** One exact, same-lot pair; duplicates and malformed URLs fail closed. */
function ge_coa_record_for_sku( $sku ) {
    return ge_coa_select_record( $sku, ge_coa_feed() );
}

function ge_coa_select_record( $sku, $files ) {
    $feed_sku = ge_coa_feed_sku( $sku );
    if ( ! $feed_sku ) { return array(); }
    $reports = array();
    $lot = '';
    foreach ( $files as $item ) {
        $name = isset( $item['name'] ) ? $item['name'] : '';
        if ( ! preg_match( '/^' . preg_quote( $feed_sku, '/' ) . '__([^_]+)__(PURITY|ENDOTOXIN)\.pdf$/i', $name, $parts ) ) { continue; }
        $type = strtolower( $parts[2] );
        $url = isset( $item['url'] ) ? $item['url'] : '';
        $id = isset( $item['id'] ) ? $item['id'] : '';
        if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $id ) || ! preg_match( '~^https://drive\.google\.com/file/d/' . preg_quote( $id, '~' ) . '/(?:view)?(?:\?.*)?$~', $url ) ) { return array(); }
        if ( isset( $reports[$type] ) || ( $lot && $lot !== $parts[1] ) ) { return array(); }
        $lot = $parts[1];
        $reports[$type] = $item;
    }
    return count( $reports ) === 2 ? array( 'lot' => $lot, 'reports' => $reports ) : array();
}

function ge_coa_report_url( $product_id, $type ) {
    return add_query_arg( array( 'ge_report' => absint( $product_id ), 'report_type' => $type ), home_url( '/' ) );
}

function ge_coa_reports_for_sku( $sku ) {
    $record = ge_coa_record_for_sku( $sku );
    if ( ! $record ) { return array(); }
    return array( 'purity' => $record['reports']['purity']['url'], 'endotoxin' => $record['reports']['endotoxin']['url'] );
}

// Resolve at click time so cached pages never point to a superseded lot.
add_action( 'template_redirect', function () {
    if ( ! isset( $_GET['ge_report'] ) ) { return; }
    nocache_headers();
    $product = function_exists( 'wc_get_product' ) ? wc_get_product( absint( $_GET['ge_report'] ) ) : false;
    $type = isset( $_GET['report_type'] ) ? sanitize_key( wp_unslash( $_GET['report_type'] ) ) : '';
    $record = $product && 'publish' === $product->get_status() ? ge_coa_record_for_sku( $product->get_sku() ) : array();
    if ( ! in_array( $type, array( 'purity', 'endotoxin' ), true ) || ! $record ) {
        wp_die( esc_html__( 'A current matching report is not available. Please contact info@goldenerasciences.com.', 'golden-era' ), esc_html__( 'Report unavailable', 'golden-era' ), array( 'response' => 404 ) );
    }
    $report = $record['reports'][$type];
    $data = ge_coa_pdf_data( $report );
    if ( ! $data ) { wp_die( 'The current report is temporarily unavailable. Please try again later.', 'Report unavailable', array( 'response' => 503 ) ); }
    header( 'Content-Type: application/pdf' );
    header( 'Content-Disposition: inline; filename="' . sanitize_file_name( $report['name'] ) . '"' );
    header( 'X-Content-Type-Options: nosniff' );
    header( 'Content-Length: ' . strlen( $data['pdf'] ) );
    echo $data['pdf']; // Validated PDF bytes from the approved current feed.
    exit;
} );

function ge_coa_render_links( $product ) {
    $record = ge_coa_record_for_sku( $product->get_sku() );
    echo '<section class="ge-product-reports" id="product-reports" aria-label="Batch reports">';
    echo '<h2>' . esc_html__( 'Batch Reports', 'golden-era' ) . '</h2>';
    if ( ! $record ) {
        echo '<p>' . esc_html__( 'Current matching reports are not available for this product and strength. Contact us for documentation.', 'golden-era' ) . '</p>';
    } else {
        echo '<p>' . esc_html__( 'Lot:', 'golden-era' ) . ' ' . esc_html( $record['lot'] ) . '</p><div class="ge-coa-links">';
        foreach ( array( 'purity' => 'Purity Report', 'endotoxin' => 'Endotoxin Report' ) as $type => $label ) {
            printf( '<a class="ge-coa" href="%s" target="_blank" rel="noopener noreferrer">%s<span class="screen-reader-text"> %s</span></a>', esc_url( ge_coa_report_url( $product->get_id(), $type ) ), esc_html( $label ), esc_html( $product->get_name() . ' – ' . $record['lot'] . ' (PDF, opens in a new tab)' ) );
        }
        echo '</div>';
        echo '<p class="ge-report-caption">' . esc_html__( 'Gallery images preview the reports. Open the PDF for the complete record.', 'golden-era' ) . '</p>';
    }
    echo '</section>';
}

// Create only the dedicated index page. Existing site content is preserved.
add_action( 'admin_init', function () {
    if ( ! current_user_can( 'manage_woocommerce' ) || get_page_by_path( 'coas' ) ) { return; }
    wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'coas', 'post_title' => 'Certificates of Analysis' ) );
} );

require_once __DIR__ . '/coa-gallery.php';
