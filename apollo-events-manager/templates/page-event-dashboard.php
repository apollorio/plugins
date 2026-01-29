<?php
/**
 * Template: Event Dashboard Public Page
 * PHASE 5: Migrated to ViewModel Architecture
 * Matches approved design: social - feed main.html
 * Uses ViewModel data transformation and shared partials.
 *
 * @package Apollo_Events_Manager
 */

defined( 'ABSPATH' ) || exit;

// Create ViewModel for dashboard access.
$view_model = Apollo_ViewModel_Factory::create_from_data( null, 'dashboard_access' );

// Get template data using the appropriate method.
if ( $view_model && method_exists( $view_model, 'get_dashboard_access_data' ) ) {
	$template_data = $view_model->get_dashboard_access_data();
} elseif ( $view_model && method_exists( $view_model, 'get_template_data' ) ) {
	$template_data = $view_model->get_template_data();
} else {
	// Fallback to basic data structure.
	$template_data = array(
		'title'           => __( 'Event Dashboard', 'apollo-events-manager' ),
		'should_redirect' => false,
		'redirect_url'    => '',
		'user_status'     => is_user_logged_in() ? 'logged_in_no_permission' : 'not_logged_in',
		'message'         => is_user_logged_in()
			? __( 'You do not have permission to access this page.', 'apollo-events-manager' )
			: __( 'Please log in to access the event dashboard.', 'apollo-events-manager' ),
		'home_url'        => home_url(),
		'home_link_text'  => __( 'Go to Homepage', 'apollo-events-manager' ),
		'login_url'       => wp_login_url( get_permalink() ),
		'login_link_text' => __( 'Log In', 'apollo-events-manager' ),
	);
}

// Load shared partials.
$template_loader = new Apollo_Template_Loader();
$template_loader->load_partial( 'assets' );

// If user is logged in and has permissions, redirect to admin dashboard.
if ( $template_data['should_redirect'] ) {
	wp_safe_redirect( $template_data['redirect_url'] );
	exit;
}

// Get header (maintain WordPress theme integration).
get_header();
?>

<div class="apollo-event-dashboard-public">
	<div class="apollo-container">
		<div class="apollo-dashboard-message">
			<h1><?php echo esc_html( $template_data['title'] ); ?></h1>

			<?php if ( 'logged_in_no_permission' === $template_data['user_status'] ) : ?>
				<p><?php echo esc_html( $template_data['message'] ); ?></p>
				<p>
					<a href="<?php echo esc_url( $template_data['home_url'] ); ?>" class="button">
						<?php echo esc_html( $template_data['home_link_text'] ); ?>
					</a>
				</p>
			<?php elseif ( 'not_logged_in' === $template_data['user_status'] ) : ?>
				<p><?php echo esc_html( $template_data['message'] ); ?></p>
				<p>
					<a href="<?php echo esc_url( $template_data['login_url'] ); ?>" class="button button-primary">
						<?php echo esc_html( $template_data['login_link_text'] ); ?>
					</a>
					<a href="<?php echo esc_url( $template_data['home_url'] ); ?>" class="button">
						<?php echo esc_html( $template_data['home_link_text'] ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	</div>
</div>

<style>
	/* Dashboard message styling */
	.apollo-event-dashboard-public {
		padding: 2rem 0;
		min-height: 60vh;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.apollo-container {
		max-width: 600px;
		margin: 0 auto;
		padding: 0 2rem;
	}

	.apollo-dashboard-message {
		text-align: center;
		background: var(--ap-bg, #fff);
		padding: 3rem 2rem;
		border-radius: var(--ap-radius-lg, 12px);
		box-shadow: 0 4px 20px rgba(0,0,0,0.1);
	}

	.apollo-dashboard-message h1 {
		color: var(--ap-text-dark, #333);
		font-size: 2rem;
		font-weight: 700;
		margin-bottom: 1.5rem;
	}

	.apollo-dashboard-message p {
		color: var(--ap-text-muted, #666);
		font-size: 1.1rem;
		line-height: 1.6;
		margin-bottom: 2rem;
	}

	.apollo-dashboard-message .button {
		display: inline-block;
		padding: 0.75rem 1.5rem;
		border-radius: var(--ap-radius-lg, 12px);
		text-decoration: none;
		font-weight: 500;
		transition: all 0.2s ease;
		margin: 0.5rem;
	}

	.apollo-dashboard-message .button-primary {
		background: var(--ap-blue, #007bff);
		color: white;
		border: 2px solid var(--ap-blue, #007bff);
	}

	.apollo-dashboard-message .button-primary:hover {
		background: var(--ap-blue-hover, #0056b3);
		border-color: var(--ap-blue-hover, #0056b3);
		transform: translateY(-1px);
	}

	.apollo-dashboard-message .button {
		background: var(--ap-bg-muted, #f5f5f5);
		color: var(--ap-text-dark, #333);
		border: 2px solid var(--ap-bg-muted, #f5f5f5);
	}

	.apollo-dashboard-message .button:hover {
		background: var(--ap-border, #e9ecef);
		border-color: var(--ap-border, #e9ecef);
		transform: translateY(-1px);
	}

	/* Mobile responsive adjustments */
	@media (max-width: 768px) {
		.apollo-event-dashboard-public {
			padding: 1rem 0;
		}

		.apollo-container {
			padding: 0 1rem;
		}

		.apollo-dashboard-message {
			padding: 2rem 1.5rem;
		}

		.apollo-dashboard-message h1 {
			font-size: 1.5rem;
		}

		.apollo-dashboard-message p {
			font-size: 1rem;
		}

		.apollo-dashboard-message .button {
			display: block;
			width: 100%;
			margin: 0.5rem 0;
		}
	}
</style>

<?php
get_footer();
?>
