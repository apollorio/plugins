<?php
/**
 * Template: Página pública do usuário
 * STRICT MODE: 100% design conformance with uni.css + aprioEXP components
 * Usa uni.css + Tailwind + aprioEXP-card-shell pattern
 * Forced tooltips on ALL placeholders
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_query_var( 'apollo_user_id' );
if ( ! $user_id ) {
	wp_die( 'Usuário não encontrado' );
}

$user = get_userdata( $user_id );
if ( ! $user ) {
	wp_die( 'Usuário não encontrado' );
}

$post_id = get_user_meta( $user_id, 'apollo_user_page_id', true );
$layout  = get_post_meta( $post_id, 'apollo_userpage_layout_v1', true );
if ( ! is_array( $layout ) ) {
	$layout = [];
}

// User meta data
$bio             = get_user_meta( $user_id, 'description', true );
$website         = get_user_meta( $user_id, 'user_url', true ) ?: $user->user_url;
$location        = get_user_meta( $user_id, 'apollo_location', true );
$is_verified     = (bool) get_user_meta( $user_id, 'apollo_verified', true );
$followers_count = (int) get_user_meta( $user_id, 'apollo_followers_count', true );
$following_count = (int) get_user_meta( $user_id, 'apollo_following_count', true );
$posts_count     = (int) get_user_meta( $user_id, 'apollo_posts_count', true );

// Cover image
$cover_url = get_user_meta( $user_id, 'apollo_cover_url', true );
if ( ! $cover_url ) {
	$cover_url = 'https://assets.apollo.rio.br/covers/default-cover.jpg';
}

// Enqueue assets - STRICT MODE
wp_enqueue_style( 'apollo-uni-css', 'https://assets.apollo.rio.br/uni.css', [], '2.0.0' );
wp_enqueue_style( 'apollo-base-css', 'https://assets.apollo.rio.br/base.css', [], '2.0.0' );
wp_enqueue_style( 'remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css', [], '4.7.0' );
wp_enqueue_script( 'tailwindcss', 'https://cdn.tailwindcss.com', [], null, true );

get_header();
?>

<!-- STRICT MODE: User Page Public View - 100% Design Conformance -->
<div id="apollo-userpage-root" class="mobile-container min-h-screen bg-slate-50">

	<!-- Cover Image -->
	<div class="hero-media relative h-48 md:h-64 overflow-hidden">
	<img
		src="<?php echo esc_url( $cover_url ); ?>"
		alt="<?php echo esc_attr( sprintf( 'Capa de %s', $user->display_name ) ); ?>"
		class="w-full h-full object-cover"
		data-ap-tooltip="Foto de capa do perfil"
	/>
	<div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
	</div>

	<!-- Profile Header Card -->
	<div class="relative -mt-16 px-4">
	<div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-6">

		<!-- Avatar + Follow -->
		<div class="flex items-start gap-4 -mt-20 mb-4">
		<div class="relative shrink-0">
			<?php
			echo get_avatar(
				$user_id,
				96,
				'',
				$user->display_name,
				[
					'class' => 'h-24 w-24 rounded-full border-4 border-white shadow-lg object-cover',
				]
			);
			?>
			<?php if ( $is_verified ) : ?>
			<span class="absolute -bottom-1 -right-1 h-6 w-6 bg-blue-500 rounded-full flex items-center justify-center border-2 border-white" data-ap-tooltip="Perfil verificado">
			<i class="ri-check-line text-white text-xs"></i>
			</span>
			<?php endif; ?>
		</div>
		<div class="flex-1 mt-16 min-w-0">
			<div class="flex items-center gap-2 flex-wrap">
			<h1 class="text-xl font-bold text-slate-900 truncate"><?php echo esc_html( $user->display_name ); ?></h1>
			</div>
			<p class="text-sm text-slate-500">@<?php echo esc_html( $user->user_login ); ?></p>
		</div>
		</div>

		<!-- Stats Row -->
		<div class="flex items-center justify-around py-3 border-y border-slate-100 my-4">
		<div class="text-center" data-ap-tooltip="Total de publicações">
			<span class="block text-lg font-bold text-slate-900"><?php echo esc_html( $posts_count ?: 0 ); ?></span>
			<span class="text-xs text-slate-500">Posts</span>
		</div>
		<div class="text-center" data-ap-tooltip="Pessoas que seguem este perfil">
			<span class="block text-lg font-bold text-slate-900"><?php echo esc_html( $followers_count ?: 0 ); ?></span>
			<span class="text-xs text-slate-500">Seguidores</span>
		</div>
		<div class="text-center" data-ap-tooltip="Perfis que este usuário segue">
			<span class="block text-lg font-bold text-slate-900"><?php echo esc_html( $following_count ?: 0 ); ?></span>
			<span class="text-xs text-slate-500">Seguindo</span>
		</div>
		</div>

		<!-- Bio -->
		<?php if ( $bio ) : ?>
		<p class="text-sm text-slate-700 mb-4" data-ap-tooltip="Biografia do usuário"><?php echo esc_html( $bio ); ?></p>
		<?php else : ?>
		<p class="text-sm text-slate-400 italic mb-4" data-ap-tooltip="Este usuário ainda não adicionou uma bio">Nenhuma bio ainda...</p>
		<?php endif; ?>

		<!-- Location & Links -->
		<div class="flex flex-wrap gap-3 text-sm text-slate-500">
		<?php if ( $location ) : ?>
		<span class="flex items-center gap-1" data-ap-tooltip="Localização">
			<i class="ri-map-pin-line"></i>
			<?php echo esc_html( $location ); ?>
		</span>
		<?php endif; ?>
		<?php if ( $website ) : ?>
		<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener" class="flex items-center gap-1 text-orange-600 hover:underline" data-ap-tooltip="Site pessoal">
			<i class="ri-link"></i>
			<?php echo esc_html( parse_url( $website, PHP_URL_HOST ) ); ?>
		</a>
		<?php endif; ?>
		</div>

		<!-- Action Buttons -->
		<div class="flex gap-2 mt-4">
		<?php if ( is_user_logged_in() && get_current_user_id() !== $user_id ) : ?>
		<button class="flex-1 py-2 px-4 bg-slate-900 text-white rounded-full text-sm font-medium hover:bg-slate-800 transition-colors" data-ap-tooltip="Seguir este perfil" data-action="follow" data-user-id="<?php echo esc_attr( $user_id ); ?>">
			<i class="ri-user-add-line mr-1"></i>
			Seguir
		</button>
		<!-- Bolha Button -->
		<button
			id="bolha-action-btn"
			class="py-2 px-4 border border-orange-300 bg-orange-50 rounded-full text-sm text-orange-700 hover:bg-orange-100 transition-colors"
			data-ap-tooltip="Adicionar à sua bolha de até 15 pessoas"
			data-user-id="<?php echo esc_attr( $user_id ); ?>"
			data-user-name="<?php echo esc_attr( $user->display_name ); ?>"
		>
			<i class="ri-bubble-chart-line mr-1"></i>
			<span class="bolha-btn-text">Bolha</span>
		</button>
		<button class="py-2 px-4 border border-slate-200 rounded-full text-sm text-slate-600 hover:bg-slate-50 transition-colors" data-ap-tooltip="Enviar mensagem" data-action="message" data-user-id="<?php echo esc_attr( $user_id ); ?>">
			<i class="ri-mail-line"></i>
		</button>
		<?php elseif ( get_current_user_id() === $user_id ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'action', 'edit' ) ); ?>" class="flex-1 py-2 px-4 bg-slate-900 text-white rounded-full text-sm font-medium hover:bg-slate-800 transition-colors text-center" data-ap-tooltip="Personalizar sua página pública">
			<i class="ri-edit-line mr-1"></i>
			Editar Página
		</a>
		<a href="<?php echo esc_url( home_url( '/meu-perfil/' ) ); ?>" class="py-2 px-4 border border-slate-200 rounded-full text-sm text-slate-600 hover:bg-slate-50 transition-colors" data-ap-tooltip="Ir para configurações privadas">
			<i class="ri-settings-3-line"></i>
		</a>
		<?php else : ?>
		<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="flex-1 py-2 px-4 bg-slate-900 text-white rounded-full text-sm font-medium hover:bg-slate-800 transition-colors text-center" data-ap-tooltip="Faça login para interagir">
			<i class="ri-login-box-line mr-1"></i>
			Entrar para Seguir
		</a>
		<?php endif; ?>
		</div>
	</div>
	</div>

	<?php
	// Bolha Section - Only visible to the profile owner
	$is_own_profile   = is_user_logged_in() && get_current_user_id() === $user_id;
	$is_viewing_other = is_user_logged_in() && get_current_user_id() !== $user_id;
	?>

	<?php if ( $is_own_profile ) : ?>
	<!-- Minha Bolha Card - Visible only to profile owner -->
	<section class="bolha-section px-4 py-4">
		<div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-6">
			<h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2" data-ap-tooltip="Sua bolha de conexões próximas (máx. 15 pessoas)">
				<i class="ri-bubble-chart-fill text-orange-500"></i>
				Minha Bolha
			</h2>

			<!-- Bolha Members List -->
			<div id="bolha-members" class="space-y-3 mb-6">
				<div class="text-center py-4 text-slate-400">
					<i class="ri-loader-4-line animate-spin text-xl"></i>
					<p class="text-sm mt-1">Carregando...</p>
				</div>
			</div>

			<!-- Pedidos Recebidos -->
			<div class="border-t border-slate-100 pt-4 mt-4">
				<h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2" data-ap-tooltip="Pessoas que querem entrar na sua bolha">
					<i class="ri-mail-download-line text-blue-500"></i>
					Pedidos Recebidos
				</h3>
				<div id="bolha-incoming" class="space-y-2">
					<p class="text-sm text-slate-400 text-center py-2">Nenhum pedido pendente</p>
				</div>
			</div>

			<!-- Pedidos Enviados -->
			<div class="border-t border-slate-100 pt-4 mt-4">
				<h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2" data-ap-tooltip="Pedidos que você enviou aguardando resposta">
					<i class="ri-mail-send-line text-green-500"></i>
					Pedidos Enviados
				</h3>
				<div id="bolha-outgoing" class="space-y-2">
					<p class="text-sm text-slate-400 text-center py-2">Nenhum pedido enviado</p>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- Widgets Grid -->
	<div id="userpage-widgets" class="px-4 py-6 space-y-4">
	<?php
	if ( ! empty( $layout['grid'] ) ) {
		if ( class_exists( 'Apollo_User_Page_Widgets' ) ) {
			$widgets = Apollo_User_Page_Widgets::get_widgets();
			foreach ( $layout['grid'] as $widget_data ) {
				$widget_id = $widget_data['widget'] ?? '';
				$props     = $widget_data['props'] ?? [];

				if ( isset( $widgets[ $widget_id ] ) && isset( $widgets[ $widget_id ]['render'] ) ) {
					$ctx = [
						'user_id'      => $user_id,
						'post_id'      => $post_id,
						'display_name' => $user->display_name,
					];
					echo '<div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-4" data-ap-tooltip="Widget personalizado">';
					echo $widgets[ $widget_id ]['render']( $props, $ctx );
					echo '</div>';
				}
			}
		}
	} else {
		echo '<div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-6 text-center" data-ap-tooltip="O usuário pode adicionar widgets aqui">';
		echo '<i class="ri-layout-grid-line text-3xl text-slate-300 mb-2"></i>';
		echo '<p class="text-slate-400 text-sm">Esta página ainda não tem widgets personalizados.</p>';
		echo '</div>';
	}//end if
	?>
	</div>

	<!-- Depoimentos (Testimonials Section) - STRICT MODE -->
	<section class="depoimentos px-4 pb-8">
	<div class="aprioEXP-card-shell bg-white rounded-2xl shadow-sm border border-slate-200/50 p-6">
		<h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2" data-ap-tooltip="Depoimentos deixados por outros usuários">
		<i class="ri-chat-quote-line text-orange-500"></i>
		Depoimentos
		</h2>

		<?php
		// Get comments for this user page
		$comments = get_comments(
			[
				'post_id' => $post_id,
				'status'  => 'approve',
				'number'  => 10,
				'orderby' => 'comment_date',
				'order'   => 'DESC',
			]
		);

		if ( ! empty( $comments ) ) :
			?>
		<div class="space-y-4">
			<?php foreach ( $comments as $comment ) : ?>
		<div class="flex gap-3">
				<?php echo get_avatar( $comment->comment_author_email, 40, '', '', [ 'class' => 'h-10 w-10 rounded-full shrink-0' ] ); ?>
			<div class="flex-1 min-w-0">
			<div class="flex items-center gap-2 mb-1">
				<span class="font-medium text-sm text-slate-900"><?php echo esc_html( $comment->comment_author ); ?></span>
				<span class="text-xs text-slate-400"><?php echo esc_html( human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) ); ?> atrás</span>
			</div>
			<p class="text-sm text-slate-700"><?php echo esc_html( $comment->comment_content ); ?></p>
			</div>
		</div>
		<?php endforeach; ?>
		</div>
		<?php else : ?>
		<div class="text-center py-6" data-ap-tooltip="Seja o primeiro a deixar um depoimento!">
		<i class="ri-chat-smile-2-line text-3xl text-slate-300 mb-2"></i>
		<p class="text-slate-400 text-sm">Nenhum depoimento ainda.</p>
		</div>
		<?php endif; ?>

		<!-- Leave a Testimonial Form -->
		<?php if ( is_user_logged_in() && get_current_user_id() !== $user_id ) : ?>
		<form class="mt-6 pt-4 border-t border-slate-100" id="depoimento-form" data-post-id="<?php echo esc_attr( $post_id ); ?>">
			<?php wp_nonce_field( 'apollo_depoimento_nonce', 'depoimento_nonce' ); ?>
		<div class="flex gap-3">
			<?php echo get_avatar( get_current_user_id(), 40, '', '', [ 'class' => 'h-10 w-10 rounded-full shrink-0' ] ); ?>
			<div class="flex-1">
			<textarea
				name="depoimento_content"
				rows="2"
				placeholder="Deixe um depoimento sobre <?php echo esc_attr( $user->display_name ); ?>..."
				class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
				data-ap-tooltip="Escreva algo legal sobre este usuário"
			></textarea>
			<button type="submit" class="mt-2 px-4 py-1.5 bg-slate-900 text-white text-sm rounded-full font-medium hover:bg-slate-800 transition-colors" data-ap-tooltip="Publicar seu depoimento">
				<i class="ri-send-plane-line mr-1"></i>
				Enviar
			</button>
			</div>
		</div>
		</form>
		<?php elseif ( ! is_user_logged_in() ) : ?>
		<div class="mt-6 pt-4 border-t border-slate-100 text-center">
		<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-700 rounded-full text-sm hover:bg-slate-200 transition-colors" data-ap-tooltip="Faça login para deixar um depoimento">
			<i class="ri-login-box-line"></i>
			Entrar para deixar um depoimento
		</a>
		</div>
		<?php endif; ?>
	</div>
	</section>

</div>

<!-- Tooltip CSS -->
<style>
[data-tooltip] {
	position: relative;
}
[data-tooltip]:hover::before,
[data-tooltip]:focus::before {
	content: attr(data-tooltip);
	position: absolute;
	bottom: calc(100% + 8px);
	left: 50%;
	transform: translateX(-50%);
	padding: 6px 10px;
	background: rgba(15, 23, 42, 0.95);
	color: #fff;
	font-size: 11px;
	font-weight: 500;
	border-radius: 6px;
	white-space: nowrap;
	z-index: 9999;
	pointer-events: none;
	animation: tooltipFade 0.2s ease;
}
[data-tooltip]:hover::after,
[data-tooltip]:focus::after {
	content: '';
	position: absolute;
	bottom: calc(100% + 4px);
	left: 50%;
	transform: translateX(-50%);
	border: 4px solid transparent;
	border-top-color: rgba(15, 23, 42, 0.95);
	z-index: 9999;
	pointer-events: none;
}
@keyframes tooltipFade {
	from { opacity: 0; transform: translateX(-50%) translateY(4px); }
	to { opacity: 1; transform: translateX(-50%) translateY(0); }
}
</style>

<!-- Depoimento Form Handler -->
<script>
document.addEventListener('DOMContentLoaded', function() {
	const form = document.getElementById('depoimento-form');
	if (!form) return;

	form.addEventListener('submit', async function(e) {
	e.preventDefault();
	const btn = form.querySelector('button[type="submit"]');
	const textarea = form.querySelector('textarea');
	const content = textarea.value.trim();

	if (!content) {
		alert('Por favor, escreva algo antes de enviar.');
		return;
	}

	btn.disabled = true;
	btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Enviando...';

	try {
		const formData = new FormData();
		formData.append('action', 'apollo_submit_depoimento');
		formData.append('post_id', form.dataset.postId);
		formData.append('content', content);
		formData.append('nonce', form.querySelector('[name="depoimento_nonce"]').value);

		const response = await fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
		method: 'POST',
		body: formData
		});

		const data = await response.json();

		if (data.success) {
		textarea.value = '';
		// Reload to show new comment
		window.location.reload();
		} else {
		alert(data.data?.message || 'Erro ao enviar depoimento.');
		}
	} catch (err) {
		alert('Erro de conexão. Tente novamente.');
	} finally {
		btn.disabled = false;
		btn.innerHTML = '<i class="ri-send-plane-line mr-1"></i> Enviar';
	}
	});
});
</script>

<!-- Bolha System JavaScript -->
<script>
(function() {
	'use strict';

	const BOLHA_API = '<?php echo esc_url( rest_url( 'apollo/v1/bolha/' ) ); ?>';
	const NONCE = '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>';
	const PROFILE_USER_ID = <?php echo (int) $user_id; ?>;
	const IS_OWN_PROFILE = <?php echo $is_own_profile ? 'true' : 'false'; ?>;
	const IS_LOGGED_IN = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;

	// API helper
	async function bolhaAPI(endpoint, method = 'GET', body = null) {
		const options = {
			method,
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': NONCE
			}
		};
		if (body) {
			options.body = JSON.stringify(body);
		}
		const response = await fetch(BOLHA_API + endpoint, options);
		return response.json();
	}

	// Render user card
	function renderUserCard(user, actions = []) {
		const actionsHtml = actions.map(a =>
			`<button class="bolha-action px-2 py-1 text-xs rounded ${a.class}" data-action="${a.action}" data-user-id="${user.id}" title="${a.tooltip}">
				<i class="${a.icon}"></i> ${a.label}
			</button>`
		).join('');

		return `
			<div class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 transition-colors">
				<a href="${user.profile_url}" class="shrink-0">
					<img src="${user.avatar_url}" alt="${user.display_name}" class="w-10 h-10 rounded-full object-cover">
				</a>
				<div class="flex-1 min-w-0">
					<a href="${user.profile_url}" class="font-medium text-sm text-slate-900 hover:text-orange-600 truncate block">
						${user.display_name}
					</a>
					<span class="text-xs text-slate-400">@${user.user_login}</span>
				</div>
				<div class="flex gap-1">${actionsHtml}</div>
			</div>
		`;
	}

	// Load bolha data for own profile
	async function loadBolhaData() {
		if (!IS_OWN_PROFILE) return;

		const membersContainer = document.getElementById('bolha-members');
		const incomingContainer = document.getElementById('bolha-incoming');
		const outgoingContainer = document.getElementById('bolha-outgoing');

		try {
			// Load bolha members
			const bolhaRes = await bolhaAPI('listar');
			if (bolhaRes.success && bolhaRes.data.length > 0) {
				membersContainer.innerHTML = bolhaRes.data.map(user =>
					renderUserCard(user, [{
						action: 'remover',
						label: 'Remover',
						icon: 'ri-close-line',
						class: 'bg-red-50 text-red-600 hover:bg-red-100',
						tooltip: 'Remover da bolha'
					}])
				).join('');
			} else {
				membersContainer.innerHTML = `
					<div class="text-center py-6" data-ap-tooltip="Adicione pessoas à sua bolha!">
						<i class="ri-bubble-chart-line text-3xl text-slate-300 mb-2"></i>
						<p class="text-slate-400 text-sm">Sua bolha está vazia.</p>
						<p class="text-slate-300 text-xs mt-1">Visite perfis e adicione pessoas!</p>
					</div>
				`;
			}

			// Load pending requests
			const pedidosRes = await bolhaAPI('pedidos');
			if (pedidosRes.success) {
				// Incoming
				if (pedidosRes.data.incoming.length > 0) {
					incomingContainer.innerHTML = pedidosRes.data.incoming.map(user =>
						renderUserCard(user, [
							{
								action: 'aceitar',
								label: 'Aceitar',
								icon: 'ri-check-line',
								class: 'bg-green-50 text-green-600 hover:bg-green-100',
								tooltip: 'Aceitar na bolha'
							},
							{
								action: 'rejeitar',
								label: 'Recusar',
								icon: 'ri-close-line',
								class: 'bg-red-50 text-red-600 hover:bg-red-100',
								tooltip: 'Recusar pedido'
							}
						])
					).join('');
				} else {
					incomingContainer.innerHTML = '<p class="text-sm text-slate-400 text-center py-2">Nenhum pedido pendente</p>';
				}

				// Outgoing
				if (pedidosRes.data.outgoing.length > 0) {
					outgoingContainer.innerHTML = pedidosRes.data.outgoing.map(user =>
						renderUserCard(user, [{
							action: 'cancelar',
							label: 'Cancelar',
							icon: 'ri-close-line',
							class: 'bg-slate-100 text-slate-600 hover:bg-slate-200',
							tooltip: 'Cancelar pedido'
						}])
					).join('');
				} else {
					outgoingContainer.innerHTML = '<p class="text-sm text-slate-400 text-center py-2">Nenhum pedido enviado</p>';
				}
			}
		} catch (err) {
			console.error('Erro ao carregar bolha:', err);
			membersContainer.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Erro ao carregar dados</p>';
		}
	}

	// Update bolha button state when viewing other profile
	async function updateBolhaButton() {
		if (!IS_LOGGED_IN || IS_OWN_PROFILE) return;

		const btn = document.getElementById('bolha-action-btn');
		if (!btn) return;

		const btnText = btn.querySelector('.bolha-btn-text');
		const userName = btn.dataset.userName;

		try {
			const res = await bolhaAPI('status/' + PROFILE_USER_ID);
			if (!res.success) return;

			btn.disabled = false;

			switch (res.status) {
				case 'na_bolha':
					btn.className = 'py-2 px-4 border border-green-300 bg-green-50 rounded-full text-sm text-green-700 hover:bg-green-100 transition-colors';
					btn.innerHTML = '<i class="ri-check-line mr-1"></i><span class="bolha-btn-text">Na sua bolha</span>';
					btn.dataset.action = 'remover';
					btn.title = 'Clique para remover da sua bolha';
					break;
				case 'pedido_enviado':
					btn.className = 'py-2 px-4 border border-yellow-300 bg-yellow-50 rounded-full text-sm text-yellow-700 cursor-not-allowed';
					btn.innerHTML = '<i class="ri-time-line mr-1"></i><span class="bolha-btn-text">Aguardando</span>';
					btn.dataset.action = 'cancelar';
					btn.title = 'Pedido enviado, aguardando resposta. Clique para cancelar.';
					break;
				case 'pedido_recebido':
					btn.className = 'py-2 px-4 border border-blue-300 bg-blue-50 rounded-full text-sm text-blue-700 hover:bg-blue-100 transition-colors';
					btn.innerHTML = '<i class="ri-mail-check-line mr-1"></i><span class="bolha-btn-text">Aceitar pedido</span>';
					btn.dataset.action = 'aceitar';
					btn.title = 'Esta pessoa quer entrar na sua bolha! Clique para aceitar.';
					break;
				default:
					if (!res.pode_pedir) {
						btn.disabled = true;
						btn.className = 'py-2 px-4 border border-slate-200 bg-slate-100 rounded-full text-sm text-slate-400 cursor-not-allowed';
						if (res.minha_bolha_cheia) {
							btn.innerHTML = '<i class="ri-bubble-chart-line mr-1"></i><span class="bolha-btn-text">Sua bolha cheia</span>';
							btn.title = 'Sua bolha está cheia (máx. 15 pessoas)';
						} else {
							btn.innerHTML = '<i class="ri-bubble-chart-line mr-1"></i><span class="bolha-btn-text">Bolha cheia</span>';
							btn.title = 'A bolha desta pessoa está cheia';
						}
					} else {
						btn.className = 'py-2 px-4 border border-orange-300 bg-orange-50 rounded-full text-sm text-orange-700 hover:bg-orange-100 transition-colors';
						btn.innerHTML = '<i class="ri-bubble-chart-line mr-1"></i><span class="bolha-btn-text">Adicionar à bolha</span>';
						btn.dataset.action = 'pedir';
						btn.title = 'Adicionar ' + userName + ' à sua bolha';
					}
			}
		} catch (err) {
			console.error('Erro ao verificar status da bolha:', err);
		}
	}

	// Handle bolha actions
	async function handleBolhaAction(action, userId) {
		let endpoint = '';
		let body = {};

		switch (action) {
			case 'pedir':
				endpoint = 'pedir';
				body = { target_id: userId };
				break;
			case 'aceitar':
				endpoint = 'aceitar';
				body = { user_id: userId };
				break;
			case 'rejeitar':
				endpoint = 'rejeitar';
				body = { user_id: userId };
				break;
			case 'remover':
				if (!confirm('Remover esta pessoa da sua bolha?')) return;
				endpoint = 'remover';
				body = { user_id: userId };
				break;
			case 'cancelar':
				endpoint = 'cancelar';
				body = { user_id: userId };
				break;
			default:
				return;
		}

		try {
			const res = await bolhaAPI(endpoint, 'POST', body);
			if (res.success) {
				// Refresh UI
				if (IS_OWN_PROFILE) {
					loadBolhaData();
				} else {
					updateBolhaButton();
				}
			} else {
				alert(res.message || 'Erro ao processar ação.');
			}
		} catch (err) {
			console.error('Erro na ação bolha:', err);
			alert('Erro de conexão. Tente novamente.');
		}
	}

	// Event delegation for bolha actions
	document.addEventListener('click', function(e) {
		const actionBtn = e.target.closest('[data-action]');
		if (!actionBtn) return;

		const action = actionBtn.dataset.action;
		const userId = parseInt(actionBtn.dataset.userId, 10);

		if (['pedir', 'aceitar', 'rejeitar', 'remover', 'cancelar'].includes(action) && userId) {
			e.preventDefault();
			handleBolhaAction(action, userId);
		}
	});

	// Initialize on DOM ready
	document.addEventListener('DOMContentLoaded', function() {
		if (IS_OWN_PROFILE) {
			loadBolhaData();
		} else if (IS_LOGGED_IN) {
			updateBolhaButton();
		}
	});
})();
</script>

<?php get_footer(); ?>
