<?php
/**
 * Consent controls, non-essential tracking suppression and legal-page update.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GE_CONSENT_COOKIE = 'ge_cookie_consent_v1';
const GE_PRIVACY_VERSION = '2026-09-28.1';

/** Whether the browser has sent a Global Privacy Control opt-out signal. */
function ge_global_privacy_control() {
	$value = isset( $_SERVER['HTTP_SEC_GPC'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_SEC_GPC'] ) ) : '';
	return '1' === $value;
}

/** Read the stored consent categories. */
function ge_cookie_consent_categories() {
	$value = isset( $_COOKIE[ GE_CONSENT_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ GE_CONSENT_COOKIE ] ) ) : '';
	return array_filter( explode( ',', $value ) );
}

function ge_cookie_consent_decided() {
	return isset( $_COOKIE[ GE_CONSENT_COOKIE ] );
}

/** Marketing consent is suppressed whenever GPC is active. */
function ge_cookie_consent_allows( $category ) {
	if ( 'necessary' === $category ) {
		return true;
	}
	if ( 'marketing' === $category && ge_global_privacy_control() ) {
		return false;
	}
	return in_array( $category, ge_cookie_consent_categories(), true );
}

/** Persist an explicit choice and return to the same page. */
function ge_cookie_consent_handle() {
	if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		return;
	}
	if ( empty( $_POST['ge_consent_action'] ) || empty( $_POST['ge_consent_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ge_consent_nonce'] ) ), 'ge_cookie_consent' ) ) {
		return;
	}

	$action     = sanitize_key( wp_unslash( $_POST['ge_consent_action'] ) );
	$categories = array( 'necessary' );
	if ( 'accept' === $action ) {
		$categories = array( 'necessary', 'analytics', 'marketing' );
	} elseif ( 'save' === $action ) {
		if ( ! empty( $_POST['ge_consent_analytics'] ) ) {
			$categories[] = 'analytics';
		}
		if ( ! empty( $_POST['ge_consent_marketing'] ) && ! ge_global_privacy_control() ) {
			$categories[] = 'marketing';
		}
	} elseif ( 'reject' !== $action ) {
		return;
	}

	$value   = implode( ',', $categories );
	$options = array(
		'expires'  => time() + ( 180 * DAY_IN_SECONDS ),
		'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
		'secure'   => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	);
	if ( defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ) {
		$options['domain'] = COOKIE_DOMAIN;
	}
	setcookie( GE_CONSENT_COOKIE, $value, $options );

	$return_url = isset( $_POST['ge_consent_return'] )
		? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['ge_consent_return'] ) ), home_url( '/' ) )
		: home_url( '/' );
	wp_safe_redirect( $return_url, 303 );
	exit;
}
add_action( 'template_redirect', 'ge_cookie_consent_handle', -900 );

/** Remove known non-essential analytics and advertising scripts before output. */
function ge_suppress_nonessential_tracking() {
	if ( ! ge_cookie_consent_allows( 'analytics' ) ) {
		foreach ( array( 'jetpack-stats', 'woocommerce-analytics', 'woocommerce-analytics-client', 'wc-order-attribution', 'sourcebuster-js' ) as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}
	if ( ! ge_cookie_consent_allows( 'marketing' ) ) {
		foreach ( array( 'facebook-for-woocommerce', 'facebook-pixel', 'meta-pixel' ) as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'ge_suppress_nonessential_tracking', PHP_INT_MAX );

add_filter( 'jetpack_disable_stats', function ( $disabled ) {
	return $disabled || ! ge_cookie_consent_allows( 'analytics' );
} );

/** Render the first-choice banner and reusable preference dialog. */
function ge_cookie_consent_ui() {
	if ( is_admin() || ! function_exists( 'ge_age_gate_verified' ) || ! ge_age_gate_verified() ) {
		return;
	}
	$return_url = home_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );
	$analytics  = ge_cookie_consent_allows( 'analytics' );
	$marketing  = ge_cookie_consent_allows( 'marketing' );
	$gpc        = ge_global_privacy_control();
	?>
	<?php if ( ! ge_cookie_consent_decided() ) : ?>
		<section class="ge-consent-banner" aria-labelledby="ge-consent-title">
			<h2 id="ge-consent-title"><?php esc_html_e( 'Your privacy choices', 'golden-era' ); ?></h2>
			<p><?php esc_html_e( 'We use necessary cookies to operate the store. With your permission, we also use analytics and advertising technologies, including the TikTok and Whop pixels.', 'golden-era' ); ?> <a href="<?php echo esc_url( home_url( '/cookie-policy/' ) ); ?>"><?php esc_html_e( 'Cookie Policy', 'golden-era' ); ?></a></p>
			<div class="ge-consent-actions">
				<?php ge_cookie_consent_quick_form( 'accept', __( 'Accept all', 'golden-era' ), $return_url ); ?>
				<?php ge_cookie_consent_quick_form( 'reject', __( 'Reject non-essential', 'golden-era' ), $return_url, true ); ?>
				<button class="ge-consent-button ge-consent-button--secondary" type="button" data-ge-consent-open><?php esc_html_e( 'Manage choices', 'golden-era' ); ?></button>
			</div>
		</section>
	<?php endif; ?>
	<dialog class="ge-consent-dialog" data-ge-consent-dialog>
		<form method="post" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<h2><?php esc_html_e( 'Cookie preferences', 'golden-era' ); ?></h2>
			<p><?php esc_html_e( 'Choose which optional technologies may run. Necessary cookies cannot be disabled because they provide age verification, consent storage, security, cart and checkout functions.', 'golden-era' ); ?></p>
			<label class="ge-consent-option"><input type="checkbox" checked disabled><span><strong><?php esc_html_e( 'Necessary', 'golden-era' ); ?></strong><small><?php esc_html_e( 'Required for the website and store to function.', 'golden-era' ); ?></small></span></label>
			<label class="ge-consent-option"><input type="checkbox" name="ge_consent_analytics" value="1" <?php checked( $analytics ); ?>><span><strong><?php esc_html_e( 'Analytics', 'golden-era' ); ?></strong><small><?php esc_html_e( 'Helps us understand site performance and usage.', 'golden-era' ); ?></small></span></label>
			<label class="ge-consent-option"><input type="checkbox" name="ge_consent_marketing" value="1" <?php checked( $marketing ); ?> <?php disabled( $gpc ); ?>><span><strong><?php esc_html_e( 'Advertising', 'golden-era' ); ?></strong><small><?php esc_html_e( 'Allows advertising measurement and campaign events, including TikTok and Whop pixel events.', 'golden-era' ); ?></small></span></label>
			<?php if ( $gpc ) : ?><p class="ge-consent-gpc"><?php esc_html_e( 'Global Privacy Control detected. Advertising data sharing remains disabled.', 'golden-era' ); ?></p><?php endif; ?>
			<input type="hidden" name="ge_consent_action" value="save">
			<input type="hidden" name="ge_consent_return" value="<?php echo esc_attr( $return_url ); ?>">
			<?php wp_nonce_field( 'ge_cookie_consent', 'ge_consent_nonce' ); ?>
			<div class="ge-consent-actions">
				<button class="ge-consent-button" type="submit"><?php esc_html_e( 'Save choices', 'golden-era' ); ?></button>
				<button class="ge-consent-button ge-consent-button--secondary" type="button" data-ge-consent-close><?php esc_html_e( 'Cancel', 'golden-era' ); ?></button>
			</div>
		</form>
	</dialog>
	<script>
	(function(){var d=document.querySelector('[data-ge-consent-dialog]');if(!d)return;document.querySelectorAll('[data-ge-consent-open]').forEach(function(b){b.addEventListener('click',function(){d.showModal();});});var c=d.querySelector('[data-ge-consent-close]');if(c)c.addEventListener('click',function(){d.close();});})();
	</script>
	<?php
}
add_action( 'wp_footer', 'ge_cookie_consent_ui', 5 );

function ge_cookie_consent_quick_form( $action, $label, $return_url, $secondary = false ) {
	$class = $secondary ? 'ge-consent-button ge-consent-button--secondary' : 'ge-consent-button';
	?>
	<form method="post" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<input type="hidden" name="ge_consent_action" value="<?php echo esc_attr( $action ); ?>">
		<input type="hidden" name="ge_consent_return" value="<?php echo esc_attr( $return_url ); ?>">
		<?php wp_nonce_field( 'ge_cookie_consent', 'ge_consent_nonce' ); ?>
		<button class="<?php echo esc_attr( $class ); ?>" type="submit"><?php echo esc_html( $label ); ?></button>
	</form>
	<?php
}

/** Publish a concise, accurate privacy notice and dedicated cookie notice. */
function ge_privacy_policy_migration() {
	if ( GE_PRIVACY_VERSION === get_option( 'ge_privacy_version' ) ) {
		return;
	}
	$privacy = get_page_by_path( 'privacy-policy' );
	$cookie  = get_page_by_path( 'cookie-policy' );
	$backup  = array( 'created_gmt' => gmdate( 'c' ), 'privacy' => $privacy ? $privacy->post_content : null, 'cookie' => $cookie ? $cookie->post_content : null );
	add_option( 'ge_privacy_backup_' . str_replace( '.', '_', GE_PRIVACY_VERSION ), $backup, '', false );

	$privacy_content = '<p><strong>Effective September 27, 2026.</strong> Golden Era Sciences explains below what personal information we collect, why we use it, and the choices available to you.</p>
	<h2>Information we collect</h2><p>We may collect identifiers and contact details such as name, email address, telephone number, billing and shipping address; commercial information such as products viewed, cart activity, purchases and customer-service communications; transaction information handled by payment providers; and internet or device information such as IP address, browser, device, referral source, cookie identifiers and interactions with the site.</p>
	<h2>How we collect and use information</h2><p>We collect information directly from you, automatically from your browser or device, and from service providers. We use it to operate the website, verify age, maintain carts and checkout, fulfill orders, provide support, prevent fraud, protect the site, comply with legal obligations, improve performance, and send communications you requested. With your permission, we use advertising technology to measure campaigns.</p>
	<h2>Cookies and advertising technology</h2><p>Necessary cookies support age verification, consent choices, security, cart and checkout. Optional analytics and advertising technologies run only according to your choices. Our advertising tools include the TikTok and Whop pixels, which may receive page and event information, timestamps, IP address, user agent, cookie identifiers, product details and purchase events. We may also use WordPress.com, Jetpack and WooCommerce services for hosting, store operation, security and analytics. See our <a href="' . esc_url( home_url( '/cookie-policy/' ) ) . '">Cookie Policy</a> and use Cookie Settings in the footer at any time.</p>
	<h2>Disclosure and sharing</h2><p>We disclose information to providers that help us host and secure the site, process payments, fulfill and ship orders, provide communications, operate WooCommerce, and measure advertising. We do not sell personal information for money. Some privacy laws may define disclosure to advertising platforms as a sale or sharing. You may opt out by rejecting advertising cookies, changing Cookie Settings, or enabling Global Privacy Control.</p>
	<h2>Retention and security</h2><p>We retain information only as reasonably needed for the purposes described above, including transaction, tax, fraud-prevention and legal requirements. Retention periods vary by record type. We use reasonable administrative, technical and organizational safeguards, but no internet transmission or storage system can be guaranteed completely secure.</p>
	<h2>Your choices and rights</h2><p>Depending on where you live, you may have rights to know, access, correct, delete or obtain a copy of personal information, and to opt out of certain sales, sharing, targeted advertising or marketing. We do not discriminate for exercising applicable privacy rights. Send a request to <a href="mailto:info@goldenerasciences.com">info@goldenerasciences.com</a>. We may need to verify your request and may retain information where an exception applies.</p>
	<h2>Marketing and SMS</h2><p>You may unsubscribe from email using the link in a message. If you expressly opt in to SMS, consent is not a condition of purchase. Message frequency varies and message and data rates may apply. Reply STOP to opt out or HELP for help. We do not share SMS consent information with third parties for their own marketing.</p>
	<h2>Age restriction</h2><p>The site is intended only for adults age 21 or older and is not directed to children.</p>
	<h2>Changes and contact</h2><p>We may update this notice and will post the revised effective date here. Questions and privacy requests may be sent to <a href="mailto:info@goldenerasciences.com">info@goldenerasciences.com</a> or 847-461-9035.</p>';

	$cookie_content = '<p><strong>Effective September 27, 2026.</strong> This notice explains the cookies and similar technologies used on Golden Era Sciences.</p>
	<h2>Necessary technologies</h2><p>These support age verification, cookie-preference storage, security, site delivery, forms, shopping cart and checkout. They cannot be disabled through our preference center because the requested service would not function correctly. The age-verification cookie lasts up to one year. The cookie-preference record lasts up to 180 days. WooCommerce, WordPress.com and payment providers may set additional session or security cookies when their features are used.</p>
	<h2>Analytics</h2><p>If you choose Analytics, WordPress.com, Jetpack or WooCommerce analytics may process device and usage information so we can understand performance and site activity.</p>
	<h2>Advertising</h2><p>If you choose Advertising, the TikTok and Whop pixels may process page views, cart, checkout and purchase events, together with IP address, user agent, timestamps, cookie identifiers and product or order details used for measurement, optimization and advertising. TikTok states that its advertising cookies may last up to 13 months. We do not intentionally send payment-card details through either pixel.</p>
	<h2>Managing choices</h2><p>Select Accept all, Reject non-essential or Manage choices when the banner appears. You may reopen Cookie Settings from the footer at any time. A detected Global Privacy Control signal disables advertising data sharing on this site. Browser controls may also delete or block stored cookies, but doing so can affect cart, checkout and other necessary functions.</p>
	<h2>Contact</h2><p>Questions may be sent to <a href="mailto:info@goldenerasciences.com">info@goldenerasciences.com</a>.</p>';

	if ( $privacy ) {
		wp_update_post( array( 'ID' => $privacy->ID, 'post_title' => 'Privacy Policy', 'post_content' => wp_kses_post( $privacy_content ), 'post_status' => 'publish' ) );
	}
	if ( $cookie ) {
		wp_update_post( array( 'ID' => $cookie->ID, 'post_title' => 'Cookie Policy', 'post_content' => wp_kses_post( $cookie_content ), 'post_status' => 'publish' ) );
	} else {
		wp_insert_post( array( 'post_title' => 'Cookie Policy', 'post_name' => 'cookie-policy', 'post_content' => wp_kses_post( $cookie_content ), 'post_status' => 'publish', 'post_type' => 'page' ) );
	}
	update_option( 'ge_privacy_version', GE_PRIVACY_VERSION, false );
	flush_rewrite_rules( false );
}
add_action( 'init', 'ge_privacy_policy_migration', 35 );
