<?php
declare(strict_types=1);

namespace Apollo\Modules\Registration;

use Apollo\Helpers\CPFValidator;
use Apollo\Modules\Registration\RegistrationRoutes;
use Apollo\Modules\Registration\CulturaRioIdentity;

/**
 * Registration Service Provider
 * Handles strict registration with CPF, SOUNDS, QUIZZ, and Cultura::Rio identity
 *
 * @package Apollo_Social
 * @since 1.0.0
 */
class RegistrationServiceProvider {

	public function register(): void {
		// Register routes
		$routes = new RegistrationRoutes();
		$routes->register();

		// Add custom fields to registration form
		add_action( 'register_form', array( $this, 'addRegistrationFields' ) );

		// Validate registration fields
		add_action( 'registration_errors', array( $this, 'validateRegistrationFields' ), 10, 3 );

		// Save custom fields after registration
		add_action( 'user_register', array( $this, 'saveRegistrationFields' ), 10, 1 );

		// Add registration form script
		add_action( 'login_enqueue_scripts', array( $this, 'enqueueRegistrationAssets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueRegistrationAssets' ) );
	}

	/**
	 * Add custom fields to registration form
	 */
	public function addRegistrationFields(): void {
		// Load ShadCN/Tailwind if available
		if ( function_exists( 'apollo_shadcn_init' ) ) {
			apollo_shadcn_init();
		}

		?>
		<div class="apollo-registration-fields" style="margin-top: 20px;">
			
			<!-- Document Type Selector -->
			<p>
				<label for="apollo_doc_type">
					Tipo de Documento <span class="required">*</span>
				</label>
				<select 
					name="apollo_doc_type" 
					id="apollo_doc_type" 
					class="input" 
					required
				>
					<option value="cpf" <?php selected( isset( $_POST['apollo_doc_type'] ) ? $_POST['apollo_doc_type'] : 'cpf', 'cpf' ); ?>>CPF (Brasileiro)</option>
					<option value="passport" <?php selected( isset( $_POST['apollo_doc_type'] ) ? $_POST['apollo_doc_type'] : '', 'passport' ); ?>>Passaporte (Estrangeiro)</option>
				</select>
				<span class="description">Selecione o tipo de documento de identificação</span>
			</p>
			
			<!-- CPF Field (Mandatory for Brazilians) -->
			<p id="cpf-field-wrapper">
				<label for="apollo_cpf">
					CPF <span class="required">*</span>
				</label>
				<input 
					type="text" 
					name="apollo_cpf" 
					id="apollo_cpf" 
					class="input apollo-cpf-input" 
					value="<?php echo isset( $_POST['apollo_cpf'] ) ? esc_attr( $_POST['apollo_cpf'] ) : ''; ?>" 
					placeholder="000.000.000-00"
					maxlength="14"
					autocomplete="off"
				/>
				<span class="description">Digite seu CPF completo</span>
			</p>
			
			<!-- Passport Field (For foreigners) -->
			<p id="passport-field-wrapper" style="display: none;">
				<label for="apollo_passport">
					Número do Passaporte <span class="required">*</span>
				</label>
				<input 
					type="text" 
					name="apollo_passport" 
					id="apollo_passport" 
					class="input apollo-passport-input" 
					value="<?php echo isset( $_POST['apollo_passport'] ) ? esc_attr( $_POST['apollo_passport'] ) : ''; ?>" 
					placeholder="Ex: AB123456"
					maxlength="20"
					autocomplete="off"
				/>
				<span class="description">Digite o número do seu passaporte</span>
				<span class="description" style="color: #f97316; font-weight: 600; display: block; margin-top: 8px;">
					⚠️ Atenção: Usuários com passaporte NÃO poderão assinar documentos digitais. 
					Assinatura digital requer CPF válido (lei brasileira).
				</span>
			</p>
			
			<!-- Passport Country -->
			<p id="passport-country-wrapper" style="display: none;">
				<label for="apollo_passport_country">
					País de Emissão <span class="required">*</span>
				</label>
				<input 
					type="text" 
					name="apollo_passport_country" 
					id="apollo_passport_country" 
					class="input" 
					value="<?php echo isset( $_POST['apollo_passport_country'] ) ? esc_attr( $_POST['apollo_passport_country'] ) : ''; ?>" 
					placeholder="Ex: Estados Unidos"
					maxlength="100"
					autocomplete="country-name"
				/>
				<span class="description">País que emitiu seu passaporte</span>
			</p>
			
			<!-- SOUNDS Field (Mandatory) -->
			<p>
				<label for="apollo_sounds">
					Gêneros Musicais <span class="required">*</span>
				</label>
				<select 
					name="apollo_sounds[]" 
					id="apollo_sounds" 
					class="input apollo-sounds-select" 
					multiple
					required
					style="min-height: 120px;"
				>
					<?php
					$sounds = $this->getAvailableSounds();
					foreach ( $sounds as $sound_slug => $sound_name ) {
						$selected = isset( $_POST['apollo_sounds'] ) && in_array( $sound_slug, $_POST['apollo_sounds'] ) ? 'selected' : '';
						echo '<option value="' . esc_attr( $sound_slug ) . '" ' . $selected . '>' . esc_html( $sound_name ) . '</option>';
					}
					?>
				</select>
				<span class="description">Selecione pelo menos um gênero musical (segure Ctrl/Cmd para múltipla seleção)</span>
			</p>
			
			<!-- QUIZZ Field (Mandatory) -->
			<div class="apollo-quizz-section" style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
				<h3 style="margin-top: 0;">Questionário de Registro <span class="required">*</span></h3>
				
				<?php
				$quizz_questions = $this->getQuizQuestions();
				foreach ( $quizz_questions as $index => $question ) {
					?>
					<p>
						<label for="apollo_quizz_<?php echo $index; ?>">
							<?php echo esc_html( $question['question'] ); ?> <span class="required">*</span>
						</label>
						<?php if ( $question['type'] === 'select' ) : ?>
							<select 
								name="apollo_quizz[<?php echo $index; ?>]" 
								id="apollo_quizz_<?php echo $index; ?>" 
								class="input" 
								required
							>
								<option value="">Selecione uma opção</option>
								<?php foreach ( $question['options'] as $option_value => $option_label ) : ?>
									<option value="<?php echo esc_attr( $option_value ); ?>">
										<?php echo esc_html( $option_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						<?php elseif ( $question['type'] === 'textarea' ) : ?>
							<textarea 
								name="apollo_quizz[<?php echo $index; ?>]" 
								id="apollo_quizz_<?php echo $index; ?>" 
								class="input" 
								rows="3"
								required
								placeholder="<?php echo esc_attr( $question['placeholder'] ?? '' ); ?>"
							></textarea>
						<?php else : ?>
							<input 
								type="<?php echo esc_attr( $question['type'] ); ?>" 
								name="apollo_quizz[<?php echo $index; ?>]" 
								id="apollo_quizz_<?php echo $index; ?>" 
								class="input" 
								required
								placeholder="<?php echo esc_attr( $question['placeholder'] ?? '' ); ?>"
							/>
						<?php endif; ?>
					</p>
					<?php
				}//end foreach
				?>
			</div>
			
			<!-- CULTURA::RIO Identity Section (MANDATORY) -->
			<div class="apollo-cultura-identity-section" style="margin-top: 25px; padding: 20px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 8px; color: #fff;">
				<h3 style="margin-top: 0; color: #00d4ff; font-size: 1.3em;">
					Na Cultura::rio eu me identifico como.. <span class="required" style="color: #ff6b6b;">*</span>
				</h3>
				<p style="color: #e0e0e0; font-size: 0.95em; margin-bottom: 20px; line-height: 1.6;">
					Nesse universo Cultural Digital que você entra agora como parte, de BETA LAB testes APOLLO::RIO, quais são as facilidades e funcionalidades que você quer ter acesso?
				</p>
				
				<div class="apollo-identity-options" style="display: flex; flex-direction: column; gap: 10px;">
					<?php
					$identities          = CulturaRioIdentity::getIdentities();
					$selected_identities = isset( $_POST['apollo_cultura_identity'] ) ? (array) $_POST['apollo_cultura_identity'] : array( 'clubber' );

					foreach ( $identities as $key => $identity ) :
						$is_locked   = $identity['locked'] ?? false;
						$is_checked  = in_array( $key, $selected_identities, true ) || $is_locked;
						$disabled    = $is_locked ? 'disabled' : '';
						$label_style = $is_locked ? 'font-weight: 600; color: #00d4ff;' : 'color: #e0e0e0;';
						?>
						<label style="display: flex; align-items: flex-start; gap: 10px; padding: 8px; background: rgba(255,255,255,0.05); border-radius: 4px; cursor: <?php echo $is_locked ? 'not-allowed' : 'pointer'; ?>;">
							<input 
								type="checkbox" 
								name="apollo_cultura_identity[]" 
								value="<?php echo esc_attr( $key ); ?>"
								<?php checked( $is_checked ); ?>
								<?php echo $disabled; ?>
								style="margin-top: 3px; accent-color: #00d4ff;"
							/>
							<?php if ( $is_locked ) : ?>
								<!-- Hidden input to ensure locked value is submitted -->
								<input type="hidden" name="apollo_cultura_identity[]" value="<?php echo esc_attr( $key ); ?>" />
							<?php endif; ?>
							<span style="<?php echo $label_style; ?>">
								<strong><?php echo esc_html( $identity['code'] ); ?>.</strong>
								<?php echo esc_html( $identity['label'] ); ?>
								<?php if ( $is_locked ) : ?>
									<span style="color: #ff6b6b; font-size: 0.8em;"> [sempre ativo]</span>
								<?php endif; ?>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
				
				<!-- Remarks Section -->
				<div class="apollo-cultura-remarks" style="margin-top: 20px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 6px; font-size: 0.85em; color: #a0a0a0; line-height: 1.6;">
					<p style="margin: 0 0 10px 0;">
						<span class="culturario-desired_remark" style="color: #00d4ff; font-weight: 600;">Business Person</span>: 
						Vendo Produto / Serviço para eventos e artistas cariocas, de equipamento de sistema de som, luz; materiao gráfico / filmagens; gravadora / stúdio de música; cursos de DJ; bar consignado; entre diversos outros.
					</p>
					<p style="margin: 0;">
						<span class="culturario-desired_remark" style="color: #00d4ff; font-weight: 600;">Visual Artist</span>: 
						Designer; Photographer; Artistas Plásticos; Video Motion; entre diversos outros.
					</p>
				</div>
				
				<p style="margin-top: 15px; font-size: 0.85em; color: #888; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
					<strong style="color: #ff6b6b;">Importante:</strong> 
					Seleções além de "Clubber" requerem aprovação de administrador para acesso às funcionalidades específicas.
					Você receberá notificação quando sua solicitação for analisada.
				</p>
			</div>
			
		</div>
		
		<script>
		// Document Type Toggle + CPF Mask
		document.addEventListener('DOMContentLoaded', function() {
			const docTypeSelect = document.getElementById('apollo_doc_type');
			const cpfWrapper = document.getElementById('cpf-field-wrapper');
			const cpfInput = document.getElementById('apollo_cpf');
			const passportWrapper = document.getElementById('passport-field-wrapper');
			const passportInput = document.getElementById('apollo_passport');
			const passportCountryWrapper = document.getElementById('passport-country-wrapper');
			const passportCountryInput = document.getElementById('apollo_passport_country');
			
			// Toggle fields based on document type
			function toggleDocFields() {
				const docType = docTypeSelect.value;
				
				if (docType === 'cpf') {
					cpfWrapper.style.display = 'block';
					cpfInput.required = true;
					passportWrapper.style.display = 'none';
					passportInput.required = false;
					passportCountryWrapper.style.display = 'none';
					passportCountryInput.required = false;
				} else {
					cpfWrapper.style.display = 'none';
					cpfInput.required = false;
					passportWrapper.style.display = 'block';
					passportInput.required = true;
					passportCountryWrapper.style.display = 'block';
					passportCountryInput.required = true;
				}
			}
			
			if (docTypeSelect) {
				docTypeSelect.addEventListener('change', toggleDocFields);
				// Initialize on load
				toggleDocFields();
			}
			
			// CPF Mask
			if (cpfInput) {
				cpfInput.addEventListener('input', function(e) {
					let value = e.target.value.replace(/\D/g, '');
					if (value.length <= 11) {
						value = value.replace(/(\d{3})(\d)/, '$1.$2');
						value = value.replace(/(\d{3})(\d)/, '$1.$2');
						value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
						e.target.value = value;
					}
				});
			}
			
			// Passport uppercase
			if (passportInput) {
				passportInput.addEventListener('input', function(e) {
					e.target.value = e.target.value.toUpperCase();
				});
			}
		});
		</script>
		<?php
	}

	/**
	 * Validate registration fields
	 */
	public function validateRegistrationFields( \WP_Error $errors, string $sanitized_user_login, string $user_email ): \WP_Error {
		// Get document type
		$doc_type = isset( $_POST['apollo_doc_type'] ) ? sanitize_text_field( $_POST['apollo_doc_type'] ) : 'cpf';

		if ( $doc_type === 'cpf' ) {
			// Validate CPF
			if ( empty( $_POST['apollo_cpf'] ) ) {
				$errors->add( 'apollo_cpf_empty', '<strong>Erro:</strong> CPF é obrigatório.' );
			} else {
				$cpf = CPFValidator::sanitize( $_POST['apollo_cpf'] );
				if ( ! CPFValidator::validate( $cpf ) ) {
					$errors->add( 'apollo_cpf_invalid', '<strong>Erro:</strong> CPF inválido. Verifique os dígitos.' );
				} else {
					// Check if CPF already exists
					$existing_user = get_users(
						array(
							'meta_key'   => 'apollo_cpf',
							'meta_value' => $cpf,
							'number'     => 1,
						)
					);
					if ( ! empty( $existing_user ) ) {
						$errors->add( 'apollo_cpf_exists', '<strong>Erro:</strong> Este CPF já está cadastrado.' );
					}
				}
			}
		} elseif ( $doc_type === 'passport' ) {
			// Validate Passport
			if ( empty( $_POST['apollo_passport'] ) ) {
				$errors->add( 'apollo_passport_empty', '<strong>Erro:</strong> Número do passaporte é obrigatório.' );
			} else {
				$passport = sanitize_text_field( $_POST['apollo_passport'] );
				if ( strlen( $passport ) < 5 || strlen( $passport ) > 20 ) {
					$errors->add( 'apollo_passport_invalid', '<strong>Erro:</strong> Número do passaporte inválido (5-20 caracteres).' );
				} else {
					// Check if passport already exists
					$existing_user = get_users(
						array(
							'meta_key'   => 'apollo_passport',
							'meta_value' => $passport,
							'number'     => 1,
						)
					);
					if ( ! empty( $existing_user ) ) {
						$errors->add( 'apollo_passport_exists', '<strong>Erro:</strong> Este passaporte já está cadastrado.' );
					}
				}
			}

			// Validate Passport Country
			if ( empty( $_POST['apollo_passport_country'] ) ) {
				$errors->add( 'apollo_passport_country_empty', '<strong>Erro:</strong> País de emissão é obrigatório.' );
			}
		} else {
			$errors->add( 'apollo_doc_type_invalid', '<strong>Erro:</strong> Tipo de documento inválido.' );
		}//end if

		// Validate SOUNDS
		if ( empty( $_POST['apollo_sounds'] ) || ! is_array( $_POST['apollo_sounds'] ) ) {
			$errors->add( 'apollo_sounds_empty', '<strong>Erro:</strong> Selecione pelo menos um gênero musical.' );
		} else {
			$sounds       = array_map( 'sanitize_text_field', $_POST['apollo_sounds'] );
			$valid_sounds = array_keys( $this->getAvailableSounds() );
			foreach ( $sounds as $sound ) {
				if ( ! in_array( $sound, $valid_sounds ) ) {
					$errors->add( 'apollo_sounds_invalid', '<strong>Erro:</strong> Gênero musical inválido selecionado.' );
					break;
				}
			}
		}

		// Validate QUIZZ
		if ( empty( $_POST['apollo_quizz'] ) || ! is_array( $_POST['apollo_quizz'] ) ) {
			$errors->add( 'apollo_quizz_empty', '<strong>Erro:</strong> Responda todas as perguntas do questionário.' );
		} else {
			$quizz_questions = $this->getQuizQuestions();
			foreach ( $quizz_questions as $index => $question ) {
				if ( empty( $_POST['apollo_quizz'][ $index ] ) ) {
					$errors->add( 'apollo_quizz_incomplete', '<strong>Erro:</strong> Responda todas as perguntas do questionário.' );
					break;
				}
			}
		}

		// Validate Cultura::Rio Identity (must include at least clubber)
		if ( empty( $_POST['apollo_cultura_identity'] ) || ! is_array( $_POST['apollo_cultura_identity'] ) ) {
			$errors->add( 'apollo_cultura_identity_empty', '<strong>Erro:</strong> Selecione sua identificação na Cultura::Rio.' );
		} else {
			$identities = array_map( 'sanitize_key', $_POST['apollo_cultura_identity'] );
			$valid_keys = array_keys( CulturaRioIdentity::getIdentities() );

			// Ensure clubber is always included
			if ( ! in_array( 'clubber', $identities, true ) ) {
				$identities[] = 'clubber';
			}

			// Validate all selections
			foreach ( $identities as $identity ) {
				if ( ! in_array( $identity, $valid_keys, true ) ) {
					$errors->add( 'apollo_cultura_identity_invalid', '<strong>Erro:</strong> Identificação cultural inválida selecionada.' );
					break;
				}
			}
		}

		return $errors;
	}

	/**
	 * Save registration fields after user is created
	 */
	public function saveRegistrationFields( int $user_id ): void {
		// Get document type
		$doc_type = isset( $_POST['apollo_doc_type'] ) ? sanitize_text_field( $_POST['apollo_doc_type'] ) : 'cpf';
		update_user_meta( $user_id, 'apollo_doc_type', $doc_type );

		if ( $doc_type === 'cpf' ) {
			// Save CPF
			if ( ! empty( $_POST['apollo_cpf'] ) ) {
				$cpf = CPFValidator::sanitize( $_POST['apollo_cpf'] );
				update_user_meta( $user_id, 'apollo_cpf', $cpf );
				update_user_meta( $user_id, 'apollo_cpf_formatted', CPFValidator::format( $cpf ) );
				// Clear passport fields if any
				delete_user_meta( $user_id, 'apollo_passport' );
				delete_user_meta( $user_id, 'apollo_passport_country' );
			}
			// Mark as eligible for document signing
			update_user_meta( $user_id, 'apollo_can_sign_documents', true );
		} elseif ( $doc_type === 'passport' ) {
			// Save Passport
			if ( ! empty( $_POST['apollo_passport'] ) ) {
				$passport = strtoupper( sanitize_text_field( $_POST['apollo_passport'] ) );
				update_user_meta( $user_id, 'apollo_passport', $passport );
				update_user_meta( $user_id, 'apollo_passport_country', sanitize_text_field( $_POST['apollo_passport_country'] ) );
				// Clear CPF fields if any
				delete_user_meta( $user_id, 'apollo_cpf' );
				delete_user_meta( $user_id, 'apollo_cpf_formatted' );
			}
			// Mark as NOT eligible for document signing (passport users can't sign)
			update_user_meta( $user_id, 'apollo_can_sign_documents', false );
		}//end if

		// Save SOUNDS
		if ( ! empty( $_POST['apollo_sounds'] ) && is_array( $_POST['apollo_sounds'] ) ) {
			$sounds = array_map( 'sanitize_text_field', $_POST['apollo_sounds'] );
			update_user_meta( $user_id, 'apollo_sounds', $sounds );

			// Also save as taxonomy terms if event_sounds taxonomy exists
			if ( taxonomy_exists( 'event_sounds' ) ) {
				wp_set_object_terms( $user_id, $sounds, 'event_sounds' );
			}
		}

		// Save QUIZZ answers
		if ( ! empty( $_POST['apollo_quizz'] ) && is_array( $_POST['apollo_quizz'] ) ) {
			$quizz_answers = array();
			foreach ( $_POST['apollo_quizz'] as $index => $answer ) {
				$quizz_answers[ $index ] = sanitize_textarea_field( $answer );
			}
			update_user_meta( $user_id, 'apollo_quizz_answers', $quizz_answers );
			update_user_meta( $user_id, 'apollo_quizz_completed', true );
			update_user_meta( $user_id, 'apollo_quizz_completed_date', current_time( 'mysql' ) );
		}

		// Save Cultura::Rio Identity
		if ( ! empty( $_POST['apollo_cultura_identity'] ) && is_array( $_POST['apollo_cultura_identity'] ) ) {
			$identities = array_map( 'sanitize_key', $_POST['apollo_cultura_identity'] );
			CulturaRioIdentity::saveUserIdentity( $user_id, $identities );
		} else {
			// Default to clubber only
			CulturaRioIdentity::saveUserIdentity( $user_id, array( 'clubber' ) );
		}

		// Mark registration as complete
		update_user_meta( $user_id, 'apollo_registration_complete', true );
		update_user_meta( $user_id, 'apollo_registration_date', current_time( 'mysql' ) );

		// Fire action for other integrations
		do_action(
			'apollo_user_registration_complete',
			$user_id,
			array(
				'doc_type'         => $doc_type,
				'sounds'           => $_POST['apollo_sounds'] ?? array(),
				'cultura_identity' => $_POST['apollo_cultura_identity'] ?? array( 'clubber' ),
			)
		);
	}

	/**
	 * Enqueue registration form assets
	 */
	public function enqueueRegistrationAssets(): void {
		// Load uni.css
		wp_enqueue_style(
			'apollo-uni-css',
			'https://assets.apollo.rio.br/uni.css',
			array(),
			'2.0.0'
		);

		// Load ShadCN if available
		if ( function_exists( 'apollo_shadcn_init' ) ) {
			apollo_shadcn_init();
		}
	}

	/**
	 * Get available sounds/genres from Core Bridge
	 *
	 * Uses apollo_get_sounds() from integration-bridge.php which:
	 * - First checks event_sounds taxonomy from Events plugin
	 * - Falls back to default list if taxonomy not available
	 *
	 * @return array Array of [slug => name]
	 */
	private function getAvailableSounds(): array {
		// Use Core Bridge function if available
		if ( function_exists( 'apollo_get_sounds' ) ) {
			return apollo_get_sounds( false );
		}

		// Fallback if Core not loaded yet
		if ( taxonomy_exists( 'event_sounds' ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'event_sounds',
					'hide_empty' => false,
				)
			);

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$sounds = array();
				foreach ( $terms as $term ) {
					$sounds[ $term->slug ] = $term->name;
				}
				return $sounds;
			}
		}

		// Final fallback: Default sounds list
		return array(
			'house'       => 'House',
			'techno'      => 'Techno',
			'trance'      => 'Trance',
			'drum-bass'   => 'Drum & Bass',
			'psytrance'   => 'Psytrance',
			'minimal'     => 'Minimal',
			'progressive' => 'Progressive',
			'tech-house'  => 'Tech House',
			'deep-house'  => 'Deep House',
			'funk'        => 'Funk',
			'disco'       => 'Disco',
			'tribal'      => 'Tribal',
		);
	}

	/**
	 * Get quiz questions for registration
	 *
	 * @return array Array of quiz question definitions
	 */
	private function getQuizQuestions(): array {
		return array(
			array(
				'question' => 'Como você conheceu o Apollo::Rio?',
				'type'     => 'select',
				'options'  => array(
					'redes-sociais' => 'Redes Sociais',
					'amigos'        => 'Indicação de Amigos',
					'evento'        => 'Em um Evento',
					'busca-online'  => 'Busca Online',
					'outro'         => 'Outro',
				),
			),
			array(
				'question' => 'Qual seu principal interesse na plataforma?',
				'type'     => 'select',
				'options'  => array(
					'eventos'  => 'Participar de Eventos',
					'network'  => 'Networking',
					'conteudo' => 'Criar Conteúdo',
					'apoiar'   => 'Apoiar a Cena',
					'outro'    => 'Outro',
				),
			),
			array(
				'question'    => 'Conte-nos um pouco sobre você e sua relação com a música eletrônica:',
				'type'        => 'textarea',
				'placeholder' => 'Descreva sua experiência, interesses e objetivos...',
			),
		);
	}
}

