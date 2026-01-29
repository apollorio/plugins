<?php
/**
 * Template Part: DJ Single - Bio Modal
 * =====================================
 * Path: apollo-core/templates/parts/dj/bio-modal.php
 *
 * @var string $dj_name     DJ display name
 * @var string $dj_bio_full Full bio HTML content
 *
 * @package Apollo_Core
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

$dj_name     = $dj_name ?? 'DJ';
$dj_bio_full = $dj_bio_full ?? '';

// Don't render if no bio content
if ( empty( $dj_bio_full ) ) {
	return;
}
?>

<div class="dj-bio-modal-backdrop" id="bioBackdrop" data-open="false" role="dialog" aria-modal="true" aria-labelledby="dj-bio-modal-title">
	<div class="dj-bio-modal">
		<div class="dj-bio-modal-header">
			<h3 id="dj-bio-modal-title">
				<?php
				printf(
					/* translators: %s: DJ name */
					esc_html__( 'Bio completa · %s', 'apollo-core' ),
					esc_html( $dj_name )
				);
				?>
			</h3>
			<button type="button" class="dj-bio-modal-close" id="bioClose" aria-label="<?php esc_attr_e( 'Fechar modal', 'apollo-core' ); ?>">
				<i class="ri-close-line"></i>
			</button>
		</div>
		<div class="dj-bio-modal-body" id="bio-full">
			<?php
			// Bio content - already filtered through the_content in parent template
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $dj_bio_full;
			?>
		</div>
	</div>
</div>
