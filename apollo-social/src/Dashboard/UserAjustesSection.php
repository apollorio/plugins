<?php
declare(strict_types=1);

namespace Apollo\Dashboard;

use Apollo\Modules\Registration\CulturaRioIdentity;

/**
 * User Dashboard - Ajustes Section
 * 
 * Displays user settings including membership status and feature visibility.
 * Shows/hides features and badges based on membership status.
 * 
 * @package Apollo_Social
 * @since 1.2.0
 */
class UserAjustesSection
{
    /**
     * Initialize hooks
     */
    public static function init(): void
    {
        // Add shortcode for ajustes section
        add_shortcode('apollo_user_ajustes', [self::class, 'renderAjustesSection']);
        
        // AJAX handlers
        add_action('wp_ajax_apollo_save_user_settings', [self::class, 'ajaxSaveSettings']);
        add_action('wp_ajax_apollo_update_profile', [self::class, 'ajaxUpdateProfile']);
    }

    /**
     * Render the Ajustes section
     */
    public static function renderAjustesSection(array $atts = []): string
    {
        if (!is_user_logged_in()) {
            return '<p>Você precisa estar logado para acessar esta seção.</p>';
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        // Get user data
        $identities = CulturaRioIdentity::getUserIdentities($user_id);
        $membership_status = CulturaRioIdentity::getMembershipStatus($user_id);
        $sounds = get_user_meta($user_id, 'apollo_sounds', true) ?: [];
        $avatar_url = get_avatar_url($user_id, ['size' => 150]);
        
        // Feature visibility settings
        $settings = get_user_meta($user_id, 'apollo_display_settings', true) ?: [];
        
        ob_start();
        ?>
        <div class="apollo-ajustes-section" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); padding: 30px; border-radius: 12px; color: #fff;">
            
            <!-- Profile Section -->
            <section class="ajustes-profile" style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <h2 style="color: #00d4ff; margin-top: 0;">👤 Perfil Público</h2>
                
                <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                    <!-- Avatar -->
                    <div class="ajustes-avatar" style="text-align: center;">
                        <img src="<?php echo esc_url($avatar_url); ?>" 
                             alt="Avatar" 
                             style="width: 120px; height: 120px; border-radius: 50%; border: 3px solid #00d4ff;" />
                        <p style="margin: 10px 0 0;">
                            <a href="<?php echo esc_url(admin_url('profile.php#your-profile')); ?>" 
                               style="color: #00d4ff; font-size: 0.9em;">
                                Alterar foto
                            </a>
                        </p>
                    </div>
                    
                    <!-- Name & Display -->
                    <div class="ajustes-name" style="flex: 1; min-width: 250px;">
                        <label style="display: block; margin-bottom: 5px; color: #a0a0a0; font-size: 0.9em;">
                            Nome Público
                        </label>
                        <input type="text" 
                               id="apollo_display_name" 
                               value="<?php echo esc_attr($user->display_name); ?>"
                               style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; color: #fff; font-size: 16px;" />
                        
                        <p style="color: #666; font-size: 0.85em; margin-top: 5px;">
                            Este nome será exibido publicamente no seu perfil.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Membership Status Section -->
            <section class="ajustes-membership" style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <h2 style="color: #00d4ff;">🎭 Meu Status na Cultura::Rio</h2>
                
                <div class="membership-card" style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 8px;">
                    <!-- Current Identities -->
                    <div style="margin-bottom: 15px;">
                        <strong style="color: #a0a0a0;">Minhas identidades:</strong>
                        <div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 8px;">
                            <?php foreach ($identities as $key): 
                                $label = CulturaRioIdentity::getIdentityLabel($key);
                                $is_clubber = $key === 'clubber';
                            ?>
                                <span style="display: inline-block; background: <?php echo $is_clubber ? '#00d4ff' : 'rgba(255,255,255,0.1)'; ?>; color: <?php echo $is_clubber ? '#000' : '#fff'; ?>; padding: 6px 12px; border-radius: 20px; font-size: 0.9em;">
                                    <?php echo esc_html($label); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Membership Status -->
                    <div style="margin-bottom: 15px;">
                        <strong style="color: #a0a0a0;">Status do membership:</strong>
                        <?php 
                        $status_colors = [
                            'none' => '#888',
                            'pending' => '#f0ad4e',
                            'approved' => '#5cb85c',
                            'rejected' => '#d9534f',
                        ];
                        $status_labels = [
                            'none' => 'Clubber (padrão)',
                            'pending' => '⏳ Aguardando aprovação',
                            'approved' => '✅ Aprovado',
                            'rejected' => '❌ Não aprovado',
                        ];
                        $status = $membership_status['status'];
                        ?>
                        <span style="display: inline-block; margin-left: 10px; color: <?php echo $status_colors[$status] ?? '#888'; ?>; font-weight: 600;">
                            <?php echo $status_labels[$status] ?? ucfirst($status); ?>
                        </span>
                        
                        <?php if ($status === 'approved' && $membership_status['approved_at']): ?>
                            <span style="color: #666; font-size: 0.85em; margin-left: 10px;">
                                (em <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($membership_status['approved_at']))); ?>)
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($status === 'pending' && !empty($membership_status['requested'])): ?>
                        <div style="background: rgba(240, 173, 78, 0.1); padding: 15px; border-radius: 6px; border-left: 3px solid #f0ad4e;">
                            <p style="margin: 0; color: #f0ad4e;">
                                <strong>Solicitação em análise:</strong><br>
                                <?php echo esc_html(implode(', ', $membership_status['requested'])); ?>
                            </p>
                            <p style="margin: 10px 0 0; color: #888; font-size: 0.85em;">
                                Nossa equipe está analisando sua solicitação. Você receberá um email quando tivermos uma resposta.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Feature Visibility Section -->
            <section class="ajustes-features" style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <h2 style="color: #00d4ff;">⚙️ Visibilidade de Recursos</h2>
                <p style="color: #a0a0a0; margin-bottom: 20px;">
                    Escolha quais informações e badges são exibidos no seu perfil público.
                </p>
                
                <div class="features-grid" style="display: grid; gap: 15px;">
                    <?php 
                    $features = self::getFeaturesList($user_id, $status);
                    foreach ($features as $feature_key => $feature): 
                        $is_enabled = $settings[$feature_key] ?? $feature['default'];
                        $is_available = $feature['available'];
                    ?>
                        <label style="display: flex; align-items: center; gap: 15px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 8px; cursor: <?php echo $is_available ? 'pointer' : 'not-allowed'; ?>; opacity: <?php echo $is_available ? '1' : '0.5'; ?>;">
                            <input type="checkbox" 
                                   name="apollo_features[<?php echo esc_attr($feature_key); ?>]" 
                                   class="apollo-feature-toggle"
                                   data-feature="<?php echo esc_attr($feature_key); ?>"
                                   <?php checked($is_enabled && $is_available); ?>
                                   <?php echo $is_available ? '' : 'disabled'; ?>
                                   style="width: 20px; height: 20px; accent-color: #00d4ff;" />
                            <div>
                                <strong style="color: #fff;"><?php echo esc_html($feature['label']); ?></strong>
                                <?php if (!$is_available): ?>
                                    <span style="color: #f0ad4e; font-size: 0.8em; margin-left: 5px;">
                                        (requer <?php echo esc_html($feature['requires']); ?>)
                                    </span>
                                <?php endif; ?>
                                <p style="margin: 5px 0 0; color: #888; font-size: 0.85em;">
                                    <?php echo esc_html($feature['description']); ?>
                                </p>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Sounds Section -->
            <section class="ajustes-sounds" style="margin-bottom: 30px;">
                <h2 style="color: #00d4ff;">🎵 Gêneros Musicais</h2>
                <p style="color: #a0a0a0;">
                    Seus gêneros favoritos: 
                    <strong style="color: #fff;">
                        <?php echo !empty($sounds) ? esc_html(implode(', ', $sounds)) : 'Nenhum selecionado'; ?>
                    </strong>
                </p>
            </section>

            <!-- Save Button -->
            <div class="ajustes-actions" style="text-align: center;">
                <button type="button" 
                        id="apollo-save-ajustes-btn"
                        style="background: linear-gradient(135deg, #00d4ff 0%, #0066cc 100%); color: #fff; border: none; padding: 15px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">
                    💾 Salvar Ajustes
                </button>
                <p id="ajustes-save-status" style="margin-top: 10px; color: #888;"></p>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var saveBtn = document.getElementById('apollo-save-ajustes-btn');
            var statusEl = document.getElementById('ajustes-save-status');
            
            saveBtn.addEventListener('click', function() {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Salvando...';
                statusEl.textContent = '';
                
                // Collect data
                var features = {};
                document.querySelectorAll('.apollo-feature-toggle').forEach(function(cb) {
                    features[cb.dataset.feature] = cb.checked;
                });
                
                var displayName = document.getElementById('apollo_display_name').value;
                
                // Send AJAX
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'apollo_save_user_settings',
                        display_name: displayName,
                        features: JSON.stringify(features),
                        _wpnonce: '<?php echo wp_create_nonce('apollo_user_settings'); ?>'
                    })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = '💾 Salvar Ajustes';
                    if (data.success) {
                        statusEl.innerHTML = '<span style="color:#5cb85c;">✅ Salvo com sucesso!</span>';
                    } else {
                        statusEl.innerHTML = '<span style="color:#d9534f;">❌ ' + (data.data || 'Erro') + '</span>';
                    }
                })
                .catch(function(err) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = '💾 Salvar Ajustes';
                    statusEl.innerHTML = '<span style="color:#d9534f;">❌ Erro de conexão</span>';
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Get features list based on membership status
     */
    private static function getFeaturesList(int $user_id, string $status): array
    {
        $is_approved = $status === 'approved';
        $identities = CulturaRioIdentity::getUserIdentities($user_id);
        
        $is_dj = in_array('dj_amateur', $identities, true) || in_array('dj_pro', $identities, true);
        $is_producer = in_array('producer_dreamer', $identities, true) || 
                       in_array('producer_starter', $identities, true) || 
                       in_array('producer_pro', $identities, true);

        return [
            'show_sounds' => [
                'label' => 'Mostrar gêneros musicais',
                'description' => 'Exibir seus gêneros musicais favoritos no perfil público.',
                'default' => true,
                'available' => true,
                'requires' => '',
            ],
            'show_membership_badge' => [
                'label' => 'Badge de Membership',
                'description' => 'Exibir badge de membro verificado no perfil.',
                'default' => true,
                'available' => $is_approved,
                'requires' => 'membership aprovado',
            ],
            'show_dj_badge' => [
                'label' => 'Badge de DJ',
                'description' => 'Exibir badge especial de DJ no perfil.',
                'default' => true,
                'available' => $is_approved && $is_dj,
                'requires' => 'membership DJ aprovado',
            ],
            'show_producer_badge' => [
                'label' => 'Badge de Producer',
                'description' => 'Exibir badge de Producer de Eventos.',
                'default' => true,
                'available' => $is_approved && $is_producer,
                'requires' => 'membership Producer aprovado',
            ],
            'show_cultura_identities' => [
                'label' => 'Identidades Cultura::Rio',
                'description' => 'Mostrar suas identidades culturais no perfil.',
                'default' => true,
                'available' => true,
                'requires' => '',
            ],
            'allow_messages' => [
                'label' => 'Permitir mensagens',
                'description' => 'Permitir que outros usuários enviem mensagens diretas.',
                'default' => true,
                'available' => true,
                'requires' => '',
            ],
            'show_events_attended' => [
                'label' => 'Eventos participados',
                'description' => 'Mostrar contador de eventos que você participou.',
                'default' => false,
                'available' => true,
                'requires' => '',
            ],
        ];
    }

    /**
     * AJAX: Save user settings
     */
    public static function ajaxSaveSettings(): void
    {
        check_ajax_referer('apollo_user_settings');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Não autenticado');
        }

        $user_id = get_current_user_id();
        
        // Save display name
        $display_name = sanitize_text_field($_POST['display_name'] ?? '');
        if (!empty($display_name)) {
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $display_name,
            ]);
        }

        // Save features
        $features = json_decode(stripslashes($_POST['features'] ?? '{}'), true);
        if (is_array($features)) {
            $sanitized = [];
            foreach ($features as $key => $value) {
                $sanitized[sanitize_key($key)] = (bool) $value;
            }
            update_user_meta($user_id, 'apollo_display_settings', $sanitized);
        }

        wp_send_json_success('Salvo');
    }
}

// Initialize
add_action('init', [UserAjustesSection::class, 'init']);

