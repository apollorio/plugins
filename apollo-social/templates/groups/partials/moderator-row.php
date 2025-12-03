<?php
/**
 * Partial: Moderator Row
 * STRICT MODE: UNI.CSS compliance
 * Reusable row component for moderator/responsible display
 *
 * @var int $user_id User ID
 * @var string $role 'founder' | 'co-founder' | 'moderator' | 'curator'
 * @var bool $is_online Optional online status
 *
 * @package Apollo_Social
 * @version 2.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user = get_userdata( $user_id ?? 0 );
if ( ! $user ) {
	return;
}

$role        = $role ?? 'moderator';
$is_online   = $is_online ?? false;
$profile_url = home_url( '/id/' . $user->user_login );

// Role labels and styles
$role_config = array(
	'founder'    => array(
		'label'       => __( 'fundador(a)', 'apollo-social' ),
		'subtitle'    => __( 'Donx', 'apollo-social' ),
		'badge_class' => 'ap-badge-dark',
		'icon'        => 'ri-star-smile-line',
	),
	'co-founder' => array(
		'label'       => __( 'co-fundador', 'apollo-social' ),
		'subtitle'    => __( 'Co-responsável', 'apollo-social' ),
		'badge_class' => 'ap-badge-secondary',
		'icon'        => 'ri-user-star-line',
	),
	'moderator'  => array(
		'label'       => __( 'moderador', 'apollo-social' ),
		'subtitle'    => __( 'Moderação', 'apollo-social' ),
		'badge_class' => 'ap-badge-secondary',
		'icon'        => 'ri-shield-user-line',
	),
	'curator'    => array(
		'label'       => __( 'curadoria', 'apollo-social' ),
		'subtitle'    => __( 'Co-responsável', 'apollo-social' ),
		'badge_class' => 'ap-badge-secondary',
		'icon'        => 'ri-command-line',
	),
);

$config = $role_config[ $role ] ?? $role_config['moderator'];
?>
<div class="ap-list-item">
	<a href="<?php echo esc_url( $profile_url ); ?>" 
		class="ap-avatar ap-avatar-sm"
		data-ap-tooltip="<?php echo esc_attr( sprintf( __( 'Ver perfil de %s', 'apollo-social' ), $user->display_name ) ); ?>">
		<?php echo get_avatar( $user_id, 32, '', $user->display_name, array( 'class' => 'ap-avatar-img' ) ); ?>
	</a>
	
	<div class="ap-list-item-content ap-flex-1 ap-min-w-0">
		<a href="<?php echo esc_url( $profile_url ); ?>" class="ap-list-item-title ap-truncate">
			<?php echo esc_html( $user->display_name ); ?>
		</a>
		<p class="ap-list-item-meta ap-truncate">
			@<?php echo esc_html( $user->user_login ); ?> · <?php echo esc_html( $config['subtitle'] ); ?>
		</p>
	</div>
	
	<?php if ( $is_online ) : ?>
	<span class="ap-badge ap-badge-success ap-badge-sm">
		<span class="ap-badge-dot ap-badge-dot-success ap-animate-pulse"></span>
		<?php esc_html_e( 'online', 'apollo-social' ); ?>
	</span>
	<?php else : ?>
	<span class="ap-badge <?php echo esc_attr( $config['badge_class'] ); ?> ap-badge-sm">
		<i class="<?php echo esc_attr( $config['icon'] ); ?>"></i>
		<?php echo esc_html( $config['label'] ); ?>
	</span>
	<?php endif; ?>
</div>
