<?php
/**
 * Profile Hero Section
 * File: template-parts/user/hero.php
 */

$current_user  = wp_get_current_user();
$user_avatar   = get_avatar_url( $current_user->ID, array( 'size' => 300 ) );
$user_role     = apollo_get_user_display_role( $current_user->ID );
$user_bio      = get_user_meta( $current_user->ID, 'description', true );
$user_location = get_user_meta( $current_user->ID, 'user_location', true );
$is_verified   = get_user_meta( $current_user->ID, 'verified', true );
$has_alert     = apollo_check_user_alerts( $current_user->ID );
$is_cenario    = current_user_can( 'cena-rio' ) || current_user_can( 'administrator' );

$stats = apollo_get_user_stats( $current_user->ID );
?>

<section class="profile-hero">
	<div class="profile-main-info">
		<div class="profile-avatar-wrap">
			<div class="profile-avatar">
				<img src="<?php echo esc_url( $user_avatar ); ?>" alt="<?php echo esc_attr( $current_user->display_name ); ?>">
			</div>
			<div class="profile-badge-icon"><i class="ri-flashlight-fill"></i></div>
		</div>
		
		<div class="profile-details">
			<h1 class="profile-name">
				<?php echo esc_html( $current_user->display_name ); ?>
				<span class="user-icon-badge user-verified">
					<?php if ( $is_verified ) : ?>
						<i class="ri-verified-badge-fill verificado"></i>
					<?php endif; ?>
					<?php if ( $has_alert ) : ?>
						<i class="ri-alert-fill alarm-on" style="color:red; display: inline-block;"></i>
					<?php endif; ?>
				</span>
				<?php if ( $user_role ) : ?>
					<span class="profile-role-badge">
						<i class="ri-music-2-line"></i> <?php echo esc_html( $user_role ); ?>
					</span>
				<?php endif; ?>
			</h1>
			
			<?php if ( $user_bio ) : ?>
				<p class="profile-bio"><?php echo esc_html( $user_bio ); ?></p>
			<?php endif; ?>
			
			<div class="profile-meta-row">
				<?php if ( $user_location ) : ?>
					<span class="meta-tag"><i class="ri-map-pin-line"></i> <?php echo esc_html( $user_location ); ?></span>
				<?php endif; ?>
				<?php if ( $is_cenario ) : ?>
					<span class="meta-tag" title="Industry Access"><i class="ri-briefcase-2-fill"></i> Cena::rio</span>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="profile-stats-grid">
		<div class="stat-pill">
			<span class="stat-label">Eventos</span>
			<span class="stat-value"><?php echo number_format( $stats['events'], 0 ); ?></span>
		</div>
		<div class="stat-pill">
			<span class="stat-label">Núcleos</span>
			<span class="stat-value"><?php echo str_pad( $stats['nucleos'], 2, '0', STR_PAD_LEFT ); ?></span>
		</div>
		<div class="stat-pill">
			<span class="stat-label">Posts</span>
			<span class="stat-value"><?php echo number_format( $stats['posts'], 0 ); ?></span>
		</div>
	</div>
</section>
