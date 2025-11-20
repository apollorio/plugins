<?php
namespace TrxAddons\AiHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Markdown\Parser\Parsedown;

/**
 * Class to make queries to the OpenAi API
 */
class OpenAi extends Api {

	/**
	 * The coefficient to calculate the maximum number of tokens from a number of words in the text prompt
	 *
	 * @access public
	 * 
	 * @var float  The coefficient value
	 */
	var $words_to_tokens_coeff = 1.25;

	/**
	 * The coefficient to calculate the maximum number of tokens from a number of words in the html prompt
	 *
	 * @access public
	 * 
	 * @var float  The coefficient value
	 */
	var $blocks_to_tokens_coeff = 2.5;

	/**
	 * Chat endpoint for the OpenAi API: chat | responses
	 */
	var $chat_endpoint = 'chat';


	/**
	 * Class constructor.
	 *
	 * @access protected
	 */
	protected function __construct() {
		parent::__construct();
		$this->chat_endpoint = trx_addons_get_option( 'ai_helper_chat_endpoint_openai', 'chat' );
		$this->logger_section = 'open-ai';
		$this->token_option = 'ai_helper_token_openai';
	}

	/**
	 * Return a base URL to the vendor site
	 * 
	 * @param string $endpoint  The endpoint to use
	 * @param string $type      The type of the URL: api or site. Default: site
	 * 
	 * @return string  The URL to the vendor site
	 */
	public function get_url( $endpoint = '', $type = 'site' ) {
		return \ThemeRex\OpenAi\Url::baseUrl( $endpoint, $type );
	}

	/**
	 * Return an object of the API
	 * 
	 * @param string $token  API token for the API
	 * 
	 * @return api  The object of the API
	 */
	public function get_api( $token = '' ) {
		if ( empty( $this->api ) ) {
			if ( empty( $token ) ) {
				$token = $this->get_token();
			}
			if ( ! empty( $token ) ) {
				$this->api = new \ThemeRex\OpenAi\OpenAi( $token );
				$proxy = trx_addons_get_option( 'ai_helper_proxy_openai', '' );
				$proxy_auth = trx_addons_get_option( 'ai_helper_proxy_auth_openai', '' );
				if ( ! empty( $proxy ) ) {
					$this->api->setProxy( $proxy, $proxy_auth );
				}
			}
		}
		return $this->api;
	}

	/**
	 * Return a default model name for the API
	 * 
	 * @access static
	 * 
	 * 
	 * @return string  Default model name for the API
	 */
	static function get_default_model() {
		return 'openai/gpt-4o';
	}

	/**
	 * Return a model name for the API
	 * 
	 * @access static
	 * 
	 * 
	 * @return string  Model name for the API
	 */
	static function get_model() {
		$default_model = trx_addons_get_option( 'ai_helper_text_model_default', self::get_default_model() );
		return Utils::is_openai_model( $default_model ) ? $default_model : self::get_default_model();
	}

	/**
	 * Return a temperature for the API
	 * 
	 * @access protected
	 * 
	 * @return float  Temperature for the API
	 */
	protected function get_temperature() {
		return trx_addons_get_option( 'ai_helper_temperature_openai', 1.0 );
	}

	/**
	 * Return a maximum number of tokens in the prompt and response for specified model or from all available models
	 *
	 * @access static
	 * 
	 * @param string $model  Model name (flow id) for the API. If '*' - return a maximum value from all available models
	 * 
	 * @return int  The maximum number of tokens in the prompt and response for specified model or from all models
	 */
	static function get_max_tokens( $model = '' ) {
		$tokens = 0;
		if ( empty( $model ) ) {
			$model = self::get_model();
		} else {
			$model = str_replace(
				array( 'openai/default', 'openai/' ),
				array( '', '' ),
				$model
			);
		}
		if ( ! empty( $model ) ) {
			$models = Lists::get_openai_chat_models();
			$tokens = static::get_tokens_from_list( $models, $model, 'max_tokens' );
		}
		return (int)$tokens;
	}

	/**
	 * Return a maximum number of tokens in the output (response) for specified model or from all available models
	 *
	 * @access static
	 * 
	 * @param string $model  Model name (flow id) for the API. If '*' - return a maximum value from all available models
	 * 
	 * @return int  The maximum number of tokens in the output (response) for specified model or from all models
	 */
	static function get_output_tokens( $model = '' ) {
		$tokens = 0;
		if ( empty( $model ) ) {
			$model = self::get_model();
		} else {
			$model = str_replace(
				array( 'openai/default', 'openai/' ),
				array( '', '' ),
				$model
			);
		}
		if ( ! empty( $model ) ) {
			$models = Lists::get_openai_chat_models();
			$tokens = static::get_tokens_from_list( $models, $model, 'output_tokens' );
		}
		return (int)$tokens;
	}

	/**
	 * Return a list of available models for the API
	 *
	 * @access public
	 */
	public function list_models( $args = array() ) {
		$args = array_merge( array(
			'token' => $this->get_token(),
		), $args );

		$response = false;

		if ( ! empty( $args['token'] ) ) {
			$api = $this->get_api( $args['token'] );
			$response = $api->listModels();
		}

		return $response;
	}

	/**
	 * Send a query to the API
	 *
	 * @access public
	 * 
	 * @param array $args  Query arguments
	 * 
	 * @return array  Response from the API
	 */
	public function query( $args = array(), $params = array() ) {
		$args = array_merge( array(
				'token' => $this->get_token(),
				'model' => $this->get_model(),
				'role' => 'gb_assistant',
				'prompt' => '',
				'system_prompt' => '',
				'temperature' => $this->get_temperature(),
			),
			$this->chat_endpoint == 'chat'
				? array(
					'n' => 1,
					'frequency_penalty' => 0,
					'presence_penalty' => 0,
					)
				: array(),
			$args
		);

		$model_tokens = self::get_max_tokens( $args['model'] );
		if ( $model_tokens > 0 || ! isset( $args['max_tokens'] ) ) {
			$args['max_tokens'] = $model_tokens;
		}

		$response = false;

		if ( ! empty( $args['token'] ) && ! empty( $args['model'] ) && ! empty( $args['prompt'] ) ) {

			$api = $this->get_api( $args['token'] );

			$messages = array(
				array(
					'role' => $this->chat_endpoint != 'chat' ? 'developer' : 'system',
					'content' => ! empty( $args['system_prompt'] ) ? $args['system_prompt'] : $this->get_system_prompt( $args['role'] )
				)
			);
			if ( ! empty( $args['prompt'] ) && ! is_bool( $args['prompt'] ) ) {
				$messages[] = array(
					"role" => "user",
					"content" => $args['prompt']
				);
			}

			$max_tokens_key = $this->chat_endpoint == 'chat' ? 'max_tokens' : 'max_output_tokens';

			$chat_args = array_merge( array(
					'model' => $args['model'],
					'messages' => $messages,
					'temperature' => (float)$args['temperature'],
				),
				$this->chat_endpoint == 'chat'
					? array(
						'frequency_penalty' => (float)$args['frequency_penalty'],
						'presence_penalty' => (float)$args['presence_penalty'],
						'n' => (int)$args['n'],
					)
					: array(),
				! empty( $args['max_tokens'] )
					? array( $max_tokens_key => (int)$args['max_tokens'] )
					: array(),
				! empty( $args['metadata'] )
					? array( 'metadata' => $args['metadata'] )
					: array(),
			);

			if ( ! empty( $args['response_format'] ) ) {
				$chat_args['response_format'] = $args['response_format'];
			}

			$chat_args = $this->prepare_args( $chat_args );

			if ( ! isset( $chat_args[ $max_tokens_key ] ) || $chat_args[ $max_tokens_key ] > 0 ) {
				$response = $this->chat_endpoint == 'chat'
								? $api->chat( $chat_args )
								: $api->responses( $chat_args );
				if ( $response ) {
					if ( $this->chat_endpoint != 'chat' ) {
						$response = $this->prepare_response( $response, $args );
					}
					$this->logger->log( $response, 'query', $args, $this->logger_section );
				}
			} else {
				$response = array(
					'error' => esc_html__( 'The number of tokens in request is over limits.', 'trx_addons' )
				);
			}
		}

		return $response;

	}


	/**
	 * Send a chat messages to the API
	 *
	 * @access public
	 * 
	 * @param array $args  Query arguments
	 * 
	 * @return array  Response from the API
	 */
	public function chat( $args = array(), $params = array() ) {
		$args = array_merge( array(
				'token' => $this->get_token(),
				'model' => $this->get_model(),
				'messages' => array(),
				'system_prompt' => '',
				'temperature' => $this->get_temperature(),
			),
			$this->chat_endpoint == 'chat'
				? array(
					'n' => 1,
					'frequency_penalty' => 0,
					'presence_penalty' => 0,
					)
				: array(
					'response_id' => '',
					),
			$args
		);

		$model_tokens = self::get_max_tokens( $args['model'] );
		if ( $model_tokens > 0 || ! isset( $args['max_tokens'] ) ) {
			$args['max_tokens'] = $model_tokens;
		}

		$response = false;

		if ( ! empty( $args['token'] ) && ! empty( $args['model'] ) && count( $args['messages'] ) > 0 ) {

			$api = $this->get_api( $args['token'] );

			$max_tokens_key = $this->chat_endpoint == 'chat' ? 'max_tokens' : 'max_output_tokens';

			$chat_args = array_merge(
				array(
					'model' => $args['model'],
					'messages' => $args['messages'],
					'temperature' => (float)$args['temperature'],
				),
				$this->chat_endpoint == 'chat'
					? array(
						'frequency_penalty' => (float)$args['frequency_penalty'],
						'presence_penalty' => (float)$args['presence_penalty'],
						'n' => (int)$args['n'],
						)
					: array(
						'previous_response_id' => ! empty( $args['response_id'] ) ? $args['response_id'] : '',
						),
				! empty( $args['max_tokens'] )
					? array( $max_tokens_key => (int)$args['max_tokens'] )
					: array(),
				! empty( $args['metadata'] )
					? array( 'metadata' => $args['metadata'] )
					: array(),
			);

			if ( ! empty( $args['response_format'] ) ) {
				$chat_args['response_format'] = $args['response_format'];
			}

			$chat_args = $this->prepare_args( $chat_args );

			if ( ! isset( $chat_args[ $max_tokens_key ] ) || $chat_args[ $max_tokens_key ] > 0 ) {
				// Add system prompt
				$system_prompt = ! empty( $args['system_prompt'] ) ? $args['system_prompt'] : $this->get_system_prompt( 'chat' );
				$system_role = $this->chat_endpoint != 'chat' ? 'developer' : 'system';
				$system_key = $this->chat_endpoint != 'chat' ? 'input' : 'messages';
				if ( ! empty( $system_prompt ) && ( empty( $chat_args[ $system_key ][0]['role'] ) || $chat_args[ $system_key ][0]['role'] != $system_role ) ) {
					array_unshift( $chat_args[ $system_key ], array(
						'role' => $system_role,
						'content' => $system_prompt
					) );
				}

				// Send a request
				$response = $this->chat_endpoint == 'chat'
								? $api->chat( $chat_args )
								: $api->responses( $chat_args );
				if ( $response ) {
					if ( $this->chat_endpoint != 'chat' ) {
						$response = $this->prepare_response( $response, $args );
					}
					$this->logger->log( $response, 'chat', $args, $this->logger_section );
				}
			} else {
				$response = array(
					'error' => esc_html__( 'The number of tokens in request is over limits.', 'trx_addons' )
				);
			}
		}

		return $response;

	}

	/**
	 * Get the system prompt
	 *
	 * @access private
	 * 
	 * @param string $role  Role of the assistant. Possible values: 'gb_assistant', 'translator', 'chat'
	 * 
	 * @return string  System prompt
	 */
	private function get_system_prompt( $role ) {
		$prompt = '';

		if ( in_array( $role, array( 'gb_assistant', 'text_generator' ) ) ) {
			$prompt = __( 'You are an assistant for writing posts. Return only the result without any additional messages. Format the response with HTML tags.', 'trx_addons' );
		} else if ( $role == 'translator' ) {
			$prompt = __( 'You are translator. Translate the text to English or leave it unchanged if it is already in English. Return only the translation result without any additional messages and formatting.', 'trx_addons' );
		}

		return apply_filters( 'trx_addons_filter_ai_helper_get_system_prompt', $prompt, $role );
	}

	/**
	 * Prepare args for the API: limit the number of tokens, convert parameters to the endpoint format
	 *
	 * @access private
	 * 
	 * @param array $args  Query arguments
	 * @param string $type  The type of the query: text | image | audio
	 * 
	 * @return array  Prepared query arguments
	 */
	private function prepare_args( $args = array(), $type = 'text' ) {
		if ( ! empty( $args['messages'] ) && is_array( $args['messages'] ) ) {
			// Count tokens in the messages and prepare the messages content
			$tokens_total = 0;
			foreach ( $args['messages'] as $k => $message ) {
				// Remove all HTML tags
				// $message['content'] = strip_tags( $message['content'] );

				// Remove duplicate newlines
				// $message['content'] = preg_replace( "/(\r?\n){2,}/", "\n", $message['content'] );

				// Remove all Gutenberg block comments
				$message['content'] = preg_replace( '/<!--[^>]*-->/', '', $message['content'] );

				// Count tokens
				$tokens_total += $this->count_tokens( $message['content'] );

				// Add attachments to the message content
				if ( ! empty( $message['attachments'] ) ) {
					$args['messages'][ $k ] = $this->add_attachments_to_message_content( $message );	//, ! empty( $args['model'] ) ? $args['model'] : static::get_model() );
				} else {
					$args['messages'][ $k ]['content'] = $message['content'];
				}
			}
			// If an endpoint is 'responses' - convert some parameters to the new format
			if ( $this->chat_endpoint == 'responses' ) {
				// Replace 'messages' with 'input'
				$args['input'] = $args['messages'];
				unset( $args['messages'] );
				// Replace 'response_format' with 'text.format'
				if ( ! empty( $args['response_format'] ) && is_array( $args['response_format'] ) && ! empty( $args['response_format']['type'] ) ) {
					if ( $args['response_format']['type'] == 'json_schema' && ! empty( $args['response_format']['json_schema'] ) ) {
						$args['response_format']['schema'] = $args['response_format']['json_schema']['schema'];
						unset( $args['response_format']['json_schema'] );
						if ( empty( $args['response_format']['name'] ) ) {
							$args['response_format']['name'] = 'text_generator';
						}
						if ( empty( $args['response_format']['strict'] ) ) {
							$args['response_format']['strict'] = true;
						}
					}
					$args['text'] = array(
						'format' => $args['response_format']
					);
					unset( $args['response_format'] );
				}
			}
			// If max_tokens is set, limit it
			if ( isset( $args['max_tokens'] ) ) {
				$args['max_tokens'] = max( 0, $args['max_tokens'] - $tokens_total );
				// Limits a max_tokens with output_tokens (if specified)
				if ( ! empty( $args['model'] ) ) {
					$output_tokens = self::get_output_tokens( $args['model'] );
					if ( $output_tokens > 0 && $args['max_tokens'] > $output_tokens ) {
						$args['max_tokens'] = $output_tokens;
					}
				}
				// Remove max_tokens if it is less than or equal to zero
				if ( $args['max_tokens'] <= 0 ) {
					unset( $args['max_tokens'] );
				}
			}
		}
		if ( isset( $args['previous_response_id'] ) && empty( $args['previous_response_id'] ) ) {
			unset( $args['previous_response_id'] );
		}
		if ( ! empty( $args['model'] ) ) {
			if ( Utils::is_openai_dall_e_3_model( $args['model'] ) ) {
				$args['n'] = 1;
				if ( isset( $args['quality'] ) && $args['quality'] === true ) {
					$args['quality'] = 'hd';
				}
			}
			$args['model'] = str_replace(
				array( 'openai/default', 'openai/' ),
				array( '', '' ),
				$args['model']
			);
			if ( empty( $args['model'] ) ) {
				unset( $args['model'] );
			}
		}
		if ( $type == 'audio' ) {
			if ( empty( $args['input'] ) && ! empty( $args['prompt'] ) ) {
				$args['input'] = $args['prompt'];
				unset( $args['prompt'] );
			}
			if ( empty( $args['file'] ) && ! empty( $args['init_audio'] ) ) {
				$args['file'] = $args['init_audio'];
				unset( $args['init_audio'] );
			}
			if ( empty( $args['response_format'] ) && ! empty( $args['output'] ) ) {
				$args['response_format'] = $args['output'];
				unset( $args['output'] );
			}
			unset( $args['base64'] );

		}
		return $args;
	}

	/**
	 * Set a message content for the API as a string (if no attachments) or as an array (if attachments are present)
	 *
	 * @access private
	 * 
	 * @param array $message  The array with a message content and attachments (if any)
	 * 
	 * @return array|string  Prepared message content
	 */
	private function add_attachments_to_message_content( $message ) {
		$content = $message['content'];
		if ( ! empty( $message['attachments'] ) ) {
			$attachments = array();
			$image_ext = Utils::get_allowed_attachments( 'image' );
			$audio_ext = Utils::get_allowed_attachments( 'audio' );
			foreach ( $message['attachments'] as $attachment ) {
				if ( empty( $attachment['name'] ) || empty( $attachment['file'] ) || ! file_exists( $attachment['file'] ) ) {
					continue;
				}
				$ext = strtolower( pathinfo( $attachment['name'], PATHINFO_EXTENSION ) );
				if ( in_array( $ext, $image_ext ) ) {
					if ( $this->chat_endpoint == 'responses' ) {
						$attachments[] = array(
							'type' => 'input_image',
							'image_url' => 'data:image/' . $ext . ';base64,' . base64_encode( trx_addons_fgc( $attachment['file'] ) ),
						);
					} else {
						$attachments[] = array(
							'type' => 'image_url',
							'image_url' => array(
								'url' => 'data:image/' . $ext . ';base64,' . base64_encode( trx_addons_fgc( $attachment['file'] ) ),
							),
						);
					}
				} else if ( in_array( $ext, $audio_ext ) ) {
					$attachments[] = array(
						'type' => 'input_audio',
						'input_audio' => array(
							'data' => base64_encode( trx_addons_fgc( $attachment['file'] ) ),
							'format' => $ext,
						),
					);
				}
			}
			if ( count( $attachments ) > 0 ) {
				$message['content'] = array_merge(
					array(
						array(
							'type' => $this->chat_endpoint == 'responses' ? 'input_text' : 'text',
							'text' => $content,
						)
					),
					$attachments
				);
			}
			unset( $message['attachments'] );
		}
		return $message;
	}

	/**
	 * Convert a response object to the format, compatible with OpenAI API response
	 */
	protected function prepare_response( $response, $args ) {
		// Parse the answer and replace all Markdown tags with HTML tags
		$answer = '';
		if ( ! empty( $response['output'] ) && is_array( $response['output'] ) ) {
			$parser = new Parsedown();
			$parse_annotations = (int)trx_addons_get_option( 'ai_helper_parse_annotations_openai_assistants', '1' ) == 1;
			foreach ( $response['output'] as $message ) {
				if ( ! empty( $message['role'] ) ) {
					if ( ! empty( $message['content'] ) && is_array( $message['content'] ) ) {
						foreach ( $message['content'] as $k => $v ) {
							if ( ! empty( $v['type'] ) && $v['type'] == 'output_text' && ! empty( $v['text'] ) ) {
								$is_json = substr( $v['text'], 0, 1 ) == '{' || substr( $v['text'], 0, 2 ) == '[{';
								$text = $is_json ? $v['text'] : $parser->text( $v['text'] );
								$is_html = ! $is_json && preg_match( '/<(br|p|ol|ul|dl|h1|h2|h3|h4|h5|h6|img)[^>]*>/i', $text, $matches );
								// Replace annotations
								if ( $parse_annotations && $message['role'] == 'assistant' && ! empty( $v['annotations'] ) && is_array( $v['annotations'] ) ) {
									$add = array();
									$idx = 0;
									foreach ( $v['annotations'] as $annotation ) {
										if ( ! empty( $annotation['text'] ) && strpos( $text, $annotation['text'] ) !== false ) {
											$idx++;
											$parts = explode( '†', $annotation['text'] );
											$name = '';
											if ( ! empty( $parts[1] ) ) {
												$parts = explode( '】', $parts[1] );
												$name = $parts[0];
											}
											if ( ! empty( $name ) ) {
												$text = str_replace( $annotation['text'], ' ' . ( $is_html ? '<span class="sc_chat_list_item_annotation_source">' : '' ) . '[' . $idx . ']' . ( $is_html ? '</span>' : '' ) . ' ', $text );
												$add[] = $is_html
															? '<span class="sc_chat_list_item_annotation" data-tooltip-text="' . esc_attr( $name ) . '">[' . $idx . ']</span> '
															: '[' . $idx . ': ' . $name . '] ';
											}
										}
									}
									if ( ! empty( $add ) ) {
										$text .= ( $is_html ? '<br>' : "\n" ) . join( ' ', $add );
									}
								}
								// Add the text to the response
								$answer .= ( ! empty( $answer ) ? ( $is_html ? '<br>' : "\n" ) : '' )
													. str_replace( '<a ', '<a' . trx_addons_external_links_target() . ' ', $text );
							}
						}
					}
					// Exit after the first user message to get the answer from assistant and a last user question only
					break;
				}
			}

			// Prepare the response format like OpenAI API
			$prompt_tokens = ! empty( $response['usage']['input_tokens'] ) ? $response['usage']['input_tokens'] : 0;
			if ( $prompt_tokens == 0 ) {
				if ( ! empty( $args['prompt'] ) ) {
					$prompt_tokens = $this->count_tokens( $args['prompt'] );
				} else if ( ! empty( $args['messages'] ) && is_array( $args['messages'] ) && count( $args['messages'] ) > 0 ) {
					$message = array_pop( $args['messages'] );
					if ( ! empty( $message['content'] ) ) {
						if ( is_array( $message['content'] ) ) {
							$prompt_tokens = 0;
							foreach ( $message['content'] as $v ) {
								if ( ! empty( $v['text'] ) ) {
									$prompt_tokens += $this->count_tokens( $v['text'] );
								}
							}
						} else {
							$prompt_tokens = $this->count_tokens( $message['content'] );
						}
					}
				}
			}
			$completion_tokens = ! empty( $response['usage']['output_tokens'] ) ? $response['usage']['output_tokens'] : ( ! empty( $answer ) ? $this->count_tokens( $answer ) : 0 );
			$total_tokens      = ! empty( $response['usage']['total_tokens'] )  ? $response['usage']['total_tokens']  : $prompt_tokens + $completion_tokens;

			$response = array(
				'response_id' => ! empty( $response['id'] ) ? $response['id'] : '',
				'finish_reason' => 'stop',
				'model' => ! empty( $args['model'] )
							? $args['model']
							: ( ! empty( $response['model'] )
								? $response['model']
								: __( 'OpenAI Responses', 'trx_addons' )
								),
				'usage' => array(
							'prompt_tokens' => $prompt_tokens,
							'completion_tokens' => $completion_tokens,
							'total_tokens' => $total_tokens,
							),
				'choices' => array(
								array(
									'message' => array(
										'role' => 'assistant',
										'content' => $answer
									)
								)
							)
			);
		}

		return $response;
	}


	/**
	 * Generate images via API
	 *
	 * @access public
	 * 
	 * @param array $args  Query arguments
	 * 
	 * @return array  Response from the API
	 */
	public function generate_images( $args = array() ) {
		$args = array_merge( array(
			'token' => $this->get_token(),
			'prompt' => '',
			'size' => '1024x1024',
			'response_format' => 'url',
			'n' => 1,
		), $args );

		// Save a model name for the log
		$model = str_replace( 'openai/', '', ! empty( $args['model'] ) ? $args['model'] : 'openai/default' );
		$args_orig = $args;

		// Prepare arguments for Open AI API format
		$args = $this->prepare_args( $args );

		$response = false;

		if ( ! empty( $args['token'] ) && ! empty( $args['prompt'] ) ) {

			$api = $this->get_api( $args['token'] );
			unset( $args['token'] );

			$response = $api->image( $args );

			if ( $response ) {
				$this->logger->log( $response, $model, $args_orig, $this->logger_section );
			}
		}

		return $response;

	}


	/**
	 * Make an image variations via API
	 *
	 * @access public
	 * 
	 * @param array $args  Query arguments
	 * 
	 * @return array  Response from the API
	 */
	public function make_variations( $args = array() ) {
		$defaults = array(
			'image' => '',
			'size' => '1024x1024',
			'response_format' => 'url',
			'n' => 1,
		);
		$args = array_merge(
			array(
				'token' => $this->get_token()
			),
			$defaults,
			$args
		);

		$response = false;

		if ( ! empty( $args['token'] ) && ! empty( $args['image'] ) ) {

			$api = $this->get_api( $args['token'] );

			if ( ! empty( $args['image'] ) ) {
				$response = $api->createImageVariation( array_intersect_key( $args, $defaults ) );
				if ( $response ) {
					$this->logger->log( $response, 'images', $args, $this->logger_section );
				}
			}
		}

		return $response;

	}


	/**
	 * Generate audio via API (Text to Speech)
	 *
	 * @access public
	 * 
	 * @param array $args  Query arguments
	 * 
	 * @return array  Response from the API
	 */
	public function generate_audio( $args = array() ) {
		$args = array_merge( array(
			'token' => $this->get_token(),
			'prompt' => '',
			'output' => 'mp3'
		), $args );

		// Save a model name for the log
		$model = str_replace( 'openai/', '', ! empty( $args['model'] ) ? $args['model'] : 'openai/default' );
		$args_orig = $args;

		// Prepare arguments for Open AI API format
		$args = $this->prepare_args( $args, 'audio' );

		$response = array(
			'status' => 'error',
			'message' => esc_html__( 'The audio generation is failed. Try again later.', 'trx_addons' )
		);

		if ( ! empty( $args['token'] ) && ! empty( $args['input'] ) ) {

			$api = $this->get_api( $args['token'] );
			unset( $args['token'] );
			$output = $api->speech( $args );

			if ( $output ) {
				// Save the response to the cache
				$output_url = trx_addons_uploads_save_data( $output, array(
					'expire' => apply_filters( 'trx_addons_filter_ai_helper_generated_audio_expire_time', 10 * 60 ),
					'ext' => $args_orig['output'],
				) );

				$response = array(
					'status' => 'success',
					'output' => array(
						$output_url
					),
					'message' => ''
				);
				$this->logger->log( $response, $model, $args_orig, $this->logger_section . '/audio' );
			}
		}

		return $response;

	}


	/**
	 * Transcription audio via API (Speech to Text)
	 *
	 * @access public
	 * 
	 * @param array $args  Query arguments
	 * 
	 * @return array  Response from the API
	 */
	public function transcription( $args = array() ) {
		$args = array_merge( array(
			'token' => $this->get_token(),
			'output' => 'json'
		), $args );

		// Save a model name for the log
		$model = str_replace( 'openai/', '', ! empty( $args['model'] ) ? $args['model'] : 'openai/default' );
		$args_orig = $args;

		// Prepare arguments for Open AI API format
		$args = $this->prepare_args( $args, 'audio' );

		$response = array(
			'status' => 'error',
			'message' => esc_html__( 'The audio transcription is failed. Try again later.', 'trx_addons' )
		);

		if ( ! empty( $args['token'] ) && ! empty( $args['file'] ) ) {

			$api = $this->get_api( $args['token'] );
			unset( $args['token'] );

			$output = $api->transcription( $args );

			if ( ! empty( $output['text'] ) ) {
				$response = array(
					'status' => 'success',
					'text' => $output['text'],
					'message' => ''
				);
				$this->logger->log( $response, $model, $args_orig, $this->logger_section . '/audio' );
			} else if ( ! empty( $output['error'] ) ) {
				$response['message'] = ! empty( $output['error']['message'] )
										? $output['error']['message']
										: esc_html__( 'The audio transcription is failed. The required field is missing.', 'trx_addons' );
			}
		}

		return $response;

	}


	/**
	 * Translation audio via API (Speech to Text + Translate to English)
	 *
	 * @access public
	 * 
	 * @param array $args  Query arguments
	 * 
	 * @return array  Response from the API
	 */
	public function translation( $args = array() ) {
		$args = array_merge( array(
			'token' => $this->get_token(),
			'output' => 'json'
		), $args );

		// Save a model name for the log
		$model = str_replace( 'openai/', '', ! empty( $args['model'] ) ? $args['model'] : 'openai/default' );
		$args_orig = $args;

		// Prepare arguments for Open AI API format
		$args = $this->prepare_args( $args, 'audio' );

		$response = array(
			'status' => 'error',
			'message' => esc_html__( 'The audio translation is failed. Try again later.', 'trx_addons' )
		);

		if ( ! empty( $args['token'] ) && ! empty( $args['file'] ) ) {

			$api = $this->get_api( $args['token'] );
			unset( $args['token'] );

			$output = $api->translate( $args );

			if ( ! empty( $output['text'] ) ) {
				$response = array(
					'status' => 'success',
					'text' => $output['text'],
					'message' => ''
				);
				$this->logger->log( $response, $model, $args_orig, $this->logger_section . '/audio' );
			} else if ( ! empty( $output['error'] ) ) {
				$response['message'] = ! empty( $output['error']['message'] )
										? $output['error']['message']
										: esc_html__( 'The audio translation is failed. The required field is missing.', 'trx_addons' );
			}
		}

		return $response;

	}

}
