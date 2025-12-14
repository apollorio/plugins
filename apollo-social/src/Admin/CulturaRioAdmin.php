<?php
declare(strict_types=1);

namespace Apollo\Admin;

use Apollo\Modules\Registration\CulturaRioIdentity;

/**
 * Cultura::Rio Admin Panel
 *
 * Admin-only interface for viewing user cultural identities
 * and managing membership requests.
 *
 * @package Apollo_Social
 * @since 1.2.0
 */
class CulturaRioAdmin {

	/**
	 * Initialize admin hooks
	 */
	public static function init(): void {
		// Add columns to users list
		add_filter( 'manage_users_columns', [ self::class, 'addUserColumns' ] );
		add_filter( 'manage_users_custom_column', [ self::class, 'renderUserColumn' ], 10, 3 );

		// Add user profile fields (admin view only)
		add_action( 'show_user_profile', [ self::class, 'renderProfileFields' ] );
		add_action( 'edit_user_profile', [ self::class, 'renderProfileFields' ] );

		// Save profile fields
		add_action( 'personal_options_update', [ self::class, 'saveProfileFields' ] );
		add_action( 'edit_user_profile_update', [ self::class, 'saveProfileFields' ] );

		// Add admin menu
		add_action( 'admin_menu', [ self::class, 'addAdminMenu' ] );

		// AJAX handlers
		add_action( 'wp_ajax_apollo_approve_membership', [ self::class, 'ajaxApproveMembership' ] );
		add_action( 'wp_ajax_apollo_reject_membership', [ self::class, 'ajaxRejectMembership' ] );
	}

	/**
	 * Add custom columns to users list
	 *
	 * @param array $columns
	 * @return array
	 */
	public static function addUserColumns( array $columns ): array {
		$columns['apollo_cultura_identity']  = __( 'Cultura::Rio', 'apollo-social' );
		$columns['apollo_membership_status'] = __( 'Membership', 'apollo-social' );

		return $columns;
	}

	/**
	 * Render custom column content
	 *
	 * @param string $value
	 * @param string $column_name
	 * @param int    $user_id
	 * @return string
	 */
	public static function renderUserColumn( string $value, string $column_name, int $user_id ): string {
		switch ( $column_name ) {
			case 'apollo_cultura_identity':
				$identities = CulturaRioIdentity::getUserIdentities( $user_id );
				if ( empty( $identities ) ) {
					return '<span style="color: #888;">—</span>';
				}
				$labels = [];
				foreach ( $identities as $key ) {
					$identity = CulturaRioIdentity::getIdentities()[ $key ] ?? null;
					if ( $identity ) {
						$color    = $key === 'clubber' ? '#00d4ff' : '#e0e0e0';
						$labels[] = '<span style="color: ' . $color . ';">' . esc_html( $identity['code'] ) . '</span>';
					}
				}

				return implode( ', ', $labels );

			case 'apollo_membership_status':
				$status       = CulturaRioIdentity::getMembershipStatus( $user_id );
				$status_label = $status['status'];

				$colors = [
					'pending'  => '#f0ad4e',
					'approved' => '#5cb85c',
					'rejected' => '#d9534f',
					'none'     => '#888',
				];

				$color = $colors[ $status_label ] ?? '#888';
				$label = ucfirst( $status_label );

				if ( $status_label === 'pending' && ! empty( $status['requested'] ) ) {
					$count  = count( $status['requested'] );
					$label .= " ({$count})";
				}

				return '<span style="color: ' . $color . '; font-weight: 600;">' . esc_html( $label ) . '</span>';
		}//end switch

		return $value;
	}

	/**
	 * Render profile fields for admin view
	 *
	 * @param \WP_User $user
	 */
	public static function renderProfileFields( \WP_User $user ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$identities    = CulturaRioIdentity::getUserIdentities( $user->ID );
		$original      = CulturaRioIdentity::getUserOriginalIdentities( $user->ID );
		$status        = CulturaRioIdentity::getMembershipStatus( $user->ID );
		$registered_at = get_user_meta( $user->ID, 'apollo_cultura_registered_at', true );

		?>
		<h2 style="color: #00d4ff; border-top: 2px solid #00d4ff; padding-top: 20px; margin-top: 30px;">
			🎭 Cultura::Rio Identity (Admin Only)
		</h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label>Registered At</label></th>
				<td>
					<?php if ( $registered_at ) : ?>
						<strong><?php echo esc_html( $registered_at ); ?></strong>
					<?php else : ?>
						<span style="color: #888;">Not registered yet</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label>Original Choices</label></th>
				<td>
					<?php if ( ! empty( $original ) ) : ?>
						<?php foreach ( $original as $key ) : ?>
							<span style="display: inline-block; background: #1a1a2e; color: #00d4ff; padding: 3px 8px; border-radius: 4px; margin: 2px; font-size: 12px;">
								<?php echo esc_html( CulturaRioIdentity::getIdentityLabel( $key ) ); ?>
							</span>
						<?php endforeach; ?>
						<p class="description">These were the user's original selections at registration (immutable for reference).</p>
					<?php else : ?>
						<span style="color: #888;">No original data</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label>Current Identities</label></th>
				<td>
					<?php
					$all_identities = CulturaRioIdentity::getIdentities();
					foreach ( $all_identities as $key => $identity ) :
						$is_checked = in_array( $key, $identities, true );
						$is_locked  = $identity['locked'] ?? false;
						?>
						<label style="display: block; margin: 5px 0;">
							<input 
								type="checkbox" 
								name="apollo_admin_identities[]" 
								value="<?php echo esc_attr( $key ); ?>"
								<?php checked( $is_checked ); ?>
								<?php echo $is_locked ? 'disabled checked' : ''; ?>
							/>
							<?php if ( $is_locked ) : ?>
								<input type="hidden" name="apollo_admin_identities[]" value="<?php echo esc_attr( $key ); ?>" />
							<?php endif; ?>
							<strong><?php echo esc_html( $identity['code'] ); ?>.</strong>
							<?php echo esc_html( $identity['label'] ); ?>
							<?php if ( $is_locked ) : ?>
								<span style="color: #d9534f;"> (locked)</span>
							<?php endif; ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th><label>Membership Status</label></th>
				<td>
					<select name="apollo_admin_membership_status">
						<option value="none" <?php selected( $status['status'], 'none' ); ?>>None</option>
						<option value="pending" <?php selected( $status['status'], 'pending' ); ?>>Pending</option>
						<option value="approved" <?php selected( $status['status'], 'approved' ); ?>>Approved</option>
						<option value="rejected" <?php selected( $status['status'], 'rejected' ); ?>>Rejected</option>
					</select>
					
					<?php if ( ! empty( $status['requested'] ) ) : ?>
						<p class="description">
							<strong>Requested memberships:</strong><br>
							<?php echo esc_html( implode( ', ', $status['requested'] ) ); ?>
						</p>
					<?php endif; ?>
					
					<?php if ( $status['approved_at'] ) : ?>
						<p class="description">
							Approved at: <?php echo esc_html( $status['approved_at'] ); ?>
							<?php if ( $status['approved_by'] ) : ?>
								by User #<?php echo esc_html( (string) $status['approved_by'] ); ?>
							<?php endif; ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label>Journey Message</label></th>
				<td>
					<?php
					$journey = CulturaRioIdentity::getMembershipJourney( $user->ID );
					if ( $journey && ! empty( $journey['progression_message'] ) ) :
						?>
						<div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: #fff; padding: 15px; border-radius: 8px;">
							<p style="margin: 0; color: #00d4ff;">
								"<?php echo esc_html( $journey['progression_message'] ); ?>"
							</p>
						</div>
						<p class="description">This message can be shown to the user to celebrate their journey.</p>
					<?php else : ?>
						<span style="color: #888;">No journey message available yet.</span>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save profile fields
	 *
	 * @param int $user_id
	 */
	public static function saveProfileFields( int $user_id ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Save identities
		if ( isset( $_POST['apollo_admin_identities'] ) ) {
			$identities = array_map( 'sanitize_key', $_POST['apollo_admin_identities'] );
			CulturaRioIdentity::saveUserIdentity( $user_id, $identities );
		}

		// Save membership status
		if ( isset( $_POST['apollo_admin_membership_status'] ) ) {
			$new_status     = sanitize_key( $_POST['apollo_admin_membership_status'] );
			$current_status = get_user_meta( $user_id, 'apollo_membership_status', true );

			if ( $new_status !== $current_status ) {
				update_user_meta( $user_id, 'apollo_membership_status', $new_status );

				if ( $new_status === 'approved' ) {
					update_user_meta( $user_id, 'apollo_membership_approved_at', current_time( 'mysql' ) );
					update_user_meta( $user_id, 'apollo_membership_approved_by', get_current_user_id() );
				} elseif ( $new_status === 'rejected' ) {
					update_user_meta( $user_id, 'apollo_membership_rejected_at', current_time( 'mysql' ) );
					update_user_meta( $user_id, 'apollo_membership_rejected_by', get_current_user_id() );
				}
			}
		}
	}

	/**
	 * Add admin menu
	 */
	public static function addAdminMenu(): void {
		add_submenu_page(
			'users.php',
			__( 'Membership Requests', 'apollo-social' ),
			__( 'Membership Requests', 'apollo-social' ),
			'manage_options',
			'apollo-membership-requests',
			[ self::class, 'renderMembershipPage' ]
		);
	}

	/**
	 * Render membership requests page
	 */
	public static function renderMembershipPage(): void {
		// Get pending users
		$pending_users = get_users(
			[
				'meta_key'   => 'apollo_membership_status',
				'meta_value' => 'pending',
			]
		);

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Cultura::Rio Membership Requests', 'apollo-social' ); ?></h1>
			
			<?php if ( empty( $pending_users ) ) : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'No pending membership requests.', 'apollo-social' ); ?></p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'User', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Email', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Identities', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Requested', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Date', 'apollo-social' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'apollo-social' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $pending_users as $user ) :
							$status     = CulturaRioIdentity::getMembershipStatus( $user->ID );
							$identities = CulturaRioIdentity::getUserIdentities( $user->ID );
							?>
							<tr>
								<td>
									<strong>
										<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>">
											<?php echo esc_html( $user->display_name ); ?>
										</a>
									</strong>
								</td>
								<td><?php echo esc_html( $user->user_email ); ?></td>
								<td>
									<?php
									$labels = CulturaRioIdentity::getIdentityLabels( $identities );
									echo esc_html( implode( ', ', array_map( fn ( $l ) => substr( $l, 0, 20 ) . '...', $labels ) ) );
									?>
								</td>
								<td>
									<?php
									if ( ! empty( $status['requested'] ) ) {
										echo esc_html( implode( ', ', $status['requested'] ) );
									}
									?>
								</td>
								<td><?php echo esc_html( $status['requested_at'] ?? '—' ); ?></td>
								<td>
									<button 
										type="button" 
										class="button button-primary apollo-approve-btn"
										data-user-id="<?php echo esc_attr( (string) $user->ID ); ?>"
									>
										<?php esc_html_e( 'Approve', 'apollo-social' ); ?>
									</button>
									<button 
										type="button" 
										class="button apollo-reject-btn"
										data-user-id="<?php echo esc_attr( (string) $user->ID ); ?>"
									>
										<?php esc_html_e( 'Reject', 'apollo-social' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<script>
				document.addEventListener('DOMContentLoaded', function() {
					// Approve buttons
					document.querySelectorAll('.apollo-approve-btn').forEach(function(btn) {
						btn.addEventListener('click', function() {
							const userId = this.dataset.userId;
							if (confirm('Approve membership for this user?')) {
								apolloMembershipAction('approve', userId, this);
							}
						});
					});
					
					// Reject buttons
					document.querySelectorAll('.apollo-reject-btn').forEach(function(btn) {
						btn.addEventListener('click', function() {
							const userId = this.dataset.userId;
							const reason = prompt('Enter rejection reason (optional):');
							if (reason !== null) {
								apolloMembershipAction('reject', userId, this, reason);
							}
						});
					});
					
					function apolloMembershipAction(action, userId, btn, reason) {
						btn.disabled = true;
						btn.textContent = 'Processing...';
						
						fetch(ajaxurl, {
							method: 'POST',
							headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
							body: new URLSearchParams({
								action: 'apollo_' + action + '_membership',
								user_id: userId,
								reason: reason || '',
								_wpnonce: '<?php echo wp_create_nonce( 'apollo_membership_action' ); ?>'
							})
						})
						.then(r => r.json())
						.then(data => {
							if (data.success) {
								btn.closest('tr').style.opacity = '0.5';
								btn.textContent = action === 'approve' ? 'Approved!' : 'Rejected';
							} else {
								alert('Error: ' + (data.data || 'Unknown error'));
								btn.disabled = false;
								btn.textContent = action === 'approve' ? 'Approve' : 'Reject';
							}
						})
						.catch(err => {
							alert('Error: ' + err.message);
							btn.disabled = false;
						});
					}
				});
				</script>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * AJAX: Approve membership
	 */
	public static function ajaxApproveMembership(): void {
		check_ajax_referer( 'apollo_membership_action' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_send_json_error( 'Invalid user ID' );
		}

		$result = CulturaRioIdentity::approveMembership( $user_id, get_current_user_id() );

		if ( $result ) {
			wp_send_json_success( 'Membership approved' );
		} else {
			wp_send_json_error( 'Failed to approve membership' );
		}
	}

	/**
	 * AJAX: Reject membership
	 */
	public static function ajaxRejectMembership(): void {
		check_ajax_referer( 'apollo_membership_action' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$reason  = isset( $_POST['reason'] ) ? sanitize_textarea_field( $_POST['reason'] ) : '';

		if ( ! $user_id ) {
			wp_send_json_error( 'Invalid user ID' );
		}

		$result = CulturaRioIdentity::rejectMembership( $user_id, get_current_user_id(), $reason );

		if ( $result ) {
			wp_send_json_success( 'Membership rejected' );
		} else {
			wp_send_json_error( 'Failed to reject membership' );
		}
	}
}

// Initialize on admin
if ( is_admin() ) {
	CulturaRioAdmin::init();
}
