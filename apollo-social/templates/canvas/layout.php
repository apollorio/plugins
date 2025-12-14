<?php
/**
 * Apollo Canvas Layout
 * Blank canvas page - NO THEME INTERFERENCE
 * Only Apollo plugins CSS/JS loaded
 */

if (! defined('ABSPATH')) {
    exit;
}

$pwa              = $view['pwa']          ?? [];
$is_pwa           = $pwa['is_pwa']        ?? false;
$show_header      = $pwa['show_header']   ?? false;
$is_clean_mode    = $pwa['is_clean_mode'] ?? false;
$pwa_instructions = $pwa['instructions']  ?? null;

// Show PWA installation instructions if needed
if (! $is_pwa && $pwa_instructions && $pwa['is_apollo_rio_active']) :
    ?>
<div id="apollo-pwa-instructions" class="apollo-pwa-instructions" style="display: none;">
	<div class="apollo-pwa-instructions-content">
		<h3>Instalar Apollo como App</h3>
		<?php if ($pwa_instructions['ios']) : ?>
		<div class="apollo-pwa-instruction-block">
			<h4><i class="<?php echo esc_attr($pwa_instructions['ios']['icon']); ?>"></i> <?php echo esc_html($pwa_instructions['ios']['title']); ?></h4>
			<ol>
				<?php foreach ($pwa_instructions['ios']['steps'] as $step) : ?>
				<li><?php echo esc_html($step); ?></li>
				<?php endforeach; ?>
			</ol>
		</div>
		<?php endif; ?>
		<?php if ($pwa_instructions['android']) : ?>
		<div class="apollo-pwa-instruction-block">
			<h4><i class="<?php echo esc_attr($pwa_instructions['android']['icon']); ?>"></i> <?php echo esc_html($pwa_instructions['android']['title']); ?></h4>
			<?php if (! empty($pwa_instructions['android']['download_url'])) : ?>
			<a href="<?php echo esc_url($pwa_instructions['android']['download_url']); ?>" class="apollo-button apollo-button-primary">
				Baixar App Android
			</a>
			<?php endif; ?>
			<ol>
				<?php foreach ($pwa_instructions['android']['steps'] as $step) : ?>
				<li><?php echo esc_html($step); ?></li>
				<?php endforeach; ?>
			</ol>
		</div>
		<?php endif; ?>
		<button class="apollo-pwa-close" onclick="document.getElementById('apollo-pwa-instructions').style.display='none'">
			<i class="ri-close-line"></i> Fechar
		</button>
	</div>
</div>
<?php endif; ?>

<!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-full">
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html($view['title'] ?? 'Apollo Social'); ?></title>

	<!-- Preconnect to CDNs -->
	<link rel="preconnect" href="https://cdn.jsdelivr.net">
	<link rel="preconnect" href="https://assets.apollo.rio.br">

	<?php
    // P0-4: WordPress head - OutputGuards already removed theme hooks
    // Only essential WordPress and Apollo assets will be output
    wp_head();
?>

	<!-- Apollo Canvas Mode Styles -->
	<style>
		/* Reset theme interference */
		body.apollo-canvas-mode {
			margin: 0 !important;
			padding: 0 !important;
			background: #fafafa !important;
		}

		/* P0-4: Hide ALL theme elements completely */
		body.apollo-canvas-mode header:not(.apollo-header),
		body.apollo-canvas-mode footer:not(.apollo-footer),
		body.apollo-canvas-mode .site-header,
		body.apollo-canvas-mode .site-footer,
		body.apollo-canvas-mode nav:not(.apollo-nav),
		body.apollo-canvas-mode .wp-block-navigation,
		body.apollo-canvas-mode .site-main:not(.apollo-canvas-main),
		body.apollo-canvas-mode .content-area:not(.apollo-canvas-main),
		body.apollo-canvas-mode .main-content:not(.apollo-canvas-main),
		body.apollo-canvas-mode .entry-content:not(.apollo-canvas-main),
		body.apollo-canvas-mode .sidebar,
		body.apollo-canvas-mode .widget-area {
			display: none !important;
		}

		/* P0-4: Ensure only Apollo content is visible */
		body.apollo-canvas-mode .apollo-header,
		body.apollo-canvas-mode .apollo-canvas-main,
		body.apollo-canvas-mode .apollo-footer {
			display: block !important;
		}

		/* PWA Instructions */
		.apollo-pwa-instructions {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(0, 0, 0, 0.8);
			z-index: 9999;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 20px;
		}

		.apollo-pwa-instructions-content {
			background: white;
			border-radius: 12px;
			padding: 24px;
			max-width: 500px;
			max-height: 80vh;
			overflow-y: auto;
		}

		.apollo-pwa-instruction-block {
			margin: 16px 0;
		}

		.apollo-pwa-instruction-block h4 {
			margin: 0 0 12px 0;
			font-size: 16px;
			font-weight: 600;
		}

		.apollo-pwa-instruction-block ol {
			margin: 8px 0;
			padding-left: 24px;
		}

		.apollo-pwa-instruction-block li {
			margin: 4px 0;
			font-size: 14px;
		}

		.apollo-pwa-close {
			margin-top: 16px;
			padding: 8px 16px;
			background: #f3f4f6;
			border: none;
			border-radius: 6px;
			cursor: pointer;
		}
	</style>
</head>
<body <?php body_class('apollo-canvas-mode'); ?>>

<?php if ($show_header && ! $is_clean_mode) : ?>
<header class="apollo-header apollo-official-header">
	<div class="apollo-header-content">
		<div class="apollo-logo">
			<i class="ri-slack-fill"></i>
			<span>Apollo::rio</span>
		</div>
		<nav class="apollo-nav">
			<a href="/feed/">Feed</a>
			<a href="/chat/">Chat</a>
			<a href="/painel/">Painel</a>
		</nav>
	</div>
</header>
<?php endif; ?>

<main class="apollo-canvas-main" id="apollo-canvas-main">
	<?php
// Check if specific template is set
if (! empty($view['template']) && $view['template'] !== 'users/dashboard.php') {
    $template_path = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/' . $view['template'];
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo $view['content'];
    }
} else {
    // Use route template or content
    $route_template = $view['route_template'] ?? null;
    if ($route_template && file_exists($route_template)) {
        include $route_template;
    } else {
        echo $view['content'];
    }
}
?>
</main>

<?php wp_footer(); ?>

</body>
</html>

