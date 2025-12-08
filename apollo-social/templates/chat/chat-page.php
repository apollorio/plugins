<?php
/**
 * Chat Page Template - ShadCN Sidebar-09
 * https://ui.shadcn.com/view/new-york-v4/sidebar-09
 *
 * Layout com sidebar de conversas e área de mensagens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Carregar sistema ShadCN/Tailwind
$shadcn_loader = APOLLO_SOCIAL_PLUGIN_DIR . 'includes/apollo-shadcn-loader.php';
if ( file_exists( $shadcn_loader ) ) {
	require_once $shadcn_loader;
	if ( class_exists( 'Apollo_ShadCN_Loader' ) ) {
		Apollo_ShadCN_Loader::get_instance();
	}
}

$user_obj = wp_get_current_user();
$user_id  = $user_obj->ID;

// Buscar conversas (placeholder - integrar com sistema real)
$conversations = [
	[
		'id'           => 1,
		'name'         => 'João Silva',
		'avatar'       => get_avatar_url( 1, [ 'size' => 40 ] ),
		'last_message' => 'Olá! Como vai?',
		'time'         => '09:34',
		'unread'       => 2,
	],
	[
		'id'           => 2,
		'name'         => 'Maria Santos',
		'avatar'       => get_avatar_url( 2, [ 'size' => 40 ] ),
		'last_message' => 'Obrigada pela ajuda!',
		'time'         => 'Ontem',
		'unread'       => 0,
	],
];

$selected_conversation = isset( $_GET['conversation'] ) ? intval( $_GET['conversation'] ) : ( ! empty( $conversations ) ? $conversations[0]['id'] : null );

get_header();
?>

<div class="flex h-screen w-full overflow-hidden bg-background">
	<!-- Sidebar - Lista de Conversas -->
	<aside class="sidebar border-r border-border bg-card w-80 flex-shrink-0 flex flex-col">
		<!-- Sidebar Header -->
		<div class="sidebar-header flex items-center justify-between p-4 border-b border-border">
			<div class="flex items-center gap-3">
				<h2 class="text-lg font-semibold text-foreground">Mensagens</h2>
			</div>
			<button class="btn btn-ghost btn-sm p-2">
				<i class="ri-add-line"></i>
			</button>
		</div>

		<!-- Search -->
		<div class="p-4 border-b border-border">
			<div class="relative">
				<i class="ri-search-line absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground"></i>
				<input 
					type="text" 
					placeholder="Buscar conversas..." 
					class="input pl-10 w-full"
					id="searchConversations"
				>
			</div>
		</div>

		<!-- Conversations List -->
		<div class="sidebar-content flex-1 overflow-y-auto">
			<?php if ( empty( $conversations ) ) : ?>
				<div class="p-8 text-center">
					<i class="ri-message-3-line text-4xl text-muted-foreground mb-4"></i>
					<p class="text-sm text-muted-foreground">Nenhuma conversa ainda</p>
				</div>
			<?php else : ?>
				<div class="space-y-1 p-2">
					<?php foreach ( $conversations as $conv ) : ?>
						<a 
							href="<?php echo esc_url( add_query_arg( 'conversation', $conv['id'] ) ); ?>"
							class="flex items-center gap-3 p-3 rounded-md hover:bg-accent transition-colors <?php echo $selected_conversation === $conv['id'] ? 'bg-accent' : ''; ?>"
						>
							<div class="avatar flex-shrink-0">
								<img src="<?php echo esc_url( $conv['avatar'] ); ?>" alt="<?php echo esc_attr( $conv['name'] ); ?>" class="rounded-full">
							</div>
							<div class="flex-1 min-w-0">
								<div class="flex items-center justify-between mb-1">
									<p class="text-sm font-medium text-foreground truncate">
										<?php echo esc_html( $conv['name'] ); ?>
									</p>
									<span class="text-xs text-muted-foreground flex-shrink-0 ml-2">
										<?php echo esc_html( $conv['time'] ); ?>
									</span>
								</div>
								<p class="text-xs text-muted-foreground truncate">
									<?php echo esc_html( $conv['last_message'] ); ?>
								</p>
							</div>
							<?php if ( $conv['unread'] > 0 ) : ?>
								<span class="badge badge-primary flex-shrink-0">
									<?php echo $conv['unread']; ?>
								</span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</aside>

	<!-- Main Chat Area -->
	<main class="flex-1 flex flex-col overflow-hidden">
		<?php if ( $selected_conversation ) : ?>
			<?php
			$current_conv = array_filter(
				$conversations,
				function ( $c ) use ( $selected_conversation ) {
					return $c['id'] === $selected_conversation;
				}
			);
			$current_conv = ! empty( $current_conv ) ? reset( $current_conv ) : null;
			?>
			
			<!-- Chat Header -->
			<header class="border-b border-border bg-card px-6 py-4 flex items-center justify-between">
				<div class="flex items-center gap-3">
					<div class="avatar">
						<img src="<?php echo esc_url( $current_conv['avatar'] ); ?>" alt="<?php echo esc_attr( $current_conv['name'] ); ?>" class="rounded-full">
					</div>
					<div>
						<h3 class="text-sm font-semibold text-foreground"><?php echo esc_html( $current_conv['name'] ); ?></h3>
						<p class="text-xs text-muted-foreground">Online</p>
					</div>
				</div>
				<div class="flex items-center gap-2">
					<button class="btn btn-ghost btn-sm">
						<i class="ri-phone-line"></i>
					</button>
					<button class="btn btn-ghost btn-sm">
						<i class="ri-video-line"></i>
					</button>
					<button class="btn btn-ghost btn-sm">
						<i class="ri-more-line"></i>
					</button>
				</div>
			</header>

			<!-- Messages Area -->
			<div class="flex-1 overflow-y-auto p-6 space-y-4" id="messagesArea">
				<!-- Mensagens de exemplo -->
				<div class="flex items-start gap-3">
					<div class="avatar flex-shrink-0">
						<img src="<?php echo esc_url( $current_conv['avatar'] ); ?>" alt="" class="rounded-full">
					</div>
					<div class="flex-1">
						<div class="bg-muted rounded-lg p-3 max-w-md">
							<p class="text-sm text-foreground">Olá! Como posso ajudar?</p>
						</div>
						<p class="text-xs text-muted-foreground mt-1">09:30</p>
					</div>
				</div>

				<div class="flex items-start gap-3 justify-end">
					<div class="flex-1 flex justify-end">
						<div class="bg-primary text-primary-foreground rounded-lg p-3 max-w-md">
							<p class="text-sm">Oi! Preciso de ajuda com meu perfil</p>
						</div>
					</div>
					<div class="avatar flex-shrink-0">
						<?php echo get_avatar( $user_id, 32, '', '', [ 'class' => 'rounded-full' ] ); ?>
					</div>
				</div>
			</div>

			<!-- Message Input -->
			<div class="border-t border-border bg-card p-4">
				<div class="flex items-center gap-2">
					<button class="btn btn-ghost btn-sm">
						<i class="ri-attachment-line"></i>
					</button>
					<input 
						type="text" 
						placeholder="Digite uma mensagem..." 
						class="input flex-1"
						id="messageInput"
					>
					<button class="btn btn-primary btn-sm">
						<i class="ri-send-plane-line"></i>
					</button>
				</div>
			</div>
		<?php else : ?>
			<!-- Empty State -->
			<div class="flex-1 flex items-center justify-center">
				<div class="text-center">
					<i class="ri-message-3-line text-6xl text-muted-foreground mb-4"></i>
					<h3 class="text-xl font-semibold text-foreground mb-2">Nenhuma conversa selecionada</h3>
					<p class="text-muted-foreground">Selecione uma conversa da lista para começar</p>
				</div>
			</div>
		<?php endif; ?>
	</main>
</div>

<script>
// Buscar conversas ao digitar
document.getElementById('searchConversations')?.addEventListener('input', function(e) {
	const search = e.target.value.toLowerCase();
	// TODO: Implementar busca de conversas
});

// Enviar mensagem
document.getElementById('messageInput')?.addEventListener('keypress', function(e) {
	if (e.key === 'Enter' && !e.shiftKey) {
		e.preventDefault();
		// TODO: Implementar envio de mensagem
		console.log('Enviar mensagem:', this.value);
		this.value = '';
	}
});
</script>

<?php get_footer(); ?>
