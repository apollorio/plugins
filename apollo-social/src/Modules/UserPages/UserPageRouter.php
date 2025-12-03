<?php

namespace Apollo\Modules\UserPages;

use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Converts /id/{userID} requests into user_page single views.
 *
 * @category ApolloSocial
 * @package  ApolloSocial\UserPages
 * @author   Apollo Platform <tech@apollo.rio.br>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://apollo.rio.br
 */
class UserPageRouter {

	/**
	 * Adjust the main query when hitting the /id/{userID} route.
	 *
	 * @param WP_Query $query Main query instance.
	 *
	 * @return void
	 */
	public static function handleUserPageRequest( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$userId = (int) $query->get( UserPageRegistrar::QUERY_VAR );
		if ( $userId <= 0 ) {
			return;
		}

		$userPage = UserPageRepository::getOrCreate( $userId );
		if ( ! $userPage instanceof WP_Post ) {
			$query->set_404();
			return;
		}

		$query->set( 'post_type', UserPageRegistrar::POST_TYPE );
		$query->set( 'p', $userPage->ID );
		$query->set( 'posts_per_page', 1 );
		$query->set( 'name', $userPage->post_name );

		$query->is_single   = true;
		$query->is_singular = true;
		$query->is_page     = false;
		$query->is_home     = false;
		$query->is_404      = false;
	}

	/**
	 * Load the plugin fallback template when theme override is absent.
	 *
	 * @param string $template Current template path.
	 *
	 * @return string
	 */
	public static function maybeUsePluginTemplate( string $template ): string {
		$userId = (int) get_query_var( UserPageRegistrar::QUERY_VAR );
		if ( $userId <= 0 ) {
			return $template;
		}

		$pluginTemplate = APOLLO_SOCIAL_PLUGIN_DIR . 'templates/single-user_page.php';
		if ( file_exists( $pluginTemplate ) ) {
			return $pluginTemplate;
		}

		return $template;
	}
}
