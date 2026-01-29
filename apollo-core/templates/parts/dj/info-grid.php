<?php
/**
 * Template Part: DJ Single - Info Grid (Bio & Links)
 * ====================================================
 * Path: apollo-core/templates/parts/dj/info-grid.php
 *
 * @var string $dj_bio_excerpt Bio excerpt text
 * @var array  $music_links    Filtered music platform links
 * @var array  $social_links   Filtered social network links
 * @var array  $asset_links    Filtered asset links
 * @var array  $platform_links Filtered other platform links
 *
 * Link array structure:
 * Each link: ['url' => string, 'icon' => string, 'label' => string]
 *
 * @package Apollo_Core
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

$dj_bio_excerpt = $dj_bio_excerpt ?? '';
$music_links    = $music_links ?? array();
$social_links   = $social_links ?? array();
$asset_links    = $asset_links ?? array();
$platform_links = $platform_links ?? array();

/**
 * Render a link section
 *
 * @param string $label    Section label
 * @param array  $links    Array of link data
 * @param string $id       Container ID
 * @param string $active   Key of active link (optional)
 */
function apollo_dj_render_link_section( $label, $links, $id, $active = '' ) {
	if ( empty( $links ) ) {
		return;
	}
	?>
	<div>
		<div class="dj-links-label"><?php echo esc_html( $label ); ?></div>
		<div class="dj-links-row" id="<?php echo esc_attr( $id ); ?>">
			<?php foreach ( $links as $key => $link ) : ?>
				<?php if ( empty( $link['url'] ) ) continue; ?>
				<a href="<?php echo esc_url( $link['url'] ); ?>"
				   class="dj-link-pill<?php echo ( $key === $active ) ? ' active' : ''; ?>"
				   target="_blank"
				   rel="noopener noreferrer">
					<i class="<?php echo esc_attr( $link['icon'] ?? 'ri-link' ); ?>"></i>
					<span><?php echo esc_html( $link['label'] ?? ucfirst( $key ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}
?>

<section class="dj-info-grid">
	<!-- Bio Column -->
	<div class="dj-info-block">
		<h2><?php esc_html_e( 'Sobre', 'apollo-core' ); ?></h2>

		<?php if ( ! empty( $dj_bio_excerpt ) ) : ?>
		<div class="dj-bio-excerpt" id="dj-bio-excerpt">
			<?php echo wp_kses_post( $dj_bio_excerpt ); ?>
		</div>

		<button type="button" class="dj-bio-toggle" id="bioToggle">
			<span><?php esc_html_e( 'ler bio completa', 'apollo-core' ); ?></span>
			<i class="ri-arrow-right-up-line"></i>
		</button>
		<?php endif; ?>
	</div>

	<!-- Links Column -->
	<div class="dj-info-block">
		<h2><?php esc_html_e( 'Links principais', 'apollo-core' ); ?></h2>

		<?php
		// Music Links (SoundCloud is primary/active)
		apollo_dj_render_link_section(
			__( 'Música', 'apollo-core' ),
			$music_links,
			'music-links',
			'soundcloud'
		);

		// Social Links
		apollo_dj_render_link_section(
			__( 'Social', 'apollo-core' ),
			$social_links,
			'social-links'
		);

		// Asset Links
		apollo_dj_render_link_section(
			__( 'Assets', 'apollo-core' ),
			$asset_links,
			'asset-links'
		);

		// Other Platforms
		if ( ! empty( $platform_links ) ) :
			apollo_dj_render_link_section(
				__( 'Outras plataformas', 'apollo-core' ),
				$platform_links,
				'other-links'
			);

			// Helper text with platform names
			$platform_names = array_map(
				function( $link ) {
					return $link['label'] ?? '';
				},
				array_filter( $platform_links, function( $link ) {
					return ! empty( $link['url'] );
				} )
			);

			if ( ! empty( $platform_names ) ) :
			?>
			<p class="more-platforms" id="more-platforms-helper">
				<?php
				printf(
					/* translators: %s: Comma-separated list of platform names */
					esc_html__( 'Clique para abrir %s.', 'apollo-core' ),
					esc_html( implode( ' · ', $platform_names ) )
				);
				?>
			</p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</section>
