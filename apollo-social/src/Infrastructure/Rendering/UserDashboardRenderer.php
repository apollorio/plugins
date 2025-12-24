<?php

namespace Apollo\Infrastructure\Rendering;

/**
 * User Dashboard Renderer
 * Renders customizable user dashboard page
 */
class UserDashboardRenderer {

	public function render() {
		// Check if this is /painel/ (own dashboard) or /id/{userID} (public profile)
		$is_painel = ( get_query_var( 'apollo_route' ) === 'user_dashboard' );

		if ( $is_painel ) {
			// Own dashboard - show tabs and full functionality
			return $this->renderOwnDashboard();
		} else {
			// Public profile - show customizable profile page
			return $this->renderPublicProfile();
		}
	}

	/**
	 * Render own dashboard (/painel/)
	 */
	private function renderOwnDashboard() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			wp_die( 'Você precisa estar logado para acessar esta página.', 'Acesso Negado', array( 'response' => 403 ) );
		}

		$user = get_user_by( 'ID', $user_id );

		// Get dashboard tabs data
		$tabs_data = $this->getDashboardTabsData( $user_id );

		// Use specific template for /painel/
		$template_path = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/users/dashboard-painel.php';

		return array(
			'title'                       => 'Painel - ' . $user->display_name,
			'content'                     => '',
			// Rendered by template
							'breadcrumbs' => array( 'Apollo Social', 'Painel' ),
			'template'                    => 'users/dashboard-painel.php',
			// Specific template
							'data'        => array(
								'user'             => array(
									'id'         => $user->ID,
									'login'      => $user->user_login,
									'name'       => $user->display_name,
									'email'      => $user->user_email,
									'avatar'     => get_avatar_url( $user->ID, array( 'size' => 200 ) ),
									'registered' => $user->user_registered,
									'bio'        => get_user_meta( $user->ID, 'description', true ),
								),
								'tabs'             => $tabs_data,
								'is_own_dashboard' => true,
							),
		);
	}

	/**
	 * Render public profile (/id/{userID} or /clubber/{userID})
	 */
	private function renderPublicProfile( $template_data ) {
		$user_id         = isset( $template_data['user_id'] ) ? absint( $template_data['user_id'] ) : get_current_user_id();
		$current_user_id = get_current_user_id();
		$is_own_profile  = ( $user_id === $current_user_id );

		if ( ! $user_id ) {
			return array(
				'title'       => 'Perfil - Usuário não encontrado',
				'content'     => '<p>Usuário não encontrado.</p>',
				'breadcrumbs' => array( 'Apollo Social', 'Perfil' ),
				'data'        => array(),
			);
		}

		$user = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			return array(
				'title'       => 'Perfil - Usuário não encontrado',
				'content'     => '<p>Usuário não encontrado.</p>',
				'breadcrumbs' => array( 'Apollo Social', 'Perfil' ),
				'data'        => array(),
			);
		}

		// Get user page
		$user_page = apollo_get_or_create_user_page( $user_id );

		// Get widgets layout
		$widgets = get_post_meta( $user_page->ID, '_apollo_widgets', true );
		if ( ! is_array( $widgets ) ) {
			$widgets = array();
		}

		// Get depoimentos (comments)
		$depoimentos = $this->getDepoimentos( $user_id );

		return array(
			'title'                       => 'Perfil de ' . $user->display_name,
			'content'                     => '',
			// Rendered by template
							'breadcrumbs' => array( 'Apollo Social', 'Perfil', $user->display_name ),
			'data'                        => array(
				'user'           => array(
					'id'         => $user->ID,
					'login'      => $user->user_login,
					'name'       => $user->display_name,
					'email'      => $user->user_email,
					'avatar'     => get_avatar_url( $user->ID, array( 'size' => 200 ) ),
					'registered' => $user->user_registered,
					'bio'        => get_user_meta( $user->ID, 'description', true ),
				),
				'widgets'        => $widgets,
				'depoimentos'    => $depoimentos,
				'can_edit'       => $is_own_profile || current_user_can( 'edit_post', $user_page->ID ),
				'page_id'        => $user_page->ID,
				'is_own_profile' => $is_own_profile,
			),
		);
	}

	/**
	 * P0-11: Get dashboard tabs data
	 */
	private function getDashboardTabsData( $user_id ) {
		return array(
			'events'      => array(
				'title' => 'Eventos favoritos',
				'icon'  => 'ri-heart-3-line',
				'data'  => $this->getFavoriteEvents( $user_id ),
			),
			'my_events'   => array(
				'title' => 'Meus eventos',
				'icon'  => 'ri-calendar-event-line',
				'data'  => $this->getMyEvents( $user_id ),
			),
			'metrics'     => array(
				'title' => 'Meus números',
				'icon'  => 'ri-bar-chart-2-line',
				'data'  => $this->getUserMetrics( $user_id ),
			),
			'nucleo'      => array(
				'title' => 'Núcleo (privado)',
				'icon'  => 'ri-lock-2-line',
				'data'  => $this->getNucleos( $user_id ),
			),
			'communities' => array(
				'title' => 'Comunidades',
				'icon'  => 'ri-community-line',
				'data'  => $this->getCommunities( $user_id ),
			),
			'docs'        => array(
				'title' => 'Documentos',
				'icon'  => 'ri-file-text-line',
				'data'  => $this->getDocuments( $user_id ),
			),
		);
	}

	/**
	 * P0-11: Get favorite events (using unified favorites system)
	 */
	private function getFavoriteEvents( $user_id ) {
		$user_id = absint( $user_id );

		// P0-11: Use unified favorites system (_apollo_favorites meta)
		$user_favorites = get_user_meta( $user_id, '_apollo_favorites', true );
		if ( ! is_array( $user_favorites ) || ! isset( $user_favorites['event_listing'] ) ) {
			return array();
		}

		$event_ids = array_map( 'absint', $user_favorites['event_listing'] );
		if ( empty( $event_ids ) ) {
			return array();
		}

		$events = array();
		foreach ( $event_ids as $event_id ) {
			$post = get_post( $event_id );
			if ( $post && 'event_listing' === $post->post_type && 'publish' === $post->post_status ) {
				$start_date = get_post_meta( $event_id, '_event_start_date', true );
				$start_time = get_post_meta( $event_id, '_event_start_time', true );
				$local_id   = get_post_meta( $event_id, '_event_local_id', true );
				$local_name = $local_id ? get_the_title( $local_id ) : '';

				$events[] = array(
					'id'        => $post->ID,
					'title'     => $post->post_title,
					'permalink' => get_permalink( $post->ID ),
					'date'      => $start_date,
					'time'      => $start_time,
					'local'     => $local_name,
					'excerpt'   => get_the_excerpt( $event_id ),
				);
			}
		}

		// Sort by date (upcoming first)
		usort(
			$events,
			function ( $a, $b ) {
				$date_a = $a['date'] ?? '';
				$date_b = $b['date'] ?? '';

				return strcmp( $date_a, $date_b );
			}
		);

		return $events;
	}

	/**
	 * P0-11: Get user's own events (author or co-author)
	 */
	private function getMyEvents( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! post_type_exists( 'event_listing' ) ) {
			return array();
		}

		// Get events where user is author
		$authored = get_posts(
			array(
				'post_type'      => 'event_listing',
				'author'         => $user_id,
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft', 'pending' ),
			)
		);

		// Get events where user is co-author
		$coauthored = get_posts(
			array(
				'post_type'      => 'event_listing',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'meta_query'     => array(
					array(
						'key'     => '_event_co_authors',
						'value'   => serialize( strval( $user_id ) ),
						'compare' => 'LIKE',
					),
				),
			)
		);

		// Merge and deduplicate
		$all_events = array();
		$seen_ids   = array();

		foreach ( $authored as $event ) {
			if ( ! in_array( $event->ID, $seen_ids ) ) {
				$all_events[] = $event;
				$seen_ids[]   = $event->ID;
			}
		}

		foreach ( $coauthored as $event ) {
			if ( ! in_array( $event->ID, $seen_ids, true ) ) {
				$all_events[] = $event;
				$seen_ids[]   = $event->ID;
			}
		}

		// Format events
		$formatted = array();
		foreach ( $all_events as $event ) {
			$start_date = get_post_meta( $event->ID, '_event_start_date', true );
			$is_author  = ( (int) $event->post_author === $user_id );

			$formatted[] = array(
				'id'          => $event->ID,
				'title'       => $event->post_title,
				'permalink'   => get_permalink( $event->ID ),
				'status'      => $event->post_status,
				'date'        => $start_date,
				'is_author'   => $is_author,
				'is_coauthor' => ! $is_author,
			);
		}

		// Sort by date
		usort(
			$formatted,
			function ( $a, $b ) {
				$date_a = $a['date'] ?? '';
				$date_b = $b['date'] ?? '';

				return strcmp( $date_a, $date_b );
			}
		);

		return $formatted;
	}

	/**
	 * P0-11: Get user metrics (enhanced)
	 */
	private function getUserMetrics( $user_id ) {
		$user_id = absint( $user_id );

		$user_page = apollo_get_user_page( $user_id );
		$page_id   = $user_page ? $user_page->ID : 0;

		// P0-11: Get social posts count
		$social_posts = count_user_posts( $user_id, 'apollo_social_post' );

		// P0-11: Get events count (authored + co-authored)
		$my_events    = $this->getMyEvents( $user_id );
		$events_count = count( $my_events );

		// P0-11: Get DJ events count (if user is DJ)
		$dj_events_count = 0;
		if ( post_type_exists( 'event_dj' ) ) {
			$dj_posts = get_posts(
				array(
					'post_type'      => 'event_dj',
					'author'         => $user_id,
					'posts_per_page' => -1,
				)
			);
			if ( ! empty( $dj_posts ) ) {
				$dj_id           = $dj_posts[0]->ID;
				$events_with_dj  = get_posts(
					array(
						'post_type'      => 'event_listing',
						'posts_per_page' => -1,
						'meta_query'     => array(
							array(
								'key'     => '_event_dj_ids',
								'value'   => serialize( strval( $dj_id ) ),
								'compare' => 'LIKE',
							),
						),
					)
				);
				$dj_events_count = count( $events_with_dj );
			}
		}//end if

		// P0-11: Get likes count (from likes table)
		$likes_count = 0;
		global $wpdb;
		$likes_table = $wpdb->prefix . 'apollo_likes';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $likes_table ) ) === $likes_table ) {
			$likes_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$likes_table} WHERE user_id = %d",
					$user_id
				)
			);
		}

		return array(
			'posts'       => $social_posts,
			'events'      => $events_count,
			'dj_events'   => $dj_events_count,
			'comments'    => $page_id ? get_comments_number( $page_id ) : 0,
			'favorites'   => count( $this->getFavoriteEvents( $user_id ) ),
			'likes_given' => $likes_count,
			'communities' => count( $this->getCommunities( $user_id ) ),
			'nucleos'     => count( $this->getNucleos( $user_id ) ),
			'documents'   => count( $this->getDocuments( $user_id ) ),
		);
	}

	/**
	 * P0-11: Get nucleos (from groups table)
	 */
	private function getNucleos( $user_id ) {
		$user_id = absint( $user_id );

		global $wpdb;
		$groups_table  = $wpdb->prefix . 'apollo_groups';
		$members_table = $wpdb->prefix . 'apollo_group_members';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $groups_table ) ) !== $groups_table ) {
			return array();
		}

		// Get nucleos where user is member
		$nucleos = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT g.*, m.role, m.status as member_status
             FROM {$groups_table} g
             INNER JOIN {$members_table} m ON g.id = m.group_id
             WHERE m.user_id = %d AND g.type = 'nucleo' AND g.status = 'approved'
             ORDER BY g.created_at DESC",
				$user_id
			)
		);

		$formatted = array();
		foreach ( $nucleos as $nucleo ) {
			$formatted[] = array(
				'id'            => $nucleo->id,
				'title'         => $nucleo->title,
				'slug'          => $nucleo->slug,
				'description'   => $nucleo->description,
				'role'          => $nucleo->role,
				'member_status' => $nucleo->member_status,
			);
		}

		return $formatted;
	}

	/**
	 * P0-11: Get communities (from groups table)
	 */
	private function getCommunities( $user_id ) {
		$user_id = absint( $user_id );

		global $wpdb;
		$groups_table  = $wpdb->prefix . 'apollo_groups';
		$members_table = $wpdb->prefix . 'apollo_group_members';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $groups_table ) ) !== $groups_table ) {
			return array();
		}

		// Get communities where user is member
		$communities = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT g.*, m.role, m.status as member_status
             FROM {$groups_table} g
             INNER JOIN {$members_table} m ON g.id = m.group_id
             WHERE m.user_id = %d AND g.type = 'comunidade' AND g.status = 'approved'
             ORDER BY g.created_at DESC",
				$user_id
			)
		);

		$formatted = array();
		foreach ( $communities as $community ) {
			$formatted[] = array(
				'id'            => $community->id,
				'title'         => $community->title,
				'slug'          => $community->slug,
				'description'   => $community->description,
				'role'          => $community->role,
				'member_status' => $community->member_status,
			);
		}

		return $formatted;
	}

	/**
	 * Get documents
	 */
	private function getDocuments( $user_id ) {
		$user_id = absint( $user_id );

		// Get from documents system if available
		if ( function_exists( 'apollo_get_user_documents' ) ) {
			return apollo_get_user_documents( $user_id );
		}

		// Fallback: get from user meta
		$doc_ids = get_user_meta( $user_id, 'apollo_documents', true );

		if ( ! is_array( $doc_ids ) || empty( $doc_ids ) ) {
			return array();
		}

		$documents = array();
		foreach ( $doc_ids as $doc_id ) {
			$doc_id = absint( $doc_id );
			if ( $doc_id ) {
				$post = get_post( $doc_id );
				if ( $post && 'publish' === $post->post_status ) {
					$documents[] = array(
						'id'      => $post->ID,
						'title'   => $post->post_title,
						'status'  => get_post_meta( $post->ID, '_apollo_doc_status', true ) ?: 'draft',
						'updated' => $post->post_modified,
					);
				}
			}
		}

		return $documents;
	}

	/**
	 * Get depoimentos (comments) for user
	 */
	private function getDepoimentos( $user_id ) {
		$user_page = apollo_get_user_page( $user_id );

		if ( ! $user_page ) {
			return array();
		}

		$args = array(
			'post_id' => $user_page->ID,
			'status'  => 'approve',
			'orderby' => 'comment_date',
			'order'   => 'DESC',
			'number'  => 50,
		);

		$comments    = get_comments( $args );
		$depoimentos = array();

		foreach ( $comments as $comment ) {
			$depoimentos[] = array(
				'id'             => $comment->comment_ID,
				'author'         => array(
					'id'     => $comment->user_id,
					'name'   => $comment->comment_author,
					'avatar' => get_avatar_url( $comment->user_id ? $comment->user_id : $comment->comment_author_email ),
				),
				'content'        => $comment->comment_content,
				'date'           => $comment->comment_date,
				'date_formatted' => human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) . ' atrás',
			);
		}

		return $depoimentos;
	}
}
