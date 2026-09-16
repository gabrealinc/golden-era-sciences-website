<?php
/** Current catalog report index. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<header class="ge-page-head"><div class="ge-container">
<p class="ge-kicker ge-kicker--gold">Product Documentation</p>
<h1 class="ge-page-head__title">Certificates of Analysis</h1>
<p class="ge-page-head__sub">Review available purity and endotoxin reports by product and lot. For laboratory research only. Reports do not establish suitability for human or veterinary use.</p>
</div></header>
<main id="main" tabindex="-1" class="ge-section"><div class="ge-container">
<label for="ge-report-search">Find a product</label>
<input id="ge-report-search" type="search" placeholder="Search product name or SKU" aria-controls="ge-report-list">
<div id="ge-report-list" class="ge-report-list">
<?php
if ( function_exists( 'wc_get_products' ) ) {
    foreach ( wc_get_products( array( 'status' => 'publish', 'visibility' => 'catalog', 'limit' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $item ) {
        $record = ge_coa_record_for_sku( $item->get_sku() );
        printf( '<article class="ge-report-row" data-report-search="%s"><div><h2><a href="%s">%s</a></h2><p>%s</p></div><div>', esc_attr( strtolower( $item->get_name() . ' ' . $item->get_sku() ) ), esc_url( get_permalink( $item->get_id() ) ), esc_html( $item->get_name() ), esc_html( $item->get_sku() ) );
        if ( $record ) {
            echo '<p>Lot: ' . esc_html( $record['lot'] ) . '</p><div class="ge-coa-links">';
            foreach ( array( 'purity' => 'Purity Report', 'endotoxin' => 'Endotoxin Report' ) as $type => $label ) {
                printf( '<a class="ge-coa" target="_blank" rel="noopener noreferrer" href="%s">%s<span class="screen-reader-text"> for %s (PDF, opens in a new tab)</span></a>', esc_url( ge_coa_report_url( $item->get_id(), $type ) ), esc_html( $label ), esc_html( $item->get_name() ) );
            }
            echo '</div>';
        } else { echo '<p>Current matching reports unavailable for this product and strength.</p>'; }
        echo '</div></article>';
    }
}
?>
</div><p id="ge-report-empty" hidden role="status">No matching products.</p>
</div></main>
<?php get_footer(); ?>
