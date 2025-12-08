<?php
/**
 * P0-5: Media Embed Helper
 *
 * Detects and converts Spotify/SoundCloud URLs to embeddable iframes.
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

namespace Apollo\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MediaEmbedHelper {

	/**
	 * P0-5: Detect media URLs in content and return embed info
	 */
	public static function detectMediaUrls( string $content ): array {
		$media = [
			'spotify'    => [],
			'soundcloud' => [],
		];

		// Detect Spotify URLs
		$spotify_patterns = [
			'/https?:\/\/(?:open\.)?spotify\.com\/(?:track|album|playlist|artist)\/([a-zA-Z0-9]+)/i',
			'/spotify:(?:track|album|playlist|artist):([a-zA-Z0-9]+)/i',
		];

		foreach ( $spotify_patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches ) ) {
				foreach ( $matches[0] as $index => $url ) {
					$id                 = $matches[1][ $index ];
					$type               = self::extractSpotifyType( $url );
					$media['spotify'][] = [
						'url'       => $url,
						'id'        => $id,
						'type'      => $type,
						'embed_url' => self::getSpotifyEmbedUrl( $id, $type ),
					];
				}
			}
		}

		// Detect SoundCloud URLs
		$soundcloud_patterns = [
			'/https?:\/\/(?:www\.)?soundcloud\.com\/([^\/\s]+)\/([^\/\s]+)/i',
			'/https?:\/\/snd\.sc\/([a-zA-Z0-9]+)/i',
		];

		foreach ( $soundcloud_patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches ) ) {
				foreach ( $matches[0] as $index => $url ) {
					$media['soundcloud'][] = [
						'url'       => $url,
						'embed_url' => self::getSoundCloudEmbedUrl( $url ),
					];
				}
			}
		}

		return $media;
	}

	/**
	 * P0-5: Extract Spotify type from URL
	 */
	private static function extractSpotifyType( string $url ): string {
		if ( strpos( $url, '/track/' ) !== false ) {
			return 'track';
		} elseif ( strpos( $url, '/album/' ) !== false ) {
			return 'album';
		} elseif ( strpos( $url, '/playlist/' ) !== false ) {
			return 'playlist';
		} elseif ( strpos( $url, '/artist/' ) !== false ) {
			return 'artist';
		} elseif ( strpos( $url, 'spotify:track:' ) !== false ) {
			return 'track';
		} elseif ( strpos( $url, 'spotify:album:' ) !== false ) {
			return 'album';
		} elseif ( strpos( $url, 'spotify:playlist:' ) !== false ) {
			return 'playlist';
		}
		return 'track';
		// Default
	}

	/**
	 * P0-5: Get Spotify embed URL
	 */
	public static function getSpotifyEmbedUrl( string $id, string $type = 'track' ): string {
		$base_url = 'https://open.spotify.com/embed';
		return "{$base_url}/{$type}/{$id}?utm_source=generator";
	}

	/**
	 * P0-5: Get SoundCloud embed URL
	 */
	public static function getSoundCloudEmbedUrl( string $url ): string {
		return 'https://w.soundcloud.com/player/?url=' . urlencode( $url ) . '&color=%23ff5500&auto_play=false&hide_related=false&show_comments=true&show_user=true&show_reposts=false&show_teaser=true&visual=true';
	}

	/**
	 * P0-5: Render Spotify embed HTML
	 */
	public static function renderSpotifyEmbed( string $id, string $type = 'track', array $attrs = [] ): string {
		$width     = $attrs['width'] ?? '100%';
		$height    = $attrs['height'] ?? $type === 'track' ? '152' : '352';
		$embed_url = self::getSpotifyEmbedUrl( $id, $type );

		return sprintf(
			'<iframe src="%s" width="%s" height="%s" frameborder="0" allowtransparency="true" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy" class="apollo-spotify-embed"></iframe>',
			esc_url( $embed_url ),
			esc_attr( $width ),
			esc_attr( $height )
		);
	}

	/**
	 * P0-5: Render SoundCloud embed HTML
	 */
	public static function renderSoundCloudEmbed( string $url, array $attrs = [] ): string {
		$width     = $attrs['width'] ?? '100%';
		$height    = $attrs['height'] ?? '166';
		$embed_url = self::getSoundCloudEmbedUrl( $url );

		return sprintf(
			'<iframe src="%s" width="%s" height="%s" frameborder="no" scrolling="no" allow="autoplay" class="apollo-soundcloud-embed"></iframe>',
			esc_url( $embed_url ),
			esc_attr( $width ),
			esc_attr( $height )
		);
	}

	/**
	 * P0-5: Process content and replace URLs with embeds
	 */
	public static function processContent( string $content ): string {
		$media     = self::detectMediaUrls( $content );
		$processed = $content;

		// Replace Spotify URLs with embeds
		foreach ( $media['spotify'] as $spotify ) {
			$embed     = self::renderSpotifyEmbed( $spotify['id'], $spotify['type'] );
			$processed = str_replace( $spotify['url'], $embed, $processed );
		}

		// Replace SoundCloud URLs with embeds
		foreach ( $media['soundcloud'] as $soundcloud ) {
			$embed     = self::renderSoundCloudEmbed( $soundcloud['url'] );
			$processed = str_replace( $soundcloud['url'], $embed, $processed );
		}

		return $processed;
	}
}
