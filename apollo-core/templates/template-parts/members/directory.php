<?php

declare(strict_types=1);
/**
 * Members Directory
 * File: template-parts/members/directory.php
 * REST: GET /members, GET /members/online
 */

$page           = max( 1, (int) ( $_GET['pg'] ?? 1 ) );
$search         = sanitize_text_field( $_GET['s'] ?? '' );
$filter         = sanitize_text_field( $_GET['filter'] ?? '' );
$members        = apollo_get_members(
	array(
		'per_page' => 24,
		'page'     => $page,
		'search'   => $search,
		'role'     => $filter,
	)
);
$online_members = apollo_get_online_members( 8 );
?>

<div class="apollo-members-directory">

	<div class="directory-header">
		<h2 class="section-title">Membros</h2>
		<form class="search-form" method="get">
			<div class="search-input-wrapper">
				<i class="ri-search-line"></i>
				<label for="members-search" class="sr-only">Buscar membros</label>
				<input type="text" id="members-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Buscar membros...', 'apollo' ); ?>" title="Buscar membros">
			</div>
			<label for="members-filter" class="sr-only">Filtrar membros</label>
			<select id="members-filter" name="filter" class="filter-select" onchange="this.form.submit()" title="Filtrar membros">
				<option value="">Todos</option>
				<option value="producer" <?php selected( $filter, 'producer' ); ?>>Produtores</option>
				<option value="artist" <?php selected( $filter, 'artist' ); ?>>Artistas</option>
				<option value="venue" <?php selected( $filter, 'venue' ); ?>>Locais</option>
			</select>
		</form>
	</div>

	<?php if ( ! empty( $online_members ) ) : ?>
	<div class="online-now">
		<h3 class="subsection-title"><i class="ri-radio-button-line pulse"></i> Online Agora</h3>
		<div class="online-avatars">
			<?php foreach ( $online_members as $member ) : ?>
			<a href="<?php echo home_url( '/membro/' . $member->user_nicename ); ?>" class="online-avatar" title="<?php echo esc_attr( $member->display_name ); ?>">
				<img src="<?php echo apollo_get_user_avatar( $member->ID, 40 ); ?>" alt="<?php echo esc_attr( 'Avatar de ' . $member->display_name ); ?>">
			</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<div class="members-grid">
		<?php if ( ! empty( $members ) ) : ?>
			<?php
			foreach ( $members as $member ) :
				$verified     = get_user_meta( $member->ID, 'verified', true );
				$role_display = apollo_get_user_display_role( $member->ID );
				$location     = get_user_meta( $member->ID, 'user_location', true );
				?>
			<article class="member-card">
				<a href="<?php echo home_url( '/membro/' . $member->user_nicename ); ?>">
					<div class="member-avatar">
						<img src="<?php echo apollo_get_user_avatar( $member->ID, 120 ); ?>" alt="<?php echo esc_attr( 'Avatar de ' . $member->display_name ); ?>">
						<?php if ( $verified ) : ?>
						<span class="verified-badge"><i class="ri-check-line"></i></span>
						<?php endif; ?>
					</div>
					<div class="member-info">
						<h3 class="member-name"><?php echo esc_html( $member->display_name ); ?></h3>
						<span class="member-role"><?php echo esc_html( $role_display ); ?></span>
						<?php if ( $location ) : ?>
						<span class="member-location"><i class="ri-map-pin-line"></i> <?php echo esc_html( $location ); ?></span>
						<?php endif; ?>
					</div>
				</a>
			</article>
			<?php endforeach; ?>
		<?php else : ?>
		<div class="empty-state col-span-full">
			<i class="ri-user-search-line"></i>
			<p>Nenhum membro encontrado.</p>
		</div>
		<?php endif; ?>
	</div>

	<nav class="pagination">
		<?php if ( $page > 1 ) : ?>
		<a href="<?php echo add_query_arg( 'pg', $page - 1 ); ?>" class="btn btn-outline">&larr; Anterior</a>
		<?php endif; ?>
		<?php if ( count( $members ) >= 24 ) : ?>
		<a href="<?php echo add_query_arg( 'pg', $page + 1 ); ?>" class="btn btn-outline">Próximo &rarr;</a>
		<?php endif; ?>
	</nav>

</div>
<style>
	.sr-only {
		position: absolute;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0, 0, 0, 0);
		white-space: nowrap;
		border: 0;
	}
</style>
<script src="https://cdn.apollo.rio.br/"></script>
