<?php
/**
 * Núcleos REST Controller
 *
 * Handles private producer team groups (Núcleos) with invite-only access
 * and admin approval workflow.
 *
 * @package Apollo\Infrastructure\Http\Controllers
 * @since 2.1.0
 */

namespace Apollo\Infrastructure\Http\Controllers;

use Apollo\Domain\Entities\GroupEntity;
use Apollo\Domain\Groups\Policies\GroupPolicy;
use Apollo\Domain\Groups\Repositories\GroupsRepository;
use Apollo\Email\UnifiedEmailService;

/**
 * Núcleos REST Controller
 *
 * Private groups visible only to staff/promoters (not clubbers/subscribers).
 * Join logic: Invite only + Admin approval required.
 */
class NucleosController extends BaseController {

	private $groupPolicy;
	private $repository;

	/**
	 * Roles that can view núcleos
	 */
	private const ALLOWED_ROLES = array( 'administrator', 'editor', 'promoter', 'staff', 'dj', 'venue_owner' );

	/**
	 * Roles that CANNOT view núcleos
	 */
	private const BLOCKED_ROLES = array( 'subscriber', 'clubber' );

	public function __construct() {
		$this->groupPolicy = new GroupPolicy();
		$this->repository  = new GroupsRepository();
	}

	/**
	 * Check if current user can access núcleos
	 *
	 * @return bool
	 */
	private function canAccessNucleos(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user  = wp_get_current_user();
		$roles = (array) $user->roles;

		// Block if user has blocked roles
		if ( array_intersect( $roles, self::BLOCKED_ROLES ) ) {
			return false;
		}

		// Allow administrators always
		if ( in_array( 'administrator', $roles, true ) ) {
			return true;
		}

		// Allow if user has any allowed role
		return (bool) array_intersect( $roles, self::ALLOWED_ROLES );
	}

	/**
	 * GET /apollo/v1/nucleos
	 *
	 * List núcleos (filtered for staff/promoters only)
	 */
	public function index( \WP_REST_Request $request ): \WP_REST_Response {
		// Check access permission
		if ( ! $this->canAccessNucleos() ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você não tem permissão para visualizar núcleos.',
				),
				403
			);
		}

		$user_id = get_current_user_id();

		$filters = array(
			'type' => 'nucleo',
		);

		// Only show published to non-admins, or those where user is member
		if ( ! current_user_can( 'manage_options' ) ) {
			$filters['status'] = 'published';
		}

		if ( $request->get_param( 'search' ) ) {
			$filters['search'] = sanitize_text_field( $request->get_param( 'search' ) );
		}

		$groups = $this->repository->findAll( $filters );

		// Filter to only show núcleos where user is a member OR admin
		$user            = $this->getCurrentUser();
		$filtered_groups = array();

		foreach ( $groups as $group ) {
			$is_member = $this->repository->isMember( $group->id, $user_id );
			$is_admin  = current_user_can( 'manage_options' );

			if ( $is_member || $is_admin ) {
				$filtered_groups[] = $this->nucleoToArray( $group, $user_id );
			}
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $filtered_groups,
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/nucleos
	 *
	 * Create a new núcleo (saves as DRAFT, requires admin approval)
	 */
	public function create( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! $this->canAccessNucleos() ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você não tem permissão para criar núcleos.',
				),
				403
			);
		}

		$user_id = get_current_user_id();

		$title       = sanitize_text_field( $request->get_param( 'title' ) );
		$description = wp_kses_post( $request->get_param( 'description' ) ?? '' );

		if ( empty( $title ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Título é obrigatório.',
				),
				400
			);
		}

		// Create núcleo with DRAFT status (requires admin approval)
		$group_data = array(
			'title'       => $title,
			'type'        => 'nucleo',
			'description' => $description,
			'status'      => 'draft', // Always draft, admin must approve
			'visibility'  => 'private',
			'creator_id'  => $user_id,
		);

		$group_id = $this->repository->create( $group_data );

		if ( ! $group_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao criar núcleo.',
				),
				500
			);
		}

		// Add creator as owner/admin of the núcleo
		$this->repository->addMember( $group_id, $user_id, 'owner' );

		// Submit to moderation queue
		if ( class_exists( '\Apollo\Application\Groups\Moderation' ) ) {
			$mod = new \Apollo\Application\Groups\Moderation();
			$mod->submitForReview(
				$group_id,
				$user_id,
				'nucleo',
				array(
					'title' => $title,
					'type'  => 'nucleo',
				)
			);
		}

		$group = $this->repository->findById( $group_id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Núcleo criado e enviado para aprovação.',
				'data'    => $this->nucleoToArray( $group, $user_id ),
			),
			201
		);
	}

	/**
	 * POST /apollo/v1/nucleos/{id}/join
	 *
	 * Request to join a núcleo (requires invite + admin approval)
	 */
	public function join( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! $this->canAccessNucleos() ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você não tem permissão para acessar núcleos.',
				),
				403
			);
		}

		$user_id  = get_current_user_id();
		$group_id = (int) $request->get_param( 'id' );

		if ( ! $group_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'ID do núcleo inválido.',
				),
				400
			);
		}

		$group = $this->repository->findById( $group_id );

		if ( ! $group || $group->type !== 'nucleo' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Núcleo não encontrado.',
				),
				404
			);
		}

		// Check if already a member
		if ( $this->repository->isMember( $group_id, $user_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Você já é membro deste núcleo.',
					'data'    => array( 'status' => 'member' ),
				),
				200
			);
		}

		// Check if user has a pending invite
		$has_invite = $this->hasValidInvite( $group_id, $user_id );

		if ( ! $has_invite ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Núcleos são apenas por convite. Solicite um convite a um membro existente.',
				),
				403
			);
		}

		// Add as pending member (requires admin approval)
		$success = $this->repository->addMember( $group_id, $user_id, 'pending' );

		if ( ! $success ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao processar solicitação.',
				),
				500
			);
		}

		// Mark invite as used
		$this->markInviteUsed( $group_id, $user_id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Solicitação enviada. Aguarde aprovação de um administrador do núcleo.',
				'data'    => array( 'status' => 'pending_approval' ),
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/nucleos/{id}/invite
	 *
	 * Send invite to join núcleo (only members can invite)
	 */
	public function invite( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id    = get_current_user_id();
		$group_id   = (int) $request->get_param( 'id' );
		$invitee_id = (int) $request->get_param( 'user_id' );
		$email      = sanitize_email( $request->get_param( 'email' ) ?? '' );

		if ( ! $group_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'ID do núcleo inválido.',
				),
				400
			);
		}

		$group = $this->repository->findById( $group_id );

		if ( ! $group || $group->type !== 'nucleo' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Núcleo não encontrado.',
				),
				404
			);
		}

		// Check if current user is a member (only members can invite)
		if ( ! $this->repository->isMember( $group_id, $user_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Apenas membros do núcleo podem enviar convites.',
				),
				403
			);
		}

		// Need either user_id or email
		if ( ! $invitee_id && ! $email ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Informe o ID do usuário ou email para enviar o convite.',
				),
				400
			);
		}

		// Create invite record
		$invite_id = $this->createInvite( $group_id, $user_id, $invitee_id, $email );

		if ( ! $invite_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao criar convite.',
				),
				500
			);
		}

		// Send notification (email or internal)
		$this->sendInviteNotification( $group, $invitee_id, $email, $user_id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Convite enviado com sucesso.',
				'data'    => array( 'invite_id' => $invite_id ),
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/nucleos/{id}/aprovar-join
	 *
	 * Admin approves pending member request
	 */
	public function approveJoin( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id        = get_current_user_id();
		$group_id       = (int) $request->get_param( 'id' );
		$member_user_id = (int) $request->get_param( 'user_id' );

		if ( ! $group_id || ! $member_user_id ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'IDs inválidos.',
				),
				400
			);
		}

		$group = $this->repository->findById( $group_id );

		if ( ! $group || $group->type !== 'nucleo' ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Núcleo não encontrado.',
				),
				404
			);
		}

		// Check if current user is admin/owner of núcleo or global admin
		$is_global_admin = current_user_can( 'manage_options' );
		$member_role     = $this->repository->getMemberRole( $group_id, $user_id );
		$is_nucleo_admin = in_array( $member_role, array( 'owner', 'admin' ), true );

		if ( ! $is_global_admin && ! $is_nucleo_admin ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Apenas administradores podem aprovar membros.',
				),
				403
			);
		}

		// Update member status from pending to member
		$success = $this->repository->updateMemberRole( $group_id, $member_user_id, 'member' );

		if ( ! $success ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao aprovar membro.',
				),
				500
			);
		}

		// Send notification to approved user
		$this->sendApprovalNotification( $group, $member_user_id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Membro aprovado com sucesso.',
			),
			200
		);
	}

	/**
	 * Check if user has a valid invite for the núcleo
	 */
	private function hasValidInvite( int $group_id, int $user_id ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_nucleo_invites';

		// Check if table exists
		// SECURITY FIX: Use prepared statement for table existence check.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return false;
		}

		$invite = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE nucleo_id = %d
				AND (invitee_user_id = %d OR invitee_email = (SELECT user_email FROM {$wpdb->users} WHERE ID = %d))
				AND status = 'pending'
				AND expires_at > NOW()",
				$group_id,
				$user_id,
				$user_id
			)
		);

		return (bool) $invite;
	}

	/**
	 * Mark invite as used
	 */
	private function markInviteUsed( int $group_id, int $user_id ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_nucleo_invites';

		// SECURITY FIX: Use prepared statement for table existence check.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return;
		}

		$wpdb->update(
			$table,
			array(
				'status'  => 'used',
				'used_at' => current_time( 'mysql' ),
			),
			array(
				'nucleo_id'       => $group_id,
				'invitee_user_id' => $user_id,
			),
			array( '%s', '%s' ),
			array( '%d', '%d' )
		);
	}

	/**
	 * Create invite record
	 */
	private function createInvite( int $group_id, int $inviter_id, int $invitee_id, string $email ): ?int {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_nucleo_invites';

		// Create table if not exists
		$this->ensureInvitesTableExists();

		$result = $wpdb->insert(
			$table,
			array(
				'nucleo_id'       => $group_id,
				'inviter_user_id' => $inviter_id,
				'invitee_user_id' => $invitee_id ?: null,
				'invitee_email'   => $email ?: null,
				'token'           => wp_generate_password( 32, false ),
				'status'          => 'pending',
				'created_at'      => current_time( 'mysql' ),
				'expires_at'      => gmdate( 'Y-m-d H:i:s', strtotime( '+7 days' ) ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : null;
	}

	/**
	 * Ensure invites table exists
	 */
	private function ensureInvitesTableExists(): void {
		global $wpdb;

		$table           = $wpdb->prefix . 'apollo_nucleo_invites';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			nucleo_id BIGINT(20) UNSIGNED NOT NULL,
			inviter_user_id BIGINT(20) UNSIGNED NOT NULL,
			invitee_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
			invitee_email VARCHAR(255) DEFAULT NULL,
			token VARCHAR(64) NOT NULL,
			status ENUM('pending', 'used', 'expired', 'cancelled') DEFAULT 'pending',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			expires_at DATETIME DEFAULT NULL,
			used_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY idx_nucleo (nucleo_id),
			KEY idx_invitee (invitee_user_id),
			KEY idx_email (invitee_email),
			KEY idx_token (token),
			KEY idx_status (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Send invite notification
	 */
	private function sendInviteNotification( $group, int $invitee_id, string $email, int $inviter_id ): void {
		$inviter = get_userdata( $inviter_id );
		$subject = sprintf( 'Convite para o Núcleo: %s', $group->title );

		$message = sprintf(
			"Olá!\n\n%s te convidou para participar do núcleo \"%s\" na plataforma Apollo.\n\nAcesse sua conta para aceitar o convite.\n\nAbraços,\nEquipe Apollo",
			$inviter ? $inviter->display_name : 'Um membro',
			$group->title
		);

		if ( $invitee_id ) {
			$invitee = get_userdata( $invitee_id );
			if ( $invitee && $invitee->user_email ) {
				UnifiedEmailService::send( array(
					'to'      => $invitee_id,
					'type'    => 'nucleo_invite',
					'subject' => $subject,
					'body'    => $message,
					'force'   => false,
				) );
			}
		} elseif ( $email ) {
			UnifiedEmailService::send( array(
				'to'      => $email,
				'type'    => 'nucleo_invite',
				'subject' => $subject,
				'body'    => $message,
				'force'   => false,
			) );
		}
	}

	/**
	 * Send approval notification
	 */
	private function sendApprovalNotification( $group, int $user_id ): void {
		$user = get_userdata( $user_id );

		if ( ! $user || ! $user->user_email ) {
			return;
		}

		$subject = sprintf( 'Você foi aprovado no Núcleo: %s', $group->title );
		$message = sprintf(
			"Parabéns!\n\nSua solicitação para participar do núcleo \"%s\" foi aprovada.\n\nAcesse a plataforma para começar a colaborar com o time.\n\nAbraços,\nEquipe Apollo",
			$group->title
		);

		UnifiedEmailService::send( array(
			'to'      => $user_id,
			'type'    => 'nucleo_approval',
			'subject' => $subject,
			'body'    => $message,
			'force'   => false,
		) );
	}

	/**
	 * Convert GroupEntity to array for API response
	 */
	private function nucleoToArray( $group, int $user_id = 0 ): array {
		$is_member   = $user_id ? $this->repository->isMember( $group->id, $user_id ) : false;
		$member_role = $is_member ? $this->repository->getMemberRole( $group->id, $user_id ) : null;

		return array(
			'id'            => $group->id,
			'title'         => $group->title,
			'slug'          => $group->slug ?? sanitize_title( $group->title ),
			'description'   => $group->description ?? '',
			'type'          => 'nucleo',
			'status'        => $group->status,
			'visibility'    => 'private',
			'creator_id'    => $group->creator_id ?? $group->created_by ?? null,
			'created_at'    => $group->created_at ?? null,
			'members_count' => $group->members_count ?? 0,
			'is_member'     => $is_member,
			'member_role'   => $member_role,
		);
	}
}
