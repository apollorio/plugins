<?php

namespace Apollo\Infrastructure\Security;

/**
 * Permission Gate Helper
 *
 * Centralized permission checking and validation for Apollo Social features.
 */
class Gate {

	private Caps $caps;

	public function __construct( Caps $caps ) {
		$this->caps = $caps;
	}

	/**
	 * Check if current user can perform action on content type
	 */
	public function can( string $action, string $content_type, $content = null ): bool {
		$user = \wp_get_current_user();

		if ( ! $user->exists() ) {
			return false;
		}

		// Build capability name
		$capability = $this->buildCapabilityName( $action, $content_type );

		// Check base capability
		if ( ! $user->has_cap( $capability ) ) {
			return false;
		}

		// Additional checks for specific content
		if ( $content && is_object( $content ) ) {
			return $this->checkContentPermissions( $action, $content_type, $content, $user );
		}

		return true;
	}

	/**
	 * Check if user can create content
	 */
	public function canCreate( string $content_type, array $data = array() ): array {
		$user = \wp_get_current_user();

		if ( ! $user->exists() ) {
			return array(
				'allowed' => false,
				'message' => 'Usuário não autenticado',
			);
		}

		// Check create capability
		$create_cap = $this->buildCapabilityName( 'create', $content_type );
		if ( ! $user->has_cap( $create_cap ) ) {
			return array(
				'allowed' => false,
				'message' => 'Você não tem permissão para criar este tipo de conteúdo',
			);
		}

		// Get approval workflow
		$workflow = $this->caps->getApprovalWorkflow( $content_type, $data );

		return array(
			'allowed'  => true,
			'workflow' => $workflow,
			'message'  => $workflow['message'],
		);
	}

	/**
	 * Check if user can edit specific content
	 */
	public function canEdit( string $content_type, $content ): array {
		$user = \wp_get_current_user();

		if ( ! $user->exists() ) {
			return array(
				'allowed' => false,
				'message' => 'Usuário não autenticado',
			);
		}

		// Check basic edit capability
		$edit_cap = $this->buildCapabilityName( 'edit', $content_type );
		if ( ! $user->has_cap( $edit_cap ) ) {
			return array(
				'allowed' => false,
				'message' => 'Você não tem permissão para editar este tipo de conteúdo',
			);
		}

		// Check if it's own content or if user can edit others
		if ( $this->isOwnContent( $content, $user ) ) {
			return array(
				'allowed' => true,
				'message' => 'Você pode editar seu próprio conteúdo',
			);
		}

		// Check edit others capability
		$edit_others_cap = $this->buildCapabilityName( 'edit_others', $content_type );
		if ( $user->has_cap( $edit_others_cap ) ) {
			return array(
				'allowed' => true,
				'message' => 'Você pode editar conteúdo de outros usuários',
			);
		}

		return array(
			'allowed' => false,
			'message' => 'Você só pode editar seu próprio conteúdo',
		);
	}

	/**
	 * Check if user can delete specific content
	 */
	public function canDelete( string $content_type, $content ): array {
		$user = \wp_get_current_user();

		if ( ! $user->exists() ) {
			return array(
				'allowed' => false,
				'message' => 'Usuário não autenticado',
			);
		}

		// Check basic delete capability
		$delete_cap = $this->buildCapabilityName( 'delete', $content_type );
		if ( ! $user->has_cap( $delete_cap ) ) {
			return array(
				'allowed' => false,
				'message' => 'Você não tem permissão para excluir este tipo de conteúdo',
			);
		}

		// Check if it's own content or if user can delete others
		if ( $this->isOwnContent( $content, $user ) ) {
			// Check if content is published (some roles can't delete published content)
			if ( $this->isPublished( $content ) && ! $this->canDeletePublished( $content_type, $user ) ) {
				return array(
					'allowed' => false,
					'message' => 'Você não pode excluir conteúdo já publicado',
				);
			}

			return array(
				'allowed' => true,
				'message' => 'Você pode excluir seu próprio conteúdo',
			);
		}

		// Check delete others capability
		$delete_others_cap = $this->buildCapabilityName( 'delete_others', $content_type );
		if ( $user->has_cap( $delete_others_cap ) ) {
			return array(
				'allowed' => true,
				'message' => 'Você pode excluir conteúdo de outros usuários',
			);
		}

		return array(
			'allowed' => false,
			'message' => 'Você só pode excluir seu próprio conteúdo',
		);
	}

	/**
	 * Check if user can moderate content
	 */
	public function canModerate( string $content_type = '' ): bool {
		$user = \wp_get_current_user();

		if ( ! $user->exists() ) {
			return false;
		}

		// Check general mod capability
		if ( $user->has_cap( 'apollo_moderate' ) ) {
			return true;
		}

		// Check specific mod capability
		if ( $content_type && $user->has_cap( "apollo_moderate_{$content_type}s" ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if user can view analytics
	 */
	public function canViewAnalytics(): bool {
		return \current_user_can( 'apollo_view_analytics' );
	}

	/**
	 * Check if user can manage analytics
	 */
	public function canManageAnalytics(): bool {
		return \current_user_can( 'apollo_manage_analytics' );
	}

	/**
	 * Check if user can export analytics
	 */
	public function canExportAnalytics(): bool {
		return \current_user_can( 'apollo_export_analytics' );
	}

	/**
	 * Get user role hierarchy level (for priority checks)
	 */
	public function getUserLevel( $user = null ): int {
		if ( ! $user ) {
			$user = \wp_get_current_user();
		}

		if ( in_array( 'administrator', $user->roles ) ) {
			return 5;
		}

		if ( in_array( 'editor', $user->roles ) ) {
			return 4;
		}

		if ( in_array( 'author', $user->roles ) ) {
			return 3;
		}

		if ( in_array( 'contributor', $user->roles ) ) {
			return 2;
		}

		if ( in_array( 'subscriber', $user->roles ) ) {
			return 1;
		}

		return 0;
		// No role
	}

	/**
	 * Check if user can publish directly without approval
	 */
	public function canPublishDirectly( string $content_type ): bool {
		return $this->caps->canPublishDirectly( $content_type );
	}

	/**
	 * Get permission summary for user interface
	 */
	public function getPermissionSummary( string $content_type ): array {
		$user  = \wp_get_current_user();
		$level = $this->getUserLevel( $user );

		$permissions = array(
			'can_create'           => $this->can( 'create', $content_type ),
			'can_edit_own'         => $this->can( 'edit', $content_type ),
			'can_edit_others'      => $this->can( 'edit_others', $content_type ),
			'can_delete_own'       => $this->can( 'delete', $content_type ),
			'can_delete_others'    => $this->can( 'delete_others', $content_type ),
			'can_publish_directly' => $this->canPublishDirectly( $content_type ),
			'can_moderate'         => $this->canModerate( $content_type ),
			'user_level'           => $level,
			'role_name'            => $this->getRoleName( $user ),
		);

		return $permissions;
	}

	/**
	 * Build capability name from action and content type
	 */
	private function buildCapabilityName( string $action, string $content_type ): string {
		// Handle special cases
		if ( $content_type === 'event' ) {
			if ( $action === 'edit_others' ) {
				return 'edit_others_eva_events';
			}
			if ( $action === 'delete_others' ) {
				return 'delete_others_eva_events';
			}

			return "{$action}_eva_events";
		}

		// Handle multi-word actions
		if ( strpos( $action, '_' ) !== false ) {
			return "{$action}_apollo_{$content_type}s";
		}

		return "{$action}_apollo_{$content_type}s";
	}

	/**
	 * Check additional content-specific permissions
	 */
	private function checkContentPermissions( string $action, string $content_type, $content, $user ): bool {
		// Check if content is published and user is trying to edit/delete
		if ( in_array( $action, array( 'edit', 'delete' ) ) && $this->isPublished( $content ) ) {
			// Some roles can't edit/delete published content
			if ( ! $this->canEditPublished( $content_type, $user ) ) {
				return false;
			}
		}

		// Check if user is trying to edit others' content
		if ( strpos( $action, 'others' ) === false && ! $this->isOwnContent( $content, $user ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if content belongs to user
	 */
	private function isOwnContent( $content, $user ): bool {
		if ( is_object( $content ) ) {
			$author_id = $content->post_author ?? $content->user_id ?? null;

			return $author_id == $user->ID;
		}

		if ( is_array( $content ) ) {
			$author_id = $content['post_author'] ?? $content['user_id'] ?? null;

			return $author_id == $user->ID;
		}

		return false;
	}

	/**
	 * Check if content is published
	 */
	private function isPublished( $content ): bool {
		if ( is_object( $content ) ) {
			return ( $content->post_status ?? $content->status ?? '' ) === 'publish';
		}

		if ( is_array( $content ) ) {
			return ( $content['post_status'] ?? $content['status'] ?? '' ) === 'publish';
		}

		return false;
	}

	/**
	 * Check if user can edit published content
	 */
	private function canEditPublished( string $content_type, $user ): bool {
		// Contributors can't edit published content
		if ( in_array( 'contributor', $user->roles ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if user can delete published content
	 */
	private function canDeletePublished( string $content_type, $user ): bool {
		// Contributors can't delete published content
		if ( in_array( 'contributor', $user->roles ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get user role display name
	 */
	private function getRoleName( $user ): string {
		$roles = array(
			'administrator' => 'Administrador',
			'editor'        => 'Editor',
			'author'        => 'Autor',
			'contributor'   => 'Colaborador',
			'subscriber'    => 'Assinante',
		);

		foreach ( $user->roles as $role ) {
			if ( isset( $roles[ $role ] ) ) {
				return $roles[ $role ];
			}
		}

		return 'Usuário';
	}

	/**
	 * Quick permission checks (static methods for convenience)
	 */
	public static function userCan( string $action, string $content_type, $content = null ): bool {
		static $instance = null;

		if ( ! $instance ) {
			$caps     = new Caps();
			$instance = new self( $caps );
		}

		return $instance->can( $action, $content_type, $content );
	}

	public static function userCanCreate( string $content_type, array $data = array() ): array {
		static $instance = null;

		if ( ! $instance ) {
			$caps     = new Caps();
			$instance = new self( $caps );
		}

		return $instance->canCreate( $content_type, $data );
	}

	public static function userCanEdit( string $content_type, $content ): array {
		static $instance = null;

		if ( ! $instance ) {
			$caps     = new Caps();
			$instance = new self( $caps );
		}

		return $instance->canEdit( $content_type, $content );
	}

	public static function userCanDelete( string $content_type, $content ): array {
		static $instance = null;

		if ( ! $instance ) {
			$caps     = new Caps();
			$instance = new self( $caps );
		}

		return $instance->canDelete( $content_type, $content );
	}
}
