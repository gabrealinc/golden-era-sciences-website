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
        'GES-KLOW-BLEND'    => 'KLOW-80mg',
        'GES-GLPT-10MG'     => 'GLP2-T-10mg',
    );

    $normalized = strtoupper( trim( (string) $sku ) );
    return isset( $aliases[ $normalized ] ) ? $aliases[ $normalized ] : trim( (string) $sku );
}

function ge_coa_reports_for_sku( $sku ) {
    $feed_sku = ge_coa_feed_sku( $sku );
    if ( '' === $feed_sku ) {
        return array();
    }

    $matches = array( 'purity' => array(), 'endotoxin' => array() );
    foreach ( ge_coa_feed() as $item ) {
        $name = isset( $item['name'] ) ? (string) $item['name'] : '';
        $url  = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
        if ( ! $url || 0 !== stripos( $name, $feed_sku . '__' ) || ! preg_match( '/\.pdf$/i', $name ) ) {
            continue;
        }

        if ( ! preg_match( '/__([^_]+)__(PURITY|ENDOTOXIN)\.pdf$/i', $name, $parts ) ) {
            continue;
        }
        $type = strtolower( $parts[2] );
        $matches[ $type ][] = array( 'lot' => $parts[1], 'url' => $url );
    }

    $reports = array();
    foreach ( $matches as $type => $items ) {
        if ( ! $items ) {
            continue;
        }
        usort( $items, function ( $a, $b ) {
            return strcmp( $b['lot'], $a['lot'] );
        } );
        $reports[ $type ] = $items[0]['url'];
    }
    return $reports;
}
