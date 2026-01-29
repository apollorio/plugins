<?php
/**
 * Tab: Núcleos
 * File: template-parts/user/tab-nucleos.php
 */

$user_id = get_current_user_id();
$nucleos = apollo_get_user_nucleos( $user_id );
?>

<div class="section-header">
	<div class="section-title">Núcleos Privados</div>
</div>

<div class="cards-grid">
	<?php if ( ! empty( $nucleos ) ) : ?>
		<?php
		foreach ( $nucleos as $nucleo ) :
			$role         = get_user_meta( $user_id, 'nucleo_role_' . $nucleo->ID, true ) ?: 'Membro';
			$member_count = apollo_count_nucleo_members( $nucleo->ID );
			?>
		<article class="apollo-card">
			<div class="card-top">
				<span class="card-meta"><?php echo esc_html( $role ); ?></span>
				<span class="status-badge status-private"><i class="ri-lock-fill"></i> Privado</span>
			</div>
			<h3 class="card-title"><?php echo esc_html( $nucleo->post_title ); ?></h3>
			<p class="card-text"><?php echo wp_trim_words( $nucleo->post_excerpt, 15 ); ?></p>
			<div class="card-footer">
				<span class="mini-tag"><?php echo $member_count; ?> Membros</span>
				<a href="<?php echo get_permalink( $nucleo->ID ); ?>" class="link-action">Acessar <i class="ri-arrow-right-line"></i></a>
			</div>
		</article>
		<?php endforeach; ?>
	<?php else : ?>
		<p class="card-text">Você ainda não participa de nenhum núcleo.</p>
	<?php endif; ?>
</div>
