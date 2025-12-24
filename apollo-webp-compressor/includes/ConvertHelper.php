<?php
/**
 * Apollo WebP Conversion Helper
 * Uses only GD or Imagick, no external binaries/services
 *
 * @package Apollo_WebP_Compressor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apollo WebP Convert Helper
 */
class ApolloWebPConvertHelper {

	/**
	 * Convert image to WebP format
	 *
	 * @param string $source Source image path.
	 * @param string $destination Destination WebP path.
	 * @return bool True on success, false on failure.
	 */
	public static function convert( $source, $destination ) {
		if ( ! file_exists( $source ) ) {
			return false;
		}

		$quality = get_option( 'apollo_webp_quality', 75 );

		// Try Imagick first (better quality).
		if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
			return self::convert_imagick( $source, $destination, $quality );
		}

		// Fallback to GD.
		if ( extension_loaded( 'gd' ) && function_exists( 'imagewebp' ) ) {
			return self::convert_gd( $source, $destination, $quality );
		}

		// No conversion method available.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Logging conversion failures is necessary.
		error_log( 'Apollo WebP: Neither Imagick nor GD with WebP support is available.' );

		return false;
	}

	/**
	 * Convert using Imagick
	 *
	 * @param string $source Source image path.
	 * @param string $destination Destination WebP path.
	 * @param int    $quality Quality (50-95).
	 * @return bool True on success, false on failure.
	 */
	private static function convert_imagick( $source, $destination, $quality ) {
		try {
			$image = new Imagick( $source );

			// Set image format to WebP.
			$image->setImageFormat( 'webp' );

			// Set compression quality.
			$image->setImageCompressionQuality( $quality );

			// Strip metadata for smaller file size.
			$image->stripImage();

			// Write image.
			$result = $image->writeImage( $destination );

			// Clean up.
			$image->clear();
			$image->destroy();

			return $result;
		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Logging conversion failures is necessary.
			error_log( 'Apollo WebP Imagick conversion failed: ' . $e->getMessage() );

			return false;
		}
	}

	/**
	 * Convert using GD
	 *
	 * @param string $source Source image path.
	 * @param string $destination Destination WebP path.
	 * @param int    $quality Quality (50-95).
	 * @return bool True on success, false on failure.
	 */
	private static function convert_gd( $source, $destination, $quality ) {
		$ext = strtolower( pathinfo( $source, PATHINFO_EXTENSION ) );

		// Load source image based on type.
		switch ( $ext ) {
			case 'jpg':
			case 'jpeg':
				$image = imagecreatefromjpeg( $source );
				break;
			case 'png':
				$image = imagecreatefrompng( $source );
				// Preserve transparency for PNG.
				imagealphablending( $image, false );
				imagesavealpha( $image, true );
				break;
			default:
				return false;
		}

		if ( ! $image ) {
			return false;
		}

		// Convert to WebP.
		$result = imagewebp( $image, $destination, $quality );

		// Clean up.
		imagedestroy( $image );

		return $result;
	}

	/**
	 * Check if WebP conversion is available.
	 *
	 * @return bool True if GD or Imagick with WebP support is available.
	 */
	public static function is_available() {
		// Check Imagick.
		if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
			$imagick = new Imagick();
			$formats = $imagick->queryFormats();
			if ( in_array( 'WEBP', $formats, true ) ) {
				return true;
			}
		}

		// Check GD.
		if ( extension_loaded( 'gd' ) && function_exists( 'imagewebp' ) ) {
			return true;
		}

		return false;
	}
}
