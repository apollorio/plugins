<?php
/**
 * Apollo Core - Shortcode Registry
 *
 * Central registry for all Apollo shortcodes. Provides:
 * - Unified shortcode registration
 * - Documentation generation
 * - Conflict detection
 * - Debug utilities
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Shortcode_Registry
 *
 * Central shortcode registry for all Apollo plugins.
 */
class Apollo_Shortcode_Registry {

	/**
	 * Singleton instance
	 *
	 * @var Apollo_Shortcode_Registry|null
	 */
	private static ?Apollo_Shortcode_Registry $instance = null;

	/**
	 * Registered shortcodes
	 *
	 * @var array<string, array>
	 */
	private array $shortcodes = array();

	/**
	 * Shortcode groups
	 *
	 * @var array<string, array>
	 */
	private array $groups = array();

	/**
	 * Detected conflicts
	 *
	 * @var array
	 */
	private array $conflicts = array();

	/**
	 * Get singleton instance
	 *
	 * @return Apollo_Shortcode_Registry
	 */
	public static function get_instance(): Apollo_Shortcode_Registry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->init_groups();
		$this->register_hooks();
	}

	/**
	 * Initialize shortcode groups
	 *
	 * @return void
	 */
	private function init_groups(): void {
		$this->groups = array(
			'events' => array(
				'label'       => __( 'Eventos', 'apollo-core' ),
				'description' => __( 'Shortcodes para exibição de eventos.', 'apollo-core' ),
				'plugin'      => 'apollo-events-manager',
				'icon'        => 'ri-calendar-event-line',
			),
			'social' => array(
				'label'       => __( 'Social', 'apollo-core' ),
				'description' => __( 'Shortcodes para recursos sociais.', 'apollo-core' ),
				'plugin'      => 'apollo-social',
				'icon'        => 'ri-group-line',
			),
			'rio'    => array(
				'label'       => __( 'Performance', 'apollo-core' ),
				'description' => __( 'Shortcodes para otimização de performance.', 'apollo-core' ),
				'plugin'      => 'apollo-rio',
				'icon'        => 'ri-speed-line',
			),
			'core'   => array(
				'label'       => __( 'Core', 'apollo-core' ),
				'description' => __( 'Shortcodes base do sistema.', 'apollo-core' ),
				'plugin'      => 'apollo-core',
				'icon'        => 'ri-settings-3-line',
			),
		);
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// Register built-in shortcodes after all plugins loaded.
		add_action( 'init', array( $this, 'register_builtin_shortcodes' ), 5 );

		// Check for conflicts after init.
		add_action( 'init', array( $this, 'detect_conflicts' ), 999 );

		// Admin notices for conflicts.
		add_action( 'admin_notices', array( $this, 'display_conflict_notices' ) );

		// Add admin page for documentation.
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );

		// REST API for shortcode info.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Add shortcode finder to editor.
		add_action( 'admin_footer', array( $this, 'render_shortcode_finder_modal' ) );
	}

	// =========================================================================
	// SHORTCODE REGISTRATION
	// =========================================================================

	/**
	 * Register a shortcode with the registry
	 *
	 * @param string $tag        Shortcode tag.
	 * @param array  $definition Shortcode definition.
	 * @return void
	 */
	public function register( string $tag, array $definition ): void {
		$defaults = array(
			'tag'         => $tag,
			'group'       => 'core',
			'label'       => $tag,
			'description' => '',
			'callback'    => null,
			'attributes'  => array(),
			'examples'    => array(),
			'supports'    => array(),
			'deprecated'  => false,
			'replacement' => '',
			'version'     => '1.0.0',
		);

		$this->shortcodes[ $tag ] = wp_parse_args( $definition, $defaults );
	}

	/**
	 * Register built-in Apollo shortcodes
	 *
	 * @return void
	 */
	public function register_builtin_shortcodes(): void {
		// =====================================================================
		// EVENTS GROUP
		// =====================================================================

		$this->register(
			'apollo_events',
			array(
				'group'       => 'events',
				'label'       => __( 'Lista de Eventos', 'apollo-core' ),
				'description' => __( 'Exibe uma grade/lista/carrossel de eventos.', 'apollo-core' ),
				'attributes'  => array(
					'limit'    => array(
						'type'        => 'number',
						'default'     => 6,
						'description' => __( 'Número máximo de eventos.', 'apollo-core' ),
					),
					'category' => array(
						'type'        => 'string',
						'default'     => '',
						'description' => __( 'Slug da categoria (separados por vírgula).', 'apollo-core' ),
					),
					'type'     => array(
						'type'        => 'string',
						'default'     => '',
						'description' => __( 'Slug do tipo de evento.', 'apollo-core' ),
					),
					'sound'    => array(
						'type'        => 'string',
						'default'     => '',
						'description' => __( 'Slug do gênero musical.', 'apollo-core' ),
					),
					'orderby'  => array(
						'type'        => 'select',
						'default'     => 'event_date',
						'options'     => array( 'event_date', 'date', 'title', 'rand' ),
						'description' => __( 'Campo de ordenação.', 'apollo-core' ),
					),
					'order'    => array(
						'type'        => 'select',
						'default'     => 'ASC',
						'options'     => array( 'ASC', 'DESC' ),
						'description' => __( 'Direção da ordenação.', 'apollo-core' ),
					),
					'layout'   => array(
						'type'        => 'select',
						'default'     => 'grid',
						'options'     => array( 'grid', 'list', 'carousel' ),
						'description' => __( 'Layout de exibição.', 'apollo-core' ),
					),
					'columns'  => array(
						'type'        => 'number',
						'default'     => 3,
						'description' => __( 'Número de colunas (1-4).', 'apollo-core' ),
					),
					'featured' => array(
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Mostrar apenas eventos em destaque.', 'apollo-core' ),
					),
					'upcoming' => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Mostrar apenas eventos futuros.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_events limit="6" layout="grid" columns="3"]',
					'[apollo_events category="festa" sound="techno" limit="12"]',
					'[apollo_events layout="carousel" featured="true"]',
				),
				'version'     => '2.0.0',
			)
		);

		$this->register(
			'apollo_event_single',
			array(
				'group'       => 'events',
				'label'       => __( 'Evento Único', 'apollo-core' ),
				'description' => __( 'Exibe os detalhes completos de um evento.', 'apollo-core' ),
				'attributes'  => array(
					'id'     => array(
						'type'        => 'number',
						'default'     => 0,
						'description' => __( 'ID do evento. Se não informado, usa o evento atual.', 'apollo-core' ),
					),
					'layout' => array(
						'type'        => 'select',
						'default'     => 'full',
						'options'     => array( 'full', 'compact', 'hero' ),
						'description' => __( 'Layout de exibição.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_event_single id="123"]',
					'[apollo_event_single layout="hero"]',
				),
				'version'     => '2.0.0',
			)
		);

		$this->register(
			'apollo_event_calendar',
			array(
				'group'       => 'events',
				'label'       => __( 'Calendário de Eventos', 'apollo-core' ),
				'description' => __( 'Exibe eventos em formato de calendário.', 'apollo-core' ),
				'attributes'  => array(
					'category' => array(
						'type'        => 'string',
						'default'     => '',
						'description' => __( 'Filtrar por categoria.', 'apollo-core' ),
					),
					'type'     => array(
						'type'        => 'string',
						'default'     => '',
						'description' => __( 'Filtrar por tipo.', 'apollo-core' ),
					),
					'months'   => array(
						'type'        => 'number',
						'default'     => 3,
						'description' => __( 'Quantidade de meses a exibir.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_event_calendar]',
					'[apollo_event_calendar months="6" category="festival"]',
				),
				'version'     => '2.0.0',
			)
		);

		$this->register(
			'apollo_featured_events',
			array(
				'group'       => 'events',
				'label'       => __( 'Eventos em Destaque', 'apollo-core' ),
				'description' => __( 'Exibe apenas eventos marcados como destaque.', 'apollo-core' ),
				'attributes'  => array(
					'limit'  => array(
						'type'        => 'number',
						'default'     => 4,
						'description' => __( 'Número máximo de eventos.', 'apollo-core' ),
					),
					'layout' => array(
						'type'        => 'select',
						'default'     => 'grid',
						'options'     => array( 'grid', 'carousel' ),
						'description' => __( 'Layout de exibição.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_featured_events]',
					'[apollo_featured_events limit="8" layout="carousel"]',
				),
				'version'     => '2.0.0',
			)
		);

		$this->register(
			'apollo_upcoming_events',
			array(
				'group'       => 'events',
				'label'       => __( 'Próximos Eventos', 'apollo-core' ),
				'description' => __( 'Lista compacta dos próximos eventos.', 'apollo-core' ),
				'attributes'  => array(
					'limit'  => array(
						'type'        => 'number',
						'default'     => 5,
						'description' => __( 'Número máximo de eventos.', 'apollo-core' ),
					),
					'layout' => array(
						'type'        => 'select',
						'default'     => 'list',
						'options'     => array( 'list', 'grid' ),
						'description' => __( 'Layout de exibição.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_upcoming_events]',
					'[apollo_upcoming_events limit="10"]',
				),
				'version'     => '2.0.0',
			)
		);

		$this->register(
			'apollo_event_card',
			array(
				'group'       => 'events',
				'label'       => __( 'Card de Evento', 'apollo-core' ),
				'description' => __( 'Exibe um card de evento específico.', 'apollo-core' ),
				'attributes'  => array(
					'id' => array(
						'type'        => 'number',
						'default'     => 0,
						'required'    => true,
						'description' => __( 'ID do evento.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_event_card id="123"]',
				),
				'version'     => '2.0.0',
			)
		);

		// =====================================================================
		// SOCIAL GROUP
		// =====================================================================

		$this->register(
			'apollo_social_feed',
			array(
				'group'       => 'social',
				'label'       => __( 'Feed Social', 'apollo-core' ),
				'description' => __( 'Exibe um feed de atividades sociais.', 'apollo-core' ),
				'attributes'  => array(
					'user_id'   => array(
						'type'        => 'number',
						'default'     => 0,
						'description' => __( 'ID do usuário. 0 para feed global.', 'apollo-core' ),
					),
					'limit'     => array(
						'type'        => 'number',
						'default'     => 10,
						'description' => __( 'Número de itens.', 'apollo-core' ),
					),
					'type'      => array(
						'type'        => 'select',
						'default'     => 'all',
						'options'     => array( 'all', 'post', 'photo', 'event', 'share' ),
						'description' => __( 'Tipo de atividade.', 'apollo-core' ),
					),
					'layout'    => array(
						'type'        => 'select',
						'default'     => 'timeline',
						'options'     => array( 'timeline', 'grid', 'compact' ),
						'description' => __( 'Layout de exibição.', 'apollo-core' ),
					),
					'show_form' => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Mostrar formulário de postagem.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_social_feed]',
					'[apollo_social_feed user_id="5" limit="20"]',
				),
				'version'     => '2.0.0',
			)
		);

		$this->register(
			'apollo_social_share',
			array(
				'group'       => 'social',
				'label'       => __( 'Botões de Compartilhamento', 'apollo-core' ),
				'description' => __( 'Exibe botões para compartilhar nas redes sociais.', 'apollo-core' ),
				'attributes'  => array(
					'url'      => array(
						'type'        => 'string',
						'default'     => '',
						'description' => __( 'URL a compartilhar. Padrão: URL atual.', 'apollo-core' ),
					),
					'title'    => array(
						'type'        => 'string',
						'default'     => '',
						'description' => __( 'Título a compartilhar. Padrão: título atual.', 'apollo-core' ),
					),
					'networks' => array(
						'type'        => 'string',
						'default'     => 'facebook,twitter,whatsapp,linkedin,telegram',
						'description' => __( 'Redes sociais (separadas por vírgula).', 'apollo-core' ),
					),
					'style'    => array(
						'type'        => 'select',
						'default'     => 'icons',
						'options'     => array( 'icons', 'buttons', 'minimal' ),
						'description' => __( 'Estilo de exibição.', 'apollo-core' ),
					),
					'size'     => array(
						'type'        => 'select',
						'default'     => 'md',
						'options'     => array( 'sm', 'md', 'lg' ),
						'description' => __( 'Tamanho dos botões.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_social_share]',
					'[apollo_social_share networks="facebook,whatsapp" style="buttons"]',
				),
				'version'     => '2.0.0',
			)
		);

		$this->register(
			'apollo_user_profile',
			array(
				'group'       => 'social',
				'label'       => __( 'Perfil de Usuário', 'apollo-core' ),
				'description' => __( 'Exibe o perfil completo de um usuário.', 'apollo-core' ),
				'attributes'  => array(
					'user_id'     => array(
						'type'        => 'number',
						'default'     => 0,
						'description' => __( 'ID do usuário. 0 para usuário atual.', 'apollo-core' ),
					),
					'show_cover'  => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Mostrar imagem de capa.', 'apollo-core' ),
					),
					'show_bio'    => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Mostrar biografia.', 'apollo-core' ),
					),
					'show_stats'  => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Mostrar estatísticas.', 'apollo-core' ),
					),
					'show_social' => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Mostrar links sociais.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_user_profile]',
					'[apollo_user_profile user_id="5" show_stats="false"]',
				),
				'version'     => '2.0.0',
			)
		);

		$this->register(
			'apollo_profile_card',
			array(
				'group'       => 'social',
				'label'       => __( 'Card de Perfil', 'apollo-core' ),
				'description' => __( 'Exibe um card compacto de perfil.', 'apollo-core' ),
				'attributes'  => array(
					'user_id' => array(
						'type'        => 'number',
						'default'     => 0,
						'required'    => true,
						'description' => __( 'ID do usuário.', 'apollo-core' ),
					),
					'size'    => array(
						'type'        => 'select',
						'default'     => 'md',
						'options'     => array( 'sm', 'md', 'lg' ),
						'description' => __( 'Tamanho do card.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_profile_card user_id="5"]',
				),
				'version'     => '2.0.0',
			)
		);

		$this->register(
			'apollo_classifieds',
			array(
				'group'       => 'social',
				'label'       => __( 'Classificados', 'apollo-core' ),
				'description' => __( 'Exibe lista de anúncios classificados.', 'apollo-core' ),
				'attributes'  => array(
					'limit'    => array(
						'type'        => 'number',
						'default'     => 12,
						'description' => __( 'Número de anúncios.', 'apollo-core' ),
					),
					'category' => array(
						'type'        => 'string',
						'default'     => '',
						'description' => __( 'Slug da categoria.', 'apollo-core' ),
					),
					'type'     => array(
						'type'        => 'select',
						'default'     => '',
						'options'     => array( '', 'venda', 'troca', 'doacao' ),
						'description' => __( 'Tipo de anúncio.', 'apollo-core' ),
					),
					'user_id'  => array(
						'type'        => 'number',
						'default'     => 0,
						'description' => __( 'Filtrar por usuário.', 'apollo-core' ),
					),
					'layout'   => array(
						'type'        => 'select',
						'default'     => 'grid',
						'options'     => array( 'grid', 'list' ),
						'description' => __( 'Layout de exibição.', 'apollo-core' ),
					),
					'columns'  => array(
						'type'        => 'number',
						'default'     => 3,
						'description' => __( 'Número de colunas.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_classifieds]',
					'[apollo_classifieds category="equipamentos" type="venda"]',
				),
				'version'     => '2.0.0',
			)
		);

		$this->register(
			'apollo_classified_form',
			array(
				'group'       => 'social',
				'label'       => __( 'Formulário de Classificado', 'apollo-core' ),
				'description' => __( 'Formulário para publicar anúncios.', 'apollo-core' ),
				'attributes'  => array(),
				'examples'    => array(
					'[apollo_classified_form]',
				),
				'version'     => '2.0.0',
			)
		);

		$this->register(
			'apollo_user_dashboard',
			array(
				'group'       => 'social',
				'label'       => __( 'Painel do Usuário', 'apollo-core' ),
				'description' => __( 'Painel com estatísticas e gerenciamento.', 'apollo-core' ),
				'attributes'  => array(
					'show_events'      => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Mostrar aba de eventos.', 'apollo-core' ),
					),
					'show_classifieds' => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Mostrar aba de anúncios.', 'apollo-core' ),
					),
					'show_activity'    => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Mostrar aba de atividade.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_user_dashboard]',
				),
				'version'     => '2.0.0',
			)
		);

		$this->register(
			'apollo_follow_button',
			array(
				'group'       => 'social',
				'label'       => __( 'Botão Seguir', 'apollo-core' ),
				'description' => __( 'Botão para seguir um usuário.', 'apollo-core' ),
				'attributes'  => array(
					'user_id' => array(
						'type'        => 'number',
						'default'     => 0,
						'required'    => true,
						'description' => __( 'ID do usuário a seguir.', 'apollo-core' ),
					),
					'size'    => array(
						'type'        => 'select',
						'default'     => 'md',
						'options'     => array( 'sm', 'md', 'lg' ),
						'description' => __( 'Tamanho do botão.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_follow_button user_id="5"]',
				),
				'version'     => '2.0.0',
			)
		);

		// =====================================================================
		// RIO (PERFORMANCE) GROUP
		// =====================================================================

		$this->register(
			'apollo_rio_optimized',
			array(
				'group'       => 'rio',
				'label'       => __( 'Conteúdo Otimizado', 'apollo-core' ),
				'description' => __( 'Wrapper para otimização automática de conteúdo.', 'apollo-core' ),
				'attributes'  => array(
					'priority'    => array(
						'type'        => 'select',
						'default'     => 'normal',
						'options'     => array( 'high', 'normal', 'low' ),
						'description' => __( 'Prioridade de carregamento.', 'apollo-core' ),
					),
					'cache'       => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Habilitar cache.', 'apollo-core' ),
					),
					'lazy_images' => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Lazy load em imagens.', 'apollo-core' ),
					),
					'defer_js'    => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Diferir scripts.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_rio_optimized]Conteúdo aqui[/apollo_rio_optimized]',
				),
				'supports'    => array( 'enclosing' ),
				'version'     => '1.0.0',
			)
		);

		$this->register(
			'apollo_rio_lazy',
			array(
				'group'       => 'rio',
				'label'       => __( 'Lazy Load', 'apollo-core' ),
				'description' => __( 'Carrega conteúdo apenas quando visível.', 'apollo-core' ),
				'attributes'  => array(
					'threshold'   => array(
						'type'        => 'string',
						'default'     => '100px',
						'description' => __( 'Margem do viewport.', 'apollo-core' ),
					),
					'placeholder' => array(
						'type'        => 'select',
						'default'     => 'skeleton',
						'options'     => array( 'skeleton', 'spinner', 'pulse', 'none' ),
						'description' => __( 'Tipo de placeholder.', 'apollo-core' ),
					),
					'animation'   => array(
						'type'        => 'select',
						'default'     => 'fade',
						'options'     => array( 'fade', 'slide', 'none' ),
						'description' => __( 'Animação de entrada.', 'apollo-core' ),
					),
					'height'      => array(
						'type'        => 'string',
						'default'     => 'auto',
						'description' => __( 'Altura mínima.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_rio_lazy]Conteúdo pesado[/apollo_rio_lazy]',
					'[apollo_rio_lazy placeholder="spinner" height="300px"]...[/apollo_rio_lazy]',
				),
				'supports'    => array( 'enclosing' ),
				'version'     => '1.0.0',
			)
		);

		$this->register(
			'apollo_rio_skeleton',
			array(
				'group'       => 'rio',
				'label'       => __( 'Skeleton Loader', 'apollo-core' ),
				'description' => __( 'Placeholder animado durante carregamento.', 'apollo-core' ),
				'attributes'  => array(
					'type'   => array(
						'type'        => 'select',
						'default'     => 'text',
						'options'     => array( 'text', 'card', 'content', 'avatar', 'image' ),
						'description' => __( 'Tipo de skeleton.', 'apollo-core' ),
					),
					'lines'  => array(
						'type'        => 'number',
						'default'     => 3,
						'description' => __( 'Número de linhas.', 'apollo-core' ),
					),
					'height' => array(
						'type'        => 'string',
						'default'     => '1rem',
						'description' => __( 'Altura das linhas.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_rio_skeleton type="card"]',
					'[apollo_rio_skeleton type="text" lines="5"]',
				),
				'version'     => '1.0.0',
			)
		);

		$this->register(
			'apollo_rio_image',
			array(
				'group'       => 'rio',
				'label'       => __( 'Imagem Progressiva', 'apollo-core' ),
				'description' => __( 'Imagem com carregamento progressivo (LQIP).', 'apollo-core' ),
				'attributes'  => array(
					'src'    => array(
						'type'        => 'string',
						'default'     => '',
						'required'    => true,
						'description' => __( 'URL da imagem.', 'apollo-core' ),
					),
					'alt'    => array(
						'type'        => 'string',
						'default'     => '',
						'description' => __( 'Texto alternativo.', 'apollo-core' ),
					),
					'width'  => array(
						'type'        => 'string',
						'default'     => '',
						'description' => __( 'Largura.', 'apollo-core' ),
					),
					'height' => array(
						'type'        => 'string',
						'default'     => '',
						'description' => __( 'Altura.', 'apollo-core' ),
					),
					'lqip'   => array(
						'type'        => 'string',
						'default'     => '',
						'description' => __( 'URL do placeholder de baixa qualidade.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_rio_image src="/img/photo.jpg" alt="Foto"]',
				),
				'version'     => '1.0.0',
			)
		);

		$this->register(
			'apollo_rio_defer',
			array(
				'group'       => 'rio',
				'label'       => __( 'Conteúdo Diferido', 'apollo-core' ),
				'description' => __( 'Carrega conteúdo após o carregamento da página.', 'apollo-core' ),
				'attributes'  => array(
					'delay'       => array(
						'type'        => 'number',
						'default'     => 0,
						'description' => __( 'Delay em ms após page load.', 'apollo-core' ),
					),
					'event'       => array(
						'type'        => 'select',
						'default'     => 'load',
						'options'     => array( 'load', 'DOMContentLoaded', 'idle' ),
						'description' => __( 'Evento para iniciar.', 'apollo-core' ),
					),
					'placeholder' => array(
						'type'        => 'select',
						'default'     => 'skeleton',
						'options'     => array( 'skeleton', 'spinner', 'none' ),
						'description' => __( 'Tipo de placeholder.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_rio_defer delay="1000"]Conteúdo não crítico[/apollo_rio_defer]',
				),
				'supports'    => array( 'enclosing' ),
				'version'     => '1.0.0',
			)
		);

		$this->register(
			'apollo_rio_prefetch',
			array(
				'group'       => 'rio',
				'label'       => __( 'Prefetch', 'apollo-core' ),
				'description' => __( 'Adiciona hints de prefetch para recursos.', 'apollo-core' ),
				'attributes'  => array(
					'urls' => array(
						'type'        => 'string',
						'default'     => '',
						'required'    => true,
						'description' => __( 'URLs separadas por vírgula.', 'apollo-core' ),
					),
					'type' => array(
						'type'        => 'select',
						'default'     => 'prefetch',
						'options'     => array( 'prefetch', 'preload', 'preconnect', 'dns-prefetch' ),
						'description' => __( 'Tipo de hint.', 'apollo-core' ),
					),
					'as'   => array(
						'type'        => 'string',
						'default'     => '',
						'description' => __( 'Tipo de recurso (para preload).', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_rio_prefetch urls="/page2,/page3"]',
					'[apollo_rio_prefetch urls="/fonts/main.woff2" type="preload" as="font"]',
				),
				'version'     => '1.0.0',
			)
		);

		$this->register(
			'apollo_rio_debug',
			array(
				'group'       => 'rio',
				'label'       => __( 'Debug Info', 'apollo-core' ),
				'description' => __( 'Informações de performance (apenas dev).', 'apollo-core' ),
				'attributes'  => array(
					'show' => array(
						'type'        => 'select',
						'default'     => 'all',
						'options'     => array( 'all', 'memory', 'queries', 'time' ),
						'description' => __( 'Tipo de informação.', 'apollo-core' ),
					),
				),
				'examples'    => array(
					'[apollo_rio_debug]',
				),
				'version'     => '1.0.0',
			)
		);

		/**
		 * Allow other plugins to register shortcodes
		 *
		 * @param Apollo_Shortcode_Registry $registry Registry instance.
		 */
		do_action( 'apollo_register_shortcodes', $this );
	}

	// =========================================================================
	// CONFLICT DETECTION
	// =========================================================================

	/**
	 * Detect shortcode conflicts
	 *
	 * @return void
	 */
	public function detect_conflicts(): void {
		global $shortcode_tags;

		$this->conflicts = array();

		foreach ( $this->shortcodes as $tag => $definition ) {
			// Check if shortcode was registered by another plugin.
			if ( isset( $shortcode_tags[ $tag ] ) ) {
				$registered_callback = $shortcode_tags[ $tag ];

				// Check if it's not from an Apollo class.
				$is_apollo = false;

				if ( is_array( $registered_callback ) && is_object( $registered_callback[0] ) ) {
					$class_name = get_class( $registered_callback[0] );
					$is_apollo  = str_contains( $class_name, 'Apollo' );
				} elseif ( is_string( $registered_callback ) ) {
					$is_apollo = str_starts_with( $registered_callback, 'apollo_' );
				}

				if ( ! $is_apollo ) {
					$this->conflicts[] = array(
						'tag'             => $tag,
						'apollo_plugin'   => $definition['group'],
						'conflict_source' => $this->identify_callback_source( $registered_callback ),
					);
				}
			}
		}
	}

	/**
	 * Identify the source of a callback
	 *
	 * @param mixed $callback Callback.
	 * @return string
	 */
	private function identify_callback_source( $callback ): string {
		if ( is_array( $callback ) && is_object( $callback[0] ) ) {
			return get_class( $callback[0] );
		} elseif ( is_array( $callback ) && is_string( $callback[0] ) ) {
			return $callback[0];
		} elseif ( is_string( $callback ) ) {
			return $callback;
		}
		return __( 'Desconhecido', 'apollo-core' );
	}

	/**
	 * Display conflict notices in admin
	 *
	 * @return void
	 */
	public function display_conflict_notices(): void {
		if ( empty( $this->conflicts ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Apollo Core:', 'apollo-core' ); ?></strong>
				<?php esc_html_e( 'Conflitos de shortcode detectados:', 'apollo-core' ); ?>
			</p>
			<ul style="margin-left: 20px; list-style: disc;">
				<?php foreach ( $this->conflicts as $conflict ) : ?>
					<li>
						<code>[<?php echo esc_html( $conflict['tag'] ); ?>]</code> -
						<?php
						echo esc_html(
							sprintf(
							/* translators: %s: source class/function name */
								__( 'Registrado por: %s', 'apollo-core' ),
								$conflict['conflict_source']
							)
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	// =========================================================================
	// GETTERS
	// =========================================================================

	/**
	 * Get all registered shortcodes
	 *
	 * @return array
	 */
	public function get_all(): array {
		return $this->shortcodes;
	}

	/**
	 * Get shortcode by tag
	 *
	 * @param string $tag Shortcode tag.
	 * @return array|null
	 */
	public function get( string $tag ): ?array {
		return $this->shortcodes[ $tag ] ?? null;
	}

	/**
	 * Get shortcodes by group
	 *
	 * @param string $group Group key.
	 * @return array
	 */
	public function get_by_group( string $group ): array {
		return array_filter( $this->shortcodes, fn( $s ) => $s['group'] === $group );
	}

	/**
	 * Get all groups
	 *
	 * @return array
	 */
	public function get_groups(): array {
		return $this->groups;
	}

	/**
	 * Get detected conflicts
	 *
	 * @return array
	 */
	public function get_conflicts(): array {
		return $this->conflicts;
	}

	// =========================================================================
	// DOCUMENTATION GENERATION
	// =========================================================================

	/**
	 * Generate markdown documentation
	 *
	 * @return string
	 */
	public function generate_documentation(): string {
		$doc  = "# Apollo Shortcodes Reference\n\n";
		$doc .= "Documentação gerada automaticamente.\n\n";
		$doc .= "---\n\n";

		foreach ( $this->groups as $group_key => $group ) {
			$shortcodes = $this->get_by_group( $group_key );

			if ( empty( $shortcodes ) ) {
				continue;
			}

			$doc .= "## {$group['label']}\n\n";
			$doc .= "{$group['description']}\n\n";

			foreach ( $shortcodes as $tag => $definition ) {
				$doc .= "### `[{$tag}]`\n\n";
				$doc .= "{$definition['description']}\n\n";

				if ( ! empty( $definition['attributes'] ) ) {
					$doc .= "**Atributos:**\n\n";
					$doc .= "| Atributo | Tipo | Padrão | Descrição |\n";
					$doc .= "|----------|------|--------|----------|\n";

					foreach ( $definition['attributes'] as $attr_name => $attr ) {
						$default = $attr['default'] ?? '';
						if ( is_bool( $default ) ) {
							$default = $default ? 'true' : 'false';
						}
						$doc .= "| `{$attr_name}` | {$attr['type']} | `{$default}` | {$attr['description']} |\n";
					}
					$doc .= "\n";
				}

				if ( ! empty( $definition['examples'] ) ) {
					$doc .= "**Exemplos:**\n\n";
					foreach ( $definition['examples'] as $example ) {
						$doc .= "```\n{$example}\n```\n\n";
					}
				}

				$doc .= "---\n\n";
			}
		}

		return $doc;
	}

	// =========================================================================
	// ADMIN PAGE
	// =========================================================================

	/**
	 * Add admin page
	 *
	 * @return void
	 */
	public function add_admin_page(): void {
		add_submenu_page(
			'tools.php',
			__( 'Apollo Shortcodes', 'apollo-core' ),
			__( 'Apollo Shortcodes', 'apollo-core' ),
			'manage_options',
			'apollo-shortcodes',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render admin page
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Apollo Shortcodes Reference', 'apollo-core' ); ?></h1>

			<?php if ( ! empty( $this->conflicts ) ) : ?>
				<div class="notice notice-warning">
					<p><?php esc_html_e( 'Conflitos detectados. Verifique as notificações acima.', 'apollo-core' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="nav-tab-wrapper">
				<?php foreach ( $this->groups as $group_key => $group ) : ?>
					<?php $shortcodes = $this->get_by_group( $group_key ); ?>
					<?php if ( ! empty( $shortcodes ) ) : ?>
						<a href="#<?php echo esc_attr( $group_key ); ?>" class="nav-tab"><?php echo esc_html( $group['label'] ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<?php foreach ( $this->groups as $group_key => $group ) : ?>
				<?php $shortcodes = $this->get_by_group( $group_key ); ?>
				<?php if ( ! empty( $shortcodes ) ) : ?>
					<div id="<?php echo esc_attr( $group_key ); ?>" class="apollo-shortcode-group">
						<h2><?php echo esc_html( $group['label'] ); ?></h2>
						<p class="description"><?php echo esc_html( $group['description'] ); ?></p>

						<table class="wp-list-table widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Shortcode', 'apollo-core' ); ?></th>
									<th><?php esc_html_e( 'Descrição', 'apollo-core' ); ?></th>
									<th><?php esc_html_e( 'Exemplo', 'apollo-core' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $shortcodes as $tag => $definition ) : ?>
									<tr>
										<td><code>[<?php echo esc_html( $tag ); ?>]</code></td>
										<td><?php echo esc_html( $definition['description'] ); ?></td>
										<td>
											<?php if ( ! empty( $definition['examples'][0] ) ) : ?>
												<code><?php echo esc_html( $definition['examples'][0] ); ?></code>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	// =========================================================================
	// REST API
	// =========================================================================

	/**
	 * Register REST routes
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'apollo/v1',
			'/shortcodes',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_shortcodes' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apollo/v1',
			'/shortcodes/(?P<tag>[a-z_]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_shortcode' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST: Get all shortcodes
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_shortcodes(): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'groups'     => $this->groups,
				'shortcodes' => $this->shortcodes,
			)
		);
	}

	/**
	 * REST: Get single shortcode
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function rest_get_shortcode( \WP_REST_Request $request ): \WP_REST_Response {
		$tag       = $request->get_param( 'tag' );
		$shortcode = $this->get( $tag );

		if ( ! $shortcode ) {
			return new \WP_REST_Response( array( 'error' => 'Not found' ), 404 );
		}

		return new \WP_REST_Response( $shortcode );
	}

	// =========================================================================
	// SHORTCODE FINDER MODAL
	// =========================================================================

	/**
	 * Render shortcode finder modal
	 *
	 * @return void
	 */
	public function render_shortcode_finder_modal(): void {
		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->base, array( 'post', 'page' ), true ) ) {
			return;
		}

		?>
		<div id="apollo-shortcode-finder" style="display: none;">
			<div class="apollo-sf-overlay"></div>
			<div class="apollo-sf-modal">
				<div class="apollo-sf-header">
					<h2><?php esc_html_e( 'Apollo Shortcodes', 'apollo-core' ); ?></h2>
					<input type="search" id="apollo-sf-search" placeholder="<?php esc_attr_e( 'Buscar shortcode...', 'apollo-core' ); ?>">
					<button type="button" class="apollo-sf-close">&times;</button>
				</div>
				<div class="apollo-sf-body">
					<?php foreach ( $this->groups as $group_key => $group ) : ?>
						<?php $shortcodes = $this->get_by_group( $group_key ); ?>
						<?php if ( ! empty( $shortcodes ) ) : ?>
							<div class="apollo-sf-group">
								<h3><?php echo esc_html( $group['label'] ); ?></h3>
								<div class="apollo-sf-list">
									<?php foreach ( $shortcodes as $tag => $definition ) : ?>
										<button type="button" class="apollo-sf-item" data-shortcode="<?php echo esc_attr( $tag ); ?>">
											<span class="apollo-sf-tag">[<?php echo esc_html( $tag ); ?>]</span>
											<span class="apollo-sf-desc"><?php echo esc_html( $definition['description'] ); ?></span>
										</button>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<style>
			.apollo-sf-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100000; }
			.apollo-sf-modal { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 8px; width: 600px; max-height: 80vh; overflow: hidden; z-index: 100001; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
			.apollo-sf-header { padding: 16px; border-bottom: 1px solid #ddd; display: flex; align-items: center; gap: 12px; }
			.apollo-sf-header h2 { margin: 0; flex-shrink: 0; }
			.apollo-sf-header input { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
			.apollo-sf-close { background: none; border: none; font-size: 24px; cursor: pointer; padding: 0 8px; }
			.apollo-sf-body { padding: 16px; overflow-y: auto; max-height: calc(80vh - 70px); }
			.apollo-sf-group h3 { margin: 0 0 8px; font-size: 13px; text-transform: uppercase; color: #666; }
			.apollo-sf-list { display: grid; gap: 8px; margin-bottom: 16px; }
			.apollo-sf-item { display: flex; flex-direction: column; align-items: flex-start; padding: 12px; border: 1px solid #ddd; border-radius: 4px; background: #fff; cursor: pointer; text-align: left; transition: all 0.2s; }
			.apollo-sf-item:hover { border-color: #2271b1; background: #f0f7fc; }
			.apollo-sf-tag { font-family: monospace; font-weight: 600; color: #2271b1; }
			.apollo-sf-desc { font-size: 12px; color: #666; margin-top: 4px; }
		</style>
		<?php
	}
}

/**
 * Get registry instance
 *
 * @return Apollo_Shortcode_Registry
 */
function apollo_shortcode_registry(): Apollo_Shortcode_Registry {
	return Apollo_Shortcode_Registry::get_instance();
}

// Initialize.
add_action(
	'plugins_loaded',
	function () {
		Apollo_Shortcode_Registry::get_instance();
	},
	5
);
