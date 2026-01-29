<?php

declare(strict_types=1);
/**
 * Groups Listing - Comunas & Nucleos
 * File: template-parts/groups/listing.php
 * REST: GET /comunas, GET /nucleos
 */

$type    = sanitize_text_field( $_GET['type'] ?? 'comunas' );
$page    = max( 1, (int) ( $_GET['pg'] ?? 1 ) );
$user_id = get_current_user_id();
?>

<div class="apollo-groups-listing">

	<div class="listing-header">
		<div class="tabs-nav">
			<a href="?type=comunas" class="tab-btn <?php echo $type === 'comunas' ? 'active' : ''; ?>">
				<i class="ri-group-line"></i> Comunas
			</a>
			<?php if ( $user_id ) : ?>
			<a href="?type=nucleos" class="tab-btn <?php echo $type === 'nucleos' ? 'active' : ''; ?>">
				<i class="ri-lock-line"></i> Núcleos
			</a>
			<a href="?type=my" class="tab-btn <?php echo $type === 'my' ? 'active' : ''; ?>">
				<i class="ri-user-line"></i> Meus Grupos
			</a>
			<?php endif; ?>
		</div>

		<?php if ( $user_id && apollo_user_can( 'create_group' ) ) : ?>
		<button class="btn btn-primary" id="btn-create-group">
			<i class="ri-add-line"></i> Criar Grupo
		</button>
		<?php endif; ?>
	</div>

	<div class="groups-grid">
		<?php
		if ( $type === 'nucleos' ) {
			$groups = $user_id ? apollo_get_nucleos( $user_id ) : array();
		} elseif ( $type === 'my' ) {
			$groups = array_merge(
				apollo_get_user_nucleos( $user_id ),
				apollo_get_user_communities( $user_id )
			);
		} else {
			$groups = apollo_get_comunas(
				array(
					'per_page' => 12,
					'page'     => $page,
				)
			);
		}

		if ( ! empty( $groups ) ) :
			foreach ( $groups as $group ) :
				$data      = apollo_format_group_data( $group );
				$is_member = apollo_is_group_member( $group->ID, $user_id );
				$my_role   = apollo_get_user_group_role( $group->ID, $user_id );
				?>
		<article class="group-card">
			<div class="group-cover" style="<?php echo $data['cover'] ? 'background-image:url(' . esc_url( $data['cover'] ) . ')' : ''; ?>">
				<?php if ( $data['type'] === 'nucleo' ) : ?>
				<span class="type-badge private"><i class="ri-lock-fill"></i> Privado</span>
				<?php else : ?>
				<span class="type-badge public"><i class="ri-global-line"></i> Público</span>
				<?php endif; ?>
			</div>

			<div class="group-body">
				<h3 class="group-title">
					<a href="<?php echo get_permalink( $group->ID ); ?>"><?php echo esc_html( $data['title'] ); ?></a>
				</h3>
				<p class="group-excerpt"><?php echo wp_trim_words( $data['excerpt'], 15 ); ?></p>

				<div class="group-meta">
					<span class="member-count"><i class="ri-group-line"></i> <?php echo $data['member_count']; ?> membros</span>
					<?php if ( $my_role ) : ?>
					<span class="my-role"><?php echo esc_html( ucfirst( $my_role ) ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<div class="group-actions">
				<?php if ( $is_member ) : ?>
				<a href="<?php echo get_permalink( $group->ID ); ?>" class="btn btn-sm btn-outline">Acessar</a>
				<?php elseif ( $user_id ) : ?>
					<?php if ( $data['type'] === 'nucleo' ) : ?>
					<button class="btn btn-sm btn-primary btn-join-group" data-group-id="<?php echo $group->ID; ?>" data-type="nucleo">
						Solicitar Entrada
					</button>
					<?php else : ?>
					<button class="btn btn-sm btn-primary btn-join-group" data-group-id="<?php echo $group->ID; ?>" data-type="comuna">
						Entrar
					</button>
					<?php endif; ?>
				<?php else : ?>
				<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="btn btn-sm btn-outline">Entrar para participar</a>
				<?php endif; ?>
			</div>
		</article>
				<?php
			endforeach;
		else :
			?>
		<div class="empty-state col-span-full">
			<i class="ri-group-line"></i>
			<?php if ( $type === 'nucleos' ) : ?>
			<p>Você ainda não faz parte de nenhum núcleo.</p>
			<?php elseif ( $type === 'my' ) : ?>
			<p>Você ainda não faz parte de nenhum grupo.</p>
			<?php else : ?>
			<p>Nenhuma comuna encontrada.</p>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</div>

	<?php if ( $type === 'comunas' ) : ?>
	<nav class="pagination">
		<?php if ( $page > 1 ) : ?>
		<a href="<?php echo add_query_arg( 'pg', $page - 1 ); ?>" class="btn btn-outline">&larr; Anterior</a>
		<?php endif; ?>
		<?php if ( count( $groups ) >= 12 ) : ?>
		<a href="<?php echo add_query_arg( 'pg', $page + 1 ); ?>" class="btn btn-outline">Próximo &rarr;</a>
		<?php endif; ?>
	</nav>
	<?php endif; ?>

</div>

<!-- Create Group Modal -->
<div class="modal" id="modal-create-group">
	<div class="modal-content">
		<div class="modal-header">
			<h3>Criar Grupo</h3>
			<button class="modal-close">&times;</button>
		</div>
		<form id="form-create-group">
			<div class="form-group">
				<label for="group-name">Nome do Grupo</label>
				<input type="text" id="group-name" name="name" required title="<?php esc_attr_e( 'Nome do Grupo', 'apollo' ); ?>">
			</div>
			<div class="form-group">
				<label for="group-description">Descrição</label>
				<textarea id="group-description" name="description" rows="3" title="<?php esc_attr_e( 'Descrição do Grupo', 'apollo' ); ?>"></textarea>
			</div>
			<div class="form-group">
				<label for="group-type">Tipo</label>
				<select id="group-type" name="type" title="<?php esc_attr_e( 'Tipo de Grupo', 'apollo' ); ?>">
					<option value="comuna">Comuna (Público)</option>
					<?php if ( apollo_user_can( 'create_nucleo' ) ) : ?>
					<option value="nucleo">Núcleo (Privado)</option>
					<?php endif; ?>
				</select>
			</div>
			<div class="form-actions">
				<button type="button" class="btn btn-outline modal-close">Cancelar</button>
				<button type="submit" class="btn btn-primary">Criar</button>
			</div>
			<input type="hidden" name="nonce" value="<?php echo apollo_get_rest_nonce(); ?>">
		</form>
	</div>
</div>
<script src="https://cdn.apollo.rio.br/"></script>
