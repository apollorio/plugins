<?php
/**
 * Apollo ShadCN/Tailwind Loader
 * Sistema centralizado para carregar Tailwind CSS e ShadCN UI em todos os plugins Apollo
 *
 * @package Apollo Social
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe para gerenciar carregamento de Tailwind + ShadCN
 */
class Apollo_ShadCN_Loader {

	private static $instance        = null;
	private static $tailwind_loaded = false;
	private static $shadcn_loaded   = false;

	/**
	 * Singleton instance
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Inicializa o loader
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tailwind' ], 5 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_shadcn' ], 10 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_tailwind' ], 5 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_shadcn' ], 10 );
	}

	/**
	 * Carrega Tailwind CSS (via CDN)
	 */
	public function enqueue_tailwind() {
		if ( self::$tailwind_loaded ) {
			return;
		}

		// Verificar se já foi carregado por outro plugin
		if ( wp_script_is( 'tailwind-cdn', 'enqueued' ) || wp_script_is( 'tailwind-cdn', 'registered' ) ) {
			self::$tailwind_loaded = true;

			return;
		}

		// Carregar Tailwind via CDN
		wp_enqueue_script(
			'apollo-tailwind-cdn',
			'https://cdn.tailwindcss.com',
			[],
			'3.4.0',
			false
			// false = carrega no <head>
		);

		// Configuração Tailwind customizada
		add_action( 'wp_head', [ $this, 'tailwind_config' ], 1 );
		add_action( 'admin_head', [ $this, 'tailwind_config' ], 1 );

		self::$tailwind_loaded = true;
	}

	/**
	 * Configuração Tailwind customizada
	 */
	public function tailwind_config() {
		?>
		<script>
		if (typeof tailwind !== 'undefined') {
			tailwind.config = {
				theme: {
					extend: {
						colors: {
							border: "hsl(var(--border))",
							input: "hsl(var(--input))",
							ring: "hsl(var(--ring))",
							background: "hsl(var(--background))",
							foreground: "hsl(var(--foreground))",
							primary: {
								DEFAULT: "hsl(var(--primary))",
								foreground: "hsl(var(--primary-foreground))",
							},
							secondary: {
								DEFAULT: "hsl(var(--secondary))",
								foreground: "hsl(var(--secondary-foreground))",
							},
							destructive: {
								DEFAULT: "hsl(var(--destructive))",
								foreground: "hsl(var(--destructive-foreground))",
							},
							muted: {
								DEFAULT: "hsl(var(--muted))",
								foreground: "hsl(var(--muted-foreground))",
							},
							accent: {
								DEFAULT: "hsl(var(--accent))",
								foreground: "hsl(var(--accent-foreground))",
							},
							popover: {
								DEFAULT: "hsl(var(--popover))",
								foreground: "hsl(var(--popover-foreground))",
							},
							card: {
								DEFAULT: "hsl(var(--card))",
								foreground: "hsl(var(--card-foreground))",
							},
						},
						borderRadius: {
							lg: "var(--radius)",
							md: "calc(var(--radius) - 2px)",
							sm: "calc(var(--radius) - 4px)",
						},
					},
				},
			};
		}
		</script>
		<?php
	}

	/**
	 * Carrega ShadCN UI Components CSS
	 */
	public function enqueue_shadcn() {
		if ( self::$shadcn_loaded ) {
			return;
		}

		// Verificar se já foi carregado
		if ( wp_style_is( 'shadcn-ui', 'enqueued' ) || wp_style_is( 'shadcn-ui', 'registered' ) ) {
			self::$shadcn_loaded = true;

			return;
		}

		// Carregar RemixIcon (requerido pelo ShadCN)
		wp_enqueue_style(
			'remixicon',
			'https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css',
			[],
			'4.7.0'
		);

		// Carregar uni.css (base Apollo - compatível com ShadCN)
		wp_enqueue_style(
			'apollo-uni-css',
			'https://assets.apollo.rio.br/uni.css',
			[],
			'2.0.0'
		);

		// Carregar CSS ShadCN customizado Apollo
		wp_enqueue_style(
			'apollo-shadcn-base',
			APOLLO_SOCIAL_PLUGIN_URL . 'assets/css/shadcn-base.css',
			[ 'apollo-uni-css', 'remixicon' ],
			APOLLO_SOCIAL_VERSION
		);

		// Variáveis CSS ShadCN
		add_action( 'wp_head', [ $this, 'shadcn_variables' ], 2 );
		add_action( 'admin_head', [ $this, 'shadcn_variables' ], 2 );

		self::$shadcn_loaded = true;
	}

	/**
	 * Define variáveis CSS ShadCN
	 */
	public function shadcn_variables() {
		?>
		<style>
		:root {
			--background: 0 0% 100%;
			--foreground: 222.2 84% 4.9%;
			--card: 0 0% 100%;
			--card-foreground: 222.2 84% 4.9%;
			--popover: 0 0% 100%;
			--popover-foreground: 222.2 84% 4.9%;
			--primary: 222.2 47.4% 11.2%;
			--primary-foreground: 210 40% 98%;
			--secondary: 210 40% 96.1%;
			--secondary-foreground: 222.2 47.4% 11.2%;
			--muted: 210 40% 96.1%;
			--muted-foreground: 215.4 16.3% 46.9%;
			--accent: 210 40% 96.1%;
			--accent-foreground: 222.2 47.4% 11.2%;
			--destructive: 0 84.2% 60.2%;
			--destructive-foreground: 210 40% 98%;
			--border: 214.3 31.8% 91.4%;
			--input: 214.3 31.8% 91.4%;
			--ring: 222.2 84% 4.9%;
			--radius: 0.5rem;
		}
		
		.dark {
			--background: 222.2 84% 4.9%;
			--foreground: 210 40% 98%;
			--card: 222.2 84% 4.9%;
			--card-foreground: 210 40% 98%;
			--popover: 222.2 84% 4.9%;
			--popover-foreground: 210 40% 98%;
			--primary: 210 40% 98%;
			--primary-foreground: 222.2 47.4% 11.2%;
			--secondary: 217.2 32.6% 17.5%;
			--secondary-foreground: 210 40% 98%;
			--muted: 217.2 32.6% 17.5%;
			--muted-foreground: 215 20.2% 65.1%;
			--accent: 217.2 32.6% 17.5%;
			--accent-foreground: 210 40% 98%;
			--destructive: 0 62.8% 30.6%;
			--destructive-foreground: 210 40% 98%;
			--border: 217.2 32.6% 17.5%;
			--input: 217.2 32.6% 17.5%;
			--ring: 212.7 26.8% 83.9%;
		}
		</style>
		<?php
	}

	/**
	 * Verifica se Tailwind está carregado
	 */
	public static function is_tailwind_loaded() {
		return self::$tailwind_loaded || wp_script_is( 'tailwind-cdn', 'enqueued' ) || wp_script_is( 'apollo-tailwind-cdn', 'enqueued' );
	}

	/**
	 * Verifica se ShadCN está carregado
	 */
	public static function is_shadcn_loaded() {
		return self::$shadcn_loaded || wp_style_is( 'shadcn-ui', 'enqueued' ) || wp_style_is( 'apollo-shadcn-base', 'enqueued' );
	}
}

/**
 * Inicializa o loader
 */
function apollo_shadcn_init() {
	// Verificar se outros plugins Apollo estão ativos
	$apollo_events_active = function_exists( 'apollo_events_manager_activate' ) || class_exists( 'Apollo_Events_Manager_Plugin' );
	$apollo_rio_active    = function_exists( 'apollo_rio_init' ) || class_exists( 'Apollo_PWA_Page_Builders' );

	// Carregar apenas se pelo menos um plugin Apollo estiver ativo
	if ( $apollo_events_active || $apollo_rio_active || function_exists( 'apollo_social_bootstrap' ) ) {
		Apollo_ShadCN_Loader::get_instance();
	}
}
add_action( 'plugins_loaded', 'apollo_shadcn_init', 5 );
