<?php
/**
 * Apollo Chat Template
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue assets via WordPress proper methods.
add_action(
	'wp_enqueue_scripts',
	function () {
		// UNI.CSS Framework.
		wp_enqueue_style(
			'apollo-uni-css',
			'https://assets.apollo.rio.br/uni.css',
			array(),
			'2.0.0'
		);

		// Remix Icons.
		wp_enqueue_style(
			'remixicon',
			'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
			array(),
			'4.7.0'
		);

		// Tailwind (CDN for dev).
		wp_enqueue_script(
			'tailwindcss',
			'https://cdn.tailwindcss.com',
			array(),
			'3.4.0',
			false
		);

		// Motion.dev.
		wp_enqueue_script(
			'motion-one',
			'https://unpkg.com/@motionone/dom/dist/motion-one.umd.js',
			array(),
			'10.16.4',
			true
		);

		// Base Apollo JS.
		wp_enqueue_script(
			'apollo-base-js',
			'https://assets.apollo.rio.br/base.js',
			array(),
			'2.0.0',
			true
		);
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
	<title>Apollo :: Mensagens</title>
	<meta name="viewport" content="width=device-width, initial-scale=1" />

	<?php wp_head(); ?>
</head>
<body class="h-full bg-slate-50 text-slate-900">
<section class="aprioEXP-body">
	<div class="min-h-screen flex flex-col">

	<!-- Top header (clean, branco, minimal) -->
	<header class="h-14 flex items-center justify-between border-b bg-white/90 backdrop-blur px-3 md:px-6">
		<div class="flex items-center gap-3">
		<!-- Mobile back -->
		<button class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 md:hidden">
			<i class="ri-arrow-left-line text-sm"></i>
		</button>
		<!-- Icon desktop -->
		<button class="hidden md:inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700">
			<i class="ri-message-3-line text-sm"></i>
		</button>
		<div class="flex flex-col">
			<span class="uppercase tracking-[0.12em] text-slate-400 text-[10px]">
			Mensagens · Apollo Social
			</span>
			<span class="text-sm font-semibold">
			@valle · Conversas instantâneas
			</span>
		</div>
		</div>

		<div class="flex items-center gap-2 text-[11px]">
		<!-- Busca desktop -->
		<div class="hidden md:flex items-center gap-2">
			<div class="relative">
			<i class="ri-search-line text-slate-400 absolute left-2 top-1.5 text-xs"></i>
			<input
				type="text"
				placeholder="Buscar conversas..."
				class="pl-6 pr-2 py-1.5 rounded-full border border-slate-200 text-[11px] w-56 bg-white focus:outline-none focus:ring-1 focus:ring-slate-300"
			/>
			</div>
		</div>

		<button
			class="hidden md:inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-1.5 font-medium text-slate-700 hover:bg-slate-50"
		>
			<i class="ri-user-3-line text-xs"></i>
			<span>Meu perfil interno</span>
		</button>

		<a
			href="#"
			class="inline-flex items-center gap-1 rounded-full bg-slate-90099 px-3 py-1.5 font-medium text-white"
		>
			<i class="ri-external-link-line text-xs"></i>
			<span>Abrir página pública</span>
		</a>

		<button class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-slate-100">
			<div class="h-7 w-7 overflow-hidden rounded-full bg-slate-200">
			<img
				src="https://lh4.googleusercontent.com/-0g2cAqYXq7s/AAAAAAAAAAI/AAAAAAAAAAc/FDmUBb5imVw/s64-rj/photo.jpg"
				alt="Valle"
				class="h-full w-full object-cover"
			/>
			</div>
		</button>
		</div>
	</header>

	<!-- Main -->
	<main class="flex-1 flex justify-center px-3 md:px-6 py-4 md:py-6">
		<div class="w-full max-w-6xl flex flex-col md:grid md:grid-cols-[minmax(0,1.1fr)_minmax(0,1.9fr)] gap-4">

		<!-- LISTA DE CONVERSAS (mobile primeiro) -->
		<aside class="aprioEXP-card-shell p-3 md:p-4 flex flex-col md:h-[calc(100vh-7rem)] bg-white/90">
			<!-- Header conversas -->
			<div class="flex items-center justify-between gap-2 mb-3">
			<div class="flex flex-col">
				<span class="uppercase tracking-[0.12em] text-slate-500 text-[10px]">
				Conversas
				</span>
				<h2 class="text-sm font-semibold">
				Mensagens diretas &amp; núcleos
				</h2>
			</div>
			<button class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50">
				<i class="ri-edit-2-line text-xs"></i>
				<span>Novo chat</span>
			</button>
			</div>

			<!-- Filtro rápido (chips limpos) -->
			<div class="flex flex-wrap gap-1 mb-3 text-[11px]">
			<button class="menutag event-category" type="button">
				<i class="ri-chat-1-line"></i>
				<span>Todos</span>
			</button>
			<button class="menutag event-category" type="button">
				<i class="ri-lock-2-line"></i>
				<span>Núcleos</span>
			</button>
			<button class="menutag event-category" type="button">
				<i class="ri-community-line"></i>
				<span>Comunidades</span>
			</button>
			<button class="menutag event-category" type="button">
				<i class="ri-user-heart-line"></i>
				<span>DMs</span>
			</button>
			</div>

			<!-- Lista conversas (clean, cards brancos, active soft) -->
			<div class="flex-1 overflow-y-auto space-y-1 text-[12px]">

			<!-- Thread 1: Núcleo Cena::rio (active) -->
			<button
				type="button"
				class="w-full text-left rounded-xl px-2.5 py-2 flex items-start gap-2 border border-slate-200 bg-slate-50"
				data-thread-trigger
				data-thread-id="nucleo-cenario"
				aria-selected="true"
			>
				<div class="shrink-0">
				<div class="h-8 w-8 rounded-full bg-slate-900 flex items-center justify-center text-[12px] font-semibold text-white">
					NR
				</div>
				</div>
				<div class="min-w-0 flex-1">
				<div class="flex items-center justify-between gap-2">
					<p class="truncate font-semibold text-[12px]">Núcleo Cena::rio</p>
					<span class="text-[10px] text-slate-400">agora</span>
				</div>
				<p class="truncate text-[11px] text-slate-600">
					Valle: “vou subir o cronograma da Dismantle no Gestor, alguém revisa?”
				</p>
				<div class="mt-1 flex items-center gap-1 text-[10px] text-slate-400">
					<span>12 pessoas</span>
					<span class="w-px h-3 bg-slate-200"></span>
					<span>3 novas</span>
				</div>
				</div>
			</button>

			<!-- Thread 2: Dismantle Team -->
			<button
				type="button"
				class="w-full text-left rounded-xl px-2.5 py-2 flex items-start gap-2 border border-transparent hover:border-slate-200 hover:bg-slate-50"
				data-thread-trigger
				data-thread-id="dismantle-team"
				aria-selected="false"
			>
				<div class="shrink-0">
				<div class="h-8 w-8 rounded-full bg-amber-500 flex items-center justify-center text-[12px] font-semibold text-white">
					DT
				</div>
				</div>
				<div class="min-w-0 flex-1">
				<div class="flex items-center justify-between gap-2">
					<p class="truncate font-semibold text-[12px]">Dismantle · Produção</p>
					<span class="text-[10px] text-slate-400">há 12 min</span>
				</div>
				<p class="truncate text-[11px] text-slate-600">
					Mark: “a plotagem do mapa chegou, consigo levar amanhã”
				</p>
				<div class="mt-1 flex items-center gap-1 text-[10px] text-slate-400">
					<span>7 pessoas</span>
					<span class="w-px h-3 bg-slate-200"></span>
					<span>sem novas</span>
				</div>
				</div>
			</button>

			<!-- Thread 3: DM com Eli -->
			<button
				type="button"
				class="w-full text-left rounded-xl px-2.5 py-2 flex items-start gap-2 border border-transparent hover:border-slate-200 hover:bg-slate-50"
				data-thread-trigger
				data-thread-id="dm-eli"
				aria-selected="false"
			>
				<div class="shrink-0">
				<div class="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center text-[12px] font-semibold text-slate-700">
					EL
				</div>
				</div>
				<div class="min-w-0 flex-1">
				<div class="flex items-center justify-between gap-2">
					<p class="truncate font-semibold text-[12px]">Eli</p>
					<span class="text-[10px] text-slate-400">ontem</span>
				</div>
				<p class="truncate text-[11px] text-slate-600">
					“quando sair o link da festa me manda por aqui, pls”
				</p>
				</div>
			</button>

			<!-- Thread 4: Sistema Apollo -->
			<button
				type="button"
				class="w-full text-left rounded-xl px-2.5 py-2 flex items-start gap-2 border border-transparent hover:border-slate-200 hover:bg-slate-50"
				data-thread-trigger
				data-thread-id="system"
				aria-selected="false"
			>
				<div class="shrink-0">
				<div class="h-8 w-8 rounded-full bg-slate-900 flex items-center justify-center text-[14px] text-white">
					<i class="ri-cpu-line"></i>
				</div>
				</div>
				<div class="min-w-0 flex-1">
				<div class="flex items-center justify-between gap-2">
					<p class="truncate font-semibold text-[12px]">Apollo · Sistema</p>
					<span class="text-[10px] text-slate-400">3d</span>
				</div>
				<p class="truncate text-[11px] text-slate-600">
					“seus dados de login foram sincronizados com o Social Core”
				</p>
				</div>
			</button>
			</div>
		</aside>

		<!-- PAINEL DE CONVERSA (open-kitchen: branco, vidro, poucos elementos) -->
		<section class="aprioEXP-card-shell p-0 flex flex-col md:h-[calc(100vh-7rem)] bg-white/95">
			<!-- Cabeçalho da conversa ativa -->
			<header class="px-3 md:px-4 py-2.5 border-b border-slate-200 flex items-center justify-between gap-2 bg-white">
			<div class="flex items-center gap-2 min-w-0" id="im-thread-header-main">
				<div
				class="h-9 w-9 rounded-full flex items-center justify-center text-[11px] font-semibold text-white bg-slate-900"
				id="im-thread-avatar"
				>
				NR
				</div>
				<div class="min-w-0">
				<div class="flex items-center gap-1">
					<p class="text-[13px] font-semibold truncate" id="im-thread-title">
					Núcleo Cena::rio
					</p>
					<span class="aprioEXP-metric-chip hidden md:inline-flex">
					<i class="ri-lock-2-line text-[11px]"></i>
					<span>Núcleo privado</span>
					</span>
				</div>
				<p class="text-[11px] text-slate-500 truncate" id="im-thread-subtitle">
					Curadoria de eventos, dados e registros da cena carioca.
				</p>
				</div>
			</div>

			<div class="flex items-center gap-2 text-[11px]">
				<span class="hidden md:inline-flex items-center gap-1 text-emerald-600">
				<span class="online inline-block h-2 w-2 rounded-full"></span>
				<span id="im-thread-status">Várias pessoas online</span>
				</span>
				<button class="inline-flex h-7 w-7 items-center justify-center rounded-full hover:bg-slate-100">
				<i class="ri-information-line text-[15px] text-slate-500"></i>
				</button>
			</div>
			</header>

			<!-- Área de mensagens -->
			<div class="flex-1 flex flex-col bg-slate-50/60">
			<!-- Scroll mensagens -->
			<div class="flex-1 overflow-y-auto px-3 md:px-4 py-3 space-y-3 text-[12px]" id="im-thread-panels">

				<!-- Painel: Núcleo Cena::rio -->
				<div data-thread-panel="nucleo-cenario" class="space-y-3">
				<div class="flex justify-center">
					<span class="aprioEXP-metric-chip text-[10px]">
					Hoje · planejamento da Dismantle
					</span>
				</div>

				<!-- Outro -->
				<div class="flex items-start gap-2 max-w-[92%]" data-msg-bubble>
					<div class="h-7 w-7 rounded-full bg-slate-900 flex items-center justify-center text-[10px] text-white">
					NR
					</div>
					<div>
					<div class="flex items-baseline gap-2">
						<span class="font-semibold text-[11px]">Núcleo Cena::rio</span>
						<span class="text-[10px] text-slate-500">19:07</span>
					</div>
					<div class="mt-1 rounded-2xl rounded-tl-sm bg-white border border-slate-200/70 px-3 py-2">
						<p class="text-slate-800">
						Subi a versão preliminar da agenda de março da Dismantle. Vê se faz sentido pra você:
						entrada, pico, after e desmonte.
						</p>
						<div class="mt-1 flex flex-wrap gap-1 text-[10px] text-slate-500">
						<span class="aprioEXP-metric-chip">arquivo .xlsx</span>
						<span class="aprioEXP-metric-chip">v1.0</span>
						</div>
					</div>
					</div>
				</div>

				<!-- Eu -->
				<div class="flex items-start gap-2 justify-end max-w-[92%] ml-auto" data-msg-bubble>
					<div class="text-right">
					<div class="flex items-baseline gap-2 justify-end">
						<span class="text-[10px] text-slate-500">19:09</span>
						<span class="font-semibold text-[11px]">Valle</span>
					</div>
					<div class="mt-1 rounded-2xl rounded-tr-sm bg-slate-90099 text-slate-50 px-3 py-2">
						<p>
						Tá perfeito. Só vou ajustar a troca do último DJ pra encaixar o registro em vídeo
						e a parte do after seguro.
						</p>
						<div class="mt-1 flex items-center justify-end gap-1 text-[10px] text-slate-200/90">
						<i class="ri-check-double-line text-[12px]"></i>
						<span>entregue</span>
						</div>
					</div>
					</div>
				</div>

				<!-- Outro 2 -->
				<div class="flex items-start gap-2 max-w-[92%]" data-msg-bubble>
					<div class="h-7 w-7 rounded-full bg-emerald-500 flex items-center justify-center text-[10px] text-white">
					TR
					</div>
					<div>
					<div class="flex items-baseline gap-2">
						<span class="font-semibold text-[11px]">Tropicalis Core</span>
						<span class="text-[10px] text-slate-500">19:11</span>
					</div>
					<div class="mt-1 rounded-2xl rounded-tl-sm bg-white border border-slate-200/70 px-3 py-2">
						<p class="text-slate-800">
						Quando o link do evento estiver público aqui na Apollo, me marca que eu já puxo pro
						mapa de festas do fim de semana.
						</p>
					</div>
					</div>
				</div>
				</div>

				<!-- Painel: Dismantle Team -->
				<div data-thread-panel="dismantle-team" class="space-y-3 hidden">
				<div class="flex justify-center">
					<span class="aprioEXP-metric-chip text-[10px]">
					Hoje · bastidores da produção
					</span>
				</div>

				<div class="flex items-start gap-2 max-w-[92%]" data-msg-bubble>
					<div class="h-7 w-7 rounded-full bg-amber-500 flex items-center justify-center text-[10px] text-white">
					MK
					</div>
					<div>
					<div class="flex items-baseline gap-2">
						<span class="font-semibold text-[11px]">Mark</span>
						<span class="text-[10px] text-slate-500">18:22</span>
					</div>
					<div class="mt-1 rounded-2xl rounded-tl-sm bg-white border border-slate-200/70 px-3 py-2">
						<p class="text-slate-800">
						A plotagem do mapa de pista chegou e ficou linda. Preciso só do texto do rodapé com
						os créditos “Apollo :: Rio”.
						</p>
					</div>
					</div>
				</div>

				<div class="flex items-start gap-2 justify-end max-w-[92%] ml-auto" data-msg-bubble>
					<div class="text-right">
					<div class="flex items-baseline gap-2 justify-end">
						<span class="text-[10px] text-slate-500">18:25</span>
						<span class="font-semibold text-[11px]">Valle</span>
					</div>
					<div class="mt-1 rounded-2xl rounded-tr-sm bg-slate-90099 text-slate-50 px-3 py-2">
						<p>
						Pode usar:
						<br />
						“Mapa de pista integrado · Apollo::rio · 2025”
						</p>
					</div>
					</div>
				</div>
				</div>

				<!-- Painel: DM Eli -->
				<div data-thread-panel="dm-eli" class="space-y-3 hidden">
				<div class="flex justify-center">
					<span class="aprioEXP-metric-chip text-[10px]">
					Ontem · convite
					</span>
				</div>

				<div class="flex items-start gap-2 max-w-[92%]" data-msg-bubble>
					<div class="h-7 w-7 rounded-full bg-slate-200 flex items-center justify-center text-[10px] text-slate-700">
					EL
					</div>
					<div>
					<div class="flex items-baseline gap-2">
						<span class="font-semibold text-[11px]">Eli</span>
						<span class="text-[10px] text-slate-500">22:01</span>
					</div>
					<div class="mt-1 rounded-2xl rounded-tl-sm bg-white border border-slate-200/70 px-3 py-2">
						<p class="text-slate-800">
						Quando sair o link da Dismantle aqui na Apollo me manda por aqui, quero colocar na agenda.
						</p>
					</div>
					</div>
				</div>

				<div class="flex items-start gap-2 justify-end max-w-[92%] ml-auto" data-msg-bubble>
					<div class="text-right">
					<div class="flex items-baseline gap-2 justify-end">
						<span class="text-[10px] text-slate-500">22:04</span>
						<span class="font-semibold text-[11px]">Valle</span>
					</div>
					<div class="mt-1 rounded-2xl rounded-tr-sm bg-slate-90099 text-slate-50 px-3 py-2">
						<p>
						Fechado! Vou subir aqui primeiro, aí já te mando o atalho do Social core.
						</p>
					</div>
					</div>
				</div>
				</div>

				<!-- Painel: Sistema -->
				<div data-thread-panel="system" class="space-y-3 hidden">
				<div class="flex justify-center">
					<span class="aprioEXP-metric-chip text-[10px]">
					Sistema Apollo
					</span>
				</div>

				<div class="flex items-start gap-2 max-w-[92%]" data-msg-bubble>
					<div class="h-7 w-7 rounded-full bg-slate-900 flex items-center justify-center text-[12px] text-white">
					<i class="ri-cpu-line"></i>
					</div>
					<div>
					<div class="flex items-baseline gap-2">
						<span class="font-semibold text-[11px]">Apollo · Sistema</span>
						<span class="text-[10px] text-slate-500">13:40</span>
					</div>
					<div class="mt-1 rounded-2xl rounded-tl-sm bg-white border border-slate-200/70 px-3 py-2">
						<p class="text-slate-800">
						Sua conta do Social Core foi sincronizada com o módulo de eventos.
						A área de “Meus números” agora considera também as interações no feed.
						</p>
					</div>
					</div>
				</div>

				<div class="flex items-start gap-2 justify-end max-w-[92%] ml-auto" data-msg-bubble>
					<div class="text-right">
					<div class="flex items-baseline gap-2 justify-end">
						<span class="text-[10px] text-slate-500">13:41</span>
						<span class="font-semibold text-[11px]">Valle</span>
					</div>
					<div class="mt-1 rounded-2xl rounded-tr-sm bg-slate-90099 text-slate-50 px-3 py-2">
						<p>
						perfeito, era exatamente essa visão integrada que eu queria pro Apollo.
						</p>
					</div>
					</div>
				</div>
				</div>

			</div>

			<!-- Composer (barra clean, arredondada) -->
			<div class="border-t border-slate-200 px-3 md:px-4 py-2.5 bg-white">
				<form class="flex items-end gap-2">
				<button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-slate-100">
					<i class="ri-add-line text-[16px] text-slate-600"></i>
				</button>
				<button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-slate-100">
					<i class="ri-emotion-line text-[16px] text-slate-600"></i>
				</button>

				<div class="flex-1 min-w-0">
					<div class="relative">
					<textarea
						rows="1"
						class="w-full resize-none rounded-full border border-slate-200 bg-slate-50 px-3 py-2 pr-9 text-[12px] focus:outline-none focus:ring-1 focus:ring-slate-300"
						placeholder="Escreva uma mensagem para a cena..."
					></textarea>
					<span class="absolute right-3 bottom-1.5 text-[10px] text-slate-400">
						ENTER para enviar
					</span>
					</div>
				</div>

				<button
					type="button"
					class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-90099 text-white"
				>
					<i class="ri-send-plane-2-line text-[15px]"></i>
				</button>
				</form>
			</div>
			</div>
		</section>
		</div>
	</main>
	</div>

	<!-- Script: troca de conversas + Motion.dev (animação minimal) -->
	<script>
	document.addEventListener("DOMContentLoaded", function () {
		var motion = window.motion || window.Motion || null;
		var animateFn = motion && motion.animate
		? motion.animate
		: function (el, keyframes, options) {
			if (!el || !el.animate) return { finished: Promise.resolve() };
			var anim = el.animate(keyframes, options);
			return anim;
			};

		var triggers = document.querySelectorAll("[data-thread-trigger]");
		var panels = document.querySelectorAll("[data-thread-panel]");
		var headerTitle = document.getElementById("im-thread-title");
		var headerSubtitle = document.getElementById("im-thread-subtitle");
		var headerStatus = document.getElementById("im-thread-status");
		var headerAvatar = document.getElementById("im-thread-avatar");

		var THREAD_META = {
		"nucleo-cenario": {
			title: "Núcleo Cena::rio",
			subtitle: "Curadoria de eventos, dados e registros da cena carioca.",
			status: "Várias pessoas online",
			avatar: { text: "NR", bg: "bg-slate-900" }
		},
		"dismantle-team": {
			title: "Dismantle · Produção",
			subtitle: "Equipe interna da festa, logística e ajustes de linha do tempo.",
			status: "Equipe em planejamento",
			avatar: { text: "DT", bg: "bg-amber-500" }
		},
		"dm-eli": {
			title: "Eli",
			subtitle: "Mensagem direta · convites e agenda.",
			status: "Último acesso ontem",
			avatar: { text: "EL", bg: "bg-slate-200 text-slate-700" }
		},
		"system": {
			title: "Apollo · Sistema",
			subtitle: "Atualizações técnicas, deploys e segurança de dados.",
			status: "Notificações de sistema",
			avatar: { text: "AP", bg: "bg-slate-900" }
		}
		};

		function clearActiveTriggers() {
		triggers.forEach(function (btn) {
			btn.classList.remove("bg-slate-50", "border-slate-200");
			btn.classList.add("border-transparent");
		});
		}

		function showPanel(id) {
		panels.forEach(function (panel) {
			var pid = panel.getAttribute("data-thread-panel");
			if (pid === id) {
			panel.classList.remove("hidden");
			var bubbles = panel.querySelectorAll("[data-msg-bubble]");
			bubbles.forEach(function (bubble, index) {
				bubble.style.opacity = 0;
				bubble.style.transform = "translateY(8px)";
				animateFn(
				bubble,
				{
					opacity: [0, 1],
					transform: ["translateY(8px)", "translateY(0px)"]
				},
				{
					duration: 220,
					delay: index * 50,
					easing: "ease-out"
				}
				);
			});
			} else {
			panel.classList.add("hidden");
			}
		});
		}

		function updateHeader(id) {
		var meta = THREAD_META[id];
		if (!meta) return;
		headerTitle.textContent = meta.title;
		headerSubtitle.textContent = meta.subtitle;
		if (headerStatus) headerStatus.textContent = meta.status;

		headerAvatar.textContent = meta.text || meta.avatar.text;
		headerAvatar.className =
			"h-9 w-9 rounded-full flex items-center justify-center text-[11px] font-semibold " +
			(meta.avatar.bg || "bg-slate-900 text-white");
		}

		triggers.forEach(function (btn) {
		btn.addEventListener("click", function () {
			var id = btn.getAttribute("data-thread-id");
			if (!id) return;
			clearActiveTriggers();
			btn.classList.remove("border-transparent");
			btn.classList.add("bg-slate-50", "border-slate-200");
			showPanel(id);
			updateHeader(id);
		});
		});

		// Thread inicial
		showPanel("nucleo-cenario");
		updateHeader("nucleo-cenario");
	});
	</script>

	<?php wp_footer(); ?>
</section>
</body>
</html>

