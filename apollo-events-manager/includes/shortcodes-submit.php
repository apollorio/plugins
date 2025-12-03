<?php
// phpcs:ignoreFile
/**
 * Frontend Event Submission Form
 * Shortcode: [submit_event_form]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function aem_submit_event_shortcode() {
	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		$login_url    = wp_login_url( get_permalink() );
		$register_url = wp_registration_url();
		return '<div class="aem-auth-required glass p-6 rounded-lg text-center">
            <h3 class="text-xl font-semibold mb-4">Login Necessário</h3>
            <p class="mb-4">Você precisa estar logado para enviar um evento.</p>
            <div class="flex gap-4 justify-center">
                <a href="' . esc_url( $login_url ) . '" class="btn btn-primary">Entrar</a>
                <a href="' . esc_url( $register_url ) . '" class="btn btn-secondary">Criar Conta</a>
            </div>
        </div>';
	}

	// Handle form submission
	if ( ! empty( $_POST['aem_submit'] ) && isset( $_POST['apollo_submit_event_nonce'] ) && wp_verify_nonce( $_POST['apollo_submit_event_nonce'], 'apollo_submit_event' ) ) {
		// Auto-generate timetable from selected DJs if timetable not provided
		if ( empty( $_POST['apollo_event_timetable'] ) && ! empty( $_POST['event_djs'] ) && is_array( $_POST['event_djs'] ) ) {
			$auto_timetable = array();
			$dj_ids         = array_filter( array_map( 'absint', $_POST['event_djs'] ) );
			// Bug fix: DateTime precisa de data completa ou usar createFromFormat para apenas hora
			if ( ! empty( $_POST['event_start_time'] ) ) {
				$start_time = sanitize_text_field( $_POST['event_start_time'] );
				// Se start_time já tem data completa, usar diretamente
				if ( strpos( $start_time, ' ' ) !== false ) {
					$start_time_obj = new DateTime( $start_time );
				} else {
					// Se é apenas hora, criar com data de hoje
					$start_time_obj = DateTime::createFromFormat( 'H:i', $start_time );
					if ( ! $start_time_obj ) {
						$start_time_obj = DateTime::createFromFormat( 'H:i:s', $start_time );
					}
					if ( ! $start_time_obj ) {
						// Fallback: usar hora padrão 20:00
						$start_time_obj = DateTime::createFromFormat( 'H:i', '20:00' );
					}
				}
			} else {
				// Hora padrão: 20:00
				$start_time_obj = DateTime::createFromFormat( 'H:i', '20:00' );
			}

			foreach ( $dj_ids as $index => $dj_id ) {
				$slot_time = clone $start_time_obj;
				$slot_time->modify( '+' . ( $index * 60 ) . ' minutes' ); 
				// 1 hour per DJ

				$auto_timetable[] = array(
					'dj'   => $dj_id,
					'from' => $slot_time->format( 'H:i' ),
					'to'   => $slot_time->modify( '+60 minutes' )->format( 'H:i' ),
				);
			}

			if ( ! empty( $auto_timetable ) ) {
				$_POST['apollo_event_timetable'] = wp_json_encode( $auto_timetable );
			}
		}//end if
		$title      = isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : '';
		$content    = isset( $_POST['post_content'] ) ? wp_kses_post( wp_unslash( $_POST['post_content'] ) ) : '';
		$start_date = isset( $_POST['event_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['event_start_date'] ) ) : '';
		$start_time = isset( $_POST['event_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['event_start_time'] ) ) : '';
		$dj_ids     = isset( $_POST['event_djs'] ) && is_array( $_POST['event_djs'] ) ? array_map( 'absint', $_POST['event_djs'] ) : array();
		$local_id   = isset( $_POST['event_local'] ) ? absint( $_POST['event_local'] ) : 0;

		$errors = array();

		// Validation
		if ( empty( $title ) ) {
			$errors[] = 'Título do evento é obrigatório.';
		}

		if ( empty( $start_date ) ) {
			$errors[] = 'Data de início é obrigatória.';
		}

		if ( empty( $local_id ) ) {
			$errors[] = 'Local é obrigatório.';
		}

		if ( ! empty( $errors ) ) {
			$error_html = '<div class="aem-error glass p-4 rounded-lg mb-4 bg-red-50 border border-red-200">
                <h4 class="font-semibold text-red-800 mb-2">Erros encontrados:</h4>
                <ul class="list-disc list-inside text-red-700">';
			foreach ( $errors as $error ) {
				$error_html .= '<li>' . esc_html( $error ) . '</li>';
			}
			$error_html .= '</ul></div>';
		} else {
			try {
				// Combine date and time
				$start_datetime = '';
				if ( ! empty( $start_date ) ) {
					$start_datetime = $start_date;
					if ( ! empty( $start_time ) ) {
						$start_datetime .= ' ' . $start_time . ':00';
					} else {
						$start_datetime .= ' 20:00:00'; 
						// Default time
					}
				}

				// Create event post
				$post_id = wp_insert_post(
					array(
						'post_type'    => 'event_listing',
						'post_status'  => 'pending', 
						// Requires moderation
																'post_title' => $title,
						'post_content' => $content,
						'post_author'  => get_current_user_id(),
					)
				);

				if ( is_wp_error( $post_id ) ) {
					throw new Exception( $post_id->get_error_message() );
				}

				if ( $post_id ) {
					// Save meta using canonical keys and apollo_update_post_meta for sanitization
					if ( ! empty( $start_datetime ) ) {
						if ( function_exists( 'apollo_update_post_meta' ) ) {
							apollo_update_post_meta( $post_id, '_event_start_date', $start_datetime );
						} else {
							update_post_meta( $post_id, '_event_start_date', $start_datetime );
						}

						// Also save time separately
						if ( ! empty( $start_time ) ) {
							if ( function_exists( 'apollo_update_post_meta' ) ) {
								apollo_update_post_meta( $post_id, '_event_start_time', $start_time );
							} else {
								update_post_meta( $post_id, '_event_start_time', $start_time );
							}
						}
					}

					if ( ! empty( $dj_ids ) ) {
						$dj_ids = array_filter( array_map( 'absint', $dj_ids ) );
						if ( function_exists( 'apollo_update_post_meta' ) ) {
							apollo_update_post_meta( $post_id, '_event_dj_ids', $dj_ids );
						} else {
							update_post_meta( $post_id, '_event_dj_ids', $dj_ids );
						}
					}

					if ( $local_id > 0 ) {
						// Use unified connection manager if available
						if ( class_exists( 'Apollo_Local_Connection' ) ) {
							$connection = Apollo_Local_Connection::get_instance();
							$connection->set_local_id( $post_id, $local_id );
						} elseif ( function_exists( 'apollo_update_post_meta' ) ) {
								apollo_update_post_meta( $post_id, '_event_local_ids', $local_id );
						} else {
							update_post_meta( $post_id, '_event_local_ids', $local_id );
						}
					}

					// Save timetable if provided
					if ( isset( $_POST['apollo_event_timetable'] ) && ! empty( $_POST['apollo_event_timetable'] ) ) {
						$timetable_json = sanitize_text_field( wp_unslash( $_POST['apollo_event_timetable'] ) );
						$timetable      = json_decode( stripslashes( $timetable_json ), true );
						if ( is_array( $timetable ) && function_exists( 'apollo_sanitize_timetable' ) ) {
							$clean_timetable = apollo_sanitize_timetable( $timetable );
							if ( ! empty( $clean_timetable ) ) {
								if ( function_exists( 'apollo_update_post_meta' ) ) {
									apollo_update_post_meta( $post_id, '_event_timetable', $clean_timetable );
								} else {
									update_post_meta( $post_id, '_event_timetable', $clean_timetable );
								}
							}
						}
					}

					// Mark as frontend submission
					update_post_meta( $post_id, '_apollo_frontend_submission', '1' );
					update_post_meta( $post_id, '_apollo_submission_date', current_time( 'mysql' ) );

					// Handle banner upload
					if ( ! empty( $_FILES['event_banner']['name'] ) ) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
						require_once ABSPATH . 'wp-admin/includes/image.php';

						$upload = wp_handle_upload( $_FILES['event_banner'], array( 'test_form' => false ) );

						if ( empty( $upload['error'] ) ) {
							$attachment = array(
								'post_mime_type' => $upload['type'],
								'post_title'     => sanitize_file_name( basename( $upload['file'] ) ),
								'post_content'   => '',
								'post_status'    => 'inherit',
								'post_parent'    => $post_id,
							);

							$attach_id   = wp_insert_attachment( $attachment, $upload['file'], $post_id );
							$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
							wp_update_attachment_metadata( $attach_id, $attach_data );
							set_post_thumbnail( $post_id, $attach_id );
						}
					}//end if

					// Clear cache
					if ( function_exists( 'apollo_clear_events_cache' ) ) {
						apollo_clear_events_cache( $post_id );
					}

					return '<div class="aem-success glass p-6 rounded-lg text-center bg-green-50 border border-green-200">
                        <h3 class="text-xl font-semibold text-green-800 mb-2">✓ Evento Enviado com Sucesso!</h3>
                        <p class="text-green-700 mb-4">Obrigado! Seu evento está em revisão e será publicado em breve.</p>
                        <a href="' . esc_url( get_permalink() ) . '" class="btn btn-primary">Enviar Outro Evento</a>
                    </div>';
				} else {
					throw new Exception( 'Erro ao criar evento. ID retornado inválido.' );
				}//end if
			} catch ( Exception $e ) {
				// Log error in debug mode
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Apollo Events: Error in submit form - ' . $e->getMessage() );
				}

				$error_html = '<div class="aem-error glass p-4 rounded-lg mb-4 bg-red-50 border border-red-200">
                    <p class="text-red-700">Erro ao criar evento: ' . esc_html( $e->getMessage() ) . '</p>
                    <p class="text-sm text-red-600 mt-2">Tente novamente ou entre em contato com o suporte.</p>
                </div>';
			}//end try
		}//end if
	}//end if

	// Get DJs and Locals for dropdowns
	$all_djs = get_posts(
		array(
			'post_type'      => 'event_dj',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		)
	);

	$all_locals = get_posts(
		array(
			'post_type'      => 'event_local',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		)
	);

	ob_start();
	?>
	<div class="aem-submit-form-wrapper glass p-6 rounded-lg max-w-3xl mx-auto">
		<h2 class="text-2xl font-bold mb-6">Enviar Novo Evento</h2>
		
		<?php
		if ( isset( $error_html ) ) {
			echo $error_html;}
		?>
		
		<form class="aem-submit-form space-y-6" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'apollo_submit_event', 'apollo_submit_event_nonce' ); ?>
			
			<!-- Event Title -->
			<div>
				<label for="post_title" class="block text-sm font-medium mb-2">
					Título do Evento <span class="text-red-500">*</span>
				</label>
				<input 
					type="text" 
					id="post_title" 
					name="post_title" 
					class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" 
					required
					value="<?php echo isset( $_POST['post_title'] ) ? esc_attr( $_POST['post_title'] ) : ''; ?>"
				>
			</div>
			
			<!-- Description -->
			<div>
				<label for="post_content" class="block text-sm font-medium mb-2">
					Descrição do Evento
				</label>
				<textarea 
					id="post_content" 
					name="post_content" 
					rows="6" 
					class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
					placeholder="Descreva o evento, line-up, informações importantes..."
				><?php echo isset( $_POST['post_content'] ) ? esc_textarea( $_POST['post_content'] ) : ''; ?></textarea>
			</div>
			
			<!-- Date and Time -->
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<div>
					<label for="event_start_date" class="block text-sm font-medium mb-2">
						Data de Início <span class="text-red-500">*</span>
					</label>
					<input 
						type="date" 
						id="event_start_date" 
						name="event_start_date" 
						class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" 
						required
						value="<?php echo isset( $_POST['event_start_date'] ) ? esc_attr( $_POST['event_start_date'] ) : ''; ?>"
					>
				</div>
				<div>
					<label for="event_start_time" class="block text-sm font-medium mb-2">
						Hora de Início
					</label>
					<input 
						type="time" 
						id="event_start_time" 
						name="event_start_time" 
						class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
						value="<?php echo isset( $_POST['event_start_time'] ) ? esc_attr( $_POST['event_start_time'] ) : '20:00'; ?>"
					>
				</div>
			</div>
			
			<!-- DJs Selection -->
			<div>
				<label for="event_djs" class="block text-sm font-medium mb-2">
					DJs (selecione múltiplos)
				</label>
				<select 
					multiple 
					id="event_djs" 
					name="event_djs[]" 
					size="8"
					class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
				>
					<?php
					foreach ( $all_djs as $dj ) {
						$dj_name = get_post_meta( $dj->ID, '_dj_name', true );
						if ( empty( $dj_name ) ) {
							$dj_name = $dj->post_title;
						}
						$selected = isset( $_POST['event_djs'] ) && in_array( $dj->ID, array_map( 'absint', $_POST['event_djs'] ) ) ? 'selected' : '';
						echo '<option value="' . esc_attr( $dj->ID ) . '" ' . $selected . '>' . esc_html( $dj_name ) . '</option>';
					}
					?>
				</select>
				<p class="mt-2 text-sm text-gray-600">
					<i class="ri-information-line"></i> Segure Ctrl (Windows) ou Cmd (Mac) para selecionar múltiplos DJs
				</p>
			</div>
			
			<!-- Local Selection -->
			<div>
				<label for="event_local" class="block text-sm font-medium mb-2">
					Local <span class="text-red-500">*</span>
				</label>
				<select 
					id="event_local" 
					name="event_local" 
					class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" 
					required
				>
					<option value="">Selecione um local</option>
					<?php
					foreach ( $all_locals as $local ) {
						$local_name = get_post_meta( $local->ID, '_local_name', true );
						if ( empty( $local_name ) ) {
							$local_name = $local->post_title;
						}
						$selected = isset( $_POST['event_local'] ) && absint( $_POST['event_local'] ) === $local->ID ? 'selected' : '';
						echo '<option value="' . esc_attr( $local->ID ) . '" ' . $selected . '>' . esc_html( $local_name ) . '</option>';
					}
					?>
				</select>
			</div>
			
			<!-- Timetable (Hidden - populated by JS if needed) -->
			<input 
				type="hidden" 
				id="apollo_event_timetable" 
				name="apollo_event_timetable" 
				value=""
			>
			<p class="text-sm text-gray-600">
				<i class="ri-information-line"></i> A programação (line-up) será criada automaticamente com base nos DJs selecionados.
			</p>
			
			<!-- Banner Upload -->
			<div>
				<label for="event_banner" class="block text-sm font-medium mb-2">
					Banner do Evento (Imagem)
				</label>
				<input 
					type="file" 
					id="event_banner" 
					name="event_banner" 
					accept="image/*"
					class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
				>
				<p class="mt-2 text-sm text-gray-600">
					<i class="ri-information-line"></i> Formatos aceitos: JPG, PNG, GIF. Tamanho recomendado: 1200x600px
				</p>
			</div>
			
			<!-- Submit Button -->
			<div class="flex justify-end gap-4">
				<button 
					type="submit" 
					name="aem_submit" 
					value="1" 
					class="btn btn-primary px-6 py-3"
				>
					<i class="ri-send-plane-line"></i> Enviar Evento para Revisão
				</button>
			</div>
	</form>
	</div>
	<?php
	return ob_get_clean();
}

// FASE 2: Shortcode legado mantido para backward compatibility
// O shortcode oficial agora é [apollo_event_submit] registrado na classe principal
// NOTE: This shortcode may be registered by Apollo_Events_Shortcodes class
// Check before registering to avoid conflicts
if ( ! shortcode_exists( 'submit_event_form' ) ) {
	add_shortcode( 'submit_event_form', 'aem_submit_event_shortcode' );
}
