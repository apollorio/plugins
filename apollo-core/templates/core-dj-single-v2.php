<?php
/**
 * Core Template: DJ Single Page (Roster Profile) - Modular Version
 * ==================================================================
 * Path: apollo-core/templates/core-dj-single-v2.php
 * Called by: apollo-events-manager/templates/single-event_dj.php
 *
 * This is the modular version using template parts for better maintainability.
 * Each section is in its own file under templates/parts/dj/
 *
 * CONTEXT CONTRACT (Required Variables):
 * --------------------------------------
 *
 * @var int    $dj_id              DJ post ID
 * @var string $dj_name            DJ display name (title)
 * @var string $dj_name_formatted  DJ name with line breaks for display
 * @var string $dj_photo_url       Hero photo URL
 * @var string $dj_tagline         Tagline/subtitle
 * @var string $dj_roles           Roles string (e.g., "DJ · Producer · Live Selector")
 * @var array  $dj_projects        Array of project/label names
 * @var string $dj_bio_excerpt     Short bio excerpt
 * @var string $dj_bio_full        Full bio HTML
 * @var string $dj_track_title     Featured track title
 * @var string $sc_embed_url       SoundCloud embed URL (full iframe src)
 * @var array  $music_links        Filtered music platform links
 * @var array  $social_links       Filtered social network links
 * @var array  $asset_links        Filtered asset links (media kit, rider)
 * @var array  $platform_links     Filtered other platform links
 * @var string $media_kit_url      Direct media kit URL for header button
 * @var bool   $is_print           Print mode flag
 *
 * LINK ARRAY STRUCTURE:
 * Each link: ['url' => string, 'icon' => string, 'label' => string]
 *
 * @package Apollo_Core
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

// =============================================================================
// DEFAULT VALUES
// =============================================================================

$dj_id             = $dj_id ?? 0;
$dj_name           = $dj_name ?? 'DJ';
$dj_name_formatted = $dj_name_formatted ?? esc_html( $dj_name );
$dj_photo_url      = $dj_photo_url ?? '';
$dj_tagline        = $dj_tagline ?? '';
$dj_roles          = $dj_roles ?? 'DJ';
$dj_projects       = $dj_projects ?? array();
$dj_bio_excerpt    = $dj_bio_excerpt ?? '';
$dj_bio_full       = $dj_bio_full ?? '';
$dj_track_title    = $dj_track_title ?? '';
$sc_embed_url      = $sc_embed_url ?? '';
$music_links       = $music_links ?? array();
$social_links      = $social_links ?? array();
$asset_links       = $asset_links ?? array();
$platform_links    = $platform_links ?? array();
$media_kit_url     = $media_kit_url ?? '';
$is_print          = $is_print ?? false;

// =============================================================================
// ASSET URLS
// =============================================================================

$cdn_base = 'https://assets.apollo.rio.br/';

$core_plugin_url = defined( 'APOLLO_CORE_PLUGIN_URL' )
	? APOLLO_CORE_PLUGIN_URL
	: plugin_dir_url( dirname( __DIR__ ) );

$img_base = $core_plugin_url . 'assets/img/';

// Fallback photo if none provided
if ( empty( $dj_photo_url ) ) {
	$dj_photo_url = $img_base . 'placeholder-dj.webp';
}

// =============================================================================
// TEMPLATE PARTS PATH
// =============================================================================

// Use a constant or global to make it accessible inside the function
$GLOBALS['apollo_dj_parts_dir'] = dirname( __FILE__ ) . '/parts/dj/';

/**
 * Load a DJ template part
 *
 * @param string $part_name Part filename without extension
 * @param array  $context   Variables to extract for the part
 */
if ( ! function_exists( 'apollo_dj_load_part' ) ) {
	function apollo_dj_load_part( $part_name, $context = array() ) {
		$parts_dir = $GLOBALS['apollo_dj_parts_dir'];
		$part_file = $parts_dir . $part_name . '.php';

		if ( file_exists( $part_file ) ) {
			extract( $context, EXTR_SKIP );
			include $part_file;
		}
	}
}

// =============================================================================
// DOCUMENT START
// =============================================================================
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $dj_name ); ?> · Apollo Roster</title>
	<link rel="icon" href="<?php echo esc_url( $img_base . 'neon-green.webp' ); ?>" type="image/webp">

	<?php
	/**
	 * Action hook before DJ single head assets
	 *
	 * @param int $dj_id DJ post ID
	 */
	do_action( 'apollo_dj_single_head_before', $dj_id );
	?>

	<!-- Apollo CDN Loader - Auto-loads CSS, icons, dark mode, etc. -->
	<script src="<?php echo esc_url( $cdn_base . 'index.min.js' ); ?>"></script>

	<!-- DJ Single Page Styles -->
	<link rel="stylesheet" href="<?php echo esc_url( $core_plugin_url . 'assets/css/dj-single.css' ); ?>">

	<?php if ( ! empty( $sc_embed_url ) ) : ?>
	<!-- SoundCloud Widget API -->
	<script src="https://w.soundcloud.com/player/api.js"></script>
	<?php endif; ?>

	<?php
	/**
	 * Action hook after DJ single head assets
	 *
	 * @param int $dj_id DJ post ID
	 */
	do_action( 'apollo_dj_single_head_after', $dj_id );
	?>
</head>
<body class="dj-single-page<?php echo $is_print ? ' is-print-mode' : ''; ?>" data-dj-id="<?php echo esc_attr( $dj_id ); ?>">

<?php
/**
 * Action hook at start of DJ single body
 *
 * @param int $dj_id DJ post ID
 */
do_action( 'apollo_dj_single_body_start', $dj_id );
?>

<section class="dj-shell">
	<div class="dj-page" id="djPage">
		<div class="dj-content">

			<?php
			// =================================================================
			// HEADER
			// =================================================================
			apollo_dj_load_part( 'header', compact( 'dj_name', 'media_kit_url' ) );

			// =================================================================
			// HERO SECTION
			// =================================================================
			apollo_dj_load_part( 'hero', compact(
				'dj_name',
				'dj_name_formatted',
				'dj_tagline',
				'dj_roles',
				'dj_projects',
				'dj_photo_url'
			) );

			// =================================================================
			// VINYL PLAYER (Only if SoundCloud URL exists)
			// =================================================================
			if ( ! $is_print && ! empty( $sc_embed_url ) ) {
				apollo_dj_load_part( 'player', compact(
					'dj_name_formatted',
					'dj_track_title',
					'sc_embed_url'
				) );
			}

			// =================================================================
			// INFO GRID (Bio & Links)
			// =================================================================
			apollo_dj_load_part( 'info-grid', compact(
				'dj_bio_excerpt',
				'music_links',
				'social_links',
				'asset_links',
				'platform_links'
			) );

			// =================================================================
			// FOOTER
			// =================================================================
			apollo_dj_load_part( 'footer', array() );
			?>

		</div>
	</div>
</section>

<?php
// =============================================================================
// BIO MODAL (Outside main shell for proper stacking)
// =============================================================================
if ( ! $is_print ) {
	apollo_dj_load_part( 'bio-modal', compact( 'dj_name', 'dj_bio_full' ) );
}
?>

<?php
/**
 * Action hook before DJ single footer scripts
 *
 * @param int $dj_id DJ post ID
 */
do_action( 'apollo_dj_single_before_scripts', $dj_id );
?>

<!-- DJ Single Page JavaScript -->
<script src="<?php echo esc_url( $core_plugin_url . 'assets/js/dj-single.js' ); ?>"></script>

<?php
/**
 * Action hook at end of DJ single body
 *
 * @param int $dj_id DJ post ID
 */
do_action( 'apollo_dj_single_body_end', $dj_id );

// Load the new official navbar via wp_footer hook
wp_footer();
?>

</body>
</html>
