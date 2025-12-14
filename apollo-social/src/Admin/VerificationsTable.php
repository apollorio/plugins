<?php
/**
 * Verifications Table Admin Interface.
 *
 * Admin interface for managing Instagram verifications with CodePen-inspired design.
 *
 * @package Apollo\Admin
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */

namespace Apollo\Admin;

use Apollo\Application\Users\UserProfileRepository;

/**
 * VerificationsTable class.
 *
 * Handles the admin interface for user verification management.
 */
class VerificationsTable {

	/**
	 * User profile repository instance.
	 *
	 * @var UserProfileRepository
	 */
	private UserProfileRepository $userRepo;

	/**
	 * Constructor - initialize repository.
	 */
	public function __construct() {
		$this->userRepo = new UserProfileRepository();
	}

	/**
	 * Initialize admin page
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'addAdminMenu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
		add_action( 'wp_ajax_apollo_verify_user', [ $this, 'handleVerifyUser' ] );
		add_action( 'wp_ajax_apollo_reject_user', [ $this, 'handleRejectUser' ] );
		add_action( 'wp_ajax_apollo_get_verification_details', [ $this, 'getVerificationDetails' ] );
	}

	/**
	 * Add admin menu item
	 */
	public function addAdminMenu(): void {
		add_menu_page(
			'Verificações Apollo',
			'Verificações',
			'manage_options',
			'apollo-verifications',
			[ $this, 'renderVerificationsPage' ],
			'dashicons-shield-alt',
			30
		);

		add_submenu_page(
			'apollo-verifications',
			'Analytics',
			'Analytics',
			'manage_options',
			'apollo-verification-analytics',
			[ $this, 'renderAnalyticsPage' ]
		);
	}

	/**
	 * Enqueue CSS and JS assets.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueueAssets( string $hook ): void {
		if ( ! str_contains( $hook, 'apollo-verification' ) ) {
			return;
		}

		// Enqueue CSS.
		wp_enqueue_style(
			'apollo-verifications-admin',
			plugin_dir_url( __FILE__ ) . '../../assets/admin/verifications.css',
			[],
			'1.0.0'
		);

		// Enqueue JS.
		wp_enqueue_script(
			'apollo-verifications-admin',
			plugin_dir_url( __FILE__ ) . '../../assets/admin/verifications.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);

		// Localize script.
		wp_localize_script(
			'apollo-verifications-admin',
			'apolloAdmin',
			[
				'nonce'    => wp_create_nonce( 'apollo_admin_nonce' ),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'messages' => [
					'verify_confirm' => 'Tem certeza que deseja verificar este usuário?',
					'reject_confirm' => 'Tem certeza que deseja rejeitar esta verificação?',
					'processing'     => 'Processando...',
					'error'          => 'Erro ao processar solicitação',
					'success_verify' => 'Usuário verificado com sucesso',
					'success_reject' => 'Verificação rejeitada',
				],
			]
		);
	}

	/**
	 * Render main verifications page.
	 *
	 * @return void
	 */
	public function renderVerificationsPage(): void {
		// Get verification data.
		$verifications = $this->userRepo->getUsersAwaitingVerification( 50 );
		$stats         = $this->userRepo->getVerificationStats();

		// Get filters from URL (admin page, nonce not required for GET filters).
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter.
		$current_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'all';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter.
		$search_query = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';

		?>
		<div class="apollo-verifications-container">
			<!-- Header Section -->
			<div class="apollo-verifications-header">
				<h1 class="apollo-page-title">
					<span class="apollo-icon">🛡️</span>
					Verificações Apollo
				</h1>

				<div class="apollo-stats-grid">
					<div class="apollo-stat-card awaiting">
						<div class="stat-number"><?php echo esc_html( $stats['awaiting_instagram_verify'] + $stats['assets_submitted'] ); ?></div>
						<div class="stat-label">Pendentes</div>
					</div>
					<div class="apollo-stat-card verified">
						<div class="stat-number"><?php echo esc_html( $stats['verified'] ); ?></div>
						<div class="stat-label">Verificados</div>
					</div>
					<div class="apollo-stat-card rejected">
						<div class="stat-number"><?php echo esc_html( $stats['rejected'] ); ?></div>
						<div class="stat-label">Rejeitados</div>
					</div>
					<div class="apollo-stat-card total">
						<div class="stat-number"><?php echo esc_html( $stats['total'] ); ?></div>
						<div class="stat-label">Total</div>
					</div>
				</div>
			</div>

			<!-- Filters Section -->
			<div class="apollo-filters-section">
				<form method="GET" class="apollo-filter-form">
					<input type="hidden" name="page" value="apollo-verifications">

					<div class="filter-group">
						<label for="status-filter">Status:</label>
						<select name="status" id="status-filter" class="apollo-select">
							<option value="all" <?php selected( $current_status, 'all' ); ?>>Todos</option>
							<option value="awaiting_instagram_verify" <?php selected( $current_status, 'awaiting_instagram_verify' ); ?>>Aguardando Assets</option>
							<option value="assets_submitted" <?php selected( $current_status, 'assets_submitted' ); ?>>Assets Enviados</option>
							<option value="verified" <?php selected( $current_status, 'verified' ); ?>>Verificados</option>
							<option value="rejected" <?php selected( $current_status, 'rejected' ); ?>>Rejeitados</option>
						</select>
					</div>

					<div class="filter-group">
						<label for="search-input">Buscar:</label>
						<input
							type="text"
							name="search"
							id="search-input"
							class="apollo-input"
							placeholder="Nome, email, Instagram..."
							value="<?php echo esc_attr( $search_query ); ?>"
						>
					</div>

					<button type="submit" class="apollo-btn primary">
						<span class="btn-icon">🔍</span>
						Filtrar
					</button>

					<?php if ( 'all' !== $current_status || $search_query ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-verifications' ) ); ?>" class="apollo-btn secondary">
							Limpar Filtros
						</a>
					<?php endif; ?>
				</form>
			</div>

			<!-- Verifications Grid -->
			<div class="apollo-verifications-grid">
				<?php if ( empty( $verifications ) ) : ?>
					<div class="apollo-empty-state">
						<div class="empty-icon">📭</div>
						<h3>Nenhuma verificação encontrada</h3>
						<p>Não há verificações que correspondam aos filtros aplicados.</p>
					</div>
				<?php else : ?>
					<?php foreach ( $verifications as $verification ) : ?>
						<?php $this->renderVerificationCard( $verification ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

		<!-- Verification Modal -->
		<div id="apollo-verification-modal" class="apollo-modal" style="display: none;">
			<div class="apollo-modal-content">
				<div class="apollo-modal-header">
					<h3 id="modal-title">Detalhes da Verificação</h3>
					<button class="apollo-modal-close">&times;</button>
				</div>
				<div class="apollo-modal-body" id="modal-body">
					<!-- Content loaded via AJAX -->
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render individual verification card.
	 *
	 * @param array $verification Verification data array.
	 * @return void
	 */
	private function renderVerificationCard( array $verification ): void {
		$status_class = $this->getStatusClass( $verification['verify_status'] );
		$metadata     = $verification['metadata'] ?? [];
		$assets       = $verification['verify_assets'] ?? [];
		$display_name = ! empty( $verification['display_name'] ) ? $verification['display_name'] : $verification['user_login'];

		?>
		<div class="apollo-verification-card <?php echo esc_attr( $status_class ); ?>" data-user-id="<?php echo esc_attr( $verification['user_id'] ); ?>">
			<div class="card-header">
				<div class="user-info">
					<div class="user-avatar">
						<?php echo esc_html( strtoupper( substr( $display_name, 0, 2 ) ) ); ?>
					</div>
					<div class="user-details">
						<h4 class="user-name"><?php echo esc_html( $display_name ); ?></h4>
						<div class="user-meta">
							<span class="email"><?php echo esc_html( $verification['user_email'] ); ?></span>
							<span class="separator">•</span>
							<span class="registered"><?php echo esc_html( gmdate( 'd/m/Y', strtotime( $verification['user_registered'] ) ) ); ?></span>
						</div>
					</div>
				</div>

				<div class="verification-status">
					<span class="status-badge <?php echo esc_attr( $status_class ); ?>">
						<?php echo esc_html( $this->getStatusLabel( $verification['verify_status'] ) ); ?>
					</span>
				</div>
			</div>

			<div class="card-body">
				<div class="verification-info">
					<div class="info-row">
						<span class="label">Instagram:</span>
						<span class="value">@<?php echo esc_html( $verification['instagram_username'] ); ?></span>
					</div>
					<div class="info-row">
						<span class="label">WhatsApp:</span>
						<span class="value"><?php echo esc_html( $verification['whatsapp_number'] ); ?></span>
					</div>
					<div class="info-row">
						<span class="label">Token:</span>
						<span class="value token"><?php echo esc_html( $verification['verify_token'] ); ?></span>
					</div>
					<?php if ( ! empty( $metadata['industry'] ) ) : ?>
						<div class="info-row">
							<span class="label">Indústria:</span>
							<span class="value"><?php echo esc_html( $metadata['industry'] ); ?></span>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $assets ) ) : ?>
					<div class="verification-assets">
						<h5>Assets Enviados (<?php echo count( $assets ); ?>)</h5>
						<div class="assets-preview">
							<?php foreach ( array_slice( $assets, 0, 3 ) as $index => $asset ) : ?>
								<div class="asset-thumbnail" title="<?php echo esc_attr( $asset['original_name'] ); ?>">
									📷
								</div>
							<?php endforeach; ?>
							<?php if ( count( $assets ) > 3 ) : ?>
								<div class="asset-more">+<?php echo esc_html( count( $assets ) - 3 ); ?></div>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="card-actions">
				<button class="apollo-btn small view-details" data-user-id="<?php echo esc_attr( $verification['user_id'] ); ?>">
					<span class="btn-icon">👁️</span>
					Ver Detalhes
				</button>

				<?php if ( in_array( $verification['verify_status'], [ 'dm_requested', 'awaiting_instagram_verify' ], true ) ) : ?>
					<button class="apollo-btn small success verify-user" data-user-id="<?php echo esc_attr( $verification['user_id'] ); ?>" title="Marcar como verificado (DM OK)">
						<span class="btn-icon">✅</span>
						Marcar como verificado (DM OK)
					</button>
					<button class="apollo-btn small warning cancel-verification" data-user-id="<?php echo esc_attr( $verification['user_id'] ); ?>" title="Cancelar/Esperando DM">
						<span class="btn-icon">⏸️</span>
						Cancelar/Esperando DM
					</button>
				<?php endif; ?>

				<?php if ( in_array( $verification['verify_status'], [ 'dm_requested', 'awaiting_instagram_verify' ], true ) ) : ?>
					<button class="apollo-btn small danger reject-user" data-user-id="<?php echo esc_attr( $verification['user_id'] ); ?>" title="Rejeitar (opcional)">
						<span class="btn-icon">❌</span>
						Rejeitar (opcional)
					</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render analytics page
	 */
	public function renderAnalyticsPage(): void {
		$analytics = $this->userRepo->getOnboardingAnalytics( '30d' );
		$stats     = $this->userRepo->getVerificationStats();

		?>
		<div class="apollo-analytics-container">
			<h1 class="apollo-page-title">
				<span class="apollo-icon">📊</span>
				Analytics de Verificação
			</h1>

			<div class="analytics-grid">
				<!-- Completion Rate Card -->
				<div class="analytics-card">
					<h3>Taxa de Conclusão (30d)</h3>
					<div class="chart-container">
						<!-- Chart would be rendered here with Chart.js -->
						<div class="chart-placeholder">
							📈 Gráfico de conclusões ao longo do tempo
						</div>
					</div>
				</div>

				<!-- Industry Distribution -->
				<div class="analytics-card">
					<h3>Distribuição por Indústria</h3>
					<div class="industry-list">
					<?php foreach ( $analytics['industry_distribution'] as $industry ) : ?>
						<div class="industry-item">
							<span class="industry-name"><?php echo esc_html( $industry['industry'] ); ?></span>
							<span class="industry-count"><?php echo esc_html( $industry['count'] ); ?></span>
						</div>
					<?php endforeach; ?>
					</div>
				</div>

				<!-- Performance Metrics -->
				<div class="analytics-card">
					<h3>Métricas de Performance</h3>
					<div class="metrics-grid">
						<div class="metric">
							<div class="metric-value"><?php echo esc_html( round( ( $stats['verified'] / max( $stats['total'], 1 ) ) * 100, 1 ) ); ?>%</div>
							<div class="metric-label">Taxa de Aprovação</div>
						</div>
						<div class="metric">
							<div class="metric-value"><?php echo esc_html( $stats['awaiting_instagram_verify'] + $stats['assets_submitted'] ); ?></div>
							<div class="metric-label">Fila de Aprovação</div>
						</div>
						<div class="metric">
							<div class="metric-value"><?php echo esc_html( round( ( $stats['rejected'] / max( $stats['total'], 1 ) ) * 100, 1 ) ); ?>%</div>
							<div class="metric-label">Taxa de Rejeição</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle verify user AJAX request.
	 *
	 * @return void
	 */
	public function handleVerifyUser(): void {
		check_ajax_referer( 'apollo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_send_json(
				[
					'success' => false,
					'message' => 'Invalid user ID',
				]
			);

			return;
		}
		$result = $this->verifyUser( $user_id );

		wp_send_json( $result );
	}

	/**
	 * Handle reject user AJAX request.
	 *
	 * @return void
	 */
	public function handleRejectUser(): void {
		check_ajax_referer( 'apollo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_send_json(
				[
					'success' => false,
					'message' => 'Invalid user ID',
				]
			);

			return;
		}
		$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';

		$result = $this->rejectUser( $user_id, $reason );

		wp_send_json( $result );
	}

	/**
	 * Get verification details for modal.
	 *
	 * @return void
	 */
	public function getVerificationDetails(): void {
		check_ajax_referer( 'apollo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_send_json(
				[
					'success' => false,
					'message' => 'Invalid user ID',
				]
			);

			return;
		}
		$verification = $this->getVerificationData( $user_id );

		if ( ! $verification ) {
			wp_send_json(
				[
					'success' => false,
					'message' => 'Verificação não encontrada',
				]
			);

			return;
		}

		ob_start();
		$this->renderVerificationModal( $verification );
		$html = ob_get_clean();

		wp_send_json(
			[
				'success' => true,
				'html'    => $html,
			]
		);
	}

	/**
	 * Verify user.
	 *
	 * @param int $user_id User ID to verify.
	 * @return array Result array with success status and message.
	 */
	private function verifyUser( int $user_id ): array {
		global $wpdb;

		try {
			$verification_table = $wpdb->prefix . 'apollo_verifications';

			// Update verification status.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
			$updated = $wpdb->update(
				$verification_table,
				[
					'verify_status' => 'verified',
					'reviewed_at'   => current_time( 'mysql' ),
					'reviewer_id'   => get_current_user_id(),
				],
				[ 'user_id' => $user_id ],
				[ '%s', '%s', '%d' ],
				[ '%d' ]
			);

			if ( false === $updated ) {
				return [
					'success' => false,
					'message' => 'Erro ao atualizar banco de dados',
				];
			}

			// Update user meta.
			update_user_meta( $user_id, 'apollo_verify_status', 'verified' );
			update_user_meta( $user_id, 'apollo_verified_at', current_time( 'mysql' ) );

			// Log action.
			$this->logAdminAction( $user_id, 'user_verified' );

			return [
				'success' => true,
				'message' => 'Usuário verificado com sucesso',
			];

		} catch ( \Exception $e ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'Error verifying user: ' . $e->getMessage() );

			return [
				'success' => false,
				'message' => 'Erro interno',
			];
		}//end try
	}

	/**
	 * Reject user verification.
	 *
	 * @param int    $user_id User ID to reject.
	 * @param string $reason  Rejection reason.
	 * @return array Result array with success status and message.
	 */
	private function rejectUser( int $user_id, string $reason ): array {
		global $wpdb;

		try {
			$verification_table = $wpdb->prefix . 'apollo_verifications';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
			$updated = $wpdb->update(
				$verification_table,
				[
					'verify_status'    => 'rejected',
					'reviewed_at'      => current_time( 'mysql' ),
					'reviewer_id'      => get_current_user_id(),
					'rejection_reason' => $reason,
				],
				[ 'user_id' => $user_id ],
				[ '%s', '%s', '%d', '%s' ],
				[ '%d' ]
			);

			if ( false === $updated ) {
				return [
					'success' => false,
					'message' => 'Erro ao atualizar banco de dados',
				];
			}

			update_user_meta( $user_id, 'apollo_verify_status', 'rejected' );

			$this->logAdminAction( $user_id, 'user_rejected', [ 'reason' => $reason ] );

			return [
				'success' => true,
				'message' => 'Verificação rejeitada',
			];

		} catch ( \Exception $e ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Production error logging.
			error_log( 'Error rejecting user: ' . $e->getMessage() );

			return [
				'success' => false,
				'message' => 'Erro interno',
			];
		}//end try
	}

	/**
	 * Get verification data for user.
	 *
	 * @param int $user_id User ID to get verification data for.
	 * @return array|null Verification data or null if not found.
	 */
	private function getVerificationData( int $user_id ): ?array {
		global $wpdb;

		$verification_table = $wpdb->prefix . 'apollo_verifications';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names cannot use placeholders.
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT v.*, u.user_login, u.user_email, u.display_name, u.user_registered
				FROM {$verification_table} v
				INNER JOIN {$wpdb->users} u ON v.user_id = u.ID
				WHERE v.user_id = %d
				ORDER BY v.id DESC LIMIT 1",
				$user_id
			),
			ARRAY_A
		);
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Render verification modal content.
	 *
	 * @param array $verification Verification data.
	 * @return void
	 */
	private function renderVerificationModal( array $verification ): void {
		$metadata_decoded = json_decode( $verification['metadata'], true );
		$metadata         = is_array( $metadata_decoded ) ? $metadata_decoded : [];
		$assets_decoded   = json_decode( $verification['verify_assets'], true );
		$assets           = is_array( $assets_decoded ) ? $assets_decoded : [];
		$display_name     = ! empty( $verification['display_name'] ) ? $verification['display_name'] : $verification['user_login'];

		?>
		<div class="verification-details">
			<div class="user-profile">
				<h4><?php echo esc_html( $display_name ); ?></h4>
				<p><strong>Email:</strong> <?php echo esc_html( $verification['user_email'] ); ?></p>
				<p><strong>Instagram:</strong> @<?php echo esc_html( $verification['instagram_username'] ); ?></p>
				<p><strong>WhatsApp:</strong> <?php echo esc_html( $verification['whatsapp_number'] ); ?></p>
				<p><strong>Token:</strong> <code><?php echo esc_html( $verification['verify_token'] ); ?></code></p>
			</div>

			<?php if ( ! empty( $metadata ) ) : ?>
				<div class="metadata-section">
					<h5>Informações do Onboarding</h5>
					<?php if ( ! empty( $metadata['industry'] ) ) : ?>
						<p><strong>Indústria:</strong> <?php echo esc_html( $metadata['industry'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $metadata['roles'] ) ) : ?>
						<p><strong>Funções:</strong> <?php echo esc_html( implode( ', ', $metadata['roles'] ) ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $metadata['member_of'] ) ) : ?>
						<p><strong>Membro de:</strong> <?php echo esc_html( implode( ', ', $metadata['member_of'] ) ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $assets ) ) : ?>
				<div class="assets-section">
					<h5>Assets de Verificação</h5>
					<div class="assets-grid">
						<?php foreach ( $assets as $asset ) : ?>
							<div class="asset-detail">
								<p><strong>Arquivo:</strong> <?php echo esc_html( $asset['original_name'] ); ?></p>
								<p><strong>Tamanho:</strong> <?php echo esc_html( size_format( $asset['file_size'] ) ); ?></p>
								<p><strong>Dimensões:</strong> <?php echo esc_html( $asset['dimensions']['width'] ); ?>x<?php echo esc_html( $asset['dimensions']['height'] ); ?></p>
								<p><strong>Enviado:</strong> <?php echo esc_html( gmdate( 'd/m/Y H:i', strtotime( $asset['uploaded_at'] ) ) ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( in_array( $verification['verify_status'], [ 'assets_submitted' ], true ) ) : ?>
			<div class="modal-actions">
				<button class="apollo-btn success verify-user" data-user-id="<?php echo esc_attr( $verification['user_id'] ); ?>">
					✅ Verificar Usuário
				</button>

				<div class="reject-section">
					<textarea id="rejection-reason" placeholder="Motivo da rejeição (opcional)" class="apollo-textarea"></textarea>
					<button class="apollo-btn danger reject-user" data-user-id="<?php echo esc_attr( $verification['user_id'] ); ?>">
						❌ Rejeitar Verificação
					</button>
				</div>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Get status CSS class.
	 *
	 * @param string $status Verification status.
	 * @return string CSS class name.
	 */
	private function getStatusClass( string $status ): string {
		$classes = [
			'awaiting_instagram_verify' => 'status-awaiting',
			'assets_submitted'          => 'status-submitted',
			'verified'                  => 'status-verified',
			'rejected'                  => 'status-rejected',
		];

		return $classes[ $status ] ?? 'status-unknown';
	}

	/**
	 * Get status label.
	 *
	 * @param string $status Verification status.
	 * @return string Human-readable status label.
	 */
	private function getStatusLabel( string $status ): string {
		$labels = [
			'awaiting_instagram_verify' => 'Aguardando Assets',
			'assets_submitted'          => 'Assets Enviados',
			'verified'                  => 'Verificado',
			'rejected'                  => 'Rejeitado',
		];

		return $labels[ $status ] ?? 'Desconhecido';
	}

	/**
	 * Log admin action.
	 *
	 * @param int    $user_id Target user ID.
	 * @param string $action  Action performed.
	 * @param array  $data    Additional data.
	 * @return void
	 */
	private function logAdminAction( int $user_id, string $action, array $data = [] ): void {
		global $wpdb;

		$audit_table = $wpdb->prefix . 'apollo_audit_log';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$audit_table
			)
		);
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $table_exists !== $audit_table ) {
			return;
		}

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Server variable, sanitized with filter.
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? filter_var( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : '';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$audit_table,
			[
				'user_id'     => get_current_user_id(),
				'action'      => $action,
				'entity_type' => 'verification',
				'entity_id'   => $user_id,
				'metadata'    => wp_json_encode(
					array_merge(
						$data,
						[
							'target_user_id' => $user_id,
							'user_agent'     => $user_agent,
							'ip_address'     => $this->getClientIp(),
						]
					)
				),
				'created_at'  => current_time( 'mysql' ),
			]
		);
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Get client IP address.
	 *
	 * @return string Client IP address.
	 */
	private function getClientIp(): string {
		$ip_headers = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- IP validation.
				$ip = filter_var( wp_unslash( $_SERVER[ $header ] ), FILTER_VALIDATE_IP );
				if ( $ip ) {
					return $ip;
				}
			}
		}

		return 'unknown';
	}
}
