<?php

namespace Apollo\Infrastructure\Workflows;

/**
 * Content Workflow Manager
 *
 * Manages approval workflows and state transitions for Apollo content.
 */
class ContentWorkflow {

	private array $workflows   = [];
	private array $states      = [];
	private array $transitions = [];

	public function __construct() {
		$this->initializeWorkflows();
	}

	/**
	 * Initialize default workflows
	 */
	private function initializeWorkflows(): void {
		$this->workflows = [
			'group' => [
				'name'          => 'Aprovação de Grupos',
				'states'        => [ 'draft', 'pending_review', 'published', 'rejected', 'suspended' ],
				'default_state' => 'draft',
			],
			'event' => [
				'name'          => 'Aprovação de Eventos',
				'states'        => [ 'draft', 'pending_review', 'published', 'rejected', 'cancelled' ],
				'default_state' => 'draft',
			],
			'ad'    => [
				'name'          => 'Aprovação de Anúncios',
				'states'        => [ 'draft', 'pending_review', 'published', 'rejected', 'expired' ],
				'default_state' => 'draft',
			],
		];

		$this->states = [
			'draft'          => [
				'label'       => 'Rascunho',
				'description' => 'Conteúdo em edição, não visível publicamente',
				'color'       => '#6b7280',
				'icon'        => '📝',
				'public'      => false,
			],
			'pending_review' => [
				'label'       => 'Aguardando Aprovação',
				'description' => 'Conteúdo enviado para moderação',
				'color'       => '#f59e0b',
				'icon'        => '⏳',
				'public'      => false,
			],
			'published'      => [
				'label'       => 'Publicado',
				'description' => 'Conteúdo aprovado e visível publicamente',
				'color'       => '#10b981',
				'icon'        => '✅',
				'public'      => true,
			],
			'rejected'       => [
				'label'       => 'Rejeitado',
				'description' => 'Conteúdo rejeitado na moderação',
				'color'       => '#ef4444',
				'icon'        => '❌',
				'public'      => false,
			],
			'suspended'      => [
				'label'       => 'Suspenso',
				'description' => 'Conteúdo temporariamente suspenso',
				'color'       => '#f97316',
				'icon'        => '⏸️',
				'public'      => false,
			],
			'cancelled'      => [
				'label'       => 'Cancelado',
				'description' => 'Evento cancelado',
				'color'       => '#6b7280',
				'icon'        => '🚫',
				'public'      => true,
			],
			'expired'        => [
				'label'       => 'Expirado',
				'description' => 'Anúncio expirado',
				'color'       => '#9ca3af',
				'icon'        => '⌛',
				'public'      => false,
			],
		];

		$this->transitions = [
			'draft'          => [ 'pending_review', 'published' ],
			'pending_review' => [ 'published', 'rejected', 'draft' ],
			'published'      => [ 'suspended', 'rejected', 'cancelled', 'expired' ],
			'rejected'       => [ 'draft', 'pending_review' ],
			'suspended'      => [ 'published', 'rejected' ],
			'cancelled'      => [],
			'expired'        => [ 'published' ],
		];
	}

	/**
	 * Get initial state for new content (FINAL MATRIX)
	 */
	public function getInitialState( string $content_type, array $data = [] ): string {
		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			return 'draft';
		}

		return $this->resolveStatus( $user, $content_type, $data );
	}

	/**
	 * Resolve status based on final matrix
	 */
	public function resolveStatus( \WP_User $user, string $content_type, array $ctx = [] ): string {
		// Editor/Administrator - always published
		if ( in_array( 'administrator', $user->roles ) || in_array( 'editor', $user->roles ) ) {
			return 'published';
		}

		// Special rules for Community/Núcleo groups - always pending_review for non-editors
		if ( $content_type === 'group' ) {
			$group_type = $ctx['type'] ?? '';
			if ( in_array( $group_type, [ 'comunidade', 'nucleo' ] ) ) {
				return 'pending_review';
			}
		}

		// Role-based matrix
		if ( in_array( 'author', $user->roles ) ) {
			// Author rules
			switch ( $content_type ) {
				case 'group':
					return 'pending_review';
				// Social/Discussion → pending
				case 'ad':
					return 'pending_review';
				// Classified → pending
				case 'event':
					return 'published';
				// Event → published directly
				default:
					return 'pending_review';
			}
		}

		if ( in_array( 'subscriber', $user->roles ) ) {
			// Subscriber rules
			switch ( $content_type ) {
				case 'group':
					return 'draft';
				// Social/Discussion → draft
				case 'ad':
					return 'published';
				// Classified → published directly
				case 'event':
					return 'pending_review';
				// Event → pending
				default:
					return 'draft';
			}
		}

		// Contributor rules - everything draft except Community/Núcleo
		if ( in_array( 'contributor', $user->roles ) ) {
			if ( $content_type === 'group' ) {
				$group_type = $ctx['type'] ?? '';
				if ( in_array( $group_type, [ 'comunidade', 'nucleo' ] ) ) {
					return 'pending_review';
				}
			}
			return 'draft';
		}

		// Default fallback
		return 'draft';
	}

	/**
	 * Check if user can publish directly
	 */
	private function canPublishDirectly( string $content_type ): bool {
		$user = wp_get_current_user();

		// Administrators and editors can always publish directly
		if ( in_array( 'administrator', $user->roles ) || in_array( 'editor', $user->roles ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Transition content to new state
	 */
	public function transition( int $content_id, string $content_type, string $new_state, array $data = [] ): array {
		$current_state = $this->getCurrentState( $content_id, $content_type );

		// Validate transition
		if ( ! $this->canTransition( $current_state, $new_state ) ) {
			return [
				'success' => false,
				'message' => "Transição de '{$current_state}' para '{$new_state}' não é permitida",
			];
		}

		// Check permissions for transition
		if ( ! $this->canUserTransition( $content_type, $current_state, $new_state ) ) {
			return [
				'success' => false,
				'message' => 'Você não tem permissão para esta transição',
			];
		}

		// Execute transition
		$result = $this->executeTransition( $content_id, $content_type, $current_state, $new_state, $data );

		if ( $result['success'] ) {
			// Log transition
			$this->logTransition( $content_id, $content_type, $current_state, $new_state, $data );

			// Execute post-transition actions
			$this->executePostTransitionActions( $content_id, $content_type, $new_state, $data );
		}

		return $result;
	}

	/**
	 * Check if transition is allowed
	 */
	private function canTransition( string $from_state, string $to_state ): bool {
		return in_array( $to_state, $this->transitions[ $from_state ] ?? [] );
	}

	/**
	 * Check if user can perform transition
	 */
	private function canUserTransition( string $content_type, string $from_state, string $to_state ): bool {
		$user = wp_get_current_user();

		// Administrators can do any transition
		if ( in_array( 'administrator', $user->roles ) ) {
			return true;
		}

		// Transitions that require mod capabilities
		$mod_transitions = [
			'pending_review' => 'published',
			'pending_review' => 'rejected',
		];
		$transition_key         = "{$from_state} => {$to_state}";

		if ( in_array( $transition_key, $mod_transitions ) ) {
			return current_user_can( 'apollo_moderate' ) || current_user_can( "apollo_moderate_{$content_type}s" );
		}

		// Users can move their own content to pending_review
		if ( $to_state === 'pending_review' ) {
			return true;
			// Will be validated at content level
		}

		// Editors can publish directly
		if ( $to_state === 'published' && in_array( 'editor', $user->roles ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Execute the actual transition
	 */
	private function executeTransition( int $content_id, string $content_type, string $from_state, string $to_state, array $data ): array {
		global $wpdb;

		try {
			// Update content status
			$table         = $this->getContentTable( $content_type );
			$status_column = $this->getStatusColumn( $content_type );

			$updated = $wpdb->update(
				$table,
				[ $status_column => $to_state ],
				[ 'id' => $content_id ],
				[ '%s' ],
				[ '%d' ]
			);

			if ( $updated === false ) {
				return [
					'success' => false,
					'message' => 'Erro ao atualizar status no banco de dados',
				];
			}

			return [
				'success' => true,
				'message' => "Status alterado para '{$this->states[$to_state]['label']}'",
			];

		} catch ( Exception $e ) {
			return [
				'success' => false,
				'message' => 'Erro interno: ' . $e->getMessage(),
			];
		}//end try
	}

	/**
	 * Get current state of content
	 */
	private function getCurrentState( int $content_id, string $content_type ): string {
		global $wpdb;

		$table         = $this->getContentTable( $content_type );
		$status_column = $this->getStatusColumn( $content_type );

		$state = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT {$status_column} FROM {$table} WHERE id = %d",
				$content_id
			)
		);

		return $state ?: 'draft';
	}

	/**
	 * Get database table for content type
	 */
	private function getContentTable( string $content_type ): string {
		global $wpdb;

		$tables = [
			'group' => $wpdb->prefix . 'apollo_groups',
			'event' => $wpdb->prefix . 'eva_events',
			'ad'    => $wpdb->prefix . 'apollo_ads',
		];

		return $tables[ $content_type ] ?? $wpdb->posts;
	}

	/**
	 * Get status column for content type
	 */
	private function getStatusColumn( string $content_type ): string {
		return $content_type === 'event' ? 'post_status' : 'status';
	}

	/**
	 * Log state transition
	 */
	private function logTransition( int $content_id, string $content_type, string $from_state, string $to_state, array $data ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'apollo_workflow_log',
			[
				'content_id'   => $content_id,
				'content_type' => $content_type,
				'from_state'   => $from_state,
				'to_state'     => $to_state,
				'user_id'      => get_current_user_id(),
				'reason'       => $data['reason'] ?? '',
				'metadata'     => json_encode( $data ),
				'created_at'   => current_time( 'mysql' ),
			],
			[ '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Execute post-transition actions
	 */
	private function executePostTransitionActions( int $content_id, string $content_type, string $new_state, array $data ): void {
		switch ( $new_state ) {
			case 'published':
				$this->notifyContentPublished( $content_id, $content_type );
				break;

			case 'rejected':
				$this->notifyContentRejected( $content_id, $content_type, $data['reason'] ?? '' );
				break;

			case 'pending_review':
				$this->notifyModeratorsNewContent( $content_id, $content_type );
				break;

			case 'suspended':
				$this->notifyContentSuspended( $content_id, $content_type, $data['reason'] ?? '' );
				break;
		}
	}

	/**
	 * Get workflow status display
	 */
	public function getStatusDisplay( string $state ): array {
		$state_info = $this->states[ $state ] ?? $this->states['draft'];

		return [
			'label'       => $state_info['label'],
			'description' => $state_info['description'],
			'color'       => $state_info['color'],
			'icon'        => $state_info['icon'],
			'public'      => $state_info['public'],
		];
	}

	/**
	 * Get available transitions for current state
	 */
	public function getAvailableTransitions( string $current_state, string $content_type ): array {
		$available       = [];
		$possible_states = $this->transitions[ $current_state ] ?? [];

		foreach ( $possible_states as $state ) {
			if ( $this->canUserTransition( $content_type, $current_state, $state ) ) {
				$available[] = [
					'state'   => $state,
					'display' => $this->getStatusDisplay( $state ),
				];
			}
		}

		return $available;
	}

	/**
	 * Get workflow summary for content
	 */
	public function getWorkflowSummary( int $content_id, string $content_type ): array {
		$current_state         = $this->getCurrentState( $content_id, $content_type );
		$status_display        = $this->getStatusDisplay( $current_state );
		$available_transitions = $this->getAvailableTransitions( $current_state, $content_type );

		return [
			'current_state'         => $current_state,
			'status_display'        => $status_display,
			'available_transitions' => $available_transitions,
			'workflow_name'         => $this->workflows[ $content_type ]['name'] ?? 'Workflow Padrão',
		];
	}

	/**
	 * Notification methods (stubs - implement with actual notification system)
	 */
	private function notifyContentPublished( int $content_id, string $content_type ): void {
		// TODO: Implement notification to content author
	}

	private function notifyContentRejected( int $content_id, string $content_type, string $reason ): void {
		// TODO: Implement notification to content author with rejection reason
	}

	private function notifyModeratorsNewContent( int $content_id, string $content_type ): void {
		// TODO: Implement notification to moderators about new content pending review
	}

	private function notifyContentSuspended( int $content_id, string $content_type, string $reason ): void {
		// TODO: Implement notification to content author about suspension
	}
}
