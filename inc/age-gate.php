<?php
/**
 * Server-side age verification.
 *
 * Unverified visitors receive only the standalone gate document. The normal
 * WordPress template, metadata, catalog markup, feeds, reports and public REST
 * responses are withheld until the visitor confirms they are 21 or older.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GE_AGE_COOKIE = 'ge_age_verified';

/** Filterable emergency toggle. */
function ge_age_gate_enabled() {
	return (bool) apply_filters( 'ge_age_gate_enabled', true );
}

if ( ge_age_gate_enabled() && ! is_admin() && ! defined( 'DONOTCACHEPAGE' ) ) {
	define( 'DONOTCACHEPAGE', true );
}

/** Administrators and visitors with the verification cookie may continue. */
function ge_age_gate_verified() {
	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return true;
	}

	$value = isset( $_COOKIE[ GE_AGE_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ GE_AGE_COOKIE ] ) ) : '';
	return hash_equals( 'yes', $value );
}

/** Keep verified and unverified HTML out of shared and browser caches. */
function ge_age_gate_no_cache() {
	if ( ! ge_age_gate_enabled() || is_admin() ) {
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
	header( 'Pragma: no-cache', true );
	header( 'Vary: Cookie', false );
}
add_action( 'send_headers', 'ge_age_gate_no_cache', 0 );

/** Set the first-party verification cookie after an explicit confirmation. */
function ge_age_gate_accept() {
	if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		return false;
	}
	if ( empty( $_POST['ge_age_action'] ) || 'accept' !== sanitize_key( wp_unslash( $_POST['ge_age_action'] ) ) ) {
		return false;
	}
	if ( empty( $_POST['ge_age_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ge_age_nonce'] ) ), 'ge_age_verify' ) ) {
		return false;
	}

	$options = array(
		'expires'  => time() + YEAR_IN_SECONDS,
		'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
		'secure'   => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	);
	if ( defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ) {
		$options['domain'] = COOKIE_DOMAIN;
	}
	setcookie( GE_AGE_COOKIE, 'yes', $options );

	$return_url = isset( $_POST['ge_age_return'] )
		? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['ge_age_return'] ) ), home_url( '/' ) )
		: home_url( '/' );
	wp_safe_redirect( $return_url, 303 );
	exit;
}

/** Render a deliberately minimal document with no underlying site markup. */
function ge_age_gate_render() {
	ge_age_gate_no_cache();
	status_header( 200 );
	header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
	header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true );
	header( 'X-Content-Type-Options: nosniff', true );
	header( "Content-Security-Policy: default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'", true );

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$return_url  = wp_validate_redirect( home_url( $request_uri ), home_url( '/' ) );
	$logo_url    = ge_logo_url();
	$site_name   = get_bloginfo( 'name' );
	$action_url  = add_query_arg( 'ge_age_verify', '1', home_url( '/' ) );
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
	<title><?php esc_html_e( 'Age Verification', 'golden-era' ); ?> | <?php echo esc_html( $site_name ); ?></title>
	<style>
		:root{color-scheme:dark;--bg:#1a1208;--panel:#241a0e;--gold:#c9a45c;--cream:#f5ead7;--muted:#d8c7aa}
		*{box-sizing:border-box}html,body{min-height:100%;margin:0}body{display:grid;place-items:center;padding:24px;background:var(--bg);color:var(--cream);font-family:Arial,sans-serif}
		main{width:min(100%,480px);padding:40px 28px;text-align:center;background:var(--panel);border:1px solid rgba(201,164,92,.34);border-radius:18px;box-shadow:0 24px 70px rgba(0,0,0,.35)}
		img{display:block;width:88px;height:88px;object-fit:contain;margin:0 auto 24px}h1{margin:0;font-family:Georgia,serif;font-size:clamp(2rem,8vw,3rem);line-height:1.05;color:var(--gold)}
		p{margin:18px auto 0;max-width:36ch;color:var(--muted);font-size:1rem;line-height:1.65}.actions{display:flex;flex-wrap:wrap;justify-content:center;gap:12px;margin-top:30px}
		button,a{min-height:48px;padding:13px 20px;border-radius:6px;font:700 .82rem/1 Arial,sans-serif;letter-spacing:.08em;text-transform:uppercase;text-decoration:none;cursor:pointer}
		button{border:1px solid var(--gold);background:var(--gold);color:#1a1208}a{display:inline-flex;align-items:center;border:1px solid rgba(245,234,215,.52);color:var(--cream)}button:focus-visible,a:focus-visible{outline:3px solid var(--cream);outline-offset:3px}
	</style>
</head>
<body>
	<main aria-labelledby="age-title">
		<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" width="88" height="88">
		<h1 id="age-title"><?php esc_html_e( 'Age Verification', 'golden-era' ); ?></h1>
		<p><?php esc_html_e( 'This website is intended only for adults age 21 or older. Please confirm your age to continue.', 'golden-era' ); ?></p>
		<div class="actions">
			<form method="post" action="<?php echo esc_url( $action_url ); ?>">
				<input type="hidden" name="ge_age_action" value="accept">
				<input type="hidden" name="ge_age_return" value="<?php echo esc_attr( $return_url ); ?>">
				<?php wp_nonce_field( 'ge_age_verify', 'ge_age_nonce' ); ?>
				<button type="submit"><?php esc_html_e( 'I am 21 or older', 'golden-era' ); ?></button>
			</form>
			<a href="https://www.google.com" rel="nofollow"><?php esc_html_e( 'Exit', 'golden-era' ); ?></a>
		</div>
	</main>
</body>
</html>
	<?php
	exit;
}

/** Stop normal theme rendering before headers, metadata or content are emitted. */
function ge_age_gate_enforce() {
	if ( ! ge_age_gate_enabled() || ge_age_gate_verified() ) {
		return;
	}
	if ( ge_age_gate_accept() ) {
		return;
	}
	ge_age_gate_render();
}
add_action( 'template_redirect', 'ge_age_gate_enforce', -1000 );

/** Public REST output would otherwise expose the catalog around the HTML gate. */
function ge_age_gate_rest( $result ) {
	if ( ! ge_age_gate_enabled() || ge_age_gate_verified() || ! empty( $result ) ) {
		return $result;
	}
	return new WP_Error(
		'ge_age_verification_required',
		__( 'Age verification is required.', 'golden-era' ),
		array( 'status' => 403 )
	);
}
add_filter( 'rest_pre_dispatch', 'ge_age_gate_rest', 99 );

/** Crawlers cannot complete the gate, so do not advertise crawlable URLs. */
add_filter( 'robots_txt', function () {
	return "User-agent: *\nDisallow: /\n";
}, 999 );
