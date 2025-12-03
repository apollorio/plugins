<?php
/**
 * Depoimentos como comentários customizados
 */
class Apollo_User_Page_Depoimentos {
	public static function rename_labels( $labels ) {
		$labels['comments']      = 'Depoimentos';
		$labels['comments_add']  = 'Adicionar Depoimento';
		$labels['comments_view'] = 'Ver Depoimentos';
		return $labels;
	}
	public static function antispam( $commentdata ) {
		// Exemplo simples: limita 1 depoimento por IP por 5 minutos
		$ip  = $_SERVER['REMOTE_ADDR'] ?? '';
		$key = 'apollo_depoimento_' . md5( $ip );
		if ( get_transient( $key ) ) {
			wp_die( 'Aguarde antes de enviar outro depoimento.' );
		}
		set_transient( $key, 1, 5 * MINUTE_IN_SECONDS );
		return $commentdata;
	}
}
add_filter( 'comments_post_type_labels_user_page', array( 'Apollo_User_Page_Depoimentos', 'rename_labels' ) );
add_filter( 'preprocess_comment', array( 'Apollo_User_Page_Depoimentos', 'antispam' ) );
