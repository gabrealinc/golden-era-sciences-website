<?php
/**
 * Customizer settings.
 *
 * Kept deliberately small. Anything that belongs in version control lives in
 * the theme; only per-site values that a non-developer should be able to
 * change live here.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'customize_register', 'ge_customize_register' );
function ge_customize_register( $wp_customize ) {

	$wp_customize->add_section( 'ge_brand', array(
		'title'    => __( 'Golden Era — Brand', 'golden-era' ),
		'priority' => 30,
	) );

	$fields = array(
		'ge_coa_feed_url' => array(
			'label'       => __( 'COA index feed URL', 'golden-era' ),
			'description' => __( 'Google Apps Script JSON feed for SKU-matched Certificates of Analysis.', 'golden-era' ),
		),
		'ge_instagram' => array(
			'label'       => __( 'Instagram URL', 'golden-era' ),
			'description' => __( 'Leave blank to hide the icon.', 'golden-era' ),
		),
		'ge_tiktok' => array(
			'label'       => __( 'TikTok URL', 'golden-era' ),
			'description' => __( 'Leave blank to hide the icon.', 'golden-era' ),
		),
		'ge_contact_email' => array(
			'label' => __( 'Contact email', 'golden-era' ),
		),
		'ge_contact_phone' => array(
			'label' => __( 'Contact phone', 'golden-era' ),
		),
	);

	foreach ( $fields as $id => $field ) {
		$is_url = in_array( $id, array( 'ge_instagram', 'ge_tiktok', 'ge_coa_feed_url' ), true );

		$wp_customize->add_setting( $id, array(
			'default'           => '',
			'sanitize_callback' => $is_url ? 'esc_url_raw' : 'sanitize_text_field',
			'transport'         => 'refresh',
		) );

		$wp_customize->add_control( $id, array(
			'section'     => 'ge_brand',
			'label'       => $field['label'],
			'description' => isset( $field['description'] ) ? $field['description'] : '',
			'type'        => $is_url ? 'url' : 'text',
		) );
	}
}
