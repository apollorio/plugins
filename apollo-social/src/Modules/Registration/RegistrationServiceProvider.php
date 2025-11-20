<?php

namespace Apollo\Modules\Registration;

use Apollo\Helpers\CPFValidator;
use Apollo\Modules\Registration\RegistrationRoutes;

/**
 * Registration Service Provider
 * Handles strict registration with CPF, SOUNDS, and QUIZZ requirements
 */
class RegistrationServiceProvider
{
    public function register(): void
    {
        // Register routes
        $routes = new RegistrationRoutes();
        $routes->register();
        
        // Add custom fields to registration form
        add_action('register_form', [$this, 'addRegistrationFields']);
        
        // Validate registration fields
        add_action('registration_errors', [$this, 'validateRegistrationFields'], 10, 3);
        
        // Save custom fields after registration
        add_action('user_register', [$this, 'saveRegistrationFields'], 10, 1);
        
        // Add registration form script
        add_action('login_enqueue_scripts', [$this, 'enqueueRegistrationAssets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueRegistrationAssets']);
    }
    
    /**
     * Add custom fields to registration form
     */
    public function addRegistrationFields(): void
    {
        // Load ShadCN/Tailwind if available
        if (function_exists('apollo_shadcn_init')) {
            apollo_shadcn_init();
        }
        
        ?>
        <div class="apollo-registration-fields" style="margin-top: 20px;">
            
            <!-- CPF Field (Mandatory) -->
            <p>
                <label for="apollo_cpf">
                    CPF <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    name="apollo_cpf" 
                    id="apollo_cpf" 
                    class="input apollo-cpf-input" 
                    value="<?php echo isset($_POST['apollo_cpf']) ? esc_attr($_POST['apollo_cpf']) : ''; ?>" 
                    placeholder="000.000.000-00"
                    maxlength="14"
                    required
                    autocomplete="off"
                />
                <span class="description">Digite seu CPF completo</span>
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
                    foreach ($sounds as $sound_slug => $sound_name) {
                        $selected = isset($_POST['apollo_sounds']) && in_array($sound_slug, $_POST['apollo_sounds']) ? 'selected' : '';
                        echo '<option value="' . esc_attr($sound_slug) . '" ' . $selected . '>' . esc_html($sound_name) . '</option>';
                    }
                    ?>
                </select>
                <span class="description">Selecione pelo menos um gênero musical (segure Ctrl/Cmd para múltipla seleção)</span>
            </p>
            
            <!-- QUIZZ Field (Mandatory) -->
            <div class="apollo-quizz-section" style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
                <h3 style="margin-top: 0;">Questionário de Registro <span class="required">*</span></h3>
                
                <?php
                $quizz_questions = $this->getQuizzQuestions();
                foreach ($quizz_questions as $index => $question) {
                    ?>
                    <p>
                        <label for="apollo_quizz_<?php echo $index; ?>">
                            <?php echo esc_html($question['question']); ?> <span class="required">*</span>
                        </label>
                        <?php if ($question['type'] === 'select'): ?>
                            <select 
                                name="apollo_quizz[<?php echo $index; ?>]" 
                                id="apollo_quizz_<?php echo $index; ?>" 
                                class="input" 
                                required
                            >
                                <option value="">Selecione uma opção</option>
                                <?php foreach ($question['options'] as $option_value => $option_label): ?>
                                    <option value="<?php echo esc_attr($option_value); ?>">
                                        <?php echo esc_html($option_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($question['type'] === 'textarea'): ?>
                            <textarea 
                                name="apollo_quizz[<?php echo $index; ?>]" 
                                id="apollo_quizz_<?php echo $index; ?>" 
                                class="input" 
                                rows="3"
                                required
                                placeholder="<?php echo esc_attr($question['placeholder'] ?? ''); ?>"
                            ></textarea>
                        <?php else: ?>
                            <input 
                                type="<?php echo esc_attr($question['type']); ?>" 
                                name="apollo_quizz[<?php echo $index; ?>]" 
                                id="apollo_quizz_<?php echo $index; ?>" 
                                class="input" 
                                required
                                placeholder="<?php echo esc_attr($question['placeholder'] ?? ''); ?>"
                            />
                        <?php endif; ?>
                    </p>
                    <?php
                }
                ?>
            </div>
            
        </div>
        
        <script>
        // CPF Mask
        document.addEventListener('DOMContentLoaded', function() {
            const cpfInput = document.getElementById('apollo_cpf');
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
        });
        </script>
        <?php
    }
    
    /**
     * Validate registration fields
     */
    public function validateRegistrationFields(\WP_Error $errors, string $sanitized_user_login, string $user_email): \WP_Error
    {
        // Validate CPF
        if (empty($_POST['apollo_cpf'])) {
            $errors->add('apollo_cpf_empty', '<strong>Erro:</strong> CPF é obrigatório.');
        } else {
            $cpf = CPFValidator::sanitize($_POST['apollo_cpf']);
            if (!CPFValidator::validate($cpf)) {
                $errors->add('apollo_cpf_invalid', '<strong>Erro:</strong> CPF inválido. Verifique os dígitos.');
            } else {
                // Check if CPF already exists
                $existing_user = get_users([
                    'meta_key' => 'apollo_cpf',
                    'meta_value' => $cpf,
                    'number' => 1
                ]);
                if (!empty($existing_user)) {
                    $errors->add('apollo_cpf_exists', '<strong>Erro:</strong> Este CPF já está cadastrado.');
                }
            }
        }
        
        // Validate SOUNDS
        if (empty($_POST['apollo_sounds']) || !is_array($_POST['apollo_sounds'])) {
            $errors->add('apollo_sounds_empty', '<strong>Erro:</strong> Selecione pelo menos um gênero musical.');
        } else {
            $sounds = array_map('sanitize_text_field', $_POST['apollo_sounds']);
            $valid_sounds = array_keys($this->getAvailableSounds());
            foreach ($sounds as $sound) {
                if (!in_array($sound, $valid_sounds)) {
                    $errors->add('apollo_sounds_invalid', '<strong>Erro:</strong> Gênero musical inválido selecionado.');
                    break;
                }
            }
        }
        
        // Validate QUIZZ
        if (empty($_POST['apollo_quizz']) || !is_array($_POST['apollo_quizz'])) {
            $errors->add('apollo_quizz_empty', '<strong>Erro:</strong> Responda todas as perguntas do questionário.');
        } else {
            $quizz_questions = $this->getQuizzQuestions();
            foreach ($quizz_questions as $index => $question) {
                if (empty($_POST['apollo_quizz'][$index])) {
                    $errors->add('apollo_quizz_incomplete', '<strong>Erro:</strong> Responda todas as perguntas do questionário.');
                    break;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Save registration fields after user is created
     */
    public function saveRegistrationFields(int $user_id): void
    {
        // Save CPF
        if (!empty($_POST['apollo_cpf'])) {
            $cpf = CPFValidator::sanitize($_POST['apollo_cpf']);
            update_user_meta($user_id, 'apollo_cpf', $cpf);
            update_user_meta($user_id, 'apollo_cpf_formatted', CPFValidator::format($cpf));
        }
        
        // Save SOUNDS
        if (!empty($_POST['apollo_sounds']) && is_array($_POST['apollo_sounds'])) {
            $sounds = array_map('sanitize_text_field', $_POST['apollo_sounds']);
            update_user_meta($user_id, 'apollo_sounds', $sounds);
            
            // Also save as taxonomy terms if event_sounds taxonomy exists
            if (taxonomy_exists('event_sounds')) {
                wp_set_object_terms($user_id, $sounds, 'event_sounds');
            }
        }
        
        // Save QUIZZ answers
        if (!empty($_POST['apollo_quizz']) && is_array($_POST['apollo_quizz'])) {
            $quizz_answers = [];
            foreach ($_POST['apollo_quizz'] as $index => $answer) {
                $quizz_answers[$index] = sanitize_textarea_field($answer);
            }
            update_user_meta($user_id, 'apollo_quizz_answers', $quizz_answers);
            update_user_meta($user_id, 'apollo_quizz_completed', true);
            update_user_meta($user_id, 'apollo_quizz_completed_date', current_time('mysql'));
        }
        
        // Mark registration as complete
        update_user_meta($user_id, 'apollo_registration_complete', true);
        update_user_meta($user_id, 'apollo_registration_date', current_time('mysql'));
    }
    
    /**
     * Enqueue registration form assets
     */
    public function enqueueRegistrationAssets(): void
    {
        // Load uni.css
        wp_enqueue_style(
            'apollo-uni-css',
            'https://assets.apollo.rio.br/uni.css',
            [],
            '2.0.0'
        );
        
        // Load ShadCN if available
        if (function_exists('apollo_shadcn_init')) {
            apollo_shadcn_init();
        }
    }
    
    /**
     * Get available sounds/genres
     */
    private function getAvailableSounds(): array
    {
        // Try to get from event_sounds taxonomy
        if (taxonomy_exists('event_sounds')) {
            $terms = get_terms([
                'taxonomy' => 'event_sounds',
                'hide_empty' => false,
            ]);
            
            if (!is_wp_error($terms) && !empty($terms)) {
                $sounds = [];
                foreach ($terms as $term) {
                    $sounds[$term->slug] = $term->name;
                }
                return $sounds;
            }
        }
        
        // Fallback: Default sounds list
        return [
            'house' => 'House',
            'techno' => 'Techno',
            'trance' => 'Trance',
            'dubstep' => 'Dubstep',
            'drum-and-bass' => 'Drum & Bass',
            'deep-house' => 'Deep House',
            'progressive-house' => 'Progressive House',
            'tech-house' => 'Tech House',
            'minimal' => 'Minimal',
            'hardstyle' => 'Hardstyle',
            'hardcore' => 'Hardcore',
            'psytrance' => 'Psytrance',
            'hip-hop' => 'Hip Hop',
            'r&b' => 'R&B',
            'pop' => 'Pop',
            'rock' => 'Rock',
            'reggae' => 'Reggae',
            'samba' => 'Samba',
            'funk' => 'Funk',
            'sertanejo' => 'Sertanejo',
        ];
    }
    
    /**
     * Get quizz questions
     */
    private function getQuizzQuestions(): array
    {
        return [
            [
                'question' => 'Como você conheceu o Apollo::Rio?',
                'type' => 'select',
                'options' => [
                    'redes-sociais' => 'Redes Sociais',
                    'amigos' => 'Indicação de Amigos',
                    'evento' => 'Em um Evento',
                    'busca-online' => 'Busca Online',
                    'outro' => 'Outro',
                ],
            ],
            [
                'question' => 'Qual seu principal interesse na plataforma?',
                'type' => 'select',
                'options' => [
                    'eventos' => 'Participar de Eventos',
                    'network' => 'Networking',
                    'conteudo' => 'Criar Conteúdo',
                    'apoiar' => 'Apoiar a Cena',
                    'outro' => 'Outro',
                ],
            ],
            [
                'question' => 'Conte-nos um pouco sobre você e sua relação com a música eletrônica:',
                'type' => 'textarea',
                'placeholder' => 'Descreva sua experiência, interesses e objetivos...',
            ],
        ];
    }
}

