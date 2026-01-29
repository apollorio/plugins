<?php
/**
 * Apollo Social Dependency Injection Container Bootstrap
 *
 * Uses lucatume/di52 for lightweight WordPress-compatible DI.
 *
 * @package Apollo\Social
 * @since 3.0.0
 */

declare(strict_types=1);

namespace Apollo\Social;

use lucatume\DI52\Container;

/**
 * Apollo DI Container singleton.
 */
final class ApolloContainer {

	/**
	 * Container instance.
	 *
	 * @var Container|null
	 */
	private static ?Container $instance = null;

	/**
	 * Get container instance.
	 *
	 * @return Container
	 */
	public static function getInstance(): Container {
		if ( null === self::$instance ) {
			self::$instance = new Container();
			self::bootstrap();
		}
		return self::$instance;
	}

	/**
	 * Bootstrap the container with service definitions.
	 *
	 * @return void
	 */
	private static function bootstrap(): void {
		$container = self::$instance;

		// =========================================================================
		// Core Services
		// =========================================================================

		// Register database wrapper as singleton.
		$container->singleton(
			\Apollo\Core\Database\SafeQuery::class,
			static fn() => new \Apollo\Core\Database\SafeQuery()
		);

		// =========================================================================
		// Infrastructure Services
		// =========================================================================

		// Feature Flags
		$container->singleton(
			Infrastructure\FeatureFlags::class,
			static fn() => new Infrastructure\FeatureFlags()
		);

		// Nonces helper
		$container->singleton(
			Infrastructure\Security\Nonces::class,
			static fn() => new Infrastructure\Security\Nonces()
		);

		// =========================================================================
		// Module Services
		// =========================================================================

		// Groups Module
		$container->singleton(
			Modules\Groups\GroupsModule::class,
			static fn( Container $c ) => new Modules\Groups\GroupsModule(
				$c->get( Infrastructure\Security\Nonces::class )
			)
		);

		// Chat Module
		$container->singleton(
			Modules\Chat\ChatModule::class,
			static fn() => new Modules\Chat\ChatModule()
		);

		// Documents Module
		$container->singleton(
			Modules\Documents\DocumentsRepository::class,
			static fn() => new Modules\Documents\DocumentsRepository()
		);

		// Activity Stream
		$container->singleton(
			Modules\Activity\ActivityStream::class,
			static fn() => new Modules\Activity\ActivityStream()
		);

		// Points System
		$container->singleton(
			Modules\Gamification\PointsSystem::class,
			static fn() => new Modules\Gamification\PointsSystem()
		);

		// Verification
		$container->singleton(
			Modules\Verification\UserVerification::class,
			static fn() => new Modules\Verification\UserVerification()
		);

		// Bubble System (Connections)
		$container->singleton(
			Modules\Connections\BubbleSystem::class,
			static fn() => new Modules\Connections\BubbleSystem()
		);

		// =========================================================================
		// REST API Controllers
		// =========================================================================

		$container->singleton(
			RestAPI\FeedController::class,
			static fn( Container $c ) => new RestAPI\FeedController(
				$c->get( Modules\Activity\ActivityStream::class )
			)
		);

		$container->singleton(
			RestAPI\ProfileController::class,
			static fn() => new RestAPI\ProfileController()
		);

		$container->singleton(
			RestAPI\ClassifiedsController::class,
			static fn() => new RestAPI\ClassifiedsController()
		);

		// =========================================================================
		// Action Hooks
		// =========================================================================

		/**
		 * Allow external code to register services.
		 *
		 * @param Container $container The DI container.
		 */
		do_action( 'apollo_social_container_bootstrap', $container );
	}

	/**
	 * Make a service from the container.
	 *
	 * @template T
	 * @param class-string<T> $abstract The class or interface to resolve.
	 * @return T The resolved instance.
	 */
	public static function make( string $abstract ) {
		return self::getInstance()->get( $abstract );
	}

	/**
	 * Bind a service to the container.
	 *
	 * @param string   $abstract The abstract class/interface name.
	 * @param callable $concrete The factory function.
	 * @return void
	 */
	public static function bind( string $abstract, callable $concrete ): void {
		self::getInstance()->bind( $abstract, $concrete );
	}

	/**
	 * Bind a singleton service to the container.
	 *
	 * @param string   $abstract The abstract class/interface name.
	 * @param callable $concrete The factory function.
	 * @return void
	 */
	public static function singleton( string $abstract, callable $concrete ): void {
		self::getInstance()->singleton( $abstract, $concrete );
	}

	/**
	 * Reset the container (mainly for testing).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$instance = null;
	}
}
