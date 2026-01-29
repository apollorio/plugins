<?php
/**
 * Comunas REST Controller
 *
 * Handles public community groups (Comunas) with open access.
 *
 * @package Apollo\Infrastructure\Http\Controllers
 * @since 2.3.0
 */

namespace Apollo\Infrastructure\Http\Controllers;

use Apollo\Domain\Entities\GroupEntity;
use Apollo\Domain\Groups\Policies\GroupPolicy;
use Apollo\Domain\Groups\Repositories\GroupsRepository;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Comunas REST Controller
 *
 * Public communities visible to everyone.
 * Join logic: Open or request-based depending on settings.
 */
class ComunasController extends BaseController {

	private $groupPolicy;
	private $repository;

	public function __construct() {
		$this->groupPolicy = new GroupPolicy();
		$this->repository  = new GroupsRepository();
	}

	/**
	 * GET /apollo/v1/comunas
	 *
	 * List all public comunas
	 */
	public function index( WP_REST_Request $request ): WP_REST_Response {
		$filters = array(
			'type'   => 'comuna',
			'status' => 'published',
		);

		if ( $request->get_param( 'search' ) ) {
			$filters['search'] = sanitize_text_field( $request->get_param( 'search' ) );
		}

		$limit  = min( 100, (int) ( $request->get_param( 'limit' ) ?: 20 ) );
		$offset = (int) ( $request->get_param( 'offset' ) ?: 0 );

		$groups          = $this->repository->findAll( $filters, $limit, $offset );
		$filtered_groups = array();

		foreach ( $groups as $group ) {
			$filtered_groups[] = $this->comunaToArray( $group );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $filtered_groups,
			),
			200
		);
	}

	/**
	 * GET /apollo/v1/comunas/{id}
	 *
	 * Get single comuna details
	 */
	public function show( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request->get_param( 'id' );

		if ( ! $group_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'ID da comuna inválido.',
				),
				400
			);
		}

		$group = $this->repository->findById( $group_id );

		if ( ! $group || $group->type !== 'comuna' ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Comuna não encontrada.',
				),
				404
			);
		}

		// Check visibility
		if ( $group->status !== 'published' && ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Comuna não disponível.',
				),
				403
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $this->comunaToArray( $group, true ),
			),
			200
		);
	}

	/**
	 * GET /apollo/v1/comunas/{id}/members
	 *
	 * Get comuna members
	 */
	public function members( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request->get_param( 'id' );

		if ( ! $group_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'ID da comuna inválido.',
				),
				400
			);
		}

		$group = $this->repository->findById( $group_id );

		if ( ! $group || $group->type !== 'comuna' ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Comuna não encontrada.',
				),
				404
			);
		}

		$members = $this->repository->getMembers( $group_id );

		$members_data = array_map(
			function ( $member ) {
				$user = get_userdata( $member->user_id );
				return array(
					'id'        => $member->user_id,
					'name'      => $user ? $user->display_name : 'Unknown',
					'avatar'    => get_avatar_url( $member->user_id, array( 'size' => 96 ) ),
					'role'      => $member->role,
					'joined_at' => $member->joined_at,
				);
			},
			$members
		);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $members_data,
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/comunas
	 *
	 * Create a new comuna
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response {
		if ( ! is_user_logged_in() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você precisa estar logado para criar uma comuna.',
				),
				401
			);
		}

		$user_id = get_current_user_id();

		$title       = sanitize_text_field( $request->get_param( 'title' ) );
		$description = wp_kses_post( $request->get_param( 'description' ) ?? '' );
		$visibility  = sanitize_key( $request->get_param( 'visibility' ) ?? 'public' );

		if ( empty( $title ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Título é obrigatório.',
				),
				400
			);
		}

		// Determine initial status based on workflow
		$initial_status = 'pending_review';
		if ( class_exists( '\Apollo\Infrastructure\Workflows\ContentWorkflow' ) ) {
			$user           = $this->getCurrentUser();
			$workflow       = new \Apollo\Infrastructure\Workflows\ContentWorkflow();
			$context        = array( 'group_type' => 'comuna' );
			$initial_status = $workflow->resolveStatus( $user, 'group', $context );
		}

		$group_data = array(
			'title'       => $title,
			'type'        => 'comuna',
			'description' => $description,
			'status'      => $initial_status,
			'visibility'  => $visibility,
			'creator_id'  => $user_id,
		);

		$group_id = $this->repository->create( $group_data );

		if ( ! $group_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao criar comuna.',
				),
				500
			);
		}

		// Add creator as admin
		$this->repository->addMember( $group_id, $user_id, 'admin' );

		// Submit to moderation if needed
		if ( in_array( $initial_status, array( 'pending', 'pending_review' ), true ) ) {
			if ( class_exists( '\Apollo\Application\Groups\Moderation' ) ) {
				$mod = new \Apollo\Application\Groups\Moderation();
				$mod->submitForReview(
					$group_id,
					$user_id,
					'comuna',
					array(
						'title' => $title,
						'type'  => 'comuna',
					)
				);
			}
		}

		$group = $this->repository->findById( $group_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Comuna criada com sucesso.',
				'data'    => $this->comunaToArray( $group ),
			),
			201
		);
	}

	/**
	 * POST /apollo/v1/comunas/{id}/join
	 *
	 * Join a comuna
	 */
	public function join( WP_REST_Request $request ): WP_REST_Response {
		if ( ! is_user_logged_in() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você precisa estar logado para entrar na comuna.',
				),
				401
			);
		}

		$user_id  = get_current_user_id();
		$group_id = (int) $request->get_param( 'id' );

		if ( ! $group_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'ID da comuna inválido.',
				),
				400
			);
		}

		$group = $this->repository->findById( $group_id );

		if ( ! $group || $group->type !== 'comuna' ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Comuna não encontrada.',
				),
				404
			);
		}

		// Check if already a member
		if ( $this->repository->isMember( $group_id, $user_id ) ) {
			return new WP_REST_Response(
				array(
					'success'        => true,
					'message'        => 'Você já é membro desta comuna.',
					'already_member' => true,
				),
				200
			);
		}

		// Add as member (comunas are open by default)
		$success = $this->repository->addMember( $group_id, $user_id, 'member' );

		if ( ! $success ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao entrar na comuna.',
				),
				500
			);
		}

		do_action( 'apollo_comuna_member_joined', $group_id, $user_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Você entrou na comuna!',
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/comunas/{id}/leave
	 *
	 * Leave a comuna
	 */
	public function leave( WP_REST_Request $request ): WP_REST_Response {
		if ( ! is_user_logged_in() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você precisa estar logado.',
				),
				401
			);
		}

		$user_id  = get_current_user_id();
		$group_id = (int) $request->get_param( 'id' );

		if ( ! $group_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'ID da comuna inválido.',
				),
				400
			);
		}

		$group = $this->repository->findById( $group_id );

		if ( ! $group || $group->type !== 'comuna' ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Comuna não encontrada.',
				),
				404
			);
		}

		// Check if member
		if ( ! $this->repository->isMember( $group_id, $user_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você não é membro desta comuna.',
				),
				400
			);
		}

		// Prevent owner from leaving
		$member = $this->repository->getMember( $group_id, $user_id );
		if ( $member && $member->role === 'owner' ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'O criador não pode sair da comuna. Transfira a propriedade primeiro.',
				),
				400
			);
		}

		$success = $this->repository->removeMember( $group_id, $user_id );

		if ( ! $success ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao sair da comuna.',
				),
				500
			);
		}

		do_action( 'apollo_comuna_member_left', $group_id, $user_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Você saiu da comuna.',
			),
			200
		);
	}

	/**
	 * POST /apollo/v1/comunas/{id}/invite
	 *
	 * Invite someone to the comuna
	 */
	public function invite( WP_REST_Request $request ): WP_REST_Response {
		if ( ! is_user_logged_in() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você precisa estar logado.',
				),
				401
			);
		}

		$user_id    = get_current_user_id();
		$group_id   = (int) $request->get_param( 'id' );
		$invitee_id = (int) $request->get_param( 'user_id' );

		if ( ! $group_id || ! $invitee_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Dados inválidos.',
				),
				400
			);
		}

		$group = $this->repository->findById( $group_id );

		if ( ! $group || $group->type !== 'comuna' ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Comuna não encontrada.',
				),
				404
			);
		}

		// Check if user is a member
		if ( ! $this->repository->isMember( $group_id, $user_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Você precisa ser membro para convidar.',
				),
				403
			);
		}

		// Check if invitee exists
		if ( ! get_userdata( $invitee_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Usuário não encontrado.',
				),
				404
			);
		}

		// Check if already member
		if ( $this->repository->isMember( $group_id, $invitee_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Usuário já é membro.',
				),
				400
			);
		}

		// Send invite (use GroupsModule invite logic)
		$result = $this->sendInvite( $group_id, $user_id, $invitee_id );

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Erro ao enviar convite.',
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Convite enviado!',
			),
			200
		);
	}

	/**
	 * Send invite to user
	 *
	 * @param int $group_id   Group ID.
	 * @param int $inviter_id Inviter user ID.
	 * @param int $invitee_id Invitee user ID.
	 * @return bool
	 */
	private function sendInvite( int $group_id, int $inviter_id, int $invitee_id ): bool {
		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->prefix . 'apollo_group_invites',
			array(
				'group_id'   => $group_id,
				'inviter_id' => $inviter_id,
				'invitee_id' => $invitee_id,
				'status'     => 'pending',
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%d', '%s', '%s' )
		);

		if ( $result ) {
			do_action( 'apollo_comuna_invite_sent', $group_id, $inviter_id, $invitee_id );
		}

		return (bool) $result;
	}

	/**
	 * Convert group entity to array
	 *
	 * @param GroupEntity $group        Group entity.
	 * @param bool        $include_meta Include additional metadata.
	 * @return array
	 */
	private function comunaToArray( GroupEntity $group, bool $include_meta = false ): array {
		$data = array(
			'id'           => $group->id,
			'title'        => $group->title,
			'slug'         => $group->slug ?? sanitize_title( $group->title ),
			'description'  => $group->description ?? '',
			'type'         => 'comuna',
			'visibility'   => $group->visibility ?? 'public',
			'status'       => $group->status,
			'member_count' => $this->repository->getMemberCount( $group->id ),
			'created_at'   => $group->created_at ?? null,
		);

		if ( $include_meta ) {
			$data['creator_id'] = $group->creator_id ?? null;
			$data['is_member']  = is_user_logged_in()
				? $this->repository->isMember( $group->id, get_current_user_id() )
				: false;
		}

		return $data;
	}
}
