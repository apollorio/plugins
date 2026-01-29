<?php
/**
 * Template Part: DJ Single - Vinyl Player
 * ========================================
 * Path: apollo-core/templates/parts/dj/player.php
 *
 * @var string $dj_name_formatted DJ name for vinyl label
 * @var string $dj_track_title    Featured track title
 * @var string $sc_embed_url      SoundCloud embed URL
 *
 * @package Apollo_Core
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

$dj_name_formatted = $dj_name_formatted ?? 'DJ';
$dj_track_title    = $dj_track_title ?? '';
$sc_embed_url      = $sc_embed_url ?? '';

// Don't render player if no SoundCloud URL
if ( empty( $sc_embed_url ) ) {
	return;
}
?>

<section class="dj-player-block" id="djPlayerBlock">
	<div>
		<div class="dj-player-title">
			<?php esc_html_e( 'Feature set para escuta', 'apollo-core' ); ?>
		</div>
		<?php if ( ! empty( $dj_track_title ) ) : ?>
		<div class="dj-player-sub" id="track-title">
			<?php echo esc_html( $dj_track_title ); ?>
		</div>
		<?php endif; ?>
	</div>

	<main class="vinyl-zone">
		<div class="vinyl-player is-paused" id="vinylPlayer" role="button" aria-label="<?php esc_attr_e( 'Play / Pause set', 'apollo-core' ); ?>" tabindex="0">
			<div class="vinyl-shadow"></div>

			<div class="vinyl-disc">
				<div class="vinyl-beam"></div>
				<div class="vinyl-rings"></div>

				<div class="vinyl-label">
					<div class="vinyl-label-text" id="vinylLabelText">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped
						echo $dj_name_formatted;
						?>
					</div>
				</div>

				<div class="vinyl-hole"></div>
			</div>

			<div class="tonearm">
				<div class="tonearm-base"></div>
				<div class="tonearm-shaft"></div>
				<div class="tonearm-head"></div>
			</div>
		</div>
	</main>

	<p class="now-playing">
		<?php
		printf(
			/* translators: %s: Platform name (SoundCloud) */
			esc_html__( 'Set de referência em destaque no %s.', 'apollo-core' ),
			'<strong>SoundCloud</strong>'
		);
		?>
	</p>

	<!-- Hidden SoundCloud Player -->
	<iframe
		id="scPlayer"
		class="dj-sc-player-hidden"
		scrolling="no"
		frameborder="no"
		allow="autoplay"
		src="<?php echo esc_url( $sc_embed_url ); ?>"
		title="<?php esc_attr_e( 'SoundCloud Player', 'apollo-core' ); ?>">
	</iframe>

	<div class="player-cta-row">
		<button class="btn-player-main" id="vinylToggle" type="button">
			<i class="ri-play-fill" id="vinylIcon"></i>
			<span><?php esc_html_e( 'Play / Pause set', 'apollo-core' ); ?></span>
		</button>
		<p class="player-note">
			<?php esc_html_e( 'Contato e condições completas no media kit e rider técnico.', 'apollo-core' ); ?>
		</p>
	</div>
</section>
