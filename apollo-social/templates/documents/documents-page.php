<?php
/**
 * Documents Page - ShadCN Sidebar-14
 * https://ui.shadcn.com/view/new-york-v4/sidebar-14
 *
 * Lista de documentos com sidebar de navegação
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

// Buscar documentos
$all_documents = function_exists( 'Apollo\CenaRio\CenaRioModule::getLibraryDocuments' )
	? Apollo\CenaRio\CenaRioModule::getLibraryDocuments( 50 )
	: [];

$my_documents = function_exists( 'Apollo\CenaRio\CenaRioModule::getUserDocuments' )
	? Apollo\CenaRio\CenaRioModule::getUserDocuments( $user_id )
	: [];

$signed_documents = [];
// TODO: Implementar busca de documentos assinados

$current_filter = isset( $_GET['filter'] ) ? sanitize_text_field( $_GET['filter'] ) : 'all';

get_header();
?>

<div class="flex h-screen w-full overflow-hidden bg-background">
	<!-- Sidebar -->
	<aside class="sidebar border-r border-border bg-card w-64 flex-shrink-0 flex flex-col">
		<!-- Sidebar Header -->
		<div class="sidebar-header flex items-center justify-between p-4 border-b border-border">
			<div class="flex items-center gap-2">
				<i class="ri-file-text-line text-xl text-primary"></i>
				<h2 class="text-lg font-semibold text-foreground">Documentos</h2>
			</div>
		</div>

		<!-- Sidebar Navigation -->
		<nav class="sidebar-content flex-1 overflow-y-auto p-4 space-y-1">
			<a href="<?php echo esc_url( add_query_arg( 'filter', 'all' ) ); ?>"
				class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors <?php echo $current_filter === 'all' ? 'bg-accent text-accent-foreground' : 'text-foreground hover:bg-accent hover:text-accent-foreground'; ?>">
				<i class="ri-folder-line"></i>
				<span>Todos os Documentos</span>
				<span class="ml-auto badge badge-default"><?php echo count( $all_documents ); ?></span>
			</a>

			<a href="<?php echo esc_url( add_query_arg( 'filter', 'my' ) ); ?>"
				class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors <?php echo $current_filter === 'my' ? 'bg-accent text-accent-foreground' : 'text-foreground hover:bg-accent hover:text-accent-foreground'; ?>">
				<i class="ri-file-user-line"></i>
				<span>Meus Documentos</span>
				<span class="ml-auto badge badge-default"><?php echo count( $my_documents ); ?></span>
			</a>

			<a href="<?php echo esc_url( add_query_arg( 'filter', 'signed' ) ); ?>"
				class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors <?php echo $current_filter === 'signed' ? 'bg-accent text-accent-foreground' : 'text-foreground hover:bg-accent hover:text-accent-foreground'; ?>">
				<i class="ri-file-check-line"></i>
				<span>Assinados</span>
				<?php if ( ! empty( $signed_documents ) ) : ?>
					<span class="ml-auto badge badge-primary"><?php echo count( $signed_documents ); ?></span>
				<?php endif; ?>
			</a>

			<div class="separator my-4"></div>

			<a href="<?php echo esc_url( home_url( '/doc/new' ) ); ?>"
				class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
				<i class="ri-add-line"></i>
				<span>Novo Documento</span>
			</a>
		</nav>

		<!-- Sidebar Footer -->
		<div class="sidebar-footer p-4 border-t border-border">
			<div class="text-xs text-muted-foreground">
				<p class="font-medium mb-1">Apollo::Rio</p>
				<p>Sistema de Documentos</p>
			</div>
		</div>
	</aside>

	<!-- Main Content -->
	<main class="flex-1 flex flex-col overflow-hidden">
		<!-- Top Bar -->
		<header class="border-b border-border bg-card px-6 py-4">
			<div class="flex items-center justify-between">
				<div>
					<h1 class="text-2xl font-bold text-foreground">
						<?php
						switch ( $current_filter ) {
							case 'my':
								echo 'Meus Documentos';
								break;
							case 'signed':
								echo 'Documentos Assinados';
								break;
							default:
								echo 'Todos os Documentos';
						}
						?>
					</h1>
					<p class="text-sm text-muted-foreground mt-1">
						<?php
						$documents_to_show = $current_filter === 'my' ? $my_documents : ( $current_filter === 'signed' ? $signed_documents : $all_documents );
						echo count( $documents_to_show ) . ' documento' . ( count( $documents_to_show ) !== 1 ? 's' : '' ) . ' encontrado' . ( count( $documents_to_show ) !== 1 ? 's' : '' );
						?>
					</p>
				</div>
				<div class="flex items-center gap-2">
					<div class="relative">
						<i class="ri-search-line absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground"></i>
						<input
							type="text"
							placeholder="Buscar documentos..."
							class="input pl-10 w-64"
							id="searchDocuments"
						>
					</div>
					<button class="btn btn-secondary">
						<i class="ri-filter-line mr-2"></i>
						Filtros
					</button>
				</div>
			</div>
		</header>

		<!-- Content Area -->
		<div class="flex-1 overflow-y-auto p-6">
			<?php if ( empty( $documents_to_show ) ) : ?>
				<div class="card p-12 text-center">
					<i class="ri-file-text-line text-6xl text-muted-foreground mb-4"></i>
					<h3 class="text-xl font-semibold text-foreground mb-2">
						Nenhum documento encontrado
					</h3>
					<p class="text-muted-foreground mb-6">
						<?php if ( $current_filter === 'my' ) : ?>
							Você ainda não criou nenhum documento
						<?php elseif ( $current_filter === 'signed' ) : ?>
							Nenhum documento assinado encontrado
						<?php else : ?>
							Não há documentos disponíveis
						<?php endif; ?>
					</p>
					<?php if ( $current_filter !== 'signed' ) : ?>
						<a href="<?php echo esc_url( home_url( '/doc/new' ) ); ?>" class="btn btn-primary">
							<i class="ri-add-line mr-2"></i>
							Criar Documento
						</a>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
					<?php
					foreach ( $documents_to_show as $doc ) :
						$is_signed = in_array( $doc->ID, array_column( $signed_documents, 'ID' ) );
						$is_mine   = $doc->post_author == $user_id;
						?>
						<div class="card hover:shadow-lg transition-shadow cursor-pointer group">
							<a href="<?php echo esc_url( get_permalink( $doc->ID ) ); ?>" class="block">
								<div class="card-header">
									<div class="flex items-start justify-between mb-2">
										<div class="flex-1">
											<h3 class="card-title text-lg group-hover:text-primary transition-colors">
												<?php echo esc_html( $doc->post_title ); ?>
											</h3>
										</div>
										<div class="flex items-center gap-2">
											<?php if ( $is_signed ) : ?>
												<i class="ri-file-check-line text-xl text-green-600" data-ap-tooltip="<?php esc_attr_e( 'Documento assinado', 'apollo-social' ); ?>"></i>
											<?php endif; ?>
											<?php if ( $is_mine ) : ?>
												<i class="ri-user-line text-xl text-muted-foreground" data-ap-tooltip="<?php esc_attr_e( 'Meu documento', 'apollo-social' ); ?>"></i>
											<?php endif; ?>
											<i class="ri-file-text-line text-2xl text-muted-foreground" data-ap-tooltip="<?php esc_attr_e( 'Documento', 'apollo-social' ); ?>"></i>
										</div>
									</div>
									<p class="card-description line-clamp-2">
										<?php echo esc_html( wp_trim_words( $doc->post_content, 20 ) ); ?>
									</p>
								</div>
								<div class="card-content">
									<div class="flex items-center justify-between text-xs text-muted-foreground">
										<div class="flex items-center gap-2" data-ap-tooltip="<?php esc_attr_e( 'Data de criação', 'apollo-social' ); ?>">
											<i class="ri-calendar-line"></i>
											<span><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $doc->post_date ) ) ); ?></span>
										</div>
										<div class="flex items-center gap-2" data-ap-tooltip="<?php esc_attr_e( 'Autor do documento', 'apollo-social' ); ?>">
											<i class="ri-user-line"></i>
											<span><?php echo esc_html( get_the_author_meta( 'display_name', $doc->post_author ) ); ?></span>
										</div>
									</div>
								</div>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</main>
</div>

<script>
// Buscar documentos
document.getElementById('searchDocuments')?.addEventListener('input', function(e) {
	const search = e.target.value.toLowerCase();
	const cards = document.querySelectorAll('.card');

	cards.forEach(card => {
		const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
		const description = card.querySelector('.card-description')?.textContent.toLowerCase() || '';

		if (title.includes(search) || description.includes(search)) {
			card.style.display = '';
		} else {
			card.style.display = 'none';
		}
	});
});
</script>

<?php get_footer(); ?>
