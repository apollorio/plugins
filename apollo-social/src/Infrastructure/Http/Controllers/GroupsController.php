<?php

namespace Apollo\Infrastructure\Http\Controllers;

use Apollo\Domain\Entities\GroupEntity;
use Apollo\Domain\Groups\Policies\GroupPolicy;
use Apollo\Domain\Groups\Repositories\GroupsRepository;

/**
 * Groups REST Controller
 */
class GroupsController extends BaseController {

	private $groupPolicy;
	private $repository;

	public function __construct() {
		$this->groupPolicy = new GroupPolicy();
		$this->repository  = new GroupsRepository();
	}

	/**
	 * GET /apollo/v1/groups
	 */
	public function index(): void {
		$params = $this->sanitizeParams( $_GET );

		$filters = array();
		if ( ! empty( $params['type'] ) ) {
			$filters['type'] = sanitize_text_field( $params['type'] );
		}
		if ( ! empty( $params['season'] ) ) {
			$filters['season_slug'] = sanitize_text_field( $params['season'] );
		}
		if ( ! empty( $params['search'] ) ) {
			$filters['search'] = sanitize_text_field( $params['search'] );
		}
		if ( ! empty( $params['status'] ) ) {
			$filters['status'] = sanitize_text_field( $params['status'] );
		}
		if ( ! empty( $params['creator_id'] ) ) {
			$filters['creator_id'] = (int) $params['creator_id'];
		}

		// Only show published groups to non-admins
		if ( ! current_user_can( 'manage_options' ) ) {
			$filters['status'] = 'published';
		}

		$groups = $this->repository->findAll( $filters );

		// Apply view permissions
		$user            = $this->getCurrentUser();
		$filtered_groups = array();

		foreach ( $groups as $group ) {
			if ( $this->groupPolicy->canView( $group, $user ) ) {
				$filtered_groups[] = $this->groupToArray( $group );
			}
		}

		$this->success( $filtered_groups );
	}

	/**
	 * POST /apollo/v1/groups
	 */
	public function create(): void {
		if ( ! $this->validateNonce() ) {
			$this->authError( 'Invalid nonce' );
		}

		$user = $this->getCurrentUser();
		if ( ! $user || ! $user->isLoggedIn() ) {
			$this->authError();
		}

		$params = $this->sanitizeParams( $_POST );

		// Validate required fields
		if ( empty( $params['title'] ) ) {
			$this->validationError( 'Title is required' );
		}

		if ( empty( $params['type'] ) ) {
			$this->validationError( 'Type is required' );
		}

		// Validate type
		$valid_types = array( 'comunidade', 'nucleo', 'season' );
		if ( ! in_array( $params['type'], $valid_types ) ) {
			$this->validationError( 'Invalid group type' );
		}

		// For season type, season_slug is required
		if ( $params['type'] === 'season' && empty( $params['season_slug'] ) ) {
			$this->validationError( 'Season slug is required for season type groups' );
		}

		// Determine initial status based on workflow
		$workflow       = new \Apollo\Infrastructure\Workflows\ContentWorkflow();
		$context        = array( 'group_type' => $params['type'] );
		$initial_status = $workflow->resolveStatus( $user, 'group', $context );

		// Create group
		$group_data = array(
			'title'       => sanitize_text_field( $params['title'] ),
			'type'        => sanitize_text_field( $params['type'] ),
			'season_slug' => ! empty( $params['season_slug'] ) ? sanitize_text_field( $params['season_slug'] ) : null,
			'description' => wp_kses_post( $params['description'] ?? '' ),
			'status'      => $initial_status,
			'visibility'  => sanitize_text_field( $params['visibility'] ?? 'public' ),
			'creator_id'  => $user->id,
		);

		$group_id = $this->repository->create( $group_data );

		if ( ! $group_id ) {
			$this->error( 'Failed to create group', 500 );

			return;
		}

		// If needs mod, submit to queue
		if ( in_array( $initial_status, array( 'pending', 'pending_review' ) ) ) {
			$mod = new \Apollo\Application\Groups\Moderation();
			$mod->submitForReview(
				$group_id,
				$user->id,
				'group',
				array(
					'title' => $group_data['title'],
					'type'  => $group_data['type'],
				)
			);
		}

		$group = $this->repository->findById( $group_id );
		if ( ! $group ) {
			$this->error( 'Group created but not found', 500 );

			return;
		}

		$this->success( $this->groupToArray( $group ), 'Group created successfully' );
	}

	/**
	 * POST /apollo/v1/groups/{id}/join
	 */
	public function join(): void {
		if ( ! $this->validateNonce() ) {
			$this->authError( 'Invalid nonce' );
		}

		$user = $this->getCurrentUser();
		if ( ! $user || ! $user->isLoggedIn() ) {
			$this->authError();
		}

		$group_id = intval( $_GET['id'] ?? 0 );
		if ( ! $group_id ) {
			$this->validationError( 'Invalid group ID' );
		}

		$group = $this->getGroupById( $group_id );
		if ( ! $group ) {
			$this->error( 'Group not found', 404 );
		}

		if ( ! $this->groupPolicy->canJoin( $user, $group ) ) {
			$this->permissionError( 'You cannot join this group' );
		}

		// Check if already a member
		if ( $this->repository->isMember( $group_id, $user->id ) ) {
			$this->success(
				array(
					'joined'         => true,
					'already_member' => true,
				),
				'You are already a member of this group'
			);

			return;
		}

		// Add user to group
		$success = $this->repository->addMember( $group_id, $user->id, 'member' );

		if ( ! $success ) {
			$this->error( 'Failed to join group', 500 );

			return;
		}

		$this->success( array( 'joined' => true ), 'Successfully joined group' );
	}

	/**
	 * POST /apollo/v1/groups/{id}/invite
	 */
	public function invite(): void {
		if ( ! $this->validateNonce() ) {
			$this->authError( 'Invalid nonce' );
		}

		$user = $this->getCurrentUser();
		if ( ! $user || ! $user->isLoggedIn() ) {
			$this->authError();
		}

		$group_id = intval( $_GET['id'] ?? 0 );
		if ( ! $group_id ) {
			$this->validationError( 'Invalid group ID' );
		}

		$params = $this->sanitizeParams( $_POST );
		if ( empty( $params['user_id'] ) ) {
			$this->validationError( 'User ID is required' );
		}

		$group = $this->getGroupById( $group_id );
		if ( ! $group ) {
			$this->error( 'Group not found', 404 );
		}

		if ( ! $this->groupPolicy->canInvite( $user, $group ) ) {
			$this->permissionError( 'You cannot send invites for this group' );
		}

		// TODO: Implement actual invite logic
		// For now, mock success
		$this->success( array( 'invited' => true ), 'Invitation sent successfully' );
	}

	/**
	 * POST /apollo/v1/groups/{id}aprovar-invite
	 */
	public function approveInvite(): void {
		if ( ! $this->validateNonce() ) {
			$this->authError( 'Invalid nonce' );
		}

		$user = $this->getCurrentUser();
		if ( ! $user || ! $user->isLoggedIn() ) {
			$this->authError();
		}

		$params = $this->sanitizeParams( $_POST );
		if ( empty( $params['invite_id'] ) ) {
			$this->validationError( 'Invite ID is required' );
		}

		// TODO: Implement invite approval logic
		// For now, placeholder
		$this->success( array( 'approved' => true ), 'Invite approved successfully' );
	}

	/**
	 * Sanitize title for slug
	 */
	private function sanitizeTitle( string $title ): string {
		return strtolower( trim( preg_replace( '/[^a-zA-Z0-9]+/', '-', $title ), '-' ) );
	}

	/**
	 * Get group by ID
	 */
	private function getGroupById( int $id ): ?GroupEntity {
		return $this->repository->findById( $id );
	}

	/**
	 * Convert GroupEntity to array for API response
	 */
	private function groupToArray( GroupEntity $group ): array {
		return array(
			'id'            => $group->id,
			'title'         => $group->title,
			'slug'          => $group->slug,
			'description'   => $group->description,
			'type'          => $group->type,
			'status'        => $group->status,
			'visibility'    => $group->visibility ?? 'public',
			'season_slug'   => $group->season_slug,
			'creator_id'    => $group->creator_id ?? $group->created_by,
			'created_at'    => $group->created_at,
			'updated_at'    => $group->updated_at ?? null,
			'published_at'  => $group->published_at ?? null,
			'members_count' => $group->members_count ?? 0,
		);
	}
}
