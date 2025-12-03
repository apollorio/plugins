<?php
declare(strict_types=1);

namespace Apollo\Modules\Registration;

/**
 * Cultura::Rio Identity System
 *
 * Manages user cultural identity registration and membership tracking.
 * Meta keys are admin-only visible for membership status tracking.
 *
 * Meta Keys (admin-only):
 * - apollo_cultura_identities: array of selected identities
 * - apollo_cultura_registered_at: timestamp of registration
 * - apollo_cultura_original_identities: original choices (immutable)
 * - apollo_membership_requested: array of membership types requested
 * - apollo_membership_status: 'pending', 'approved', 'rejected'
 * - apollo_membership_approved_at: timestamp when admin approved
 * - apollo_membership_approved_by: admin user ID who approved
 *
 * @package Apollo_Social
 * @since 1.2.0
 */
class CulturaRioIdentity {

	/**
	 * Identity options with codes
	 *
	 * @var array
	 */
	private static array $identities = array(
		'clubber'           => array(
			'label'                                => 'Clubber, daqueles que participativos ou não, agora estamos juntxs!',
			'code'                                 => 'a',
			'locked'                               => true, 
			// Always selected, can't be deselected
								'membership_level' => null, 
	// No membership required
		),
		'dj_amateur'        => array(
			'label'            => 'DJ, aspirante/amador',
			'code'             => 'b',
			'locked'           => false,
			'membership_level' => 'dj_amateur',
		),
		'dj_pro'            => array(
			'label'            => 'DJ, profissional',
			'code'             => 'c',
			'locked'           => false,
			'membership_level' => 'dj_professional',
		),
		'producer_dreamer'  => array(
			'label'            => 'Producer de Eventos, quero iniciar meu sonho (evento)',
			'code'             => 'd',
			'locked'           => false,
			'membership_level' => 'event_producer_starter',
		),
		'producer_starter'  => array(
			'label'            => 'Producer de Eventos, iniciando eventos',
			'code'             => 'e',
			'locked'           => false,
			'membership_level' => 'event_producer_active',
		),
		'producer_pro'      => array(
			'label'            => 'Producer de Eventos, profissional',
			'code'             => 'f',
			'locked'           => false,
			'membership_level' => 'event_producer_professional',
		),
		'music_producer'    => array(
			'label'            => 'Producer de Música',
			'code'             => 'j',
			'locked'           => false,
			'membership_level' => 'music_producer',
		),
		'cultural_producer' => array(
			'label'            => 'Producer Cultural',
			'code'             => 'k',
			'locked'           => false,
			'membership_level' => 'cultural_producer',
		),
		'business'          => array(
			'label'            => 'Business Person',
			'code'             => 'l',
			'locked'           => false,
			'membership_level' => 'business',
		),
		'government'        => array(
			'label'            => 'Government',
			'code'             => 'm',
			'locked'           => false,
			'membership_level' => 'government',
		),
		'promoter'          => array(
			'label'            => 'Promoter',
			'code'             => 'n',
			'locked'           => false,
			'membership_level' => 'promoter',
		),
		'visual_artist'     => array(
			'label'            => 'Visual Artist',
			'code'             => 'p',
			'locked'           => false,
			'membership_level' => 'visual_artist',
		),
	);

	/**
	 * Get all identity options
	 *
	 * @return array
	 */
	public static function getIdentities(): array {
		return self::$identities;
	}

	/**
	 * Get remarks for identities (static content)
	 *
	 * @return array
	 */
	public static function getRemarks(): array {
		return array(
			'business'      => 'Vendo Produto / Serviço para eventos e artistas cariocas, de equipamento de sistema de som, luz; materiao gráfico / filmagens; gravadora / stúdio de música; cursos de DJ; bar consignado; entre diversos outros.',
			'visual_artist' => 'Designer; Photographer; Artistas Plásticos; Video Motion; entre diversos outros.',
		);
	}

	/**
	 * Get locked identities (always selected)
	 *
	 * @return array
	 */
	public static function getLockedIdentities(): array {
		return array_filter( self::$identities, fn( $identity ) => $identity['locked'] === true );
	}

	/**
	 * Save user cultural identity
	 *
	 * @param int   $user_id
	 * @param array $selected_identities
	 * @return bool
	 */
	public static function saveUserIdentity( int $user_id, array $selected_identities ): bool {
		// Always ensure 'clubber' is included
		if ( ! in_array( 'clubber', $selected_identities, true ) ) {
			array_unshift( $selected_identities, 'clubber' );
		}

		// Validate identities
		$valid_keys          = array_keys( self::$identities );
		$selected_identities = array_filter( $selected_identities, fn( $id ) => in_array( $id, $valid_keys, true ) );

		// Save current identities
		update_user_meta( $user_id, 'apollo_cultura_identities', $selected_identities );

		// Save registration timestamp
		$registered_at = get_user_meta( $user_id, 'apollo_cultura_registered_at', true );
		if ( empty( $registered_at ) ) {
			update_user_meta( $user_id, 'apollo_cultura_registered_at', current_time( 'mysql' ) );
			// Save original choices (immutable for admin reference)
			update_user_meta( $user_id, 'apollo_cultura_original_identities', $selected_identities );
		}

		// Determine membership requests (exclude clubber)
		$membership_requests = array();
		foreach ( $selected_identities as $identity_key ) {
			if ( $identity_key === 'clubber' ) {
				continue;
			}
			$identity = self::$identities[ $identity_key ] ?? null;
			if ( $identity && ! empty( $identity['membership_level'] ) ) {
				$membership_requests[] = $identity['membership_level'];
			}
		}

		// Save membership requests if any
		if ( ! empty( $membership_requests ) ) {
			update_user_meta( $user_id, 'apollo_membership_requested', $membership_requests );

			// Set initial status as pending if not already set
			$current_status = get_user_meta( $user_id, 'apollo_membership_status', true );
			if ( empty( $current_status ) ) {
				update_user_meta( $user_id, 'apollo_membership_status', 'pending' );
				update_user_meta( $user_id, 'apollo_membership_requested_at', current_time( 'mysql' ) );
			}
		}

		// Log for audit
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'[Apollo] User %d registered with Cultura::Rio identities: %s',
					$user_id,
					implode( ', ', $selected_identities )
				)
			);
		}

		return true;
	}

	/**
	 * Get user's cultural identities
	 *
	 * @param int $user_id
	 * @return array
	 */
	public static function getUserIdentities( int $user_id ): array {
		$identities = get_user_meta( $user_id, 'apollo_cultura_identities', true );
		return is_array( $identities ) ? $identities : array( 'clubber' );
	}

	/**
	 * Get user's original registration identities (admin reference)
	 *
	 * @param int $user_id
	 * @return array
	 */
	public static function getUserOriginalIdentities( int $user_id ): array {
		$identities = get_user_meta( $user_id, 'apollo_cultura_original_identities', true );
		return is_array( $identities ) ? $identities : array();
	}

	/**
	 * Get user's membership status
	 *
	 * @param int $user_id
	 * @return array
	 */
	public static function getMembershipStatus( int $user_id ): array {
		return array(
			'requested'    => get_user_meta( $user_id, 'apollo_membership_requested', true ) ?: array(),
			'status'       => get_user_meta( $user_id, 'apollo_membership_status', true ) ?: 'none',
			'requested_at' => get_user_meta( $user_id, 'apollo_membership_requested_at', true ) ?: null,
			'approved_at'  => get_user_meta( $user_id, 'apollo_membership_approved_at', true ) ?: null,
			'approved_by'  => get_user_meta( $user_id, 'apollo_membership_approved_by', true ) ?: null,
		);
	}

	/**
	 * Approve user membership (admin only)
	 *
	 * @param int        $user_id
	 * @param int        $admin_id
	 * @param array|null $approved_memberships Specific memberships to approve (null = all requested)
	 * @return bool
	 */
	public static function approveMembership( int $user_id, int $admin_id, ?array $approved_memberships = null ): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$requested = get_user_meta( $user_id, 'apollo_membership_requested', true ) ?: array();

		// If specific memberships provided, use those; otherwise approve all
		$to_approve = $approved_memberships ?? $requested;

		update_user_meta( $user_id, 'apollo_membership_status', 'approved' );
		update_user_meta( $user_id, 'apollo_membership_approved', $to_approve );
		update_user_meta( $user_id, 'apollo_membership_approved_at', current_time( 'mysql' ) );
		update_user_meta( $user_id, 'apollo_membership_approved_by', $admin_id );

		// Fire action for other integrations
		do_action( 'apollo_membership_approved', $user_id, $to_approve, $admin_id );

		return true;
	}

	/**
	 * Reject user membership (admin only)
	 *
	 * @param int    $user_id
	 * @param int    $admin_id
	 * @param string $reason
	 * @return bool
	 */
	public static function rejectMembership( int $user_id, int $admin_id, string $reason = '' ): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		update_user_meta( $user_id, 'apollo_membership_status', 'rejected' );
		update_user_meta( $user_id, 'apollo_membership_rejected_at', current_time( 'mysql' ) );
		update_user_meta( $user_id, 'apollo_membership_rejected_by', $admin_id );
		if ( ! empty( $reason ) ) {
			update_user_meta( $user_id, 'apollo_membership_rejection_reason', sanitize_textarea_field( $reason ) );
		}

		// Fire action for other integrations
		do_action( 'apollo_membership_rejected', $user_id, $admin_id, $reason );

		return true;
	}

	/**
	 * Get membership journey message for user
	 * Used for future "congratulations" messages
	 *
	 * @param int $user_id
	 * @return array|null
	 */
	public static function getMembershipJourney( int $user_id ): ?array {
		$original = self::getUserOriginalIdentities( $user_id );
		$current  = self::getUserIdentities( $user_id );
		$status   = self::getMembershipStatus( $user_id );

		if ( empty( $original ) ) {
			return null;
		}

		// Build journey data
		$journey = array(
			'started_as'        => $original,
			'current'           => $current,
			'membership_status' => $status['status'],
			'registered_at'     => get_user_meta( $user_id, 'apollo_cultura_registered_at', true ),
		);

		// Add progression messages
		if ( $status['status'] === 'approved' && in_array( 'dj_amateur', $original, true ) ) {
			$journey['progression_message'] = sprintf(
				'Olha, você estava tentando ser DJ, agora temos orgulho de fazer parte disso com você. Desejamos tudo de melhor!',
				get_user_meta( $user_id, 'display_name', true )
			);
		}

		return $journey;
	}

	/**
	 * Get identity label by key
	 *
	 * @param string $key
	 * @return string
	 */
	public static function getIdentityLabel( string $key ): string {
		return self::$identities[ $key ]['label'] ?? $key;
	}

	/**
	 * Get identity labels from keys
	 *
	 * @param array $keys
	 * @return array
	 */
	public static function getIdentityLabels( array $keys ): array {
		$labels = array();
		foreach ( $keys as $key ) {
			$labels[ $key ] = self::getIdentityLabel( $key );
		}
		return $labels;
	}
}
