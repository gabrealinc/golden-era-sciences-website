<?php
/**
 * One-time correction for a typo in database-backed WordPress content.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GE_SPELLING_CLEANUP_VERSION', '2026-11-11.1' );

add_action( 'init', 'ge_correct_lyophilized_spelling', 50 );

/** Replace the approved misspelling anywhere it is stored in site content. */
function ge_correct_lyophilized_spelling() {
	if ( GE_SPELLING_CLEANUP_VERSION === get_option( 'ge_spelling_cleanup_version' ) || get_transient( 'ge_spelling_cleanup_lock' ) ) {
		return;
	}

	set_transient( 'ge_spelling_cleanup_lock', 1, 5 * MINUTE_IN_SECONDS );

	$backup = array(
		'created_gmt' => gmdate( 'c' ),
		'posts'       => array(),
	);
	$posts = get_posts(
		array(
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'all',
		)
	);

	foreach ( $posts as $post ) {
		$updates = array( 'ID' => $post->ID );
		$changed = false;

		foreach ( array( 'post_title', 'post_excerpt', 'post_content' ) as $field ) {
			$corrected = str_ireplace( 'syphilized', 'lyophilized', $post->$field );
			if ( $corrected !== $post->$field ) {
				$updates[ $field ] = $corrected;
				$changed            = true;
			}
		}

		if ( ! $changed ) {
			continue;
		}

		$backup['posts'][ $post->ID ] = array(
			'post_title'   => $post->post_title,
			'post_excerpt' => $post->post_excerpt,
			'post_content' => $post->post_content,
		);
		wp_update_post( wp_slash( $updates ) );
	}

	add_option( 'ge_spelling_cleanup_backup_' . str_replace( '.', '_', GE_SPELLING_CLEANUP_VERSION ), $backup, '', false );
	update_option( 'ge_spelling_cleanup_version', GE_SPELLING_CLEANUP_VERSION, false );
	delete_transient( 'ge_spelling_cleanup_lock' );
}
