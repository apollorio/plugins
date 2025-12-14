<?php
/**
 * ================================================================================
 * APOLLO AUTH - Login & Registration Template
 * ================================================================================
 * Main template for user authentication with:
 * - Security states (normal, warning, danger, success)
 * - Login/Registration forms
 * - Aptitude Quiz System
 * - Visual effects (corruption, glitch, siren)
 *
 * @package Apollo_Social
 * @since 1.0.0
 *
 * USAGE:
 * - Include this template directly or use [apollo_login_form] shortcode
 * - Configure via WordPress filters: apollo_auth_config
 *
 * TEMPLATE PARTS:
 * - parts/header.php - Apollo branding header
 * - parts/login-form.php - Login form
 * - parts/register-form.php - Registration form
 * - parts/footer.php - Footer with copyright
 * - parts/lockout-overlay.php - Security lockout display
 * - parts/aptitude-quiz.php - Quiz overlay
 * ================================================================================
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

// Get configuration
$auth_config = apply_filters(
    'apollo_auth_config',
    [
        'ajax_url'             => admin_url('admin-ajax.php'),
        'nonce'                => wp_create_nonce('apollo_auth_nonce'),
        'max_failed_attempts'  => 3,
        'lockout_duration'     => 60,
        'simon_levels'         => 4,
        'reaction_targets'     => 4,
        'redirect_after_login' => home_url('/feed/'),
        'terms_url'            => 'https://apollo.rio.br/politica',
        'bug_report_url'       => 'https://apollo.rio.br/bug/',
        'show_instagram'       => true,
        'require_cpf'          => true,
    ]
);

// Get available sounds/genres for registration
$available_sounds = apply_filters(
    'apollo_registration_sounds',
    [
        'techno'      => 'Techno',
        'house'       => 'House',
        'trance'      => 'Trance',
        'drum_bass'   => 'Drum & Bass',
        'funk'        => 'Funk',
        'tribal'      => 'Tribal',
        'minimal'     => 'Minimal',
        'progressive' => 'Progressive',
        'melodic'     => 'Melódico',
        'hard'        => 'Hard',
        'psy'         => 'Psy',
        'ambient'     => 'Ambient',
    ]
);

// Localize script configuration
$js_config = [
    'ajaxUrl'            => $auth_config['ajax_url'],
    'nonce'              => $auth_config['nonce'],
    'maxFailedAttempts'  => $auth_config['max_failed_attempts'],
    'lockoutDuration'    => $auth_config['lockout_duration'],
    'simonLevels'        => $auth_config['simon_levels'],
    'reactionTargets'    => $auth_config['reaction_targets'],
    'redirectAfterLogin' => $auth_config['redirect_after_login'],
    'strings'            => [
        'loginSuccess'   => __('Acesso autorizado. Redirecionando...', 'apollo-social'),
        'loginFailed'    => __('Credenciais incorretas. Tente novamente.', 'apollo-social'),
        'warningState'   => __('Atenção: última tentativa antes do bloqueio.', 'apollo-social'),
        'lockedOut'      => __('Sistema bloqueado por segurança.', 'apollo-social'),
        'quizComplete'   => __('Teste de aptidão concluído com sucesso!', 'apollo-social'),
        'quizFailed'     => __('Resposta incorreta. Reiniciando pergunta...', 'apollo-social'),
        'patternCorrect' => '♫♫♫',
        'ethicsCorrect'  => __('É trabalho, renda, a sonoridade e arte favorita de alguem.', 'apollo-social'),
    ],
];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta name="theme-color" content="#000000">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<title><?php echo esc_html__('Apollo::Rio - Terminal de Acesso', 'apollo-social'); ?></title>
	
	<!-- Remix Icons -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css">
	
	<!-- Auth Styles -->
	<?php
    // Enqueue styles - in production, use wp_enqueue_style
    $css_path = plugin_dir_url(__FILE__) . 'css/auth-styles.css';
?>
	<link rel="stylesheet" href="<?php echo esc_url($css_path); ?>">
	
	<?php wp_head(); ?>
</head>
<body data-state="normal">
	
	<!-- Background Layers -->
	<div class="bg-layer"></div>
	<div class="grid-overlay"></div>
	<div class="noise-overlay"></div>

	<!-- Terminal Container -->
	<div class="terminal-wrapper" data-tooltip="<?php esc_attr_e('Terminal de Autenticação Apollo', 'apollo-social'); ?>">
		
		<!-- Scan Line Effect -->
		<div class="scan-line"></div>
		
		<!-- Notification Area -->
		<div class="notification-area"></div>

		<!-- Header -->
		<?php require __DIR__ . '/parts/header.php'; ?>

		<!-- Main Content Area -->
		<div class="scroll-area">
			
			<!-- Login Section -->
			<section id="login-section" data-tooltip="<?php esc_attr_e('Formulário de Login', 'apollo-social'); ?>">
				<?php require __DIR__ . '/parts/login-form.php'; ?>
			</section>

			<!-- Register Section (Hidden by default) -->
			<section id="register-section" class="hidden" data-tooltip="<?php esc_attr_e('Formulário de Registro', 'apollo-social'); ?>">
				<?php require __DIR__ . '/parts/register-form.php'; ?>
			</section>

		</div>

		<!-- Footer -->
		<?php require __DIR__ . '/parts/footer.php'; ?>

		<!-- Lockout Overlay -->
		<?php require __DIR__ . '/parts/lockout-overlay.php'; ?>

		<!-- Aptitude Quiz Overlay -->
		<?php require __DIR__ . '/parts/aptitude-quiz.php'; ?>

	</div>

	<!-- Pass configuration to JavaScript -->
	<script>
		window.apolloAuthConfig = <?php echo wp_json_encode($js_config); ?>;
	</script>

	<!-- Auth Scripts -->
	<?php
$js_path = plugin_dir_url(__FILE__) . 'js/auth-scripts.js';
?>
	<script src="<?php echo esc_url($js_path); ?>" defer></script>

	<?php wp_footer(); ?>
</body>
</html>
