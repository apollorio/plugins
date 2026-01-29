<?php
/**
 * Apollo Social - Workflow Integration Example
 * Demonstrates the complete workflow system with Canvas UI
 */

// This file shows how to integrate all components

require_once 'src/Infrastructure/Workflows/ContentWorkflow.php';
require_once 'src/Application/Groups/Moderation.php';
require_once 'src/Application/Groups/CanvasController.php';

use Apollo\Infrastructure\Workflows\ContentWorkflow;
use Apollo\Application\Groups\Moderation;
use Apollo\Application\Groups\CanvasController;

/**
 * Example: Group Creation with Workflow
 */
function apollo_create_group_example() {
	$workflow   = new ContentWorkflow();
	$moderation = new Moderation();

	// Get current user and determine initial status
	$user         = wp_get_current_user();
	$content_type = 'group';
	$context      = array( 'group_type' => 'comunidade' );
	// Could be: post, discussion, comunidade, nucleo

	// Resolve initial status based on workflow matrix
	$initial_status = $workflow->resolveStatus( $user, $content_type, $context );

	// Create group with correct initial status
	global $wpdb;
	$groups_table = $wpdb->prefix . 'apollo_groups';

	$group_data = array(
		'title'       => 'Exemplo de Grupo',
		'description' => 'Descrição do grupo exemplo',
		'type'        => 'comunidade',
		'status'      => $initial_status,
		'creator_id'  => $user->ID,
		'created_at'  => current_time( 'mysql' ),
		'updated_at'  => current_time( 'mysql' ),
	);

	$wpdb->insert( $groups_table, $group_data );
	$group_id = $wpdb->insert_id;

	// If needs moderation, submit to queue
	if ( in_array( $initial_status, array( 'pending', 'pending_review' ) ) ) {
		$moderation->submitForReview(
			$group_id,
			$user->ID,
			'group',
			array(
				'title' => $group_data['title'],
				'type'  => $group_data['type'],
			)
		);
	}

	return array(
		'group_id'         => $group_id,
		'initial_status'   => $initial_status,
		'needs_moderation' => in_array( $initial_status, array( 'pending', 'pending_review' ) ),
	);
}

/**
 * Example: Display Group Dashboard
 */
function apollo_display_user_dashboard( $user_id ) {
	$canvas = new CanvasController();

	// Get user's groups with workflow status
	$groups = $canvas->getUserGroupsDashboard( $user_id );

	echo '<div class="apollo-user-dashboard">';
	echo '<h2>Meus Grupos</h2>';

	if ( empty( $groups ) ) {
		echo '<p>Você ainda não criou nenhum grupo.</p>';
		echo '<a href="/criar-grupo/" class="apollo-btn apollo-btn-primary">Criar Primeiro Grupo</a>';
	} else {
		foreach ( $groups as $group ) {
			echo $canvas->renderGroupCard( $group );

			// Show moderation actions for editors/admins
			$current_user = wp_get_current_user();
			if ( user_can( $current_user, 'apollo_moderate' ) ) {
				echo $canvas->renderModerationActions( $group, $current_user );
			}
		}
	}

	echo '</div>';
}

/**
 * Example: AJAX Handler for Group Creation
 */
function apollo_handle_group_creation() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'apollo_create_group' ) ) {
		wp_die( 'Security check failed' );
	}

	$workflow   = new ContentWorkflow();
	$moderation = new Moderation();

	// Get form data
	$title       = sanitize_text_field( $_POST['title'] );
	$description = sanitize_textarea_field( $_POST['description'] );
	$group_type  = sanitize_text_field( $_POST['group_type'] );

	// Validate required fields
	if ( empty( $title ) || empty( $group_type ) ) {
		wp_send_json_error( 'Título e tipo são obrigatórios' );
		return;
	}

	$user    = wp_get_current_user();
	$context = array( 'group_type' => $group_type );

	// Resolve status using workflow
	$status = $workflow->resolveStatus( $user, 'group', $context );

	// Create group
	global $wpdb;
	$groups_table = $wpdb->prefix . 'apollo_groups';

	$group_data = array(
		'title'       => $title,
		'description' => $description,
		'type'        => $group_type,
		'slug'        => sanitize_title( $title ),
		'status'      => $status,
		'creator_id'  => $user->ID,
		'created_at'  => current_time( 'mysql' ),
		'updated_at'  => current_time( 'mysql' ),
	);

	$result = $wpdb->insert( $groups_table, $group_data );

	if ( $result === false ) {
		wp_send_json_error( 'Erro ao criar grupo' );
		return;
	}

	$group_id = $wpdb->insert_id;

	// Submit for moderation if needed
	$needs_moderation = in_array( $status, array( 'pending', 'pending_review' ) );
	if ( $needs_moderation ) {
		$moderation->submitForReview(
			$group_id,
			$user->ID,
			'group',
			array(
				'title'       => $title,
				'type'        => $group_type,
				'description' => $description,
			)
		);
	}

	// Return success response
	wp_send_json_success(
		array(
			'message'          => 'Grupo criado com sucesso!',
			'group_id'         => $group_id,
			'status'           => $status,
			'needs_moderation' => $needs_moderation,
			'redirect_url'     => $status === 'draft' ? "/grupo/editar/{$group_id}/" : "/grupo/{$group_data['slug']}/",
		)
	);
}

/**
 * Example: WordPress Hooks Integration
 */
function apollo_register_workflow_hooks() {
	// Register AJAX handlers
	add_action( 'wp_ajax_apollo_create_group', 'apollo_handle_group_creation' );
	add_action( 'wp_ajax_apollo_resubmit_group', 'apollo_handle_resubmit' );

	// Register REST API routes
	add_action(
		'rest_api_init',
		function () {
			$moderation_controller = new \Apollo\API\Controllers\ModerationController();
			$moderation_controller->register_routes();
		}
	);

	// Enqueue scripts and styles
	add_action(
		'wp_enqueue_scripts',
		function () {
			wp_enqueue_style(
				'apollo-moderation',
				plugin_dir_url( __FILE__ ) . 'assets/css/apollo-moderation.css',
				array(),
				'1.0.0'
			);

			wp_enqueue_script(
				'apollo-moderation',
				plugin_dir_url( __FILE__ ) . 'assets/js/apollo-moderation.js',
				array( 'jquery' ),
				'1.0.0',
				true
			);

			// Localize script with nonce
			wp_localize_script(
				'apollo-moderation',
				'apolloModeration',
				array(
					'nonce'    => wp_create_nonce( 'wp_rest' ),
					'apiUrl'   => rest_url( 'apollo/v1/' ),
					'messages' => array(
						'confirmApprove' => 'Tem certeza que deseja aprovar este grupo?',
						'confirmReject'  => 'Tem certeza que deseja rejeitar este grupo?',
						'reasonRequired' => 'Por favor, informe o motivo da rejeição.',
						'approved'       => 'Grupo aprovado com sucesso!',
						'rejected'       => 'Grupo rejeitado com sucesso!',
						'error'          => 'Erro ao processar solicitação. Tente novamente.',
					),
				)
			);
		}
	);
}

/**
 * Example: Shortcode for Group Listing
 */
function apollo_groups_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'user_id'         => get_current_user_id(),
			'status'          => 'all',
			'type'            => 'all',
			'show_moderation' => false,
		),
		$atts
	);

	if ( ! $atts['user_id'] ) {
		return '<p>Você precisa estar logado para ver seus grupos.</p>';
	}

	ob_start();
	apollo_display_user_dashboard( $atts['user_id'] );
	return ob_get_clean();
}
add_shortcode( 'apollo_groups', 'apollo_groups_shortcode' );

/**
 * Example: CLI Command Integration
 */
function apollo_test_workflow_integration() {
	// This would be called via WP-CLI: wp apollo test-integration

	echo "🧪 Testing Apollo Workflow Integration\n";
	echo "=====================================\n\n";

	$workflow   = new ContentWorkflow();
	$moderation = new Moderation();

	// Test different user roles and content types
	$test_cases = array(
		array(
			'role'         => 'subscriber',
			'content_type' => 'group',
			'context'      => array( 'group_type' => 'post' ),
		),
		array(
			'role'         => 'subscriber',
			'content_type' => 'group',
			'context'      => array( 'group_type' => 'comunidade' ),
		),
		array(
			'role'         => 'contributor',
			'content_type' => 'group',
			'context'      => array( 'group_type' => 'post' ),
		),
		array(
			'role'         => 'author',
			'content_type' => 'group',
			'context'      => array( 'group_type' => 'comunidade' ),
		),
		array(
			'role'         => 'editor',
			'content_type' => 'group',
			'context'      => array( 'group_type' => 'comunidade' ),
		),
	);

	foreach ( $test_cases as $i => $case ) {
		echo 'Test ' . ( $i + 1 ) . ": {$case['role']} creating {$case['content_type']} ({$case['context']['group_type']})\n";

		// Create test user with role
		$user = new WP_User();
		$user->add_role( $case['role'] );

		// Test workflow resolution
		$status = $workflow->resolveStatus( $user, $case['content_type'], $case['context'] );
		echo "  → Resolved status: {$status}\n";

		// Test if needs moderation
		$needs_moderation = in_array( $status, array( 'pending', 'pending_review' ) );
		echo '  → Needs moderation: ' . ( $needs_moderation ? 'Yes' : 'No' ) . "\n";

		echo "\n";
	}

	echo "✅ Integration test completed!\n";
}

// Initialize hooks
apollo_register_workflow_hooks();

/**
 * Installation Example
 */
function apollo_install_workflow_system() {
	// This would be called during plugin activation

	echo "🚀 Installing Apollo Workflow System...\n";

	// Create database tables (handled by Schema class)
	// Set up user capabilities (handled by Caps class)
	// Configure workflow rules (handled by ContentWorkflow class)

	echo "✅ Installation completed!\n";
	echo "\n📋 Next steps:\n";
	echo "1. Run: wp apollo setup-permissions\n";
	echo "2. Run: wp apollo seed --users --seasons\n";
	echo "3. Test: wp apollo test-matrix\n";
	echo "4. Use shortcode: [apollo_groups]\n";
	echo "5. Access moderation at: /wp-admin/admin.php?page=apollo-moderation\n";
}

// Example usage in templates:
/*
<?php
// In your theme or plugin template file

$canvas = new Apollo\Application\Groups\CanvasController();
$user_groups = $canvas->getUserGroupsDashboard(get_current_user_id());

foreach ($user_groups as $group) {
	echo $canvas->renderGroupCard($group);

	// Show status badge
	echo $canvas->renderStatusBadge($group);

	// Show moderation actions for editors
	if (current_user_can('apollo_moderate')) {
		echo $canvas->renderModerationActions($group, wp_get_current_user());
	}
}
?>

<!-- Include styles and scripts -->
<link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__) . 'assets/css/apollo-moderation.css'; ?>">
<script src="<?php echo plugin_dir_url(__FILE__) . 'assets/js/apollo-moderation.js'; ?>"></script>
<script>
// Set global nonce for API calls
window.apolloNonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
</script>
*/
