<?php
/**
 * FAQ content.
 *
 * Kept as a filterable array so the copy lives in version control and
 * deploys with the theme. To manage these in the WordPress admin instead,
 * register a CPT and return its posts through the `ge_faqs` filter.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param int|null $limit Return only the first N entries.
 * @return array<int, array{q: string, a: string}>
 */
function ge_faqs( $limit = null ) {
	$faqs = array(
		array(
			'q' => __( 'What are Golden Era Sciences products intended for?', 'golden-era' ),
			'a' => __( 'All materials offered by Golden Era Sciences are supplied exclusively for laboratory research use. They are not for human consumption, not for veterinary use, and not intended for medical, diagnostic, or therapeutic purposes. By purchasing, you acknowledge that these materials will be used strictly within a qualified research setting.', 'golden-era' ),
		),
		array(
			'q' => __( 'Have these products been evaluated by the FDA?', 'golden-era' ),
			'a' => __( 'No. Products available through Golden Era Sciences have not been evaluated by the U.S. Food and Drug Administration (FDA) and are not intended to diagnose, treat, cure, or prevent any condition.', 'golden-era' ),
		),
		array(
			'q' => __( 'Do you provide usage or administration guidance?', 'golden-era' ),
			'a' => __( 'No. We do not provide instructions, protocols, or guidance regarding application or use. All materials are intended for professionals familiar with proper research methodologies and handling standards.', 'golden-era' ),
		),
		array(
			'q' => __( 'Can these materials be used outside of a research environment?', 'golden-era' ),
			'a' => __( 'No. Use is strictly limited to controlled laboratory environments. Any use outside of this scope is not permitted.', 'golden-era' ),
		),
		array(
			'q' => __( 'How do you ensure consistency and quality?', 'golden-era' ),
			'a' => __( 'Each batch is produced and handled with a focus on high purity standards (≥99%), independent third-party testing, and full batch traceability. This ensures reliable, research-grade materials across all products.', 'golden-era' ),
		),
		array(
			'q' => __( 'How can I verify what I am receiving?', 'golden-era' ),
			'a' => __( 'Every product is tied to a batch-specific Certificate of Analysis (COA). These reports provide identity confirmation, purity metrics, and analytical testing data.', 'golden-era' ),
		),
		array(
			'q' => __( 'What is a COA and why does it matter?', 'golden-era' ),
			'a' => __( 'A Certificate of Analysis is a third-party laboratory document that verifies the composition and analytical profile of a compound. It exists to support transparency and research validation.', 'golden-era' ),
		),
		array(
			'q' => __( 'Where are your products manufactured?', 'golden-era' ),
			'a' => __( 'All materials are produced in the United States under controlled manufacturing and handling environments.', 'golden-era' ),
		),
		array(
			'q' => __( 'Who can place an order?', 'golden-era' ),
			'a' => __( 'By placing an order, you confirm that you are 21 years of age or older, are purchasing for research purposes only, and understand how to properly handle laboratory materials. We reserve the right to limit or refuse orders if necessary.', 'golden-era' ),
		),
		array(
			'q' => __( 'Do I need credentials to purchase?', 'golden-era' ),
			'a' => __( 'Not in all cases. However, purchasers are expected to have the knowledge and capability to handle research materials responsibly. Additional verification may be requested if needed.', 'golden-era' ),
		),
		array(
			'q' => __( 'What payment methods are accepted?', 'golden-era' ),
			'a' => __( 'We accept major credit cards through secure third-party processing systems. Sensitive payment information is not stored directly by Golden Era Sciences.', 'golden-era' ),
		),
		array(
			'q' => __( 'How quickly are orders processed?', 'golden-era' ),
			'a' => __( 'Orders are typically prepared and dispatched within 1–2 business days. Processing times may vary slightly during periods of elevated demand.', 'golden-era' ),
		),
		array(
			'q' => __( 'How long does delivery take?', 'golden-era' ),
			'a' => __( 'Delivery timelines depend on location and carrier performance. Typical U.S. delivery window is 3–7 business days.', 'golden-era' ),
		),
		array(
			'q' => __( 'Do you offer international shipping?', 'golden-era' ),
			'a' => __( 'International orders may be accommodated upon request. Customers are responsible for understanding import regulations and ensuring compliance with local laws. We are not responsible for delays or issues caused by customs.', 'golden-era' ),
		),
		array(
			'q' => __( 'Why might an order be delayed?', 'golden-era' ),
			'a' => __( 'Delays may occur due to inventory availability, order verification, or carrier-related issues. If applicable, you will be notified.', 'golden-era' ),
		),
		array(
			'q' => __( 'Can I return a product?', 'golden-era' ),
			'a' => __( 'No. All sales are final due to the nature of research materials and integrity requirements.', 'golden-era' ),
		),
		array(
			'q' => __( 'What if something is wrong with my order?', 'golden-era' ),
			'a' => __( 'If your order arrives damaged, incorrect, or incomplete, contact us within 7 days of delivery with your order number and clear photos. Approved cases will be resolved with a replacement.', 'golden-era' ),
		),
		array(
			'q' => __( 'What happens if I enter the wrong shipping address?', 'golden-era' ),
			'a' => __( 'Customers are responsible for providing accurate shipping information. We are not responsible for delivery failures or lost shipments due to incorrect addresses.', 'golden-era' ),
		),
		array(
			'q' => __( 'How should materials be stored?', 'golden-era' ),
			'a' => __( 'Proper storage is essential for maintaining stability. General guidelines include refrigeration (2–8°C), protection from light, and retention in original packaging.', 'golden-era' ),
		),
		array(
			'q' => __( 'What does "lyophilized" mean?', 'golden-era' ),
			'a' => __( 'Lyophilization is a freeze-drying process that removes moisture from a compound. Product-specific handling requirements should be confirmed from the label and available batch documentation.', 'golden-era' ),
		),
		array(
			'q' => __( 'What is the difference between lyophilized and non-lyophilized materials?', 'golden-era' ),
			'a' => __( 'Lyophilized materials are freeze-dried. Non-lyophilized materials are not freeze-dried. Follow the handling information provided for the specific product.', 'golden-era' ),
		),
		array(
			'q' => __( 'Why does the vial appear vacuum sealed?', 'golden-era' ),
			'a' => __( 'Many products are sealed under vacuum to preserve integrity. Variations in pressure are normal and do not impact quality.', 'golden-era' ),
		),
		array(
			'q' => __( 'What vial sizes are used?', 'golden-era' ),
			'a' => __( 'We utilize 3 mL and 6 mL vials. Selection depends on formulation and volume requirements.', 'golden-era' ),
		),
		array(
			'q' => __( 'What responsibility does the purchaser assume?', 'golden-era' ),
			'a' => __( 'By purchasing, you accept full responsibility for proper handling, storage conditions, and use within a research environment. Golden Era Sciences assumes no liability for misuse.', 'golden-era' ),
		),
		array(
			'q' => __( 'Are there location-specific regulations?', 'golden-era' ),
			'a' => __( 'Yes. Buyers are responsible for ensuring compliance with all local, state, and federal regulations prior to purchase.', 'golden-era' ),
		),
		array(
			'q' => __( 'How can I contact Golden Era Sciences?', 'golden-era' ),
			'a' => __( 'For questions regarding orders, shipping, or general inquiries, email us at info@goldenerasciences.com or call 847-461-9035. Response time is typically 24–48 hours.', 'golden-era' ),
		),
	);

	/**
	 * Filter the FAQ list.
	 *
	 * @param array $faqs Array of q/a pairs.
	 */
	$faqs = apply_filters( 'ge_faqs', $faqs );

	if ( $limit ) {
		$faqs = array_slice( $faqs, 0, (int) $limit );
	}

	return $faqs;
}
