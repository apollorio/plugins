<?php

/**
 * P0-8: Playlist Widget
 *
 * Widget for displaying Spotify/SoundCloud playlists on user pages.
 *
 * @package Apollo_Social
 * @version 2.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

class Apollo_Widget_Playlist
{
    /**
     * P0-8: Render playlist widget
     */
    public static function render($props, $context)
    {
        $spotify_url    = $props['spotify_url']    ?? '';
        $soundcloud_url = $props['soundcloud_url'] ?? '';
        $title          = $props['title']          ?? 'Playlist';

        $html = '<div class="apollo-widget-playlist shadcn-card rounded-lg border bg-card text-card-foreground shadow-sm">';
        $html .= '<div class="shadcn-card-header pb-2">';
        $html .= '<h3 class="shadcn-card-title text-lg font-semibold flex items-center gap-2">';
        $html .= '<i class="ri-music-2-line"></i>';
        $html .= '<span>' . esc_html($title) . '</span>';
        $html .= '</h3>';
        $html .= '</div>';
        $html .= '<div class="shadcn-card-content">';

        // Render Spotify embed if available
        if (! empty($spotify_url) && class_exists('\Apollo\Helpers\MediaEmbedHelper')) {
            $media = \Apollo\Helpers\MediaEmbedHelper::detectMediaUrls($spotify_url);
            if (! empty($media['spotify'])) {
                $spotify = $media['spotify'][0];
                $html .= \Apollo\Helpers\MediaEmbedHelper::renderSpotifyEmbed(
                    $spotify['id'],
                    $spotify['type'],
                    [
                        'width'  => '100%',
                        'height' => $spotify['type'] === 'track' ? '152' : '352',
                    ]
                );
            }
        }

        // Render SoundCloud embed if available
        if (! empty($soundcloud_url) && class_exists('\Apollo\Helpers\MediaEmbedHelper')) {
            $media = \Apollo\Helpers\MediaEmbedHelper::detectMediaUrls($soundcloud_url);
            if (! empty($media['soundcloud'])) {
                $soundcloud = $media['soundcloud'][0];
                $html .= \Apollo\Helpers\MediaEmbedHelper::renderSoundCloudEmbed(
                    $soundcloud['url'],
                    [
                        'width'  => '100%',
                        'height' => '166',
                    ]
                );
            }
        }

        // Empty state
        if (empty($spotify_url) && empty($soundcloud_url)) {
            $html .= '<div class="flex flex-col items-center justify-center py-8 text-center">';
            $html .= '<i class="ri-music-2-line text-4xl text-muted-foreground mb-2"></i>';
            $html .= '<p class="text-sm text-muted-foreground">Nenhuma playlist adicionada ainda.</p>';
            $html .= '<p class="text-xs text-muted-foreground mt-1">Adicione uma playlist do Spotify ou SoundCloud!</p>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * P0-8: Get widget schema
     */
    public static function getSchema()
    {
        return [
            'title'       => 'Playlist',
            'icon'        => 'music-2-line',
            'propsSchema' => [
                'title' => [
                    'type'    => 'string',
                    'default' => 'Playlist',
                    'label'   => 'Título',
                ],
                'spotify_url' => [
                    'type'        => 'string',
                    'default'     => '',
                    'label'       => 'URL do Spotify',
                    'placeholder' => 'https://open.spotify.com/playlist/...',
                ],
                'soundcloud_url' => [
                    'type'        => 'string',
                    'default'     => '',
                    'label'       => 'URL do SoundCloud',
                    'placeholder' => 'https://soundcloud.com/...',
                ],
            ],
        ];
    }
}
