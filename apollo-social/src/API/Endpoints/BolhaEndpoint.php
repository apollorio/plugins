<?php
/**
 * Apollo Bolha (Bubble) REST API Endpoints
 *
 * Sistema de "Bolha" entre usuários - lista de até 15 pessoas.
 * Relação simétrica baseada em convites com aceitação.
 * Sem métricas de ego (sem contadores públicos).
 *
 * @package Apollo_Social
 * @since 1.3.0
 */

declare(strict_types=1);

namespace Apollo\API\Endpoints;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bolha Endpoint Class
 *
 * Implementa rotas REST para gerenciamento de "Bolha" entre usuários.
 *
 * Meta keys utilizadas:
 * - apollo_bolha: array de user_ids com relação confirmada
 * - apollo_bolha_pedidos_outgoing: array de user_ids para quem enviou pedido
 * - apollo_bolha_pedidos_incoming: array de user_ids que enviaram pedido
 */
class BolhaEndpoint {

	/**
	 * Limite máximo de pessoas na bolha
	 */
	private const BOLHA_LIMIT = 15;

	/**
	 * Namespace da API
	 */
	private const NAMESPACE = 'apollo/v1';

	/**
	 * Register REST routes
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all bolha routes
	 */
	public function register_routes(): void {
		// POST /bolha/pedir - Enviar pedido de bolha
		register_rest_route(
			self::NAMESPACE,
			'/bolha/pedir',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'pedir_bolha' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => array(
					'target_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'description'       => __( 'ID do usuário que receberá o pedido.', 'apollo-social' ),
						'validate_callback' => array( $this, 'validate_user_id' ),
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /bolha/aceitar - Aceitar pedido de bolha
		register_rest_route(
			self::NAMESPACE,
			'/bolha/aceitar',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'aceitar_bolha' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => array(
					'user_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'description'       => __( 'ID do usuário que solicitou a bolha.', 'apollo-social' ),
						'validate_callback' => array( $this, 'validate_user_id' ),
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /bolha/rejeitar - Rejeitar pedido de bolha
		register_rest_route(
			self::NAMESPACE,
			'/bolha/rejeitar',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rejeitar_bolha' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => array(
					'user_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'description'       => __( 'ID do usuário cujo pedido será rejeitado.', 'apollo-social' ),
						'validate_callback' => array( $this, 'validate_user_id' ),
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /bolha/remover - Remover pessoa da bolha
		register_rest_route(
			self::NAMESPACE,
			'/bolha/remover',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'remover_bolha' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => array(
					'user_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'description'       => __( 'ID do usuário a ser removido da bolha.', 'apollo-social' ),
						'validate_callback' => array( $this, 'validate_user_id' ),
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// GET /bolha/listar - Listar pessoas na bolha
		register_rest_route(
			self::NAMESPACE,
			'/bolha/listar',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'listar_bolha' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		// GET /bolha/pedidos - Listar pedidos pendentes
		register_rest_route(
			self::NAMESPACE,
			'/bolha/pedidos',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'listar_pedidos' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			)
		);

		// GET /bolha/status/{user_id} - Status da relação com um usuário
		register_rest_route(
			self::NAMESPACE,
			'/bolha/status/(?P<user_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => array(
					'user_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'validate_callback' => array( $this, 'validate_user_id' ),
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /bolha/cancelar - Cancelar pedido enviado
		register_rest_route(
			self::NAMESPACE,
			'/bolha/cancelar',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'cancelar_pedido' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => array(
					'user_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'description'       => __( 'ID do usuário para quem o pedido foi enviado.', 'apollo-social' ),
						'validate_callback' => array( $this, 'validate_user_id' ),
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Check if user is logged in
	 *
	 * @return bool|WP_Error
	 */
	public function check_logged_in() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'Você precisa estar logado para acessar esta funcionalidade.', 'apollo-social' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Validate user ID exists
	 *
	 * @param mixed $value Value to validate.
	 * @return bool
	 */
	public function validate_user_id( $value ): bool {
		$user_id = absint( $value );
		return $user_id > 0 && get_user_by( 'id', $user_id ) !== false;
	}

	/**
	 * POST /bolha/pedir - Enviar pedido de bolha
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function pedir_bolha( WP_REST_Request $request ) {
		$current_user_id = get_current_user_id();
		$target_id       = (int) $request->get_param( 'target_id' );

		// Não pode pedir para si mesmo
		if ( $current_user_id === $target_id ) {
			return new WP_Error(
				'bolha_self_request',
				__( 'Você não pode adicionar a si mesmo à sua bolha.', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		// Obter dados atuais
		$minha_bolha     = $this->get_bolha( $current_user_id );
		$target_bolha    = $this->get_bolha( $target_id );
		$meus_outgoing   = $this->get_pedidos_outgoing( $current_user_id );
		$meus_incoming   = $this->get_pedidos_incoming( $current_user_id );
		$target_incoming = $this->get_pedidos_incoming( $target_id );
		$target_outgoing = $this->get_pedidos_outgoing( $target_id );

		// Verificar se já está na bolha
		if ( in_array( $target_id, $minha_bolha, true ) ) {
			return new WP_Error(
				'bolha_already_exists',
				__( 'Esta pessoa já está na sua bolha.', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		// Verificar se já existe pedido outgoing
		if ( in_array( $target_id, $meus_outgoing, true ) ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'status'  => 'pedido_ja_enviado',
					'message' => __( 'Você já enviou um pedido para esta pessoa. Aguardando resposta.', 'apollo-social' ),
				)
			);
		}

		// Verificar se existe pedido incoming (o target já pediu para eu)
		if ( in_array( $target_id, $meus_incoming, true ) ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'status'  => 'pedido_pendente_aceitar',
					'message' => __( 'Esta pessoa já te enviou um pedido! Aceite para criar a bolha.', 'apollo-social' ),
				)
			);
		}

		// Verificar limite de bolha do solicitante
		if ( count( $minha_bolha ) >= self::BOLHA_LIMIT ) {
			return new WP_Error(
				'bolha_limit_reached',
				__( 'Sua bolha está cheia (máx. 15 pessoas).', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		// Verificar limite de bolha do destinatário
		if ( count( $target_bolha ) >= self::BOLHA_LIMIT ) {
			return new WP_Error(
				'bolha_target_limit_reached',
				__( 'A bolha dessa pessoa está cheia (máx. 15 pessoas).', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		// Adicionar pedido
		$meus_outgoing[]   = $target_id;
		$target_incoming[] = $current_user_id;

		update_user_meta( $current_user_id, 'apollo_bolha_pedidos_outgoing', array_unique( $meus_outgoing ) );
		update_user_meta( $target_id, 'apollo_bolha_pedidos_incoming', array_unique( $target_incoming ) );

		// Disparar ação para notificações
		do_action( 'apollo_bolha_pedido_enviado', $current_user_id, $target_id );

		return rest_ensure_response(
			array(
				'success' => true,
				'status'  => 'pedido_enviado',
				'message' => __( 'Pedido de bolha enviado com sucesso!', 'apollo-social' ),
			)
		);
	}

	/**
	 * POST /bolha/aceitar - Aceitar pedido de bolha
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function aceitar_bolha( WP_REST_Request $request ) {
		$current_user_id = get_current_user_id();
		$solicitante_id  = (int) $request->get_param( 'user_id' );

		// Obter dados
		$meus_incoming        = $this->get_pedidos_incoming( $current_user_id );
		$solicitante_outgoing = $this->get_pedidos_outgoing( $solicitante_id );

		// Verificar se existe pedido pendente
		if ( ! in_array( $solicitante_id, $meus_incoming, true ) ) {
			return new WP_Error(
				'bolha_no_pending_request',
				__( 'Não existe pedido pendente desta pessoa.', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		// Verificar limite de bolha para ambos
		$minha_bolha       = $this->get_bolha( $current_user_id );
		$solicitante_bolha = $this->get_bolha( $solicitante_id );

		if ( count( $minha_bolha ) >= self::BOLHA_LIMIT ) {
			return new WP_Error(
				'bolha_limit_reached',
				__( 'Sua bolha está cheia (máx. 15 pessoas).', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		if ( count( $solicitante_bolha ) >= self::BOLHA_LIMIT ) {
			return new WP_Error(
				'bolha_solicitante_limit_reached',
				__( 'A bolha da pessoa que te convidou está cheia.', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		// Remover dos pendentes
		$meus_incoming        = array_diff( $meus_incoming, array( $solicitante_id ) );
		$solicitante_outgoing = array_diff( $solicitante_outgoing, array( $current_user_id ) );

		update_user_meta( $current_user_id, 'apollo_bolha_pedidos_incoming', array_values( $meus_incoming ) );
		update_user_meta( $solicitante_id, 'apollo_bolha_pedidos_outgoing', array_values( $solicitante_outgoing ) );

		// Adicionar à bolha de ambos (relação simétrica)
		$minha_bolha[]       = $solicitante_id;
		$solicitante_bolha[] = $current_user_id;

		update_user_meta( $current_user_id, 'apollo_bolha', array_unique( $minha_bolha ) );
		update_user_meta( $solicitante_id, 'apollo_bolha', array_unique( $solicitante_bolha ) );

		// Disparar ação para notificações
		do_action( 'apollo_bolha_criada', $current_user_id, $solicitante_id );

		return rest_ensure_response(
			array(
				'success' => true,
				'status'  => 'bolha_criada',
				'message' => __( 'Bolha criada! Vocês agora estão conectados.', 'apollo-social' ),
			)
		);
	}

	/**
	 * POST /bolha/rejeitar - Rejeitar pedido de bolha
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rejeitar_bolha( WP_REST_Request $request ) {
		$current_user_id = get_current_user_id();
		$solicitante_id  = (int) $request->get_param( 'user_id' );

		// Obter dados
		$meus_incoming        = $this->get_pedidos_incoming( $current_user_id );
		$solicitante_outgoing = $this->get_pedidos_outgoing( $solicitante_id );

		// Verificar se existe pedido pendente
		if ( ! in_array( $solicitante_id, $meus_incoming, true ) ) {
			return new WP_Error(
				'bolha_no_pending_request',
				__( 'Não existe pedido pendente desta pessoa.', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		// Remover dos pendentes
		$meus_incoming        = array_diff( $meus_incoming, array( $solicitante_id ) );
		$solicitante_outgoing = array_diff( $solicitante_outgoing, array( $current_user_id ) );

		update_user_meta( $current_user_id, 'apollo_bolha_pedidos_incoming', array_values( $meus_incoming ) );
		update_user_meta( $solicitante_id, 'apollo_bolha_pedidos_outgoing', array_values( $solicitante_outgoing ) );

		// Disparar ação para notificações (opcional)
		do_action( 'apollo_bolha_pedido_rejeitado', $current_user_id, $solicitante_id );

		return rest_ensure_response(
			array(
				'success' => true,
				'status'  => 'pedido_rejeitado',
				'message' => __( 'Pedido rejeitado.', 'apollo-social' ),
			)
		);
	}

	/**
	 * POST /bolha/remover - Remover pessoa da bolha
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function remover_bolha( WP_REST_Request $request ) {
		$current_user_id = get_current_user_id();
		$target_id       = (int) $request->get_param( 'user_id' );

		// Obter bolhas de ambos
		$minha_bolha  = $this->get_bolha( $current_user_id );
		$target_bolha = $this->get_bolha( $target_id );

		// Remover de ambos os lados
		$minha_bolha  = array_diff( $minha_bolha, array( $target_id ) );
		$target_bolha = array_diff( $target_bolha, array( $current_user_id ) );

		update_user_meta( $current_user_id, 'apollo_bolha', array_values( $minha_bolha ) );
		update_user_meta( $target_id, 'apollo_bolha', array_values( $target_bolha ) );

		// Limpar qualquer resquício em pending também
		$this->limpar_pendentes( $current_user_id, $target_id );

		// Disparar ação
		do_action( 'apollo_bolha_removida', $current_user_id, $target_id );

		return rest_ensure_response(
			array(
				'success' => true,
				'status'  => 'removido_da_bolha',
				'message' => __( 'Pessoa removida da sua bolha.', 'apollo-social' ),
			)
		);
	}

	/**
	 * POST /bolha/cancelar - Cancelar pedido enviado
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function cancelar_pedido( WP_REST_Request $request ) {
		$current_user_id = get_current_user_id();
		$target_id       = (int) $request->get_param( 'user_id' );

		// Obter dados
		$meus_outgoing   = $this->get_pedidos_outgoing( $current_user_id );
		$target_incoming = $this->get_pedidos_incoming( $target_id );

		// Verificar se existe pedido pendente
		if ( ! in_array( $target_id, $meus_outgoing, true ) ) {
			return new WP_Error(
				'bolha_no_outgoing_request',
				__( 'Não existe pedido pendente para esta pessoa.', 'apollo-social' ),
				array( 'status' => 400 )
			);
		}

		// Remover dos pendentes
		$meus_outgoing   = array_diff( $meus_outgoing, array( $target_id ) );
		$target_incoming = array_diff( $target_incoming, array( $current_user_id ) );

		update_user_meta( $current_user_id, 'apollo_bolha_pedidos_outgoing', array_values( $meus_outgoing ) );
		update_user_meta( $target_id, 'apollo_bolha_pedidos_incoming', array_values( $target_incoming ) );

		return rest_ensure_response(
			array(
				'success' => true,
				'status'  => 'pedido_cancelado',
				'message' => __( 'Pedido cancelado.', 'apollo-social' ),
			)
		);
	}

	/**
	 * GET /bolha/listar - Listar pessoas na bolha
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function listar_bolha( WP_REST_Request $request ): WP_REST_Response {
		$current_user_id = get_current_user_id();
		$bolha_ids       = $this->get_bolha( $current_user_id );

		$users = array();
		foreach ( $bolha_ids as $user_id ) {
			$user_data = $this->format_user_minimal( $user_id );
			if ( $user_data ) {
				$users[] = $user_data;
			}
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $users,
			)
		);
	}

	/**
	 * GET /bolha/pedidos - Listar pedidos pendentes
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function listar_pedidos( WP_REST_Request $request ): WP_REST_Response {
		$current_user_id = get_current_user_id();

		$incoming_ids = $this->get_pedidos_incoming( $current_user_id );
		$outgoing_ids = $this->get_pedidos_outgoing( $current_user_id );

		$incoming = array();
		foreach ( $incoming_ids as $user_id ) {
			$user_data = $this->format_user_minimal( $user_id );
			if ( $user_data ) {
				$incoming[] = $user_data;
			}
		}

		$outgoing = array();
		foreach ( $outgoing_ids as $user_id ) {
			$user_data = $this->format_user_minimal( $user_id );
			if ( $user_data ) {
				$outgoing[] = $user_data;
			}
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(
					'incoming' => $incoming,
					'outgoing' => $outgoing,
				),
			)
		);
	}

	/**
	 * GET /bolha/status/{user_id} - Status da relação com um usuário
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_status( WP_REST_Request $request ): WP_REST_Response {
		$current_user_id = get_current_user_id();
		$target_id       = (int) $request->get_param( 'user_id' );

		$bolha    = $this->get_bolha( $current_user_id );
		$outgoing = $this->get_pedidos_outgoing( $current_user_id );
		$incoming = $this->get_pedidos_incoming( $current_user_id );

		$status = 'none';
		if ( in_array( $target_id, $bolha, true ) ) {
			$status = 'na_bolha';
		} elseif ( in_array( $target_id, $outgoing, true ) ) {
			$status = 'pedido_enviado';
		} elseif ( in_array( $target_id, $incoming, true ) ) {
			$status = 'pedido_recebido';
		}

		// Verificar limites
		$minha_bolha_count  = count( $bolha );
		$target_bolha_count = count( $this->get_bolha( $target_id ) );

		return rest_ensure_response(
			array(
				'success'            => true,
				'status'             => $status,
				'pode_pedir'         => $status === 'none' && $minha_bolha_count < self::BOLHA_LIMIT && $target_bolha_count < self::BOLHA_LIMIT,
				'minha_bolha_cheia'  => $minha_bolha_count >= self::BOLHA_LIMIT,
				'target_bolha_cheia' => $target_bolha_count >= self::BOLHA_LIMIT,
			)
		);
	}

	// =========================================
	// Helper Methods
	// =========================================

	/**
	 * Get bolha array for user
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function get_bolha( int $user_id ): array {
		$bolha = get_user_meta( $user_id, 'apollo_bolha', true );
		return is_array( $bolha ) ? array_map( 'absint', $bolha ) : array();
	}

	/**
	 * Get outgoing requests for user
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function get_pedidos_outgoing( int $user_id ): array {
		$outgoing = get_user_meta( $user_id, 'apollo_bolha_pedidos_outgoing', true );
		return is_array( $outgoing ) ? array_map( 'absint', $outgoing ) : array();
	}

	/**
	 * Get incoming requests for user
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function get_pedidos_incoming( int $user_id ): array {
		$incoming = get_user_meta( $user_id, 'apollo_bolha_pedidos_incoming', true );
		return is_array( $incoming ) ? array_map( 'absint', $incoming ) : array();
	}

	/**
	 * Limpar pendentes entre dois usuários
	 *
	 * @param int $user_a First user ID.
	 * @param int $user_b Second user ID.
	 */
	private function limpar_pendentes( int $user_a, int $user_b ): void {
		// A's outgoing
		$a_outgoing = $this->get_pedidos_outgoing( $user_a );
		$a_outgoing = array_diff( $a_outgoing, array( $user_b ) );
		update_user_meta( $user_a, 'apollo_bolha_pedidos_outgoing', array_values( $a_outgoing ) );

		// A's incoming
		$a_incoming = $this->get_pedidos_incoming( $user_a );
		$a_incoming = array_diff( $a_incoming, array( $user_b ) );
		update_user_meta( $user_a, 'apollo_bolha_pedidos_incoming', array_values( $a_incoming ) );

		// B's outgoing
		$b_outgoing = $this->get_pedidos_outgoing( $user_b );
		$b_outgoing = array_diff( $b_outgoing, array( $user_a ) );
		update_user_meta( $user_b, 'apollo_bolha_pedidos_outgoing', array_values( $b_outgoing ) );

		// B's incoming
		$b_incoming = $this->get_pedidos_incoming( $user_b );
		$b_incoming = array_diff( $b_incoming, array( $user_a ) );
		update_user_meta( $user_b, 'apollo_bolha_pedidos_incoming', array_values( $b_incoming ) );
	}

	/**
	 * Format user data minimally (no counters, no ego metrics)
	 *
	 * @param int $user_id User ID.
	 * @return array|null
	 */
	private function format_user_minimal( int $user_id ): ?array {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return null;
		}

		return array(
			'id'           => $user->ID,
			'display_name' => $user->display_name,
			'user_login'   => $user->user_login,
			'avatar_url'   => get_avatar_url( $user->ID, array( 'size' => 96 ) ),
			'profile_url'  => home_url( '/u/' . $user->user_login . '/' ),
		);
	}

	/**
	 * Get bolha IDs for a user (static helper for external use)
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public static function get_user_bolha_ids( int $user_id ): array {
		$bolha = get_user_meta( $user_id, 'apollo_bolha', true );
		return is_array( $bolha ) ? array_map( 'absint', $bolha ) : array();
	}
}
