<?php
/** Exact current report viewer. Variables come from the validated resolver. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$pdf_url = add_query_arg( 'report_pdf', '1', ge_coa_report_url( $product->get_id(), $type ) );
$download_url = add_query_arg( 'report_download', '1', $pdf_url );
$report_title = $product->get_name() . ' – ' . ucfirst( $type ) . ' Report';
add_filter( 'pre_get_document_title', function () use ( $report_title ) { return $report_title . ' | Golden Era Sciences'; } );
get_header();
?>
<main id="main" tabindex="-1" class="ge-section"><div class="ge-container ge-report-viewer">
<p class="ge-kicker ge-kicker--gold">Batch Documentation</p>
<h1><?php echo esc_html( $report_title ); ?></h1>
<p>Lot: <?php echo esc_html( $record['lot'] ); ?></p>
<p class="ge-report-filename"><?php echo esc_html( $report['name'] ); ?></p>
<div class="ge-coa-links">
<a class="ge-coa" href="<?php echo esc_url( $download_url ); ?>">Download PDF</a>
<a class="ge-coa" href="<?php echo esc_url( $pdf_url ); ?>">Open PDF directly</a>
<a class="ge-coa" href="<?php echo esc_url( ge_coa_library_url() ); ?>">All Batch Reports</a>
</div>
<p id="ge-report-loading" role="status">Loading the complete report…</p>
<p>If the viewer stays blank or your browser does not display PDFs, use Download PDF above.</p>
<iframe class="ge-report-frame" data-report-frame src="<?php echo esc_url( $pdf_url ); ?>" title="<?php echo esc_attr( $report_title . ' – ' . $record['lot'] ); ?>"></iframe>
<p class="ge-report-caption">For laboratory research use only. Not for human or veterinary use.</p>
</div></main>
<?php get_footer(); ?>
