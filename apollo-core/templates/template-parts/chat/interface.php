<?php

declare(strict_types=1);
/**
 * Chat Interface
 * File: template-parts/chat/interface.php
 * REST: GET /chat/conversations, GET /chat/conversations/{id}, POST /chat/messages
 */

if ( ! is_user_logged_in() ) {
	wp_redirect( home_url( '/login' ) );
	exit;
}

$user_id             = get_current_user_id();
$conversations       = apollo_get_conversations( $user_id );
$active_id           = isset( $_GET['c'] ) ? (int) $_GET['c'] : ( isset( $conversations[0] ) ? $conversations[0]->id : null );
$active_conversation = $active_id ? apollo_get_conversation_messages( $active_id, 50 ) : array();
$online_friends      = apollo_get_chat_online_users( $user_id );
?>

<div class="apollo-chat-interface" data-user-id="<?php echo $user_id; ?>">

	<aside class="chat-sidebar">
		<div class="sidebar-header">
			<h2>Mensagens</h2>
			<button class="btn btn-icon" id="btn-new-chat" title="Nova conversa">
				<i class="ri-edit-line"></i>
			</button>
		</div>

		<?php if ( ! empty( $online_friends ) ) : ?>
		<div class="online-friends">
			<span class="label">Online</span>
			<div class="friends-row">
				<?php foreach ( array_slice( $online_friends, 0, 5 ) as $friend ) : ?>
				<button class="friend-avatar" data-user-id="<?php echo $friend->ID; ?>" title="<?php echo esc_attr( $friend->display_name ); ?>">
					<img src="<?php echo apollo_get_user_avatar( $friend->ID, 36 ); ?>" alt="Avatar de <?php echo esc_attr( $friend->display_name ); ?>">
					<span class="online-dot"></span>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<div class="conversations-list">
			<?php if ( ! empty( $conversations ) ) : ?>
				<?php
				foreach ( $conversations as $conv ) :
					$other_id = ( $conv->user_one == $user_id ) ? $conv->user_two : $conv->user_one;
					$other    = get_userdata( $other_id );
					if ( ! $other ) {
						continue;
					}
					$last_msg = apollo_get_last_message( $conv->id );
					?>
				<a href="?c=<?php echo $conv->id; ?>" class="conversation-item <?php echo $conv->id == $active_id ? 'active' : ''; ?> <?php echo $conv->unread_count > 0 ? 'unread' : ''; ?>">
					<img src="<?php echo apollo_get_user_avatar( $other_id, 48 ); ?>" class="conv-avatar" alt="Avatar de <?php echo esc_attr( $other->display_name ); ?>">
					<div class="conv-info">
						<span class="conv-name"><?php echo esc_html( $other->display_name ); ?></span>
						<span class="conv-preview"><?php echo $last_msg ? wp_trim_words( $last_msg->content, 8 ) : 'Nova conversa'; ?></span>
					</div>
					<?php if ( $conv->unread_count > 0 ) : ?>
					<span class="unread-badge"><?php echo $conv->unread_count; ?></span>
					<?php endif; ?>
				</a>
				<?php endforeach; ?>
			<?php else : ?>
			<div class="empty-conversations">
				<p>Nenhuma conversa ainda.</p>
			</div>
			<?php endif; ?>
		</div>
	</aside>

	<main class="chat-main">
		<?php
		if ( $active_id ) :
			$conv     = array_filter( $conversations, fn( $c ) => $c->id == $active_id );
			$conv     = reset( $conv );
			$other_id = ( $conv->user_one == $user_id ) ? $conv->user_two : $conv->user_one;
			$other    = get_userdata( $other_id );
			?>
		<div class="chat-header">
			<button class="btn btn-icon btn-back-mobile" title="Voltar"><i class="ri-arrow-left-line"></i></button>
			<a href="<?php echo home_url( '/membro/' . $other->user_nicename ); ?>" class="chat-user">
				<img src="<?php echo apollo_get_user_avatar( $other_id, 40 ); ?>" alt="Avatar de <?php echo esc_attr( $other->display_name ); ?>">
				<div>
					<span class="name"><?php echo esc_html( $other->display_name ); ?></span>
					<span class="status" id="user-status">Online</span>
				</div>
			</a>
			<div class="chat-actions">
				<button class="btn btn-icon" title="Mais opções"><i class="ri-more-2-line"></i></button>
			</div>
		</div>

		<div class="chat-messages" id="chat-messages" data-conversation-id="<?php echo $active_id; ?>">
			<?php
			$messages  = array_reverse( $active_conversation );
			$prev_date = '';
			foreach ( $messages as $msg ) :
				$is_mine  = $msg->sender_id == $user_id;
				$msg_date = date( 'Y-m-d', strtotime( $msg->created_at ) );

				if ( $msg_date !== $prev_date ) :
					$prev_date = $msg_date;
					?>
			<div class="date-separator">
				<span><?php echo $msg_date == date( 'Y-m-d' ) ? 'Hoje' : date_i18n( 'd M', strtotime( $msg->created_at ) ); ?></span>
			</div>
			<?php endif; ?>

			<div class="message <?php echo $is_mine ? 'mine' : 'theirs'; ?>" data-message-id="<?php echo $msg->id; ?>">
				<?php if ( ! $is_mine ) : ?>
				<img src="<?php echo apollo_get_user_avatar( $msg->sender_id, 32 ); ?>" class="msg-avatar" alt="Avatar do remetente">
				<?php endif; ?>
				<div class="msg-bubble">
					<p><?php echo wp_kses_post( $msg->content ); ?></p>
					<span class="msg-time"><?php echo date( 'H:i', strtotime( $msg->created_at ) ); ?></span>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="typing-indicator" id="typing-indicator" style="display:none;">
			<span><?php echo esc_html( $other->display_name ); ?> está digitando...</span>
		</div>

		<form class="chat-composer" id="chat-composer">
			<button type="button" class="btn btn-icon btn-attach" title="Anexar arquivo"><i class="ri-attachment-2"></i></button>
			<label for="chat-message" class="sr-only">Mensagem</label>
			<input id="chat-message" type="text" name="message" placeholder="<?php esc_attr_e( 'Digite sua mensagem...', 'apollo' ); ?>" title="Mensagem" autocomplete="off">
			<button type="submit" class="btn btn-primary btn-send" title="Enviar mensagem"><i class="ri-send-plane-fill"></i></button>
			<input type="hidden" name="conversation_id" value="<?php echo $active_id; ?>">
			<input type="hidden" name="receiver_id" value="<?php echo $other_id; ?>">
			<input type="hidden" name="nonce" value="<?php echo apollo_get_rest_nonce(); ?>">
		</form>

		<?php else : ?>
		<div class="chat-placeholder">
			<i class="ri-message-3-line"></i>
			<p>Selecione uma conversa ou inicie uma nova.</p>
		</div>
		<?php endif; ?>
	</main>

</div>

<!-- New Chat Modal -->
<div class="modal" id="modal-new-chat">
	<div class="modal-content">
		<div class="modal-header">
			<h3>Nova Conversa</h3>
			<button class="modal-close">&times;</button>
		</div>
		<div class="form-group">
			<label for="new-chat-search" class="sr-only">Buscar amigo</label>
			<input type="text" id="new-chat-search" placeholder="<?php esc_attr_e( 'Buscar amigo...', 'apollo' ); ?>" title="Buscar amigo">
		</div>
		<div id="new-chat-results"></div>
	</div>
</div>

<style>
	.sr-only {
		position: absolute !important;
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

<?php
function apollo_get_last_message( $conversation_id ) {
	global $wpdb;
	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}apollo_messages
         WHERE conversation_id = %d ORDER BY created_at DESC LIMIT 1",
			$conversation_id
		)
	);
}
?>
<script src="https://cdn.apollo.rio.br/"></script>
