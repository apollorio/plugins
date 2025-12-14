<?php

namespace Apollo\API\Controllers;

use Apollo\Application\Groups\Moderation;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Controller for Moderation Actions
 * Handles approve/reject requests with proper workflow integration
 */
class ModerationController {

	private $mod;

	public function __construct() {
		$this->mod = new Moderation();
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes(): void {
		register_rest_route(
			'apollo/v1',
			'/groups/(?P<id>\d+)aprovar',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'approve_group' ],
				'permission_callback' => [ $this, 'can_moderate' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			'apollo/v1',
			'/groups/(?P<id>\d+)/reject',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'reject_group' ],
				'permission_callback' => [ $this, 'can_moderate' ],
				'args'                => [
					'id'     => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'reason' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					],
				],
			]
		);

		register_rest_route(
			'apollo/v1',
			'/groups/(?P<id>\d+)/resubmit',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'resubmit_group' ],
				'permission_callback' => [ $this, 'can_resubmit' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			'apollo/v1',
			'/groups/(?P<id>\d+)/status',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_group_status' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * Approve group
	 */
	public function approve_group( WP_REST_Request $request ): WP_REST_Response {
		$group_id     = $request->get_param( 'id' );
		$moderator_id = get_current_user_id();

		// Get group to verify status
		$group = $this->get_group( $group_id );
		if ( ! $group ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Grupo não encontrado',
				],
				404
			);
		}

		// Check if group is in pending status
		if ( ! in_array( $group['status'], [ 'pending', 'pending_review' ] ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Grupo não está aguardando moderação',
				],
				400
			);
		}

		// Approve the group
		$result = $this->mod->approveGroup( $group_id, $moderator_id );

		if ( $result['success'] ) {
			return new WP_REST_Response(
				[
					'success'    => true,
					'message'    => 'Grupo aprovado com sucesso',
					'new_status' => 'published',
					'group'      => $this->get_group( $group_id ),
				],
				200
			);
		} else {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $result['message'] ?? 'Erro ao aprovar grupo',
				],
				500
			);
		}
	}

	/**
	 * Reject group with sanitized reason
	 */
	public function reject_group( WP_REST_Request $request ): WP_REST_Response {
		$group_id     = $request->get_param( 'id' );
		$reason       = $request->get_param( 'reason' );
		$moderator_id = get_current_user_id();

		// Get group to verify status
		$group = $this->get_group( $group_id );
		if ( ! $group ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Grupo não encontrado',
				],
				404
			);
		}

		// Check if group is in pending status
		if ( ! in_array( $group['status'], [ 'pending', 'pending_review' ] ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Grupo não está aguardando moderação',
				],
				400
			);
		}

		// Reject the group with sanitized reason
		$result = $this->mod->rejectGroup( $group_id, $moderator_id, $reason );

		if ( $result['success'] ) {
			return new WP_REST_Response(
				[
					'success'          => true,
					'message'          => 'Grupo rejeitado com sucesso',
					'new_status'       => 'rejected',
					'rejection_reason' => $result['reason'] ?? $reason,
					'standard_message' => "Apollo rejeitou sua inclusão..<br>Motivo: <span class=\"apollo-reason\">{$result['reason']}</span>",
					'group'            => $this->get_group( $group_id ),
				],
				200
			);
		} else {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $result['message'] ?? 'Erro ao rejeitar grupo',
				],
				500
			);
		}//end if
	}

	/**
	 * Resubmit rejected group (reset to draft for editing)
	 */
	public function resubmit_group( WP_REST_Request $request ): WP_REST_Response {
		$group_id = $request->get_param( 'id' );
		$user_id  = get_current_user_id();

		// Get group to verify ownership and status
		$group = $this->get_group( $group_id );
		if ( ! $group ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Grupo não encontrado',
				],
				404
			);
		}

		// Check ownership
		if ( $group['creator_id'] != $user_id ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Você não tem permissão para reenviar este grupo',
				],
				403
			);
		}

		// Check if group is rejected
		if ( $group['status'] !== 'rejected' ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Apenas grupos rejeitados podem ser reenviados',
				],
				400
			);
		}

		// Reset to draft status
		global $wpdb;
		$groups_table = $wpdb->prefix . 'apollo_groups';

		$updated = $wpdb->update(
			$groups_table,
			[
				'status'     => 'draft',
				'updated_at' => current_time( 'mysql' ),
			],
			[ 'id' => $group_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		if ( $updated !== false ) {
			return new WP_REST_Response(
				[
					'success'      => true,
					'message'      => 'Grupo movido para rascunho. Você pode editá-lo e reenviar.',
					'new_status'   => 'draft',
					'redirect_url' => "/grupo/editar/{$group_id}/",
					'group'        => $this->get_group( $group_id ),
				],
				200
			);
		} else {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Erro ao atualizar status do grupo',
				],
				500
			);
		}
	}

	/**
	 * Get group status with rejection notice
	 */
	public function get_group_status( WP_REST_Request $request ): WP_REST_Response {
		$group_id = $request->get_param( 'id' );

		$group = $this->get_group( $group_id );
		if ( ! $group ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Grupo não encontrado',
				],
				404
			);
		}

		$status_data = [
			'group_id'   => $group_id,
			'status'     => $group['status'],
			'created_at' => $group['created_at'],
			'updated_at' => $group['updated_at'],
		];

		// Add rejection notice if rejected
		if ( $group['status'] === 'rejected' ) {
			$rejection_notice = $this->mod->getRejectionNotice( $group_id );
			if ( $rejection_notice ) {
				$status_data['rejection_notice'] = $rejection_notice;
			}
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => $status_data,
			],
			200
		);
	}

	/**
	 * Check if user can moderate
	 */
	public function can_moderate(): bool {
		return current_user_can( 'apollo_moderate' ) || current_user_can( 'apollo_moderate_all' );
	}

	/**
	 * Check if user can resubmit (owner check done in method)
	 */
	public function can_resubmit(): bool {
		return is_user_logged_in();
	}

	/**
	 * Get group by ID
	 */
	private function get_group( int $group_id ): ?array {
		global $wpdb;

		$groups_table = $wpdb->prefix . 'apollo_groups';

		$group = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$groups_table} WHERE id = %d", $group_id ),
			ARRAY_A
		);

		return $group ?: null;
	}
}
