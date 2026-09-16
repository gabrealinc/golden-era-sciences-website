<?php
/** Exact current report viewer. Variables come from the validated resolver. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$pdf_url = add_query_arg( 'report_pdf', '1', ge_coa_report_url( $product->get_id(), $type ) );
$download_url = add_query_arg( 'report_download', '1', $pdf_url );
$product_url = get_permalink( $product->get_id() );
$report_title = $product->get_name() . ' – ' . ucfirst( $type ) . ' Report';
add_filter( 'pre_get_document_title', function () use ( $report_title ) { return $report_title . ' | Golden Era Sciences'; } );
get_header();
?>
<main id="main" tabindex="-1" class="ge-section"><div class="ge-container ge-report-viewer">
<p class="ge-kicker ge-kicker--gold">Batch Documentation</p>
<h1><?php echo esc_html( $report_title ); ?></h1>
<p class="ge-report-product">Product: <a href="<?php echo esc_url( $product_url ); ?>"><?php echo esc_html( $product->get_name() ); ?></a><br>SKU: <?php echo esc_html( $product->get_sku() ); ?></p>
<p>Lot: <?php echo esc_html( $record['lot'] ); ?></p>
<p class="ge-report-filename"><?php echo esc_html( $report['name'] ); ?></p>
<nav class="ge-coa-links ge-report-switch" aria-label="Reports for this product and lot">
<?php foreach ( array( 'purity' => 'Purity Report', 'endotoxin' => 'Endotoxin Report' ) as $report_type => $label ) : ?>
<a class="ge-coa" href="<?php echo esc_url( ge_coa_report_url( $product->get_id(), $report_type ) ); ?>"<?php if ( $report_type === $type ) { echo ' aria-current="page"'; } ?>><?php echo esc_html( $label ); ?></a>
<?php endforeach; ?>
</nav>
<div class="ge-coa-links">
<a class="ge-btn ge-btn--dark" href="<?php echo esc_url( $product_url ); ?>">Back to Product</a>
<a class="ge-coa" href="<?php echo esc_url( $download_url ); ?>">Download PDF</a>
<a class="ge-coa" href="<?php echo esc_url( $pdf_url ); ?>">Open PDF directly</a>
<a class="ge-coa" href="<?php echo esc_url( ge_coa_library_url() ); ?>">All Batch Reports</a>
</div>
<p id="ge-report-loading" role="status">Loading the complete report…</p>
<p>If the viewer stays blank or your browser does not display PDFs, use Download PDF above.</p>
<iframe class="ge-report-frame" data-report-frame src="<?php echo esc_url( $pdf_url ); ?>" title="<?php echo esc_attr( $report_title . ' – ' . $record['lot'] ); ?>"></iframe>
<div class="ge-coa-links"><a class="ge-btn ge-btn--dark" href="<?php echo esc_url( $product_url ); ?>">Back to Product</a></div>
<p class="ge-report-caption">For laboratory research use only. Not for human or veterinary use.</p>
</div></main>
<?php get_footer(); ?>
