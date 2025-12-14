<?php
declare(strict_types=1);

/**
 * Apollo Core - Co-autores Settings Tab
 *
 * Comprehensive co-author permissions for all CPTs:
 * - event_listing (Eventos)
 * - event_dj (DJs)
 * - event_local (Locais)
 * - nucleo (Núcleos)
 * - comuna (Comunidades)
 * - apollo_social_post (Posts Sociais)
 *
 * @package Apollo_Core
 * @since 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Co-authors Settings class
 */
class Apollo_Coauthors_Settings {

	/**
	 * Option key for co-authors settings
	 */
	public const OPTION_KEY = 'apollo_coauthors_settings';

	/**
	 * Supported CPTs for co-authors
	 *
	 * @var array
	 */
	private static $cpts = array(
		'event_listing'      => array(
			'label'        => 'Eventos',
			'label_single' => 'Evento',
			'icon'         => 'dashicons-calendar-alt',
			'extra'        => false,
		),
		'event_dj'           => array(
			'label'        => 'DJs',
			'label_single' => 'DJ',
			'icon'         => 'dashicons-format-audio',
			'extra'        => false,
		),
		'event_local'        => array(
			'label'        => 'Locais',
			'label_single' => 'Local',
			'icon'         => 'dashicons-location',
			'extra'        => false,
		),
		'nucleo'             => array(
			'label'        => 'Núcleos',
			'label_single' => 'Núcleo',
			'icon'         => 'dashicons-groups',
			'extra'        => 'nucleo',
		),
		'comuna'             => array(
			'label'        => 'Comunidades',
			'label_single' => 'Comunidade',
			'icon'         => 'dashicons-networking',
			'extra'        => 'comuna',
		),
		'apollo_social_post' => array(
			'label'        => 'Posts Sociais',
			'label_single' => 'Post Social',
			'icon'         => 'dashicons-share',
			'extra'        => false,
		),
	);

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'admin_post_apollo_save_coauthors_settings', array( __CLASS__, 'save_settings' ) );
	}

	/**
	 * Get default settings for a CPT
	 *
	 * @param string $cpt CPT slug.
	 * @return array Default settings.
	 */
	public static function get_default_settings( string $cpt ): array {
		$defaults = array(
			'enabled'              => false,
			'can_add_coauthors'    => false,
			'can_edit_coauthors'   => false,
			'can_remove_coauthors' => false,
			'max_coauthors'        => 5,
			'can_edit_content'     => false,
			'can_delete_content'   => false,
		);

		// Extra settings for núcleo.
		if ( 'nucleo' === $cpt ) {
			$defaults['can_add_members']    = false;
			$defaults['can_remove_members'] = false;
			$defaults['can_toggle_privacy'] = false;
			$defaults['default_privacy']    = 'private';
			$defaults['new_nucleo_privacy'] = 'private';
			$defaults['can_post_files']     = false;
			$defaults['max_pinned_files']   = 10;
		}

		// Extra settings for comuna.
		if ( 'comuna' === $cpt ) {
			$defaults['can_add_members']    = false;
			$defaults['can_remove_members'] = false;
			$defaults['can_post_files']     = false;
			$defaults['max_pinned_files']   = 10;
		}

		return $defaults;
	}

	/**
	 * Get all settings
	 *
	 * @return array All co-authors settings.
	 */
	public static function get_settings(): array {
		$saved = get_option( self::OPTION_KEY, array() );

		$settings = array();
		foreach ( self::$cpts as $cpt => $config ) {
			$defaults         = self::get_default_settings( $cpt );
			$settings[ $cpt ] = isset( $saved[ $cpt ] ) ? wp_parse_args( $saved[ $cpt ], $defaults ) : $defaults;
		}

		return $settings;
	}

	/**
	 * Get settings for a specific CPT
	 *
	 * @param string $cpt CPT slug.
	 * @return array CPT settings.
	 */
	public static function get_cpt_settings( string $cpt ): array {
		$settings = self::get_settings();

		return isset( $settings[ $cpt ] ) ? $settings[ $cpt ] : self::get_default_settings( $cpt );
	}

	/**
	 * Check if co-authors are enabled for a CPT
	 *
	 * @param string $cpt CPT slug.
	 * @return bool True if enabled.
	 */
	public static function is_enabled( string $cpt ): bool {
		$settings = self::get_cpt_settings( $cpt );

		return ! empty( $settings['enabled'] );
	}

	/**
	 * Render the co-authors settings tab
	 */
	public static function render_tab() {
		if ( ! current_user_can( 'manage_apollo_mod_settings' ) ) {
			wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'apollo-core' ) );
		}

		$settings = self::get_settings();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="apollo-coauthors-settings-form">
			<?php wp_nonce_field( 'apollo_coauthors_settings_save', 'apollo_coauthors_nonce' ); ?>
			<input type="hidden" name="action" value="apollo_save_coauthors_settings">

			<h2><?php esc_html_e( 'Configurações de Co-autores', 'apollo-core' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Configure as permissões de co-autores para cada tipo de conteúdo. Co-autores podem colaborar na administração do conteúdo conforme as permissões definidas abaixo.', 'apollo-core' ); ?>
			</p>

			<?php foreach ( self::$cpts as $cpt => $config ) : ?>
				<?php self::render_cpt_section( $cpt, $config, $settings[ $cpt ] ); ?>
			<?php endforeach; ?>

			<?php submit_button( __( 'Salvar Configurações', 'apollo-core' ) ); ?>
		</form>

		<style>
			.apollo-coauthors-settings-form {
				max-width: 900px;
			}
			.apollo-cpt-section {
				background: #fff;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				margin-bottom: 20px;
				padding: 0;
			}
			.apollo-cpt-header {
				background: #f6f7f7;
				border-bottom: 1px solid #c3c4c7;
				padding: 15px 20px;
				display: flex;
				align-items: center;
				gap: 10px;
			}
			.apollo-cpt-header h3 {
				margin: 0;
				font-size: 14px;
				flex: 1;
			}
			.apollo-cpt-header .dashicons {
				font-size: 20px;
				width: 20px;
				height: 20px;
				color: #2271b1;
			}
			.apollo-cpt-body {
				padding: 20px;
			}
			.apollo-cpt-body .form-table {
				margin: 0;
			}
			.apollo-cpt-body .form-table th {
				width: 300px;
				padding-left: 0;
			}
			.apollo-toggle {
				display: inline-flex;
				align-items: center;
				gap: 8px;
			}
			.apollo-toggle input[type="checkbox"] {
				width: 40px;
				height: 20px;
				appearance: none;
				background: #ccc;
				border-radius: 20px;
				position: relative;
				cursor: pointer;
				transition: background 0.2s;
			}
			.apollo-toggle input[type="checkbox"]:checked {
				background: #2271b1;
			}
			.apollo-toggle input[type="checkbox"]::after {
				content: '';
				position: absolute;
				width: 16px;
				height: 16px;
				background: #fff;
				border-radius: 50%;
				top: 2px;
				left: 2px;
				transition: left 0.2s;
			}
			.apollo-toggle input[type="checkbox"]:checked::after {
				left: 22px;
			}
			.apollo-toggle-label {
				color: #666;
				font-size: 12px;
			}
			.apollo-extra-section {
				margin-top: 20px;
				padding-top: 20px;
				border-top: 1px dashed #c3c4c7;
			}
			.apollo-extra-section h4 {
				margin: 0 0 15px 0;
				color: #1d2327;
				font-size: 13px;
			}
			.apollo-number-input {
				width: 100px;
			}
		</style>
		<?php
	}

	/**
	 * Render a CPT section
	 *
	 * @param string $cpt      CPT slug.
	 * @param array  $config   CPT configuration.
	 * @param array  $settings CPT settings.
	 */
	private static function render_cpt_section( string $cpt, array $config, array $settings ) {
		$field_prefix = "coauthors[{$cpt}]";
		?>
		<section id="<?php echo esc_attr( $cpt ); ?>" class="apollo-cpt-section">
			<div class="apollo-cpt-header">
				<span class="dashicons <?php echo esc_attr( $config['icon'] ); ?>"></span>
				<h3><?php echo esc_html( $config['label'] ); ?></h3>
			</div>
			<div class="apollo-cpt-body">
				<table class="form-table">
					<?php
					// Enable co-authors.
					self::render_toggle_row(
						$field_prefix . '[enabled]',
						__( 'Co-autores estão liberados para ' . $config['label'] . '?', 'apollo-core' ),
						$settings['enabled']
					);

					// Can add co-authors.
					self::render_toggle_row(
						$field_prefix . '[can_add_coauthors]',
						__( 'Co-autores podem adicionar outros usuários como co-autores?', 'apollo-core' ),
						$settings['can_add_coauthors']
					);

					// Can edit co-authors.
					self::render_toggle_row(
						$field_prefix . '[can_edit_coauthors]',
						__( 'Co-autores podem editar e retirar usuários de co-autor?', 'apollo-core' ),
						$settings['can_edit_coauthors']
					);

					// Max co-authors.
					self::render_number_row(
						$field_prefix . '[max_coauthors]',
						__( 'Máximo de usuários como co-autores permitidos:', 'apollo-core' ),
						$settings['max_coauthors'],
						0,
						999999
					);

					// Can edit content.
					self::render_toggle_row(
						$field_prefix . '[can_edit_content]',
						/* translators: %s: CPT singular label */
						sprintf( __( 'Co-autores podem editar o(a) %s que administra?', 'apollo-core' ), $config['label_single'] ),
						$settings['can_edit_content']
					);

					// Can delete content.
					self::render_toggle_row(
						$field_prefix . '[can_delete_content]',
						/* translators: %s: CPT singular label */
						sprintf( __( 'Co-autores podem deletar o(a) %s que administra?', 'apollo-core' ), $config['label_single'] ),
						$settings['can_delete_content']
					);
					?>
				</table>

				<?php
				// Extra settings for núcleo.
				if ( 'nucleo' === $config['extra'] ) {
					self::render_nucleo_extra( $field_prefix, $settings );
				}

				// Extra settings for comuna.
				if ( 'comuna' === $config['extra'] ) {
					self::render_comuna_extra( $field_prefix, $settings );
				}
				?>
			</div>
		</section>
		<?php
	}

	/**
	 * Render toggle row
	 *
	 * @param string $name    Field name.
	 * @param string $label   Field label.
	 * @param bool   $checked Is checked.
	 */
	private static function render_toggle_row( string $name, string $label, bool $checked ) {
		?>
		<tr>
			<th scope="row">
				<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<label class="apollo-toggle">
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?>>
					<span class="apollo-toggle-label"><?php echo $checked ? esc_html__( 'Sim', 'apollo-core' ) : esc_html__( 'Não', 'apollo-core' ); ?></span>
				</label>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render number row
	 *
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 * @param int    $value Current value.
	 * @param int    $min   Minimum value.
	 * @param int    $max   Maximum value.
	 */
	private static function render_number_row( string $name, string $label, int $value, int $min = 0, int $max = 999999 ) {
		?>
		<tr>
			<th scope="row">
				<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input
					type="number"
					name="<?php echo esc_attr( $name ); ?>"
					id="<?php echo esc_attr( $name ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					min="<?php echo esc_attr( $min ); ?>"
					max="<?php echo esc_attr( $max ); ?>"
					class="apollo-number-input"
				>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render select row
	 *
	 * @param string $name    Field name.
	 * @param string $label   Field label.
	 * @param string $value   Current value.
	 * @param array  $options Options array.
	 */
	private static function render_select_row( string $name, string $label, string $value, array $options ) {
		?>
		<tr>
			<th scope="row">
				<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>">
					<?php foreach ( $options as $opt_value => $opt_label ) : ?>
						<option value="<?php echo esc_attr( $opt_value ); ?>" <?php selected( $value, $opt_value ); ?>>
							<?php echo esc_html( $opt_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render extra settings for núcleo
	 *
	 * @param string $field_prefix Field prefix.
	 * @param array  $settings     Settings array.
	 */
	private static function render_nucleo_extra( string $field_prefix, array $settings ) {
		?>
		<div class="apollo-extra-section">
			<h4><?php esc_html_e( 'EXTRA NÚCLEO', 'apollo-core' ); ?></h4>
			<table class="form-table">
				<?php
				// Can add members.
				self::render_toggle_row(
					$field_prefix . '[can_add_members]',
					__( 'Co-autores podem adicionar membros?', 'apollo-core' ),
					$settings['can_add_members'] ?? false
				);

				// Can remove members.
				self::render_toggle_row(
					$field_prefix . '[can_remove_members]',
					__( 'Co-autores podem remover membros?', 'apollo-core' ),
					$settings['can_remove_members'] ?? false
				);

				// Can toggle privacy.
				self::render_toggle_row(
					$field_prefix . '[can_toggle_privacy]',
					__( 'Co-autores podem escolher se o núcleo é público ou privado?', 'apollo-core' ),
					$settings['can_toggle_privacy'] ?? false
				);

				// Default privacy for all.
				self::render_select_row(
					$field_prefix . '[default_privacy]',
					__( 'Qual padrão de núcleos que todos devem ficar agora?', 'apollo-core' ),
					$settings['default_privacy'] ?? 'private',
					array(
						'public'  => __( 'Público', 'apollo-core' ),
						'private' => __( 'Privado', 'apollo-core' ),
					)
				);

				// New núcleo privacy.
				self::render_select_row(
					$field_prefix . '[new_nucleo_privacy]',
					__( 'Qual padrão para os novos núcleos criados?', 'apollo-core' ),
					$settings['new_nucleo_privacy'] ?? 'private',
					array(
						'public'  => __( 'Público', 'apollo-core' ),
						'private' => __( 'Privado', 'apollo-core' ),
					)
				);

				// Can post files.
				self::render_toggle_row(
					$field_prefix . '[can_post_files]',
					__( 'Co-autores podem postar arquivos no grupo de produtores que administra?', 'apollo-core' ),
					$settings['can_post_files'] ?? false
				);

				// Max pinned files.
				self::render_number_row(
					$field_prefix . '[max_pinned_files]',
					__( 'Co-autores tem limite de quantos arquivos fixados no mural de Núcleo?', 'apollo-core' ),
					$settings['max_pinned_files'] ?? 10,
					0,
					9999
				);
				?>
			</table>
		</div>
		<?php
	}

	/**
	 * Render extra settings for comuna
	 *
	 * @param string $field_prefix Field prefix.
	 * @param array  $settings     Settings array.
	 */
	private static function render_comuna_extra( string $field_prefix, array $settings ) {
		?>
		<div class="apollo-extra-section">
			<h4><?php esc_html_e( 'EXTRA COMUNIDADE', 'apollo-core' ); ?></h4>
			<table class="form-table">
				<?php
				// Can add members.
				self::render_toggle_row(
					$field_prefix . '[can_add_members]',
					__( 'Co-autores podem adicionar membros na comunidade?', 'apollo-core' ),
					$settings['can_add_members'] ?? false
				);

				// Can remove members.
				self::render_toggle_row(
					$field_prefix . '[can_remove_members]',
					__( 'Co-autores podem remover membros na comunidade?', 'apollo-core' ),
					$settings['can_remove_members'] ?? false
				);

				// Can post files.
				self::render_toggle_row(
					$field_prefix . '[can_post_files]',
					__( 'Co-autores podem postar arquivos na comunidade pública de usuários logados que administra?', 'apollo-core' ),
					$settings['can_post_files'] ?? false
				);

				// Max pinned files.
				self::render_number_row(
					$field_prefix . '[max_pinned_files]',
					__( 'Co-autores tem limite de quantos arquivos fixados no mural de comuna?', 'apollo-core' ),
					$settings['max_pinned_files'] ?? 10,
					0,
					9999
				);
				?>
			</table>
		</div>
		<?php
	}

	/**
	 * Save settings
	 */
	public static function save_settings() {
		if ( ! current_user_can( 'manage_apollo_mod_settings' ) ) {
			wp_die( esc_html__( 'Você não tem permissão para executar esta ação.', 'apollo-core' ) );
		}

		check_admin_referer( 'apollo_coauthors_settings_save', 'apollo_coauthors_nonce' );

		$input    = isset( $_POST['coauthors'] ) ? wp_unslash( $_POST['coauthors'] ) : array();
		$settings = array();

		foreach ( self::$cpts as $cpt => $config ) {
			$cpt_input = isset( $input[ $cpt ] ) ? $input[ $cpt ] : array();

			$settings[ $cpt ] = array(
				// Boolean fields.
				'enabled'              => ! empty( $cpt_input['enabled'] ),
				'can_add_coauthors'    => ! empty( $cpt_input['can_add_coauthors'] ),
				'can_edit_coauthors'   => ! empty( $cpt_input['can_edit_coauthors'] ),
				'can_remove_coauthors' => ! empty( $cpt_input['can_remove_coauthors'] ),
				'can_edit_content'     => ! empty( $cpt_input['can_edit_content'] ),
				'can_delete_content'   => ! empty( $cpt_input['can_delete_content'] ),

				// Numeric fields.
				'max_coauthors'        => isset( $cpt_input['max_coauthors'] ) ? absint( $cpt_input['max_coauthors'] ) : 5,
			);

			// Extra fields for núcleo.
			if ( 'nucleo' === $config['extra'] ) {
				$settings[ $cpt ]['can_add_members']    = ! empty( $cpt_input['can_add_members'] );
				$settings[ $cpt ]['can_remove_members'] = ! empty( $cpt_input['can_remove_members'] );
				$settings[ $cpt ]['can_toggle_privacy'] = ! empty( $cpt_input['can_toggle_privacy'] );
				$settings[ $cpt ]['default_privacy']    = isset( $cpt_input['default_privacy'] ) && in_array( $cpt_input['default_privacy'], array( 'public', 'private' ), true ) ? $cpt_input['default_privacy'] : 'private';
				$settings[ $cpt ]['new_nucleo_privacy'] = isset( $cpt_input['new_nucleo_privacy'] ) && in_array( $cpt_input['new_nucleo_privacy'], array( 'public', 'private' ), true ) ? $cpt_input['new_nucleo_privacy'] : 'private';
				$settings[ $cpt ]['can_post_files']     = ! empty( $cpt_input['can_post_files'] );
				$settings[ $cpt ]['max_pinned_files']   = isset( $cpt_input['max_pinned_files'] ) ? absint( $cpt_input['max_pinned_files'] ) : 10;
			}

			// Extra fields for comuna.
			if ( 'comuna' === $config['extra'] ) {
				$settings[ $cpt ]['can_add_members']    = ! empty( $cpt_input['can_add_members'] );
				$settings[ $cpt ]['can_remove_members'] = ! empty( $cpt_input['can_remove_members'] );
				$settings[ $cpt ]['can_post_files']     = ! empty( $cpt_input['can_post_files'] );
				$settings[ $cpt ]['max_pinned_files']   = isset( $cpt_input['max_pinned_files'] ) ? absint( $cpt_input['max_pinned_files'] ) : 10;
			}
		}

		update_option( self::OPTION_KEY, $settings );

		// Log action.
		if ( function_exists( 'apollo_mod_log_action' ) ) {
			apollo_mod_log_action(
				get_current_user_id(),
				'coauthors_settings_updated',
				'settings',
				0,
				array( 'timestamp' => current_time( 'mysql' ) )
			);
		}

		wp_safe_redirect( admin_url( 'admin.php?page=apollo-mod&tab=coauthors&updated=1' ) );
		exit;
	}

	/**
	 * Check if user can perform co-author action
	 *
	 * @param int    $user_id  User ID.
	 * @param int    $post_id  Post ID.
	 * @param string $action   Action to check (add_coauthors, edit_content, delete_content, etc.).
	 * @return bool True if allowed.
	 */
	public static function can_perform_action( int $user_id, int $post_id, string $action ): bool {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$cpt      = $post->post_type;
		$settings = self::get_cpt_settings( $cpt );

		// Check if co-authors are enabled.
		if ( empty( $settings['enabled'] ) ) {
			return false;
		}

		// Check if user is a co-author.
		$co_authors = get_post_meta( $post_id, '_apollo_coauthors', true );
		if ( ! is_array( $co_authors ) ) {
			$co_authors = get_post_meta( $post_id, '_event_co_authors', true );
		}

		if ( ! is_array( $co_authors ) || ! in_array( $user_id, $co_authors, true ) ) {
			return false;
		}

		// Check specific permission.
		$action_map = array(
			'add_coauthors'    => 'can_add_coauthors',
			'edit_coauthors'   => 'can_edit_coauthors',
			'remove_coauthors' => 'can_edit_coauthors',
			'edit_content'     => 'can_edit_content',
			'delete_content'   => 'can_delete_content',
			'add_members'      => 'can_add_members',
			'remove_members'   => 'can_remove_members',
			'toggle_privacy'   => 'can_toggle_privacy',
			'post_files'       => 'can_post_files',
		);

		$setting_key = isset( $action_map[ $action ] ) ? $action_map[ $action ] : null;

		if ( ! $setting_key || empty( $settings[ $setting_key ] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get max co-authors for a CPT
	 *
	 * @param string $cpt CPT slug.
	 * @return int Max co-authors.
	 */
	public static function get_max_coauthors( string $cpt ): int {
		$settings = self::get_cpt_settings( $cpt );

		return isset( $settings['max_coauthors'] ) ? (int) $settings['max_coauthors'] : 5;
	}

	/**
	 * Get max pinned files for a CPT
	 *
	 * @param string $cpt CPT slug.
	 * @return int Max pinned files.
	 */
	public static function get_max_pinned_files( string $cpt ): int {
		$settings = self::get_cpt_settings( $cpt );

		return isset( $settings['max_pinned_files'] ) ? (int) $settings['max_pinned_files'] : 10;
	}
}

// Initialize.
Apollo_Coauthors_Settings::init();
