<?php
/**
 * FASE 2: Comments Endpoint
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

namespace Apollo\API\Endpoints;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CommentsEndpoint {

	/**
	 * Register AJAX handlers
	 */
	public function register(): void {
		add_action( 'wp_ajax_apollo_submit_comment', array( $this, 'submitComment' ) );
		add_action( 'wp_ajax_nopriv_apollo_submit_comment', array( $this, 'submitComment' ) );
	}

	/**
	 * Submit comment via AJAX
	 */
	public function submitComment() {
		check_ajax_referer( 'apollo_comment_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Você precisa estar logado para comentar.', 'apollo-social' ) ) );

			return;
		}

		$post_id         = absint( $_POST['post_id'] ?? 0 );
		$comment_content = sanitize_textarea_field( $_POST['comment'] ?? '' );

		if ( ! $post_id || ! $comment_content ) {
			wp_send_json_error( array( 'message' => __( 'Dados inválidos.', 'apollo-social' ) ) );

			return;
		}

		$user         = wp_get_current_user();
		$comment_data = array(
			'comment_post_ID'      => $post_id,
			'comment_author'       => $user->display_name,
			'comment_author_email' => $user->user_email,
			'comment_content'      => $comment_content,
			'comment_type'         => 'comment',
			'comment_parent'       => 0,
			'user_id'              => $user->ID,
			'comment_approved'     => 1,
		// Auto-aprovar para usuários logados
		);

		$comment_id = wp_insert_comment( $comment_data );

		if ( is_wp_error( $comment_id ) ) {
			wp_send_json_error( array( 'message' => $comment_id->get_error_message() ) );

			return;
		}

		// Retornar HTML do comentário
		$comment = get_comment( $comment_id );
		ob_start();
		$this->renderComment( $comment );
		$comment_html = ob_get_clean();

		wp_send_json_success(
			array(
				'comment_id'    => $comment_id,
				'html'          => $comment_html,
				'comment_count' => get_comments_number( $post_id ),
			)
		);
	}

	/**
	 * Render single comment HTML
	 */
	private function renderComment( $comment ) {
		$author = get_userdata( $comment->user_id );
		?>
		<div class="apollo-comment" data-comment-id="<?php echo esc_attr( $comment->comment_ID ); ?>">
			<div class="flex gap-3">
				<img src="<?php echo esc_url( get_avatar_url( $comment->user_id ) ); ?>" 
					alt="<?php echo esc_attr( $author->display_name ); ?>"
					class="w-10 h-10 rounded-full">
				<div class="flex-1">
					<div class="flex items-center gap-2 mb-1">
						<span class="font-semibold"><?php echo esc_html( $author->display_name ); ?></span>
						<span class="text-sm text-muted-foreground">
							<?php echo human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ); ?> atrás
						</span>
					</div>
					<p class="text-sm"><?php echo esc_html( $comment->comment_content ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}
}
