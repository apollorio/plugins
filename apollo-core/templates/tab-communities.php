<?php
/**
 * Tab: Communities
 * File: template-parts/user/tab-communities.php
 */

$user_id     = get_current_user_id();
$communities = apollo_get_user_communities( $user_id );
?>

<div class="section-header">
	<div class="section-title">Comunidades Públicas</div>
</div>

<div class="cards-grid three-col">
	<?php if ( ! empty( $communities ) ) : ?>
		<?php
		foreach ( $communities as $community ) :
			$member_count = apollo_count_community_members( $community->ID );
			$category     = get_the_terms( $community->ID, 'community_category' );
			$cat_name     = $category && ! is_wp_error( $category ) ? $category[0]->name : 'Geral';
			?>
		<article class="apollo-card">
			<div class="card-top">
				<span class="card-meta"><?php echo esc_html( $cat_name ); ?></span>
				<span class="status-badge status-public">Aberta</span>
			</div>
			<h3 class="card-title"><?php echo esc_html( $community->post_title ); ?></h3>
			<p class="card-text"><?php echo wp_trim_words( $community->post_excerpt, 12 ); ?></p>
			<div class="card-footer">
				<span class="mini-tag"><?php echo apollo_format_member_count( $member_count ); ?></span>
			</div>
		</article>
		<?php endforeach; ?>
	<?php else : ?>
		<p class="card-text">Você ainda não participa de comunidades.</p>
	<?php endif; ?>
</div>
