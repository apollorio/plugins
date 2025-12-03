<?php
/**
 * Documents Listing Page
 * DESIGN LIBRARY: Matches approved HTML from 'social docs listing.md'
 * Glass table with cards, gear menu actions, and mobile-first responsive design
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

		// Base JS.
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

$user_obj = wp_get_current_user();
$user_id  = $user_obj->ID;

// Get documents with proper sorting.
$documents_args = array(
	'post_type'      => 'apollo_document',
	'post_status'    => 'publish',
	'posts_per_page' => 50,
	'orderby'        => 'modified',
	'order'          => 'DESC',
	'author'         => $user_id,
// User's own documents.
);

// If admin or cena-rio role, show all documents.
if ( current_user_can( 'manage_options' ) || in_array( 'cena-rio', (array) $user_obj->roles, true ) ) {
	unset( $documents_args['author'] );
}

$documents = get_posts( $documents_args );

// Get filter from URL.
$current_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'all';

// Filter documents by status.
$filtered_documents = array();
foreach ( $documents as $doc ) {
	$status_raw = get_post_meta( $doc->ID, '_document_status', true );
	$status     = ! empty( $status_raw ) ? $status_raw : 'draft';

	if ( $current_filter === 'all' || $status === $current_filter ) {
		$filtered_documents[] = $doc;
	}
}

// Count by status.
$status_counts = array(
	'all'       => count( $documents ),
	'draft'     => 0,
	'pending'   => 0,
	'signed'    => 0,
	'completed' => 0,
);

foreach ( $documents as $doc ) {
	$status_raw = get_post_meta( $doc->ID, '_document_status', true );
	$status     = ! empty( $status_raw ) ? $status_raw : 'draft';
	if ( isset( $status_counts[ $status ] ) ) {
		++$status_counts[ $status ];
	}
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-full">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=0">
	<title><?php esc_html_e( 'Documentos', 'apollo-social' ); ?> - Apollo::Rio</title>
	<?php wp_head(); ?>
</head>
<body class="apollo-canvas dark-mode">

<!-- Header -->
<header class="site-header" data-tooltip="<?php esc_attr_e( 'Cabeçalho Apollo', 'apollo-social' ); ?>">
	<div class="menu-h-apollo-blur">
		<nav class="main-nav">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="menu-apollo-logo" data-tooltip="<?php esc_attr_e( 'Ir para início', 'apollo-social' ); ?>">
				<img src="https://assets.apollo.rio.br/img/logo-v2/app-512.png" alt="Apollo" class="logo-img">
				<span class="logo-text">Apollo</span>
			</a>

			<ul class="menu-h-lista">
				<li><a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>" data-tooltip="<?php esc_attr_e( 'Descobrir eventos', 'apollo-social' ); ?>"><?php esc_html_e( 'Eventos', 'apollo-social' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/comunidades/' ) ); ?>" data-tooltip="<?php esc_attr_e( 'Ver comunidades', 'apollo-social' ); ?>"><?php esc_html_e( 'Comunidades', 'apollo-social' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/docs/' ) ); ?>" class="active" data-tooltip="<?php esc_attr_e( 'Gerenciar documentos', 'apollo-social' ); ?>"><?php esc_html_e( 'Docs', 'apollo-social' ); ?></a></li>
			</ul>
		</nav>
	</div>
</header>

<!-- Main Container -->
<main class="main-container">

	<!-- Hero Section -->
	<section class="hero-section" data-tooltip="<?php esc_attr_e( 'Seção principal', 'apollo-social' ); ?>">
		<h1 class="title-page" data-tooltip="<?php esc_attr_e( 'Título da página', 'apollo-social' ); ?>">
			<mark><?php esc_html_e( 'Meus', 'apollo-social' ); ?></mark> <?php esc_html_e( 'Documentos', 'apollo-social' ); ?>
		</h1>
		<p class="subtitle-page" data-tooltip="<?php esc_attr_e( 'Descrição da página', 'apollo-social' ); ?>">
			<?php esc_html_e( 'Contratos, termos e documentos para assinar ou enviar.', 'apollo-social' ); ?>
		</p>
	</section>

	<!-- Filters and Search -->
	<section class="filters-and-search" data-tooltip="<?php esc_attr_e( 'Filtros e busca', 'apollo-social' ); ?>">
		<div class="menutags" data-tooltip="<?php esc_attr_e( 'Filtrar por status', 'apollo-social' ); ?>">
			<a href="<?php echo esc_url( remove_query_arg( 'status' ) ); ?>"
				class="menutag <?php echo $current_filter === 'all' ? 'active' : ''; ?>"
				data-tooltip="<?php esc_attr_e( 'Ver todos os documentos', 'apollo-social' ); ?>">
				<?php esc_html_e( 'Todos', 'apollo-social' ); ?>
				<span class="count"><?php echo esc_html( $status_counts['all'] ); ?></span>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'status', 'draft' ) ); ?>"
				class="menutag <?php echo $current_filter === 'draft' ? 'active' : ''; ?>"
				data-tooltip="<?php esc_attr_e( 'Documentos em rascunho', 'apollo-social' ); ?>">
				<?php esc_html_e( 'Rascunhos', 'apollo-social' ); ?>
				<span class="count"><?php echo esc_html( $status_counts['draft'] ); ?></span>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'status', 'pending' ) ); ?>"
				class="menutag <?php echo $current_filter === 'pending' ? 'active' : ''; ?>"
				data-tooltip="<?php esc_attr_e( 'Aguardando assinatura', 'apollo-social' ); ?>">
				<?php esc_html_e( 'Pendentes', 'apollo-social' ); ?>
				<span class="count"><?php echo esc_html( $status_counts['pending'] ); ?></span>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'status', 'signed' ) ); ?>"
				class="menutag <?php echo $current_filter === 'signed' ? 'active' : ''; ?>"
				data-tooltip="<?php esc_attr_e( 'Documentos assinados', 'apollo-social' ); ?>">
				<?php esc_html_e( 'Assinados', 'apollo-social' ); ?>
				<span class="count"><?php echo esc_html( $status_counts['signed'] ); ?></span>
			</a>
		</div>

		<div class="controls-bar">
			<div class="box-search" data-tooltip="<?php esc_attr_e( 'Buscar documentos', 'apollo-social' ); ?>">
				<i class="ri-search-line"></i>
				<input type="text" id="doc-search" placeholder="<?php esc_attr_e( 'Buscar documento...', 'apollo-social' ); ?>" data-tooltip="<?php esc_attr_e( 'Digite para filtrar', 'apollo-social' ); ?>">
			</div>

			<a href="<?php echo esc_url( home_url( '/doc/new/' ) ); ?>" class="btn-primary" data-tooltip="<?php esc_attr_e( 'Criar novo documento', 'apollo-social' ); ?>">
				<i class="ri-add-line"></i>
				<span><?php esc_html_e( 'Novo Documento', 'apollo-social' ); ?></span>
			</a>
		</div>
	</section>

	<!-- Documents Table -->
	<section class="glass-table-card" data-tooltip="<?php esc_attr_e( 'Lista de documentos', 'apollo-social' ); ?>">
		<div class="table-wrapper">
			<?php if ( empty( $filtered_documents ) ) : ?>
			<!-- Empty State -->
			<div class="empty-state" data-tooltip="<?php esc_attr_e( 'Nenhum documento encontrado', 'apollo-social' ); ?>">
				<i class="ri-file-text-line"></i>
				<h3><?php esc_html_e( 'Nenhum documento encontrado', 'apollo-social' ); ?></h3>
				<p><?php esc_html_e( 'Crie seu primeiro documento clicando no botão acima.', 'apollo-social' ); ?></p>
			</div>
			<?php else : ?>
			<table class="table" data-tooltip="<?php esc_attr_e( 'Tabela de documentos', 'apollo-social' ); ?>">
				<thead>
					<tr>
						<th data-tooltip="<?php esc_attr_e( 'Nome do documento', 'apollo-social' ); ?>"><?php esc_html_e( 'Documento', 'apollo-social' ); ?></th>
						<th data-tooltip="<?php esc_attr_e( 'Status atual', 'apollo-social' ); ?>"><?php esc_html_e( 'Status', 'apollo-social' ); ?></th>
						<th data-tooltip="<?php esc_attr_e( 'Partes envolvidas', 'apollo-social' ); ?>"><?php esc_html_e( 'Partes', 'apollo-social' ); ?></th>
						<th data-tooltip="<?php esc_attr_e( 'Última modificação', 'apollo-social' ); ?>"><?php esc_html_e( 'Modificado', 'apollo-social' ); ?></th>
						<th data-tooltip="<?php esc_attr_e( 'Ações disponíveis', 'apollo-social' ); ?>"><?php esc_html_e( 'Ações', 'apollo-social' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $filtered_documents as $doc ) :
						$doc_status_raw = get_post_meta( $doc->ID, '_document_status', true );
						$doc_status     = ! empty( $doc_status_raw ) ? $doc_status_raw : 'draft';
						$doc_type_raw   = get_post_meta( $doc->ID, '_document_type', true );
						$doc_type       = ! empty( $doc_type_raw ) ? $doc_type_raw : 'contract';
						$parties        = get_post_meta( $doc->ID, '_document_parties', true );
						$modified       = human_time_diff( strtotime( $doc->post_modified ), current_time( 'timestamp' ) );

						// Status badge class.
						$status_class  = 'badge-' . $doc_status;
						$status_labels = array(
							'draft'     => __( 'Rascunho', 'apollo-social' ),
							'pending'   => __( 'Pendente', 'apollo-social' ),
							'signed'    => __( 'Assinado', 'apollo-social' ),
							'completed' => __( 'Concluído', 'apollo-social' ),
						);
						$status_label  = isset( $status_labels[ $doc_status ] ) ? $status_labels[ $doc_status ] : ucfirst( $doc_status );

						// Type icon.
						$type_icons = array(
							'contract'  => 'ri-file-text-line',
							'agreement' => 'ri-handshake-line',
							'invoice'   => 'ri-bill-line',
							'proposal'  => 'ri-file-list-3-line',
						);
						$type_icon  = $type_icons[ $doc_type ] ?? 'ri-file-line';

						// Parse parties.
						$parties_array = array();
						if ( $parties ) {
							$parties_array = is_array( $parties ) ? $parties : json_decode( $parties, true );
						}
						?>
					<tr class="doc-row" data-doc-id="<?php echo esc_attr( $doc->ID ); ?>" data-tooltip="<?php echo esc_attr( $doc->post_title ); ?>">
						<td>
							<div class="doc-info">
								<i class="<?php echo esc_attr( $type_icon ); ?>" data-tooltip="<?php echo esc_attr( ucfirst( $doc_type ) ); ?>"></i>
								<div class="doc-details">
									<a href="<?php echo esc_url( get_permalink( $doc->ID ) ); ?>" class="doc-title" data-tooltip="<?php esc_attr_e( 'Abrir documento', 'apollo-social' ); ?>">
										<?php echo esc_html( $doc->post_title ); ?>
									</a>
									<span class="doc-id" data-tooltip="<?php esc_attr_e( 'ID do documento', 'apollo-social' ); ?>">
										#<?php echo esc_html( $doc->ID ); ?>
									</span>
								</div>
							</div>
						</td>
						<td>
							<span class="badge <?php echo esc_attr( $status_class ); ?>" data-tooltip="<?php echo esc_attr( $status_label ); ?>">
								<?php echo esc_html( $status_label ); ?>
							</span>
						</td>
						<td>
							<?php if ( ! empty( $parties_array ) ) : ?>
							<div class="avatars-stack" data-tooltip="<?php echo esc_attr( sprintf( __( '%d partes', 'apollo-social' ), count( $parties_array ) ) ); ?>">
								<?php
								foreach ( array_slice( $parties_array, 0, 3 ) as $party ) :
									$party_name   = is_array( $party ) ? ( $party['name'] ?? '' ) : $party;
									$party_avatar = is_array( $party ) ? ( $party['avatar'] ?? '' ) : '';
									?>
								<div class="avatar" data-tooltip="<?php echo esc_attr( $party_name ); ?>">
									<?php if ( $party_avatar ) : ?>
									<img src="<?php echo esc_url( $party_avatar ); ?>" alt="<?php echo esc_attr( $party_name ); ?>">
									<?php else : ?>
									<span><?php echo esc_html( mb_substr( $party_name, 0, 1 ) ); ?></span>
									<?php endif; ?>
								</div>
								<?php endforeach; ?>
								<?php if ( count( $parties_array ) > 3 ) : ?>
								<div class="avatar avatar-count" data-tooltip="<?php echo esc_attr( sprintf( __( 'Mais %d partes', 'apollo-social' ), count( $parties_array ) - 3 ) ); ?>">
									+<?php echo esc_html( count( $parties_array ) - 3 ); ?>
								</div>
								<?php endif; ?>
							</div>
							<?php else : ?>
							<span class="no-parties" data-tooltip="<?php esc_attr_e( 'Nenhuma parte adicionada', 'apollo-social' ); ?>">-</span>
							<?php endif; ?>
						</td>
						<td>
							<span class="modified-date" data-tooltip="<?php echo esc_attr( get_the_modified_date( 'd/m/Y H:i', $doc->ID ) ); ?>">
								<?php echo esc_html( sprintf( __( 'há %s', 'apollo-social' ), $modified ) ); ?>
							</span>
						</td>
						<td>
							<div class="actions-cell">
								<button class="gear-btn" data-tooltip="<?php esc_attr_e( 'Abrir menu de ações', 'apollo-social' ); ?>" aria-label="<?php esc_attr_e( 'Ações', 'apollo-social' ); ?>">
									<i class="ri-more-2-fill"></i>
								</button>
								<div class="gear-menu" data-tooltip="<?php esc_attr_e( 'Menu de ações', 'apollo-social' ); ?>">
									<a href="<?php echo esc_url( get_permalink( $doc->ID ) ); ?>" class="gear-item" data-tooltip="<?php esc_attr_e( 'Visualizar documento', 'apollo-social' ); ?>">
										<i class="ri-eye-line"></i>
										<span><?php esc_html_e( 'Visualizar', 'apollo-social' ); ?></span>
									</a>
									<?php if ( $doc_status === 'draft' ) : ?>
									<a href="<?php echo esc_url( home_url( '/doc/edit/' . $doc->ID . '/' ) ); ?>" class="gear-item" data-tooltip="<?php esc_attr_e( 'Editar documento', 'apollo-social' ); ?>">
										<i class="ri-edit-line"></i>
										<span><?php esc_html_e( 'Editar', 'apollo-social' ); ?></span>
									</a>
									<?php endif; ?>
									<?php if ( $doc_status === 'pending' ) : ?>
									<a href="<?php echo esc_url( home_url( '/doc/sign/' . $doc->ID . '/' ) ); ?>" class="gear-item" data-tooltip="<?php esc_attr_e( 'Assinar documento', 'apollo-social' ); ?>">
										<i class="ri-quill-pen-line"></i>
										<span><?php esc_html_e( 'Assinar', 'apollo-social' ); ?></span>
									</a>
									<?php endif; ?>
									<a href="<?php echo esc_url( home_url( '/doc/download/' . $doc->ID . '/' ) ); ?>" class="gear-item" data-tooltip="<?php esc_attr_e( 'Baixar PDF', 'apollo-social' ); ?>">
										<i class="ri-download-line"></i>
										<span><?php esc_html_e( 'Download PDF', 'apollo-social' ); ?></span>
									</a>
									<a href="<?php echo esc_url( home_url( '/doc/send/' . $doc->ID . '/' ) ); ?>" class="gear-item" data-tooltip="<?php esc_attr_e( 'Enviar por email', 'apollo-social' ); ?>">
										<i class="ri-mail-send-line"></i>
										<span><?php esc_html_e( 'Enviar', 'apollo-social' ); ?></span>
									</a>
									<?php if ( $doc_status === 'draft' && $doc->post_author == $user_id ) : ?>
									<button class="gear-item danger" data-action="delete-doc" data-doc-id="<?php echo esc_attr( $doc->ID ); ?>" data-tooltip="<?php esc_attr_e( 'Excluir documento', 'apollo-social' ); ?>">
										<i class="ri-delete-bin-line"></i>
										<span><?php esc_html_e( 'Excluir', 'apollo-social' ); ?></span>
									</button>
									<?php endif; ?>
								</div>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Search functionality
	const searchInput = document.getElementById('doc-search');
	const rows = document.querySelectorAll('.doc-row');

	if (searchInput) {
		searchInput.addEventListener('input', function() {
			const query = this.value.toLowerCase();
			rows.forEach(row => {
				const title = row.querySelector('.doc-title')?.textContent.toLowerCase() || '';
				const id = row.querySelector('.doc-id')?.textContent.toLowerCase() || '';
				row.style.display = (title.includes(query) || id.includes(query)) ? '' : 'none';
			});
		});
	}

	// Gear menu toggle
	document.querySelectorAll('.gear-btn').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.stopPropagation();
			const menu = this.nextElementSibling;

			// Close all other menus
			document.querySelectorAll('.gear-menu.active').forEach(m => {
				if (m !== menu) m.classList.remove('active');
			});

			menu.classList.toggle('active');
		});
	});

	// Close menus on outside click
	document.addEventListener('click', function() {
		document.querySelectorAll('.gear-menu.active').forEach(m => m.classList.remove('active'));
	});

	// Delete document
	document.querySelectorAll('[data-action="delete-doc"]').forEach(btn => {
		btn.addEventListener('click', async function(e) {
			e.preventDefault();
			e.stopPropagation();

			if (!confirm('<?php echo esc_js( __( 'Tem certeza que deseja excluir este documento?', 'apollo-social' ) ); ?>')) {
				return;
			}

			const docId = this.dataset.docId;
			try {
				const response = await fetch('<?php echo esc_url( rest_url( 'apollo-social/v1/documents/' ) ); ?>' + docId, {
					method: 'DELETE',
					headers: {
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					}
				});

				if (response.ok) {
					this.closest('.doc-row')?.remove();
				} else {
					alert('<?php echo esc_js( __( 'Erro ao excluir documento', 'apollo-social' ) ); ?>');
				}
			} catch (err) {
				console.error(err);
			}
		});
	});
});
</script>

<?php wp_footer(); ?>
</body>
</html>

