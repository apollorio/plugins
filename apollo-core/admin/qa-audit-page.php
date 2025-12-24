<?php
/**
 * Apollo QA Audit Page
 *
 * Admin page to view and run QA audits
 *
 * @package Apollo_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add QA Audit admin page
 */
function apollo_add_qa_audit_page() {
	add_submenu_page(
		'apollo-control',
		__( 'QA Audit', 'apollo-core' ),
		__( 'QA Audit', 'apollo-core' ),
		'manage_options',
		'apollo-qa-audit',
		'apollo_render_qa_audit_page'
	);
}
add_action( 'admin_menu', 'apollo_add_qa_audit_page', 20 );

/**
 * Render QA Audit page
 */
function apollo_render_qa_audit_page() {
	require_once APOLLO_CORE_PLUGIN_DIR . 'tests/audit-qa-sheet.php';

	$runner  = new Apollo_QA_Audit_Runner();
	$results = $runner->run_audit();

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Apollo::Rio - Quality Assurance & Audit', 'apollo-core' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Comprehensive audit report for all Apollo modules.', 'apollo-core' ); ?></p>

		<?php echo $runner->generate_report( $results ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="apollo-qa-actions">
			<button type="button" class="button button-primary" id="apollo-run-audit">
				<?php esc_html_e( 'Run Full Audit', 'apollo-core' ); ?>
			</button>
			<button type="button" class="button" id="apollo-export-audit">
				<?php esc_html_e( 'Export Report', 'apollo-core' ); ?>
			</button>
		</div>
	</div>

	<style>
		.apollo-qa-report table {
			margin-top: 20px;
		}
		.apollo-qa-report th {
			background: #f0f0f1;
			font-weight: 600;
		}
		.apollo-qa-actions {
			margin-top: 20px;
		}
	</style>
	<?php
}

