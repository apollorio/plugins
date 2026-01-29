<?php
/**
 * Document Status - Two-Layer Status Model
 *
 * FASE 5: Normalização de status sem "ready/signing/signed = publish"
 *
 * Camada 1: WordPress post_status (visibilidade)
 *   - draft, pending, private, publish, trash
 *
 * Camada 2: Apollo document state (fluxo de trabalho)
 *   - draft, pending_review, ready, signing, signed, completed, archived
 *
 * Mapeamento seguro:
 *   - draft → draft (não publicado)
 *   - pending_review → pending (aguardando moderação)
 *   - ready → private (pronto, mas não público)
 *   - signing → private (em processo de assinatura)
 *   - signed → private (assinado, ainda não entregue)
 *   - completed → private ou publish (depende do tipo)
 *   - archived → private (arquivo morto)
 *
 * @package Apollo\Modules\Documents
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Modules\Documents;

/**
 * Document Status Manager
 */
class DocumentStatus {

	// =========================================================================
	// Apollo Document States (Camada 2 - Workflow)
	// =========================================================================

	/** @var string Draft - initial state */
	public const STATE_DRAFT = 'draft';

	/** @var string Pending review - awaiting moderation */
	public const STATE_PENDING_REVIEW = 'pending_review';

	/** @var string Ready - approved, ready for signing */
	public const STATE_READY = 'ready';

	/** @var string Signing - signature process in progress */
	public const STATE_SIGNING = 'signing';

	/** @var string Signed - all required signatures collected */
	public const STATE_SIGNED = 'signed';

	/** @var string Completed - document finalized and delivered */
	public const STATE_COMPLETED = 'completed';

	/** @var string Archived - moved to archive */
	public const STATE_ARCHIVED = 'archived';

	/** @var string Cancelled - voided/cancelled */
	public const STATE_CANCELLED = 'cancelled';

	/** @var string Rejected - rejected during review */
	public const STATE_REJECTED = 'rejected';

	/**
	 * All valid Apollo states
	 */
	public const ALL_STATES = array(
		self::STATE_DRAFT,
		self::STATE_PENDING_REVIEW,
		self::STATE_READY,
		self::STATE_SIGNING,
		self::STATE_SIGNED,
		self::STATE_COMPLETED,
		self::STATE_ARCHIVED,
		self::STATE_CANCELLED,
		self::STATE_REJECTED,
	);

	// =========================================================================
	// WordPress post_status Mapping (Camada 1 - Visibilidade)
	// =========================================================================

	/**
	 * Map Apollo state to WordPress post_status
	 *
	 * IMPORTANTE: Nenhum estado de trabalho (signing, signed) vira 'publish' automaticamente.
	 * 'publish' só é usado para documentos que DEVEM ser públicos (ex: editais).
	 *
	 * @param string $apollo_state Apollo document state.
	 * @return string WordPress post_status.
	 */
	public static function mapToPostStatus( string $apollo_state ): string {
		$map = array(
			// Draft states → draft
			self::STATE_DRAFT          => 'draft',

			// Review states → pending
			self::STATE_PENDING_REVIEW => 'pending',
			self::STATE_REJECTED       => 'pending',

			// Active workflow states → private (NOT publish!)
			self::STATE_READY          => 'private',
			self::STATE_SIGNING        => 'private',
			self::STATE_SIGNED         => 'private',

			// Final states → private by default
			self::STATE_COMPLETED      => 'private',
			self::STATE_ARCHIVED       => 'private',
			self::STATE_CANCELLED      => 'private',
		);

		return $map[ $apollo_state ] ?? 'draft';
	}

	/**
	 * Inverse map: WordPress post_status to possible Apollo states
	 *
	 * @param string $post_status WordPress post_status.
	 * @return array Possible Apollo states.
	 */
	public static function mapFromPostStatus( string $post_status ): array {
		$inverse = array(
			'draft'   => array( self::STATE_DRAFT ),
			'pending' => array( self::STATE_PENDING_REVIEW, self::STATE_REJECTED ),
			'private' => array( self::STATE_READY, self::STATE_SIGNING, self::STATE_SIGNED, self::STATE_COMPLETED, self::STATE_ARCHIVED, self::STATE_CANCELLED ),
			'publish' => array( self::STATE_COMPLETED ), // Only completed can be public
			'trash'   => array(), // Any state can be trashed
		);

		return $inverse[ $post_status ] ?? array( self::STATE_DRAFT );
	}

	// =========================================================================
	// State Transitions (FASE 9 - Workflow)
	// =========================================================================

	/**
	 * Valid state transitions matrix
	 *
	 * Format: 'from_state' => array( 'allowed_to_states' )
	 */
	private const TRANSITIONS = array(
		self::STATE_DRAFT          => array(
			self::STATE_PENDING_REVIEW,
			self::STATE_READY,
			self::STATE_CANCELLED,
		),

		self::STATE_PENDING_REVIEW => array(
			self::STATE_DRAFT,
			self::STATE_READY,
			self::STATE_REJECTED,
			self::STATE_CANCELLED,
		),

		self::STATE_REJECTED       => array(
			self::STATE_DRAFT,
			self::STATE_PENDING_REVIEW,
			self::STATE_CANCELLED,
		),

		self::STATE_READY          => array(
			self::STATE_SIGNING,
			self::STATE_DRAFT, // Can go back
			self::STATE_CANCELLED,
		),

		self::STATE_SIGNING        => array(
			self::STATE_SIGNED,
			self::STATE_READY, // Can go back if signatures incomplete
			self::STATE_CANCELLED,
		),

		self::STATE_SIGNED         => array(
			self::STATE_COMPLETED,
			self::STATE_SIGNING, // Rare: additional signatures needed
			self::STATE_CANCELLED,
		),

		self::STATE_COMPLETED      => array(
			self::STATE_ARCHIVED,
			// Cannot go back from completed
		),

		self::STATE_ARCHIVED       => array(
			self::STATE_COMPLETED, // Unarchive
		),

		self::STATE_CANCELLED      => array(
			self::STATE_DRAFT, // Can be reopened
		),
	);

	/**
	 * Check if a state transition is valid
	 *
	 * @param string $from Current state.
	 * @param string $to   Target state.
	 * @return bool True if valid transition.
	 */
	public static function isValidTransition( string $from, string $to ): bool {
		// Same state is always valid (no-op)
		if ( $from === $to ) {
			return true;
		}

		// Check if transition exists in matrix
		if ( ! isset( self::TRANSITIONS[ $from ] ) ) {
			return false;
		}

		return in_array( $to, self::TRANSITIONS[ $from ], true );
	}

	/**
	 * Get allowed transitions from a state
	 *
	 * @param string $from Current state.
	 * @return array Allowed target states.
	 */
	public static function getAllowedTransitions( string $from ): array {
		return self::TRANSITIONS[ $from ] ?? array();
	}

	/**
	 * Get required capability for a transition
	 *
	 * @param string $from Current state.
	 * @param string $to   Target state.
	 * @return string Required capability.
	 */
	public static function getTransitionCapability( string $from, string $to ): string {
		// Admin can do anything
		if ( current_user_can( 'manage_options' ) ) {
			return 'exist';
		}

		// Specific transition capabilities
		$caps = array(
			// Publishing requires specific cap
			self::STATE_COMPLETED      => 'apollo_publish_documents',

			// Moderation
			self::STATE_PENDING_REVIEW => 'apollo_moderate_documents',
			self::STATE_REJECTED       => 'apollo_moderate_documents',
			self::STATE_READY          => 'apollo_moderate_documents',

			// Signing
			self::STATE_SIGNING        => 'apollo_manage_documents',
			self::STATE_SIGNED         => 'apollo_manage_documents',

			// Archive
			self::STATE_ARCHIVED       => 'apollo_archive_documents',
			self::STATE_CANCELLED      => 'apollo_cancel_documents',
		);

		return $caps[ $to ] ?? 'apollo_edit_documents';
	}

	/**
	 * Check if user can perform transition
	 *
	 * @param string $from    Current state.
	 * @param string $to      Target state.
	 * @param int    $user_id User ID (default current user).
	 * @return bool True if user can perform transition.
	 */
	public static function userCanTransition( string $from, string $to, int $user_id = 0 ): bool {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		// First check if transition is valid
		if ( ! self::isValidTransition( $from, $to ) ) {
			return false;
		}

		// Then check capability
		$cap = self::getTransitionCapability( $from, $to );
		return user_can( $user_id, $cap );
	}

	// =========================================================================
	// State Labels & Display
	// =========================================================================

	/**
	 * Get human-readable label for state
	 *
	 * @param string $state Apollo state.
	 * @return string Translated label.
	 */
	public static function getLabel( string $state ): string {
		$labels = array(
			self::STATE_DRAFT          => __( 'Rascunho', 'apollo-social' ),
			self::STATE_PENDING_REVIEW => __( 'Aguardando Revisão', 'apollo-social' ),
			self::STATE_REJECTED       => __( 'Rejeitado', 'apollo-social' ),
			self::STATE_READY          => __( 'Pronto para Assinatura', 'apollo-social' ),
			self::STATE_SIGNING        => __( 'Em Assinatura', 'apollo-social' ),
			self::STATE_SIGNED         => __( 'Assinado', 'apollo-social' ),
			self::STATE_COMPLETED      => __( 'Concluído', 'apollo-social' ),
			self::STATE_ARCHIVED       => __( 'Arquivado', 'apollo-social' ),
			self::STATE_CANCELLED      => __( 'Cancelado', 'apollo-social' ),
		);

		return $labels[ $state ] ?? $state;
	}

	/**
	 * Get color/badge class for state
	 *
	 * @param string $state Apollo state.
	 * @return string CSS class.
	 */
	public static function getBadgeClass( string $state ): string {
		$classes = array(
			self::STATE_DRAFT          => 'badge-secondary',
			self::STATE_PENDING_REVIEW => 'badge-warning',
			self::STATE_REJECTED       => 'badge-danger',
			self::STATE_READY          => 'badge-info',
			self::STATE_SIGNING        => 'badge-primary',
			self::STATE_SIGNED         => 'badge-success',
			self::STATE_COMPLETED      => 'badge-success',
			self::STATE_ARCHIVED       => 'badge-dark',
			self::STATE_CANCELLED      => 'badge-secondary',
		);

		return $classes[ $state ] ?? 'badge-secondary';
	}

	/**
	 * Get icon for state
	 *
	 * @param string $state Apollo state.
	 * @return string Icon class (Lucide/Heroicon style).
	 */
	public static function getIcon( string $state ): string {
		$icons = array(
			self::STATE_DRAFT          => 'file-text',
			self::STATE_PENDING_REVIEW => 'clock',
			self::STATE_REJECTED       => 'x-circle',
			self::STATE_READY          => 'check-circle',
			self::STATE_SIGNING        => 'edit-3',
			self::STATE_SIGNED         => 'pen-tool',
			self::STATE_COMPLETED      => 'check-square',
			self::STATE_ARCHIVED       => 'archive',
			self::STATE_CANCELLED      => 'slash',
		);

		return $icons[ $state ] ?? 'file';
	}

	// =========================================================================
	// State Groups
	// =========================================================================

	/**
	 * Get states that are "active" (not terminal)
	 *
	 * @return array Active states.
	 */
	public static function getActiveStates(): array {
		return array(
			self::STATE_DRAFT,
			self::STATE_PENDING_REVIEW,
			self::STATE_READY,
			self::STATE_SIGNING,
		);
	}

	/**
	 * Get states that are "terminal" (no further action needed)
	 *
	 * @return array Terminal states.
	 */
	public static function getTerminalStates(): array {
		return array(
			self::STATE_SIGNED,
			self::STATE_COMPLETED,
			self::STATE_ARCHIVED,
			self::STATE_CANCELLED,
			self::STATE_REJECTED,
		);
	}

	/**
	 * Get states where document can be signed
	 *
	 * @return array Signable states.
	 */
	public static function getSignableStates(): array {
		return array(
			self::STATE_READY,
			self::STATE_SIGNING,
		);
	}

	/**
	 * Get states where document can be edited
	 *
	 * @return array Editable states.
	 */
	public static function getEditableStates(): array {
		return array(
			self::STATE_DRAFT,
			self::STATE_PENDING_REVIEW,
			self::STATE_REJECTED,
		);
	}

	/**
	 * Check if document is signable in current state
	 *
	 * @param string $state Current state.
	 * @return bool True if signable.
	 */
	public static function isSignable( string $state ): bool {
		return in_array( $state, self::getSignableStates(), true );
	}

	/**
	 * Check if document is editable in current state
	 *
	 * @param string $state Current state.
	 * @return bool True if editable.
	 */
	public static function isEditable( string $state ): bool {
		return in_array( $state, self::getEditableStates(), true );
	}

	/**
	 * Check if document is in terminal state
	 *
	 * @param string $state Current state.
	 * @return bool True if terminal.
	 */
	public static function isTerminal( string $state ): bool {
		return in_array( $state, self::getTerminalStates(), true );
	}
}
