<?php
namespace TrxAddons\ElementorTemplates\Templates;

defined( 'ABSPATH' ) || exit;

// use TrxAddons\ElementorTemplates\Utils;
// use TrxAddons\ElementorTemplates\Options;


/**
 * Class Library - show a Templates Library and import any template to the current page
 */
class Library {

	private $templates_library_cache_name = 'trx_addons_elementor_list_templates';
	private $templates_library_cache_time = 2 * 24 * 60 * 60;
	private $templates_library_option_favorites = 'trx_addons_elementor_favorite_templates';
	private $templates_library_option_loaded_media = 'trx_addons_elementor_templates_loaded_media';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Update list of templates
		add_action( 'admin_init', array( $this, 'update_list_templates' ) );

		// Refresh templates list
		add_action( 'wp_ajax_trx_addons_elementor_templates_library_refresh', array( $this, 'refresh_list_templates' ) );

		// Import template
		add_action( 'wp_ajax_trx_addons_elementor_templates_library_item_import', array( $this, 'import_template' ) );

		// Mark/unmark template as favorite
		add_action( 'wp_ajax_trx_addons_elementor_templates_library_item_favorite', array( $this, 'favorite_template' ) );

		// Parse image to layout
		add_action( 'wp_ajax_trx_addons_elementor_templates_library_image_to_layout', array( $this, 'image_to_layout' ) );

		// Enqueue scripts and styles
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_preview_scripts' ) );

		// Add messages to js-vars
		add_filter( 'trx_addons_filter_localize_script_admin', array( $this, 'localize_script_admin' ) );

		// Clear the loaded media cache after the demo import
		add_action( 'trx_addons_action_importer_import_end',  array( $this, 'clear_list_loaded_media' ), 10, 1 );

		// Prepare a page content and styles for the preview dialog
		add_action( 'wp_head', array( $this, 'prepare_preview_styles' ) );
		add_filter( 'trx_addons_filter_page_content', array( $this, 'prepare_preview_html' ) );

	}

	/**
	 * Get list of templates
	 * 
	 * @return array  List of templates or false
	 */
	public function get_list_templates() {
		return get_transient( $this->templates_library_cache_name );
	}

	/**
	 * Save list of templates to the cache
	 * 
	 * @param array $list  List of templates
	 */
	public function set_list_templates( $list ) {
		set_transient( $this->templates_library_cache_name, $list, $this->templates_library_cache_time );
	}

	/**
	 * Update list of templates
	 * 
	 * @param bool $force  If true - force update
	 * 
	 * @hooked admin_init
	 */
	public function update_list_templates( $force = false ) {
		$templates = $force ? false : $this->get_list_templates();
		if ( ! is_array( $templates ) ) {
			$templates_available = trx_addons_get_upgrade_data( array( 'action' => 'info_elementor_templates' ) );
			if ( empty( $templates_available['error'] ) && ! empty( $templates_available['data'] ) && $templates_available['data'][0] == '{' ) {
				$templates = json_decode( $templates_available['data'], true );
			}
			$this->set_list_templates( is_array( $templates ) ? $templates : array() );
		}
	}

	/**
	 * Get list of favorite templates
	 * 
	 * @return array  List of favorite templates
	 */
	public function get_list_favorites() {
		return get_option( $this->templates_library_option_favorites, array() );
	}

	/**
	 * Save list of favorite templates
	 * 
	 * @param array $list  List of favorite templates
	 */
	public function set_list_favorites( $list ) {
		update_option( $this->templates_library_option_favorites, $list );
	}

	/**
	 * Get list of loaded media from the templates library
	 * 
	 * @return array  List of loaded media
	 */
	public function get_list_loaded_media() {
		return get_option( $this->templates_library_option_loaded_media, array() );
	}

	/**
	 * Save list of loaded media from the templates library
	 * 
	 * @param array $list  List of loaded media
	 */
	public function set_list_loaded_media( $list ) {
		update_option( $this->templates_library_option_loaded_media, $list );
	}

	/**
	 * Clear list of loaded media from the templates library
	 * 
	 * @param object $importer  Importer object
	 */
	public function clear_list_loaded_media( $importer ) {
		if ( is_object( $importer ) && $importer->options['demo_set'] == 'full' ) {
			$this->set_list_loaded_media( array() );
		}
	}

	/**
	 * Get template data by type and name
	 * 
	 * @param string $name  Template name
	 * @param string $type  Template type (not used now)
	 * 
	 * @return array  Template data
	 */
	public function get_template_data( $name, $type = '' ) {
		$templates = $this->get_list_templates();
		return ! empty( $templates[ $name ] ) ? $templates[ $name ] : false;
	}

	/**
	 * Get templates tabs and categories
	 * 
	 * @return array  Templates tabs and categories
	 */
	public function get_tabs_and_categories() {
		$tabs = array();
		$templates = $this->get_list_templates();
		if ( is_array( $templates ) ) {
			foreach ( $templates as $name => $data ) {
				if ( ! empty( $data['type'] ) ) {
					if ( empty( $tabs[ $data['type'] ] ) ) {
						$tabs[ $data['type'] ] = array(
							'title' => $this->get_tab_title( $data['type'] ),
							'category' => array()
						);
					}
					$cats = array_map( 'trim', explode( ',', ! empty( $data['category'] ) ? $data['category'] : '' ) );
					foreach ( $cats as $cat ) {
						if ( empty( $cat ) ) {
							continue;
						}
						if ( ! isset( $tabs[ $data['type'] ]['category'][ $cat ] ) ) {
							$tabs[ $data['type'] ]['category'][ $cat ] = array(
								'title' => ucfirst( str_replace( array( '-', '_' ), ' ', $cat ) ),
								'total' => 0
							);
						}
						$tabs[ $data['type'] ]['category'][ $cat ]['total']++;
					}
					ksort( $tabs[ $data['type'] ]['category'] );
				}
			}
		}
		return $tabs;
	}

	
	/**
	 * Get the tab's title by type
	 * 
	 * @return string  Tab's title
	 */
	private function get_tab_title( $type ) {
		return $type == 'page' ? esc_html__( 'Pages', 'trx_addons' ) : esc_html__( 'Blocks', 'trx_addons' );
	}


	/**
	 * Refresh list of templates
	 * 
	 * @hooked wp_ajax_trx_addons_elementor_templates_library_refresh
	 */
	public function refresh_list_templates() {

		trx_addons_verify_nonce();

		$this->update_list_templates( true );

		$response = array(
			'error' => '',
			'data' => array(
				'templates' => $this->get_list_templates(),
				'tabs' => $this->get_tabs_and_categories(),
			)
		);

		trx_addons_ajax_response( $response );
	}

	/**
	 * Mark/unmark template as favorite
	 * 
	 * @hooked wp_ajax_trx_addons_elementor_templates_library_item_favorite
	 */
	public function favorite_template() {

		trx_addons_verify_nonce();

		$response = array(
			'error' => '',
			'data' => array()
		);

		$template_name = trx_addons_sanitize_slug( trx_addons_get_value_gp( 'template_name' ) );
		$favorite = (int)trx_addons_get_value_gp( 'favorite' );

		$templates = $this->get_list_favorites();
		if ( $favorite ) {
			$templates[ $template_name ] = true;
		} else {
			unset( $templates[ $template_name ] );
		}
		$this->set_list_favorites( $templates );

		trx_addons_ajax_response( $response );
	}


	/**
	 * Import template
	 * 
	 * @hooked wp_ajax_trx_addons_elementor_templates_library_item_import
	 */
	public function import_template() {

		trx_addons_verify_nonce();

		$response = array(
			'error' => '',
			'data' => array()
		);

		$template_name = trx_addons_sanitize_slug( trx_addons_get_value_gp( 'template_name' ) );
		$template_type = trx_addons_get_value_gp( 'template_type' );

		$templates = $this->get_list_templates();
		$template_data = ! empty( $templates[ $template_name ] ) ? $templates[ $template_name ] : false;

		if ( ! is_array( $template_data ) ) {
			$response['error'] = esc_html__( 'The contents of the selected template are inaccessible!', 'trx_addons' );
		} else {
			$from_cache = false;
			if ( ! empty( $template_data['content'] ) ) {
				$response['data'] = $template_data['content'];
				$from_cache = true;
			} else {
				$key = trx_addons_get_theme_activation_code();
				if ( empty( $key ) ) {
					$response['error'] = esc_html__( 'Theme is not activated!', 'trx_addons' );
				} else {
					$template_content = trx_addons_get_upgrade_data( array(
						'action' => 'download_elementor_template',
						'key' => $key,
						'template' => $template_name,
						'type' => $template_type
					) );
					if ( ! empty( $template_content['error'] ) ) {
						$response['error'] = $template_content['error'];
					} else if ( empty( $template_content['data'] ) || $template_content['data'][0] != '{' ) {
						$response['error'] = esc_html__( 'The contents of the selected template are unavailable!', 'trx_addons' );
					} else {
						$response['data'] = json_decode( $template_content['data'], true );
						if ( ! is_array( $response['data']['content'] ) ) {
							$response['error'] = esc_html__( 'The contents of the selected template are corrupted!', 'trx_addons' );
							$response['data'] = array();
						}
					}
				}
			}
			if ( empty( $response['error'] ) ) {
				// Download images from the template content and save them to the uploads folder. Replace URLs in the content
				$response['data']['content'] = $this->download_images( $response['data']['content'], $template_name );
				if ( ! $from_cache ) {
					// Change the thumbnail size names - replace the prefix 'elementra-thumb-' with the 'current-theme-slug-thumb-'
					$response['data']['content'] = $this->replace_thumb_sizes( $response['data']['content'], $template_name );
					// Save template content to the cache
					$templates[ $template_name ]['content'] = $response['data'];
					$this->set_list_templates( $templates );
				}
				// Prepare template content for import
				$response['data']['content'] = $this->prepare_template( $response['data']['content'], $template_name );
			}
		}

		trx_addons_ajax_response( $response );
	}

	/**
	 * Download images from the content of Elementor's template and save them to the uploads folder.
	 * Replace URLs in the content and return modified content.
	 * 
	 * @param array $content  Template content
	 * @param string $template_name  Template name
	 * @param string $loaded  Loaded media list. Passed by reference.
	 * 
	 * @return array  Modified template content
	 */
	public function download_images( $content, $template_name = '', &$loaded = false ) {
		$first_call = $loaded === false;
		if ( $first_call ) {
			$loaded = $this->get_list_loaded_media();
		}
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$templates_url = untrailingslashit( trx_addons_get_upgrade_domain_url() ) . '/templates/elementor/' . $template_name . '/images/';
		$no_image_attachment = get_option( 'trx_addons_no_image_attachment', array() );
		if ( is_array( $content) || is_object( $content ) ) {
			foreach ( $content as $k => $v ) {
				if ( is_array( $v ) || is_object( $v ) ) {
					$content[ $k ] = $this->download_images( $v, $template_name, $loaded );
				} else if ( is_string( $v ) && stripos( $v, 'http' ) === 0 ) {					// Check if the string is a URL
					$is_image = preg_match( '/http[s]?:.*\.(jpg|jpeg|png|gif|svg|webp)/i', $v );
					$is_video = preg_match( '/http[s]?:.*\.(mp4|webm|ogg)/i', $v );
					$is_audio = preg_match( '/http[s]?:.*\.(mp3|ogg|wav)/i', $v );
					$is_media = $is_image || $is_video || $is_audio;
					$is_local = empty( $content['source'] ) || $content['source'] == 'library';
					// Check if the URL is a local media file from the templates library
					if ( $is_media && $is_local ) {
						$media_name = basename( $v );
						$ext = strtolower( pathinfo( $media_name, PATHINFO_EXTENSION ) );
						$no_media_key = $is_video ? 'mp4' : ( $is_audio ? 'mp3' : ( $ext == 'svg' ? 'svg' : 'img' ) );
						$need_download = true;
						// If the media file is already loaded - check if it exists in the media library
						$attachment_url = trx_addons_is_from_uploads( $v )
										? $v
										: ( ! empty( $loaded[ $v ]['url'] ) && ( empty( $no_image_attachment[ $no_media_key ]['url'] ) || $loaded[ $v ]['url'] != $no_image_attachment[ $no_media_key ]['url'] )
											? $loaded[ $v ]['url']
											: ''
											);
						if ( ! empty( $attachment_url ) ) {
							$attachment_id = trx_addons_attachment_url_to_postid( $attachment_url );
							if ( ! empty( $attachment_id ) ) {
								// Update the loaded media ID if it exists in the media library
								if ( isset( $loaded[ $v ] ) ) {
									$loaded[ $v ]['id'] = $attachment_id;
								} else {
									foreach ( $loaded as $key => $value ) {
										if ( $value['url'] == $attachment_url ) {
											$loaded[ $key ]['id'] = $attachment_id;
											break;
										}
									}
									$need_download = false;
								}
							} else {
								// Remove the loaded media from the list if it is not exists in the media library
								if ( isset( $loaded[ $v ] ) ) {
									unset( $loaded[ $v ] );
								} else {
									foreach ( $loaded as $key => $value ) {
										if ( $value['url'] == $attachment_url ) {
											unset( $loaded[ $key ] );
											break;
										}
									}
								}
							}
						}
						// If the media file is not loaded yet or if the media was removed from the library - download it
						if ( $need_download ) {
							if ( ! isset( $loaded[ $v ] ) ) {
								$loaded[ $v ] = array(
									'id' => 0,
									'url' => '',
								);
								// Get a media file content
								$is_no_media = false;
								$file_array = array(
									'name' => $media_name,
								);
								$media_content = trx_addons_fgc( $templates_url . $media_name );
								if ( empty( $media_content ) && apply_filters( 'trx_addons_filter_elementor_templates_download_images_from_site', false ) ) {
									$media_content = trx_addons_fgc( $v );
								}
								if ( empty( $media_content ) ) {
									if ( ! empty( $no_image_attachment[ $no_media_key ]['id'] ) && ! empty( $no_image_attachment[ $no_media_key ]['url'] ) ) {
										$loaded[ $v ] = array(
											'id'  => $no_image_attachment[ $no_media_key ]['id'],
											'url' => $no_image_attachment[ $no_media_key ]['url'],
										);
									} else {
										$media_name = $file_array['name'] = ( $is_video ? 'no-video' : ( $is_audio ? 'no-audio' : 'no-image' ) )
																			. '.' . ( $no_media_key == 'img' ? 'jpg' : $no_media_key );
										$media_content = trx_addons_fgc( trx_addons_get_file_dir( 'css/images/' . $media_name ) );
										$is_no_media = true;
									}
								}
								if ( ! empty( $media_content ) ) {
									// Save a content to the file to temp location
									$temp_file_name = wp_tempnam( $media_name );
									if ( $temp_file_name && trx_addons_fpc( $temp_file_name, $media_content ) ) {
										$file_array['tmp_name'] = $temp_file_name;
									}
									if ( ! empty( $file_array['tmp_name'] ) ) {
										$attachment_post_data = array(
											'post_title' => $media_name,
											'post_content' => '',
											'post_excerpt' => '',
											'post_status' => 'inherit',
										);
										// Allow SVG uploading
										if ( $ext == 'svg' ) {
											$old_setting = trx_addons_get_setting( 'allow_upload_svg', false );
											trx_addons_set_setting( 'allow_upload_svg', true );
										}
										$old_setting = trx_addons_get_setting( 'allow_upload_svg', false );
										trx_addons_set_setting( 'allow_upload_svg', true );
										// Save an image to the media library
										$attachment_id = media_handle_sideload( $file_array, 0, null, $attachment_post_data );
										// Restore the old SVG setting
										if ( $ext == 'svg' ) {
											trx_addons_set_setting( 'allow_upload_svg', $old_setting );
										}
										// Save the result to the cache
										if ( ! is_wp_error( $attachment_id ) ) {
											$loaded[ $v ] = array(
												'id' => $attachment_id,
												'url' => wp_get_attachment_url( $attachment_id ),	//trx_addons_get_attachment_url( $attachment_id )
											);
											// Save the no-media data
											if ( $is_no_media ) {
												$no_image_attachment[ $no_media_key ] = array(
													'id' => $loaded[ $v ]['id'],
													'url' => $loaded[ $v ]['url'],
												);
												update_option( 'trx_addons_no_image_attachment', $no_image_attachment );
											}
										}
									}
								}
							}
							// Replace the URL in the content
							if ( ! empty( $loaded[ $v ]['id'] ) && ! empty( $loaded[ $v ]['url'] ) ) {
								$content[ $k ] = $loaded[ $v ]['url'];
								if ( isset( $content['id'] ) ) {
									$content['id'] = $loaded[ $v ]['id'];
								}
							}
							// Update the original loaded data with a new URL and ID if a media file was removed from the media library and loaded again
							if ( ! empty( $loaded[ $v ]['id'] ) && ! empty( $loaded[ $v ]['url'] ) && trx_addons_is_local_url( $v ) ) {
								foreach ( $loaded as $key => $value ) {
									if ( $value['url'] == $v ) {
										$loaded[ $key ]['id'] = $loaded[ $v ]['id'];
										$loaded[ $key ]['url'] = $loaded[ $v ]['url'];
										unset( $loaded[ $v ] );
										break;
									}
								}
							}
						}
					} else if ( isset( $content['is_external'] ) ) {	// Replace all links in the content with '#' to prevent loading of external resources
						$content[ $k ] = '#';
						// $content['is_external'] = '';
					}
				}
			}
		}
		// Save the list of loaded media
		if ( $first_call ) {
			$this->set_list_loaded_media( $loaded );
		}
		return $content;
	}

	/**
	 * Change the thumbnail size names - replace the prefix 'elementra-thumb-' with the 'current-theme-slug-thumb-'
	 * 
	 * @param array $content  Template content
	 * @param string $template_name  Template name
	 * @param string $old_theme_slug  Theme slug to replace
	 * @param string $new_theme_slug  New theme slug to replace with
	 * 
	 * @return array  Modified template content
	 */
	public function replace_thumb_sizes( $content, $template_name = '', $old_theme_slug = '', $new_theme_slug = '' ) {
		if ( is_array( $content ) || is_object( $content ) ) {
			if ( empty( $old_theme_slug ) ) {
				$old_theme_slug = apply_filters( 'trx_addons_filter_templates_library_replace_thumb_sizes_theme_slug', 'elementra' );
				$new_theme_slug = get_template();
			}
			foreach ( $content as $k => $v ) {
				if ( is_array( $v ) ) {
					$content[ $k ] = $this->replace_thumb_sizes( $v, $template_name, $old_theme_slug, $new_theme_slug );
				} else if ( is_string( $v ) && strpos( $v, $old_theme_slug . '-thumb-' ) !== false ) {
					$content[ $k ] = str_replace( $old_theme_slug . '-thumb-', $new_theme_slug . '-thumb-', $v );
				}
			}
		}
		return $content;
	}

	/**
	 * Check an Elementor's template for each widget exists and replace them with the default ones if not exists
	 * 
	 * @param array $content  Template content
	 * @param string $template_name  Template name
	 * 
	 * @return array  Modified template content
	 */
	public function prepare_template( $content, $template_name = '' ) {
		if ( is_array( $content ) || is_object( $content ) ) {
			foreach ( $content as $k => $v ) {
				if ( ! empty( $v['elements'] ) ) {
					$content[ $k ]['elements'] = $this->prepare_template( $v['elements'], $template_name );
				} else if ( ! empty( $v['elType'] ) && $v['elType'] == 'widget' && ! empty( $v['widgetType'] ) ) {
					$widget = $v['widgetType'];
					$widget_class = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $widget );
					if ( empty( $widget_class ) ) {
						// Replace the widget with the default one
						$content[ $k ]['widgetType'] = 'alert';
						$content[ $k ]['settings'] = array(
							'alert_type' => 'warning',
							'show_dismiss' => '',
							'alert_title' => sprintf( __( 'Unavailable Widget!', 'trx_addons' ), $widget, $template_name ),
							'alert_description' => sprintf( __( 'Widget "%s" from the template "%s" is not available now!', 'trx_addons' ), $widget, $template_name ),
						);
					}
				}
			}
		}
		return $content;
	}


	/**
	 * Parse the image to the string with layout schema
	 * 
	 * @hooked wp_ajax_trx_addons_elementor_templates_library_image_to_layout
	 */
	public function image_to_layout() {

		trx_addons_verify_nonce();

		$response = array(
			'error' => '',
			'data' => array()
		);

		$image = '';
		$image_send = apply_filters( 'trx_addons_filter_elementor_templates_image_to_layout_image_send', 'content' );	// 'content' - send to the API the content of the file (for any site - local or with public access)
																														// 'url' - send to the API the local URL of the uploaded file (only for the sites with public access)

		if ( ! empty( $_FILES["upload_image"]["tmp_name"] ) ) {
			$validate = wp_check_filetype( $_FILES["upload_image"]["name"] );
			if ( $validate['type'] == false ) {
				$answer['error'] = __( "File type is not allowed.", "trx_addons" );
			} else {
				$ext = trx_addons_get_file_ext( $_FILES["upload_image"]["name"] );
				if ( empty( $ext ) ) {
					$ext = 'png';
				}
				if ( $image_send == 'url' ) {
					$image = trx_addons_uploads_save_data( trx_addons_fgc( $_FILES["upload_image"]["tmp_name"] ), array(
						'expire' => apply_filters( 'trx_addons_filter_ai_helper_uploaded_image_expire_time', 10 * 60 ),
						'ext' => $ext,
					) );
				} else {
					$image = base64_encode( trx_addons_fgc( $_FILES["upload_image"]["tmp_name"] ) );
				}
			}
		}

		if ( empty( $image ) ) {
			$response['error'] = esc_html__( 'No image to parse!', 'trx_addons' );

		} else if ( ! class_exists( '\TrxAddons\AiHelper\TrxAiAssistants' ) ) {
			$response['error'] = esc_html__( 'Images Parser is not available!', 'trx_addons' );
		
		} else {
			$rez = \TrxAddons\AiHelper\TrxAiAssistants::instance()->image_to_layout( array(
				'image'  => $image,
				'method' => $image_send,
				'ext'    => $ext,
			) );

			if ( empty( $rez['error'] ) ) {
				$response['data'] = ! empty( $rez['layout'] ) ? $rez['layout'] : '';
			} else {
				$response['error'] = ! empty( $rez['error']['message'] )
										? $rez['error']['message']
										: ( is_string( $rez['error'] ) ? $rez['error'] : __( 'Unexpected server response while parsing the image.', 'trx_addons' ) );
			}
		}

		trx_addons_ajax_response( $response );
	}

	/**
	 * Load styles and scripts for the templates library editor area
	 *
	 * @return void
	 */
	public function enqueue_editor_scripts() {
		wp_enqueue_script( 'trx_addons_elementor_extension_templates_library', trx_addons_get_file_url( TRX_ADDONS_PLUGIN_ADDONS . 'elementor-templates/js/templates-library.js' ), array( 'jquery' ), null, false );
		wp_enqueue_style( 'trx_addons_elementor_extension_templates_library', trx_addons_get_file_url( TRX_ADDONS_PLUGIN_ADDONS . 'elementor-templates/css/templates-library.css'), array( 'dashicons' ), null );
	}

	/**
	 * Load styles and scripts for the templates library preview area
	 *
	 * @return void
	 */
	public function enqueue_preview_scripts() {
		wp_enqueue_style( 'trx_addons_elementor_extension_templates_library', trx_addons_get_file_url( TRX_ADDONS_PLUGIN_ADDONS . 'elementor-templates/css/templates-library-preview.css'), array( 'dashicons' ), null );
	}

	/**
	 * Localize script to show messages in the admin mode
	 * 
	 * @hooked 'trx_addons_filter_localize_script_admin'
	 * 
	 * @param array $vars  Array of variables to be passed to the script
	 * 
	 * @return array  Modified array of variables
	 */
	function localize_script_admin( $vars ) {
		$vars['elementor_templates_library'] = $this->get_list_templates();
		if ( ! is_array( $vars['elementor_templates_library'] ) ) $vars['elementor_templates_library'] = array();
		$vars['elementor_templates_library_favorites'] = $this->get_list_favorites();
		$vars['elementor_templates_library_tabs'] = $this->get_tabs_and_categories();
		$vars['elementor_templates_library_url'] = '//upgrade.themerex.net/templates/elementor';
		$vars['elementor_templates_library_pagination_items'] = array( 'block' => 50, 'page' => 20 );
		$vars['elementor_templates_library_navigation_style'] = apply_filters( 'trx_addons_filter_templates_library_navigation_style', 'toolbar' );	// 'toolbar' or 'sidebar'
		$vars['msg_elementor_templates_library_button_title'] = esc_html__( "ThemeREX Templates", 'trx_addons' );
		$vars['msg_elementor_templates_library_title'] = esc_html__( "ThemeREX Templates", 'trx_addons' ) . trx_addons_get_theme_doc_link( '#theme_addons_elementor_templates' );
		$vars['msg_elementor_templates_library_close'] = esc_html__( "Close Library", 'trx_addons' );
		$vars['msg_elementor_templates_library_preview_close'] = esc_html__( "Close preview", 'trx_addons' );
		$vars['msg_elementor_templates_library_refresh'] = esc_html__( "Refresh Library", 'trx_addons' );
		$vars['msg_elementor_templates_library_refresh_title'] = esc_html__( "Check for updates on the templates library server", 'trx_addons' );
		$vars['msg_elementor_templates_library_search'] = esc_html__( "SEARCH", 'trx_addons' );
		$vars['msg_elementor_templates_library_image_to_layout'] = esc_html__( "Filter by image", 'trx_addons' );
		$vars['msg_elementor_templates_library_image_to_layout_accuracy'] = esc_html__( "Accuracy %d%", 'trx_addons' );
		$vars['msg_elementor_templates_library_category_all'] = esc_html__( "All Templates", 'trx_addons' );
		$vars['msg_elementor_templates_library_category_favorites'] = esc_html__( "Favorites", 'trx_addons' );
		$vars['msg_elementor_templates_library_filter_by_category'] = esc_html__( "Filter by category", 'trx_addons' );
		$vars['msg_elementor_templates_library_empty'] = esc_html__( "No templates available!", 'trx_addons' );
		$vars['msg_elementor_templates_library_unsupported_image_type'] = esc_html__( "Unsupported image type!", 'trx_addons' );
		$vars['msg_elementor_templates_library_type_page'] = esc_html__( "Page", 'trx_addons' );
		$vars['msg_elementor_templates_library_type_block'] = esc_html__( "Block", 'trx_addons' );
		$vars['msg_elementor_templates_library_add_template'] = esc_html__( "Add Template from Library", 'trx_addons' );
		$vars['msg_elementor_templates_library_import_template'] = esc_html__( "Insert", 'trx_addons' );
		$vars['msg_elementor_templates_library_import_confirm'] = esc_html__( "Insert a template into the page?", 'trx_addons' );
		$vars['msg_elementor_templates_library_item_new'] = esc_html__( "New", 'trx_addons' );
		$vars['msg_elementor_templates_library_preview_back'] = esc_html__( "Back to Library", 'trx_addons' );
		return $vars;
	}

	/**
	 * Prepare styles for the preview dialog
	 * 
	 * @hooked 'wp_head'
	 */
	function prepare_preview_styles() {
		if ( trx_addons_get_value_gp( 'utm-source' ) == 'elementor-templates-library-preview' ) {
			?>
			<style>
				.trx_template_description {
					display: none !important;
				}
			<?php
			if ( trx_addons_get_value_gp( 'utm-source-type' ) != 'header' ) {
				?>
				header.top_panel {
					display: none !important;
				}
				<?php
			}
			if ( trx_addons_get_value_gp( 'utm-source-type' ) != 'footer' ) {
				?>
				footer.footer_wrap {
					display: none !important;
				}
				<?php
			}
			?></style><?php
		}
	}

	/**
	 * Prepare a page html for the preview dialog
	 * 
	 * @hooked 'trx_addons_filter_page_content'
	 */
	function prepare_preview_html( $content ) {
		if ( trx_addons_get_value_gp( 'utm-source' ) == 'elementor-templates-library-preview' ) {
			// Remove the header from the page content (if current template if not a header template)
			if ( trx_addons_get_value_gp( 'utm-source-type' ) != 'header' ) {
				$content = preg_replace( '/<header class="top_panel[\s\S]*?<\/header>/i', '', $content );
			}
			// Remove the footer from the page content (if current template if not a footer template)
			if ( trx_addons_get_value_gp( 'utm-source-type' ) != 'footer' ) {
				$content = preg_replace( '/<footer class="footer_wrap[\s\S]*?<\/footer>/i', '', $content );
			}
			// Replace all links with '#' to prevent loading of external resources
			$content = preg_replace( '/<a\s+([^>]*?)href=["\'](http[s]?:\/\/[^"\']+)["\'](.*?)>/i', '<a $1href="#"$3>', $content );
		}
		return $content;
	}

}
