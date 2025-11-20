<?php
namespace TrxAddons\AiHelper\Elementor;

use TrxAddons\AiHelper\Utils;

if ( ! class_exists( 'Helper' ) ) {

	/**
	 * Main class for AI Helper Elementor
	 */
	class Helper extends ContentGenerator {

		private const CONTENT_KEYS = array(
			'title', 'title_text', 'heading', 'sub_heading', 'text', 'description_text', 'description', 'content', 'editor',
			'tab_title', 'tab_subtitle', 'tab_content', 'accordion_title', 'accordion_content',
			'counter_title', 'counter_subtitle', 'starting_number', 'ending_number', 'rating',
			'team_member_name', 'team_member_position', 'person_name', 'company_name',
			'menu_title', 'menu_description', 'menu_price', 'original_price',
			'bg_text', 'caption', 'button_text',
			'slide1_title', 'slide2_title', 'ribbon_title', 'feature_text', 'tooltip_content',
			'table_title', 'table_subtitle', 'table_duration', 'table_additional_info', 'table_button_text',
		);
	
		/**
		 * Constructor
		 */
		function __construct() {
			// Enqueue scripts and styles for the elementor
			add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_styles' ) );
			add_filter( 'trx_addons_filter_localize_script_admin', array( $this, 'localize_script' ) );

			// AJAX callback for the 'Elementor Generate text' buttons
			add_action( 'wp_ajax_trx_addons_ai_helper_elementor_generate_text', array( $this, 'elementor_generate_text' ) );
			// AJAX callback to fetch answer from the assistant
			add_action( 'wp_ajax_trx_addons_ai_helper_elementor_generate_text_fetch', array( $this, 'fetch_answer' ) );
		}

		/**
		 * Enqueue scripts for the elementor
		 * 
		 * @hooked 'elementor/editor/after_enqueue_scripts'
		 */
		function enqueue_scripts() {
			if ( self::is_allowed() ) {
				wp_enqueue_script( 'trx_addons-ai-helper-elementor-generate-text', trx_addons_get_file_url( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/Elementor/assets/js/index.js' ), array( 'jquery' ), null, true );
			}
		}

		/**
		 * Enqueue styles for the elementor
		 * 
		 * @hooked 'elementor/editor/after_enqueue_styles'
		 */
		function enqueue_styles() {
			if ( self::is_allowed() ) {
				wp_enqueue_style( 'trx_addons-ai-helper-elementor-generate-text', trx_addons_get_file_url( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/Elementor/assets/css/index.css' ) );
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
			if ( self::is_allowed() ) {
				$vars['elm_ai_generate_text_btn_label'] = esc_html__( "Generate texts with AI", 'trx_addons' );
				$vars['elm_ai_generate_text_modal_close'] = esc_html__( "Close", 'trx_addons' );
				$vars['elm_ai_generate_text_modal_submit'] = esc_html__( "Generate", 'trx_addons' );
				$vars['elm_ai_generate_text_modal_purpose_label'] = esc_html__( "Purpose of the block", 'trx_addons' );
				$vars['elm_ai_generate_text_modal_purpose_pl'] = esc_html__( "About Us", 'trx_addons' );
				$vars['elm_ai_generate_text_modal_temperature_label'] = esc_html__( "Temperature from 0.0 (more focused) to 2.0 (more random)", 'trx_addons' );
				$vars['elm_ai_generate_text_modal_temperature_pl'] = esc_html__( "0.7", 'trx_addons' );
				$vars['elm_ai_generate_text_modal_title_case_label'] = esc_html__( "Title Case", 'trx_addons' );
				$vars['elm_ai_generate_text_modal_title_case_list'] = array(
																			'sentence' => esc_html__( "Sentence", 'trx_addons' ),
																			'title' => esc_html__( "Title", 'trx_addons' ),
																			'upper' => esc_html__( "Uppercase", 'trx_addons' ),
																			'lower' => esc_html__( "Lowercase", 'trx_addons' ),
																		);
				$vars['elm_ai_generate_text_modal_title_case_default'] = 'sentence';
				$vars['elm_ai_generate_text_modal_prompt_label'] = esc_html__( "Additional Prompt to AI (optional)", 'trx_addons' );
			}
			return $vars;
		}

		/**
		 * Send a query to API to process text
		 * 
		 * @hooked 'wp_ajax_trx_addons_ai_helper_elementor_generate_text'
		 */
		function elementor_generate_text() {

			trx_addons_verify_nonce();

			$answer = array(
				'error' => '',
				'data' => array(
					'fields' => array(),
					'message' => ''
				)
			);

			$content = json_decode( trx_addons_get_value_gp( 'content' ), true );
			$purpose = trx_addons_get_value_gp( 'purpose' );
			$case = trx_addons_get_value_gp( 'case' );
			$temperature = max( 0, min( 2, (float)trx_addons_get_value_gp( 'temperature', 0.7 ) ) );
			$user_prompt = trx_addons_get_value_gp( 'prompt' );
			
			if ( ! empty( $content ) ) {
				// Translate the purpose to the English
				if ( ! empty( $purpose ) && trx_addons_str_is_not_english( $purpose ) ) {
					$purpose = Utils::translate( $purpose );
				}
				// Translate the user prompt to the English
				if ( ! empty( $user_prompt ) && trx_addons_str_is_not_english( $user_prompt ) ) {
					$user_prompt = Utils::translate( $user_prompt );
				}

				// Extract fields from the content to fill with AI.
				// In the $content all extracted fields marked with the prefix '[ref_id]N[/ref_id] Original value'
				$fields = $this->parse_content( $content );

				// Load the schema and system prompt for the AI
				$schema = trx_addons_fgc( trx_addons_get_file_dir( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/Elementor/assets/ai-templates/fields-schema.json' ) );
				$system_prompt = trx_addons_fgc( trx_addons_get_file_dir( TRX_ADDONS_PLUGIN_ADDONS . 'ai-helper/support/Elementor/assets/ai-templates/fields-sp.json' ) );
				
				// Add company info to the system prompt
				$company_info = $this->company_info();
				if ( ! empty( $company_info ) && is_array( $company_info ) ) {
					$system_prompt = str_replace( '{{company_info}}', json_encode( $company_info, JSON_UNESCAPED_UNICODE ), $system_prompt );
				}

				// Get the API object
				$api = Utils::get_chat_api( $this->get_model() );

				// Process fields in batches
				$result = array();
				$messages = array();
				$batch_size = 6;	//count( $fields );
				$batch_count = 0;
				for ( $i = 0; $i < count( $fields ); $i += $batch_count) {
					// Old way: get fixed batch of fields (fields from the same widget can be in different batches)
					// $batch = array_slice( $fields, $i, $batch_size );
					// New way: put all fields from the same widget (by widget_id) to the batch
					$batch = array();
					$batch_count = 0;
					$last_widget_id = '';
					for ( $j = $i; $j < count( $fields ); $j++ ) {
						if ( $fields[ $j ]['widget_id'] != $last_widget_id ) {
							// If the next widget is different, break the loop
							$last_widget_id = $fields[ $j ]['widget_id'];
							if ( $batch_count >= $batch_size ) {
								// If the batch size is reached, break the loop
								break;
							}
						}
						$batch[] = $fields[ $j ];
						$batch_count++;
					}
					if ( $i == 0 ) {
						// Put the whole prompt to the messages array on the first iteration
						$prompt = sprintf( "Content purpose is: %s."
									. "\n\nExact string length match is obligatory. Be creative, do not repeat yourself!"
									. "\n\nGenerate unique headings (titles) and content for widgets with different 'widget_id', even if the initial field values in different widgets are the same."
									. "\n\nBe sure to change the text case to %s case in widgets 'Heading', 'Title', 'Advanced Title' and in the fields 'title' and 'heading' in other widgets."
									. "%s"
									. "\n\nContent fields to fill:\n%s",							// Prompt for AI (not need to be translated!)
									! empty( $purpose ) ? $purpose : 'Regular block',				// 1) Purpose (not need to be translated!)
									$case,															// 2) Title case
									! empty( $user_prompt ) ? "\n\n{$user_prompt}" : '',			// 3) Additional prompt (if any)
									json_encode( $batch, JSON_UNESCAPED_UNICODE )					// 4) Fields to fill
						);
					} else {
						// and only the batch of fields to fill on the next iterations
						$prompt = sprintf( "Fill the next batch with fields:\n%s",
									json_encode( $batch, JSON_UNESCAPED_UNICODE )
						);
					}
					// Add the prompt to the messages
					$messages[] = array(
						'role' => 'user',
						'content' => $prompt,
					);
					$response = $api->chat( array(
						'model' => $this->get_model(),
						'system_prompt' => $system_prompt,
						'messages' => $messages,
						'response_format' => json_decode( $schema, true ),
						'role' => 'elementor_content_generator',
						'n' => 1,
						'temperature' => $temperature,
						'max_tokens' => apply_filters( 'trx_addons_filter_ai_helper_content_generator_max_tokens', 0 ),	// 0 - no limit
					) );

					$answer = $this->parse_response( $response, $answer );
					if ( ! empty( $answer['data']['fields'] ) ) {
						$result = array_merge( $result, ! empty( $answer['data']['fields']['fields'] ) ? $answer['data']['fields']['fields'] : $answer['data']['fields'] );
						$messages[] = array(
							'role' => 'assistant',
							'content' => json_encode( array( 'fields' => $result ), JSON_UNESCAPED_UNICODE ),
						);
						$answer['data']['fields'] = array();
					} else if ( ! empty( $answer['error'] ) ) {
						break;
					}
				}

				if ( empty( $answer['error'] ) ) {
					$answer['data']['fields'] = $this->merge_content( $content, $result );
				}

			} else {
				$answer['error'] = esc_html__( 'The json with the row content is broken!', 'trx_addons' );
			}

			// Return response to the AJAX handler
			trx_addons_ajax_response( $answer );
		}

		/**
		 * Parse the row content (array) and extract all fields to fill with AI.
		 * Extracted fields in the original content marked with the prefix '[ref_id]N[/ref_id] Original content'
		 *
		 * @param  mixed $content The row content
		 * @param  int   $id      The start ref_id for the fields
		 * 
		 * @return array The array with all fields to fill
		 */
		function parse_content( &$content, $id = 0 ) {
			$fields = array();
			$widget = '';
			$widget_id = '';
			// Parse widget's settings
			if ( ! empty( $content['elType'] ) ) {	// && $content['elType'] == 'widget' - commented to parse also parameters of containers
				$widget = isset( $content['widgetType'] )
							? $content['widgetType']
							: $content['elType'];
				if ( isset( $content['id'] ) ) {
					$widget_id = $content['id'];
				}
				if ( ! empty( $content['settings'] ) && is_array( $content['settings'] ) ) {
					$fields = $this->parse_settings( $content['settings'], $id, $widget, $widget_id );
				}
			}
			// Parse inner elements
			if ( ! empty( $content['elements'] ) && is_array( $content['elements'] ) ) {
				for ( $i = 0; $i < count( $content['elements'] ); $i++ ) {
					$fields2 = $this->parse_content( $content['elements'][ $i ], $id );
					$fields = array_merge( $fields, $fields2 );
					$id += count( $fields2 );
				}
			}
			return $fields;
		}

		/**
		 * Parse the settings of the widget and extract all fields to fill with AI.
		 * Extracted fields in the original content marked with the prefix '[ref_id]N[/ref_id] Original content'
		 * 
		 * @param  mixed $settings The widget settings
		 * @param  int   $id       The start ref_id for the fields
		 * @param  string $widget  The widget type
		 * @param  string $widget_id The widget ID
		 * 
		 * @return array The array with all fields to fill
		 */
		function parse_settings( &$settings, $id, $widget, $widget_id ) {
			$fields = array();
			foreach ( $settings as $key => $value ) {
				if ( in_array( $key, self::CONTENT_KEYS, true ) && is_string( $value ) && '' !== $value ) {
					$id++;
					$fields[] = array(
						'ref_id' => (string)$id,
						'widget' => $widget,
						'widget_id' => $widget_id,
						'field' => $key,
						'value' => $value
					);
					$settings[ $key ] = "[ref_id]{$id}[/ref_id] {$value}";
				} else if ( is_array( $value ) ) {
					$fields2 = $this->parse_settings( $settings[ $key ], $id, $widget, $widget_id );
					$fields = array_merge( $fields, $fields2 );
					$id += count( $fields2 );
				}
			}
			return $fields;
		}


		/**
		 * Merge the generated fields with the original content:
		 * replace the original fields with the prefix '[ref_id]N[/ref_id] Original content'
		 * with the generated values
		 *
		 * @param  mixed $content The row content
		 * @param  array $fields  The array with the generated fields
		 * 
		 * @return array The row content with the generated fields
		 */
		function merge_content( $content, $fields ) {
			if ( is_array( $content ) && is_array( $fields ) ) {
				foreach ( $content as $key => $value ) {
					if ( in_array( $key, self::CONTENT_KEYS ) && is_string( $value ) ) {
						if ( preg_match( '/\[ref_id\](\d+)\[\/ref_id\].*/', $value, $matches ) ) {
							$ref_id = $matches[1];
							$found = false;
							foreach ( $fields as $field ) {
								if ( $field['ref_id'] == $ref_id ) {
									$content[ $key ] = wp_unslash( $field['value'] );
									$found = true;
									break;
								}
							}
							if ( ! $found ) {
								$content[ $key ] = str_replace( "[ref_id]{$ref_id}[/ref_id] ", '', $value );
							}
						}
					} else if ( is_array( $value ) ) {
						$content[ $key ] = $this->merge_content( $value, $fields );
					}
				}
			}
			return $content;
		}

		/**
		 * Get the company info for the AI (from the plugin's options)
		 * 
		 * @return array The company info
		 */
		function company_info() {
			// Get the company info from the plugin's options
			$company_info = array(
				'organization_model' => array(
					'company_name' => trx_addons_get_option( 'ai_helper_company_name' ),
					'industry' => trx_addons_get_option( 'ai_helper_company_industry' ),
					'contacts' => array(
						'address' => trx_addons_get_option( 'ai_helper_company_address' ),
						'phone' => trx_addons_get_option( 'ai_helper_company_phone' ),
						'email' => trx_addons_get_option( 'ai_helper_company_email' ),
					),
					'description' => trx_addons_get_option( 'ai_helper_company_description' ),
					'mission' => trx_addons_get_option( 'ai_helper_company_mission' ),
					'history' => trx_addons_get_option( 'ai_helper_company_history' ),
					'values' => array_map( 'trim', explode( "\n", trx_addons_get_option( 'ai_helper_company_values' ) ) ),
					'services' => array(),
					'team' => array(),
				),
			);
			// Add services
			$services = trx_addons_get_option( 'ai_helper_company_services' );
			if ( is_array( $services ) ) {
				foreach ( $services as $service ) {
					$company_info['organization_model']['services'][] = array(
						'service_name' => $service['name'],
						'service_description' => $service['description'],
						'service_features' => array_map( 'trim', explode( "\n", $service['features'] ) ),
					);
				}
			}
			// Add team members
			$team = trx_addons_get_option( 'ai_helper_company_team' );
			if ( is_array( $team ) ) {
				foreach ( $team as $member ) {
					$company_info['organization_model']['team'][] = array(
						'name' => $member['name'],
						'position' => $member['position'],
						'bio' => $member['bio'],
					);
				}
			}

			return $company_info;
		}
   }
}