<?php
declare(strict_types=1);
namespace Apollo\Modules\Gamification;

defined( 'ABSPATH' ) || exit;
final class PointsSystem {
	private static ?self $instance = null;
	private static array $triggers = array(
		'user_register'        => array(
			'points' => 50,
			'label'  => 'Cadastro completo',
			'once'   => true,
		),
		'profile_complete'     => array(
			'points' => 100,
			'label'  => 'Perfil 100%',
			'once'   => true,
		),
		'avatar_upload'        => array(
			'points' => 25,
			'label'  => 'Avatar enviado',
			'once'   => true,
		),
		'post_created'         => array(
			'points'      => 10,
			'label'       => 'Post criado',
			'daily_limit' => 5,
		),
		'post_liked'           => array(
			'points'      => 2,
			'label'       => 'Recebeu curtida',
			'daily_limit' => 50,
		),
		'gave_like'            => array(
			'points'      => 1,
			'label'       => 'Curtiu conteúdo',
			'daily_limit' => 20,
		),
		'comment_posted'       => array(
			'points'      => 5,
			'label'       => 'Comentou',
			'daily_limit' => 10,
		),
		'received_comment'     => array(
			'points'      => 3,
			'label'       => 'Recebeu comentário',
			'daily_limit' => 20,
		),
		'event_created'        => array(
			'points' => 50,
			'label'  => 'Evento criado',
		),
		'event_attended'       => array(
			'points' => 25,
			'label'  => 'Participou de evento',
		),
		'event_interested'     => array(
			'points'      => 5,
			'label'       => 'Interesse em evento',
			'daily_limit' => 10,
		),
		'classified_posted'    => array(
			'points' => 30,
			'label'  => 'Anúncio publicado',
		),
		'classified_sold'      => array(
			'points' => 50,
			'label'  => 'Anúncio vendido',
		),
		'group_created'        => array(
			'points' => 75,
			'label'  => 'Grupo criado',
			'once'   => true,
		),
		'group_joined'         => array(
			'points'      => 15,
			'label'       => 'Entrou em grupo',
			'daily_limit' => 3,
		),
		'invite_sent'          => array(
			'points'      => 10,
			'label'       => 'Convite enviado',
			'daily_limit' => 5,
		),
		'invite_accepted'      => array(
			'points' => 25,
			'label'  => 'Convite aceito',
		),
		'bubble_added'         => array(
			'points' => 20,
			'label'  => 'Close friend adicionado',
			'max'    => 15,
		),
		'document_signed'      => array(
			'points' => 40,
			'label'  => 'Documento assinado',
		),
		'document_created'     => array(
			'points' => 20,
			'label'  => 'Documento criado',
		),
		'first_login'          => array(
			'points' => 10,
			'label'  => 'Primeiro login',
			'once'   => true,
		),
		'daily_login'          => array(
			'points' => 5,
			'label'  => 'Login diário',
		),
		'streak_7_days'        => array(
			'points' => 50,
			'label'  => 'Streak 7 dias',
			'once'   => false,
		),
		'streak_30_days'       => array(
			'points' => 200,
			'label'  => 'Streak 30 dias',
			'once'   => false,
		),
		'share_event'          => array(
			'points'      => 5,
			'label'       => 'Compartilhou evento',
			'daily_limit' => 5,
		),
		'profile_viewed'       => array(
			'points'      => 1,
			'label'       => 'Perfil visualizado',
			'daily_limit' => 100,
		),
		'supplier_contact'     => array(
			'points' => 15,
			'label'  => 'Contato fornecedor',
		),
		'testimonial_given'    => array(
			'points' => 20,
			'label'  => 'Depoimento dado',
		),
		'testimonial_received' => array(
			'points' => 30,
			'label'  => 'Depoimento recebido',
		),
		'verified_member'      => array(
			'points' => 500,
			'label'  => 'Membro verificado',
			'once'   => true,
		),
		'membership_approved'  => array(
			'points' => 100,
			'label'  => 'Membership aprovado',
			'once'   => true,
		),
		'first_event'          => array(
			'points' => 100,
			'label'  => 'Primeiro evento',
			'once'   => true,
		),
		'first_classified'     => array(
			'points' => 50,
			'label'  => 'Primeiro anúncio',
			'once'   => true,
		),
		'first_group'          => array(
			'points' => 50,
			'label'  => 'Primeiro grupo',
			'once'   => true,
		),
		'nucleo_created'       => array(
			'points' => 100,
			'label'  => 'Núcleo criado',
		),
		'nucleo_invite_sent'   => array(
			'points' => 15,
			'label'  => 'Convite núcleo',
		),
		'comuna_moderator'     => array(
			'points' => 200,
			'label'  => 'Moderador de comunidade',
			'once'   => true,
		),
		'content_featured'     => array(
			'points' => 100,
			'label'  => 'Conteúdo destacado',
		),
		'report_spam'          => array(
			'points'      => 10,
			'label'       => 'Reportou spam',
			'daily_limit' => 5,
		),
		'help_newbie'          => array(
			'points' => 25,
			'label'  => 'Ajudou novato',
		),
		'complete_onboarding'  => array(
			'points' => 75,
			'label'  => 'Onboarding completo',
			'once'   => true,
		),
	);
	private static array $ranks    = array(
		'bronze'   => array(
			'min'   => 0,
			'label' => 'Bronze',
			'color' => '#CD7F32',
			'icon'  => '🥉',
		),
		'silver'   => array(
			'min'   => 250,
			'label' => 'Prata',
			'color' => '#C0C0C0',
			'icon'  => '🥈',
		),
		'gold'     => array(
			'min'   => 1000,
			'label' => 'Ouro',
			'color' => '#FFD700',
			'icon'  => '🥇',
		),
		'platinum' => array(
			'min'   => 2500,
			'label' => 'Platina',
			'color' => '#E5E4E2',
			'icon'  => '💎',
		),
		'diamond'  => array(
			'min'   => 5000,
			'label' => 'Diamante',
			'color' => '#B9F2FF',
			'icon'  => '💠',
		),
		'master'   => array(
			'min'   => 10000,
			'label' => 'Master',
			'color' => '#9400D3',
			'icon'  => '👑',
		),
		'legend'   => array(
			'min'   => 25000,
			'label' => 'Lenda',
			'color' => '#FF4500',
			'icon'  => '🔥',
		),
	);
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	public function init(): void {
		add_action( 'user_register', fn( $id ) => $this->award( $id, 'user_register' ) );
		add_action( 'wp_login', fn( $login, $user ) => $this->handleLogin( $user->ID ), 10, 2 );
		add_action( 'apollo_post_liked', fn( $post_id, $user_id ) => $this->handleLike( $post_id, $user_id ), 10, 2 );
		add_action( 'apollo_comment_posted', fn( $comment_id, $user_id ) => $this->award( $user_id, 'comment_posted' ), 10, 2 );
		add_action( 'apollo_event_created', fn( $event_id, $user_id ) => $this->award( $user_id, 'event_created' ), 10, 2 );
		add_action( 'apollo_event_interested', fn( $event_id, $user_id ) => $this->award( $user_id, 'event_interested' ), 10, 2 );
		add_action( 'apollo_classified_published', fn( $ad_id, $user_id ) => $this->award( $user_id, 'classified_posted' ), 10, 2 );
		add_action( 'apollo_group_joined', fn( $group_id, $user_id ) => $this->award( $user_id, 'group_joined' ), 10, 2 );
		add_action( 'apollo_bubble_added', fn( $user_id, $friend_id ) => $this->award( $user_id, 'bubble_added' ), 10, 2 );
		add_action( 'apollo_document_signed', fn( $doc_id, $user_id ) => $this->award( $user_id, 'document_signed' ), 10, 2 );
		add_action( 'apollo_membership_approved', fn( $user_id ) => $this->award( $user_id, 'membership_approved' ) );
		add_action( 'rest_api_init', array( $this, 'registerEndpoints' ) );
	}
	public function award( int $user_id, string $trigger, array $meta = array() ): bool {
		if ( ! isset( self::$triggers[ $trigger ] ) ) {
			return false;
		}
		$config = self::$triggers[ $trigger ];
		if ( ! empty( $config['once'] ) && $this->hasTriggered( $user_id, $trigger ) ) {
			return false;
		}
		if ( ! empty( $config['daily_limit'] ) && $this->getDailyCount( $user_id, $trigger ) >= $config['daily_limit'] ) {
			return false;
		}
		$points    = $config['points'];
		$current   = $this->getPoints( $user_id );
		$new_total = $current + $points;
		update_user_meta( $user_id, '_apollo_points', $new_total );
		$this->logTransaction( $user_id, $trigger, $points, $meta );
		$this->checkRankUp( $user_id, $current, $new_total );
		do_action( 'apollo_points_awarded', $user_id, $trigger, $points, $new_total );
		return true;
	}
	public function deduct( int $user_id, int $points, string $reason = '' ): bool {
		$current   = $this->getPoints( $user_id );
		$new_total = max( 0, $current - $points );
		update_user_meta( $user_id, '_apollo_points', $new_total );
		$this->logTransaction( $user_id, 'deduction', -$points, array( 'reason' => $reason ) );
		return true;
	}
	public function getPoints( int $user_id ): int {
		return (int) get_user_meta( $user_id, '_apollo_points', true );
	}
	public function getRank( int $user_id ): array {
		$points       = $this->getPoints( $user_id );
		$current_rank = 'bronze';
		foreach ( self::$ranks as $key => $rank ) {
			if ( $points >= $rank['min'] ) {
				$current_rank = $key;
			}
		}
		$rank_data = self::$ranks[ $current_rank ];
		$next_rank = $this->getNextRank( $current_rank );
		return array(
			'rank'           => $current_rank,
			'label'          => $rank_data['label'],
			'color'          => $rank_data['color'],
			'icon'           => $rank_data['icon'],
			'points'         => $points,
			'next_rank'      => $next_rank ? self::$ranks[ $next_rank ]['label'] : null,
			'points_to_next' => $next_rank ? self::$ranks[ $next_rank ]['min'] - $points : 0,
			'progress'       => $next_rank ? round( ( $points - $rank_data['min'] ) / ( self::$ranks[ $next_rank ]['min'] - $rank_data['min'] ) * 100 ) : 100,
		);
	}
	public function getLeaderboard( int $limit = 20, string $period = 'all' ): array {
		global $wpdb;
		if ( $period === 'all' ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, meta_value as points FROM {$wpdb->usermeta} WHERE meta_key = '_apollo_points' ORDER BY CAST(meta_value AS UNSIGNED) DESC LIMIT %d",
					$limit
				),
				ARRAY_A
			);
		}
		$start = match ( $period ) {
			'daily' => gmdate( 'Y-m-d 00:00:00' ),
			'weekly' => gmdate( 'Y-m-d 00:00:00', strtotime( '-7 days' ) ),
			'monthly' => gmdate( 'Y-m-01 00:00:00' ),
			default => '1970-01-01',
		};
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, SUM(points) as points FROM {$wpdb->prefix}apollo_points_log WHERE created_at >= %s GROUP BY user_id ORDER BY points DESC LIMIT %d",
				$start,
				$limit
			),
			ARRAY_A
		);
	}
	public function registerEndpoints(): void {
		register_rest_route(
			'apollo/v1',
			'/points/me',
			array(
				'methods'             => 'GET',
				'callback'            => fn() => new \WP_REST_Response( $this->getRank( get_current_user_id() ), 200 ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/leaderboard',
			array(
				'methods'             => 'GET',
				'callback'            => fn( $r ) => new \WP_REST_Response( $this->getLeaderboard( (int) ( $r->get_param( 'limit' ) ?: 20 ), $r->get_param( 'period' ) ?: 'all' ), 200 ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'apollo/v1',
			'/points/triggers',
			array(
				'methods'             => 'GET',
				'callback'            => fn() => new \WP_REST_Response( self::$triggers, 200 ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
			)
		);
	}
	private function hasTriggered( int $user_id, string $trigger ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}apollo_points_log WHERE user_id = %d AND trigger_name = %s LIMIT 1",
				$user_id,
				$trigger
			)
		);
	}
	private function getDailyCount( int $user_id, string $trigger ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}apollo_points_log WHERE user_id = %d AND trigger_name = %s AND DATE(created_at) = CURDATE()",
				$user_id,
				$trigger
			)
		);
	}
	private function logTransaction( int $user_id, string $trigger, int $points, array $meta = array() ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'apollo_points_log',
			array(
				'user_id'      => $user_id,
				'trigger_name' => $trigger,
				'points'       => $points,
				'meta'         => wp_json_encode( $meta ),
				'created_at'   => current_time( 'mysql', true ),
			)
		);
	}
	private function checkRankUp( int $user_id, int $old_points, int $new_points ): void {
		$old_rank = $this->calculateRank( $old_points );
		$new_rank = $this->calculateRank( $new_points );
		if ( $old_rank !== $new_rank ) {
			update_user_meta( $user_id, '_apollo_rank', $new_rank );
			do_action( 'apollo_rank_changed', $user_id, $old_rank, $new_rank );
		}
	}
	private function calculateRank( int $points ): string {
		$rank = 'bronze';
		foreach ( self::$ranks as $key => $r ) {
			if ( $points >= $r['min'] ) {
				$rank = $key;
			}
		}
		return $rank;
	}
	private function getNextRank( string $current ): ?string {
		$keys  = array_keys( self::$ranks );
		$index = array_search( $current, $keys );
		return $index !== false && isset( $keys[ $index + 1 ] ) ? $keys[ $index + 1 ] : null;
	}
	private function handleLogin( int $user_id ): void {
		$last_login = get_user_meta( $user_id, '_apollo_last_login', true );
		$today      = gmdate( 'Y-m-d' );
		if ( $last_login !== $today ) {
			$this->award( $user_id, 'daily_login' );
			$streak = (int) get_user_meta( $user_id, '_apollo_login_streak', true );
			if ( $last_login === gmdate( 'Y-m-d', strtotime( '-1 day' ) ) ) {
				++$streak;
				if ( $streak === 7 ) {
					$this->award( $user_id, 'streak_7_days' );
				}
				if ( $streak === 30 ) {
					$this->award( $user_id, 'streak_30_days' );
				}
			} else {
				$streak = 1;
			}
			update_user_meta( $user_id, '_apollo_login_streak', $streak );
			update_user_meta( $user_id, '_apollo_last_login', $today );
		}
	}
	private function handleLike( int $post_id, int $user_id ): void {
		$this->award( $user_id, 'gave_like' );
		$author_id = (int) get_post_field( 'post_author', $post_id );
		if ( $author_id && $author_id !== $user_id ) {
			$this->award( $author_id, 'post_liked' );
		}
	}
	public static function getTriggers(): array {
		return self::$triggers; }
	public static function getRanks(): array {
		return self::$ranks; }
}
add_action( 'plugins_loaded', fn() => PointsSystem::instance()->init(), 15 );
