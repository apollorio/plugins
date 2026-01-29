<?php
/**
 * Cena Rio Page Template
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue assets via WordPress proper methods.
add_action(
	'wp_enqueue_scripts',
	function () {
		// Apollo CDN Loader (carrega uni.css, icons, dark-mode, scroll, etc.).
		wp_enqueue_script(
			'apollo-cdn-loader',
			'https://assets.apollo.rio.br/index.min.js',
			array(),
			'3.1.0',
			true
		);

		// Motion.dev animation library.
		wp_enqueue_script(
			'motion-one',
			'https://unpkg.com/@motionone/dom/dist/motion-one.umd.js',
			array(),
			'1.0.0',
			true
		);

		// Inline styles.
		$cena_css = '
			.aprioEXP-body { font-family: system-ui, -apple-system, sans-serif; }
			.aprioEXP-card-shell {
				border-radius: 0.75rem;
				border: 1px solid rgba(148, 163, 184, 0.4);
				background-color: rgba(255, 255, 255, 0.9);
				box-shadow: 0 14px 40px rgba(15, 23, 42, 0.09);
				backdrop-filter: blur(10px);
			}
		';
		wp_add_inline_style( 'apollo-inline', $cena_css );
	},
	10
);

// Trigger enqueue if not already done.
if ( ! did_action( 'wp_enqueue_scripts' ) ) {
	do_action( 'wp_enqueue_scripts' );
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full w-full bg-white">
<head>
	<meta charset="UTF-8" />
	<title>Cena::rio - Apollo Social</title>
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body class="h-full bg-slate-50 text-slate-900">
<section class="aprioEXP-body">
	<div class="min-h-screen flex flex-col">

	<!-- Header -->
	<header class="h-14 flex items-center justify-between border-b bg-white/90 backdrop-blur px-3 md:px-6">
		<div class="flex items-center gap-3">
		<div class="h-8 w-8 rounded-full bg-slate-900 text-white flex items-center justify-center">
			<i class="ri-flashlight-fill"></i>
		</div>
		<div class="flex flex-col">
			<span class="uppercase tracking-[0.12em] text-slate-400 text-[10px]">
			Curadoria
			</span>
			<span class="text-sm font-semibold">
			Cena::rio
			</span>
		</div>
		</div>

		<div class="flex items-center gap-3">
		<a href="/feed/" class="text-sm font-medium text-slate-600 hover:text-slate-900">Feed</a>
		<a href="/painel/" class="text-sm font-medium text-slate-600 hover:text-slate-900">Painel</a>
		</div>
	</header>

	<!-- Main -->
	<main class="flex-1 flex justify-center px-3 md:px-6 py-4 md:py-6">
		<div class="w-full max-w-4xl">

		<!-- Hero Section -->
		<section class="aprioEXP-card-shell p-6 mb-6 text-center">
			<h1 class="text-2xl md:text-3xl font-bold mb-2">A Cena Carioca Acontece Aqui</h1>
			<p class="text-slate-600 max-w-xl mx-auto">
			Guia vivo de festas eletrônicas, núcleos e comunidades do Rio de Janeiro.
			Conecte-se com quem faz a cultura acontecer.
			</p>
		</section>

		<!-- Grid de Destaques -->
		<div class="grid md:grid-cols-3 gap-4">
			<!-- Card 1 -->
			<article class="aprioEXP-card-shell p-4 hover:border-slate-300 transition-colors cursor-pointer">
			<div class="flex items-center gap-2 mb-3">
				<div class="h-8 w-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
				<i class="ri-calendar-event-line"></i>
				</div>
				<h3 class="font-semibold text-sm">Agenda da Semana</h3>
			</div>
			<p class="text-xs text-slate-600 mb-3">
				Confira os principais eventos de música eletrônica rolando de quinta a domingo.
			</p>
			<div class="text-xs font-medium text-emerald-600 flex items-center gap-1">
				Ver agenda <i class="ri-arrow-right-line"></i>
			</div>
			</article>

			<!-- Card 2 -->
			<article class="aprioEXP-card-shell p-4 hover:border-slate-300 transition-colors cursor-pointer">
			<div class="flex items-center gap-2 mb-3">
				<div class="h-8 w-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
				<i class="ri-team-line"></i>
				</div>
				<h3 class="font-semibold text-sm">Núcleos & Coletivos</h3>
			</div>
			<p class="text-xs text-slate-600 mb-3">
				Conheça quem está por trás das festas e movimentos culturais da cidade.
			</p>
			<div class="text-xs font-medium text-purple-600 flex items-center gap-1">
				Explorar núcleos <i class="ri-arrow-right-line"></i>
			</div>
			</article>

			<!-- Card 3 -->
			<article class="aprioEXP-card-shell p-4 hover:border-slate-300 transition-colors cursor-pointer">
			<div class="flex items-center gap-2 mb-3">
				<div class="h-8 w-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center">
				<i class="ri-map-pin-user-line"></i>
				</div>
				<h3 class="font-semibold text-sm">Mapa da Cena</h3>
			</div>
			<p class="text-xs text-slate-600 mb-3">
				Navegue pelos spots, casas noturnas e locations secretas.
			</p>
			<div class="text-xs font-medium text-orange-600 flex items-center gap-1">
				Abrir mapa <i class="ri-arrow-right-line"></i>
			</div>
			</article>
		</div>

		</div>
	</main>
	</div>
</section>
</body>
</html>

