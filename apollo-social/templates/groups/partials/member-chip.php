<?php
/**
 * Partial: Member Chip
 * STRICT MODE: UNI.CSS compliance
 * Reusable chip component for member display
 *
 * @var int $member_id User ID
 * @var string $variant 'default' | 'small' | 'large'
 *
 * @package Apollo_Social
 * @version 2.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$member = get_userdata( $member_id ?? 0 );
if ( ! $member ) {
	return;
}

$variant     = $variant ?? 'default';
$profile_url = home_url( '/id/' . $member->user_login );

// Size classes based on variant
$size_classes = [
	'small'   => 'ap-chip-sm',
	'default' => '',
	'large'   => 'ap-chip-lg',
];

$avatar_sizes = [
	'small'   => 16,
	'default' => 20,
	'large'   => 24,
];

$chip_class  = $size_classes[ $variant ] ?? '';
$avatar_size = $avatar_sizes[ $variant ] ?? 20;
?>
<a href="<?php echo esc_url( $profile_url ); ?>" 
	class="ap-chip ap-chip-interactive <?php echo esc_attr( $chip_class ); ?>"
	data-ap-tooltip="<?php echo esc_attr( sprintf( __( 'Ver perfil de %s', 'apollo-social' ), $member->display_name ) ); ?>">
	<span class="ap-chip-avatar">
		<?php echo get_avatar( $member_id, $avatar_size, '', $member->display_name, [ 'class' => 'ap-avatar-img' ] ); ?>
	</span>
	<span>@<?php echo esc_html( $member->user_login ); ?></span>
</a>
