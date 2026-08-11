<?php
/**
 * Versioned, one-time compliance content migration.
 *
 * WordPress.com GitHub Deployments only copies theme files. This migration
 * safely applies the approved database-backed content changes on the first
 * request after version 1.1.0 is deployed. It is idempotent and stores a
 * private backup of every changed record in wp_options before writing.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GE_COMPLIANCE_MIGRATION_VERSION', '2026-08-11.3' );

add_action( 'init', 'ge_run_compliance_migration', 30 );

function ge_run_compliance_migration() {
	if ( GE_COMPLIANCE_MIGRATION_VERSION === get_option( 'ge_compliance_migration_version' ) ) {
		return;
	}
	if ( get_transient( 'ge_compliance_migration_lock' ) ) {
		return;
	}

	// Wait for WooCommerce so a partial deployment never marks the work done.
	if ( ! post_type_exists( 'product' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}
	set_transient( 'ge_compliance_migration_lock', 1, 5 * MINUTE_IN_SECONDS );

	$backup = array(
		'created_gmt' => gmdate( 'c' ),
		'posts'       => array(),
		'post_meta'   => array(),
		'terms'       => array(),
		'menu_items'  => array(),
	);

	ge_migrate_product_copy( $backup );
	ge_migrate_contact_page( $backup );
	ge_retire_legacy_pages( $backup );
	ge_remove_biological_categories( $backup );
	ge_assign_research_product_category( $backup );
	ge_repair_catalog_menu_links( $backup );

	// add_option() deliberately refuses to overwrite the original snapshot.
	add_option( 'ge_compliance_migration_backup_' . str_replace( '.', '_', GE_COMPLIANCE_MIGRATION_VERSION ), $backup, '', false );
	update_option( 'ge_compliance_migration_version', GE_COMPLIANCE_MIGRATION_VERSION, false );
	flush_rewrite_rules( false );
	delete_transient( 'ge_compliance_migration_lock' );
}

function ge_compliance_product_copy() {
	return array(
		'AOD-9604' => array(
			'Synthetic peptide fragment, 5 mg lyophilized powder. >98% purity (HPLC verified). Supplied for laboratory research use only.',
			'AOD-9604 is a synthetic peptide corresponding to the C-terminal region of human growth hormone, residues 177 to 191. Supplied as a lyophilized powder in a 3 mL clear vial.',
		),
		'BPC-157' => array(
			'Synthetic pentadecapeptide, 10 mg lyophilized powder. >98% purity (HPLC verified). Supplied for laboratory research use only.',
			'BPC-157 is a synthetic pentadecapeptide composed of 15 amino acids. Supplied as a lyophilized powder in a 3 mL clear vial.',
		),
		'TB-500' => array(
			'Synthetic peptide, 10 mg lyophilized powder. >98% purity (HPLC verified). Supplied for laboratory research use only.',
			'TB-500 is a synthetic peptide analogue based on a 43-amino-acid sequence of thymosin beta-4. Supplied as a lyophilized powder in a 3 mL clear vial.',
		),
		'BPC-157 / TB-500' => array(
			'Two-peptide research blend, 10 mg BPC-157 and 10 mg TB-500, lyophilized. >98% purity (HPLC verified). For laboratory research use only.',
			'A combined preparation containing 10 mg BPC-157 (synthetic pentadecapeptide) and 10 mg TB-500 (synthetic thymosin beta-4 analogue) in a single 3 mL clear vial, supplied as a lyophilized powder.',
		),
		'CJC-1295 / Ipamorelin' => array(
			'Two-peptide research blend, 5 mg CJC-1295 (No DAC) and 5 mg Ipamorelin, lyophilized. >98% purity (HPLC verified). For laboratory research use only.',
			'A combined preparation containing 5 mg CJC-1295 without DAC (synthetic 29-amino-acid peptide analogue) and 5 mg Ipamorelin (synthetic pentapeptide) in a single 3 mL clear vial, supplied as a lyophilized powder.',
		),
		'GHK-Cu' => array(
			'Copper peptide complex, 50 mg lyophilized powder. >98% purity (HPLC verified). Supplied for laboratory research use only.',
			'GHK-Cu is a copper complex of the naturally occurring tripeptide glycyl-L-histidyl-L-lysine. Supplied as a lyophilized powder in a 3 mL clear vial.',
		),
		'GHK-Cu / BPC-157 / TB-500' => array(
			'Three-peptide research blend, 50 mg GHK-Cu, 10 mg BPC-157, 10 mg TB-500, lyophilized. >98% purity (HPLC verified). For laboratory research use only.',
			'A combined preparation containing 50 mg GHK-Cu (copper tripeptide complex), 10 mg BPC-157 (synthetic pentadecapeptide), and 10 mg TB-500 (synthetic thymosin beta-4 analogue) in a single 6 mL amber vial, supplied as a lyophilized powder.',
		),
		'GHK-Cu / KPV / BPC-157 / TB-500' => array(
			'Four-peptide research blend, 25 mg GHK-Cu, 10 mg KPV, 10 mg BPC-157, 10 mg TB-500, lyophilized. >98% purity (HPLC verified). For laboratory research use only.',
			'A combined preparation containing 25 mg GHK-Cu (copper tripeptide complex), 10 mg KPV (synthetic tripeptide), 10 mg BPC-157 (synthetic pentadecapeptide), and 10 mg TB-500 (synthetic thymosin beta-4 analogue) in a single 6 mL clear vial, supplied as a lyophilized powder.',
		),
		'GLP-R' => array(
			'Retatrutide, 24 mg lyophilized powder. >98% purity (HPLC verified). Supplied for laboratory research use only.',
			'GLP-R contains retatrutide, a synthetic peptide compound. Supplied as a lyophilized powder in a 3 mL clear vial.',
		),
		'GLP-T' => array(
			'Tirzepatide, lyophilized powder in 10 mg and 30 mg options. >98% purity (HPLC verified). Supplied for laboratory research use only.',
			'GLP-T contains tirzepatide, a synthetic peptide compound. Supplied as a lyophilized powder in a 3 mL clear vial, available in 10 mg and 30 mg strengths.',
		),
		'GLP-S' => array(
			'Semaglutide, 20 mg lyophilized powder. >98% purity (HPLC verified). Supplied for laboratory research use only.',
			'GLP-S contains semaglutide, a synthetic peptide compound. Supplied as a lyophilized powder in a 6 mL amber vial.',
		),
		'Ipamorelin' => array(
			'Synthetic pentapeptide, 10 mg lyophilized powder. >98% purity (HPLC verified). Supplied for laboratory research use only.',
			'Ipamorelin is a synthetic pentapeptide. Supplied as a lyophilized powder in a 3 mL clear vial.',
		),
		'Kisspeptin' => array(
			'Synthetic peptide, 10 mg lyophilized powder. >98% purity (HPLC verified). Supplied for laboratory research use only.',
			'Kisspeptin is a synthetic peptide derived from the KISS1 gene product. Supplied as a lyophilized powder in a 3 mL clear vial.',
		),
		'KPV' => array(
			'Synthetic tripeptide, 10 mg lyophilized powder. >98% purity (HPLC verified). Supplied for laboratory research use only.',
			'KPV is a synthetic tripeptide composed of the amino acids lysine, proline, and valine. Supplied as a lyophilized powder in a 3 mL clear vial.',
		),
		'MOTS-c' => array(
			'Synthetic peptide, 10 mg lyophilized powder. >98% purity (HPLC verified). Supplied for laboratory research use only.',
			'MOTS-c is a synthetic 16-amino-acid peptide corresponding to a sequence encoded within the mitochondrial 12S rRNA region. Supplied as a lyophilized powder in a 3 mL clear vial.',
		),
		'Sermorelin' => array(
			'Synthetic 29-amino-acid peptide, 10 mg lyophilized powder. >98% purity (HPLC verified). Supplied for laboratory research use only.',
			'Sermorelin is a synthetic peptide comprising a 29-amino-acid sequence. Supplied as a lyophilized powder in a 3 mL clear vial.',
		),
		'NAD+' => array(
			'Nicotinamide adenine dinucleotide, 1000 mg lyophilized powder. >98% purity (HPLC verified). Supplied for laboratory research use only.',
			'NAD+ (nicotinamide adenine dinucleotide) is a naturally occurring coenzyme supplied here in purified, lyophilized form in a 6 mL amber vial.',
		),
	);
}

function ge_migrate_product_copy( &$backup ) {
	$shared = '<h2>Quality and Documentation</h2><p>Each batch is produced in the United States under controlled manufacturing conditions and verified by independent third-party laboratories using HPLC and MS analysis. Available batch documentation can be accessed through the COA library.</p><h2>Storage</h2><p>Store lyophilized material refrigerated at 2 to 8°C, protected from light, in its original packaging.</p><h2>Research Use Disclaimer</h2><p>For Research Use Only. Not for human or veterinary use. This product is supplied strictly for laboratory research by qualified professionals and is not intended to diagnose, treat, cure, or prevent any condition.</p>';

	foreach ( ge_compliance_product_copy() as $title => $copy ) {
		$posts = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'title' => $title, 'posts_per_page' => 1 ) );
		if ( ! $posts ) {
			continue;
		}
		$post = $posts[0];
		$backup['posts'][ $post->ID ] = array( 'title' => $post->post_title, 'status' => $post->post_status, 'content' => $post->post_content, 'excerpt' => $post->post_excerpt );
		wp_update_post( array( 'ID' => $post->ID, 'post_excerpt' => $copy[0], 'post_content' => '<p>' . esc_html( $copy[1] ) . '</p>' . $shared ) );

		foreach ( array( '_yoast_wpseo_metadesc', 'rank_math_description', '_aioseo_description' ) as $meta_key ) {
			$old_value = get_post_meta( $post->ID, $meta_key, true );
			if ( '' !== $old_value ) {
				$backup['post_meta'][ $post->ID ][ $meta_key ] = $old_value;
				update_post_meta( $post->ID, $meta_key, $copy[0] );
			}
		}

		$thumbnail_id = get_post_thumbnail_id( $post->ID );
		if ( $thumbnail_id ) {
			$backup['post_meta'][ $thumbnail_id ]['_wp_attachment_image_alt'] = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
			update_post_meta( $thumbnail_id, '_wp_attachment_image_alt', $post->post_title . ' research product vial' );
		}
	}
}

function ge_migrate_contact_page( &$backup ) {
	$page = get_page_by_path( 'contact' );
	if ( ! $page ) {
		return;
	}
	$backup['posts'][ $page->ID ] = array( 'title' => $page->post_title, 'status' => $page->post_status, 'content' => $page->post_content, 'excerpt' => $page->post_excerpt );
	$content = $page->post_content;
	$content = str_replace( array( 'Get in Touch', 'Let&#8217;s talk research.', "Let’s talk research." ), array( 'Contact Golden Era Sciences', 'Contact Us', 'Contact Us' ), $content );
	$content = preg_replace( '#<p class="ges-contact-sub">.*?</p>#s', '<p class="ges-contact-sub">Questions about products, orders, COAs, or wholesale? Reach out and we will get back to you within 24 to 48 hours.</p><p class="ges-contact-sub"><a href="mailto:info@goldenerasciences.com">info@goldenerasciences.com</a></p><p class="ges-contact-sub">Fill out the form below and our team will follow up by email.</p>', $content, 1 );
	$content = preg_replace( '#<div class="ges-book-call-box">.*?</div>\s*</div>\s*<style>.*?</style>#s', '', $content );
	$content = preg_replace( '#(<div class="jetpack_forms_contact-form-custom-success-message">).*?(</div>)#s', '$1Thank you. Your inquiry has been received, and our team will follow up by email.$2', $content );
	wp_update_post( array( 'ID' => $page->ID, 'post_title' => 'Contact Us', 'post_excerpt' => 'Questions about products, orders, COAs, or wholesale? Contact Golden Era Sciences.', 'post_content' => $content ) );
}

// Jetpack may preserve its success message in block attributes even after the
// page body is migrated. Sanitize the rendered Contact page as a final guard.
add_filter( 'the_content', 'ge_compliance_contact_render_guard', 99 );
function ge_compliance_contact_render_guard( $content ) {
	if ( ! is_page( 'contact' ) ) {
		return ge_remove_booking_links( $content );
	}
	// The legacy editor saved its inline CSS as visible text after WordPress
	// sanitized the style tag. page.php already supplies the approved header,
	// so remove that entire duplicate legacy header before the form.
	$content = preg_replace( '#^\s*(?:<style[^>]*>)?\s*\.ges-contact-wrap\{.*?\.ges-contact-sub\{.*?\}\s*(?:</style>)?\s*#s', '', $content, 1 );
	$content = preg_replace( '#<div class="ges-contact-wrap">.*?</div>\s*#s', '', $content, 1 );
	$content = preg_replace( '#<p class="ges-contact-sub">.*?</p>\s*#s', '', $content );
	$content = preg_replace(
		'#(<div class="jetpack_forms_contact-form-custom-success-message">).*?(</div>)#s',
		'$1Thank you. Your inquiry has been received, and our team will follow up by email.$2',
		$content
	);
	$content = preg_replace( '#<button\b[^>]*class=["\'][^"\']*pushbutton-wide[^"\']*["\'][^>]*>.*?</button>#is', '', $content );
	return ge_remove_booking_links( $content );
}

function ge_remove_booking_links( $content ) {
	return preg_replace( '#<a\b[^>]*href=["\'][^"\']*(?:calendar\.google\.com/calendar/.*/appointments|calendly\.com)[^"\']*["\'][^>]*>.*?</a>#is', '', $content );
}

add_filter( 'wp_nav_menu_objects', 'ge_remove_booking_menu_items' );
function ge_remove_booking_menu_items( $items ) {
	return array_values( array_filter( $items, function ( $item ) {
		$url = isset( $item->url ) ? $item->url : '';
		return ! preg_match( '#(?:calendar\.google\.com/calendar/.*/appointments|calendly\.com)#i', $url );
	} ) );
}

// Keep every Jetpack form submission on the Contact page routed to the single
// approved inbox, regardless of the form author's account email.
add_filter( 'render_block_data', 'ge_contact_form_recipient', 20, 2 );
function ge_contact_form_recipient( $parsed_block, $source_block ) {
	if ( is_page( 'contact' ) && isset( $parsed_block['blockName'] ) && 'jetpack/contact-form' === $parsed_block['blockName'] ) {
		$parsed_block['attrs']['to']                 = 'info@goldenerasciences.com';
		$parsed_block['attrs']['emailNotifications'] = 'yes';
	}
	return $parsed_block;
}

function ge_retire_legacy_pages( &$backup ) {
	foreach ( array( 'all-peptides', 'popular-peptides', 'education', 'sample-page', 'peptide-calculator', 'calculator' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( ! $page || 'trash' === $page->post_status ) {
			continue;
		}
		ge_trash_page_tree( $page->ID, $backup );
	}
}

function ge_trash_page_tree( $page_id, &$backup ) {
	$children = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'post_parent' => $page_id, 'posts_per_page' => -1 ) );
	foreach ( $children as $child ) {
		ge_trash_page_tree( $child->ID, $backup );
	}
	$page = get_post( $page_id );
	if ( $page && 'trash' !== $page->post_status ) {
		$backup['posts'][ $page->ID ] = array( 'title' => $page->post_title, 'status' => $page->post_status, 'content' => $page->post_content, 'excerpt' => $page->post_excerpt );
		wp_trash_post( $page->ID );
	}
}

function ge_remove_biological_categories( &$backup ) {
	$names = array( 'Blends', 'Cardiovascular', 'Cellular Longevity', 'Dermatological', 'Gastrointestinal', 'Hormonal', 'Immune', 'Metabolic', 'Mitochondrial', 'Musculoskeletal', 'Reproductive Health', 'Tissue Regeneration' );
	foreach ( $names as $name ) {
		$term = get_term_by( 'name', $name, 'product_cat' );
		if ( ! $term ) {
			continue;
		}
		$backup['terms'][ $term->term_id ] = array( 'name' => $term->name, 'slug' => $term->slug, 'description' => $term->description );
		wp_delete_term( $term->term_id, 'product_cat' );
	}
}

function ge_assign_research_product_category( &$backup ) {
	$term = term_exists( 'Research Products', 'product_cat' );
	if ( ! $term ) {
		$term = wp_insert_term( 'Research Products', 'product_cat', array( 'slug' => 'research-products' ) );
	}
	if ( is_wp_error( $term ) ) {
		return;
	}
	$term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
	update_option( 'default_product_cat', $term_id );

	$product_ids = get_posts( array(
		'post_type'      => 'product',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	foreach ( $product_ids as $product_id ) {
		$old_terms = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
		$backup['post_meta'][ $product_id ]['_product_cat_terms'] = is_wp_error( $old_terms ) ? array() : $old_terms;
		wp_set_object_terms( $product_id, array( $term_id ), 'product_cat', false );
	}

	$uncategorized = get_term_by( 'slug', 'uncategorized', 'product_cat' );
	if ( $uncategorized && (int) $uncategorized->term_id !== $term_id ) {
		wp_delete_term( $uncategorized->term_id, 'product_cat' );
	}
}

function ge_repair_catalog_menu_links( &$backup ) {
	foreach ( wp_get_nav_menus() as $menu ) {
		foreach ( wp_get_nav_menu_items( $menu->term_id ) as $item ) {
			$url = (string) get_post_meta( $item->ID, '_menu_item_url', true );
			if ( false === strpos( $url, '/all-peptides' ) && false === strpos( $url, '/product-category/' ) ) {
				continue;
			}
			$backup['menu_items'][ $item->ID ] = array( 'url' => $url, 'title' => $item->title );
			update_post_meta( $item->ID, '_menu_item_type', 'custom' );
			update_post_meta( $item->ID, '_menu_item_object', 'custom' );
			update_post_meta( $item->ID, '_menu_item_object_id', $item->ID );
			update_post_meta( $item->ID, '_menu_item_url', ge_shop_url() );
			wp_update_post( array( 'ID' => $item->ID, 'post_title' => 'All Peptides' ) );
		}
	}
}

add_action( 'template_redirect', 'ge_compliance_legacy_redirects', 1 );
function ge_compliance_legacy_redirects() {
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( preg_match( '#^(all-peptides|popular-peptides|education|peptide-calculator|calculator)(/|$)#', $path ) || 0 === strpos( $path, 'product-category/' ) ) {
		wp_safe_redirect( ge_shop_url(), 301 );
		exit;
	}
}
