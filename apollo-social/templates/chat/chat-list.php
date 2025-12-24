<?php
/**
 * Chat List Template - Apollo Social
 * Based on CodePen: https://codepen.io/Rafael-Valle-the-looper/pen/vEGJvEG
 *
 * Renders chat interface with thread switching and message bubbles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$conversations = $view['data']['conversations'] ?? array();
$current_user  = $view['data']['current_user'] ?? array();
$ajax_url      = admin_url( 'admin-ajax.php' );
$nonce         = wp_create_nonce( 'apollo_chat' );
?>

<section class="aprioEXP-body">
	<div class="min-h-screen flex flex-col apollo-chat-viewport">

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
						@<?php echo esc_html( $current_user['name'] ?? 'user' ); ?> · Conversas instantâneas
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

				<button class="hidden md:inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-1.5 font-medium text-slate-700 hover:bg-slate-50">
					<i class="ri-user-3-line text-xs"></i>
					<span>Meu perfil interno</span>
				</button>

				<a href="#" class="inline-flex items-center gap-1 rounded-full bg-slate-90099 px-3 py-1.5 font-medium text-white">
					<i class="ri-external-link-line text-xs"></i>
					<span>Abrir página pública</span>
				</a>

				<button class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-slate-100">
					<div class="h-7 w-7 overflow-hidden rounded-full bg-slate-200">
						<img src="<?php echo esc_url( $current_user['avatar'] ?? '' ); ?>" alt="<?php echo esc_attr( $current_user['name'] ?? '' ); ?>" class="h-full w-full object-cover" />
					</div>
				</button>
			</div>
		</header>

	<!-- Main -->
	<main class="flex-1 flex justify-center px-3 md:px-6 py-4 md:py-6">
		<div class="w-full max-w-none flex flex-col md:grid md:grid-cols-[minmax(0,1.1fr)_minmax(0,1.9fr)] gap-4">

				<!-- LISTA DE CONVERSAS (mobile primeiro) -->
				<aside class="aprioEXP-card-shell p-3 md:p-4 flex flex-col apollo-chat-panel bg-white/90">
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
						<button class="menutag event-category" type="button" data-filter="all">
							<i class="ri-chat-1-line"></i>
							<span>Todos</span>
						</button>
						<button class="menutag event-category" type="button" data-filter="nucleos">
							<i class="ri-lock-2-line"></i>
							<span>Núcleos</span>
						</button>
						<button class="menutag event-category" type="button" data-filter="communities">
							<i class="ri-community-line"></i>
							<span>Comunidades</span>
						</button>
						<button class="menutag event-category" type="button" data-filter="dms">
							<i class="ri-user-heart-line"></i>
							<span>DMs</span>
						</button>
					</div>

					<!-- Lista conversas (clean, cards brancos, active soft) -->
					<div class="flex-1 overflow-y-auto space-y-1 text-[12px]">
						<?php if ( empty( $conversations ) ) : ?>
							<p class="text-center text-slate-400 py-8">Nenhuma conversa ainda</p>
						<?php else : ?>
							<?php foreach ( $conversations as $index => $conv ) : ?>
								<button
									type="button"
									class="w-full text-left rounded-xl px-2.5 py-2 flex items-start gap-2 border <?php echo $index === 0 ? 'border-slate-200 bg-slate-50' : 'border-transparent hover:border-slate-200 hover:bg-slate-50'; ?>"
									data-thread-trigger
									data-thread-id="<?php echo esc_attr( $conv['id'] ?? 'conv-' . $index ); ?>"
									aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
								>
									<div class="shrink-0">
										<div class="h-8 w-8 rounded-full <?php echo esc_attr( $conv['bg_color'] ?? 'bg-slate-900' ); ?> flex items-center justify-center text-[12px] font-semibold text-white">
											<?php echo esc_html( $conv['initials'] ?? '??' ); ?>
										</div>
									</div>
									<div class="min-w-0 flex-1">
										<div class="flex items-center justify-between gap-2">
											<p class="truncate font-semibold text-[12px]"><?php echo esc_html( $conv['title'] ?? 'Conversa' ); ?></p>
											<span class="text-[10px] text-slate-400"><?php echo esc_html( $conv['time'] ?? 'agora' ); ?></span>
										</div>
										<p class="truncate text-[11px] text-slate-600">
											<?php echo esc_html( $conv['last_message'] ?? 'Nenhuma mensagem' ); ?>
										</p>
										<?php if ( ! empty( $conv['meta'] ) ) : ?>
											<div class="mt-1 flex items-center gap-1 text-[10px] text-slate-400">
												<span><?php echo esc_html( $conv['meta'] ); ?></span>
											</div>
										<?php endif; ?>
									</div>
								</button>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</aside>

				<!-- PAINEL DE CONVERSA (open-kitchen: branco, vidro, poucos elementos) -->
				<section class="aprioEXP-card-shell p-0 flex flex-col apollo-chat-panel bg-white/95">
					<!-- Cabeçalho da conversa ativa -->
					<header class="px-3 md:px-4 py-2.5 border-b border-slate-200 flex items-center justify-between gap-2 bg-white" id="im-thread-header-main">
						<div class="flex items-center gap-2 min-w-0">
							<div class="h-9 w-9 rounded-full flex items-center justify-center text-[11px] font-semibold text-white bg-slate-900" id="im-thread-avatar">
								<?php echo ! empty( $conversations ) ? esc_html( $conversations[0]['initials'] ?? '??' ) : '??'; ?>
							</div>
							<div class="min-w-0">
								<div class="flex items-center gap-1">
									<p class="text-[13px] font-semibold truncate" id="im-thread-title">
										<?php echo ! empty( $conversations ) ? esc_html( $conversations[0]['title'] ?? 'Selecione uma conversa' ) : 'Selecione uma conversa'; ?>
									</p>
									<?php if ( ! empty( $conversations ) && isset( $conversations[0]['is_private'] ) ) : ?>
										<span class="aprioEXP-metric-chip hidden md:inline-flex">
											<i class="ri-lock-2-line text-[11px]"></i>
											<span>Núcleo privado</span>
										</span>
									<?php endif; ?>
								</div>
								<p class="text-[11px] text-slate-500 truncate" id="im-thread-subtitle">
									<?php echo ! empty( $conversations ) ? esc_html( $conversations[0]['subtitle'] ?? '' ) : 'Selecione uma conversa para começar'; ?>
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
							<div class="flex justify-center">
								<span class="aprioEXP-metric-chip text-[10px]">
									Selecione uma conversa para ver mensagens
								</span>
							</div>
						</div>

						<!-- Composer (barra clean, arredondada) -->
						<div class="border-t border-slate-200 px-3 md:px-4 py-2.5 bg-white">
							<form class="flex items-end gap-2" id="apollo-chat-form">
								<button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-slate-100">
									<i class="ri-add-line text-[16px] text-slate-600"></i>
								</button>
								<button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-slate-100">
									<i class="ri-emotion-line text-[16px] text-slate-600"></i>
								</button>

								<div class="flex-1 min-w-0">
									<div class="relative">
										<textarea
											id="apollo-chat-input"
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
									type="submit"
									class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-90099 text-white"
									data-hold-to-confirm
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
</section>

<script>
window.apolloChatData = {
	ajaxUrl: <?php echo json_encode( $ajax_url ); ?>,
	nonce: <?php echo json_encode( $nonce ); ?>,
	currentUserId: <?php echo absint( $current_user['id'] ?? 0 ); ?>,
	conversations: <?php echo json_encode( $conversations ); ?>
};
</script>
