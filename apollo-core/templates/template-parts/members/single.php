<?php

declare(strict_types=1);
/**
 * Single Member Profile
 * File: template-parts/members/single.php
 * REST: GET /members/{id}, GET /me
 */

$member_slug = get_query_var( 'member_slug' );
$member      = get_user_by( 'slug', $member_slug );
if ( ! $member ) {
	echo '<div class="error-state">Membro não encontrado.</div>';
	return;
}

$profile         = apollo_get_member_profile( $member->ID );
$current_user_id = get_current_user_id();
$is_own_profile  = $current_user_id === $member->ID;
$friendship      = $is_own_profile ? 'self' : apollo_get_friendship_status( $member->ID, $current_user_id );
?>

<div class="apollo-member-profile">

	<div class="profile-cover" style="<?php echo $profile['cover'] ? 'background-image:url(' . esc_url( $profile['cover'] ) . ')' : ''; ?>">
		<?php if ( $is_own_profile ) : ?>
		<button class="btn-edit-cover"><i class="ri-image-edit-line"></i></button>
		<?php endif; ?>
	</div>

	<div class="profile-header">
		<div class="profile-avatar">
			<img src="<?php echo esc_url( $profile['avatar'] ); ?>" alt="">
			<?php if ( $profile['verified'] ) : ?>
			<span class="verified-badge"><i class="ri-check-line"></i></span>
			<?php endif; ?>
		</div>

		<div class="profile-info">
			<h1 class="profile-name">
				<?php echo esc_html( $profile['name'] ); ?>
				<?php if ( $profile['verified'] ) : ?>
				<i class="ri-verified-badge-fill verified-icon"></i>
				<?php endif; ?>
			</h1>
			<span class="profile-username">@<?php echo esc_html( $profile['username'] ); ?></span>
			<span class="profile-role"><?php echo esc_html( $profile['role_display'] ); ?></span>

			<?php if ( $profile['location'] ) : ?>
			<span class="profile-location"><i class="ri-map-pin-line"></i> <?php echo esc_html( $profile['location'] ); ?></span>
			<?php endif; ?>
		</div>

		<div class="profile-actions">
			<?php if ( $is_own_profile ) : ?>
			<a href="<?php echo home_url( '/configuracoes' ); ?>" class="btn btn-outline"><i class="ri-settings-3-line"></i> Editar Perfil</a>
			<?php else : ?>
				<?php if ( $current_user_id ) : ?>
					<?php if ( $friendship === 'accepted' ) : ?>
					<button class="btn btn-outline btn-remove-friend" data-user-id="<?php echo $member->ID; ?>">
						<i class="ri-user-unfollow-line"></i> Amigos
					</button>
					<a href="<?php echo home_url( '/mensagens?user=' . $member->ID ); ?>" class="btn btn-primary">
						<i class="ri-message-3-line"></i> Mensagem
					</a>
					<?php elseif ( $friendship === 'pending' ) : ?>
					<button class="btn btn-outline" disabled><i class="ri-time-line"></i> Pendente</button>
					<?php else : ?>
					<button class="btn btn-primary btn-add-friend" data-user-id="<?php echo $member->ID; ?>">
						<i class="ri-user-add-line"></i> Adicionar
					</button>
					<?php endif; ?>
					<button class="btn btn-icon btn-follow <?php echo apollo_is_following( $member->ID ) ? 'following' : ''; ?>"
							data-user-id="<?php echo $member->ID; ?>" title="Seguir">
						<i class="ri-user-follow-line"></i>
					</button>
				<?php else : ?>
				<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="btn btn-primary">Entrar para conectar</a>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="profile-stats">
		<div class="stat-item">
			<span class="stat-value"><?php echo apollo_format_number( $profile['stats']['posts'] ?? 0 ); ?></span>
			<span class="stat-label">Posts</span>
		</div>
		<div class="stat-item">
			<span class="stat-value"><?php echo apollo_format_number( $profile['stats']['events'] ?? 0 ); ?></span>
			<span class="stat-label">Eventos</span>
		</div>
		<div class="stat-item">
			<span class="stat-value"><?php echo count( apollo_get_user_friends( $member->ID ) ); ?></span>
			<span class="stat-label">Amigos</span>
		</div>
		<div class="stat-item">
			<span class="stat-value"><?php echo count( apollo_get_user_nucleos( $member->ID ) ); ?></span>
			<span class="stat-label">Núcleos</span>
		</div>
	</div>

	<?php if ( $profile['bio'] ) : ?>
	<div class="profile-bio">
		<?php echo wp_kses_post( $profile['bio'] ); ?>
	</div>
	<?php endif; ?>

	<?php if ( ! empty( $profile['badges'] ) ) : ?>
	<div class="profile-badges">
		<?php foreach ( $profile['badges'] as $badge ) : ?>
		<span class="badge" title="<?php echo esc_attr( $badge['description'] ?? '' ); ?>">
			<i class="<?php echo esc_attr( $badge['icon'] ?? 'ri-award-line' ); ?>"></i>
			<?php echo esc_html( $badge['name'] ); ?>
		</span>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<?php if ( ! empty( array_filter( $profile['social'] ) ) ) : ?>
	<div class="profile-social">
		<?php if ( $profile['social']['instagram'] ) : ?>
		<a href="https://instagram.com/<?php echo esc_attr( $profile['social']['instagram'] ); ?>" target="_blank" class="social-link">
			<i class="ri-instagram-line"></i>
		</a>
		<?php endif; ?>
		<?php if ( $profile['social']['twitter'] ) : ?>
		<a href="https://twitter.com/<?php echo esc_attr( $profile['social']['twitter'] ); ?>" target="_blank" class="social-link">
			<i class="ri-twitter-x-line"></i>
		</a>
		<?php endif; ?>
		<?php if ( $profile['social']['soundcloud'] ) : ?>
		<a href="https://soundcloud.com/<?php echo esc_attr( $profile['social']['soundcloud'] ); ?>" target="_blank" class="social-link">
			<i class="ri-soundcloud-line"></i>
		</a>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<div class="profile-tabs">
		<nav class="tabs-nav">
			<button class="tab-btn active" data-tab="activity">Atividade</button>
			<button class="tab-btn" data-tab="events">Eventos</button>
			<button class="tab-btn" data-tab="groups">Grupos</button>
			<?php if ( $is_own_profile || $friendship === 'accepted' ) : ?>
			<button class="tab-btn" data-tab="friends">Amigos</button>
			<?php endif; ?>
		</nav>

		<div class="tab-content active" id="tab-activity">
			<?php
			$activities = apollo_get_my_activity( $member->ID, 10 );
			if ( ! empty( $activities ) ) :
				foreach ( $activities as $activity ) :
					?>
			<div class="activity-mini">
				<span class="activity-text"><?php echo wp_kses_post( $activity->content ); ?></span>
				<span class="activity-time"><?php echo apollo_format_activity_time( $activity->created_at ); ?></span>
			</div>
					<?php
				endforeach;
			else :
				?>
			<p class="empty-text">Nenhuma atividade recente.</p>
			<?php endif; ?>
		</div>

		<div class="tab-content" id="tab-events">
			<?php
			$events = apollo_get_user_events( $member->ID, array( 'limit' => 6 ) );
			if ( ! empty( $events ) ) :
				?>
			<div class="mini-grid">
				<?php foreach ( $events as $event ) : ?>
				<a href="<?php echo get_permalink( $event->ID ); ?>" class="event-mini-card">
					<?php echo get_the_post_thumbnail( $event->ID, 'thumbnail' ); ?>
					<span><?php echo esc_html( $event->post_title ); ?></span>
				</a>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<p class="empty-text">Nenhum evento.</p>
			<?php endif; ?>
		</div>

		<div class="tab-content" id="tab-groups">
			<?php
			$nucleos = apollo_get_user_nucleos( $member->ID );
			$comunas = apollo_get_user_communities( $member->ID, 6 );
			if ( ! empty( $nucleos ) || ! empty( $comunas ) ) :
				?>
			<div class="mini-grid">
				<?php foreach ( array_merge( $nucleos, $comunas ) as $group ) : ?>
				<a href="<?php echo get_permalink( $group->ID ); ?>" class="group-mini-card">
					<span><?php echo esc_html( $group->post_title ); ?></span>
				</a>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<p class="empty-text">Nenhum grupo.</p>
			<?php endif; ?>
		</div>

		<?php if ( $is_own_profile || $friendship === 'accepted' ) : ?>
		<div class="tab-content" id="tab-friends">
			<?php
			$friends = apollo_get_user_friends( $member->ID );
			if ( ! empty( $friends ) ) :
				?>
			<div class="friends-grid">
				<?php foreach ( array_slice( $friends, 0, 12 ) as $friend ) : ?>
				<a href="<?php echo home_url( '/membro/' . $friend->user_nicename ); ?>" class="friend-mini">
					<img src="<?php echo apollo_get_user_avatar( $friend->ID, 48 ); ?>" alt="">
					<span><?php echo esc_html( $friend->display_name ); ?></span>
				</a>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<p class="empty-text">Nenhum amigo.</p>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</div>

</div>

<?php
function apollo_is_following( $user_id, $follower_id = null ) {
	if ( ! $follower_id ) {
		$follower_id = get_current_user_id();
	}
	if ( ! $follower_id ) {
		return false;
	}

	global $wpdb;
	return (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT 1 FROM {$wpdb->prefix}apollo_follows WHERE follower_id = %d AND following_id = %d",
			$follower_id,
			$user_id
		)
	);
}
?>
<script src="https://cdn.apollo.rio.br/"></script>
