<?php
namespace TrxAddons\AiHelper\Elementor;

if ( ! class_exists( 'BlogGenerator' ) ) {

    /**
	 * Main class for AI Blog Generator
	 */
	class BlogGenerator extends ContentGenerator {

		/**
		 * An array with all images with width >= 1200px from the media library
		 */
		var $images = array();

		/**
		 * An index of the next featured image in the $images array
		 */
		var $featured_image_idx = -1;

		/**
		 * An index of the next content image in the $images array
		 */
		var $content_image_idx = -1;

		/**
		 * A flag to set featured images in the posts
		 */
		var $set_featured_images = 0;

		/**
		 * A flag to set content images in the posts
		 */
		var $set_content_images  = 0;

		/**
		 * A content image to use in the posts
		 * If empty - use the next image from the media library or placeholder
		 * If not empty - use the specified image
		 */
		var $content_image_url   = '';
		var $content_image_id    = 0;

		/**
		 * A minimum width of the images to use in the blog posts
		 */
		const MIN_IMAGE_WIDTH = 1024;

		/**
		 * Constructor
		 */
		function __construct() {
			// Add the 'Generate Company' button to the plugin's options
			add_action( "trx_addons_action_load_scripts_admin", array( $this, 'options_page_load_scripts' ) );
			add_filter( 'trx_addons_filter_localize_script_admin', array( $this, 'localize_script' ) );

			// AJAX callback for the 'Blog Generator' button
			add_action( 'wp_ajax_trx_addons_ai_helper_blog_generator', array( $this, 'blog_generator' ) );

			// Callback function to fetch answer from the assistant
			add_action( 'wp_ajax_trx_addons_ai_helper_blog_generator_fetch', array( $this, 'fetch_answer' ) );
		}

		/**
		 * Load scripts for ThemeREX Addons options page
		 * 
		 * @hooked trx_addons_action_load_scripts_admin
		 * 
		 * @trigger trx_addons_filter_need_options
		 * 
		 * @param bool $all Load all scripts. Default is false. Not used in this function
		 */
		function options_page_load_scripts( $all = false ) {
			if ( apply_filters('trx_addons_filter_need_options', isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'trx_addons_options' )
				&& self::is_allowed()
			) {
				wp_enqueue_style( 'trx_addons-ai-blog-generator', trx_addons_get_file_url( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/Elementor/assets/css/blog-generator.css' ) );
				wp_enqueue_script( 'trx_addons-ai-blog-generator', trx_addons_get_file_url( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/Elementor/assets/js/blog-generator.js' ), array('jquery'), null, true );
			}
		}

		/**
		 * Localize script to show messages
		 * 
		 * @hooked 'trx_addons_filter_localize_script_admin'
		 * 
		 * @param array $vars  Array of variables to be passed to the script
		 * 
		 * @return array  Modified array of variables
		 */
		function localize_script( $vars ) {
			if ( apply_filters('trx_addons_filter_need_options', isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'trx_addons_options' )
				&& self::is_allowed()
			) {
				wp_enqueue_media();
				$vars['elm_ai_blog_generator_bad_data'] = esc_html__( "Unexpected answer from the API server!", 'trx_addons' );
				$vars['elm_ai_blog_generator_no_posts'] = esc_html__( "No posts inserted!", 'trx_addons' );
				$vars['elm_ai_blog_generator_posts_inserted'] = esc_html__( "%d posts are inserted!", 'trx_addons' );
				$vars['elm_ai_blog_generator_dialog_caption'] = esc_html__( "Generator Settings", 'trx_addons' );
				$vars['elm_ai_blog_generator_label_posts'] = esc_html__( "Posts", 'trx_addons' );
				$vars['elm_ai_blog_generator_label_cats'] = esc_html__( "Categories", 'trx_addons' );
				$vars['elm_ai_blog_generator_label_tags'] = esc_html__( "Tags", 'trx_addons' );
				$vars['elm_ai_blog_generator_label_comments'] = esc_html__( "Comments", 'trx_addons' );
				$vars['elm_ai_blog_generator_label_images'] = esc_html__( "Images", 'trx_addons' );
				$vars['elm_ai_blog_generator_title_case'] = esc_html__( "Case of titles", 'trx_addons' );
				$vars['elm_ai_blog_generator_title_case_title'] = esc_html__( "Title", 'trx_addons' );
				$vars['elm_ai_blog_generator_title_case_sentence'] = esc_html__( "Sentence", 'trx_addons' );
				$vars['elm_ai_blog_generator_total'] = esc_html__( "Total", 'trx_addons' );
				$vars['elm_ai_blog_generator_per_post'] = esc_html__( "Per post", 'trx_addons' );
				$vars['elm_ai_blog_generator_every_post'] = esc_html__( "In each # post", 'trx_addons' );
				$vars['elm_ai_blog_generator_set_featured_images'] = esc_html__( "Featured", 'trx_addons' );
				$vars['elm_ai_blog_generator_set_content_images'] = esc_html__( "In content", 'trx_addons' );
				$vars['elm_ai_blog_generator_choose_image'] = esc_html__( "Choose Image", 'trx_addons' );
			}
			return $vars;
		}

		/**
		 * Send a query to API to generate a demo data for the blog by the industry
		 * 
		 * @hooked 'wp_ajax_trx_addons_ai_helper_blog_generator'
		 */
		function blog_generator() {

			trx_addons_verify_nonce();

			$answer = array(
				'error' => '',
				'success' => '',
				'data' => array(
					'tags_and_cats' => array(),
					'posts' => array(),
					'message' => ''
				)
			);

			$fields = trx_addons_get_value_gp( 'fields' );
			if ( empty( $fields['ai_helper_company_name'] ) || empty( $fields['ai_helper_company_industry'] ) ) {
				$answer['error'] = esc_html__( 'Please fill the company name and industry before generate a company demo blog', 'trx_addons' );
			} else {
				// Get the generator settings
				$posts_total   = max( 1, min( 100, (int)trx_addons_get_value_gp( 'posts_total', 9 ) ) );
				$cats_total    = max( 0, min( 20, (int)trx_addons_get_value_gp( 'cats_total', 6 ) ) );
				$cats_per_post = max( 0, min( 10, (int)trx_addons_get_value_gp( 'cats_per_post', 1 ) ) );
				$tags_total    = max( 0, min( 20, (int)trx_addons_get_value_gp( 'tags_total', 9 ) ) );
				$tags_per_post = max( 0, min( 10, (int)trx_addons_get_value_gp( 'tags_per_post', 3 ) ) );
				$comments_per_post = max( 0, min( 10, (int)trx_addons_get_value_gp( 'comments_per_post', 2 ) ) );
				$comments_every_post = max( 1, min( 10, (int)trx_addons_get_value_gp( 'comments_every_post', 2 ) ) );
				$title_case = trx_addons_get_value_gp( 'title_case', 'sentence' );
				$this->set_featured_images = (int)trx_addons_get_value_gp( 'set_featured_images', 0 );
				$this->set_content_images  = (int)trx_addons_get_value_gp( 'set_content_images', 0 );
				$this->content_image_url   = trx_addons_get_value_gp( 'content_image', '' );
				$this->content_image_id    = ! empty( $this->content_image_url ) ? trx_addons_attachment_url_to_postid( $this->content_image_url ) : 0;
				// Generate tags and categories
				$answer = $this->generate_tags_and_categories( array(
																	'company_name' => $fields['ai_helper_company_name'],
																	'company_industry' => $fields['ai_helper_company_industry'],
																	'cats' => max( $cats_per_post * 3, $cats_total ),
																	'tags' => max( $tags_per_post * 3, $tags_total ),
																),
																$answer
				);
				// Generate posts
				if ( ! empty( $answer['data']['tags_and_cats'] ) ) {
					$answer = $this->generate_posts( array(
														'company_name' => $fields['ai_helper_company_name'],
														'company_industry' => $fields['ai_helper_company_industry'],
														'total' => $posts_total,
														'title_case' => $title_case,
													),
													$answer
					);
				} else {
					$answer['error'] = esc_html__( 'Can not generate tags and categories', 'trx_addons' );
				}
		        // Add randomly selected tags and categories to each post
				if ( ! empty( $answer['data']['posts'] ) ) {
					$meta = array( 'tags' => $tags_per_post, 'categories' => $cats_per_post );
					for ( $p = 0; $p < count( $answer['data']['posts'] ); $p++ ) {
						foreach( $meta as $meta_key => $meta_value ) {
							$items = array();
							if ( $meta_value > 0 ) {
								shuffle( $answer['data']['tags_and_cats'][$meta_key] );
								$idx = (array)array_rand( $answer['data']['tags_and_cats'][$meta_key], $meta_value );
								foreach( $idx as $i ) {
									$items[] = $answer['data']['tags_and_cats'][$meta_key][$i];
								}
							}
							$answer['data']['posts'][$p][$meta_key] = $items;
						}
					}
				} else if ( empty( $answer['error'] ) ) {
					$answer['error'] = esc_html__( 'Can not generate blog posts', 'trx_addons' );
				}
		        // Add comments to each post
				if ( ! empty( $answer['data']['posts'] ) && $comments_per_post > 0 ) {
					for ( $p = 0; $p < count( $answer['data']['posts'] ); $p++ ) {
						if ( $comments_every_post > 1 && $p % $comments_every_post === 0 ) {
							continue;
						}
						$answer['data']['posts'][$p]['comments'] = array();
						for ( $c = 0; $c < $comments_per_post; $c++ ) {
							$answer['data']['posts'][$p]['comments'][] = $this->get_random_comment( $c );
						};
					}
				}
		        // Add a meta-data with the Elementor data for each post
				if ( ! empty( $answer['data']['posts'] ) ) {
					// Prepare images
					$this->get_images( count( $answer['data']['posts'] ) );
					// Prepare the post meta data
					for ( $p = 0; $p < count( $answer['data']['posts'] ); $p++ ) {
						$meta = $this->get_post_meta();
						if ( ! empty( $meta['_content'] ) ) {
							$answer['data']['posts'][$p]['content'] = $meta['_content'];
							unset( $meta['_content'] );
						}
						$answer['data']['posts'][$p]['meta'] = $meta;
					}
				}
		        // Insert posts to the database with the meta-data, comments and tags/categories
				if ( ! empty( $answer['data']['posts'] ) ) {
					$this->insert_posts( $answer['data']['posts'] );
					// Clear the cache for the posts, categories, tags and comments
					trx_addons_clear_cache('all');
				}
			}

			// Return response to the AJAX handler
			trx_addons_ajax_response( $answer );
		}

		/**
		 * Insert posts to the database with the meta-data, comments and tags/categories, specified in the $posts array
		 * Check each category/tag if it exists, if not - create it
		 * 
		 * @param array $posts  The array of posts to insert
		 */
		function insert_posts( $posts ) {
			$tags = array();
			$cats = array();
			$time_offset = count( $posts ) * 60;

			foreach ( $posts as $post ) {
				// Prepare categories list
				$post_cats = array();
				if ( ! empty( $post['categories'] ) ) {
					foreach ( $post['categories'] as $cat ) {
						$slug = sanitize_title( trim( $cat ) );
						$id = isset( $cats[ $slug ] ) ? $cats[ $slug ] : 0;
						if ( $id == 0 ) {
							// Check if the category already exists
							$term = get_term_by( 'slug', $slug, 'category', ARRAY_A );
							if ( empty( $term['term_id'] ) ) {
								// If not, create it
								$term = wp_insert_term( trim( $cat ), 'category', array(
									'description' => '',
									'slug' => $slug,
								) );
								// Add a category to the list of already created categories
								$id = $cats[ $slug ] = $term['term_id'];
							} else {
								$id = $term['term_id'];
							}
						}
						$post_cats[] = $id;
					}
				}
				// Prepare tags list
				$post_tags = array();
				if ( ! empty( $post['tags'] ) ) {
					foreach ( $post['tags'] as $tag ) {
						$slug = sanitize_title( trim( $tag ) );
						$id = isset( $tags[ $slug ] ) ? $tags[ $slug ] : 0;
						if ( $id == 0 ) {
							// Check if the tag already exists
							$term = get_term_by( 'slug', $slug, 'post_tag', ARRAY_A );
							if ( empty( $term['term_id'] ) ) {
								// If not, create it
								$term = wp_insert_term( trim( $tag ), 'post_tag', array(
									'description' => '',
									'slug' => $slug,
								) );
								// Add a tag to the list of already created tags
								$id = $cats[ $slug ] = $term['term_id'];
							} else {
								$id = $term['term_id'];
							}
						}
						$post_tags[] = $id;
					}
				}
				// Insert post
				$post_id = wp_insert_post( array(
					'post_title' => $post['title'],
					'post_content' => $post['content'],
					'post_status' => 'publish',
					'post_type' => 'post',
					'post_author' => get_current_user_id(),
					'post_date' => date( 'Y-m-d H:i:s', time() - $time_offset + (int) ( (float) get_option( 'gmt_offset' ) * 3600 ) ),	// current_time( 'mysql' ),
					'post_date_gmt' => date( 'Y-m-d H:i:s', time() - $time_offset ),													// current_time( 'mysql', 1 ),
					'post_category' => $post_cats,
					'tags_input' => $post_tags,
				) );
				// Add meta data
				if ( ! empty( $post['meta'] ) ) {
					foreach ( $post['meta'] as $key => $value ) {
						update_post_meta( $post_id, $key, $value );
					}
				}
				// Add comments
				if ( ! empty( $post['comments'] ) ) {
					$comment_time_offset = count( $post['comments'] ) * 60;
					foreach ( $post['comments'] as $comment ) {
						wp_insert_comment( array(
							'comment_post_ID' => $post_id,
							'comment_author' => $comment['author'],
							'comment_author_url' => $comment['author_url'],
							'comment_author_email' => $comment['author_email'],
							'comment_content' => $comment['content'],
							'user_id' => get_current_user_id(),
							'comment_approved' => 1,
							'comment_date' => date( 'Y-m-d H:i:s', time() - $time_offset + $comment_time_offset + (int) ( (float) get_option( 'gmt_offset' ) * 3600 ) ),
							'comment_date_gmt' => date( 'Y-m-d H:i:s', time() - $time_offset + $comment_time_offset ),
						) );
						$comment_time_offset += 60;
					}
				}
				$time_offset -= 60;
			}
		}

		/**
		 * Send a query to API to generate tags and categories for the blog by the industry
		 * 
		 * @param array $args  Array with the arguments:
		 * @param string $args['company_name']  The name of the company
		 * @param string $args['company_industry']  The industry of the company
		 * @param int $args['tags']  The number of tags to generate
		 * @param int $args['cats']  The number of categories to generate
		 * @param array $answer  The answer to return
		 * 
		 * @return array  The answer with a key 'tags_and_cats' containing an array with tags and categories in the related keys
		 */
		function generate_tags_and_categories( $args, $answer ) {
			$response = $this->get_api()->query(
				array(
					'model' => $this->get_model(),
					'system_prompt' => __( "You are a helpful blog content generator.", 'trx_addons' ),
					'prompt' => sprintf( __( "Generate %d unique tags and %d unique categories for a company '%s' from the industry '%s'", 'trx_addons' ),
											$args['tags'],
											$args['cats'],
											$args['company_name'],
											$args['company_industry']
										)
								. ' ' . __( "Tags and categories should be relevant to the blog content and the company's focus.", 'trx_addons' )
								. ' ' . __( "Tags should be 1-2 words each, like 'Adventure', 'Travel Tips', 'Hiking Trails', etc.", 'trx_addons' )
								. ' ' . __( "Categories should also be 1-2 words each, like 'Travel Guide', 'Outdoor Experiences', etc.", 'trx_addons' )
								. ' ' . __( "Return a JSON object with two keys: 'tags' (array of 5 strings) and 'categories' (array of 5 strings).", 'trx_addons' ),
					'role' => 'blog_generator',
					'n' => 1,
					'temperature' => 0.8,
					'response_format' => ['type' => 'json_object'],
					'max_tokens' => 4000,
				)
			);
			$answer = $this->parse_response( $response, $answer, 'tags_and_cats' );
			return $answer;
		}

		/**
		 * Send a query to API to generate a blog posts for the company in the specified industry
		 * 
		 * @param array $args  Array with the arguments:
		 * @param string $args['company_name']  The name of the company
		 * @param string $args['company_industry']  The industry of the company
		 * @param int    $args['total']  The total number of posts to generate
		 * @param string $args['title_case']  The case of the post titles (title or sentence)
		 * @param array $answer  The answer to return
		 * 
		 * @return array  The answer with a key 'posts' containing an array with tags and categories in the related keys
		 */
		function generate_posts( $args, $answer ) {
			$template = json_decode( trx_addons_fgc( trx_addons_get_file_dir( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/Elementor/assets/ai-templates/blog-posts.json' ) ), true );
			if ( isset( $template['instructions'] ) ) {
				$response = $this->get_api()->query(
					array(
						'model' => $this->get_model(),
						'system_prompt' => wp_json_encode( $template ),
						'prompt' => sprintf( __( "Generate %d engaging blog post titles for the topic '%s' in the '%s' case.", 'trx_addons' ),
												$args['total'],
												$args['company_industry'],
												$args['title_case']
											)
									. ' ' . __( "Return a JSON object with a key 'posts' whose value is a list of posts.", 'trx_addons' )
									. ' ' . __( "Each post should have only the key 'title'. Do not include tags or categories.", 'trx_addons' ),
						'role' => 'blog_generator',
						'n' => 1,
						'temperature' => 1,
						'response_format' => ['type' => 'json_object'],
						'max_tokens' => apply_filters( 'trx_addons_filter_ai_helper_blog_generator_max_tokens', 4000 ),
					)
				);
				$answer = $this->parse_response( $response, $answer, 'posts' );
				if ( isset( $answer['data']['posts']['posts'] ) ) {
					$answer['data']['posts'] = $answer['data']['posts']['posts'];
				}
				$answer['data']['posts'] = array_slice( $answer['data']['posts'], 0, $args['total'] );
			} else {
				$answer['error'] = esc_html__( 'Can not load the blog posts template', 'trx_addons' );
			}
			return $answer;
		}

		/**
		 * Return the post meta data:
		 * '_elementor_data' - json with the post content for Elementor
		 * '_elementor_page_assets' - serialized string with the post assets for Elementor
		 * 
		 * @return array  The post meta data
		 */
		function get_post_meta() {
			// Prepare a post meta data
			$meta = array(
				'_wp_page_template' => 'default',
				'trx_addons_post_views_count' => mt_rand( 100, 1000 ),
				'trx_addons_post_likes_count' => mt_rand( 10, 100 ),
			);
			// Add the thumbnail
			$thumbnail = $this->get_image( 'featured', 'id' );
			if ( (int)$thumbnail > 0 ) {
				$meta['_thumbnail_id'] = $thumbnail;
			}
			// Add an Elementor data
			$elementor_data = json_decode( trx_addons_fgc( trx_addons_get_file_dir( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/Elementor/assets/ai-templates/blog-posts-elementor-data.json' ) ), true );
			if ( ! empty( $elementor_data ) ) {
				$elementor_assets = array();
				$meta['_content'] = $this->prepare_elementor_data( $elementor_data, $elementor_assets );
				$meta['_elementor_data'] = json_encode( $elementor_data );
				// $meta['_elementor_page_assets'] = serialize( array( 'styles' => $elementor_assets ) );
				$meta['_elementor_edit_mode'] = 'builder';
				$meta['_elementor_template_type'] = 'wp-post';
				$meta['_elementor_version'] = defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.28.3';
			}
			return $meta;
		}

		/**
		 * Replace the element's 'id' with the random unique id and the 'id' and 'url' with the random images in the elementor data
		 * 
		 * @param array $elementor_data  The Elementor data passed by reference (changed in place)
		 * @param array $elementor_assets  The Elementor assets passed by reference (changed in place)
		 * 
		 * @return string  The content of the post (the heading and text editor content)
		 */
		function prepare_elementor_data( &$elementor_data, &$elementor_assets ) {
			$content = '';
			$idx = 0;
			foreach ( $elementor_data as $key => $data ) {
				if ( isset( $data['id'] ) && ! empty( $data['id'] ) ) {
					$elementor_data[$key]['id'] = trx_addons_get_unique_id();
				}
				if ( isset( $data['elType'] ) && $data['elType'] == 'widget' && isset( $data['widgetType'] ) ) {
					if ( ! in_array( $data['widgetType'], $elementor_assets ) ) {
						$elementor_assets[] = $data['widgetType'];
					}
					if ( $data['widgetType'] == 'image' ) {
						$image = $this->get_image( 'content' );
						$elementor_data[$key]['settings']['image']['id'] = $image['id'];
						$elementor_data[$key]['settings']['image']['url'] = $image['url'];
					} else if ( $data['widgetType'] == 'heading' ) {
						// Replace the heading with a random text
						if ( $data['settings']['header_size'] == 'h6' ) {
							$comment = $this->get_random_comment();
							$elementor_data[$key]['settings']['title'] = $comment['author'];
						} else {
							$header = explode( '.', str_replace( ',', '', $this->get_random_text( $idx++, mt_rand( 4, 6 ) ) ) );
							$elementor_data[$key]['settings']['title'] = $header[0];
						}
						$content .= '<' . $data['settings']['header_size'] . '>' . $elementor_data[$key]['settings']['title'] . '</' . $data['settings']['header_size'] . '>';
					} else if ( $data['widgetType'] == 'text-editor' ) {
						// Replace the text with a random text
						$elementor_data[$key]['settings']['editor'] = '<p>' . $this->get_random_text( $idx++ ) . '</p>';
						$content .= $elementor_data[$key]['settings']['editor'];
					}
				} else if ( isset( $data['elements'] ) && ! empty( $data['elements'] ) ) {
					$content .= $this->prepare_elementor_data( $elementor_data[$key]['elements'], $elementor_assets );
				}
			}
			return $content;
		}

		/**
		 * Get a next image from the media library
		 * 
		 * @param string $area  The area to get the image for: 'featured' or 'content'
		 * @param string $prop  The property to return: 'all' (default), 'id', 'url'
		 * 
		 * @return array|string  The image data (id and url) or the specified property of the image
		 */
		function get_image( $area, $prop = 'all' ) {
			$image = array(
				'id' => 0,
				'url' => $area == 'content' && class_exists( '\Elementor\Utils' ) ? \Elementor\Utils::get_placeholder_image_src() : trx_addons_get_no_image(),
			);
			if ( $area == 'featured' ) {	// Get the next featured image from the start of the array
				if ( $this->set_featured_images && $this->featured_image_idx >= 0 ) {
					$image = $this->images[ $this->featured_image_idx++ ];
					if ( $this->featured_image_idx >= count( $this->images ) ) {
						$this->featured_image_idx = 0;
					}
				}
			} else {	// Get the next content image from the end of the array (to avoid using the same image in the post content and as a featured image)
				if ( $this->set_content_images ) {
					if ( ! empty( $this->content_image_url ) ) {
						// If the content image is specified, use it
						$image = array(
							'id'  => $this->content_image_id,
							'url' => $this->content_image_url
						);
					} else if ( $this->content_image_idx >= 0 ) {
						// If the content image is the same as the featured image, skip it
						if ( $this->content_image_idx == $this->featured_image_idx - 1 ) {
							$this->content_image_idx--;
							if ( $this->content_image_idx < 0 ) {
								$this->content_image_idx = count( $this->images ) - 1;
							}
						}
						$image = $this->images[ $this->content_image_idx-- ];
						if ( $this->content_image_idx < 0 ) {
							$this->content_image_idx = count( $this->images ) - 1;
						}
					}
				}
			}
			return $prop == 'all' ? $image : $image[ $prop ];
		}

		/**
		 * Get a images with a corresponding width from the media library
		 * 
		 * @param int $total  The total number of images to get
		 */
		function get_images( $total ) {
			$images_count = ( $this->set_featured_images > 0 ? $total : 0 )
							+ ( $this->set_content_images > 0 && empty( $this->content_image_url ) ? $total : 0 );
			if ( $images_count > 0 ) {
				$media = get_posts( array(
					'post_type' => 'attachment',
					'post_mime_type' => 'image',
					'posts_per_page' => max( 40, $images_count ),
					'orderby' => 'id',
					'order' => 'ASC',
					'post_status' => 'inherit',
					'suppress_filters' => true,
				) );
				if ( ! empty( $media ) ) {
					$cnt = 0;
					$this->images = array_filter( array_map(
								// Map each media item to an array with the image data
								function( $item ) {
									$meta = get_post_meta( $item->ID, '_wp_attachment_metadata', true );
									return array(
										'id' => $item->ID,
										'url' => wp_get_attachment_image_url( $item->ID, 'full' ),
										//'alt' => get_post_meta( $item->ID, '_wp_attachment_image_alt', true ),
										'width' => ! empty( $meta['width'] ) ? $meta['width'] : 0,
										'height' => ! empty( $meta['height'] ) ? $meta['height'] : 0,
									);
								}, $media ),
								// Filter out items with a not allowed width
								function( $item ) use ( &$cnt, $images_count ) {
									$allow = ! empty( $item['width'] ) && $item['width'] >= self::MIN_IMAGE_WIDTH
											&& ! empty( $item['url'] ) && strpos( $item['url'], 'placeholder' ) === false;
									if ( $allow ) {
										$cnt++;
									}
									return $cnt <= $images_count && $allow;
								} );
					// Shuffle the images to get a random order and sequence indexes
					shuffle( $this->images );
					// Set the start indexes for the featured (from the begin of the list) and content (from the end of the list) images
					if ( count( $this->images ) > 0 ) {
						if ( $this->set_featured_images > 0 ) {
							$this->featured_image_idx = 0;
						}
						if ( $this->set_content_images > 0 && empty( $this->content_image_url ) ) {
							$this->content_image_idx  = count( $this->images ) - 1;
						}
					}
				}
			}
		}

		/**
		 * Get a random comment
		 * 
		 * @param int $idx  The index of the comment to get. Default is 0.
		 * 
		 * @return array  The random comment
		 */
		function get_random_comment( $idx = 0 ) {
			static $comments = false;
			if ( $comments === false ) {
				$comments = json_decode( trx_addons_fgc( trx_addons_get_file_dir( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/Elementor/assets/ai-templates/blog-posts-comments.json' ) ), true );
				if ( is_array( $comments ) ) {
					for ( $i = 0; $i < count( $comments ); $i++ ) {
						$comments[$i]['content'] = $this->get_random_text( $i );
					}
				} else {
					$comments = array();
				}
			}
			if ( count( $comments ) > 0 ) {
				if ( $idx == 0 ) {
					shuffle( $comments );
				} else {
					$idx %= count( $comments );
				}
			}
			return count( $comments ) > 0 ? $comments[ $idx ] : array(
				'author' => __( 'John Doe', 'trx_addons' ),
				'author_url' => 'https://example.com',
				"author_email" => "john-doe@protonmail.com",
				'content' => $this->get_random_text(),
			);
		}

		/**
		 * Get a random 'Lorem ipsum...' text
		 * 
		 * @param int $idx  The index of the text to get. Default is 0.
		 * @param int $words  The number of words to generate. If 0 - do not crop the text
		 * 
		 * @return string  The random text
		 */
		function get_random_text( $idx = 0, $words = 0 ) {
			static $texts = false;
			if ( $texts === false ) {
				$texts = json_decode( trx_addons_fgc( trx_addons_get_file_dir( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/Elementor/assets/ai-templates/blog-posts-texts.json' ) ), true );
				if ( ! is_array( $texts ) ) {
					$texts = array();
				}
			}
			if ( count( $texts ) > 0 ) {
				if ( $idx == 0 ) {
					shuffle( $texts );
				} else {
					$idx %= count( $texts );
				}
				$text = $texts[ $idx ];
			} else {
				$text = __( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'trx_addons' );
			}
			if ( $words > 0 ) {
				$text = implode( ' ', array_slice( explode( ' ', $text ), 0, $words ) );
			}
			return $text;
		}
	}
}