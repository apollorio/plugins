<?php

declare(strict_types=1);

/**
 * Apollo Core - Membership WP-CLI Commands
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Apollo Membership CLI Commands
 */
class Apollo_Membership_CLI_Command {

	/**
	 * List all membership types
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo membership list
	 *
	 * @when after_wp_load
	 */
	public function list( $args, $assoc_args ) {
		$memberships = apollo_get_memberships();

		if ( empty( $memberships ) ) {
			WP_CLI::warning( 'No memberships found.' );

			return;
		}

		$table_data = [];
		foreach ( $memberships as $slug => $data ) {
			$table_data[] = [
				'Slug'           => $slug,
				'Label'          => $data['label'],
				'Frontend Label' => $data['frontend_label'],
				'Color'          => $data['color'],
				'Text Color'     => $data['text_color'],
			];
		}

		WP_CLI\Utils\format_items( 'table', $table_data, [ 'Slug', 'Label', 'Frontend Label', 'Color', 'Text Color' ] );

		$version = get_option( 'apollo_memberships_version', '1.0.0' );
		WP_CLI::line( '' );
		WP_CLI::line( 'Schema Version: ' . $version );
	}

	/**
	 * Add a new membership type
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The membership slug (e.g., vip-member)
	 *
	 * --label=<label>
	 * : The membership label
	 *
	 * --frontend-label=<label>
	 * : The frontend label
	 *
	 * --color=<hex>
	 * : Background color (hex format, e.g., #FF5733)
	 *
	 * --text-color=<hex>
	 * : Text color (hex format, e.g., #FFFFFF)
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo membership add vip-member --label="VIP Member" --frontend-label="VIP" --color="#FFD700" --text-color="#8B6B00"
	 *
	 * @when after_wp_load
	 */
	public function add( $args, $assoc_args ) {
		$slug = sanitize_key( $args[0] );

		// Validate required parameters.
		$required = [ 'label', 'frontend-label', 'color', 'text-color' ];
		foreach ( $required as $param ) {
			if ( empty( $assoc_args[ $param ] ) ) {
				WP_CLI::error( sprintf( 'Required parameter --%s is missing.', $param ) );
			}
		}

		// Check if slug already exists.
		if ( apollo_membership_exists( $slug ) ) {
			WP_CLI::error( 'Membership with this slug already exists.' );
		}

		// Validate color format.
		if ( ! preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $assoc_args['color'] ) ) {
			WP_CLI::error( 'Invalid color format. Use hex format (e.g., #FF5733).' );
		}

		if ( ! preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $assoc_args['text-color'] ) ) {
			WP_CLI::error( 'Invalid text color format. Use hex format (e.g., #FFFFFF).' );
		}

		// Get current memberships.
		$memberships = get_option( 'apollo_memberships', [] );

		// Add new membership.
		$memberships[ $slug ] = [
			'label'          => sanitize_text_field( $assoc_args['label'] ),
			'frontend_label' => sanitize_text_field( $assoc_args['frontend-label'] ),
			'color'          => sanitize_hex_color( $assoc_args['color'] ),
			'text_color'     => sanitize_hex_color( $assoc_args['text-color'] ),
		];

		// Save.
		$result = apollo_save_memberships( $memberships );

		if ( ! $result ) {
			WP_CLI::error( 'Failed to save membership.' );
		}

		// Log action.
		apollo_mod_log_action(
			get_current_user_id(),
			'membership_type_created_cli',
			'membership',
			0,
			[
				'slug'  => $slug,
				'label' => $memberships[ $slug ]['label'],
			]
		);

		WP_CLI::success( sprintf( 'Membership "%s" created successfully.', $slug ) );
	}

	/**
	 * Assign membership to a user
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : The user ID
	 *
	 * <membership_slug>
	 * : The membership slug
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo membership assign 123 apollo
	 *
	 * @when after_wp_load
	 */
	public function assign( $args, $assoc_args ) {
		$user_id         = absint( $args[0] );
		$membership_slug = sanitize_key( $args[1] );

		// Validate user.
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			WP_CLI::error( 'User not found.' );
		}

		// Validate membership.
		if ( ! apollo_membership_exists( $membership_slug ) ) {
			WP_CLI::error( 'Membership not found.' );
		}

		// Set membership.
		$result = apollo_set_user_membership( $user_id, $membership_slug, get_current_user_id() );

		if ( ! $result ) {
			WP_CLI::error( 'Failed to assign membership.' );
		}

		WP_CLI::success( sprintf( 'Membership "%s" assigned to user %d (%s).', $membership_slug, $user_id, $user->display_name ) );
	}

	/**
	 * Get user's current membership
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : The user ID
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo membership get 123
	 *
	 * @when after_wp_load
	 */
	public function get( $args, $assoc_args ) {
		$user_id = absint( $args[0] );

		// Validate user.
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			WP_CLI::error( 'User not found.' );
		}

		$membership_slug = apollo_get_user_membership( $user_id );
		$membership_data = apollo_get_membership_data( $membership_slug );

		WP_CLI::line( sprintf( 'User: %s (%s)', $user->display_name, $user->user_email ) );
		WP_CLI::line( sprintf( 'Membership Slug: %s', $membership_slug ) );
		WP_CLI::line( sprintf( 'Membership Label: %s', $membership_data['label'] ) );
		WP_CLI::line( sprintf( 'Frontend Label: %s', $membership_data['frontend_label'] ) );
		WP_CLI::line( sprintf( 'Color: %s', $membership_data['color'] ) );
		WP_CLI::line( sprintf( 'Text Color: %s', $membership_data['text_color'] ) );

		$instagram = get_user_meta( $user_id, '_apollo_instagram_id', true );
		if ( $instagram ) {
			WP_CLI::line( sprintf( 'Instagram: @%s', $instagram ) );
		}
	}

	/**
	 * Export memberships to JSON file
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : Output file path (default: memberships-export.json)
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo membership export
	 *     wp apollo membership export /tmp/memberships.json
	 *
	 * @when after_wp_load
	 */
	public function export( $args, $assoc_args ) {
		$file = isset( $args[0] ) ? $args[0] : 'memberships-export.json';

		$json = apollo_export_memberships_json();

		$result = file_put_contents( $file, $json );

		if ( false === $result ) {
			WP_CLI::error( 'Failed to write export file.' );
		}

		WP_CLI::success( sprintf( 'Memberships exported to: %s', $file ) );
	}

	/**
	 * Import memberships from JSON file
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Input file path
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo membership import /tmp/memberships.json
	 *
	 * @when after_wp_load
	 */
	public function import( $args, $assoc_args ) {
		$file = $args[0];

		if ( ! file_exists( $file ) ) {
			WP_CLI::error( 'File not found.' );
		}

		$json = file_get_contents( $file );

		if ( false === $json ) {
			WP_CLI::error( 'Failed to read import file.' );
		}

		$result = apollo_import_memberships_json( $json );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		// Log action.
		apollo_mod_log_action(
			get_current_user_id(),
			'memberships_imported_cli',
			'membership',
			0,
			[
				'file' => $file,
			]
		);

		WP_CLI::success( 'Memberships imported successfully.' );
	}

	/**
	 * Delete a membership type
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The membership slug
	 *
	 * [--yes]
	 * : Skip confirmation
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo membership delete vip-member --yes
	 *
	 * @when after_wp_load
	 */
	public function delete( $args, $assoc_args ) {
		$slug = sanitize_key( $args[0] );

		// Cannot delete nao-verificado.
		if ( 'nao-verificado' === $slug ) {
			WP_CLI::error( 'Cannot delete default membership.' );
		}

		// Cannot delete default memberships.
		$defaults = apollo_get_default_memberships();
		if ( isset( $defaults[ $slug ] ) ) {
			WP_CLI::error( 'Cannot delete default membership types.' );
		}

		// Check if exists.
		if ( ! apollo_membership_exists( $slug ) ) {
			WP_CLI::error( 'Membership not found.' );
		}

		// Confirm.
		WP_CLI::confirm( 'Are you sure you want to delete this membership type? All users will be reassigned to nao-verificado.', $assoc_args );

		// Delete.
		$result = apollo_delete_membership( $slug );

		if ( ! $result ) {
			WP_CLI::error( 'Failed to delete membership.' );
		}

		// Log action.
		apollo_mod_log_action(
			get_current_user_id(),
			'membership_type_deleted_cli',
			'membership',
			0,
			[
				'slug' => $slug,
			]
		);

		WP_CLI::success( 'Membership deleted successfully. Users reassigned to nao-verificado.' );
	}

	/**
	 * Count users per membership type
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo membership stats
	 *
	 * @when after_wp_load
	 */
	public function stats( $args, $assoc_args ) {
		$memberships = apollo_get_memberships();

		$table_data = [];
		foreach ( $memberships as $slug => $data ) {
			$count = count(
				get_users(
					[
						'meta_key'   => '_apollo_membership',
						'meta_value' => $slug,
						'fields'     => 'ID',
					]
				)
			);

			$table_data[] = [
				'Membership' => $data['label'],
				'Slug'       => $slug,
				'Users'      => $count,
			];
		}

		WP_CLI\Utils\format_items( 'table', $table_data, [ 'Membership', 'Slug', 'Users' ] );

		$total_users = count( get_users( [ 'fields' => 'ID' ] ) );
		WP_CLI::line( '' );
		WP_CLI::line( 'Total Users: ' . $total_users );
	}
}

WP_CLI::add_command( 'apollo membership', 'Apollo_Membership_CLI_Command' );
