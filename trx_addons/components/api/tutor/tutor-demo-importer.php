<?php
/**
 * Plugin support: Tutor LMS (Importer support)
 *
 * @package ThemeREX Addons
 * @since v2.35.1
 */

// Don't load directly
if ( ! defined( 'TRX_ADDONS_VERSION' ) ) {
	exit;
}


if ( ! function_exists( 'trx_addons_tutor_importer_required_plugins' ) ) {
	if ( is_admin() ) {
		add_filter( 'trx_addons_filter_importer_required_plugins', 'trx_addons_tutor_importer_required_plugins', 10, 2 );
	}
	/**
	 * Check if the required plugins are installed
	 * 
	 * @hooked trx_addons_filter_importer_required_plugins
	 *
	 * @param string $not_installed Not installed plugins list
	 * @param string $list          Required plugins list
	 * 
	 * @return string               Not installed plugins list (with new plugins)
	 */
	function trx_addons_tutor_importer_required_plugins( $not_installed = '', $list = '' ) {
		if ( strpos( $list, 'tutor' ) !== false && ! trx_addons_exists_tutor() ) {
			$not_installed .= '<br>' . esc_html__('tutor', 'trx_addons');
		}
		return $not_installed;
	}
}

if ( ! function_exists( 'trx_addons_tutor_importer_set_options' ) ) {
	if ( is_admin() ) {
		add_filter( 'trx_addons_filter_importer_options', 'trx_addons_tutor_importer_set_options' );
	}
	/**
	 * Set plugin's specific importer options
	 * 
	 * @hooked trx_addons_filter_importer_options
	 *
	 * @param array $options Importer options
	 * 
	 * @return array         Modified options
	 */
	function trx_addons_tutor_importer_set_options( $options = array() ) {
		if ( trx_addons_exists_tutor() && in_array( 'tutor', $options['required_plugins'] ) ) {
			$options['additional_options'][]	= 'tutor_option';					// Add slugs to export options for this plugin
			$options['additional_options'][]	= 'widget_tutor_course_widget';

			if ( is_array( $options['files']) && count( $options['files'] ) > 0 ) {
				foreach ( $options['files'] as $k => $v ) {
					$options['files'][$k]['file_with_tutor'] = str_replace( 'name.ext', 'tutor.txt', $v['file_with_'] );
				}
			}
		}
		return $options;
	}
}

if ( ! function_exists( 'trx_addons_tutor_importer_check_options' ) ) {
	add_filter( 'trx_addons_filter_import_theme_options', 'trx_addons_tutor_importer_check_options', 10, 4 );
	/**
	 * Check if options will be imported
	 * 
	 * @hooked trx_addons_filter_import_theme_options
	 *
	 * @param boolean $allow    Allow import or not
	 * @param string  $k        Option name to import
	 * @param string  $v        Option value to import
	 * @param array   $options  Importer options
	 * 
	 * @return boolean          Allow import or not
	 */
	function trx_addons_tutor_importer_check_options( $allow, $k, $v, $options ) {
		if ( $allow && in_array( $k, array( 'tutor_option', 'widget_tutor_course_widget' ) ) ) {
			$allow = trx_addons_exists_tutor() && in_array( 'tutor', $options['required_plugins'] );
		}
		return $allow;
	}
}

if ( ! function_exists( 'trx_addons_tutor_importer_show_params' ) ) {
	if ( is_admin() ) {
		add_action( 'trx_addons_action_importer_params', 'trx_addons_tutor_importer_show_params', 10, 1 );
	}
	/**
	 * Display a plugin name in the required plugins list on the Importer settings page
	 * 
	 * @hooked trx_addons_action_importer_params
	 *
	 * @param object $importer  Importer object
	 */
	function trx_addons_tutor_importer_show_params( $importer ) {
		if ( trx_addons_exists_tutor() && in_array( 'tutor', $importer->options['required_plugins'] ) ) {
			$importer->show_importer_params( array(
				'slug' => 'tutor',
				'title' => esc_html__('Import Tutor LMS', 'trx_addons'),
				'part' => 0
			) );
		}
	}
}

if ( ! function_exists( 'trx_addons_tutor_importer_import' ) ) {
	if ( is_admin() ) {
		add_action( 'trx_addons_action_importer_import', 'trx_addons_tutor_importer_import', 10, 2 );
	}
	/**
	 * Import Tutor LMS data
	 * 
	 * @hooked trx_addons_action_importer_import
	 *
	 * @param object $importer  Importer object
	 * @param string $action    Action to perform: 'import_tutor'
	 */
	function trx_addons_tutor_importer_import( $importer, $action ) {
		if ( trx_addons_exists_tutor() && in_array( 'tutor', $importer->options['required_plugins'] ) ) {
			if ( $action == 'import_tutor' ) {
				$importer->response['start_from_id'] = 0;
				$importer->import_dump( 'tutor', esc_html__( 'Tutor LMS meta', 'trx_addons' ) );
			}
		}
	}
}

if ( ! function_exists( 'trx_addons_tutor_importer_check_row' ) ) {
	if ( is_admin() ) {
		add_filter('trx_addons_filter_importer_import_row', 'trx_addons_tutor_importer_check_row', 9, 4);
	}
	/**
	 * Check if the row will be imported
	 * 
	 * @hooked trx_addons_filter_importer_import_row
	 *
	 * @param boolean $flag   Allow import or not
	 * @param string  $table  Table name
	 * @param array   $row    Row data
	 * @param string  $list   Comma separated list of the required plugins
	 * 
	 * @return boolean        Allow import or not
	 */
	function trx_addons_tutor_importer_check_row( $flag, $table, $row, $list ) {
		if ( $flag || strpos( $list, 'tutor' ) === false ) {
			return $flag;
		}
		if ( trx_addons_exists_tutor() ) {
			if ( $table == 'posts' ) {
				$flag = in_array( $row['post_type'], array(
														tutor()->course_post_type,
														tutor()->lesson_post_type,
														tutor()->quiz_post_type,
														tutor()->assignment_post_type,
														tutor()->zoom_post_type,
														tutor()->meet_post_type,
				) );
			}
		}
		return $flag;
	}
}

if ( ! function_exists( 'trx_addons_tutor_importer_import_fields' ) ) {
	if ( is_admin() ) {
		add_action( 'trx_addons_action_importer_import_fields',	'trx_addons_tutor_importer_import_fields', 10, 1 );
	}
	/**
	 * Add a plugin's fields in the Importer's fields list
	 * 
	 * @hooked trx_addons_action_importer_import_fields
	 *
	 * @param object $importer  Importer object
	 */
	function trx_addons_tutor_importer_import_fields($importer) {
		if ( trx_addons_exists_tutor() && in_array('tutor', $importer->options['required_plugins']) ) {
			$importer->show_importer_fields(array(
				'slug'=>'tutor', 
				'title' => esc_html__('Tutor LMS meta', 'trx_addons')
			) );
		}
	}
}


if ( ! function_exists( 'trx_addons_tutor_importer_export' ) ) {
	if ( is_admin() ) {
		add_action( 'trx_addons_action_importer_export', 'trx_addons_tutor_importer_export', 10, 1 );
	}
	/**
	 * Export Tutor LMS data
	 * 
	 * @hooked trx_addons_action_importer_export
	 * 
	 * @trigger trx_addons_filter_importer_export_tables
	 *
	 * @param object $importer  Importer object
	 */
	function trx_addons_tutor_importer_export( $importer ) {
		if ( trx_addons_exists_tutor() && in_array( 'tutor', $importer->options['required_plugins'] ) ) {
			trx_addons_fpc( $importer->export_file_dir( 'tutor.txt' ), serialize( apply_filters( 'trx_addons_filter_importer_export_tables', array(
				// "tutor_carts"					=> $importer->export_dump("tutor_carts"),
				// "tutor_cart_items"				=> $importer->export_dump("tutor_cart_items"),
				// "tutor_coupons"					=> $importer->export_dump("tutor_coupons"),
				// "tutor_coupon_applications"		=> $importer->export_dump("tutor_coupon_applications"),
				// "tutor_coupon_usages"			=> $importer->export_dump("tutor_coupon_usages"),
				// "tutor_customers"				=> $importer->export_dump("tutor_customers"),

				"tutor_earnings"				=> $importer->export_dump("tutor_earnings"),
				"tutor_email_queue"				=> $importer->export_dump("tutor_email_queue"),
				"tutor_gradebooks"				=> $importer->export_dump("tutor_gradebooks"),
				"tutor_gradebooks_results"		=> $importer->export_dump("tutor_gradebooks_results"),

				// "tutor_migration"				=> $importer->export_dump("tutor_migration"),
				// "tutor_ordermeta"				=> $importer->export_dump("tutor_ordermeta"),
				// "tutor_orders"					=> $importer->export_dump("tutor_orders"),
				// "tutor_order_items"				=> $importer->export_dump("tutor_order_items"),

				"tutor_quiz_attempts"			=> $importer->export_dump("tutor_quiz_attempts"),
				"tutor_quiz_attempt_answers"	=> $importer->export_dump("tutor_quiz_attempt_answers"),
				"tutor_quiz_questions"			=> $importer->export_dump("tutor_quiz_questions"),
				"tutor_quiz_question_answers"	=> $importer->export_dump("tutor_quiz_question_answers"),
				"tutor_withdraws"				=> $importer->export_dump("tutor_withdraws"),
				), 'tutor' ) )
			);
		}
	}
}

if ( ! function_exists( 'trx_addons_tutor_importer_export_fields' ) ) {
	if ( is_admin() ) {
		add_action( 'trx_addons_action_importer_export_fields',	'trx_addons_tutor_importer_export_fields', 10, 1 );
	}
	/**
	 * Add a plugin's name to the Exporter's fields list
	 * 
	 * @hooked trx_addons_action_importer_export_fields
	 *
	 * @param object $importer  Importer object
	 */
	function trx_addons_tutor_importer_export_fields( $importer ) {
		if ( trx_addons_exists_tutor() && in_array( 'tutor', $importer->options['required_plugins'] ) ) {
			$importer->show_exporter_fields( array(
				'slug'	=> 'tutor',
				'title' => esc_html__('Tutor LMS', 'trx_addons')
			) );
		}
	}
}
