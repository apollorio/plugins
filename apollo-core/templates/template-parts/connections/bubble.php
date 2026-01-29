<?php

declare(strict_types=1);
/**
 * Connections (Bolha System)
 * File: template-parts/connections/bubble.php
 * REST: GET /bolha/listar, GET /bolha/pedidos, POST /bolha/pedir, etc.
 */

if ( ! is_user_logged_in() ) {
	wp_redirect( home_url( '/login' ) );
	exit;
}

$user_id = get_current_user_id();
$tab     = sanitize_text_field( $_GET['tab'] ?? 'friends' );
$stats   = apollo_get_connection_stats( $user_id );
$pending = apollo_get_pending_friend_requests( $user_id );
?>

<div class="apollo-connections">

	<div class="connections-header">
		<h2>Conexões</h2>
		<div class="stats-row">
			<span><strong><?php echo $stats['friends']; ?></strong> amigos</span>
			<span><strong><?php echo $stats['followers']; ?></strong> seguidores</span>
			<span><strong><?php echo $stats['following']; ?></strong> seguindo</span>
		</div>
	</div>

	<?php if ( ! empty( $pending ) ) : ?>
	<div class="pending-requests">
		<h3><i class="ri-user-received-line"></i> Pedidos Pendentes (<?php echo count( $pending ); ?>)</h3>
		<div class="requests-list">
			<?php foreach ( $pending as $request ) : ?>
			<div class="request-item" data-id="<?php echo $request->id; ?>">
				<img src="<?php echo apollo_get_user_avatar( $request->user_id, 48 ); ?>" alt="">
				<div class="request-info">
					<a href="<?php echo home_url( '/membro/' . get_userdata( $request->user_id )->user_nicename ); ?>">
						<?php echo esc_html( $request->display_name ); ?>
					</a>
					<span class="request-time"><?php echo human_time_diff( strtotime( $request->created_at ) ); ?> atrás</span>
				</div>
				<div class="request-actions">
					<button class="btn btn-sm btn-primary btn-accept" data-user-id="<?php echo $request->user_id; ?>">Aceitar</button>
					<button class="btn btn-sm btn-outline btn-reject" data-user-id="<?php echo $request->user_id; ?>">Recusar</button>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<div class="tabs-nav">
		<a href="?tab=friends" class="tab-btn <?php echo $tab === 'friends' ? 'active' : ''; ?>">Amigos</a>
		<a href="?tab=bubble" class="tab-btn <?php echo $tab === 'bubble' ? 'active' : ''; ?>">Bolha</a>
		<a href="?tab=followers" class="tab-btn <?php echo $tab === 'followers' ? 'active' : ''; ?>">Seguidores</a>
		<a href="?tab=following" class="tab-btn <?php echo $tab === 'following' ? 'active' : ''; ?>">Seguindo</a>
	</div>

	<div class="connections-content">
		<?php if ( $tab === 'bubble' ) : ?>
			<?php $bubble = apollo_get_user_bubble( $user_id ); ?>
			<?php if ( ! empty( $bubble ) ) : ?>
			<div class="connections-grid">
				<?php foreach ( $bubble as $friend ) : ?>
				<div class="connection-card bubble-friend">
					<img src="<?php echo apollo_get_user_avatar( $friend->ID, 80 ); ?>" alt="">
					<a href="<?php echo home_url( '/membro/' . $friend->user_nicename ); ?>" class="name">
						<?php echo esc_html( $friend->display_name ); ?>
					</a>
					<span class="badge close-friend"><i class="ri-star-fill"></i> Amigo Próximo</span>
					<button class="btn btn-sm btn-outline btn-remove-bubble" data-user-id="<?php echo $friend->ID; ?>">
						<i class="ri-subtract-line"></i>
					</button>
				</div>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<div class="empty-state">
				<i class="ri-group-line"></i>
				<p>Sua bolha está vazia. Adicione amigos próximos!</p>
			</div>
			<?php endif; ?>

		<?php elseif ( $tab === 'followers' ) : ?>
			<?php $followers = apollo_get_followers( $user_id ); ?>
			<?php if ( ! empty( $followers ) ) : ?>
			<div class="connections-grid">
				<?php foreach ( $followers as $follower ) : ?>
				<div class="connection-card">
					<img src="<?php echo apollo_get_user_avatar( $follower->ID, 80 ); ?>" alt="">
					<a href="<?php echo home_url( '/membro/' . $follower->user_nicename ); ?>" class="name">
						<?php echo esc_html( $follower->display_name ); ?>
					</a>
					<?php if ( ! apollo_is_following( $follower->ID, $user_id ) ) : ?>
					<button class="btn btn-sm btn-primary btn-follow" data-user-id="<?php echo $follower->ID; ?>">
						Seguir de volta
					</button>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<div class="empty-state"><p>Nenhum seguidor ainda.</p></div>
			<?php endif; ?>

		<?php elseif ( $tab === 'following' ) : ?>
			<?php $following = apollo_get_following( $user_id ); ?>
			<?php if ( ! empty( $following ) ) : ?>
			<div class="connections-grid">
				<?php foreach ( $following as $user ) : ?>
				<div class="connection-card">
					<img src="<?php echo apollo_get_user_avatar( $user->ID, 80 ); ?>" alt="">
					<a href="<?php echo home_url( '/membro/' . $user->user_nicename ); ?>" class="name">
						<?php echo esc_html( $user->display_name ); ?>
					</a>
					<button class="btn btn-sm btn-outline btn-unfollow" data-user-id="<?php echo $user->ID; ?>">
						Seguindo
					</button>
				</div>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<div class="empty-state"><p>Você não está seguindo ninguém.</p></div>
			<?php endif; ?>

		<?php else : // friends ?>
			<?php $friends = apollo_get_user_friends( $user_id ); ?>
			<?php if ( ! empty( $friends ) ) : ?>
			<div class="connections-grid">
				<?php
				foreach ( $friends as $friend ) :
					$is_in_bubble = in_array( $friend, apollo_get_user_bubble( $user_id ) );
					?>
				<div class="connection-card">
					<img src="<?php echo apollo_get_user_avatar( $friend->ID, 80 ); ?>" alt="">
					<a href="<?php echo home_url( '/membro/' . $friend->user_nicename ); ?>" class="name">
						<?php echo esc_html( $friend->display_name ); ?>
					</a>
					<div class="card-actions">
						<a href="<?php echo home_url( '/mensagens?user=' . $friend->ID ); ?>" class="btn btn-sm btn-icon" title="Mensagem">
							<i class="ri-message-3-line"></i>
						</a>
						<button class="btn btn-sm btn-icon btn-add-bubble <?php echo $is_in_bubble ? 'active' : ''; ?>"
								data-user-id="<?php echo $friend->ID; ?>" title="<?php echo $is_in_bubble ? 'Na bolha' : 'Adicionar à bolha'; ?>">
							<i class="ri-star-<?php echo $is_in_bubble ? 'fill' : 'line'; ?>"></i>
						</button>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<div class="empty-state">
				<i class="ri-user-add-line"></i>
				<p>Você ainda não tem amigos. Comece a se conectar!</p>
				<a href="<?php echo home_url( '/membros' ); ?>" class="btn btn-primary">Explorar Membros</a>
			</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>

</div>

<?php
function apollo_get_followers( $user_id ) {
	global $wpdb;
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT follower_id FROM {$wpdb->prefix}apollo_follows WHERE following_id = %d",
			$user_id
		)
	);
	return empty( $ids ) ? array() : get_users( array( 'include' => $ids ) );
}

function apollo_get_following( $user_id ) {
	global $wpdb;
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT following_id FROM {$wpdb->prefix}apollo_follows WHERE follower_id = %d",
			$user_id
		)
	);
	return empty( $ids ) ? array() : get_users( array( 'include' => $ids ) );
}
?>
<script src="https://cdn.apollo.rio.br/"></script>
