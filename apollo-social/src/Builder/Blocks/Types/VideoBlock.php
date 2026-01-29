<?php
/**
 * Video Block Type
 *
 * @package Apollo_Social
 * @since 2.0.0
 */

namespace Apollo_Social\Builder\Blocks\Types;

use Apollo_Social\Builder\Blocks\AbstractBlock;

/**
 * Class VideoBlock
 *
 * Embed de vídeo YouTube
 */
class VideoBlock extends AbstractBlock {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'video';
		$this->name = 'Vídeo';
		$this->icon = 'video';
		$this->settings = array(
			array(
				'name'    => 'url',
				'type'    => 'url',
				'label'   => 'URL do YouTube',
				'default' => '',
			),
		);
	}

	/**
	 * Extract YouTube video ID from URL
	 *
	 * @param string $url YouTube URL
	 * @return string|false Video ID or false
	 */
	private function extract_youtube_id( $url ) {
		$pattern = '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/';

		if ( preg_match( $pattern, $url, $matches ) ) {
			return $matches[1];
		}

		return false;
	}

	/**
	 * Render block
	 *
	 * @param array $props Block properties
	 * @param array $profile Profile data
	 * @return string
	 */
	public function render( $props, $profile ) {
		$url      = isset( $props['url'] ) ? $props['url'] : '';
		$block_id = isset( $props['id'] ) ? $props['id'] : '';

		if ( empty( $url ) ) {
			return '';
		}

		$video_id = $this->extract_youtube_id( $url );

		if ( ! $video_id ) {
			return '';
		}

		$embed_url = sprintf( 'https://www.youtube.com/embed/%s', $video_id );

		$content = sprintf(
			'<div class="hub-video__container">
				<iframe
					class="hub-video__iframe"
					src="%s"
					frameborder="0"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
					allowfullscreen
					loading="lazy">
				</iframe>
			</div>',
			esc_url( $embed_url )
		);

		return $this->render_wrapper( $content, $block_id );
	}

	/**
	 * Validate properties
	 *
	 * @param array $props Properties to validate
	 * @return array
	 */
	public function validate( $props ) {
		$url = isset( $props['url'] ) ? $this->sanitize_url( $props['url'] ) : '';

		// Validar se é URL do YouTube
		if ( ! empty( $url ) && ! $this->extract_youtube_id( $url ) ) {
			$url = '';
		}

		return array(
			'url' => $url,
		);
	}
}
