<?php

declare(strict_types=1);
/**
 * Gamification - Leaderboard
 * File: template-parts/gamification/leaderboard.php
 * REST: GET /leaderboard, GET /points/me, GET /competitions
 */

$user_id      = get_current_user_id();
$leaderboard  = apollo_get_leaderboard( 20 );
$my_points    = $user_id ? apollo_get_user_points( $user_id ) : 0;
$competitions = apollo_get_competitions( array( 'status' => 'active' ) );
?>

<div class="apollo-gamification">

	<?php if ( $user_id ) : ?>
	<div class="my-stats-card">
		<div class="points-display">
			<i class="ri-medal-line"></i>
			<span class="points-value"><?php echo apollo_format_number( $my_points ); ?></span>
			<span class="points-label">pontos</span>
		</div>
		<div class="rank-info">
			<?php
			$my_rank = array_search( $user_id, array_column( $leaderboard, 'user' ) );
			if ( $my_rank !== false ) :
				?>
			<span>Ranking: #<?php echo $my_rank + 1; ?></span>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

	<div class="leaderboard-section">
		<h2>Ranking</h2>

		<?php if ( ! empty( $leaderboard ) ) : ?>
		<div class="leaderboard-podium">
			<?php foreach ( array_slice( $leaderboard, 0, 3 ) as $idx => $entry ) : ?>
			<div class="podium-place place-<?php echo $idx + 1; ?>">
				<div class="podium-avatar">
					<img src="<?php echo esc_url( $entry['avatar'] ); ?>" alt="">
					<span class="rank-badge"><?php echo $idx + 1; ?></span>
				</div>
				<a href="<?php echo home_url( '/membro/' . $entry['user']->user_nicename ); ?>" class="name">
					<?php echo esc_html( $entry['user']->display_name ); ?>
				</a>
				<span class="points"><?php echo apollo_format_number( $entry['points'] ); ?> pts</span>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="leaderboard-list">
			<?php foreach ( array_slice( $leaderboard, 3 ) as $entry ) : ?>
			<div class="leaderboard-row <?php echo $entry['user']->ID == $user_id ? 'is-me' : ''; ?>">
				<span class="rank">#<?php echo $entry['rank']; ?></span>
				<img src="<?php echo esc_url( $entry['avatar'] ); ?>" class="avatar" alt="">
				<a href="<?php echo home_url( '/membro/' . $entry['user']->user_nicename ); ?>" class="name">
					<?php echo esc_html( $entry['user']->display_name ); ?>
				</a>
				<span class="points"><?php echo apollo_format_number( $entry['points'] ); ?></span>
			</div>
			<?php endforeach; ?>
		</div>
		<?php else : ?>
		<div class="empty-state">
			<p>Nenhum participante ainda.</p>
		</div>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $competitions ) ) : ?>
	<div class="competitions-section">
		<h2>Competições Ativas</h2>
		<div class="competitions-grid">
			<?php foreach ( $competitions as $comp ) : ?>
			<article class="competition-card">
				<h3><?php echo esc_html( $comp->post_title ); ?></h3>
				<p><?php echo wp_trim_words( $comp->post_excerpt, 15 ); ?></p>
				<div class="comp-meta">
					<span><i class="ri-calendar-line"></i> Termina em
						<?php echo human_time_diff( strtotime( get_post_meta( $comp->ID, 'end_date', true ) ) ); ?></span>
				</div>
				<a href="<?php echo get_permalink( $comp->ID ); ?>" class="btn btn-outline btn-sm">Ver detalhes</a>
			</article>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

</div>
<script src="https://cdn.apollo.rio.br/"></script>