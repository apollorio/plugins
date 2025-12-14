<?php
declare(strict_types=1);

/**
 * Apollo Email Templates CPT
 *
 * Custom Post Type for storing email templates.
 *
 * @package Apollo_Core
 * @since 3.0.0
 */
class Apollo_Email_Templates_CPT {

	/**
	 * Post type name
	 *
	 * @var string
	 */
	public const POST_TYPE = 'apollo_email_template';

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_post_type' ] );
		add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
		add_action( 'save_post', [ __CLASS__, 'save_meta' ] );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ __CLASS__, 'add_columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ __CLASS__, 'render_column' ], 10, 2 );
	}

	/**
	 * Register post type
	 *
	 * @return void
	 */
	public static function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'            => [
					'name'          => __( 'Email Templates', 'apollo-core' ),
					'singular_name' => __( 'Email Template', 'apollo-core' ),
					'add_new'       => __( 'Add New Template', 'apollo-core' ),
					'add_new_item'  => __( 'Add New Email Template', 'apollo-core' ),
					'edit_item'     => __( 'Edit Email Template', 'apollo-core' ),
					'new_item'      => __( 'New Email Template', 'apollo-core' ),
					'view_item'     => __( 'View Email Template', 'apollo-core' ),
					'search_items'  => __( 'Search Templates', 'apollo-core' ),
				],
				'public'            => false,
				'show_ui'           => true,
				'show_in_menu'      => false, // Will be added to Apollo Hub menu.
				'show_in_admin_bar' => true,
				'capability_type'   => 'post',
				'hierarchical'      => false,
				'supports'          => [ 'title', 'editor' ],
				'has_archive'       => false,
				'rewrite'           => false,
				'query_var'         => false,
				'can_export'        => true,
				'show_in_rest'      => false,
			]
		);
	}

	/**
	 * Add meta boxes
	 *
	 * @return void
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'apollo_email_template_meta',
			__( 'Template Settings', 'apollo-core' ),
			[ __CLASS__, 'render_meta_box' ],
			self::POST_TYPE,
			'side',
			'default'
		);

		add_meta_box(
			'apollo_email_template_placeholders',
			__( 'Available Placeholders', 'apollo-core' ),
			[ __CLASS__, 'render_placeholders_box' ],
			self::POST_TYPE,
			'normal',
			'low'
		);
	}

	/**
	 * Render meta box
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public static function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'apollo_email_template_meta', 'apollo_email_template_nonce' );

		$template_slug = get_post_meta( $post->ID, '_apollo_template_slug', true );
		$flow_default  = get_post_meta( $post->ID, '_apollo_flow_default', true );
		$language      = get_post_meta( $post->ID, '_apollo_template_language', true );

		?>
		<p>
			<label for="apollo_template_slug">
				<strong><?php esc_html_e( 'Template Slug', 'apollo-core' ); ?></strong>
			</label><br>
			<input type="text" id="apollo_template_slug" name="apollo_template_slug" 
				value="<?php echo esc_attr( $template_slug ); ?>" 
				class="widefat" 
				placeholder="ex: registration-confirm">
			<small><?php esc_html_e( 'Unique identifier for this template', 'apollo-core' ); ?></small>
		</p>

		<p>
			<label for="apollo_flow_default">
				<strong><?php esc_html_e( 'Default for Flow', 'apollo-core' ); ?></strong>
			</label><br>
			<select id="apollo_flow_default" name="apollo_flow_default" class="widefat">
				<option value=""><?php esc_html_e( 'None (Manual use only)', 'apollo-core' ); ?></option>
				<option value="registration_confirm" <?php selected( $flow_default, 'registration_confirm' ); ?>>
					<?php esc_html_e( 'Registration Confirmation', 'apollo-core' ); ?>
				</option>
				<option value="producer_notify" <?php selected( $flow_default, 'producer_notify' ); ?>>
					<?php esc_html_e( 'Producer Notification', 'apollo-core' ); ?>
				</option>
			</select>
		</p>

		<p>
			<label for="apollo_template_language">
				<strong><?php esc_html_e( 'Language', 'apollo-core' ); ?></strong>
			</label><br>
			<select id="apollo_template_language" name="apollo_template_language" class="widefat">
				<option value="pt-BR" <?php selected( $language, 'pt-BR' ); ?>>Português (Brasil)</option>
				<option value="en-US" <?php selected( $language, 'en-US' ); ?>>English (US)</option>
			</select>
		</p>
		<?php
	}

	/**
	 * Render placeholders box
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public static function render_placeholders_box( \WP_Post $post ): void {
		$placeholders = [
			'User'   => [
				'{{user_name}}'  => __( 'User display name', 'apollo-core' ),
				'{{user_email}}' => __( 'User email address', 'apollo-core' ),
				'{{first_name}}' => __( 'User first name', 'apollo-core' ),
				'{{last_name}}'  => __( 'User last name', 'apollo-core' ),
			],
			'Event'  => [
				'{{event_title}}' => __( 'Event title', 'apollo-core' ),
				'{{event_date}}'  => __( 'Event date', 'apollo-core' ),
				'{{event_venue}}' => __( 'Event venue', 'apollo-core' ),
				'{{event_url}}'   => __( 'Event URL', 'apollo-core' ),
			],
			'Site'   => [
				'{{site_name}}' => __( 'Site name', 'apollo-core' ),
				'{{site_url}}'  => __( 'Site URL', 'apollo-core' ),
			],
			'System' => [
				'{{confirm_url}}'   => __( 'Confirmation URL (for registration)', 'apollo-core' ),
				'{{producer_name}}' => __( 'Producer/Event creator name', 'apollo-core' ),
			],
		];

		?>
		<div class="apollo-placeholders-help">
			<p><?php esc_html_e( 'Use these placeholders in your template. They will be replaced with actual values when the email is sent.', 'apollo-core' ); ?></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th style="width: 200px;"><?php esc_html_e( 'Placeholder', 'apollo-core' ); ?></th>
						<th><?php esc_html_e( 'Description', 'apollo-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $placeholders as $category => $items ) : ?>
						<tr>
							<td colspan="2" style="background: #f0f0f1; font-weight: bold;">
								<?php echo esc_html( $category ); ?>
							</td>
						</tr>
						<?php foreach ( $items as $placeholder => $description ) : ?>
							<tr>
								<td><code><?php echo esc_html( $placeholder ); ?></code></td>
								<td><?php echo esc_html( $description ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Save meta data
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function save_meta( int $post_id ): void {
		if ( get_post_type( $post_id ) !== self::POST_TYPE ) {
			return;
		}

		if ( ! isset( $_POST['apollo_email_template_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_email_template_nonce'] ) ), 'apollo_email_template_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save template slug.
		if ( isset( $_POST['apollo_template_slug'] ) ) {
			$slug = sanitize_key( $_POST['apollo_template_slug'] );
			update_post_meta( $post_id, '_apollo_template_slug', $slug );
		}

		// Save flow default.
		if ( isset( $_POST['apollo_flow_default'] ) ) {
			$flow = sanitize_key( $_POST['apollo_flow_default'] );
			update_post_meta( $post_id, '_apollo_flow_default', $flow );
		}

		// Save language.
		if ( isset( $_POST['apollo_template_language'] ) ) {
			$lang = sanitize_text_field( wp_unslash( $_POST['apollo_template_language'] ) );
			update_post_meta( $post_id, '_apollo_template_language', $lang );
		}
	}

	/**
	 * Add columns to list table
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function add_columns( array $columns ): array {
		$new_columns = [];
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'title' === $key ) {
				$new_columns['template_slug'] = __( 'Slug', 'apollo-core' );
				$new_columns['flow_default']  = __( 'Default Flow', 'apollo-core' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render column content
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'template_slug':
				$slug = get_post_meta( $post_id, '_apollo_template_slug', true );
				echo esc_html( $slug ?: '—' );

				break;

			case 'flow_default':
				$flow = get_post_meta( $post_id, '_apollo_flow_default', true );
				echo esc_html( $flow ?: '—' );

				break;
		}
	}
}

// Initialize.
Apollo_Email_Templates_CPT::init();



