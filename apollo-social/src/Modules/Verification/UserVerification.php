<?php
declare(strict_types=1);
namespace Apollo\Modules\Verification;

defined( 'ABSPATH' ) || exit;
final class UserVerification {
	private static array $verification_levels = array(
		'none'     => array(
			'label' => 'Não Verificado',
			'icon'  => '',
			'color' => '#9AA0A6',
		),
		'email'    => array(
			'label' => 'Email Verificado',
			'icon'  => '✉️',
			'color' => '#4CAF50',
		),
		'phone'    => array(
			'label' => 'Telefone Verificado',
			'icon'  => '📱',
			'color' => '#2196F3',
		),
		'document' => array(
			'label' => 'Documento Verificado',
			'icon'  => '📄',
			'color' => '#FF9800',
		),
		'full'     => array(
			'label' => 'Totalmente Verificado',
			'icon'  => '✅',
			'color' => '#9C27B0',
		),
	);
	private static array $badges              = array(
		'verified'   => array(
			'label' => 'Verificado',
			'icon'  => '✓',
			'color' => '#1DA1F2',
			'class' => 'verified',
		),
		'producer'   => array(
			'label' => 'Producer',
			'icon'  => '🎪',
			'color' => '#FF8C42',
			'class' => 'apollo',
		),
		'dj'         => array(
			'label' => 'DJ',
			'icon'  => '🎧',
			'color' => '#63C720',
			'class' => 'green',
		),
		'business'   => array(
			'label' => 'Business',
			'icon'  => '💼',
			'color' => '#EDD815',
			'class' => 'yellow',
		),
		'government' => array(
			'label' => 'Govern',
			'icon'  => '🏛️',
			'color' => '#167CF9',
			'class' => 'blue',
		),
		'moderator'  => array(
			'label' => 'Moderador',
			'icon'  => '🛡️',
			'color' => '#9820C7',
			'class' => 'purple',
		),
		'pioneer'    => array(
			'label' => 'Pioneiro',
			'icon'  => '🚀',
			'color' => '#E91E63',
			'class' => 'pink',
		),
		'supporter'  => array(
			'label' => 'Apoiador',
			'icon'  => '❤️',
			'color' => '#D90D21',
			'class' => 'red',
		),
		'legend'     => array(
			'label' => 'Lenda',
			'icon'  => '🔥',
			'color' => '#FF4500',
			'class' => 'legend',
		),
	);
	public static function init(): void {
		add_action( 'rest_api_init', array( self::class, 'registerEndpoints' ) );
	}
	public static function isVerified( int $user_id ): bool {
		return (bool) get_user_meta( $user_id, '_apollo_verified', true );
	}
	public static function getVerificationLevel( int $user_id ): string {
		return get_user_meta( $user_id, '_apollo_verification_level', true ) ?: 'none';
	}
	public static function verify( int $user_id, string $level = 'full', int $admin_id = 0 ): bool {
		if ( ! isset( self::$verification_levels[ $level ] ) ) {
			return false;
		}
		update_user_meta( $user_id, '_apollo_verified', true );
		update_user_meta( $user_id, '_apollo_verification_level', $level );
		update_user_meta( $user_id, '_apollo_verified_at', current_time( 'mysql', true ) );
		if ( $admin_id ) {
			update_user_meta( $user_id, '_apollo_verified_by', $admin_id );
		}
		do_action( 'apollo_user_verified', $user_id, $level, $admin_id );
		return true;
	}
	public static function unverify( int $user_id ): bool {
		delete_user_meta( $user_id, '_apollo_verified' );
		delete_user_meta( $user_id, '_apollo_verification_level' );
		delete_user_meta( $user_id, '_apollo_verified_at' );
		delete_user_meta( $user_id, '_apollo_verified_by' );
		do_action( 'apollo_user_unverified', $user_id );
		return true;
	}
	public static function getUserBadges( int $user_id ): array {
		$badges = array();
		if ( self::isVerified( $user_id ) ) {
			$badges[] = self::$badges['verified'];
		}
		$identities        = get_user_meta( $user_id, 'apollo_cultura_identities', true ) ?: array();
		$membership_status = get_user_meta( $user_id, 'apollo_membership_status', true );
		$is_approved       = $membership_status === 'approved';
		if ( $is_approved ) {
			if ( array_intersect( array( 'producer_dreamer', 'producer_starter', 'producer_pro' ), $identities ) ) {
				$badges[] = self::$badges['producer'];
			}
			if ( array_intersect( array( 'dj_amateur', 'dj_pro' ), $identities ) ) {
				$badges[] = self::$badges['dj'];
			}
			if ( in_array( 'business', $identities, true ) ) {
				$badges[] = self::$badges['business'];
			}
			if ( in_array( 'government', $identities, true ) ) {
				$badges[] = self::$badges['government'];
			}
		}
		$custom = get_user_meta( $user_id, '_apollo_badges', true ) ?: array();
		foreach ( $custom as $badge_key ) {
			if ( isset( self::$badges[ $badge_key ] ) ) {
				$badges[] = self::$badges[ $badge_key ];
			}
		}
		$rank = get_user_meta( $user_id, '_apollo_rank', true );
		if ( $rank === 'legend' ) {
			$badges[] = self::$badges['legend'];
		}
		return array_unique( $badges, SORT_REGULAR );
	}
	public static function addBadge( int $user_id, string $badge_key ): bool {
		if ( ! isset( self::$badges[ $badge_key ] ) ) {
			return false;
		}
		$badges = get_user_meta( $user_id, '_apollo_badges', true ) ?: array();
		if ( in_array( $badge_key, $badges, true ) ) {
			return false;
		}
		$badges[] = $badge_key;
		update_user_meta( $user_id, '_apollo_badges', $badges );
		do_action( 'apollo_badge_added', $user_id, $badge_key );
		return true;
	}
	public static function removeBadge( int $user_id, string $badge_key ): bool {
		$badges = get_user_meta( $user_id, '_apollo_badges', true ) ?: array();
		$key    = array_search( $badge_key, $badges, true );
		if ( $key === false ) {
			return false;
		}
		unset( $badges[ $key ] );
		update_user_meta( $user_id, '_apollo_badges', array_values( $badges ) );
		do_action( 'apollo_badge_removed', $user_id, $badge_key );
		return true;
	}
	public static function renderBadges( int $user_id ): string {
		$badges = self::getUserBadges( $user_id );
		if ( empty( $badges ) ) {
			return '';
		}
		$html = '<span class="apollo-badges">';
		foreach ( $badges as $badge ) {
			$html .= sprintf(
				'<span class="apollo-badge apollo-badge-%s" style="background:%s" title="%s">%s</span>',
				esc_attr( $badge['class'] ?? 'default' ),
				esc_attr( $badge['color'] ),
				esc_attr( $badge['label'] ),
				$badge['icon']
			);
		}
		$html .= '</span>';
		return $html;
	}
	public static function registerEndpoints(): void {
		register_rest_route(
			'apollo/v1',
			'/verification/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => fn( $r ) => new \WP_REST_Response(
					array(
						'verified' => self::isVerified( (int) $r->get_param( 'id' ) ),
						'level'    => self::getVerificationLevel( (int) $r->get_param( 'id' ) ),
						'badges'   => self::getUserBadges( (int) $r->get_param( 'id' ) ),
					),
					200
				),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/verification/verify',
			array(
				'methods'             => 'POST',
				'callback'            => fn( $r ) => new \WP_REST_Response(
					array(
						'success' => self::verify( (int) $r->get_param( 'user_id' ), $r->get_param( 'level' ) ?: 'full', get_current_user_id() ),
					),
					200
				),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);
		register_rest_route(
			'apollo/v1',
			'/badges/add',
			array(
				'methods'             => 'POST',
				'callback'            => fn( $r ) => new \WP_REST_Response(
					array(
						'success' => self::addBadge( (int) $r->get_param( 'user_id' ), $r->get_param( 'badge' ) ),
					),
					200
				),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);
	}
	public static function getBadgeTypes(): array {
		return self::$badges; }
	public static function getVerificationLevels(): array {
		return self::$verification_levels; }
}
add_action( 'plugins_loaded', array( UserVerification::class, 'init' ), 15 );
