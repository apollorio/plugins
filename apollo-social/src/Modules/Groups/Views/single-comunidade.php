<?php
/**
 * Single comunidade view with Analytics tracking
 *
 * Template for displaying single comunidade (/comunidade/{slug})
 */

// Get comunidade slug from URL
$slug = get_query_var( 'apollo_slug', '' );

// TODO: implement single comunidade template
// 1. Load comunidade data by slug
// 2. Check viewing permissions
// 3. Display comunidade info, members, posts
// 4. Show join/leave actions if applicable
// 5. Render using Canvas Mode layout

?>

<div class="apollo-group-single apollo-comunidade-single" data-group-type="comunidade" data-group-slug="<?php echo esc_attr( $slug ); ?>">
	
	<div class="group-header">
		<h1 class="group-title">Comunidade: <?php echo esc_html( $slug ); ?></h1>
		<div class="group-actions">
			<button class="btn apollo-join-group-btn" data-group-type="comunidade" data-group-slug="<?php echo esc_attr( $slug ); ?>">
				Entrar na Comunidade
			</button>
		</div>
	</div>
	
	<div class="group-content">
		<p>Conteúdo da comunidade será implementado aqui.</p>
		
		<!-- Union badges toggle -->
		<div class="union-badges-section">
			<label>
				<input type="checkbox" class="apollo-union-badges-toggle" data-group-type="comunidade">
				Exibir badges da União
			</label>
		</div>
	</div>
	
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Track group view
	if (typeof apolloAnalytics !== 'undefined') {
		apolloAnalytics.trackGroupView('comunidade', '<?php echo esc_js( $slug ); ?>');
	}
	
	// Track group join clicks
	document.querySelectorAll('.apollo-join-group-btn').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var groupType = this.getAttribute('data-group-type');
			var groupSlug = this.getAttribute('data-group-slug');
			
			if (typeof apolloAnalytics !== 'undefined') {
				apolloAnalytics.trackGroupJoin(groupType, groupSlug);
			}
			
			// TODO: Implement actual join logic
			console.log('Joining group:', groupType, groupSlug);
		});
	});
	
	// Track union badges toggle
	document.querySelectorAll('.apollo-union-badges-toggle').forEach(function(toggle) {
		toggle.addEventListener('change', function() {
			var action = this.checked ? 'on' : 'off';
			
			if (typeof apolloAnalytics !== 'undefined') {
				apolloAnalytics.trackUnionBadgesToggle(action);
			}
		});
	});
});
</script>
