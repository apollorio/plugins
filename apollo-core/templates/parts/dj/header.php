<?php
/**
 * Template Part: DJ Single - Header
 * ==================================
 * Path: apollo-core/templates/parts/dj/header.php
 *
 * @var string $dj_name       DJ display name
 * @var string $media_kit_url Media kit URL for header button
 *
 * @package Apollo_Core
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

$dj_name       = $dj_name ?? 'DJ';
$media_kit_url = $media_kit_url ?? '';
?>

<header class="dj-header">
	<div class="dj-header-left">
		<span><?php echo esc_html( apply_filters( 'apollo_dj_roster_label', 'Apollo::rio · DJ Roster' ) ); ?></span>
		<strong id="dj-header-name"><?php echo esc_html( strtoupper( $dj_name ) ); ?></strong>
	</div>

	<?php if ( ! empty( $media_kit_url ) ) : ?>
	<a href="<?php echo esc_url( $media_kit_url ); ?>"
	   id="mediakit-link"
	   class="dj-pill-link"
	   target="_blank"
	   rel="noopener noreferrer">
		<i class="ri-clipboard-line"></i>
		<?php esc_html_e( 'Media kit', 'apollo-core' ); ?>
	</a>
	<?php endif; ?>
</header>
