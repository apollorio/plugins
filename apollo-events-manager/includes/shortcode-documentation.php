<?php
// phpcs:ignoreFile
/**
 * Apollo Events Manager - Shortcode Documentation Generator
 *
 * Auto-generates shortcode documentation on activation
 *
 * @package Apollo_Events_Manager
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Get all registered shortcodes with metadata
 */
function apollo_get_all_shortcodes_documentation()
{
    global $shortcode_tags;

    $apollo_shortcodes = [];

    // Define metadata for each shortcode
    $shortcode_metadata = [
        // Event Shortcodes
        'apollo_events' => [
            'name'        => 'apollo_events',
            'category'    => 'Events',
            'description' => 'Exibe lista completa de eventos com filtros e paginação',
            'usage'       => '[apollo_events per_page="10" orderby="date" order="DESC"]',
            'advantages'  => [
                'Listagem completa com filtros',
                'Paginação automática',
                'Suporte a ordenação',
                'Integração com analytics',
            ],
            'negative_aspects' => [
                'Pode ser lento com muitos eventos (precisa cache)',
                'Não tem filtro por categoria no shortcode',
                'Falta suporte a busca por texto',
            ],
        ],
        'events' => [
            'name'        => 'events',
            'category'    => 'Events',
            'description' => 'Alias para apollo_events - Lista de eventos',
            'usage'       => '[events per_page="10"]',
            'advantages'  => [
                'Nome curto e intuitivo',
                'Compatível com padrões comuns',
            ],
            'negative_aspects' => [
                'Pode conflitar com outros plugins',
                'Nome muito genérico',
            ],
        ],
        'event' => [
            'name'        => 'event',
            'category'    => 'Events',
            'description' => 'Exibe conteúdo completo de um evento específico (usado em lightbox)',
            'usage'       => '[event id="123"]',
            'advantages'  => [
                'Renderização completa do evento',
                'Suporte a lightbox/modal',
                'Inclui todos os placeholders',
            ],
            'negative_aspects' => [
                'Requer ID do evento',
                'Não valida se evento existe',
                'Pode ser pesado para muitos eventos',
            ],
        ],
        'apollo_event' => [
            'name'        => 'apollo_event',
            'category'    => 'Events',
            'description' => 'Acessa valores de placeholders específicos de eventos',
            'usage'       => '[apollo_event field="dj_list" id="123"]',
            'advantages'  => [
                'Acesso granular a dados',
                'Sistema de placeholders robusto',
                'Flexível e extensível',
            ],
            'negative_aspects' => [
                'Requer conhecimento dos placeholders',
                'Pode ser confuso para usuários não técnicos',
                'Falta validação de field inválido',
            ],
        ],
        'eventos-page' => [
            'name'        => 'eventos-page',
            'category'    => 'Events',
            'description' => 'Portal completo de eventos com filtros avançados e modal',
            'usage'       => '[eventos-page]',
            'advantages'  => [
                'Interface completa pronta',
                'Filtros em tempo real',
                'Modal/lightbox integrado',
                'Design responsivo',
            ],
            'negative_aspects' => [
                'Pouco customizável via atributos',
                'Depende de JavaScript',
                'Pode ser pesado em mobile',
            ],
        ],
        'event_summary' => [
            'name'        => 'event_summary',
            'category'    => 'Events',
            'description' => 'Resumo compacto de um evento',
            'usage'       => '[event_summary id="123"]',
            'advantages'  => [
                'Visual compacto',
                'Rápido de carregar',
                'Ideal para sidebars',
            ],
            'negative_aspects' => [
                'Informações limitadas',
                'Não mostra todos os detalhes',
                'Falta customização visual',
            ],
        ],
        'past_events' => [
            'name'        => 'past_events',
            'category'    => 'Events',
            'description' => 'Lista eventos passados',
            'usage'       => '[past_events limit="5"]',
            'advantages'  => [
                'Filtro automático por data',
                'Útil para histórico',
                'Performance otimizada',
            ],
            'negative_aspects' => [
                'Não permite customizar período',
                'Falta ordenação customizável',
                'Não agrupa por mês/ano',
            ],
        ],
        'upcoming_events' => [
            'name'        => 'upcoming_events',
            'category'    => 'Events',
            'description' => 'Lista eventos futuros',
            'usage'       => '[upcoming_events limit="10"]',
            'advantages'  => [
                'Filtro automático por data',
                'Ideal para homepages',
                'Performance otimizada',
            ],
            'negative_aspects' => [
                'Não permite customizar período',
                'Falta ordenação customizável',
                'Não diferencia eventos próximos vs distantes',
            ],
        ],
        'related_events' => [
            'name'        => 'related_events',
            'category'    => 'Events',
            'description' => 'Eventos relacionados baseado em categorias/sounds',
            'usage'       => '[related_events id="123" limit="5"]',
            'advantages'  => [
                'Recomendações inteligentes',
                'Baseado em taxonomias',
                'Aumenta engajamento',
            ],
            'negative_aspects' => [
                'Algoritmo simples (só taxonomias)',
                'Não considera histórico do usuário',
                'Pode retornar poucos resultados',
            ],
        ],
        'event_register' => [
            'name'        => 'event_register',
            'category'    => 'Events',
            'description' => 'Formulário de registro para evento',
            'usage'       => '[event_register id="123"]',
            'advantages'  => [
                'Integração direta',
                'Validação automática',
                'Suporte a pagamento',
            ],
            'negative_aspects' => [
                'Depende de plugin de pagamento',
                'Não tem confirmação por email',
                'Falta integração com calendário',
            ],
        ],
        'apollo_event_user_overview' => [
            'name'        => 'apollo_event_user_overview',
            'category'    => 'User',
            'description' => 'Visão geral de estatísticas do usuário logado',
            'usage'       => '[apollo_event_user_overview]',
            'advantages'  => [
                'Personalizado por usuário',
                'Mostra co-autoria e favoritos',
                'Distribuição de interesses',
            ],
            'negative_aspects' => [
                'Requer usuário logado',
                'Pode ser lento com muitos dados',
                'Falta exportação de dados',
            ],
        ],

        // DJ Shortcodes
        'event_djs' => [
            'name'        => 'event_djs',
            'category'    => 'DJs',
            'description' => 'Lista DJs de um evento',
            'usage'       => '[event_djs id="123"]',
            'advantages'  => [
                'Lista completa de DJs',
                'Suporte a timetable',
                'Links para perfis',
            ],
            'negative_aspects' => [
                'Não mostra horários se não houver timetable',
                'Falta ordenação por horário',
                'Não agrupa por dia',
            ],
        ],
        'event_dj' => [
            'name'        => 'event_dj',
            'category'    => 'DJs',
            'description' => 'Exibe um DJ específico de um evento',
            'usage'       => '[event_dj id="123" dj_id="456"]',
            'advantages'  => [
                'Foco em um DJ',
                'Informações detalhadas',
                'Link para perfil completo',
            ],
            'negative_aspects' => [
                'Requer dois IDs',
                'Não valida relacionamento',
                'Falta foto do DJ',
            ],
        ],
        'single_event_dj' => [
            'name'        => 'single_event_dj',
            'category'    => 'DJs',
            'description' => 'Exibe perfil completo de um DJ',
            'usage'       => '[single_event_dj id="456"]',
            'advantages'  => [
                'Perfil completo',
                'Links sociais',
                'Próximos eventos',
                'Player de música',
            ],
            'negative_aspects' => [
                'Pode ser pesado com muitos eventos',
                'Falta integração com redes sociais',
                'Player depende de URL externa',
            ],
        ],
        'submit_dj_form' => [
            'name'        => 'submit_dj_form',
            'category'    => 'DJs',
            'description' => 'Formulário de submissão de DJ',
            'usage'       => '[submit_dj_form]',
            'advantages'  => [
                'Submissão pública',
                'Validação automática',
                'Moderação opcional',
            ],
            'negative_aspects' => [
                'Requer usuário logado',
                'Falta preview antes de enviar',
                'Não envia notificação ao admin',
            ],
        ],
        'dj_dashboard' => [
            'name'        => 'dj_dashboard',
            'category'    => 'DJs',
            'description' => 'Dashboard do DJ com seus eventos e estatísticas',
            'usage'       => '[dj_dashboard]',
            'advantages'  => [
                'Visão consolidada',
                'Estatísticas pessoais',
                'Gerenciamento de eventos',
            ],
            'negative_aspects' => [
                'Requer usuário logado',
                'Pode ser lento com muitos eventos',
                'Falta exportação de dados',
            ],
        ],

        // Local Shortcodes
        'event_locals' => [
            'name'        => 'event_locals',
            'category'    => 'Locals',
            'description' => 'Lista locais de um evento',
            'usage'       => '[event_locals id="123"]',
            'advantages'  => [
                'Lista completa de locais',
                'Informações de endereço',
                'Links para mapas',
            ],
            'negative_aspects' => [
                'Não mostra múltiplos locais bem',
                'Falta integração com Google Maps',
                'Não valida coordenadas',
            ],
        ],
        'event_local' => [
            'name'        => 'event_local',
            'category'    => 'Locals',
            'description' => 'Exibe um local específico de um evento',
            'usage'       => '[event_local id="123" local_id="789"]',
            'advantages'  => [
                'Foco em um local',
                'Informações detalhadas',
                'Mapa integrado',
            ],
            'negative_aspects' => [
                'Requer dois IDs',
                'Não valida relacionamento',
                'Falta foto do local',
            ],
        ],
        'single_event_local' => [
            'name'        => 'single_event_local',
            'category'    => 'Locals',
            'description' => 'Exibe perfil completo de um local',
            'usage'       => '[single_event_local id="789"]',
            'advantages'  => [
                'Perfil completo',
                'Mapa interativo',
                'Próximos eventos',
                'Informações técnicas',
            ],
            'negative_aspects' => [
                'Pode ser pesado com muitos eventos',
                'Falta integração com Google Maps',
                'Tech notes não são públicas',
            ],
        ],
        'submit_local_form' => [
            'name'        => 'submit_local_form',
            'category'    => 'Locals',
            'description' => 'Formulário de submissão de local',
            'usage'       => '[submit_local_form]',
            'advantages'  => [
                'Submissão pública',
                'Validação automática',
                'Geocodificação automática',
            ],
            'negative_aspects' => [
                'Requer usuário logado',
                'Falta preview antes de enviar',
                'Geocodificação pode falhar',
            ],
        ],
        'local_dashboard' => [
            'name'        => 'local_dashboard',
            'category'    => 'Locals',
            'description' => 'Dashboard do local com eventos e estatísticas',
            'usage'       => '[local_dashboard]',
            'advantages'  => [
                'Visão consolidada',
                'Estatísticas de eventos',
                'Gerenciamento de tech notes',
            ],
            'negative_aspects' => [
                'Requer usuário logado',
                'Pode ser lento com muitos eventos',
                'Tech notes são privadas',
            ],
        ],

        // Form Shortcodes
        'submit_event_form' => [
            'name'        => 'submit_event_form',
            'category'    => 'Forms',
            'description' => 'Formulário de submissão de evento',
            'usage'       => '[submit_event_form]',
            'advantages'  => [
                'Submissão pública',
                'Validação automática',
                'Moderação opcional',
                'Integração com save-date',
            ],
            'negative_aspects' => [
                'Requer usuário logado',
                'Falta preview antes de enviar',
                'Não envia notificação ao admin',
                'Duplicidade control só no save',
            ],
        ],
        'apollo_public_event_form' => [
            'name'        => 'apollo_public_event_form',
            'category'    => 'Forms',
            'description' => 'Formulário público de submissão de evento (landing)',
            'usage'       => '[apollo_public_event_form]',
            'advantages'  => [
                'Não requer login',
                'Design moderno',
                'Integração com REST API',
                'Suporte a cupom Apollo',
            ],
            'negative_aspects' => [
                'Pode gerar spam',
                'Falta validação de email',
                'Não envia confirmação',
                'Depende de moderação manual',
            ],
        ],

        // Dashboard Shortcodes
        'event_dashboard' => [
            'name'        => 'event_dashboard',
            'category'    => 'Dashboard',
            'description' => 'Dashboard de eventos do usuário',
            'usage'       => '[event_dashboard]',
            'advantages'  => [
                'Gerenciamento completo',
                'Estatísticas pessoais',
                'Ações rápidas',
                'Lista de eventos',
            ],
            'negative_aspects' => [
                'Requer usuário logado',
                'Pode ser lento com muitos eventos',
                'Falta filtros avançados',
                'Não tem busca',
            ],
        ],
    ];

    // Filter only Apollo shortcodes
    foreach ($shortcode_tags as $tag => $callback) {
        if (isset($shortcode_metadata[ $tag ])) {
            $apollo_shortcodes[ $tag ] = $shortcode_metadata[ $tag ];
        }
    }

    return $apollo_shortcodes;
}

/**
 * Generate shortcode documentation HTML
 */
function apollo_generate_shortcode_documentation_html()
{
    $shortcodes = apollo_get_all_shortcodes_documentation();

    if (empty($shortcodes)) {
        return '<p>' . esc_html__('Nenhum shortcode encontrado.', 'apollo-events-manager') . '</p>';
    }

    // Group by category
    $by_category = [];
    foreach ($shortcodes as $shortcode) {
        $cat = $shortcode['category'];
        if (! isset($by_category[ $cat ])) {
            $by_category[ $cat ] = [];
        }
        $by_category[ $cat ][] = $shortcode;
    }

    $html = '<div class="apollo-shortcode-docs">';
    $html .= '<h2>' . esc_html__('📋 Shortcodes Disponíveis', 'apollo-events-manager') . '</h2>';
    $html .= '<p class="description">' . esc_html__('Documentação completa de todos os shortcodes do Apollo Events Manager.', 'apollo-events-manager') . '</p>';

    foreach ($by_category as $category => $items) {
        $html .= '<div class="apollo-shortcode-category">';
        $html .= '<h3>' . esc_html($category) . ' (' . count($items) . ')</h3>';

        foreach ($items as $shortcode) {
            $html .= '<div class="apollo-shortcode-item">';
            $html .= '<div class="apollo-shortcode-header">';
            $html .= '<code class="apollo-shortcode-name">[&nbsp;' . esc_html($shortcode['name']) . '&nbsp;]</code>';
            $html .= '</div>';

            $html .= '<div class="apollo-shortcode-body">';
            $html .= '<p class="apollo-shortcode-desc"><strong>' . esc_html__('Descrição:', 'apollo-events-manager') . '</strong> ' . esc_html($shortcode['description']) . '</p>';

            if (! empty($shortcode['usage'])) {
                $html .= '<p class="apollo-shortcode-usage"><strong>' . esc_html__('Uso:', 'apollo-events-manager') . '</strong> <code>' . esc_html($shortcode['usage']) . '</code></p>';
            }

            if (! empty($shortcode['advantages'])) {
                $html .= '<div class="apollo-shortcode-advantages">';
                $html .= '<strong>✅ ' . esc_html__('Vantagens:', 'apollo-events-manager') . '</strong>';
                $html .= '<ul>';
                foreach ($shortcode['advantages'] as $advantage) {
                    $html .= '<li>' . esc_html($advantage) . '</li>';
                }
                $html .= '</ul>';
                $html .= '</div>';
            }

            if (! empty($shortcode['negative_aspects'])) {
                $html .= '<div class="apollo-shortcode-negative">';
                $html .= '<strong>⚠️ ' . esc_html__('Aspectos Negativos / Melhorias:', 'apollo-events-manager') . '</strong>';
                $html .= '<ul>';
                foreach ($shortcode['negative_aspects'] as $negative) {
                    $html .= '<li>' . esc_html($negative) . '</li>';
                }
                $html .= '</ul>';
                $html .= '</div>';
            }

            $html .= '</div>';
            $html .= '</div>';
        }//end foreach

        $html .= '</div>';
    }//end foreach

    $html .= '</div>';

    // Add CSS
    $html .= '<style>
        .apollo-shortcode-docs { margin: 20px 0; }
        .apollo-shortcode-category { margin: 30px 0; padding: 20px; background: #f9f9f9; border-radius: 8px; }
        .apollo-shortcode-category h3 { margin-top: 0; color: #0073aa; }
        .apollo-shortcode-item { margin: 20px 0; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 6px; }
        .apollo-shortcode-header { margin-bottom: 15px; }
        .apollo-shortcode-name { font-size: 16px; font-weight: 600; color: #0073aa; background: #f0f0f0; padding: 5px 10px; border-radius: 4px; }
        .apollo-shortcode-body p { margin: 10px 0; }
        .apollo-shortcode-usage code { background: #f5f5f5; padding: 3px 6px; border-radius: 3px; }
        .apollo-shortcode-advantages { margin: 15px 0; padding: 10px; background: #e8f5e9; border-left: 3px solid #4caf50; }
        .apollo-shortcode-advantages ul { margin: 10px 0 0 20px; }
        .apollo-shortcode-negative { margin: 15px 0; padding: 10px; background: #fff3e0; border-left: 3px solid #ff9800; }
        .apollo-shortcode-negative ul { margin: 10px 0 0 20px; }
        .apollo-shortcode-advantages ul li, .apollo-shortcode-negative ul li { margin: 5px 0; }
    </style>';

    return $html;
}

/**
 * Show shortcode documentation on activation
 */
function apollo_show_shortcode_documentation_on_activation()
{
    // Check if we should show docs (set during activation)
    if (! get_transient('apollo_show_docs_on_activation')) {
        return;
        // Not activated or already shown
    }

    $transient_key = 'apollo_shortcode_docs_shown';

    if (get_transient($transient_key)) {
        delete_transient('apollo_show_docs_on_activation');

        return;
        // Already shown
    }

    // Set transient for 1 hour (so it shows once per activation)
    set_transient($transient_key, true, HOUR_IN_SECOND);
    delete_transient('apollo_show_docs_on_activation');

    // Add admin notice
    add_action(
        'admin_notices',
        function () {
            $docs = apollo_generate_shortcode_documentation_html();
            ?>
		<div class="notice notice-info is-dismissible apollo-shortcode-docs-notice">
			<div style="max-height: 600px; overflow-y: auto; padding: 10px;">
				<?php echo wp_kses_post($docs); // SECURITY: Escape HTML output to prevent XSS ?>
			</div>
			<p>
				<a href="<?php echo esc_url(admin_url('admin.php?page=apollo-shortcodes')); ?>" class="button button-primary">
					<?php esc_html_e('Ver Documentação Completa', 'apollo-events-manager'); ?>
				</a>
				<button type="button" class="notice-dismiss">
					<span class="screen-reader-text"><?php esc_html_e('Dispensar este aviso.', 'apollo-events-manager'); ?></span>
				</button>
			</p>
		</div>
		<script>
		jQuery(document).on('click', '.apollo-shortcode-docs-notice .notice-dismiss', function() {
			jQuery.ajax({
				url: ajaxurl,
				data: {
					action: 'apollo_dismiss_shortcode_docs',
					nonce: '<?php echo wp_create_nonce('apollo_dismiss_docs'); ?>'
				}
			});
		});
		</script>
			<?php
        }
    );
}

// Check on admin init
add_action('admin_init', 'apollo_show_shortcode_documentation_on_activation');

// AJAX handler to dismiss notice
add_action(
    'wp_ajax_apollo_dismiss_shortcode_docs',
    function () {
        check_ajax_referer('apollo_dismiss_docs', 'nonce');
        delete_transient('apollo_shortcode_docs_shown');
        delete_transient('apollo_show_docs_on_activation');
        wp_send_json_success();
    }
);

/**
 * Add shortcode documentation to admin page
 */
function apollo_add_shortcode_documentation_to_admin()
{
    add_action(
        'admin_menu',
        function () {
            add_submenu_page(
                'apollo-events',
                __('Shortcodes', 'apollo-events-manager'),
                __('Shortcodes', 'apollo-events-manager'),
                'manage_options',
                'apollo-shortcodes',
                function () {
                    ?>
				<div class="wrap">
						<h1><?php echo esc_html__('Apollo Events Manager - Shortcodes', 'apollo-events-manager'); ?></h1>
						<?php echo apollo_generate_shortcode_documentation_html(); ?>
				</div>
					<?php
                }
            );
        }
    );
}

// Initialize
apollo_add_shortcode_documentation_to_admin();
