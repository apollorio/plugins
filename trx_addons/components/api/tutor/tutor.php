<?php
/**
 * Plugin support: Tutor LMS
 *
 * @package ThemeREX Addons
 * @since v2.35.1
 */

// Don't load directly
if ( ! defined( 'TRX_ADDONS_VERSION' ) ) {
	exit;
}

if ( ! function_exists( 'trx_addons_exists_tutor' ) ) {
	/**
	 * Check if Tutor LMS plugin is installed and activated
	 *
	 * @return bool  true if plugin is installed and activated
	 */
	function trx_addons_exists_tutor() {
		return function_exists('tutor_utils');
	}
}

if ( ! function_exists( 'trx_addons_tutor_get_options_page_ids' ) ) {
	/**
	 * Return a list of option names for Tutor LMS pages
	 * 
	 * @return array  List of option names
	 */
	function trx_addons_tutor_get_options_page_ids() {
		return apply_filters( 'trx_addons_filter_tutor_pages', array(
			'tutor_dashboard_page_id',
			'tutor_toc_page_id',
			'tutor_cart_page_id',
			'tutor_checkout_page_id',
			'course_archive_page',
			'instructor_register_page',
			'student_register_page'
		) );
	}
}

if ( ! function_exists( 'trx_addons_is_tutor_page' ) ) {
	/**
	 * Check if current page is any tutor page
	 *
	 * @return bool  true if current page is any Tutor LMS page
	 */
	function trx_addons_is_tutor_page() {
		$rez = false;
		if ( trx_addons_exists_tutor() && ! is_search() && ! is_admin() ) {
			$rez = tutor_utils()->is_tutor_frontend_dashboard()
					|| tutor_utils()->get_course_builder_screen()
					|| is_post_type_archive( tutor()->course_post_type )
					|| is_tax( 'course-category' )
					|| is_tax( 'course-tag' )
					|| is_singular( array(
							tutor()->course_post_type,
							tutor()->lesson_post_type,
							tutor()->quiz_post_type,
							tutor()->assignment_post_type,
							tutor()->zoom_post_type,
							tutor()->meet_post_type,
						) )
					|| ( trx_addons_check_url( '/profile/' ) && trx_addons_check_url( 'view=student' ) )
					|| ( trx_addons_check_url( '/profile/' ) && trx_addons_check_url( 'view=instructor' ) );
			if ( ! $rez ) {
				$id = get_the_ID();
				if ( $id > 0 ) {
					foreach( trx_addons_tutor_get_options_page_ids() as $page ) {
						$page_id = (int)tutor_utils()->get_option( $page );
						if ( $page_id > 0 && is_page() && $id == $page_id ) {
							$rez = true;
							break;
						}
					}
				}
			}
		}
		return $rez;
	}
}

if ( ! function_exists( 'trx_addons_tutor_change_courses_slug' ) ) {
	add_filter('trx_addons_cpt_list', 'trx_addons_tutor_change_courses_slug');
	/**
	 * Change slug for the internl courses post type to avoid conflicts with the Tutor LMS plugin
	 * 
	 * @hooked trx_addons_cpt_list
	 *
	 * @param array $list  List of post types parameters
	 * 
	 * @return array       Modified list of post types parameters
	 */
	function trx_addons_tutor_change_courses_slug( $list ) {
		if ( ! empty( $list['courses']['post_type_slug'] ) && $list['courses']['post_type_slug'] == 'courses' ) {
			$list['courses']['post_type_slug'] = 'cpt_courses';
		}
		return $list;
	}
}

if ( ! function_exists( 'trx_addons_tutor_load_scripts_front' ) ) {
	add_action( "wp_enqueue_scripts", 'trx_addons_tutor_load_scripts_front', TRX_ADDONS_ENQUEUE_SCRIPTS_PRIORITY );
	add_action( 'trx_addons_action_pagebuilder_preview_scripts', 'trx_addons_tutor_load_scripts_front', 10, 1 );
	/**
	 * Enqueue scripts and styles for frontend
	 * 
	 * @hooked wp_enqueue_scripts
	 * @hooked trx_addons_action_pagebuilder_preview_scripts
	 * 
	 * @trigger trx_addons_action_load_scripts_front
	 * 
	 * @param bool $force  Force enqueue scripts and styles (without check if it's necessary)
	 */
	function trx_addons_tutor_load_scripts_front( $force = false ) {
		if ( ! trx_addons_exists_tutor() ) {
			return;
		}
		trx_addons_enqueue_optimized( 'tutor', $force, array(
			'need' => trx_addons_is_tutor_page(),
			'check' => array(
				array( 'type' => 'sc',  'sc' => 'tutor_student_registration_form' ),
				array( 'type' => 'sc',  'sc' => 'tutor_dashboard' ),
				array( 'type' => 'sc',  'sc' => 'tutor_instructor_registration_form' ),
				array( 'type' => 'sc',  'sc' => 'tutor_course' ),
				array( 'type' => 'sc',  'sc' => 'tutor_instructor_list' ),
				array( 'type' => 'sc',  'sc' => 'tutor_cart' ),
				array( 'type' => 'sc',  'sc' => 'tutor_checkout' ),
				array( 'type' => 'gb',  'sc' => 'wp:tutor-gutenberg/student-registration' ),
				array( 'type' => 'gb',  'sc' => 'wp:tutor-gutenberg/instructor-registration' ),
				array( 'type' => 'elm', 'sc' => '"shortcode":"[tutor_student_registration_form' ),
				array( 'type' => 'elm', 'sc' => '"shortcode":"[tutor_dashboard' ),
				array( 'type' => 'elm', 'sc' => '"shortcode":"[tutor_instructor_registration_form' ),
				array( 'type' => 'elm', 'sc' => '"shortcode":"[tutor_course' ),
				array( 'type' => 'elm', 'sc' => '"shortcode":"[tutor_instructor_list' ),
				array( 'type' => 'elm', 'sc' => '"shortcode":"[tutor_cart' ),
				array( 'type' => 'elm', 'sc' => '"shortcode":"[tutor_checkout' ),
				// Elementor widgets from the plugin Tutor LMS Elementor Addons
				array( 'type' => 'elm', 'sc' => '"widgetType":"etlms-' ),

			)
		) );
	}
}

if ( ! function_exists( 'trx_addons_tutor_check_in_html_output' ) ) {
	add_filter( 'trx_addons_filter_get_menu_cache_html', 'trx_addons_tutor_check_in_html_output', 10, 1 );
	add_action( 'trx_addons_action_show_layout_from_cache', 'trx_addons_tutor_check_in_html_output', 10, 1 );
	add_action( 'trx_addons_action_check_page_content', 'trx_addons_tutor_check_in_html_output', 10, 1 );
	/**
	 * Check if tutor shortcodes are present in the HTML output of the page or in the menu or the layouts cache
	 * and force loading scripts and styles
	 * 
	 * @hooked trx_addons_filter_get_menu_cache_html
	 * @hooked trx_addons_action_show_layout_from_cache
	 * @hooked trx_addons_action_check_page_content
	 *
	 * @param string $content  HTML output to check
	 * 
	 * @return string          Checked HTML output
	 */
	function trx_addons_tutor_check_in_html_output( $content = '' ) {
		if ( ! trx_addons_exists_tutor() ) {
			return $content;
		}
		$args = array(
			'check' => array(
				'class=[\'"][^\'"]*tutor-course',
				'class=[\'"][^\'"]*tutor-wrap',
			)
		);
		if ( trx_addons_check_in_html_output( 'tutor', $content, $args ) ) {
			trx_addons_tutor_load_scripts_front( true );
		}
		return $content;
	}
}

if ( ! function_exists( 'trx_addons_tutor_extended_taxonomy_allow_in_the_terms' ) ) {
	add_filter( 'trx_addons_filter_extended_taxonomy_filter_get_the_terms', 'trx_addons_tutor_extended_taxonomy_allow_in_the_terms' );
	/**
	 * Allow the extended taxonomy in the get_the_terms() function
	 * 
	 * @hooked trx_addons_filter_extended_taxonomy_filter_get_the_terms
	 * 
	 * @param bool $allow  true - allow, false - disallow
	 * 
	 * @return bool        true - allow, false - disallow
	 */
	function trx_addons_tutor_extended_taxonomy_allow_in_the_terms( $allow = false) {
		if ( trx_addons_is_tutor_page() ) {
			$allow = true;
		}
		return $allow;
	}
}

if ( ! function_exists( 'trx_addons_tutor_sc_layouts_content_add_inline_css' ) ) {
	add_filter( 'trx_addons_filter_sc_layout_content_need_inline_css', 'trx_addons_tutor_sc_layouts_content_add_inline_css', 10, 2 );
	/**
	 * Add inline styles for the extended taxonomy
	 * 
	* @param boolean $add      true - add inline styles, false - not
	* @param int     $post_id  Post ID
	*/
	function trx_addons_tutor_sc_layouts_content_add_inline_css( $add, $post_id = 0 ) {
		if ( trx_addons_exists_tutor() && trx_addons_is_tutor_page() ) {
			$add = true;
		}
		return $add;
	}
}


// Demo data install
//----------------------------------------------------------------------------

// One-click import support
if ( is_admin() ) {
	require_once TRX_ADDONS_PLUGIN_DIR . TRX_ADDONS_PLUGIN_API . 'tutor/tutor-demo-importer.php';
}
