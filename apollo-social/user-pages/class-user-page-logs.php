<?php
/**
 * Métricas e logs de edições/depoimentos
 */
class Apollo_User_Page_Logs {
	public static function log_edit( $user_id ) {
		$logs = get_user_meta( $user_id, 'apollo_userpage_edit_logs', true );
		if ( ! is_array( $logs ) ) {
			$logs = [];
		}
		$logs[] = [
			'datetime' => current_time( 'mysql' ),
			'ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
		];
		update_user_meta( $user_id, 'apollo_userpage_edit_logs', $logs );
	}
	public static function log_depoimento( $user_id ) {
		$logs = get_user_meta( $user_id, 'apollo_userpage_depoimento_logs', true );
		if ( ! is_array( $logs ) ) {
			$logs = [];
		}
		$logs[] = [
			'datetime' => current_time( 'mysql' ),
			'ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
		];
		update_user_meta( $user_id, 'apollo_userpage_depoimento_logs', $logs );
	}
}
