<?php
/**
 * Apollo Relationships Schema
 *
 * Defines all data relationships between Custom Post Types in the Apollo ecosystem.
 * Provides explicit mapping for implicit relationships stored in meta fields.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace Apollo_Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Apollo_Relationships
 *
 * Central registry for all CPT relationships.
 */
final class Apollo_Relationships {

	/*
	|--------------------------------------------------------------------------
	| Relationship Types
	|--------------------------------------------------------------------------
	*/

	/** One-to-One relationship (e.g., user_page → WP_User) */
	public const ONE_TO_ONE = 'one_to_one';

	/** One-to-Many relationship (e.g., event → many classifieds) */
	public const ONE_TO_MANY = 'one_to_many';

	/** Many-to-One relationship (e.g., many classifieds → one event) */
	public const MANY_TO_ONE = 'many_to_one';

	/** Many-to-Many relationship (e.g., events ↔ DJs) */
	public const MANY_TO_MANY = 'many_to_many';

	/*
	|--------------------------------------------------------------------------
	| Storage Types
	|--------------------------------------------------------------------------
	*/

	/** Single ID stored as integer */
	public const STORAGE_SINGLE_ID = 'single_id';

	/** Array of IDs stored as serialized PHP array */
	public const STORAGE_SERIALIZED_ARRAY = 'serialized_array';

	/** Array of IDs stored as JSON */
	public const STORAGE_JSON_ARRAY = 'json_array';

	/** Comma-separated IDs */
	public const STORAGE_CSV = 'csv';

	/** WordPress taxonomy term */
	public const STORAGE_TAXONOMY = 'taxonomy';

	/** Custom table (future) */
	public const STORAGE_TABLE = 'table';

	/*
	|--------------------------------------------------------------------------
	| Relationship Registry
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get all relationship definitions
	 *
	 * @return array<string, array>
	 */
	public static function get_schema(): array {
		$schema = array(
			/*
			|------------------------------------------------------------------
			| Events → DJs (Many-to-Many)
			|------------------------------------------------------------------
			| Events can have multiple DJs, DJs can play at multiple events.
			| Stored in event meta as serialized array of DJ post IDs.
			*/
			'event_to_dj'            => array(
				'type'       => self::MANY_TO_MANY,
				'from'       => 'event_listing',
				'to'         => 'event_dj',
				'meta_key'   => '_event_dj_ids',
				'storage'    => self::STORAGE_SERIALIZED_ARRAY,
				'label'      => __( 'Event DJs', 'apollo-core' ),
				'inverse'    => 'dj_to_event',
				'cascade'    => 'none', // Don't delete related on delete
				'queryable'  => true,
				'rest_field' => 'djs',
			),

			/*
			|------------------------------------------------------------------
			| DJs → Events (Inverse of event_to_dj)
			|------------------------------------------------------------------
			| Virtual relationship - queries events that reference this DJ.
			*/
			'dj_to_event'            => array(
				'type'       => self::MANY_TO_MANY,
				'from'       => 'event_dj',
				'to'         => 'event_listing',
				'meta_key'   => '_event_dj_ids',
				'storage'    => self::STORAGE_SERIALIZED_ARRAY,
				'label'      => __( 'DJ Events', 'apollo-core' ),
				'inverse'    => 'event_to_dj',
				'is_reverse' => true, // This is the reverse lookup
				'queryable'  => true,
				'rest_field' => 'events',
			),

			/*
			|------------------------------------------------------------------
			| Events → Venues (Many-to-Many)
			|------------------------------------------------------------------
			| Events can have multiple venues, venues can host multiple events.
			*/
			'event_to_local'         => array(
				'type'       => self::MANY_TO_MANY,
				'from'       => 'event_listing',
				'to'         => 'event_local',
				'meta_key'   => '_event_local_ids',
				'storage'    => self::STORAGE_SERIALIZED_ARRAY,
				'label'      => __( 'Event Venues', 'apollo-core' ),
				'inverse'    => 'local_to_event',
				'queryable'  => true,
				'rest_field' => 'venues',
			),

			/*
			|------------------------------------------------------------------
			| Venues → Events (Inverse)
			|------------------------------------------------------------------
			*/
			'local_to_event'         => array(
				'type'       => self::MANY_TO_MANY,
				'from'       => 'event_local',
				'to'         => 'event_listing',
				'meta_key'   => '_event_local_ids',
				'storage'    => self::STORAGE_SERIALIZED_ARRAY,
				'label'      => __( 'Venue Events', 'apollo-core' ),
				'inverse'    => 'event_to_local',
				'is_reverse' => true,
				'queryable'  => true,
				'rest_field' => 'events',
			),

			/*
			|------------------------------------------------------------------
			| Classifieds → Events (Many-to-One)
			|------------------------------------------------------------------
			| Ticket resales can be linked to a specific event.
			*/
			'classified_to_event'    => array(
				'type'       => self::MANY_TO_ONE,
				'from'       => 'apollo_classified',
				'to'         => 'event_listing',
				'meta_key'   => '_classified_event_id',
				'storage'    => self::STORAGE_SINGLE_ID,
				'label'      => __( 'Related Event', 'apollo-core' ),
				'inverse'    => 'event_to_classified',
				'queryable'  => true,
				'rest_field' => 'event',
			),

			/*
			|------------------------------------------------------------------
			| Events → Classifieds (One-to-Many, Inverse)
			|------------------------------------------------------------------
			*/
			'event_to_classified'    => array(
				'type'       => self::ONE_TO_MANY,
				'from'       => 'event_listing',
				'to'         => 'apollo_classified',
				'meta_key'   => '_classified_event_id',
				'storage'    => self::STORAGE_SINGLE_ID,
				'label'      => __( 'Event Classifieds', 'apollo-core' ),
				'inverse'    => 'classified_to_event',
				'is_reverse' => true,
				'queryable'  => true,
				'rest_field' => 'classifieds',
			),

			/*
			|------------------------------------------------------------------
			| Classifieds → Season (Many-to-One via taxonomy)
			|------------------------------------------------------------------
			*/
			'classified_to_season'   => array(
				'type'       => self::MANY_TO_ONE,
				'from'       => 'apollo_classified',
				'to'         => 'event_season',
				'meta_key'   => '_classified_season_id',
				'taxonomy'   => 'event_season',
				'storage'    => self::STORAGE_TAXONOMY,
				'label'      => __( 'Season', 'apollo-core' ),
				'queryable'  => true,
				'rest_field' => 'season',
			),

			/*
			|------------------------------------------------------------------
			| Events → Season (Many-to-One via taxonomy)
			|------------------------------------------------------------------
			*/
			'event_to_season'        => array(
				'type'       => self::MANY_TO_ONE,
				'from'       => 'event_listing',
				'to'         => 'event_season',
				'meta_key'   => '_event_season_id',
				'taxonomy'   => 'event_season',
				'storage'    => self::STORAGE_TAXONOMY,
				'label'      => __( 'Season', 'apollo-core' ),
				'queryable'  => true,
				'rest_field' => 'season',
			),

			/*
			|------------------------------------------------------------------
			| Social Posts → Author (Many-to-One)
			|------------------------------------------------------------------
			*/
			'social_post_to_author'  => array(
				'type'       => self::MANY_TO_ONE,
				'from'       => 'apollo_social_post',
				'to'         => 'user',
				'meta_key'   => null, // Uses post_author
				'storage'    => 'post_author',
				'label'      => __( 'Author', 'apollo-core' ),
				'queryable'  => true,
				'rest_field' => 'author',
			),

			/*
			|------------------------------------------------------------------
			| User Page → User (One-to-One)
			|------------------------------------------------------------------
			*/
			'user_page_to_user'      => array(
				'type'       => self::ONE_TO_ONE,
				'from'       => 'user_page',
				'to'         => 'user',
				'meta_key'   => null, // Uses post_author
				'storage'    => 'post_author',
				'label'      => __( 'User', 'apollo-core' ),
				'queryable'  => true,
				'rest_field' => 'user',
			),

			/*
			|------------------------------------------------------------------
			| User → Events (RSVPs) - Many-to-Many
			|------------------------------------------------------------------
			*/
			'user_to_event_rsvp'     => array(
				'type'          => self::MANY_TO_MANY,
				'from'          => 'user',
				'to'            => 'event_listing',
				'meta_key'      => '_event_rsvp_users',
				'user_meta'     => '_user_rsvp_events',
				'storage'       => self::STORAGE_SERIALIZED_ARRAY,
				'label'         => __( 'RSVPs', 'apollo-core' ),
				'inverse'       => 'event_to_user_rsvp',
				'bidirectional' => true,
				'queryable'     => true,
				'rest_field'    => 'rsvp_events',
			),

			/*
			|------------------------------------------------------------------
			| Events → RSVPed Users (Inverse)
			|------------------------------------------------------------------
			*/
			'event_to_user_rsvp'     => array(
				'type'       => self::MANY_TO_MANY,
				'from'       => 'event_listing',
				'to'         => 'user',
				'meta_key'   => '_event_rsvp_users',
				'storage'    => self::STORAGE_SERIALIZED_ARRAY,
				'label'      => __( 'Attendees', 'apollo-core' ),
				'inverse'    => 'user_to_event_rsvp',
				'queryable'  => true,
				'rest_field' => 'attendees',
			),

			/*
			|------------------------------------------------------------------
			| User → Interested Events - Many-to-Many
			|------------------------------------------------------------------
			*/
			'user_to_event_interest' => array(
				'type'          => self::MANY_TO_MANY,
				'from'          => 'user',
				'to'            => 'event_listing',
				'meta_key'      => '_event_interested_users',
				'user_meta'     => '_user_interested_events',
				'storage'       => self::STORAGE_SERIALIZED_ARRAY,
				'label'         => __( 'Interested', 'apollo-core' ),
				'inverse'       => 'event_to_user_interest',
				'bidirectional' => true,
				'queryable'     => true,
				'rest_field'    => 'interested_events',
			),

			/*
			|------------------------------------------------------------------
			| Events → Interested Users (Inverse)
			|------------------------------------------------------------------
			*/
			'event_to_user_interest' => array(
				'type'       => self::MANY_TO_MANY,
				'from'       => 'event_listing',
				'to'         => 'user',
				'meta_key'   => '_event_interested_users',
				'storage'    => self::STORAGE_SERIALIZED_ARRAY,
				'label'      => __( 'Interested Users', 'apollo-core' ),
				'inverse'    => 'user_to_event_interest',
				'queryable'  => true,
				'rest_field' => 'interested_users',
			),

			/*
			|------------------------------------------------------------------
			| Suppliers → Events (Many-to-Many)
			|------------------------------------------------------------------
			| Suppliers/vendors that work at events.
			*/
			'supplier_to_event'      => array(
				'type'       => self::MANY_TO_MANY,
				'from'       => 'apollo_supplier',
				'to'         => 'event_listing',
				'meta_key'   => '_supplier_event_ids',
				'storage'    => self::STORAGE_SERIALIZED_ARRAY,
				'label'      => __( 'Supplier Events', 'apollo-core' ),
				'inverse'    => 'event_to_supplier',
				'queryable'  => true,
				'rest_field' => 'events',
			),

			/*
			|------------------------------------------------------------------
			| Events → Suppliers (Inverse)
			|------------------------------------------------------------------
			*/
			'event_to_supplier'      => array(
				'type'       => self::MANY_TO_MANY,
				'from'       => 'event_listing',
				'to'         => 'apollo_supplier',
				'meta_key'   => '_event_supplier_ids',
				'storage'    => self::STORAGE_SERIALIZED_ARRAY,
				'label'      => __( 'Event Suppliers', 'apollo-core' ),
				'inverse'    => 'supplier_to_event',
				'queryable'  => true,
				'rest_field' => 'suppliers',
			),

			/*
			|------------------------------------------------------------------
			| DJs → Social Posts (One-to-Many)
			|------------------------------------------------------------------
			| DJ announcements and social posts.
			*/
			'dj_to_social_post'      => array(
				'type'       => self::ONE_TO_MANY,
				'from'       => 'event_dj',
				'to'         => 'apollo_social_post',
				'meta_key'   => '_social_post_dj_id',
				'storage'    => self::STORAGE_SINGLE_ID,
				'label'      => __( 'DJ Posts', 'apollo-core' ),
				'inverse'    => 'social_post_to_dj',
				'is_reverse' => true,
				'queryable'  => true,
				'rest_field' => 'posts',
			),

			/*
			|------------------------------------------------------------------
			| Social Posts → DJ (Many-to-One)
			|------------------------------------------------------------------
			*/
			'social_post_to_dj'      => array(
				'type'       => self::MANY_TO_ONE,
				'from'       => 'apollo_social_post',
				'to'         => 'event_dj',
				'meta_key'   => '_social_post_dj_id',
				'storage'    => self::STORAGE_SINGLE_ID,
				'label'      => __( 'Related DJ', 'apollo-core' ),
				'inverse'    => 'dj_to_social_post',
				'queryable'  => true,
				'rest_field' => 'dj',
			),

			/*
			|------------------------------------------------------------------
			| User → Followers (Many-to-Many)
			|------------------------------------------------------------------
			*/
			'user_to_followers'      => array(
				'type'             => self::MANY_TO_MANY,
				'from'             => 'user',
				'to'               => 'user',
				'meta_key'         => '_user_followers',
				'user_meta'        => '_user_following',
				'storage'          => self::STORAGE_SERIALIZED_ARRAY,
				'label'            => __( 'Followers', 'apollo-core' ),
				'inverse'          => 'user_to_following',
				'bidirectional'    => true,
				'self_referential' => true,
				'queryable'        => true,
				'rest_field'       => 'followers',
			),

			/*
			|------------------------------------------------------------------
			| User → Following (Inverse)
			|------------------------------------------------------------------
			*/
			'user_to_following'      => array(
				'type'             => self::MANY_TO_MANY,
				'from'             => 'user',
				'to'               => 'user',
				'meta_key'         => '_user_following',
				'storage'          => self::STORAGE_SERIALIZED_ARRAY,
				'label'            => __( 'Following', 'apollo-core' ),
				'inverse'          => 'user_to_followers',
				'self_referential' => true,
				'queryable'        => true,
				'rest_field'       => 'following',
			),

			/*
			|------------------------------------------------------------------
			| User → Bubble (Close Friends) - Many-to-Many
			|------------------------------------------------------------------
			*/
			'user_to_bubble'         => array(
				'type'             => self::MANY_TO_MANY,
				'from'             => 'user',
				'to'               => 'user',
				'meta_key'         => '_user_bubble',
				'storage'          => self::STORAGE_SERIALIZED_ARRAY,
				'label'            => __( 'Bubble', 'apollo-core' ),
				'self_referential' => true,
				'queryable'        => true,
				'rest_field'       => 'bubble',
			),

			/*
			|------------------------------------------------------------------
			| User → Favorites (Many-to-Many, polymorphic)
			|------------------------------------------------------------------
			*/
			'user_to_favorites'      => array(
				'type'          => self::MANY_TO_MANY,
				'from'          => 'user',
				'to'            => array( 'event_listing', 'event_dj', 'event_local', 'apollo_classified' ),
				'meta_key'      => '_favorites_user_ids',
				'user_meta'     => '_user_favorites',
				'storage'       => self::STORAGE_SERIALIZED_ARRAY,
				'label'         => __( 'Favorites', 'apollo-core' ),
				'polymorphic'   => true,
				'bidirectional' => true,
				'queryable'     => true,
				'rest_field'    => 'favorites',
			),

			/*
			|------------------------------------------------------------------
			| Groups (Comunas/Nucleos) → Members
			|------------------------------------------------------------------
			*/
			'group_to_members'       => array(
				'type'          => self::MANY_TO_MANY,
				'from'          => array( 'comuna', 'nucleo' ),
				'to'            => 'user',
				'meta_key'      => '_group_member_ids',
				'user_meta'     => '_user_groups',
				'storage'       => self::STORAGE_SERIALIZED_ARRAY,
				'label'         => __( 'Members', 'apollo-core' ),
				'inverse'       => 'user_to_groups',
				'bidirectional' => true,
				'queryable'     => true,
				'rest_field'    => 'members',
			),
		);

		/**
		 * Filter relationship schema.
		 *
		 * @param array $schema Relationship definitions.
		 */
		return \apply_filters( 'apollo_relationships_schema', $schema );
	}

	/**
	 * Get a specific relationship definition
	 *
	 * @param string $name Relationship name.
	 * @return array|null Relationship definition or null.
	 */
	public static function get( string $name ): ?array {
		$schema = self::get_schema();
		return $schema[ $name ] ?? null;
	}

	/**
	 * Get relationships for a post type
	 *
	 * @param string $post_type Post type.
	 * @return array Relationships where this type is the 'from'.
	 */
	public static function get_for_post_type( string $post_type ): array {
		$schema        = self::get_schema();
		$relationships = array();

		foreach ( $schema as $name => $definition ) {
			$from = $definition['from'];

			// Handle array of post types.
			if ( \is_array( $from ) ) {
				if ( \in_array( $post_type, $from, true ) ) {
					$relationships[ $name ] = $definition;
				}
			} elseif ( $from === $post_type ) {
				$relationships[ $name ] = $definition;
			}
		}

		return $relationships;
	}

	/**
	 * Get relationships pointing to a post type
	 *
	 * @param string $post_type Post type.
	 * @return array Relationships where this type is the 'to'.
	 */
	public static function get_pointing_to( string $post_type ): array {
		$schema        = self::get_schema();
		$relationships = array();

		foreach ( $schema as $name => $definition ) {
			$to = $definition['to'];

			// Handle array of post types.
			if ( \is_array( $to ) ) {
				if ( \in_array( $post_type, $to, true ) ) {
					$relationships[ $name ] = $definition;
				}
			} elseif ( $to === $post_type ) {
				$relationships[ $name ] = $definition;
			}
		}

		return $relationships;
	}

	/**
	 * Get all queryable relationships
	 *
	 * @return array
	 */
	public static function get_queryable(): array {
		$schema = self::get_schema();

		return \array_filter(
			$schema,
			fn( $def ) => ! empty( $def['queryable'] )
		);
	}

	/**
	 * Get all REST-exposed relationships
	 *
	 * @return array
	 */
	public static function get_rest_fields(): array {
		$schema = self::get_schema();

		return \array_filter(
			$schema,
			fn( $def ) => ! empty( $def['rest_field'] )
		);
	}

	/**
	 * Validate a relationship definition
	 *
	 * @param string $name       Relationship name.
	 * @param array  $definition Relationship definition.
	 * @return array Validation result with 'valid' and 'errors'.
	 */
	public static function validate( string $name, array $definition ): array {
		$errors = array();

		// Required fields.
		$required = array( 'type', 'from', 'to' );
		foreach ( $required as $field ) {
			if ( empty( $definition[ $field ] ) ) {
				$errors[] = \sprintf( 'Missing required field: %s', $field );
			}
		}

		// Valid type.
		$valid_types = array( self::ONE_TO_ONE, self::ONE_TO_MANY, self::MANY_TO_ONE, self::MANY_TO_MANY );
		if ( ! empty( $definition['type'] ) && ! \in_array( $definition['type'], $valid_types, true ) ) {
			$errors[] = \sprintf( 'Invalid relationship type: %s', $definition['type'] );
		}

		// Storage type for non-post_author.
		if ( empty( $definition['storage'] ) && ( $definition['storage'] ?? '' ) !== 'post_author' ) {
			if ( empty( $definition['meta_key'] ) ) {
				$errors[] = 'Relationship requires either meta_key or storage=post_author';
			}
		}

		return array(
			'valid'  => empty( $errors ),
			'errors' => $errors,
		);
	}

	/**
	 * Get the inverse relationship name
	 *
	 * @param string $name Relationship name.
	 * @return string|null Inverse relationship name.
	 */
	public static function get_inverse( string $name ): ?string {
		$definition = self::get( $name );
		return $definition['inverse'] ?? null;
	}

	/**
	 * Check if relationship is bidirectional
	 *
	 * @param string $name Relationship name.
	 * @return bool
	 */
	public static function is_bidirectional( string $name ): bool {
		$definition = self::get( $name );
		return ! empty( $definition['bidirectional'] );
	}

	/**
	 * Check if relationship is a reverse lookup
	 *
	 * @param string $name Relationship name.
	 * @return bool
	 */
	public static function is_reverse( string $name ): bool {
		$definition = self::get( $name );
		return ! empty( $definition['is_reverse'] );
	}
}
