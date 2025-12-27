<?php
declare( strict_types=1 );

if ( false === class_exists( 'WP_CLI', false ) ) {
	class WP_CLI {

		/**
		 * @param string $name
		 * @param callable|string $callable
		 * @param array $args
		 * @return void
		 */
		public static function add_command( string $name, $callable, array $args = array() ) : void {}

		/**
		 * @param string $hook
		 * @param callable $callback
		 * @param int $priority
		 * @param int $accepted_args
		 * @return void
		 */
		public static function add_hook( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ) : void {}

		/**
		 * @param string $hook
		 * @param callable $callback
		 * @param int $priority
		 * @param int $accepted_args
		 * @return void
		 */
		public static function add_wp_hook( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ) : void {}

		/**
		 * @param string $message
		 * @return void
		 */
		public static function log( string $message ) : void {}

		/**
		 * @param string $message
		 * @return void
		 */
		public static function success( string $message ) : void {}

		/**
		 * @param string $message
		 * @return void
		 */
		public static function warning( string $message ) : void {}

		/**
		 * @param string $message
		 * @param bool $exit
		 * @return void
		 */
		public static function error( string $message, bool $exit = true ) : void {}

		/**
		 * @param string $command
		 * @param array $assoc_args
		 * @param array $options
		 * @return string|array<mixed>|int|bool
		 */
		public static function runcommand( string $command, array $assoc_args = array(), array $options = array() ) {}

		/**
		 * @param string|null $key
		 * @return mixed
		 */
		public static function get_config( ?string $key = null ) {}

		/**
		 * @return object
		 */
		public static function get_runner() {
			return (object) array();
		}
	}
}

if ( false === class_exists( 'WP_CLI_Command', false ) ) {
	abstract class WP_CLI_Command {}
}

namespace WP_CLI\Utils {
	/**
	 * @param array $assoc_args
	 * @param string $flag
	 * @param mixed $default
	 * @return mixed
	 */
	function get_flag_value( array $assoc_args, string $flag, $default = null ) {
		return $default;
	}

	/**
	 * @param string $type
	 * @param array<int, array<string, mixed>> $items
	 * @param array<int, string> $fields
	 * @return void
	 */
	function format_items( string $type, array $items, array $fields ) : void {}

	/**
	 * @param string $question
	 * @param string $default
	 * @return string
	 */
	function prompt( string $question, string $default = '' ) : string {
		return $default;
	}
}
