<?php
/** WooCommerce report previews. Only theme-managed gallery assets are replaced. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ge_coa_image_key( $report ) {
    return $report['id'] . '|' . ( isset( $report['updated'] ) ? $report['updated'] : '' );
}

function ge_coa_sync_product( $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product || 'publish' !== $product->get_status() ) { return; }
    $lock = 'ge_coa_lock_' . absint( $product_id );
    if ( get_transient( $lock ) ) { return; }
    set_transient( $lock, 1, 120 );
    try {
        $record = ge_coa_record_for_sku( $product->get_sku() );
        if ( empty( $GLOBALS['ge_coa_feed_valid'] ) ) { return; }
        $old = (array) get_post_meta( $product_id, '_ge_coa_gallery', true );
        $old_ids = array_filter( array_map( 'absint', array_column( $old, 'attachment' ) ) );
        $old_pdf_ids = array_filter( array_map( 'absint', array_column( $old, 'pdf_attachment' ) ) );
        $base = array_values( array_diff( $product->get_gallery_image_ids( 'edit' ), $old_ids ) );
        $new = array();
        if ( $record ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            foreach ( array( 'purity', 'endotoxin' ) as $type ) {
                $report = $record['reports'][$type];
                $key = ge_coa_image_key( $report );
                if ( isset( $old[$type]['key'], $old[$type]['attachment'] ) && $old[$type]['key'] === $key && wp_attachment_is_image( $old[$type]['attachment'] ) ) {
                    $new[$type] = $old[$type];
                    continue;
                }
                $data = ge_coa_pdf_data( $report );
                if ( ! $data ) { throw new Exception( 'Current PDF delivery failed.' ); }
                $pdf_upload = wp_upload_bits( sanitize_file_name( $report['name'] ), null, $data['pdf'] );
                if ( $pdf_upload['error'] ) { throw new Exception( $pdf_upload['error'] ); }
                $pdf_attachment = wp_insert_attachment( array( 'post_mime_type' => 'application/pdf', 'post_title' => $report['name'], 'post_status' => 'inherit' ), $pdf_upload['file'], $product_id, true );
                if ( is_wp_error( $pdf_attachment ) ) { wp_delete_file( $pdf_upload['file'] ); throw new Exception( 'PDF import failed.' ); }
                update_post_meta( $pdf_attachment, '_ge_coa_source_key', $key );
                $new[$type] = array( 'key' => $key, 'attachment' => 0, 'pdf_attachment' => $pdf_attachment );
                $editor = wp_get_image_editor( $pdf_upload['file'] );
                $bytes = '';
                if ( ! is_wp_error( $editor ) ) {
                    $preview_path = $pdf_upload['file'] . '-preview.png';
                    $saved = $editor->save( $preview_path, 'image/png' );
                    if ( ! is_wp_error( $saved ) ) { $bytes = file_get_contents( $saved['path'] ); wp_delete_file( $saved['path'] ); }
                }
                if ( ! $bytes ) { $bytes = $data['preview']; }
                $image = $bytes ? @getimagesizefromstring( $bytes ) : false;
                if ( ! $image || ! in_array( $image['mime'], array( 'image/png', 'image/jpeg' ), true ) || $image[0] < 600 ) { throw new Exception( 'PDF rendering unavailable or preview too small.' ); }
                $extension = 'image/png' === $image['mime'] ? '.png' : '.jpg';
                $upload = wp_upload_bits( sanitize_file_name( $product->get_sku() . '-' . $record['lot'] . '-' . $type ) . $extension, null, $bytes );
                if ( $upload['error'] ) { throw new Exception( $upload['error'] ); }
                $title = $product->get_name() . ' – ' . $record['lot'] . ' – ' . ucfirst( $type ) . ' Report (page 1 preview)';
                $attachment = wp_insert_attachment( array( 'post_mime_type' => $image['mime'], 'post_title' => $title, 'post_status' => 'inherit', 'post_excerpt' => 'Open the matching PDF from Batch Reports for the complete record.' ), $upload['file'], $product_id, true );
                if ( is_wp_error( $attachment ) ) { wp_delete_file( $upload['file'] ); throw new Exception( $attachment->get_error_message() ); }
                update_post_meta( $attachment, '_ge_coa_source_key', $key );
                update_post_meta( $attachment, '_wp_attachment_image_alt', $title );
                wp_update_attachment_metadata( $attachment, wp_generate_attachment_metadata( $attachment, $upload['file'] ) );
                $new[$type] = array( 'key' => $key, 'attachment' => $attachment, 'pdf_attachment' => $pdf_attachment );
            }
        }
        $new_ids = array_column( $new, 'attachment' );
        // Main product image remains #1; purity and endotoxin become #2 and #3.
        $product->set_gallery_image_ids( array_merge( $new_ids, $base ) );
        $product->save();
        update_post_meta( $product_id, '_ge_coa_gallery', $new );
        delete_post_meta( $product_id, '_ge_coa_sync_error' );
        foreach ( array_diff( array_merge( $old_ids, $old_pdf_ids ), array_merge( $new_ids, array_column( $new, 'pdf_attachment' ) ) ) as $id ) {
            if ( get_post_meta( $id, '_ge_coa_source_key', true ) ) { wp_delete_attachment( $id, true ); }
        }
    } catch ( Exception $error ) {
        // Leave the prior pair intact in storage, hide it from current product output.
        foreach ( $new as $entry ) {
            if ( ! empty( $entry['attachment'] ) && ! in_array( $entry['attachment'], $old_ids, true ) ) { wp_delete_attachment( $entry['attachment'], true ); }
            if ( ! empty( $entry['pdf_attachment'] ) && ! in_array( $entry['pdf_attachment'], $old_pdf_ids, true ) ) { wp_delete_attachment( $entry['pdf_attachment'], true ); }
        }
        update_post_meta( $product_id, '_ge_coa_sync_error', $error->getMessage() );
    } finally { delete_transient( $lock ); }
}
add_action( 'ge_coa_sync_product', 'ge_coa_sync_product' );

function ge_coa_queue_gallery() {
    if ( ! get_option( 'ge_coa_gallery_ready' ) || ! function_exists( 'wc_get_products' ) || ! function_exists( 'as_enqueue_async_action' ) ) { return; }
    foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'return' => 'ids' ) ) as $id ) {
        as_enqueue_async_action( 'ge_coa_sync_product', array( $id ), 'ge-coa', true );
    }
}
add_action( 'ge_coa_queue_gallery', 'ge_coa_queue_gallery' );
add_action( 'init', function () {
    if ( ! function_exists( 'wc_get_products' ) ) { return; }
    if ( ! wp_next_scheduled( 'ge_coa_queue_gallery' ) ) { wp_schedule_event( time() + 5, 'hourly', 'ge_coa_queue_gallery' ); }
} );

// Hide superseded managed previews until the replacement pair has been imported.
add_filter( 'woocommerce_product_get_gallery_image_ids', function ( $ids, $product ) {
    $record = ge_coa_record_for_sku( $product->get_sku() );
    $keys = $record ? array_map( 'ge_coa_image_key', $record['reports'] ) : array();
    return array_values( array_filter( $ids, function ( $id ) use ( $keys ) {
        $source = get_post_meta( $id, '_ge_coa_source_key', true );
        return ! $source || in_array( $source, $keys, true );
    } ) );
}, 10, 2 );

add_action( 'admin_menu', function () {
    add_management_page( 'Batch Reports', 'Batch Reports', 'manage_woocommerce', 'ge-batch-reports', 'ge_coa_admin_page' );
} );
function ge_coa_admin_page() {
    echo '<div class="wrap"><h1>Batch Reports</h1><p>The active pair follows the newest uploaded lot for an exact SKU and strength. Upload both locked filenames to the verified Purity Reports and Endotoxin Reports folders. Duplicate reports are withheld. Gallery previews synchronize hourly; use Sync now for an immediate update.</p><table class="widefat"><thead><tr><th>Product</th><th>Current reports</th><th>Gallery</th><th>Action</th></tr></thead><tbody>';
    foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $product ) {
        $record = ge_coa_record_for_sku( $product->get_sku() );
        $images = (array) get_post_meta( $product->get_id(), '_ge_coa_gallery', true );
        $error = get_post_meta( $product->get_id(), '_ge_coa_sync_error', true );
        printf( '<tr><td>%s<br>%s</td><td>%s</td><td>%s</td><td><form method="post" action="%s"><input type="hidden" name="action" value="ge_coa_sync"><input type="hidden" name="product_id" value="%d">', esc_html( $product->get_name() ), esc_html( $product->get_sku() ), esc_html( $record ? $record['lot'] : 'No exact current pair' ), esc_html( $error ? $error : count( $images ) . ' managed preview(s)' ), esc_url( admin_url( 'admin-post.php' ) ), $product->get_id() );
        wp_nonce_field( 'ge_coa_sync' );
        echo '<button class="button">Sync now</button></form></td></tr>';
    }
    echo '</tbody></table></div>';
}
add_action( 'admin_post_ge_coa_sync', function () {
    if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Unauthorized', '', array( 'response' => 403 ) ); }
    check_admin_referer( 'ge_coa_sync' );
    $id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    ge_coa_sync_product( $id );
    if ( ! get_option( 'ge_coa_gallery_ready' ) && count( (array) get_post_meta( $id, '_ge_coa_gallery', true ) ) === 2 && ! get_post_meta( $id, '_ge_coa_sync_error', true ) ) {
        update_option( 'ge_coa_gallery_ready', 1, false );
        ge_coa_queue_gallery();
    }
    wp_safe_redirect( admin_url( 'tools.php?page=ge-batch-reports' ) ); exit;
} );
