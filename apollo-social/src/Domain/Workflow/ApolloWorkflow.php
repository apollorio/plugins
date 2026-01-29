<?php
/**
 * Apollo Workflow
 *
 * FASE 9: Workflow unificado para todos os módulos.
 *
 * Estados canônicos:
 * - draft, pending_review, approved, rejected, suspended, expired, cancelled, archived
 *
 * Cada domínio (Documents, Groups, Ads) mapeia seus estados específicos
 * para os estados canônicos.
 *
 * Funcionalidades:
 * - Matriz de transições permitidas
 * - Capability por transição
 * - Validação server-side
 * - Auditoria de transições
 *
 * @package Apollo\Domain\Workflow
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Domain\Workflow;

use Apollo\Infrastructure\ApolloLogger;
use WP_Error;

/**
 * Apollo Workflow - Unified State Machine
 */
class ApolloWorkflow {

	// =========================================================================
	// Canonical States (shared across all modules)
	// =========================================================================

	/** @var string Draft - initial state */
	public const STATE_DRAFT = 'draft';

	/** @var string Pending review */
	public const STATE_PENDING_REVIEW = 'pending_review';

	/** @var string Approved */
	public const STATE_APPROVED = 'approved';

	/** @var string Rejected */
	public const STATE_REJECTED = 'rejected';

	/** @var string Suspended (temporarily blocked) */
	public const STATE_SUSPENDED = 'suspended';

	/** @var string Expired (time-based) */
	public const STATE_EXPIRED = 'expired';

	/** @var string Cancelled */
	public const STATE_CANCELLED = 'cancelled';

	/** @var string Archived */
	public const STATE_ARCHIVED = 'archived';

	/** @var string Published/Active */
	public const STATE_PUBLISHED = 'published';

	/**
	 * All canonical states
	 */
	public const ALL_STATES = array(
		self::STATE_DRAFT,
		self::STATE_PENDING_REVIEW,
		self::STATE_APPROVED,
		self::STATE_REJECTED,
		self::STATE_SUSPENDED,
		self::STATE_EXPIRED,
		self::STATE_CANCELLED,
		self::STATE_ARCHIVED,
		self::STATE_PUBLISHED,
	);

	// =========================================================================
	// Domains
	// =========================================================================

	public const DOMAIN_DOCUMENTS = 'documents';
	public const DOMAIN_GROUPS    = 'groups';
	public const DOMAIN_ADS       = 'ads';
	public const DOMAIN_USERS     = 'users';

	// =========================================================================
	// Transition Matrix (Canonical)
	// =========================================================================

	/**
	 * Valid transitions for canonical states
	 */
	private const CANONICAL_TRANSITIONS = array(
		self::STATE_DRAFT          => array(
			self::STATE_PENDING_REVIEW,
			self::STATE_CANCELLED,
		),

		self::STATE_PENDING_REVIEW => array(
			self::STATE_DRAFT,
			self::STATE_APPROVED,
			self::STATE_REJECTED,
			self::STATE_CANCELLED,
		),

		self::STATE_APPROVED       => array(
			self::STATE_PUBLISHED,
			self::STATE_SUSPENDED,
			self::STATE_CANCELLED,
			self::STATE_ARCHIVED,
		),

		self::STATE_REJECTED       => array(
			self::STATE_DRAFT,
			self::STATE_PENDING_REVIEW,
			self::STATE_CANCELLED,
		),

		self::STATE_PUBLISHED      => array(
			self::STATE_SUSPENDED,
			self::STATE_EXPIRED,
			self::STATE_CANCELLED,
			self::STATE_ARCHIVED,
		),

		self::STATE_SUSPENDED      => array(
			self::STATE_PUBLISHED, // Unsuspend
			self::STATE_CANCELLED,
			self::STATE_ARCHIVED,
		),

		self::STATE_EXPIRED        => array(
			self::STATE_DRAFT, // Renew
			self::STATE_ARCHIVED,
		),

		self::STATE_CANCELLED      => array(
			self::STATE_DRAFT, // Reopen
		),

		self::STATE_ARCHIVED       => array(
			self::STATE_PUBLISHED, // Unarchive
		),
	);

	// =========================================================================
	// Domain Mappings
	// =========================================================================

	/**
	 * Map domain-specific states to canonical states
	 */
	private const DOMAIN_STATE_MAP = array(
		// Documents domain
		self::DOMAIN_DOCUMENTS => array(
			'draft'          => self::STATE_DRAFT,
			'pending_review' => self::STATE_PENDING_REVIEW,
			'ready'          => self::STATE_APPROVED,
			'signing'        => self::STATE_APPROVED, // Sub-state of approved
			'signed'         => self::STATE_APPROVED, // Sub-state of approved
			'completed'      => self::STATE_PUBLISHED,
			'rejected'       => self::STATE_REJECTED,
			'archived'       => self::STATE_ARCHIVED,
			'cancelled'      => self::STATE_CANCELLED,
		),

		// Groups domain
		self::DOMAIN_GROUPS    => array(
			'draft'     => self::STATE_DRAFT,
			'pending'   => self::STATE_PENDING_REVIEW,
			'active'    => self::STATE_PUBLISHED,
			'suspended' => self::STATE_SUSPENDED,
			'closed'    => self::STATE_ARCHIVED,
		),

		// Ads/Classifieds domain
		self::DOMAIN_ADS       => array(
			'draft'     => self::STATE_DRAFT,
			'pending'   => self::STATE_PENDING_REVIEW,
			'approved'  => self::STATE_APPROVED,
			'published' => self::STATE_PUBLISHED,
			'rejected'  => self::STATE_REJECTED,
			'expired'   => self::STATE_EXPIRED,
			'sold'      => self::STATE_ARCHIVED,
			'removed'   => self::STATE_CANCELLED,
		),

		// Users domain
		self::DOMAIN_USERS     => array(
			'pending'   => self::STATE_PENDING_REVIEW,
			'active'    => self::STATE_PUBLISHED,
			'suspended' => self::STATE_SUSPENDED,
			'banned'    => self::STATE_CANCELLED,
		),
	);

	// =========================================================================
	// Capability Matrix
	// =========================================================================

	/**
	 * Required capability for each transition
	 */
	private const TRANSITION_CAPS = array(
		// Anyone can submit for review
		self::STATE_PENDING_REVIEW => 'read',

		// Moderation
		self::STATE_APPROVED       => 'apollo_moderate',
		self::STATE_REJECTED       => 'apollo_moderate',
		self::STATE_SUSPENDED      => 'apollo_moderate',

		// Publishing
		self::STATE_PUBLISHED      => 'apollo_publish',

		// Archive/Cancel
		self::STATE_ARCHIVED       => 'apollo_archive',
		self::STATE_CANCELLED      => 'apollo_cancel',

		// Back to draft (own items only)
		self::STATE_DRAFT          => 'read',
	);

	/**
	 * Domain-specific capability prefixes
	 */
	private const DOMAIN_CAP_PREFIX = array(
		self::DOMAIN_DOCUMENTS => 'apollo_documents_',
		self::DOMAIN_GROUPS    => 'apollo_groups_',
		self::DOMAIN_ADS       => 'apollo_ads_',
		self::DOMAIN_USERS     => 'apollo_users_',
	);

	// =========================================================================
	// Public API
	// =========================================================================

	/**
	 * Check if a transition is valid
	 *
	 * @param string $from   Current state.
	 * @param string $to     Target state.
	 * @param string $domain Domain (optional).
	 * @return bool True if valid.
	 */
	public static function isValidTransition( string $from, string $to, string $domain = '' ): bool {
		// Same state is always valid
		if ( $from === $to ) {
			return true;
		}

		// Map to canonical if domain provided
		if ( $domain ) {
			$from = self::toCanonical( $from, $domain );
			$to   = self::toCanonical( $to, $domain );
		}

		// Check transition matrix
		if ( ! isset( self::CANONICAL_TRANSITIONS[ $from ] ) ) {
			return false;
		}

		return in_array( $to, self::CANONICAL_TRANSITIONS[ $from ], true );
	}

	/**
	 * Get allowed transitions from a state
	 *
	 * @param string $from   Current state.
	 * @param string $domain Domain (optional).
	 * @return array Allowed target states.
	 */
	public static function getAllowedTransitions( string $from, string $domain = '' ): array {
		if ( $domain ) {
			$from = self::toCanonical( $from, $domain );
		}

		$canonical_allowed = self::CANONICAL_TRANSITIONS[ $from ] ?? array();

		// If domain provided, convert back to domain states
		if ( $domain && isset( self::DOMAIN_STATE_MAP[ $domain ] ) ) {
			$domain_allowed = array();
			$reverse_map    = array_flip( self::DOMAIN_STATE_MAP[ $domain ] );

			foreach ( $canonical_allowed as $canonical ) {
				if ( isset( $reverse_map[ $canonical ] ) ) {
					$domain_allowed[] = $reverse_map[ $canonical ];
				} else {
					$domain_allowed[] = $canonical; // Keep canonical if no mapping
				}
			}

			return $domain_allowed;
		}

		return $canonical_allowed;
	}

	/**
	 * Check if user can perform transition
	 *
	 * @param string $from      Current state.
	 * @param string $to        Target state.
	 * @param string $domain    Domain.
	 * @param int    $user_id   User ID (default current).
	 * @param int    $author_id Author ID (for ownership check).
	 * @return bool True if user can transition.
	 */
	public static function userCanTransition(
		string $from,
		string $to,
		string $domain = '',
		int $user_id = 0,
		int $author_id = 0
	): bool {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		// Admin can do anything
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		// First check if transition is valid
		if ( ! self::isValidTransition( $from, $to, $domain ) ) {
			return false;
		}

		// Get canonical target state
		$canonical_to = $domain ? self::toCanonical( $to, $domain ) : $to;

		// Get required capability
		$base_cap = self::TRANSITION_CAPS[ $canonical_to ] ?? 'apollo_moderate';

		// Add domain prefix if applicable
		if ( $domain && isset( self::DOMAIN_CAP_PREFIX[ $domain ] ) ) {
			$domain_cap = str_replace( 'apollo_', self::DOMAIN_CAP_PREFIX[ $domain ], $base_cap );

			// Check domain-specific cap first
			if ( user_can( $user_id, $domain_cap ) ) {
				return true;
			}
		}

		// Check base cap
		if ( user_can( $user_id, $base_cap ) ) {
			return true;
		}

		// Special case: authors can move their own items to draft
		if ( $canonical_to === self::STATE_DRAFT && $author_id && $user_id === $author_id ) {
			return true;
		}

		return false;
	}

	/**
	 * Perform a transition with validation and logging
	 *
	 * @param string $from      Current state.
	 * @param string $to        Target state.
	 * @param string $domain    Domain.
	 * @param int    $object_id Object ID (post, user, etc).
	 * @param array  $context   Additional context.
	 * @return true|WP_Error True on success, error otherwise.
	 */
	public static function transition(
		string $from,
		string $to,
		string $domain,
		int $object_id,
		array $context = array()
	) {
		$user_id   = $context['user_id'] ?? get_current_user_id();
		$author_id = $context['author_id'] ?? 0;

		// Validate transition
		if ( ! self::isValidTransition( $from, $to, $domain ) ) {
			return new WP_Error(
				'invalid_transition',
				sprintf(
					/* translators: %1$s: from state, %2$s: to state */
					__( 'Transição inválida de "%1$s" para "%2$s".', 'apollo-social' ),
					$from,
					$to
				)
			);
		}

		// Check permission
		if ( ! self::userCanTransition( $from, $to, $domain, $user_id, $author_id ) ) {
			return new WP_Error(
				'permission_denied',
				__( 'Você não tem permissão para esta transição.', 'apollo-social' )
			);
		}

		// Log transition
		if ( class_exists( '\Apollo\Infrastructure\ApolloLogger' ) ) {
			ApolloLogger::info(
				'workflow_transition',
				array(
					'domain'    => $domain,
					'object_id' => $object_id,
					'from'      => $from,
					'to'        => $to,
					'user_id'   => $user_id,
					'reason'    => $context['reason'] ?? '',
				),
				'workflow'
			);
		}

		/**
		 * Action: apollo_workflow_transition
		 *
		 * Fired when a workflow transition is performed.
		 *
		 * @param string $from      Previous state.
		 * @param string $to        New state.
		 * @param string $domain    Domain.
		 * @param int    $object_id Object ID.
		 * @param array  $context   Context.
		 */
		do_action( 'apollo_workflow_transition', $from, $to, $domain, $object_id, $context );

		/**
		 * Domain-specific action
		 */
		do_action( "apollo_workflow_{$domain}_transition", $from, $to, $object_id, $context );

		return true;
	}

	/**
	 * Map domain state to canonical state
	 *
	 * @param string $state  Domain-specific state.
	 * @param string $domain Domain.
	 * @return string Canonical state.
	 */
	public static function toCanonical( string $state, string $domain ): string {
		if ( ! isset( self::DOMAIN_STATE_MAP[ $domain ] ) ) {
			return $state;
		}

		return self::DOMAIN_STATE_MAP[ $domain ][ $state ] ?? $state;
	}

	/**
	 * Map canonical state to domain state
	 *
	 * @param string $state  Canonical state.
	 * @param string $domain Domain.
	 * @return string Domain-specific state.
	 */
	public static function fromCanonical( string $state, string $domain ): string {
		if ( ! isset( self::DOMAIN_STATE_MAP[ $domain ] ) ) {
			return $state;
		}

		$reverse = array_flip( self::DOMAIN_STATE_MAP[ $domain ] );
		return $reverse[ $state ] ?? $state;
	}

	// =========================================================================
	// State Labels & Display
	// =========================================================================

	/**
	 * Get human-readable label for canonical state
	 *
	 * @param string $state Canonical state.
	 * @return string Translated label.
	 */
	public static function getLabel( string $state ): string {
		$labels = array(
			self::STATE_DRAFT          => __( 'Rascunho', 'apollo-social' ),
			self::STATE_PENDING_REVIEW => __( 'Aguardando Revisão', 'apollo-social' ),
			self::STATE_APPROVED       => __( 'Aprovado', 'apollo-social' ),
			self::STATE_REJECTED       => __( 'Rejeitado', 'apollo-social' ),
			self::STATE_SUSPENDED      => __( 'Suspenso', 'apollo-social' ),
			self::STATE_EXPIRED        => __( 'Expirado', 'apollo-social' ),
			self::STATE_CANCELLED      => __( 'Cancelado', 'apollo-social' ),
			self::STATE_ARCHIVED       => __( 'Arquivado', 'apollo-social' ),
			self::STATE_PUBLISHED      => __( 'Publicado', 'apollo-social' ),
		);

		return $labels[ $state ] ?? $state;
	}

	/**
	 * Get badge/color class for state
	 *
	 * @param string $state Canonical state.
	 * @return string CSS class.
	 */
	public static function getBadgeClass( string $state ): string {
		$classes = array(
			self::STATE_DRAFT          => 'badge-secondary',
			self::STATE_PENDING_REVIEW => 'badge-warning',
			self::STATE_APPROVED       => 'badge-info',
			self::STATE_REJECTED       => 'badge-danger',
			self::STATE_SUSPENDED      => 'badge-warning',
			self::STATE_EXPIRED        => 'badge-secondary',
			self::STATE_CANCELLED      => 'badge-dark',
			self::STATE_ARCHIVED       => 'badge-dark',
			self::STATE_PUBLISHED      => 'badge-success',
		);

		return $classes[ $state ] ?? 'badge-secondary';
	}

	/**
	 * Get all domain states
	 *
	 * @param string $domain Domain.
	 * @return array States array.
	 */
	public static function getDomainStates( string $domain ): array {
		return array_keys( self::DOMAIN_STATE_MAP[ $domain ] ?? array() );
	}

	/**
	 * Check if state is terminal (no further action needed)
	 *
	 * @param string $state State.
	 * @return bool True if terminal.
	 */
	public static function isTerminal( string $state ): bool {
		$terminal = array(
			self::STATE_ARCHIVED,
			self::STATE_CANCELLED,
			self::STATE_EXPIRED,
		);

		return in_array( $state, $terminal, true );
	}

	/**
	 * Check if state is active (visible/published)
	 *
	 * @param string $state State.
	 * @return bool True if active.
	 */
	public static function isActive( string $state ): bool {
		$active = array(
			self::STATE_APPROVED,
			self::STATE_PUBLISHED,
		);

		return in_array( $state, $active, true );
	}
}
