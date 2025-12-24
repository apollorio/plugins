<?php

/**
 * Métricas e logs de edições/depoimentos
 */
class Apollo_User_Page_Logs {

	public static function log_edit( $user_id ) {
		$logs = get_user_meta( $user_id, 'apollo_userpage_edit_logs', true );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}
		$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$logs[] = array(
			'datetime' => current_time( 'mysql' ),
			'ip'       => $ip,
		);
		update_user_meta( $user_id, 'apollo_userpage_edit_logs', $logs );
	}
	public static function log_depoimento( $user_id ) {
		$logs = get_user_meta( $user_id, 'apollo_userpage_depoimento_logs', true );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}
		$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$logs[] = array(
			'datetime' => current_time( 'mysql' ),
			'ip'       => $ip,
		);
		update_user_meta( $user_id, 'apollo_userpage_depoimento_logs', $logs );
	}
}
