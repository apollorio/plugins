<?php
/**
 * User Privacy Tab
 *
 * Adds privacy settings tab to user profile page
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User Privacy Tab
 */
class Apollo_User_Privacy_Tab {

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'apollo_user_page_tabs', [ __CLASS__, 'add_privacy_tab' ], 20 );
		add_action( 'apollo_user_page_tab_content', [ __CLASS__, 'render_privacy_content' ], 20 );
		add_action( 'wp_ajax_apollo_save_privacy_settings', [ __CLASS__, 'ajax_save_privacy' ] );
	}

	/**
	 * Add privacy tab
	 */
	public static function add_privacy_tab() {
		?>
		<button class="apollo-tab-btn" data-tab="privacy">
			<i class="ri-shield-line"></i> <?php esc_html_e( 'Privacidade', 'apollo-social' ); ?>
		</button>
		<?php
	}

	/**
	 * Render privacy content
	 */
	public static function render_privacy_content() {
		$user_id = get_current_user_id();
		$seo_enabled = get_user_meta( $user_id, '_apollo_seo_enabled', true );
		$visit_tracking = get_user_meta( $user_id, '_apollo_visit_tracking', true );
		$visit_tracking = $visit_tracking ? $visit_tracking : 'default';

		?>
		<div class="apollo-tab-panel" data-panel="privacy">
			<h3><?php esc_html_e( 'Configurações de Privacidade', 'apollo-social' ); ?></h3>

			<div class="apollo-privacy-setting">
				<label>
					<input type="checkbox" 
						id="apollo_seo_enabled"
						<?php checked( $seo_enabled, '1' ); ?>>
					<strong><?php esc_html_e( 'Quer ser achado no Google?', 'apollo-social' ); ?></strong>
					<p class="description">
						<?php esc_html_e( 'Quando ativado, seu perfil pode aparecer nos resultados de busca do Google.', 'apollo-social' ); ?>
					</p>
				</label>
			</div>

			<div class="apollo-privacy-setting">
				<label>
					<strong><?php esc_html_e( 'Rastreamento de Visitas', 'apollo-social' ); ?></strong>
					<select id="apollo_visit_tracking" class="regular-text">
						<option value="default" <?php selected( $visit_tracking, 'default' ); ?>>
							<?php esc_html_e( 'Usar configuração padrão do admin', 'apollo-social' ); ?>
						</option>
						<option value="invisible" <?php selected( $visit_tracking, 'invisible' ); ?>>
							<?php esc_html_e( 'Invisível - Não mostrar quando visito páginas', 'apollo-social' ); ?>
						</option>
						<option value="visible" <?php selected( $visit_tracking, 'visible' ); ?>>
							<?php esc_html_e( 'Visível - Mostrar "Visitou há X minutos"', 'apollo-social' ); ?>
						</option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Controle como suas visitas a páginas de outros usuários são exibidas.', 'apollo-social' ); ?>
					</p>
				</label>
			</div>

			<button type="button" class="button button-primary apollo-save-privacy">
				<?php esc_html_e( 'Salvar Configurações', 'apollo-social' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * AJAX: Save privacy settings
	 */
	public static function ajax_save_privacy() {
		check_ajax_referer( 'apollo_user_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'You must be logged in', 'apollo-social' ) ] );
		}

		$user_id = get_current_user_id();
		$seo_enabled = isset( $_POST['seo_enabled'] ) ? (bool) $_POST['seo_enabled'] : false;
		$visit_tracking = isset( $_POST['visit_tracking'] ) ? sanitize_key( $_POST['visit_tracking'] ) : 'default';

		update_user_meta( $user_id, '_apollo_seo_enabled', $seo_enabled ? '1' : '0' );
		update_user_meta( $user_id, '_apollo_visit_tracking', $visit_tracking );

		wp_send_json_success( [ 'message' => __( 'Privacy settings saved', 'apollo-social' ) ] );
	}
}

Apollo_User_Privacy_Tab::init();

