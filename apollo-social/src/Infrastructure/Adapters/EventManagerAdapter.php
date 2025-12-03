<?php

namespace Apollo\Infrastructure\Adapters;

/**
 * Adapter for WP Event Manager plugin integration
 * Provides seamless integration with WP Event Manager for event handling
 */
class EventManagerAdapter {

	private $config;

	public function __construct() {
		$this->config = config( 'integrations.wp_event_manager' );

		if ( $this->config['enabled'] ?? false ) {
			$this->init_hooks();
		}
	}

	/**
	 * Initialize WordPress hooks for Event Manager integration
	 */
	private function init_hooks() {
		// Event lifecycle hooks
		add_action( 'post_updated', array( $this, 'on_event_updated' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'on_event_deleted' ), 10, 1 );
		add_action( 'transition_post_status', array( $this, 'on_event_status_changed' ), 10, 3 );

		// Event submission hooks
		add_action( 'event_manager_save_event_listing', array( $this, 'on_event_saved' ), 10, 2 );
		add_filter( 'submit_event_form_fields', array( $this, 'add_apollo_fields' ), 10, 1 );
		add_filter( 'event_manager_event_listing_data', array( $this, 'process_apollo_data' ), 10, 2 );

		// Event display hooks
		add_action( 'single_event_listing_meta_start', array( $this, 'add_apollo_meta' ), 10, 1 );
		add_filter( 'the_event_description', array( $this, 'enhance_event_description' ), 10, 2 );

		// Event query modifications
		add_action( 'pre_get_posts', array( $this, 'modify_event_queries' ), 10, 1 );
		add_filter( 'event_manager_get_listings_args', array( $this, 'add_apollo_filters' ), 10, 1 );

		// Featured events
		if ( $this->config['featured_events'] ?? false ) {
			add_filter( 'event_manager_featured_event_listings', array( $this, 'get_featured_events' ), 10, 2 );
		}
	}

	/**
	 * Check if WP Event Manager is active and available
	 */
	public function is_available(): bool {
		return class_exists( 'WP_Event_Manager' ) || function_exists( 'get_event_manager_permalink' );
	}

	/**
	 * Handle event updates
	 */
	public function on_event_updated( $post_id, $post_after, $post_before ) {
		if ( ! $this->is_event_listing( $post_id ) ) {
			return;
		}

		$apollo_meta = $this->get_apollo_event_meta( $post_id );
		if ( empty( $apollo_meta ) ) {
			return;
		}

		// Sync with Apollo system
		do_action( 'apollo_event_updated', $post_id, $apollo_meta, $post_after );
	}

	/**
	 * Handle event deletion
	 */
	public function on_event_deleted( $post_id ) {
		if ( ! $this->is_event_listing( $post_id ) ) {
			return;
		}

		$apollo_meta = $this->get_apollo_event_meta( $post_id );
		if ( empty( $apollo_meta ) ) {
			return;
		}

		do_action( 'apollo_event_deleted', $post_id, $apollo_meta );
	}

	/**
	 * Handle event status changes
	 */
	public function on_event_status_changed( $new_status, $old_status, $post ) {
		if ( ! $this->is_event_listing( $post->ID ) ) {
			return;
		}

		$apollo_meta = $this->get_apollo_event_meta( $post->ID );
		if ( empty( $apollo_meta ) ) {
			return;
		}

		// Handle approval/rejection
		if ( $old_status === 'pending' && $new_status === 'publish' ) {
			do_action( 'apollo_event_approved', $post->ID, $apollo_meta );
		} elseif ( $new_status === 'draft' || $new_status === 'trash' ) {
			do_action( 'apollo_event_rejected', $post->ID, $apollo_meta );
		}

		do_action( 'apollo_event_status_changed', $post->ID, $new_status, $old_status, $apollo_meta );
	}

	/**
	 * Handle event saving
	 */
	public function on_event_saved( $post_id, $values ) {
		if ( ! $this->is_event_listing( $post_id ) ) {
			return;
		}

		// Process Apollo-specific data
		$apollo_data = $this->extract_apollo_data( $values );
		if ( ! empty( $apollo_data ) ) {
			$this->save_apollo_event_meta( $post_id, $apollo_data );
			do_action( 'apollo_event_saved', $post_id, $apollo_data );
		}
	}

	/**
	 * Add Apollo-specific fields to event submission form
	 */
	public function add_apollo_fields( $fields ) {
		$apollo_fields = array(
			'apollo_group_type' => array(
				'label'    => 'Tipo de Grupo',
				'type'     => 'select',
				'required' => false,
				'options'  => array(
					''           => 'Selecione...',
					'comunidade' => 'Comunidade',
					'nucleo'     => 'Núcleo',
					'season'     => 'Season',
				),
				'priority' => 1,
			),
			'apollo_group_id'   => array(
				'label'    => 'Grupo Apollo',
				'type'     => 'select',
				'required' => false,
				'options'  => $this->get_apollo_groups_options(),
				'priority' => 2,
			),
			'apollo_visibility' => array(
				'label'    => 'Visibilidade',
				'type'     => 'select',
				'required' => false,
				'options'  => array(
					'public'  => 'Público',
					'members' => 'Apenas Membros',
					'private' => 'Privado',
				),
				'default'  => 'public',
				'priority' => 3,
			),
			'apollo_tags'       => array(
				'label'       => 'Tags Apollo',
				'type'        => 'text',
				'required'    => false,
				'placeholder' => 'Separado por vírgulas',
				'priority'    => 4,
			),
			'apollo_capacity'   => array(
				'label'       => 'Capacidade Máxima',
				'type'        => 'number',
				'required'    => false,
				'placeholder' => '0 = ilimitado',
				'priority'    => 5,
			),
		);

		// Merge with existing fields
		return array_merge(
			$fields,
			array(
				'apollo' => array(
					'label'    => 'Configurações Apollo',
					'fields'   => $apollo_fields,
					'priority' => 25,
				),
			)
		);
	}

	/**
	 * Process Apollo data during event submission
	 */
	public function process_apollo_data( $data, $post_id ) {
		$apollo_fields = array( 'apollo_group_type', 'apollo_group_id', 'apollo_visibility', 'apollo_tags', 'apollo_capacity' );

		foreach ( $apollo_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$data[ $field ] = sanitize_text_field( $_POST[ $field ] );
			}
		}

		return $data;
	}

	/**
	 * Add Apollo meta to single event display
	 */
	public function add_apollo_meta( $post ) {
		$apollo_meta = $this->get_apollo_event_meta( $post->ID );
		if ( empty( $apollo_meta ) ) {
			return;
		}

		echo '<div class="apollo-event-meta">';

		// Group info
		if ( ! empty( $apollo_meta['group_type'] ) ) {
			echo '<div class="apollo-group-type">';
			echo '<strong>Grupo:</strong> ' . esc_html( ucfirst( $apollo_meta['group_type'] ) );
			echo '</div>';
		}

		// Visibility
		if ( ! empty( $apollo_meta['visibility'] ) ) {
			$visibility_labels = array(
				'public'  => 'Público',
				'members' => 'Apenas Membros',
				'private' => 'Privado',
			);
			echo '<div class="apollo-visibility">';
			echo '<strong>Visibilidade:</strong> ' . esc_html( $visibility_labels[ $apollo_meta['visibility'] ] ?? $apollo_meta['visibility'] );
			echo '</div>';
		}

		// Capacity
		if ( ! empty( $apollo_meta['capacity'] ) && $apollo_meta['capacity'] > 0 ) {
			echo '<div class="apollo-capacity">';
			echo '<strong>Capacidade:</strong> ' . esc_html( $apollo_meta['capacity'] ) . ' pessoas';
			echo '</div>';
		}

		// Tags
		if ( ! empty( $apollo_meta['tags'] ) ) {
			$tags = explode( ',', $apollo_meta['tags'] );
			echo '<div class="apollo-tags">';
			echo '<strong>Tags:</strong> ';
			foreach ( $tags as $tag ) {
				echo '<span class="apollo-tag">' . esc_html( trim( $tag ) ) . '</span> ';
			}
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Enhance event description with Apollo data
	 */
	public function enhance_event_description( $description, $post ) {
		if ( ! $this->is_event_listing( $post->ID ) ) {
			return $description;
		}

		$apollo_meta = $this->get_apollo_event_meta( $post->ID );
		if ( empty( $apollo_meta ) ) {
			return $description;
		}

		// Add Apollo branding/info if configured
		$apollo_info = apply_filters( 'apollo_event_description_enhancement', '', $apollo_meta, $post );

		return $description . $apollo_info;
	}

	/**
	 * Modify event queries for Apollo integration
	 */
	public function modify_event_queries( $query ) {
		if ( ! $query->is_main_query() || ! is_admin() ) {
			return;
		}

		if ( $query->get( 'post_type' ) === 'event_listing' ) {
			// Add Apollo meta query if needed
			$apollo_filter = $query->get( 'apollo_filter' );
			if ( $apollo_filter ) {
				$meta_query   = $query->get( 'meta_query' ) ?: array();
				$meta_query[] = array(
					'key'     => '_apollo_group_type',
					'value'   => $apollo_filter,
					'compare' => '=',
				);
				$query->set( 'meta_query', $meta_query );
			}
		}
	}

	/**
	 * Add Apollo filters to event listings
	 */
	public function add_apollo_filters( $args ) {
		// Add Apollo-specific filters if needed
		if ( isset( $_GET['apollo_group_type'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_apollo_group_type',
				'value'   => sanitize_text_field( $_GET['apollo_group_type'] ),
				'compare' => '=',
			);
		}

		if ( isset( $_GET['apollo_visibility'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_apollo_visibility',
				'value'   => sanitize_text_field( $_GET['apollo_visibility'] ),
				'compare' => '=',
			);
		}

		return $args;
	}

	/**
	 * Get featured events for Apollo
	 */
	public function get_featured_events( $events, $args ) {
		// Get Apollo featured events
		$apollo_featured = get_posts(
			array(
				'post_type'      => 'event_listing',
				'posts_per_page' => 5,
				'meta_query'     => array(
					array(
						'key'     => '_apollo_featured',
						'value'   => '1',
						'compare' => '=',
					),
				),
			)
		);

		return array_merge( $events, $apollo_featured );
	}

	/**
	 * Create Apollo event programmatically
	 */
	public function create_apollo_event( $event_data ): ?int {
		if ( ! $this->is_available() ) {
			return null;
		}

		$defaults = array(
			'post_type'   => 'event_listing',
			'post_status' => $this->config['default_status'] ?? 'pending',
			'meta_input'  => array(),
		);

		$event_data = array_merge( $defaults, $event_data );

		// Extract Apollo meta
		$apollo_meta   = array();
		$apollo_fields = array( 'apollo_group_type', 'apollo_group_id', 'apollo_visibility', 'apollo_tags', 'apollo_capacity' );

		foreach ( $apollo_fields as $field ) {
			if ( isset( $event_data[ $field ] ) ) {
				$apollo_meta[ $field ] = $event_data[ $field ];
				unset( $event_data[ $field ] );
			}
		}

		// Create event
		$post_id = wp_insert_post( $event_data );

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			// Save Apollo meta
			$this->save_apollo_event_meta( $post_id, $apollo_meta );

			do_action( 'apollo_event_created', $post_id, $apollo_meta );

			return $post_id;
		}

		return null;
	}

	/**
	 * Get Apollo events by group
	 */
	public function get_events_by_group( $group_type, $group_id = null, $args = array() ): array {
		$query_args = array_merge(
			array(
				'post_type'      => 'event_listing',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => '_apollo_group_type',
						'value'   => $group_type,
						'compare' => '=',
					),
				),
			),
			$args
		);

		if ( $group_id ) {
			$query_args['meta_query'][] = array(
				'key'     => '_apollo_group_id',
				'value'   => $group_id,
				'compare' => '=',
			);
		}

		return get_posts( $query_args );
	}

	/**
	 * Check if user can view event based on Apollo rules
	 */
	public function user_can_view_event( $user_id, $post_id ): bool {
		$apollo_meta = $this->get_apollo_event_meta( $post_id );

		if ( empty( $apollo_meta['visibility'] ) || $apollo_meta['visibility'] === 'public' ) {
			return true;
		}

		if ( $apollo_meta['visibility'] === 'members' ) {
			// Check if user is member of the event's group
			$group_type = $apollo_meta['group_type'] ?? null;
			if ( $group_type ) {
				$groups_adapter = new GroupsAdapter();
				return $groups_adapter->user_has_group_access( $user_id, $group_type );
			}
		}

		if ( $apollo_meta['visibility'] === 'private' ) {
			// Check if user is event author or admin
			$event = get_post( $post_id );
			return $user_id == $event->post_author || user_can( $user_id, 'manage_options' );
		}

		return false;
	}

	/**
	 * Helper methods
	 */
	private function is_event_listing( $post_id ): bool {
		return get_post_type( $post_id ) === 'event_listing';
	}

	private function get_apollo_event_meta( $post_id ): array {
		return array(
			'group_type' => get_post_meta( $post_id, '_apollo_group_type', true ),
			'group_id'   => get_post_meta( $post_id, '_apollo_group_id', true ),
			'visibility' => get_post_meta( $post_id, '_apollo_visibility', true ),
			'tags'       => get_post_meta( $post_id, '_apollo_tags', true ),
			'capacity'   => get_post_meta( $post_id, '_apollo_capacity', true ),
			'featured'   => get_post_meta( $post_id, '_apollo_featured', true ),
		);
	}

	private function save_apollo_event_meta( $post_id, $apollo_data ) {
		foreach ( $apollo_data as $key => $value ) {
			update_post_meta( $post_id, '_' . $key, sanitize_text_field( $value ) );
		}
	}

	private function extract_apollo_data( $values ): array {
		$apollo_fields = array( 'apollo_group_type', 'apollo_group_id', 'apollo_visibility', 'apollo_tags', 'apollo_capacity' );
		$apollo_data   = array();

		foreach ( $apollo_fields as $field ) {
			if ( isset( $values[ $field ] ) ) {
				$apollo_data[ $field ] = $values[ $field ];
			}
		}

		return $apollo_data;
	}

	private function get_apollo_groups_options(): array {
		// This would be implemented to get actual Apollo groups
		// For now, return empty array as placeholder
		return apply_filters( 'apollo_event_groups_options', array() );
	}

	/**
	 * Get configuration
	 */
	public function get_config(): array {
		return $this->config;
	}

	/**
	 * Update configuration
	 */
	public function update_config( array $config ): bool {
		$this->config = array_merge( $this->config, $config );
		return update_option( 'apollo_event_manager_config', $this->config );
	}
}
