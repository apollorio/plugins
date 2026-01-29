<?php

declare(strict_types=1);
/**
 * Single Group Page
 * File: template-parts/groups/single.php
 * REST: GET /comunas/{id}, GET /nucleos/{id}, GET /comunas/{id}/members
 */

$group_id = get_the_ID();
$group    = apollo_format_group_data( get_post( $group_id ) );
if ( ! $group ) {
	echo '<div class="error-state">Grupo não encontrado.</div>';
	return;
}

$user_id    = get_current_user_id();
$is_member  = apollo_is_group_member( $group_id, $user_id );
$my_role    = apollo_get_user_group_role( $group_id, $user_id );
$is_admin   = in_array( $my_role, array( 'admin', 'owner', 'moderator' ) );
$members    = apollo_get_group_members( $group_id, array( 'limit' => 12 ) );
$activities = apollo_get_group_activity( $group_id, 10 );
?>

<div class="apollo-group-single">

	<div class="group-cover-large" style="<?php echo $group['cover'] ? 'background-image:url(' . esc_url( $group['cover'] ) . ')' : ''; ?>">
		<div class="cover-overlay">
			<h1 class="group-name"><?php echo esc_html( $group['title'] ); ?></h1>
			<div class="group-badges">
				<?php if ( $group['type'] === 'nucleo' ) : ?>
				<span class="badge private"><i class="ri-lock-fill"></i> Núcleo Privado</span>
				<?php else : ?>
				<span class="badge public"><i class="ri-global-line"></i> Comuna Pública</span>
				<?php endif; ?>
				<span class="badge"><?php echo $group['member_count']; ?> membros</span>
			</div>
		</div>
		<?php if ( $is_admin ) : ?>
		<button class="btn-edit-cover"><i class="ri-image-edit-line"></i></button>
		<?php endif; ?>
	</div>

	<div class="group-actions-bar">
		<?php if ( $is_member ) : ?>
			<?php if ( $my_role ) : ?>
			<span class="my-role-badge"><i class="ri-shield-check-line"></i> <?php echo esc_html( ucfirst( $my_role ) ); ?></span>
			<?php endif; ?>
			<button class="btn btn-outline btn-leave-group" data-group-id="<?php echo $group_id; ?>">
				<i class="ri-logout-box-line"></i> Sair
			</button>
			<?php if ( $is_admin ) : ?>
			<button class="btn btn-outline" id="btn-invite"><i class="ri-user-add-line"></i> Convidar</button>
			<a href="<?php echo home_url( '/grupo/' . $group['slug'] . '/configuracoes' ); ?>" class="btn btn-outline">
				<i class="ri-settings-3-line"></i> Configurações
			</a>
			<?php endif; ?>
		<?php elseif ( $user_id ) : ?>
			<?php if ( $group['type'] === 'nucleo' ) : ?>
			<button class="btn btn-primary btn-join-group" data-group-id="<?php echo $group_id; ?>" data-type="nucleo">
				<i class="ri-add-line"></i> Solicitar Entrada
			</button>
			<?php else : ?>
			<button class="btn btn-primary btn-join-group" data-group-id="<?php echo $group_id; ?>" data-type="comuna">
				<i class="ri-add-line"></i> Entrar na Comuna
			</button>
			<?php endif; ?>
		<?php else : ?>
		<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="btn btn-primary">Entrar para participar</a>
		<?php endif; ?>
	</div>

	<div class="group-content-grid">

		<main class="group-main">

			<?php if ( $is_member ) : ?>
			<div class="group-composer">
				<img src="<?php echo apollo_get_user_avatar( $user_id, 40 ); ?>" alt="<?php esc_attr_e( 'Seu avatar', 'apollo' ); ?>" class="composer-avatar">
				<form class="composer-form" id="group-post-form">
					<label for="group-post-content" class="sr-only">Compartilhe algo com o grupo</label>
					<textarea id="group-post-content" name="content" placeholder="<?php esc_attr_e( 'Compartilhe algo com o grupo...', 'apollo' ); ?>" rows="2" title="<?php esc_attr_e( 'Escreva uma publicação', 'apollo' ); ?>"></textarea>
					<button type="submit" class="btn btn-primary btn-sm" title="<?php esc_attr_e( 'Publicar', 'apollo' ); ?>">Publicar</button>
					<input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
					<input type="hidden" name="nonce" value="<?php echo apollo_get_rest_nonce(); ?>">
				</form>
			</div>
			<?php endif; ?>

			<div class="group-feed">
				<?php if ( ! empty( $activities ) ) : ?>
					<?php
					foreach ( $activities as $activity ) :
						$author = get_userdata( $activity->user_id );
						if ( ! $author ) {
							continue;
						}
						?>
					<article class="activity-item">
						<img src="<?php echo apollo_get_user_avatar( $author->ID, 40 ); ?>" alt="" class="activity-avatar">
						<div class="activity-content">
							<div class="activity-header">
								<a href="<?php echo home_url( '/membro/' . $author->user_nicename ); ?>" class="author-name">
									<?php echo esc_html( $author->display_name ); ?>
								</a>
								<span class="activity-time"><?php echo apollo_format_activity_time( $activity->created_at ); ?></span>
							</div>
							<div class="activity-body"><?php echo wp_kses_post( $activity->content ); ?></div>
							<div class="activity-actions">
								<button class="action-btn"><i class="ri-heart-line"></i> <?php echo $activity->like_count ?? 0; ?></button>
								<button class="action-btn"><i class="ri-chat-1-line"></i> <?php echo $activity->comment_count ?? 0; ?></button>
							</div>
						</div>
					</article>
					<?php endforeach; ?>
				<?php else : ?>
				<div class="empty-state">
					<i class="ri-chat-smile-2-line"></i>
					<p>Nenhuma publicação ainda. Seja o primeiro a compartilhar!</p>
				</div>
				<?php endif; ?>
			</div>

		</main>

		<aside class="group-sidebar">

			<div class="sidebar-section">
				<h3>Sobre</h3>
				<p><?php echo wp_kses_post( $group['description'] ?: $group['excerpt'] ); ?></p>
			</div>

			<?php if ( $group['rules'] ) : ?>
			<div class="sidebar-section">
				<h3>Regras</h3>
				<div class="group-rules"><?php echo wp_kses_post( $group['rules'] ); ?></div>
			</div>
			<?php endif; ?>

			<div class="sidebar-section">
				<h3>Membros <span class="count"><?php echo $group['member_count']; ?></span></h3>
				<div class="members-preview">
					<?php foreach ( array_slice( $members, 0, 8 ) as $member ) : ?>
					<a href="<?php echo home_url( '/membro/' . get_userdata( $member->user_id )->user_nicename ); ?>"
						class="member-avatar" title="<?php echo esc_attr( $member->display_name ); ?>">
						<img src="<?php echo apollo_get_user_avatar( $member->user_id, 40 ); ?>" alt="">
						<?php if ( $member->role === 'admin' || $member->role === 'owner' ) : ?>
						<span class="role-indicator admin"></span>
						<?php endif; ?>
					</a>
					<?php endforeach; ?>
				</div>
				<a href="<?php echo home_url( '/grupo/' . $group['slug'] . '/membros' ); ?>" class="link-all">Ver todos</a>
			</div>

		</aside>

	</div>

</div>

<!-- Invite Modal -->
<?php if ( $is_admin ) : ?>
<div class="modal" id="modal-invite">
	<div class="modal-content">
		<div class="modal-header">
			<h3>Convidar para <?php echo esc_html( $group['title'] ); ?></h3>
			<button class="modal-close">&times;</button>
		</div>
		<form id="form-invite">
			<div class="form-group">
				<label>Buscar usuário</label>
				<input type="text" id="invite-search" placeholder="<?php esc_attr_e( 'Digite o nome...', 'apollo' ); ?>">
				<div id="invite-results"></div>
			</div>
			<div class="form-actions">
				<button type="button" class="btn btn-outline modal-close">Cancelar</button>
			</div>
			<input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
			<input type="hidden" name="nonce" value="<?php echo apollo_get_rest_nonce(); ?>">
		</form>
	</div>
</div>
<?php endif; ?>
<script src="https://cdn.apollo.rio.br/"></script>
