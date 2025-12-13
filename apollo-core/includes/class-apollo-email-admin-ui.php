<?php
declare(strict_types=1);

/**
 * Apollo Email Admin UI
 *
 * Modular admin interface for configuring email flows and templates.
 *
 * @package Apollo_Core
 * @since 3.0.0
 */
class Apollo_Email_Admin_UI {

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_apollo_email_save_flow', array( __CLASS__, 'ajax_save_flow' ) );
		add_action( 'wp_ajax_apollo_email_send_test', array( __CLASS__, 'ajax_send_test' ) );
		add_action( 'wp_ajax_apollo_email_preview', array( __CLASS__, 'ajax_preview' ) );
	}

	/**
	 * Add admin menu
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		add_submenu_page(
			'apollo-core-hub',
			__( 'Emails', 'apollo-core' ),
			__( '📧 Emails', 'apollo-core' ),
			'manage_options',
			'apollo-emails',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public static function enqueue_scripts( string $hook ): void {
		if ( 'apollo-core-hub_page_apollo-emails' !== $hook ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
	}

	/**
	 * Render admin page
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied', 'apollo-core' ) );
		}

		$flows = self::get_available_flows();
		$templates = self::get_templates();
		$current_flows = get_option( 'apollo_email_flows', array() );

		?>
		<div class="wrap apollo-email-admin">
			<h1><?php esc_html_e( '📧 Apollo Email Configuration', 'apollo-core' ); ?></h1>

			<div class="apollo-email-tabs" style="margin-top: 20px;">
				<nav class="nav-tab-wrapper">
					<a href="#flows" class="nav-tab nav-tab-active"><?php esc_html_e( 'Email Flows', 'apollo-core' ); ?></a>
					<a href="#templates" class="nav-tab"><?php esc_html_e( 'Templates', 'apollo-core' ); ?></a>
					<a href="#test" class="nav-tab"><?php esc_html_e( 'Test Email', 'apollo-core' ); ?></a>
				</nav>

				<div id="flows" class="tab-content" style="display: block;">
					<?php self::render_flows_section( $flows, $templates, $current_flows ); ?>
				</div>

				<div id="templates" class="tab-content" style="display: none;">
					<?php self::render_templates_section( $templates ); ?>
				</div>

				<div id="test" class="tab-content" style="display: none;">
					<?php self::render_test_section( $flows ); ?>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('.nav-tab').on('click', function(e) {
				e.preventDefault();
				var target = $(this).attr('href');
				$('.nav-tab').removeClass('nav-tab-active');
				$(this).addClass('nav-tab-active');
				$('.tab-content').hide();
				$(target).show();
			});
		});
		</script>
		<?php
	}

	/**
	 * Render flows section
	 *
	 * @param array $flows         Available flows.
	 * @param array $templates     Available templates.
	 * @param array $current_flows Current flow configurations.
	 * @return void
	 */
	private static function render_flows_section( array $flows, array $templates, array $current_flows ): void {
		?>
		<div class="card" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Configure Email Flows', 'apollo-core' ); ?></h2>
			<p><?php esc_html_e( 'Configure which template and settings to use for each email flow.', 'apollo-core' ); ?></p>

			<?php foreach ( $flows as $flow_slug => $flow_data ) : ?>
				<?php
				$flow_config = $current_flows[ $flow_slug ] ?? array();
				$enabled = ! empty( $flow_config['enabled'] );
				$template_id = $flow_config['template_id'] ?? '';
				$subject = $flow_config['subject'] ?? $flow_data['default_subject'];
				$extra_recipients = $flow_config['extra_recipients'] ?? '';
				?>
				<div class="apollo-flow-config" style="border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 4px;">
					<h3><?php echo esc_html( $flow_data['name'] ); ?></h3>
					<p class="description"><?php echo esc_html( $flow_data['description'] ); ?></p>

					<table class="form-table">
						<tr>
							<th><label><?php esc_html_e( 'Enabled', 'apollo-core' ); ?></label></th>
							<td>
								<input type="checkbox" 
									name="flow_<?php echo esc_attr( $flow_slug ); ?>_enabled" 
									value="1" 
									<?php checked( $enabled ); ?>>
								<label><?php esc_html_e( 'Enable this email flow', 'apollo-core' ); ?></label>
							</td>
						</tr>
						<tr>
							<th><label><?php esc_html_e( 'Template', 'apollo-core' ); ?></label></th>
							<td>
								<select name="flow_<?php echo esc_attr( $flow_slug ); ?>_template" class="regular-text">
									<option value=""><?php esc_html_e( 'Default Template', 'apollo-core' ); ?></option>
									<?php foreach ( $templates as $template ) : ?>
										<option value="<?php echo esc_attr( $template->ID ); ?>" 
											<?php selected( $template_id, $template->ID ); ?>>
											<?php echo esc_html( $template->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label><?php esc_html_e( 'Subject', 'apollo-core' ); ?></label></th>
							<td>
								<input type="text" 
									name="flow_<?php echo esc_attr( $flow_slug ); ?>_subject" 
									value="<?php echo esc_attr( $subject ); ?>" 
									class="regular-text">
								<p class="description"><?php esc_html_e( 'Use placeholders like {{user_name}}, {{event_title}}, etc.', 'apollo-core' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label><?php esc_html_e( 'Extra Recipients', 'apollo-core' ); ?></label></th>
							<td>
								<input type="text" 
									name="flow_<?php echo esc_attr( $flow_slug ); ?>_extra_recipients" 
									value="<?php echo esc_attr( $extra_recipients ); ?>" 
									class="regular-text">
								<p class="description"><?php esc_html_e( 'Comma-separated email addresses or user roles (e.g., admin, moderators)', 'apollo-core' ); ?></p>
							</td>
						</tr>
					</table>

					<p>
						<button type="button" 
							class="button button-secondary apollo-preview-flow" 
							data-flow="<?php echo esc_attr( $flow_slug ); ?>">
							<?php esc_html_e( 'Preview', 'apollo-core' ); ?>
						</button>
						<button type="button" 
							class="button button-primary apollo-save-flow" 
							data-flow="<?php echo esc_attr( $flow_slug ); ?>">
							<?php esc_html_e( 'Save Flow', 'apollo-core' ); ?>
						</button>
					</p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render templates section
	 *
	 * @param array $templates Templates list.
	 * @return void
	 */
	private static function render_templates_section( array $templates ): void {
		?>
		<div class="card" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Email Templates', 'apollo-core' ); ?></h2>
			<p>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=apollo_email_template' ) ); ?>" 
					class="button button-primary">
					<?php esc_html_e( 'Create New Template', 'apollo-core' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=apollo_email_template' ) ); ?>" 
					class="button">
					<?php esc_html_e( 'Manage Templates', 'apollo-core' ); ?>
				</a>
			</p>

			<?php if ( empty( $templates ) ) : ?>
				<p><?php esc_html_e( 'No templates created yet. Create your first template to customize email designs.', 'apollo-core' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Template', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Slug', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Default Flow', 'apollo-core' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'apollo-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $templates as $template ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $template->post_title ); ?></strong></td>
								<td><code><?php echo esc_html( get_post_meta( $template->ID, '_apollo_template_slug', true ) ?: '—' ); ?></code></td>
								<td><?php echo esc_html( get_post_meta( $template->ID, '_apollo_flow_default', true ) ?: '—' ); ?></td>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $template->ID ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Edit', 'apollo-core' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render test section
	 *
	 * @param array $flows Available flows.
	 * @return void
	 */
	private static function render_test_section( array $flows ): void {
		$current_user = wp_get_current_user();
		?>
		<div class="card" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Send Test Email', 'apollo-core' ); ?></h2>
			<p><?php esc_html_e( 'Test email sending with sample data to verify your configuration.', 'apollo-core' ); ?></p>

			<table class="form-table">
				<tr>
					<th><label for="test_flow"><?php esc_html_e( 'Email Flow', 'apollo-core' ); ?></label></th>
					<td>
						<select id="test_flow" class="regular-text">
							<option value=""><?php esc_html_e( 'Select a flow...', 'apollo-core' ); ?></option>
							<?php foreach ( $flows as $flow_slug => $flow_data ) : ?>
								<option value="<?php echo esc_attr( $flow_slug ); ?>">
									<?php echo esc_html( $flow_data['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="test_email"><?php esc_html_e( 'Test Email Address', 'apollo-core' ); ?></label></th>
					<td>
						<input type="email" 
							id="test_email" 
							value="<?php echo esc_attr( $current_user->user_email ); ?>" 
							class="regular-text">
						<p class="description"><?php esc_html_e( 'Email address to send test to', 'apollo-core' ); ?></p>
					</td>
				</tr>
			</table>

			<p>
				<button type="button" 
					id="apollo-send-test-email" 
					class="button button-primary">
					<?php esc_html_e( 'Send Test Email', 'apollo-core' ); ?>
				</button>
			</p>

			<div id="test-result" style="margin-top: 20px; display: none;"></div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#apollo-send-test-email').on('click', function() {
				var flow = $('#test_flow').val();
				var email = $('#test_email').val();

				if (!flow || !email) {
					alert('<?php echo esc_js( __( 'Please select a flow and enter an email address', 'apollo-core' ) ); ?>');
					return;
				}

				$(this).prop('disabled', true).text('<?php echo esc_js( __( 'Sending...', 'apollo-core' ) ); ?>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'apollo_email_send_test',
						nonce: '<?php echo esc_js( wp_create_nonce( 'apollo_email_test' ) ); ?>',
						flow: flow,
						email: email
					},
					success: function(response) {
						$('#test-result').show().html(
							'<div class="notice notice-' + (response.success ? 'success' : 'error') + '"><p>' + 
							(response.data.message || 'Unknown error') + 
							'</p></div>'
						);
					},
					error: function() {
						$('#test-result').show().html(
							'<div class="notice notice-error"><p><?php echo esc_js( __( 'Failed to send test email', 'apollo-core' ) ); ?></p></div>'
						);
					},
					complete: function() {
						$('#apollo-send-test-email').prop('disabled', false).text('<?php echo esc_js( __( 'Send Test Email', 'apollo-core' ) ); ?>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Get available flows
	 *
	 * @return array
	 */
	private static function get_available_flows(): array {
		return array(
			'registration_confirm' => array(
				'name'            => __( 'Registration Confirmation', 'apollo-core' ),
				'description'     => __( 'Sent to new users with a confirmation link', 'apollo-core' ),
				'default_subject' => __( 'Bem-vindo ao {{site_name}}! Confirme sua conta', 'apollo-core' ),
			),
			'producer_notify'      => array(
				'name'            => __( 'Producer Notification', 'apollo-core' ),
				'description'     => __( 'Sent to party producers when relevant event actions happen', 'apollo-core' ),
				'default_subject' => __( 'Novo evento: {{event_title}}', 'apollo-core' ),
			),
		);
	}

	/**
	 * Get templates
	 *
	 * @return array
	 */
	private static function get_templates(): array {
		$query = new WP_Query(
			array(
				'post_type'      => 'apollo_email_template',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		return $query->posts;
	}

	/**
	 * AJAX: Save flow configuration
	 *
	 * @return void
	 */
	public static function ajax_save_flow(): void {
		check_ajax_referer( 'apollo_email_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'apollo-core' ) ) );
		}

		$flow_slug = sanitize_key( $_POST['flow'] ?? '' );
		if ( empty( $flow_slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid flow', 'apollo-core' ) ) );
		}

		$flows = get_option( 'apollo_email_flows', array() );

		$flows[ $flow_slug ] = array(
			'enabled'         => ! empty( $_POST['enabled'] ),
			'template_id'     => absint( $_POST['template_id'] ?? 0 ),
			'subject'         => sanitize_text_field( $_POST['subject'] ?? '' ),
			'extra_recipients' => sanitize_text_field( $_POST['extra_recipients'] ?? '' ),
		);

		update_option( 'apollo_email_flows', $flows );

		wp_send_json_success( array( 'message' => __( 'Flow saved successfully', 'apollo-core' ) ) );
	}

	/**
	 * AJAX: Send test email
	 *
	 * @return void
	 */
	public static function ajax_send_test(): void {
		check_ajax_referer( 'apollo_email_test', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'apollo-core' ) ) );
		}

		$flow = sanitize_key( $_POST['flow'] ?? '' );
		$email = sanitize_email( $_POST['email'] ?? '' );

		if ( empty( $flow ) || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid flow or email address', 'apollo-core' ) ) );
		}

		$email_service = Apollo_Email_Service::instance();
		$result = $email_service->send_test( $flow, $email );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Test email sent successfully', 'apollo-core' ) ) );
	}

	/**
	 * AJAX: Preview email
	 *
	 * @return void
	 */
	public static function ajax_preview(): void {
		check_ajax_referer( 'apollo_email_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'apollo-core' ) ) );
		}

		$flow = sanitize_key( $_POST['flow'] ?? '' );
		if ( empty( $flow ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid flow', 'apollo-core' ) ) );
		}

		$email_service = Apollo_Email_Service::instance();
		$sample_variables = $email_service->get_sample_variables( $flow );
		
		// Get flow config to determine template.
		$flow_config = get_option( 'apollo_email_flows', array() );
		$template_id = $flow_config[ $flow ]['template_id'] ?? '';
		
		if ( $template_id ) {
			$template_html = $email_service->load_template( (string) $template_id, $sample_variables );
		} else {
			$template_html = $email_service->load_template( $flow, $sample_variables );
		}

		if ( is_wp_error( $template_html ) ) {
			wp_send_json_error( array( 'message' => $template_html->get_error_message() ) );
		}

		wp_send_json_success( array( 'html' => $template_html ) );
	}
}

// Initialize.
Apollo_Email_Admin_UI::init();

