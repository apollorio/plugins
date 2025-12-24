<?php
/**
 * Single núcleo view with Analytics tracking
 *
 * Template for displaying single núcleo (/nucleo/{slug})
 */

// Get núcleo slug from URL
$slug = get_query_var( 'apollo_slug', '' );

// TODO: implement single núcleo template
// 1. Load núcleo data by slug
// 2. Check viewing permissions (private group)
// 3. Display núcleo info for members only
// 4. Show invitation-based join if applicable
// 5. Render using Canvas Mode layout

?>

<div class="apollo-group-single apollo-nucleo-single" data-group-type="nucleo" data-group-slug="<?php echo esc_attr( $slug ); ?>">
	
	<div class="group-header">
		<h1 class="group-title">Núcleo: <?php echo esc_html( $slug ); ?></h1>
		<div class="group-actions">
			<button class="btn apollo-join-group-btn" data-group-type="nucleo" data-group-slug="<?php echo esc_attr( $slug ); ?>">
				Entrar no Núcleo
			</button>
			<button class="btn apollo-invite-btn" data-group-type="nucleo" data-group-slug="<?php echo esc_attr( $slug ); ?>">
				Enviar Convite
			</button>
		</div>
	</div>
	
	<div class="group-content">
		<p>Conteúdo do núcleo será implementado aqui.</p>
		
		<!-- Chat section -->
		<div class="group-chat-section">
			<h3>Chat do Núcleo</h3>
			<div class="chat-messages" id="chat-messages"></div>
			<div class="chat-input">
				<input type="text" id="chat-message-input" placeholder="Digite sua mensagem...">
				<button class="btn apollo-send-message-btn" data-group-type="nucleo">Enviar</button>
			</div>
		</div>
	</div>
	
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Track group view
	if (typeof apolloAnalytics !== 'undefined') {
		apolloAnalytics.trackGroupView('nucleo', '<?php echo esc_js( $slug ); ?>');
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
	
	// Track invite sending
	document.querySelectorAll('.apollo-invite-btn').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var groupType = this.getAttribute('data-group-type');
			var groupSlug = this.getAttribute('data-group-slug');
			
			if (typeof apolloAnalytics !== 'undefined') {
				apolloAnalytics.trackInviteSent(groupType, 'manual');
			}
			
			// TODO: Implement invite logic
			console.log('Sending invite for:', groupType, groupSlug);
		});
	});
	
	// Track chat messages
	document.querySelectorAll('.apollo-send-message-btn').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var groupType = this.getAttribute('data-group-type');
			var messageInput = document.getElementById('chat-message-input');
			
			if (messageInput.value.trim()) {
				if (typeof apolloAnalytics !== 'undefined') {
					apolloAnalytics.trackChatMessage(groupType);
				}
				
				// TODO: Implement actual message sending
				console.log('Sending message to:', groupType);
				messageInput.value = '';
			}
		});
	});
	
	// Enter key for chat
	var chatInput = document.getElementById('chat-message-input');
	if (chatInput) {
		chatInput.addEventListener('keypress', function(e) {
			if (e.key === 'Enter') {
				document.querySelector('.apollo-send-message-btn').click();
			}
		});
	}
});
</script>
