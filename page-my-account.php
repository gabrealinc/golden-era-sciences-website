<?php
/**
 * Customer account page.
 *
 * Visitors see WooCommerce's native login and registration forms in a
 * full-screen Golden Era landing treatment. Logged-in customers retain the
 * regular site layout for their account, orders and addresses.
 *
 * @package golden-era
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_user_logged_in() ) {
	get_header();
	while ( have_posts() ) :
		the_post();
		?>
		<main id="main" tabindex="-1" class="ge-section">
			<div class="ge-container"><div class="ge-prose"><?php the_content(); ?></div></div>
		</main>
		<?php
	endwhile;
	get_footer();
	return;
}

while ( have_posts() ) :
	the_post();
	?>
	<!doctype html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class( 'ge-login-page' ); ?>>
	<?php wp_body_open(); ?>
	<main class="ge-login-gate" id="main">
		<div class="ge-login-gate__media" aria-hidden="true">
			<video autoplay loop muted playsinline poster="<?php echo esc_url( ge_asset( 'images/hero-fallback.jpg' ) ); ?>">
				<source src="<?php echo esc_url( ge_asset( 'video/hero-loop.mp4' ) ); ?>" type="video/mp4">
			</video>
		</div>
		<div class="ge-login-gate__scrim"></div>
		<div class="ge-login-gate__inner">
			<a class="ge-login-gate__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo esc_url( ge_logo_url() ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="320" height="320">
				<span><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</a>
			<section class="ge-login-gate__panel" aria-labelledby="ge-login-gate-title">
				<p class="ge-kicker ge-kicker--gold"><?php esc_html_e( 'Customer Access', 'golden-era' ); ?></p>
				<h1 id="ge-login-gate-title"><?php esc_html_e( 'Golden Era Sciences', 'golden-era' ); ?></h1>
				<p class="ge-login-gate__intro"><?php esc_html_e( 'Create an account to access our full private research catalog.', 'golden-era' ); ?></p>
				<div class="ge-login-gate__forms"><?php the_content(); ?></div>
				<p class="ge-login-gate__switch">
					<span class="ge-login-gate__switch-login"><?php esc_html_e( 'Already have an account?', 'golden-era' ); ?></span>
					<button class="ge-login-gate__switch-button" type="button" data-ge-account-view="login"><?php esc_html_e( 'Login here', 'golden-era' ); ?></button>
					<span class="ge-login-gate__switch-register" hidden><?php esc_html_e( 'Need to create an account?', 'golden-era' ); ?></span>
					<button class="ge-login-gate__switch-button" type="button" data-ge-account-view="register" hidden><?php esc_html_e( 'Create an account', 'golden-era' ); ?></button>
				</p>
				<script>
					(function () {
						var forms = document.querySelector( '.ge-login-gate__forms' );
						var title = document.getElementById( 'ge-login-gate-title' );
						var intro = document.querySelector( '.ge-login-gate__intro' );
						var loginPrompt = document.querySelector( '.ge-login-gate__switch-login' );
						var registerPrompt = document.querySelector( '.ge-login-gate__switch-register' );
						var loginHeading = forms ? forms.querySelector( '#customer_login .u-column1 h2' ) : null;
						var registerHeading = forms ? forms.querySelector( '#customer_login .u-column2 h2' ) : null;
						var registerButton = forms ? forms.querySelector( '.woocommerce-form-register__submit' ) : null;
						var buttons = document.querySelectorAll( '[data-ge-account-view]' );

						if ( ! forms || ! title || ! intro || ! buttons.length ) {
							return;
						}

						function setView( view ) {
							var isLogin = view === 'login';
							forms.classList.toggle( 'is-login-view', isLogin );
							title.textContent = '<?php echo esc_js( __( 'Golden Era Sciences', 'golden-era' ) ); ?>';
							intro.textContent = isLogin ? '<?php echo esc_js( __( 'Sign in to access our full private research catalog.', 'golden-era' ) ); ?>' : '<?php echo esc_js( __( 'Create an account to access our full private research catalog.', 'golden-era' ) ); ?>';
							if ( loginHeading ) {
								loginHeading.textContent = '<?php echo esc_js( __( 'Login', 'golden-era' ) ); ?>';
							}
							if ( registerHeading ) {
								registerHeading.textContent = '<?php echo esc_js( __( 'Create account', 'golden-era' ) ); ?>';
							}
							if ( registerButton ) {
								registerButton.textContent = '<?php echo esc_js( __( 'Create account', 'golden-era' ) ); ?>';
								registerButton.value = '<?php echo esc_js( __( 'Create account', 'golden-era' ) ); ?>';
							}
							loginPrompt.hidden = isLogin;
							registerPrompt.hidden = ! isLogin;
							buttons[0].hidden = isLogin;
							buttons[1].hidden = ! isLogin;
						}

						forms.classList.add( 'ge-account-choice-ready' );
						buttons.forEach( function ( button ) {
							button.addEventListener( 'click', function () {
								setView( button.getAttribute( 'data-ge-account-view' ) );
							} );
						} );
						setView( 'register' );
					}());
				</script>
			</section>
		</div>
	</main>
	<?php wp_footer(); ?>
	</body>
	</html>
	<?php
endwhile;
