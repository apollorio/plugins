<?php
namespace Apollo\Infrastructure\Rendering;

use WP_Query;

/**
 * Feed Renderer
 * FASE 2: Feed Social Unificado com múltiplas fontes
 */
class FeedRenderer {

	private $current_user_id;

	public function __construct() {
		$this->current_user_id = get_current_user_id();
	}

	public function render( $template_data ) {
		// Get current user
		$current_user = wp_get_current_user();

		// FASE 2: Obter feed unificado de múltiplas fontes
		$feed_posts = $this->getUnifiedFeedPosts();

		return array(
			'title'                       => 'Feed',
			'content'                     => '', 
			// Will be rendered by template
							'breadcrumbs' => array( 'Apollo Social', 'Feed' ),
			'data'                        => array(
				'posts'        => $feed_posts,
				'current_user' => array(
					'id'     => $current_user->ID,
					'name'   => $current_user->display_name,
					'avatar' => get_avatar_url( $current_user->ID ),
				),
			),
		);
	}

	/**
	 * FASE 2: Obter posts de múltiplas fontes e mesclar por data
	 */
	public function getUnifiedFeedPosts( $page = 1, $per_page = 20 ) {
		$all_items = array();

		// 1. Posts de usuários (apollo_social_post)
		$user_posts = $this->getUserPosts( $per_page );
		foreach ( $user_posts as $post ) {
			$all_items[] = array(
				'type' => 'user_post',
				'id'   => $post->ID,
				'date' => $post->post_date,
				'data' => $this->formatUserPost( $post ),
			);
		}

		// 2. Eventos do Apollo Events Manager
		$events = $this->getEvents( $per_page / 2 );
		foreach ( $events as $event ) {
			$all_items[] = array(
				'type' => 'event',
				'id'   => $event->ID,
				'date' => get_post_meta( $event->ID, '_event_start_date', true ) ?: $event->post_date,
				'data' => $this->formatEvent( $event ),
			);
		}

		// 3. Anúncios/Classificados (se existir CPT ou tabela)
		$ads = $this->getAds( $per_page / 4 );
		foreach ( $ads as $ad ) {
			$all_items[] = array(
				'type' => 'ad',
				'id'   => $ad->ID ?? $ad->id,
				'date' => $ad->post_date ?? $ad->created_at ?? date( 'Y-m-d H:i:s' ),
				'data' => $this->formatAd( $ad ),
			);
		}

		// 4. Notícias (posts WordPress com categoria específica)
		$news = $this->getNews( $per_page / 4 );
		foreach ( $news as $news_item ) {
			$all_items[] = array(
				'type' => 'news',
				'id'   => $news_item->ID,
				'date' => $news_item->post_date,
				'data' => $this->formatNews( $news_item ),
			);
		}

		// Ordenar por data (mais recente primeiro)
		usort(
			$all_items,
			function ( $a, $b ) {
				return strtotime( $b['date'] ) - strtotime( $a['date'] );
			}
		);

		// Paginação: retornar apenas os itens da página solicitada
		$offset = ( $page - 1 ) * $per_page;
		return array_slice( $all_items, $offset, $per_page );
	}

	/**
	 * Obter posts de usuários
	 */
	private function getUserPosts( $limit = 10 ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'apollo_social_post',
				'posts_per_page' => $limit,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		return $query->posts;
	}

	/**
	 * Formatar post de usuário
	 * P0-5: Inclui detecção de Spotify/SoundCloud
	 */
	private function formatUserPost( $post ) {
		$author_id     = $post->post_author;
		$like_count    = $this->getLikeCount( 'apollo_social_post', $post->ID );
		$comment_count = get_comments_number( $post->ID );
		$user_liked    = $this->current_user_id ? $this->userLiked( 'apollo_social_post', $post->ID ) : false;

		$content = $post->post_content;

		// P0-5: Detectar URLs de Spotify/SoundCloud
		$media_embeds = array();
		if ( class_exists( '\Apollo\Helpers\MediaEmbedHelper' ) ) {
			$detected_media = \Apollo\Helpers\MediaEmbedHelper::detectMediaUrls( $content );
			if ( ! empty( $detected_media['spotify'] ) || ! empty( $detected_media['soundcloud'] ) ) {
				$media_embeds = $detected_media;
			}
		}

		return array(
			'id'            => $post->ID,
			'title'         => get_the_title( $post->ID ),
			'content'       => apply_filters( 'the_content', $content ),
			'excerpt'       => get_the_excerpt( $post->ID ),
			'author'        => array(
				'id'     => $author_id,
				'name'   => get_the_author_meta( 'display_name', $author_id ),
				'avatar' => get_avatar_url( $author_id ),
			),
			'date'          => get_the_date( 'c', $post->ID ),
			'permalink'     => get_permalink( $post->ID ),
			'thumbnail'     => get_the_post_thumbnail_url( $post->ID, 'medium' ),
			'like_count'    => $like_count,
			'comment_count' => $comment_count,
			'user_liked'    => $user_liked,
			'media_embeds'  => $media_embeds, 
		// P0-5: Spotify/SoundCloud embeds
		);
	}

	/**
	 * Obter eventos
	 */
	private function getEvents( $limit = 5 ) {
		if ( ! post_type_exists( 'event_listing' ) ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'event_listing',
				'posts_per_page' => $limit,
				'post_status'    => 'publish',
				'meta_key'       => '_event_start_date',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'     => '_event_start_date',
						'value'   => date( 'Y-m-d' ),
						'compare' => '>=',
					),
				),
			)
		);

		return $query->posts;
	}

	/**
	 * Formatar evento
	 */
	private function formatEvent( $event ) {
		$author_id      = $event->post_author;
		$like_count     = $this->getLikeCount( 'event_listing', $event->ID );
		$user_liked     = $this->current_user_id ? $this->userLiked( 'event_listing', $event->ID ) : false;
		$user_favorited = $this->current_user_id ? $this->userFavoritedEvent( $event->ID ) : false;

		// Usar helper do Apollo Events Manager se disponível
		$start_date = get_post_meta( $event->ID, '_event_start_date', true );
		$start_time = get_post_meta( $event->ID, '_event_start_time', true );
		$local_id   = get_post_meta( $event->ID, '_event_local_id', true );
		$local_name = $local_id ? get_the_title( $local_id ) : '';

		return array(
			'id'             => $event->ID,
			'title'          => get_the_title( $event->ID ),
			'excerpt'        => get_the_excerpt( $event->ID ),
			'author'         => array(
				'id'     => $author_id,
				'name'   => get_the_author_meta( 'display_name', $author_id ),
				'avatar' => get_avatar_url( $author_id ),
			),
			'date'           => $start_date ?: get_the_date( 'c', $event->ID ),
			'start_date'     => $start_date,
			'start_time'     => $start_time,
			'local'          => $local_name,
			'permalink'      => get_permalink( $event->ID ),
			'thumbnail'      => get_the_post_thumbnail_url( $event->ID, 'medium' ),
			'like_count'     => $like_count,
			'user_liked'     => $user_liked,
			'user_favorited' => $user_favorited,
		);
	}

	/**
	 * Obter anúncios
	 */
	private function getAds( $limit = 5 ) {
		// Tentar CPT primeiro
		if ( post_type_exists( 'apollo_ad' ) ) {
			$query = new WP_Query(
				array(
					'post_type'      => 'apollo_ad',
					'posts_per_page' => $limit,
					'post_status'    => 'publish',
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			);
			return $query->posts;
		}

		// Tentar tabela custom
		global $wpdb;
		$table_name = $wpdb->prefix . 'apollo_ads';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $table_name WHERE status = 'published' ORDER BY created_at DESC LIMIT %d",
					$limit
				)
			);
		}

		return array();
	}

	/**
	 * Formatar anúncio
	 */
	private function formatAd( $ad ) {
		if ( is_object( $ad ) && isset( $ad->post_type ) ) {
			// É um post WordPress
			return array(
				'id'        => $ad->ID,
				'title'     => get_the_title( $ad->ID ),
				'excerpt'   => get_the_excerpt( $ad->ID ),
				'permalink' => get_permalink( $ad->ID ),
				'thumbnail' => get_the_post_thumbnail_url( $ad->ID, 'medium' ),
			);
		}

		// É da tabela custom
		return array(
			'id'                        => $ad->id,
			'title'                     => $ad->title ?? '',
			'excerpt'                   => $ad->description ?? '',
			'permalink'                 => '#', 
			// Implementar rota se necessário
							'thumbnail' => $ad->image_url ?? '',
		);
	}

	/**
	 * Obter notícias
	 */
	private function getNews( $limit = 5 ) {
		$query = new WP_Query(
			array(
				'post_type'                               => 'post',
				'posts_per_page'                          => $limit,
				'post_status'                             => 'publish',
				'category_name'                           => 'noticias', 
				// Ajustar conforme necessário
												'orderby' => 'date',
				'order'                                   => 'DESC',
			)
		);

		return $query->posts;
	}

	/**
	 * Formatar notícia
	 */
	private function formatNews( $news ) {
		return array(
			'id'        => $news->ID,
			'title'     => get_the_title( $news->ID ),
			'excerpt'   => get_the_excerpt( $news->ID ),
			'permalink' => get_permalink( $news->ID ),
			'thumbnail' => get_the_post_thumbnail_url( $news->ID, 'medium' ),
		);
	}

	/**
	 * Obter contagem de likes
	 */
	private function getLikeCount( $content_type, $content_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'apollo_likes';

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE content_type = %s AND content_id = %d",
				$content_type,
				$content_id
			)
		);
	}

	/**
	 * Verificar se usuário curtiu
	 */
	private function userLiked( $content_type, $content_id ) {
		if ( ! $this->current_user_id ) {
			return false;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'apollo_likes';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE content_type = %s AND content_id = %d AND user_id = %d",
				$content_type,
				$content_id,
				$this->current_user_id
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Verificar se usuário favoritou evento
	 */
	/**
	 * P0-6: Check if user favorited event (unified favorites system)
	 */
	private function userFavoritedEvent( $event_id ) {
		if ( ! $this->current_user_id ) {
			return false;
		}

		// Use unified favorites system
		$user_favorites = get_user_meta( $this->current_user_id, 'apollo_favorites', true );
		if ( ! is_array( $user_favorites ) ) {
			return false;
		}

		// Check event_listing favorites
		if ( isset( $user_favorites['event_listing'] ) && is_array( $user_favorites['event_listing'] ) ) {
			return in_array( $event_id, $user_favorites['event_listing'], true );
		}

		return false;
	}
}
