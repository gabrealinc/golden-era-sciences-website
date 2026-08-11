<?php
/**
 * Newsletter subscribe form.
 *
 * By default this posts to the theme's own AJAX handler (inc/subscribe.php),
 * which stores the subscriber and synchronizes the approved subscriber sheet.
 *
 * To use MailPoet, Jetpack, or another provider's form instead, return their
 * shortcode from this filter:
 *
 *   add_filter( 'ge_subscribe_shortcode', function () {
 *       return '[mailpoet_form id="1"]';
 *   } );
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shortcode = apply_filters( 'ge_subscribe_shortcode', '' );

if ( $shortcode ) {
	echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>

<form class="ge-form" data-ge-subscribe
      action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post">

	<input type="hidden" name="action" value="ge_subscribe">

	<?php
	/*
	 * Honeypot. Hidden from people, catnip for bots. See inc/subscribe.php for
	 * why this replaces a nonce here: the form is on every page, and a nonce
	 * baked into WordPress.com's cached HTML expires while the cache does not.
	 */
	?>
	<div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
		<label for="ge-website"><?php esc_html_e( 'Leave this field empty', 'golden-era' ); ?></label>
		<input id="ge-website" name="ge_website" type="text" tabindex="-1" autocomplete="off">
	</div>

	<div class="ge-form__row">
		<label class="ge-screen-reader-text" for="ge-first"><?php esc_html_e( 'First name', 'golden-era' ); ?></label>
		<input class="ge-input" id="ge-first" name="first_name" type="text"
		       placeholder="<?php esc_attr_e( 'First name', 'golden-era' ); ?>"
		       autocomplete="given-name" required>

		<label class="ge-screen-reader-text" for="ge-last"><?php esc_html_e( 'Last name', 'golden-era' ); ?></label>
		<input class="ge-input" id="ge-last" name="last_name" type="text"
		       placeholder="<?php esc_attr_e( 'Last name', 'golden-era' ); ?>"
		       autocomplete="family-name">
	</div>

	<label class="ge-screen-reader-text" for="ge-email"><?php esc_html_e( 'Email', 'golden-era' ); ?></label>
	<input class="ge-input" id="ge-email" name="email" type="email"
	       placeholder="<?php esc_attr_e( 'Email address', 'golden-era' ); ?>"
	       autocomplete="email" required>

	<label class="ge-screen-reader-text" for="ge-phone"><?php esc_html_e( 'Phone number (optional)', 'golden-era' ); ?></label>
	<input class="ge-input" id="ge-phone" name="phone_number" type="tel"
	       placeholder="<?php esc_attr_e( 'Phone number (optional)', 'golden-era' ); ?>"
	       autocomplete="tel" inputmode="tel">

	<button class="ge-btn ge-btn--gold" type="submit">
		<?php esc_html_e( 'Subscribe', 'golden-era' ); ?>
	</button>

	<p class="ge-form__note">
		<?php esc_html_e( 'By subscribing, you agree to receive email updates. If you provide a phone number, you also consent to receive recurring SMS updates. Message and data rates may apply. Reply STOP to opt out.', 'golden-era' ); ?>
	</p>

	<p class="ge-form__status" data-ge-status role="status" aria-live="polite"></p>

</form>
