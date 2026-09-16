<?php
/** Apply the selling catalog explicitly approved by Gabby on September 16. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'GE_CATALOG_VERSION', '2026-09-16.1' );
add_action( 'init', 'ge_apply_sheet_catalog', 40 );

function ge_apply_sheet_catalog() {
    if ( ! function_exists( 'wc_get_products' ) || GE_CATALOG_VERSION === get_option( 'ge_catalog_version' ) || get_transient( 'ge_catalog_lock' ) ) { return; }
    $reference = json_decode( file_get_contents( GE_DIR . '/tools/catalog-reference.json' ), true );
    $rows = isset( $reference['products'] ) ? $reference['products'] : array();
    $skus = array_column( $rows, 'sku' );
    if ( 30 !== count( $rows ) || 30 !== count( array_unique( $skus ) ) ) { return; }
    set_transient( 'ge_catalog_lock', 1, 5 * MINUTE_IN_SECONDS );
    try {
        $legacy = array(
            'GHK-Cu-50mg' => 108, 'KPV-10mg' => 106, 'Kisspeptin-10mg' => 113,
            'BPC-157-10mg' => 105, 'TB-500-10mg' => 107, 'MOTS-c-10mg' => 110,
            'NAD-1000mg' => 114, 'GLP1-S-20mg' => 122, 'GLP2-T-10mg' => 119,
            'GLOW-70mg' => 117, 'Klow-55mg' => 118, 'Wolverine-20mg' => 115,
            'CJC-1295-Ipamorelin-5mg-5mg' => 116, 'Ipamorelin-10mg' => 111, 'AOD-9604-5mg' => 109,
        );
        // Snapshot all old parents and children before changing any database record.
        if ( ! get_option( 'ge_catalog_backup_' . GE_CATALOG_VERSION ) ) {
            $backup = array( 'created' => gmdate( 'c' ), 'products' => array(), 'prices' => array(), 'images' => array() );
            foreach ( wc_get_products( array( 'status' => array( 'publish', 'draft', 'private' ), 'limit' => -1 ) ) as $old ) {
                $ids = array_merge( array( $old->get_id() ), $old->is_type( 'variable' ) ? $old->get_children() : array() );
                foreach ( $ids as $id ) {
                    $backup['products'][$id] = array( 'post' => get_post( $id, ARRAY_A ), 'meta' => get_post_meta( $id ), 'terms' => wp_get_object_terms( $id, array( 'product_type', 'product_cat', 'product_tag' ), array( 'fields' => 'all' ) ) );
                }
            }
            foreach ( $legacy as $sku => $id ) {
                $old = wc_get_product( $id );
                if ( ! $old ) { throw new Exception( 'Expected legacy product missing: ' . $id ); }
                $backup['images'][$sku] = $old->get_image_id();
                if ( $old->is_type( 'variable' ) ) {
                    foreach ( $old->get_children() as $child ) {
                        $variation = wc_get_product( $child );
                        if ( ! $variation ) { continue; }
                        if ( 119 === $id ) {
                            $strength = strtolower( implode( ' ', $variation->get_attributes() ) );
                            if ( preg_match( '/\b(10|30)[ -]?mg\b/i', $strength, $match ) ) {
                                $backup['prices']['GLP2-T-' . $match[1] . 'mg'] = $variation->get_regular_price();
                            }
                        } else { $backup['prices'][$sku] = $variation->get_regular_price(); }
                    }
                } else { $backup['prices'][$sku] = $old->get_regular_price(); }
            }
            add_option( 'ge_catalog_backup_' . GE_CATALOG_VERSION, $backup, '', false );
        }
        $backup = get_option( 'ge_catalog_backup_' . GE_CATALOG_VERSION );
        $map = (array) get_option( 'ge_catalog_sheet_map', array() );
        $placeholder = ge_catalog_placeholder();
        $category = term_exists( 'research-products', 'product_cat' );
        if ( ! $category ) { $category = wp_insert_term( 'Research Products', 'product_cat', array( 'slug' => 'research-products' ) ); }
        if ( is_wp_error( $category ) ) { throw new Exception( 'Catalog category could not be created.' ); }
        $category_id = (int) ( is_array( $category ) ? $category['term_id'] : $category );
        foreach ( $rows as $row ) {
            $sku = $row['sku'];
            $id = isset( $map[$sku] ) ? absint( $map[$sku] ) : wc_get_product_id_by_sku( $sku );
            if ( ! $id && isset( $legacy[$sku] ) ) { $id = $legacy[$sku]; }
            if ( $id ) {
                $old = wc_get_product( $id );
                if ( $old && $old->is_type( 'variable' ) ) {
                    foreach ( $old->get_children() as $child ) { wp_update_post( array( 'ID' => $child, 'post_status' => 'private' ) ); }
                }
                wp_set_object_terms( $id, 'simple', 'product_type' );
                wc_delete_product_transients( $id );
                $item = new WC_Product_Simple( $id );
            } else { $item = new WC_Product_Simple(); }
            $item->set_name( $row['websiteName'] );
            $item->set_sku( $sku );
            $item->set_status( 'publish' );
            $item->set_catalog_visibility( 'visible' );
            $item->set_category_ids( array( $category_id ) );
            $item->set_tag_ids( array() );
            $item->set_featured( ! empty( $row['popular'] ) );
            $attributes = array();
            foreach ( array( 'Strength' => $row['strength'], 'Vial Size' => $row['vialSize'] ) as $name => $value ) {
                $attribute = new WC_Product_Attribute();
                $attribute->set_name( $name );
                $attribute->set_options( array( $value ) );
                $attribute->set_visible( true );
                $attribute->set_variation( false );
                $attributes[] = $attribute;
            }
            $item->set_attributes( $attributes );
            $item->set_default_attributes( array() );
            $price = isset( $row['retailPrice'] ) && '' !== $row['retailPrice'] ? $row['retailPrice'] : ( isset( $backup['prices'][$sku] ) ? $backup['prices'][$sku] : '' );
            $item->set_regular_price( $price );
            $item->set_sale_price( '' );
            $item->set_price( $price );
            if ( ! $id ) { $item->set_manage_stock( false ); $item->set_stock_status( 'instock' ); }
            $image = isset( $backup['images'][$sku] ) ? $backup['images'][$sku] : 0;
            // Changed vial sizes and new strengths use a neutral image, never an old strength's label.
            if ( in_array( $sku, array( 'NAD-1000mg', 'GLOW-70mg' ), true ) ) { $image = 0; }
            $item->set_image_id( $image ?: $placeholder );
            $spec = $row['subtitle'] . '. Strength: ' . $row['strength'] . '. Vial size: ' . $row['vialSize'] . '.';
            $item->set_short_description( $spec . ' For laboratory research use only.' );
            $description = '<p>' . esc_html( $spec ) . '</p><p>Lot: ' . esc_html( $row['lot'] ) . '.</p>';
            if ( ! empty( $row['manufactured'] ) ) { $description .= '<p>Manufacture date: ' . esc_html( $row['manufactured'] ) . '. Expiration date: ' . esc_html( $row['expires'] ) . '.</p>'; }
            $description .= '<h2>Batch Documentation</h2><p>' . ( $row['coaDeclared'] ? 'Available purity and endotoxin reports are linked in Batch Reports for this exact product and lot.' : 'Current batch reports are not available for this product.' ) . '</p>';
            $description .= '<h2>Research Use Disclaimer</h2><p>For Research Use Only. Not for human or veterinary use. Supplied strictly for laboratory research; not intended to diagnose, treat, cure, or prevent any condition.</p>';
            $item->set_description( $description );
            $id = $item->save();
            if ( ! $id ) { throw new Exception( 'Product save failed: ' . $sku ); }
            $map[$sku] = $id;
            update_option( 'ge_catalog_sheet_map', $map, false );
            update_post_meta( $id, '_ge_catalog_sheet_row', $row['sheetRow'] );
            update_post_meta( $id, '_ge_catalog_lot', $row['lot'] );
            update_post_meta( $id, '_ge_catalog_coa_declared', $row['coaDeclared'] ? 'yes' : 'no' );
            update_post_meta( $id, '_ge_catalog_pricing_pending', '' === (string) $price ? 'yes' : 'no' );
            wc_delete_product_transients( $id );
        }
        // Retire unmatched old strengths without deleting order history or records.
        foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1 ) ) as $item ) {
            if ( ! in_array( $item->get_id(), array_values( $map ), true ) ) { $item->set_status( 'draft' ); $item->save(); }
        }
        update_option( 'ge_catalog_version', GE_CATALOG_VERSION, false );
        delete_option( 'ge_catalog_error' );
        flush_rewrite_rules( false );
        ge_coa_queue_gallery();
    } catch ( Exception $error ) { update_option( 'ge_catalog_error', $error->getMessage(), false ); }
    finally { delete_transient( 'ge_catalog_lock' ); }
}

function ge_catalog_placeholder() {
    $id = absint( get_option( 'ge_catalog_placeholder' ) );
    if ( $id && wp_attachment_is_image( $id ) ) { return $id; }
    $bytes = file_get_contents( GE_DIR . '/assets/images/vial-placeholder.jpg' );
    $upload = wp_upload_bits( 'golden-era-research-vial.jpg', null, $bytes );
    if ( $upload['error'] ) { throw new Exception( 'Neutral product image could not be imported.' ); }
    $id = wp_insert_attachment( array( 'post_mime_type' => 'image/jpeg', 'post_title' => 'Golden Era Sciences research vial', 'post_status' => 'inherit' ), $upload['file'], 0, true );
    if ( is_wp_error( $id ) ) { throw new Exception( 'Neutral product image could not be saved.' ); }
    update_post_meta( $id, '_wp_attachment_image_alt', 'Neutral research vial illustration; see the listed product specifications.' );
    update_option( 'ge_catalog_placeholder', $id, false );
    return $id;
}

add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
    return '' === $product->get_price() && 'yes' === get_post_meta( $product->get_id(), '_ge_catalog_pricing_pending', true ) ? '<span class="ge-pricing-pending">Pricing pending</span>' : $html;
}, 20, 2 );
