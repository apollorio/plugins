<?php
/**
 * Apollo Base Elementor Widget
 *
 * Abstract base class for all Apollo Elementor widgets.
 * Provides common functionality and helper methods.
 *
 * @package Apollo_Core
 * @subpackage Elementor
 * @since 2.0.0
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure Elementor is loaded.
if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Class Apollo_Base_Widget
 *
 * Abstract base widget for Apollo Elementor integration.
 */
abstract class Apollo_Base_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget category.
	 *
	 * @var string
	 */
	protected string $widget_category = 'apollo';

	/**
	 * Get widget categories.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories(): array {
		return array( $this->widget_category );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords(): array {
		return array( 'apollo' );
	}

	/**
	 * Get custom help URL.
	 *
	 * @return string Help URL.
	 */
	public function get_custom_help_url(): string {
		return 'https://apollo.rio.br/docs/elementor-widgets/';
	}

	/**
	 * Register layout controls.
	 *
	 * @param array $options Layout options.
	 * @return void
	 */
	protected function register_layout_controls( array $options = array() ): void {
		$defaults = array(
			'layouts'        => array(
				'grid'     => __( 'Grade', 'apollo-core' ),
				'list'     => __( 'Lista', 'apollo-core' ),
				'carousel' => __( 'Carrossel', 'apollo-core' ),
			),
			'default_layout' => 'grid',
			'columns'        => true,
			'default_cols'   => 3,
			'limit'          => true,
			'default_limit'  => 6,
		);

		$options = wp_parse_args( $options, $defaults );

		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Layout', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => $options['default_layout'],
				'options' => $options['layouts'],
			)
		);

		if ( $options['columns'] ) {
			$this->add_responsive_control(
				'columns',
				array(
					'label'     => esc_html__( 'Colunas', 'apollo-core' ),
					'type'      => \Elementor\Controls_Manager::SELECT,
					'default'   => (string) $options['default_cols'],
					'options'   => array(
						'1' => '1',
						'2' => '2',
						'3' => '3',
						'4' => '4',
						'5' => '5',
						'6' => '6',
					),
					'condition' => array(
						'layout!' => 'carousel',
					),
				)
			);
		}

		if ( $options['limit'] ) {
			$this->add_control(
				'limit',
				array(
					'label'   => esc_html__( 'Quantidade', 'apollo-core' ),
					'type'    => \Elementor\Controls_Manager::NUMBER,
					'default' => $options['default_limit'],
					'min'     => 1,
					'max'     => 50,
				)
			);
		}
	}

	/**
	 * Register taxonomy select control.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param string $label    Control label.
	 * @param array  $args     Additional args.
	 * @return void
	 */
	protected function register_taxonomy_control( string $taxonomy, string $label, array $args = array() ): void {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		$options = array( '' => __( 'Todas', 'apollo-core' ) );

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}

		$control_args = wp_parse_args(
			$args,
			array(
				'label'    => $label,
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'options'  => $options,
				'multiple' => true,
				'default'  => array(),
			)
		);

		$this->add_control( sanitize_key( $taxonomy ), $control_args );
	}

	/**
	 * Register order controls.
	 *
	 * @param array $orderby_options Custom orderby options.
	 * @return void
	 */
	protected function register_order_controls( array $orderby_options = array() ): void {
		if ( empty( $orderby_options ) ) {
			$orderby_options = array(
				'date'  => __( 'Data', 'apollo-core' ),
				'title' => __( 'Título', 'apollo-core' ),
				'rand'  => __( 'Aleatório', 'apollo-core' ),
			);
		}

		$this->add_control(
			'orderby',
			array(
				'label'   => esc_html__( 'Ordenar por', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => array_key_first( $orderby_options ),
				'options' => $orderby_options,
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => esc_html__( 'Ordem', 'apollo-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'desc',
				'options' => array(
					'asc'  => __( 'Crescente', 'apollo-core' ),
					'desc' => __( 'Decrescente', 'apollo-core' ),
				),
			)
		);
	}

	/**
	 * Register display toggle controls.
	 *
	 * @param array $toggles Toggle definitions [ 'key' => 'label' ].
	 * @param array $defaults Default values [ 'key' => true/false ].
	 * @return void
	 */
	protected function register_display_toggles( array $toggles, array $defaults = array() ): void {
		foreach ( $toggles as $key => $label ) {
			$default = $defaults[ $key ] ?? true;

			$this->add_control(
				'show_' . $key,
				array(
					'label'        => $label,
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'Sim', 'apollo-core' ),
					'label_off'    => esc_html__( 'Não', 'apollo-core' ),
					'return_value' => 'yes',
					'default'      => $default ? 'yes' : '',
				)
			);
		}
	}

	/**
	 * Register carousel controls.
	 *
	 * @return void
	 */
	protected function register_carousel_controls(): void {
		$this->add_control(
			'carousel_heading',
			array(
				'label'     => esc_html__( 'Configurações do Carrossel', 'apollo-core' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'layout' => 'carousel',
				),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => esc_html__( 'Autoplay', 'apollo-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'layout' => 'carousel',
				),
			)
		);

		$this->add_control(
			'autoplay_speed',
			array(
				'label'     => esc_html__( 'Velocidade (ms)', 'apollo-core' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 5000,
				'min'       => 1000,
				'max'       => 15000,
				'step'      => 500,
				'condition' => array(
					'layout'   => 'carousel',
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_navigation',
			array(
				'label'        => esc_html__( 'Navegação', 'apollo-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'layout' => 'carousel',
				),
			)
		);

		$this->add_control(
			'show_pagination',
			array(
				'label'        => esc_html__( 'Paginação', 'apollo-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'layout' => 'carousel',
				),
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'        => esc_html__( 'Loop Infinito', 'apollo-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'layout' => 'carousel',
				),
			)
		);
	}

	/**
	 * Get toggle value as boolean.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $key      Setting key.
	 * @return bool
	 */
	protected function get_toggle_value( array $settings, string $key ): bool {
		$full_key = str_starts_with( $key, 'show_' ) ? $key : 'show_' . $key;
		return ! empty( $settings[ $full_key ] ) && 'yes' === $settings[ $full_key ];
	}

	/**
	 * Convert Elementor settings to shortcode attributes.
	 *
	 * @param array $settings Widget settings.
	 * @param array $mapping  Mapping of widget setting to shortcode attribute.
	 * @return array
	 */
	protected function settings_to_attributes( array $settings, array $mapping = array() ): array {
		$attributes = array();

		foreach ( $mapping as $elementor_key => $shortcode_key ) {
			if ( isset( $settings[ $elementor_key ] ) ) {
				$value = $settings[ $elementor_key ];

				// Convert switcher values.
				if ( 'yes' === $value ) {
					$value = true;
				} elseif ( '' === $value && str_starts_with( $elementor_key, 'show_' ) ) {
					$value = false;
				}

				// Convert arrays to comma-separated.
				if ( is_array( $value ) ) {
					$value = implode( ',', array_filter( $value ) );
				}

				$attributes[ $shortcode_key ] = $value;
			}
		}

		return $attributes;
	}

	/**
	 * Render using Template Loader.
	 *
	 * @param string $template Template name.
	 * @param array  $data     Template data.
	 * @return void
	 */
	protected function render_template( string $template, array $data = array() ): void {
		if ( class_exists( 'Apollo_Template_Loader' ) ) {
			Apollo_Template_Loader::load( $template, $data );
		} else {
			$this->render_placeholder( __( 'Template Loader não disponível.', 'apollo-core' ) );
		}
	}

	/**
	 * Render placeholder for editor.
	 *
	 * @param string $message Placeholder message.
	 * @param string $icon    Remix icon class.
	 * @return void
	 */
	protected function render_placeholder( string $message, string $icon = 'ri-apps-2-line' ): void {
		?>
		<div class="apollo-elementor-placeholder">
			<i class="<?php echo esc_attr( $icon ); ?>"></i>
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render widget output.
	 *
	 * Child classes should override this method.
	 *
	 * @return void
	 */
	protected function render(): void {
		$this->render_placeholder( __( 'Widget não configurado.', 'apollo-core' ) );
	}
}
