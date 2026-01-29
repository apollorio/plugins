<?php

namespace Apollo\API\Endpoints;

/**
 * Widgets API Endpoints
 * Handles saving and loading widget layouts
 */
class WidgetsEndpoints {

	public function register() {
		add_action( 'wp_ajax_apollo_save_widgets', array( $this, 'saveWidgets' ) );
		add_action( 'wp_ajax_apollo_add_depoimento', array( $this, 'addDepoimento' ) );
		add_action( 'wp_ajax_apollo_delete_widget', array( $this, 'deleteWidget' ) );
	}

	/**
	 * Save widgets layout
	 */
	public function saveWidgets() {
		// Verify nonce
		$route = isset( $_POST['route'] ) ? sanitize_text_field( wp_unslash( $_POST['route'] ) ) : 'default';
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'apollo_canvas_' . $route ) ) {
			wp_send_json_error( array( 'message' => 'Nonce inválido' ) );

			return;
		}

		$page_id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;

		if ( ! $page_id ) {
			wp_send_json_error( array( 'message' => 'ID da página inválido' ) );

			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $page_id ) ) {
			wp_send_json_error( array( 'message' => 'Sem permissão para editar' ) );

			return;
		}

		// Get and validate widgets
		$widgets_json = isset( $_POST['widgets'] ) ? wp_unslash( $_POST['widgets'] ) : '[]';
		$widgets      = json_decode( $widgets_json, true );

		if ( ! is_array( $widgets ) ) {
			wp_send_json_error( array( 'message' => 'Dados de widgets inválidos' ) );

			return;
		}

		// Sanitize widgets
		$sanitized_widgets = $this->sanitizeWidgets( $widgets );

		// Save widgets
		update_post_meta( $page_id, '_apollo_widgets', $sanitized_widgets );
		update_post_meta( $page_id, '_apollo_layout_updated', current_time( 'mysql' ) );

		wp_send_json_success(
			array(
				'message' => 'Layout salvo com sucesso',
				'widgets' => $sanitized_widgets,
			)
		);
	}

	/**
	 * Add depoimento (comment)
	 */
	public function addDepoimento() {
		// Verify nonce
		$route = isset( $_POST['route'] ) ? sanitize_text_field( wp_unslash( $_POST['route'] ) ) : 'default';
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'apollo_canvas_' . $route ) ) {
			wp_send_json_error( array( 'message' => 'Nonce inválido' ) );

			return;
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$content = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '';

		if ( ! $user_id || empty( $content ) ) {
			wp_send_json_error( array( 'message' => 'Dados inválidos' ) );

			return;
		}

		// Get user page
		$user_page = apollo_get_user_page( $user_id );

		if ( ! $user_page ) {
			wp_send_json_error( array( 'message' => 'Página do usuário não encontrada' ) );

			return;
		}

		// Check if comments are allowed
		$widgets            = get_post_meta( $user_page->ID, '_apollo_widgets', true );
		$depoimentos_widget = null;

		foreach ( $widgets as $widget ) {
			if ( $widget['type'] === 'depoimentos' ) {
				$depoimentos_widget = $widget;

				break;
			}
		}

		if ( ! $depoimentos_widget || ( $depoimentos_widget['config']['allow_comments'] ?? true ) === false ) {
			wp_send_json_error( array( 'message' => 'Comentários não permitidos' ) );

			return;
		}

		// Create comment
		$comment_data = array(
			'comment_post_ID'      => $user_page->ID,
			'comment_author'       => wp_get_current_user()->display_name,
			'comment_author_email' => wp_get_current_user()->user_email,
			'comment_content'      => $content,
			'comment_type'         => 'apollo_depoimento',
			'user_id'              => get_current_user_id(),
			'comment_approved'     => 1,
		// Auto-approve for now
		);

		$comment_id = wp_insert_comment( $comment_data );

		if ( ! $comment_id ) {
			wp_send_json_error( array( 'message' => 'Erro ao criar depoimento' ) );

			return;
		}

		$comment = get_comment( $comment_id );

		wp_send_json_success(
			array(
				'message'    => 'Depoimento adicionado com sucesso',
				'depoimento' => array(
					'id'             => $comment->comment_ID,
					'author'         => array(
						'id'     => $comment->user_id,
						'name'   => $comment->comment_author,
						'avatar' => get_avatar_url( $comment->user_id ? $comment->user_id : $comment->comment_author_email ),
					),
					'content'        => $comment->comment_content,
					'date'           => $comment->comment_date,
					'date_formatted' => human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) . ' atrás',
				),
			)
		);
	}

	/**
	 * Delete widget
	 */
	public function deleteWidget() {
		// Verify nonce
		$route = isset( $_POST['route'] ) ? sanitize_text_field( wp_unslash( $_POST['route'] ) ) : 'default';
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'apollo_canvas_' . $route ) ) {
			wp_send_json_error( array( 'message' => 'Nonce inválido' ) );

			return;
		}

		$page_id   = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;
		$widget_id = isset( $_POST['widget_id'] ) ? sanitize_text_field( $_POST['widget_id'] ) : '';

		if ( ! $page_id || empty( $widget_id ) ) {
			wp_send_json_error( array( 'message' => 'Dados inválidos' ) );

			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $page_id ) ) {
			wp_send_json_error( array( 'message' => 'Sem permissão' ) );

			return;
		}

		// Get widgets
		$widgets = get_post_meta( $page_id, '_apollo_widgets', true );

		if ( ! is_array( $widgets ) ) {
			wp_send_json_error( array( 'message' => 'Widgets não encontrados' ) );

			return;
		}

		// Remove widget
		$widgets = array_filter(
			$widgets,
			function ( $widget ) use ( $widget_id ) {
				return ( $widget['id'] ?? '' ) !== $widget_id;
			}
		);

		// Re-index array
		$widgets = array_values( $widgets );

		// Save updated widgets
		update_post_meta( $page_id, '_apollo_widgets', $widgets );

		wp_send_json_success( array( 'message' => 'Widget removido' ) );
	}

	/**
	 * Sanitize widgets array
	 */
	private function sanitizeWidgets( $widgets ) {
		$sanitized = array();

		foreach ( $widgets as $widget ) {
			if ( ! is_array( $widget ) ) {
				continue;
			}

			$sanitized[] = array(
				'id'       => isset( $widget['id'] ) ? sanitize_text_field( $widget['id'] ) : uniqid( 'widget-' ),
				'type'     => isset( $widget['type'] ) ? sanitize_key( $widget['type'] ) : 'unknown',
				'position' => array(
					'x' => isset( $widget['position']['x'] ) ? absint( $widget['position']['x'] ) : 0,
					'y' => isset( $widget['position']['y'] ) ? absint( $widget['position']['y'] ) : 0,
				),
				'size'     => array(
					'w' => isset( $widget['size']['w'] ) ? absint( $widget['size']['w'] ) : 6,
					'h' => isset( $widget['size']['h'] ) ? absint( $widget['size']['h'] ) : 4,
				),
				'config'   => isset( $widget['config'] ) && is_array( $widget['config'] )
					? $this->sanitizeConfig( $widget['config'] )
					: array(),
			);
		}//end foreach

		return $sanitized;
	}

	/**
	 * Sanitize widget config
	 */
	private function sanitizeConfig( $config ) {
		$sanitized = array();

		foreach ( $config as $key => $value ) {
			$key = sanitize_key( $key );

			if ( is_string( $value ) ) {
				$sanitized[ $key ] = sanitize_text_field( $value );
			} elseif ( is_int( $value ) ) {
				$sanitized[ $key ] = absint( $value );
			} elseif ( is_bool( $value ) ) {
				$sanitized[ $key ] = (bool) $value;
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitizeConfig( $value );
			}
		}

		return $sanitized;
	}
}
