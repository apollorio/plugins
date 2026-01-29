<?php
/**
 * Sidebar Status
 * File: template-parts/user/sidebar-status.php
 */

$user_id     = get_current_user_id();
$communities = apollo_get_user_communities( $user_id, 5 );
?>

<div class="sidebar-block">
	<div class="sidebar-title">Status Social</div>
	<div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
		<?php if ( ! empty( $communities ) ) : ?>
			<?php foreach ( $communities as $community ) : ?>
				<span class="mini-tag" style="background:#ecfdf3; color:#065f46; border:1px solid #a7f3d0;">
					<?php echo esc_html( $community->post_title ); ?>
				</span>
			<?php endforeach; ?>
		<?php else : ?>
			<span class="mini-tag">Nenhuma comunidade</span>
		<?php endif; ?>
	</div>
</div>
