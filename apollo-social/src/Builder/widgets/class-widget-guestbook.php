<?php
/**
 * Apollo Widget: Guestbook (Depoimentos)
 *
 * Comment list + form using native WP comments.
 * Labels everything as "Depoimentos" (Habbo style).
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || exit;

class Apollo_Widget_Guestbook extends Apollo_Widget_Base {

	public function get_name() {
		return 'guestbook';
	}

	public function get_title() {
		return __( 'Depoimentos', 'apollo-social' );
	}

	public function get_icon() {
		return 'dashicons-format-status';
	}

	public function get_description() {
		return __( 'Let visitors leave messages on your home.', 'apollo-social' );
	}

	public function get_tooltip() {
		return __( 'Uses native WordPress comments. Visitors can leave "depoimentos" (testimonials).', 'apollo-social' );
	}

	public function get_default_width() {
		return 300;
	}

	public function get_default_height() {
		return 350;
	}

	/**
	 * Settings
	 */
	public function get_settings() {
		return array(
			'max_comments' => $this->field(
				'slider',
				__( 'Max Depoimentos', 'apollo-social' ),
				5,
				array(
					'min' => 1,
					'max' => 20,
				)
			),
			'show_form'    => $this->field( 'switch', __( 'Show Form', 'apollo-social' ), true ),
			'show_avatars' => $this->field( 'switch', __( 'Show Avatars', 'apollo-social' ), true ),
		);
	}

	/**
	 * Render widget
	 *
	 * Data source: wp_comments table (post_id = apollo_home ID)
	 * Tooltip: Uses native wp_list_comments and comment_form
	 */
	public function render( $data ) {
		$settings = $data['settings'] ?? array();
		$post_id  = $data['post_id'] ?? 0;

		if ( ! $post_id ) {
			return '<div class="apollo-widget-guestbook apollo-widget-error">'
				. __( 'Invalid home', 'apollo-social' )
				. '</div>';
		}

		$max_comments = absint( $settings['max_comments'] ?? 5 );
		$show_form    = ! empty( $settings['show_form'] );
		$show_avatars = ! empty( $settings['show_avatars'] );

		// Get comments
		$comments = get_comments(
			array(
				'post_id' => $post_id,
				'status'  => 'approve',
				'number'  => $max_comments,
				'orderby' => 'comment_date_gmt',
				'order'   => 'DESC',
			)
		);

		$comment_count = get_comments_number( $post_id );

		ob_start();
		?>
		<div class="apollo-widget-guestbook" data-post-id="<?php echo absint( $post_id ); ?>">
			<h4 class="widget-title">
				<span class="dashicons dashicons-format-status"></span>
				<?php _e( 'Depoimentos', 'apollo-social' ); ?>
				<span class="comment-count">(<?php echo absint( $comment_count ); ?>)</span>
			</h4>
			
			<div class="depoimentos-list">
				<?php if ( empty( $comments ) ) : ?>
					<p class="depoimentos-empty"><?php _e( 'Seja o primeiro a deixar um depoimento!', 'apollo-social' ); ?></p>
				<?php else : ?>
					<?php foreach ( $comments as $comment ) : ?>
						<div class="depoimento-item">
							<?php if ( $show_avatars ) : ?>
								<div class="depoimento-avatar">
									<?php echo get_avatar( $comment->comment_author_email, 40 ); ?>
								</div>
							<?php endif; ?>
							<div class="depoimento-content">
								<span class="depoimento-author"><?php echo esc_html( $comment->comment_author ); ?></span>
								<span class="depoimento-date"><?php echo human_time_diff( strtotime( $comment->comment_date_gmt ), current_time( 'timestamp' ) ); ?> atrás</span>
								<p class="depoimento-text"><?php echo wp_kses_post( $comment->comment_content ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			
			<?php if ( $show_form ) : ?>
				<div class="depoimento-form-wrapper">
					<form class="depoimento-form" data-action="apollo_builder_add_depoimento">
						<?php wp_nonce_field( 'apollo-builder-nonce', 'depoimento_nonce' ); ?>
						<input type="hidden" name="post_id" value="<?php echo absint( $post_id ); ?>">
						
						<?php if ( ! is_user_logged_in() ) : ?>
							<input type="text" 
									name="author" 
									placeholder="<?php esc_attr_e( 'Seu nome', 'apollo-social' ); ?>" 
									required
									class="depoimento-author-input">
						<?php endif; ?>
						
						<textarea name="content" 
									placeholder="<?php esc_attr_e( 'Deixe seu depoimento...', 'apollo-social' ); ?>"
									rows="2"
									maxlength="500"
									required
									class="depoimento-content-input"></textarea>
						
						<button type="submit" class="depoimento-submit">
							<span class="dashicons dashicons-testimonial"></span>
							<?php _e( 'Enviar', 'apollo-social' ); ?>
						</button>
					</form>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}

