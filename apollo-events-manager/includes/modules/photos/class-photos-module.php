<?php
/**
 * Photos Module
 *
 * Handles event photo galleries, sliders, and community uploads.
 *
 * @package Apollo_Events_Manager
 * @subpackage Modules
 * @since 2.0.0
 */

namespace Apollo\Events\Modules;

use Apollo\Events\Core\Abstract_Module;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Photos_Module
 *
 * Provides photo gallery functionality for events.
 *
 * @since 2.0.0
 */
class Photos_Module extends Abstract_Module {

	/**
	 * Meta key for event photos.
	 *
	 * @var string
	 */
	const PHOTOS_META_KEY = '_event_photos';

	/**
	 * Meta key for community photos.
	 *
	 * @var string
	 */
	const COMMUNITY_PHOTOS_META_KEY = '_event_community_photos';

	/**
	 * Get module unique identifier.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_id(): string {
		return 'photos';
	}

	/**
	 * Get module name.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Fotos', 'apollo-events' );
	}

	/**
	 * Get module description.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Galerias de fotos, sliders de imagens e uploads da comunidade.', 'apollo-events' );
	}

	/**
	 * Get module version.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_version(): string {
		return '2.0.0';
	}

	/**
	 * Check if module is enabled by default.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function is_default_enabled(): bool {
		return true;
	}

	/**
	 * Initialize the module.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init(): void {
		$this->register_shortcodes();
		$this->register_assets();
		$this->register_ajax_handlers();
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_ajax_handlers(): void {
		add_action( 'wp_ajax_apollo_upload_event_photo', array( $this, 'ajax_upload_photo' ) );
		add_action( 'wp_ajax_nopriv_apollo_upload_event_photo', array( $this, 'ajax_login_required' ) );
	}

	/**
	 * Register module shortcodes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'apollo_event_gallery', array( $this, 'render_gallery' ) );
		add_shortcode( 'apollo_photo_slider', array( $this, 'render_slider' ) );
		add_shortcode( 'apollo_photo_grid', array( $this, 'render_grid' ) );
		add_shortcode( 'apollo_photo_masonry', array( $this, 'render_masonry' ) );
		add_shortcode( 'apollo_community_photos', array( $this, 'render_community_photos' ) );
		add_shortcode( 'apollo_photo_upload', array( $this, 'render_upload_form' ) );
	}

	/**
	 * Register module assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_assets(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue module assets.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueue_assets( string $context = '' ): void {
		$plugin_url = plugins_url( '', dirname( dirname( __DIR__ ) ) );

		wp_register_style(
			'apollo-photos',
			$plugin_url . '/assets/css/photos.css',
			array(),
			$this->get_version()
		);

		wp_register_script(
			'apollo-photos',
			$plugin_url . '/assets/js/photos.js',
			array( 'jquery' ),
			$this->get_version(),
			true
		);

		wp_localize_script(
			'apollo-photos',
			'apolloPhotos',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'apollo_photos_nonce' ),
				'maxFileSize'  => wp_max_upload_size(),
				'allowedTypes' => array( 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ),
				'i18n'         => array(
					'close'         => __( 'Fechar', 'apollo-events' ),
					'prev'          => __( 'Anterior', 'apollo-events' ),
					'next'          => __( 'Próximo', 'apollo-events' ),
					'uploading'     => __( 'Enviando...', 'apollo-events' ),
					'uploadSuccess' => __( 'Foto enviada com sucesso!', 'apollo-events' ),
					'uploadError'   => __( 'Erro ao enviar foto.', 'apollo-events' ),
					'fileTooLarge'  => __( 'Arquivo muito grande.', 'apollo-events' ),
					'invalidType'   => __( 'Tipo de arquivo não permitido.', 'apollo-events' ),
				),
			)
		);
	}

	/**
	 * Render gallery shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_gallery( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id'   => get_the_ID(),
				'columns'    => 3,
				'limit'      => 12,
				'lightbox'   => 'true',
				'show_title' => 'false',
			),
			$atts,
			'apollo_event_gallery'
		);

		$event_id   = absint( $atts['event_id'] );
		$columns    = absint( $atts['columns'] );
		$limit      = absint( $atts['limit'] );
		$lightbox   = filter_var( $atts['lightbox'], FILTER_VALIDATE_BOOLEAN );
		$show_title = filter_var( $atts['show_title'], FILTER_VALIDATE_BOOLEAN );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-photos' );
		wp_enqueue_script( 'apollo-photos' );

		$photos = $this->get_event_photos( $event_id );

		if ( empty( $photos ) ) {
			return '<p class="apollo-no-photos">' . esc_html__( 'Nenhuma foto disponível.', 'apollo-events' ) . '</p>';
		}

		$photos = array_slice( $photos, 0, $limit );

		ob_start();
		?>
		<div class="apollo-photo-gallery"
			data-lightbox="<?php echo $lightbox ? 'true' : 'false'; ?>">
			<div class="apollo-photo-gallery__grid apollo-photo-gallery__grid--cols-<?php echo esc_attr( $columns ); ?>">
				<?php foreach ( $photos as $index => $photo_id ) : ?>
					<?php
					$full_url  = wp_get_attachment_image_url( $photo_id, 'full' );
					$thumb_url = wp_get_attachment_image_url( $photo_id, 'medium_large' );
					$title     = get_the_title( $photo_id );
					$alt       = get_post_meta( $photo_id, '_wp_attachment_image_alt', true );
					?>
					<div class="apollo-photo-gallery__item">
						<a href="<?php echo esc_url( $full_url ); ?>"
							class="apollo-photo-gallery__link"
							data-index="<?php echo esc_attr( $index ); ?>"
							data-title="<?php echo esc_attr( $title ); ?>">
							<img src="<?php echo esc_url( $thumb_url ); ?>"
								alt="<?php echo esc_attr( $alt ?: $title ); ?>"
								loading="lazy">
							<div class="apollo-photo-gallery__overlay">
								<i class="fas fa-search-plus"></i>
							</div>
						</a>
						<?php if ( $show_title && $title ) : ?>
							<span class="apollo-photo-gallery__title"><?php echo esc_html( $title ); ?></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render photo slider shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_slider( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id' => get_the_ID(),
				'limit'    => 10,
				'autoplay' => 'true',
				'height'   => '400px',
				'arrows'   => 'true',
				'dots'     => 'true',
			),
			$atts,
			'apollo_photo_slider'
		);

		$event_id = absint( $atts['event_id'] );
		$limit    = absint( $atts['limit'] );
		$autoplay = filter_var( $atts['autoplay'], FILTER_VALIDATE_BOOLEAN );
		$height   = sanitize_text_field( $atts['height'] );
		$arrows   = filter_var( $atts['arrows'], FILTER_VALIDATE_BOOLEAN );
		$dots     = filter_var( $atts['dots'], FILTER_VALIDATE_BOOLEAN );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-photos' );
		wp_enqueue_script( 'apollo-photos' );

		$photos = $this->get_event_photos( $event_id );

		if ( empty( $photos ) ) {
			return '';
		}

		$photos = array_slice( $photos, 0, $limit );

		ob_start();
		?>
		<div class="apollo-photo-slider"
			data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>"
			style="height: <?php echo esc_attr( $height ); ?>">
			<div class="apollo-photo-slider__track">
				<?php foreach ( $photos as $photo_id ) : ?>
					<?php $full_url = wp_get_attachment_image_url( $photo_id, 'large' ); ?>
					<div class="apollo-photo-slider__slide">
						<img src="<?php echo esc_url( $full_url ); ?>"
							alt="<?php echo esc_attr( get_the_title( $photo_id ) ); ?>"
							loading="lazy">
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $arrows && count( $photos ) > 1 ) : ?>
				<button type="button" class="apollo-photo-slider__nav apollo-photo-slider__nav--prev" aria-label="<?php esc_attr_e( 'Anterior', 'apollo-events' ); ?>">
					<i class="fas fa-chevron-left"></i>
				</button>
				<button type="button" class="apollo-photo-slider__nav apollo-photo-slider__nav--next" aria-label="<?php esc_attr_e( 'Próximo', 'apollo-events' ); ?>">
					<i class="fas fa-chevron-right"></i>
				</button>
			<?php endif; ?>

			<?php if ( $dots && count( $photos ) > 1 ) : ?>
				<div class="apollo-photo-slider__dots">
					<?php foreach ( $photos as $index => $photo_id ) : ?>
						<button type="button"
								class="apollo-photo-slider__dot <?php echo 0 === $index ? 'is-active' : ''; ?>"
								data-index="<?php echo esc_attr( $index ); ?>"
								aria-label="<?php echo esc_attr( sprintf( __( 'Ir para slide %d', 'apollo-events' ), $index + 1 ) ); ?>">
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render photo grid shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_grid( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id' => get_the_ID(),
				'columns'  => 4,
				'limit'    => 8,
				'style'    => 'square',
			),
			$atts,
			'apollo_photo_grid'
		);

		$event_id = absint( $atts['event_id'] );
		$columns  = absint( $atts['columns'] );
		$limit    = absint( $atts['limit'] );
		$style    = sanitize_key( $atts['style'] );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-photos' );
		wp_enqueue_script( 'apollo-photos' );

		$photos = $this->get_event_photos( $event_id );

		if ( empty( $photos ) ) {
			return '';
		}

		$photos    = array_slice( $photos, 0, $limit );
		$total     = count( $this->get_event_photos( $event_id ) );
		$remaining = $total - $limit;

		ob_start();
		?>
		<div class="apollo-photo-grid apollo-photo-grid--<?php echo esc_attr( $style ); ?> apollo-photo-grid--cols-<?php echo esc_attr( $columns ); ?>">
			<?php foreach ( $photos as $index => $photo_id ) : ?>
				<?php
				$is_last   = $index === count( $photos ) - 1 && $remaining > 0;
				$full_url  = wp_get_attachment_image_url( $photo_id, 'full' );
				$thumb_url = wp_get_attachment_image_url( $photo_id, 'medium' );
				?>
				<div class="apollo-photo-grid__item <?php echo $is_last ? 'apollo-photo-grid__item--more' : ''; ?>">
					<a href="<?php echo esc_url( $full_url ); ?>" class="apollo-photo-grid__link" data-index="<?php echo esc_attr( $index ); ?>">
						<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" loading="lazy">
						<?php if ( $is_last ) : ?>
							<div class="apollo-photo-grid__more">
								<span>+<?php echo esc_html( $remaining ); ?></span>
							</div>
						<?php endif; ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render masonry layout shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_masonry( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id' => get_the_ID(),
				'columns'  => 3,
				'limit'    => 12,
				'gap'      => '10px',
			),
			$atts,
			'apollo_photo_masonry'
		);

		$event_id = absint( $atts['event_id'] );
		$columns  = absint( $atts['columns'] );
		$limit    = absint( $atts['limit'] );
		$gap      = sanitize_text_field( $atts['gap'] );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-photos' );
		wp_enqueue_script( 'apollo-photos' );

		$photos = $this->get_event_photos( $event_id );

		if ( empty( $photos ) ) {
			return '';
		}

		$photos = array_slice( $photos, 0, $limit );

		ob_start();
		?>
		<div class="apollo-photo-masonry"
			style="column-count: <?php echo esc_attr( $columns ); ?>; column-gap: <?php echo esc_attr( $gap ); ?>">
			<?php foreach ( $photos as $index => $photo_id ) : ?>
				<?php
				$full_url  = wp_get_attachment_image_url( $photo_id, 'full' );
				$thumb_url = wp_get_attachment_image_url( $photo_id, 'large' );
				?>
				<div class="apollo-photo-masonry__item" style="margin-bottom: <?php echo esc_attr( $gap ); ?>">
					<a href="<?php echo esc_url( $full_url ); ?>" class="apollo-photo-masonry__link" data-index="<?php echo esc_attr( $index ); ?>">
						<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" loading="lazy">
						<div class="apollo-photo-masonry__overlay">
							<i class="fas fa-expand"></i>
						</div>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render community photos shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_community_photos( $atts ): string {
		$atts = shortcode_atts(
			array(
				'event_id'    => get_the_ID(),
				'columns'     => 4,
				'limit'       => 20,
				'show_upload' => 'true',
			),
			$atts,
			'apollo_community_photos'
		);

		$event_id    = absint( $atts['event_id'] );
		$columns     = absint( $atts['columns'] );
		$limit       = absint( $atts['limit'] );
		$show_upload = filter_var( $atts['show_upload'], FILTER_VALIDATE_BOOLEAN );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-photos' );
		wp_enqueue_script( 'apollo-photos' );

		$photos = $this->get_community_photos( $event_id );

		ob_start();
		?>
		<div class="apollo-community-photos">
			<?php if ( $show_upload && is_user_logged_in() ) : ?>
				<div class="apollo-community-photos__header">
					<h3 class="apollo-community-photos__title">
						<i class="fas fa-camera"></i>
						<?php esc_html_e( 'Fotos da Comunidade', 'apollo-events' ); ?>
					</h3>
					<button type="button" class="apollo-btn apollo-btn--primary apollo-btn--sm apollo-photo-upload-trigger"
							data-event-id="<?php echo esc_attr( $event_id ); ?>">
						<i class="fas fa-plus"></i>
						<?php esc_html_e( 'Adicionar Foto', 'apollo-events' ); ?>
					</button>
				</div>
			<?php endif; ?>

			<?php if ( empty( $photos ) ) : ?>
				<div class="apollo-community-photos__empty">
					<i class="fas fa-images"></i>
					<p><?php esc_html_e( 'Nenhuma foto da comunidade ainda.', 'apollo-events' ); ?></p>
					<?php if ( is_user_logged_in() ) : ?>
						<p><?php esc_html_e( 'Seja o primeiro a compartilhar!', 'apollo-events' ); ?></p>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="apollo-photo-gallery__grid apollo-photo-gallery__grid--cols-<?php echo esc_attr( $columns ); ?>">
					<?php
					$photos = array_slice( $photos, 0, $limit );
					foreach ( $photos as $index => $photo ) :
						$photo_id  = is_array( $photo ) ? $photo['attachment_id'] : $photo;
						$user_id   = is_array( $photo ) ? ( $photo['user_id'] ?? 0 ) : 0;
						$full_url  = wp_get_attachment_image_url( $photo_id, 'full' );
						$thumb_url = wp_get_attachment_image_url( $photo_id, 'medium' );
						?>
						<div class="apollo-photo-gallery__item apollo-community-photos__item">
							<a href="<?php echo esc_url( $full_url ); ?>"
								class="apollo-photo-gallery__link"
								data-index="<?php echo esc_attr( $index ); ?>">
								<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" loading="lazy">
								<div class="apollo-photo-gallery__overlay">
									<i class="fas fa-search-plus"></i>
								</div>
							</a>
							<?php if ( $user_id ) : ?>
								<div class="apollo-community-photos__author">
									<?php echo get_avatar( $user_id, 24 ); ?>
									<span><?php echo esc_html( get_the_author_meta( 'display_name', $user_id ) ); ?></span>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render upload form shortcode.
	 *
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_upload_form( $atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<p class="apollo-login-required">' . esc_html__( 'Faça login para enviar fotos.', 'apollo-events' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'event_id'  => get_the_ID(),
				'max_files' => 5,
			),
			$atts,
			'apollo_photo_upload'
		);

		$event_id  = absint( $atts['event_id'] );
		$max_files = absint( $atts['max_files'] );

		if ( ! $event_id ) {
			return '';
		}

		wp_enqueue_style( 'apollo-photos' );
		wp_enqueue_script( 'apollo-photos' );

		ob_start();
		?>
		<div class="apollo-photo-upload" data-event-id="<?php echo esc_attr( $event_id ); ?>" data-max-files="<?php echo esc_attr( $max_files ); ?>">
			<div class="apollo-photo-upload__dropzone">
				<input type="file"
						id="apollo-photo-input-<?php echo esc_attr( $event_id ); ?>"
						class="apollo-photo-upload__input"
						accept="image/jpeg,image/png,image/webp,image/gif"
						multiple>
				<label for="apollo-photo-input-<?php echo esc_attr( $event_id ); ?>" class="apollo-photo-upload__label">
					<i class="fas fa-cloud-upload-alt"></i>
					<span class="apollo-photo-upload__text">
						<?php esc_html_e( 'Arraste fotos aqui ou clique para selecionar', 'apollo-events' ); ?>
					</span>
					<span class="apollo-photo-upload__hint">
						<?php printf( esc_html__( 'Máximo de %d fotos. JPG, PNG, WebP ou GIF.', 'apollo-events' ), $max_files ); ?>
					</span>
				</label>
			</div>

			<div class="apollo-photo-upload__preview"></div>

			<div class="apollo-photo-upload__progress" style="display: none;">
				<div class="apollo-photo-upload__progress-bar">
					<div class="apollo-photo-upload__progress-fill"></div>
				</div>
				<span class="apollo-photo-upload__progress-text"></span>
			</div>

			<button type="button" class="apollo-btn apollo-btn--primary apollo-photo-upload__submit" style="display: none;">
				<i class="fas fa-upload"></i>
				<?php esc_html_e( 'Enviar Fotos', 'apollo-events' ); ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX handler for photo upload.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_upload_photo(): void {
		check_ajax_referer( 'apollo_photos_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Login necessário', 'apollo-events' ) ), 401 );
		}

		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

		if ( ! $event_id || 'event_listing' !== get_post_type( $event_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Evento inválido', 'apollo-events' ) ), 400 );
		}

		if ( empty( $_FILES['photo'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Nenhuma foto enviada', 'apollo-events' ) ), 400 );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$user_id = get_current_user_id();

		// Handle file upload.
		$attachment_id = media_handle_upload( 'photo', $event_id );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ), 400 );
		}

		// Add to community photos.
		$community_photos   = $this->get_community_photos( $event_id );
		$community_photos[] = array(
			'attachment_id' => $attachment_id,
			'user_id'       => $user_id,
			'uploaded_at'   => current_time( 'mysql' ),
		);
		update_post_meta( $event_id, self::COMMUNITY_PHOTOS_META_KEY, $community_photos );

		wp_send_json_success(
			array(
				'attachment_id' => $attachment_id,
				'url'           => wp_get_attachment_image_url( $attachment_id, 'medium' ),
				'message'       => __( 'Foto enviada com sucesso!', 'apollo-events' ),
			)
		);
	}

	/**
	 * AJAX handler for login required.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function ajax_login_required(): void {
		wp_send_json_error(
			array(
				'message'  => __( 'Faça login para enviar fotos', 'apollo-events' ),
				'loginUrl' => wp_login_url( wp_get_referer() ),
			),
			401
		);
	}

	/**
	 * Get event photos.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return array
	 */
	public function get_event_photos( int $event_id ): array {
		$photos = get_post_meta( $event_id, self::PHOTOS_META_KEY, true );
		return is_array( $photos ) ? $photos : array();
	}

	/**
	 * Get community photos.
	 *
	 * @since 2.0.0
	 * @param int $event_id Event ID.
	 * @return array
	 */
	public function get_community_photos( int $event_id ): array {
		$photos = get_post_meta( $event_id, self::COMMUNITY_PHOTOS_META_KEY, true );
		return is_array( $photos ) ? $photos : array();
	}

	/**
	 * Get settings schema.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_settings_schema(): array {
		return array(
			'enable_lightbox'  => array(
				'type'        => 'boolean',
				'label'       => __( 'Habilitar Lightbox', 'apollo-events' ),
				'description' => __( 'Exibe fotos em modal ao clicar.', 'apollo-events' ),
				'default'     => true,
			),
			'enable_community' => array(
				'type'        => 'boolean',
				'label'       => __( 'Fotos da Comunidade', 'apollo-events' ),
				'description' => __( 'Permite usuários enviarem fotos.', 'apollo-events' ),
				'default'     => true,
			),
			'moderate_uploads' => array(
				'type'        => 'boolean',
				'label'       => __( 'Moderar uploads', 'apollo-events' ),
				'description' => __( 'Exige aprovação para fotos da comunidade.', 'apollo-events' ),
				'default'     => false,
			),
			'max_upload_size'  => array(
				'type'        => 'number',
				'label'       => __( 'Tamanho máximo (MB)', 'apollo-events' ),
				'description' => __( 'Tamanho máximo de cada foto.', 'apollo-events' ),
				'default'     => 5,
				'min'         => 1,
				'max'         => 20,
			),
			'default_columns'  => array(
				'type'        => 'number',
				'label'       => __( 'Colunas padrão', 'apollo-events' ),
				'description' => __( 'Número de colunas na galeria.', 'apollo-events' ),
				'default'     => 3,
				'min'         => 2,
				'max'         => 6,
			),
		);
	}
}
