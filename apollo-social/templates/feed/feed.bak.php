<?php
/**
 * Feed Template - Apollo Social
 * Based on CodePen: https://codepen.io/Rafael-Valle-the-looper/pen/OPNjrPm
 *
 * Renders social feed with composer, filter tabs, and post cards
 * NOTE: This is a partial template included in Canvas layout - NO DOCTYPE/HTML/BODY tags
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// FASE 2: Dados já vêm do FeedRenderer via CanvasBuilder
$posts        = $view['data']['posts'] ?? array();
$current_user = $view['data']['current_user'] ?? array();

$ajax_url      = admin_url( 'admin-ajax.php' );
$rest_url      = rest_url( 'apollo/v1' );
$nonce         = wp_create_nonce( 'wp_rest' );
$comment_nonce = wp_create_nonce( 'apollo_comment_nonce' );
?>

<div class="apollo-feed-root aprioEXP-body h-full" id="apollo-feed-root" style="background: var(--bg-surface);">
<div class="min-h-screen flex flex-col">
	<!-- Minimal Header -->
	<header class="sticky top-0 z-50 bg-white/60 backdrop-blur-2xl border-b border-slate-200/50">
	<div class="max-w-7xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">
		<div class="flex items-center gap-4">
		<div class="flex items-center gap-3">
			<div class="h-9 w-9 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
			<i class="ri-slack-fill text-white text-[28px]"></i>
			</div>
			<div>
			<h1 class="text-[16px] font-extrabold mt-2 text-slate-900">Apollo::rio</h1>
			<p class="text-[14px] text-slate-500">@<?php echo esc_html( $current_user['name'] ?? 'user' ); ?></p>
			</div>
		</div>
		</div>

		<div class="flex items-center gap-3">
		<button class="hidden md:flex h-9 w-9 items-center justify-center rounded-full hover:bg-slate-100 transition-colors">
			<i class="ri-search-line text-slate-600"></i>
		</button>
		<button class="hidden md:flex h-9 w-9 items-center justify-center rounded-full hover:bg-slate-100 transition-colors">
			<i class="ri-notification-3-line text-slate-600"></i>
		</button>
		<button class="h-9 w-9 rounded-full overflow-hidden ring-2 ring-white shadow-sm">
			<img src="<?php echo esc_url( $current_user['avatar'] ?? '' ); ?>" alt="<?php echo esc_attr( $current_user['name'] ?? '' ); ?>" class="h-full w-full object-cover" />
		</button>
		</div>
	</div>
	</header>

	<!-- Main Content -->
	<main class="flex-1 flex justify-center px-4 md:px-6 py-8">
	<div class="w-full max-w-2xl">
	   
		<!-- Composer -->
		<section class="bg-white rounded-2xl shadow-sm border border-slate-200/50 p-4 mb-6">
		<div class="flex gap-3">
			<div class="h-10 w-10 rounded-full overflow-hidden shrink-0">
			<img src="<?php echo esc_url( $current_user['avatar'] ?? '' ); ?>" alt="<?php echo esc_attr( $current_user['name'] ?? '' ); ?>" class="h-full w-full object-cover" />
			</div>
			<div class="flex-1">
			<input 
				type="text" 
				id="apollo-feed-composer-input"
				placeholder="O que está acontecendo na cena?" 
				class="w-full border-0 outline-none text-[15px] text-slate-900 placeholder:text-slate-400 bg-transparent"
			/>
			<div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
				<div class="flex items-center gap-2">
				<button id="ADD_IMAGE_TO_POST" class="h-8 w-8 flex items-center justify-center rounded-full hover:bg-orange-50 text-orange-600 transition-colors" title="Add Image">
					<i class="ri-image-line text-lg"></i>
				</button>
				<button id="SIMPLE_POLL_QUESTION_MAX5answers_SELECT_ONLY_ONE_AND_ON_CLICK_SHOW_POLL RESULTS_OR_HIDDEN_RESULTS_ON_USER_OPTION" class="h-8 w-8 flex items-center justify-center rounded-full hover:bg-orange-50 text-orange-600 transition-colors" title="Run a poll with max 5 answers">
					<i class="ri-align-item-bottom-line"></i>
				</button>
				<button id="check_in_some_location" class="h-8 w-8 flex items-center justify-center rounded-full hover:bg-orange-50 text-orange-600 transition-colors" title="Arrived in the Event? Share location">
					<i class="ri-map-pin-line text-lg"></i>
				</button>
				<button id="ANNOUNCE_TICKETS_CLASSIFIEDS_wpadevrts" class="h-8 w-8 flex items-center justify-center rounded-full hover:bg-orange-50 text-orange-600 transition-colors" title="Announce on classifieds">
					<i class="ri-newspaper-line"></i>
				</button>
				</div>
				<button class="px-4 py-1.5 bg-slate-900 text-white rounded-full text-sm font-medium hover:bg-slate-800 transition-colors apollo-feed-publish-btn" data-hold-to-confirm>
				<i class="ri-speak-ai-fill"></i> Publicar
				</button>
			</div>
			</div>
		</div>
		</section>

		<!-- Filter Tabs -->
		<div class="flex items-center gap-2 mb-6 overflow-x-auto pb-2" role="tablist">
		<button class="menutag" data-tab-target="feed-all" data-active="true" role="tab">
			Tudo
		</button>
		<button class="menutag" data-tab-target="feed-events" role="tab">
			Eventos
		</button>
		<button class="menutag" data-tab-target="feed-communities" role="tab">
			Comunidades
		</button>
		<button class="menutag" data-tab-target="feed-system" role="tab">
			Sistema
		</button>
		</div>

		<!-- Feed -->
		<div class="space-y-6">

		<!-- TAB: TUDO -->
		<div data-tab-panel="feed-all" role="tabpanel" class="space-y-6">
			<?php if ( empty( $posts ) ) : ?>
			<article class="bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5">
				<p class="text-[15px] text-slate-800 text-center">Nenhum post ainda. Seja o primeiro a compartilhar!</p>
			</article>
			<?php else : ?>
				<?php foreach ( $posts as $post_item ) : ?>
					<?php
					// FASE 2: Usar partials baseado no tipo
					$post_type = $post_item['type'] ?? 'user_post';
					$post_data = $post_item;

					if ( $post_type === 'event' ) {
						include APOLLO_SOCIAL_PLUGIN_DIR . 'templates/feed/partials/post-event.php';
					} elseif ( $post_type === 'ad' ) {
						// TODO: Criar partial para anúncios
						include APOLLO_SOCIAL_PLUGIN_DIR . 'templates/feed/partials/post-user.php';
					} elseif ( $post_type === 'news' ) {
						// TODO: Criar partial para notícias
						include APOLLO_SOCIAL_PLUGIN_DIR . 'templates/feed/partials/post-user.php';
					} else {
						// Post de usuário (padrão)
						include APOLLO_SOCIAL_PLUGIN_DIR . 'templates/feed/partials/post-user.php';
					}
					?>
			<?php endforeach; ?>
			<?php endif; ?>
		   
			<!-- Load More Button -->
			<div class="text-center py-4">
			<button id="apollo-feed-load-more" class="px-6 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm font-medium hover:bg-slate-200 transition-colors">
				Carregar mais
			</button>
			</div>
		</div>

		<!-- TAB: EVENTOS -->
		<div data-tab-panel="feed-events" role="tabpanel" class="hidden space-y-6">
			<article class="bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5" data-feed-card>
			<p class="text-[15px] text-slate-800">Seus eventos autorais este mês: Dismantle #01, After Botafogo, Sunset Ilha.</p>
			</article>
		</div>

		<!-- TAB: COMUNIDADES -->
		<div data-tab-panel="feed-communities" role="tabpanel" class="hidden space-y-6">
			<article class="bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5" data-feed-card>
			<p class="text-[15px] text-slate-800">Thread fixa para reviews de festas da comunidade Tropicalis.</p>
			</article>
		</div>

		<!-- TAB: SISTEMA -->
		<div data-tab-panel="feed-system" role="tabpanel" class="hidden space-y-6">
			<article class="bg-white rounded-2xl shadow-sm border border-slate-200/50 p-5" data-feed-card>
			<p class="text-[15px] text-slate-800">Apollo está finalizando etapa de segurança. Em breve: produção.</p>
			</article>
		</div>
		</div>
	</div>
	</main>
</div>

<!-- Mobile Bottom Nav -->
<div class="md:hidden sticky bottom-0 bg-white/90 backdrop-blur-xl border-t border-slate-200/50 px-3 py-2 flex items-center justify-around">
	<button class="flex flex-col items-center gap-0.5 text-slate-600" title="Discover Events">
	<i class="ri-calendar-line text-xl"></i>
	<span class="text-[9px]">Eventos</span>
	</button>
	<button class="flex flex-col items-center gap-0.5 text-slate-400">
	<i class="ri-bar-chart-line text-xl" title="Group as Comunidades"></i>
	<span class="text-[9px]">Comunas</span>
	</button>
	<button id="btn-add-mobile" class="h-12 w-12 -mt-8 rounded-full bg-neutral-900 text-white flex items-center justify-center shadow-lg" title="Add New OPTION LIST DISPLAY FOR EVENTO / ANNOUNCE classified / GROUP AS COMMUNITY or GROUP AS NUCLEO (ALL GROUPS MUST BE APPROVED BY ADMIN!!!)">
	<i class="ri-add-line text-2xl"></i>
	</button>
	<button class="flex flex-col items-center gap-0.5 text-slate-400" title="Chat">
	<i class="ri-team-line text-xl"></i>
	<span class="text-[9px]">Chats</span>
	</button>
	<button class="flex flex-col items-center gap-0.5 text-slate-400" title="Settings">
	<i class="ri-settings-3-line text-xl"></i>
	<span class="text-[9px]">Ajustes</span>
	</button>
</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
	const { animate } = window.Motion || {};
	const root = document.getElementById("apollo-feed-root");
	if (!root) return;

	const tabs = document.querySelectorAll('[role="tab"]');
	const panels = document.querySelectorAll('[data-tab-panel]');

	function animatePanel(panel) {
	if (!animate) return;
	const cards = panel.querySelectorAll("[data-feed-card]");
	cards.forEach((card, index) => {
		animate(card, 
		{ opacity: [0, 1], transform: ["translateY(30px)", "translateY(0px)"] },
		{ duration: 0.5, delay: index * 0.1, easing: [0.25, 0.8, 0.25, 1] }
		);
	});
	}

	function setActiveTab(targetId) {
	tabs.forEach((tab) => {
		const isActive = tab.getAttribute("data-tab-target") === targetId;
		tab.setAttribute("data-active", isActive ? "true" : "false");
	});

	panels.forEach((panel) => {
		if (panel.getAttribute("data-tab-panel") === targetId) {
		panel.classList.remove("hidden");
		animatePanel(panel);
		} else {
		panel.classList.add("hidden");
		}
	});
	}

	tabs.forEach((tab) => {
	tab.addEventListener("click", (e) => {
		e.preventDefault();
		const target = tab.getAttribute("data-tab-target");
		if (target) setActiveTab(target);
	});
	});

	setActiveTab("feed-all");

	// Like animation
	if (animate) {
	document.querySelectorAll(".ri-heart-3-line").forEach((heart) => {
		heart.closest('button')?.addEventListener("click", (e) => {
		e.preventDefault();
		animate(heart, 
			{ scale: [1, 1.4, 1], rotate: [0, -20, 20, 0] },
			{ duration: 0.6, easing: "ease-in-out" }
		);
		heart.classList.toggle("ri-heart-3-line");
		heart.classList.toggle("ri-heart-3-fill");
		});
	});
	}
});
</script>

<style>
[data-feed-card] {
	animation: fadeIn 0.5s ease-out backwards;
}

[data-feed-card]:nth-child(1) { animation-delay: 0.1s; }
[data-feed-card]:nth-child(2) { animation-delay: 0.2s; }
[data-feed-card]:nth-child(3) { animation-delay: 0.3s; }
[data-feed-card]:nth-child(4) { animation-delay: 0.4s; }

@keyframes fadeIn {
	from {
	opacity: 0;
	transform: translateY(20px);
	}
	to {
	opacity: 1;
	transform: translateY(0);
	}
}

::-webkit-scrollbar {
	width: 6px;
	height: 6px;
}

::-webkit-scrollbar-track {
	background: transparent;
}

::-webkit-scrollbar-thumb {
	background: #cbd5e1;
	border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
	background: #94a3b8;
}

* {
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
}
</style>

<script>
window.apolloFeedData = {
	ajaxUrl: <?php echo json_encode( $ajax_url ); ?>,
	restUrl: <?php echo json_encode( $rest_url ); ?>,
	nonce: <?php echo json_encode( $nonce ); ?>,
	commentNonce: <?php echo json_encode( $comment_nonce ); ?>,
	currentUserId: <?php echo absint( $current_user['id'] ?? 0 ); ?>,
	posts: <?php echo json_encode( $posts ); ?>
};
</script>
