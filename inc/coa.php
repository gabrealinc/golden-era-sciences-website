<?php
/**
 * Google Drive Certificate of Analysis matching.
 *
 * Feed items are matched to WooCommerce products by an exact SKU prefix:
 * SKU__LOT-NUMBER__YYYY-MM-DD.pdf
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ge_coa_library_url() {
	return 'https://drive.google.com/drive/folders/16yO3ZxYaQoA6iu6qErbXkNaaZKFZhtjz';
}

function ge_coa_feed() {
	$feed_url = get_theme_mod( 'ge_coa_feed_url', '' );
	if ( ! $feed_url ) {
		return array();
	}

	$cache_key = 'ge_coa_feed_' . md5( $feed_url );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return is_array( $cached ) ? $cached : array();
	}

	$response = wp_safe_remote_get( $feed_url, array( 'timeout' => 10 ) );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return array();
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	$items = isset( $data['files'] ) && is_array( $data['files'] ) ? $data['files'] : array();
	set_transient( $cache_key, $items, 15 * MINUTE_IN_SECONDS );
	return $items;
}

function ge_coa_url_for_sku( $sku ) {
	$sku = strtoupper( trim( (string) $sku ) );
	if ( '' === $sku ) {
		return '';
	}

	$matches = array();
	foreach ( ge_coa_feed() as $item ) {
		$name = isset( $item['name'] ) ? (string) $item['name'] : '';
		$url  = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
		if ( ! $url || 0 !== stripos( $name, $sku . '__' ) || ! preg_match( '/\.pdf$/i', $name ) ) {
			continue;
		}

		$date = '0000-00-00';
		if ( preg_match( '/__(\d{4}-\d{2}-\d{2})\.pdf$/i', $name, $parts ) ) {
			$date = $parts[1];
		}
		$matches[] = array( 'date' => $date, 'url' => $url );
	}

	if ( ! $matches ) {
		return '';
	}

	usort( $matches, function ( $a, $b ) {
		return strcmp( $b['date'], $a['date'] );
	} );
	return $matches[0]['url'];
}
