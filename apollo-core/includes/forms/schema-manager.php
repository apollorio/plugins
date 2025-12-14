<?php

declare(strict_types=1);

/**
 * Apollo Core - Form Schema Manager
 *
 * Manages form schemas for different form types (new_user, cpt_event, cpt_local, cpt_dj)
 *
 * @package Apollo_Core
 * @since 3.1.0
 * Path: wp-content/plugins/apollo-core/includes/forms/schema-manager.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get form schema for a specific form type
 *
 * @param string $form_type Form type: new_user, cpt_event, cpt_local, cpt_dj.
 * @return array Form schema with fields.
 */
function apollo_get_form_schema( string $form_type ): array {
	// Try cache first
	$cached = apollo_cache_get_form_schema( $form_type );
	if ( false !== $cached ) {
		return $cached;
	}

	$schemas = get_option( 'apollo_form_schemas', array() );

	// If schema doesn't exist, return default.
	$schema = array();
	if ( ! isset( $schemas[ $form_type ] ) ) {
		$schema = apollo_get_default_form_schema( $form_type );
	} else {
		$schema = $schemas[ $form_type ];
	}

	// Cache the result
	apollo_cache_form_schema( $form_type, $schema );

	return $schema;
}

/**
 * Save form schema for a specific form type
 *
 * @param string $form_type Form type.
 * @param array  $schema    Schema array with fields.
 * @return bool True on success, false on failure.
 */
function apollo_save_form_schema( string $form_type, array $schema ): bool {
	// Validate schema structure.
	if ( ! apollo_validate_form_schema( $schema ) ) {
		return false;
	}

	// Get all schemas.
	$schemas = get_option( 'apollo_form_schemas', array() );

	// Update specific schema.
	$schemas[ $form_type ] = $schema;

	// Save to database.
	$result = update_option( 'apollo_form_schemas', $schemas );

	// Log schema change for audit.
	if ( $result ) {
		apollo_log_schema_change( $form_type, $schema );

		// Invalidate cache for all forms
		apollo_cache_flush_group( 'apollo_forms' );
	}

	return $result;
}

/**
 * Get default form schema for a form type
 *
 * @param string $form_type Form type.
 * @return array Default schema.
 */
function apollo_get_default_form_schema( $form_type ) {
	$defaults = array(
		'new_user'  => array(
			array(
				'key'        => 'user_login',
				'label'      => __( 'Username', 'apollo-core' ),
				'type'       => 'text',
				'required'   => true,
				'visible'    => true,
				'default'    => '',
				'validation' => '/^[a-z0-9_-]{3,15}$/i',
				'order'      => 1,
			),
			array(
				'key'        => 'user_email',
				'label'      => __( 'Email', 'apollo-core' ),
				'type'       => 'email',
				'required'   => true,
				'visible'    => true,
				'default'    => '',
				'validation' => 'email',
				'order'      => 2,
			),
			array(
				'key'        => 'user_pass',
				'label'      => __( 'Password', 'apollo-core' ),
				'type'       => 'password',
				'required'   => true,
				'visible'    => true,
				'default'    => '',
				'validation' => '',
				'order'      => 3,
			),
			array(
				'key'        => 'instagram_user_id',
				'label'      => __( 'Instagram ID', 'apollo-core' ),
				'type'       => 'instagram',
				'required'   => false,
				'visible'    => true,
				'default'    => '',
				'validation' => '/^[A-Za-z0-9_]{1,30}$/',
				'order'      => 4,
			),
		),
		'cpt_event' => array(
			array(
				'key'        => 'post_title',
				'label'      => __( 'Event Title', 'apollo-core' ),
				'type'       => 'text',
				'required'   => true,
				'visible'    => true,
				'default'    => '',
				'validation' => '',
				'order'      => 1,
			),
			array(
				'key'        => 'post_content',
				'label'      => __( 'Description', 'apollo-core' ),
				'type'       => 'textarea',
				'required'   => true,
				'visible'    => true,
				'default'    => '',
				'validation' => '',
				'order'      => 2,
			),
			array(
				'key'        => '_event_start_date',
				'label'      => __( 'Start Date', 'apollo-core' ),
				'type'       => 'date',
				'required'   => true,
				'visible'    => true,
				'default'    => '',
				'validation' => 'date',
				'order'      => 3,
			),
			array(
				'key'        => 'instagram_user_id',
				'label'      => __( 'Event Instagram', 'apollo-core' ),
				'type'       => 'instagram',
				'required'   => false,
				'visible'    => true,
				'default'    => '',
				'validation' => '/^[A-Za-z0-9_]{1,30}$/',
				'order'      => 4,
			),
		),
		'cpt_local' => array(
			array(
				'key'        => 'post_title',
				'label'      => __( 'Venue Name', 'apollo-core' ),
				'type'       => 'text',
				'required'   => true,
				'visible'    => true,
				'default'    => '',
				'validation' => '',
				'order'      => 1,
			),
			array(
				'key'        => 'post_content',
				'label'      => __( 'Description', 'apollo-core' ),
				'type'       => 'textarea',
				'required'   => false,
				'visible'    => true,
				'default'    => '',
				'validation' => '',
				'order'      => 2,
			),
			array(
				'key'        => '_local_address',
				'label'      => __( 'Address', 'apollo-core' ),
				'type'       => 'text',
				'required'   => true,
				'visible'    => true,
				'default'    => '',
				'validation' => '',
				'order'      => 3,
			),
		),
		'cpt_dj'    => array(
			array(
				'key'        => 'post_title',
				'label'      => __( 'DJ Name', 'apollo-core' ),
				'type'       => 'text',
				'required'   => true,
				'visible'    => true,
				'default'    => '',
				'validation' => '',
				'order'      => 1,
			),
			array(
				'key'        => 'post_content',
				'label'      => __( 'Bio', 'apollo-core' ),
				'type'       => 'textarea',
				'required'   => false,
				'visible'    => true,
				'default'    => '',
				'validation' => '',
				'order'      => 2,
			),
			array(
				'key'        => 'instagram_user_id',
				'label'      => __( 'Instagram ID', 'apollo-core' ),
				'type'       => 'instagram',
				'required'   => false,
				'visible'    => true,
				'default'    => '',
				'validation' => '/^[A-Za-z0-9_]{1,30}$/',
				'order'      => 3,
			),
		),
	);

	return isset( $defaults[ $form_type ] ) ? $defaults[ $form_type ] : array();
}

/**
 * Validate form schema structure
 *
 * @param array $schema Schema to validate.
 * @return bool True if valid, false otherwise.
 */
function apollo_validate_form_schema( $schema ) {
	if ( ! is_array( $schema ) ) {
		return false;
	}

	foreach ( $schema as $field ) {
		// Check required keys.
		$required_keys = array( 'key', 'label', 'type', 'required', 'visible', 'order' );
		foreach ( $required_keys as $required_key ) {
			if ( ! isset( $field[ $required_key ] ) ) {
				return false;
			}
		}

		// Validate type.
		$valid_types = array( 'text', 'textarea', 'number', 'email', 'select', 'checkbox', 'date', 'instagram', 'password' );
		if ( ! in_array( $field['type'], $valid_types, true ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Initialize default schemas on first run
 */
function apollo_init_form_schemas() {
	$schemas = get_option( 'apollo_form_schemas' );

	if ( false === $schemas ) {
		$default_schemas = array(
			'new_user'  => apollo_get_default_form_schema( 'new_user' ),
			'cpt_event' => apollo_get_default_form_schema( 'cpt_event' ),
			'cpt_local' => apollo_get_default_form_schema( 'cpt_local' ),
			'cpt_dj'    => apollo_get_default_form_schema( 'cpt_dj' ),
		);

		add_option( 'apollo_form_schemas', $default_schemas );
		add_option( 'apollo_form_schema_version', '1.0.0' );
	}
}
add_action( 'admin_init', 'apollo_init_form_schemas' );

/**
 * Migrate form schemas (idempotent)
 */
function apollo_migrate_form_schema(): void {
	$current_version = get_option( 'apollo_form_schema_version', '0.0.0' );

	// Version 1.0.0 migration - initial setup.
	if ( version_compare( $current_version, '1.0.0', '<' ) ) {
		apollo_init_form_schemas();
		update_option( 'apollo_form_schema_version', '1.0.0' );
	}

	// Future migrations go here.
}

/**
 * Log schema change for audit
 *
 * @param string $form_type Form type.
 * @param array  $schema    New schema.
 */
function apollo_log_schema_change( $form_type, $schema ) {
	if ( ! function_exists( 'apollo_mod_log_action' ) ) {
		return;
	}

	apollo_mod_log_action(
		get_current_user_id(),
		'schema_updated',
		'form_schema',
		0,
		array(
			'form_type'   => $form_type,
			'field_count' => count( $schema ),
			'timestamp'   => current_time( 'mysql' ),
		)
	);
}

/**
 * Validate field value against schema
 *
 * @param mixed $value Field value.
 * @param array $field_schema Field schema definition.
 * @return true|WP_Error True if valid, WP_Error if invalid.
 */
function apollo_validate_field_value( $value, $field_schema ) {
	// Check required.
	if ( $field_schema['required'] && empty( $value ) ) {
		return new WP_Error(
			'required_field',
			sprintf(
				/* translators: %s: field label */
				__( '%s is required.', 'apollo-core' ),
				$field_schema['label']
			)
		);
	}

	// Skip validation if empty and not required.
	if ( empty( $value ) && ! $field_schema['required'] ) {
		return true;
	}

	// Validate by type.
	switch ( $field_schema['type'] ) {
		case 'email':
			if ( ! is_email( $value ) ) {
				return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'apollo-core' ) );
			}

			break;

		case 'number':
			if ( ! is_numeric( $value ) ) {
				return new WP_Error( 'invalid_number', __( 'Must be a number.', 'apollo-core' ) );
			}

			break;

		case 'instagram':
			if ( ! preg_match( '/^[A-Za-z0-9_]{1,30}$/', $value ) ) {
				return new WP_Error( 'invalid_instagram', __( 'Invalid Instagram username. Only letters, numbers, and underscores allowed (max 30 characters).', 'apollo-core' ) );
			}

			break;
	}

	// Custom validation regex.
	if ( ! empty( $field_schema['validation'] ) && is_string( $field_schema['validation'] ) && strpos( $field_schema['validation'], '/' ) === 0 ) {
		if ( ! preg_match( $field_schema['validation'], $value ) ) {
			return new WP_Error(
				'validation_failed',
				sprintf(
					/* translators: %s: field label */
					__( '%s format is invalid.', 'apollo-core' ),
					$field_schema['label']
				)
			);
		}
	}

	return true;
}

/**
 * Check if Instagram ID is unique for user registration
 *
 * @param string $instagram_id Instagram ID to check.
 * @param int    $exclude_user_id User ID to exclude from check.
 * @return bool True if unique, false if already exists.
 */
function apollo_is_instagram_id_unique( string $instagram_id, int $exclude_user_id = 0 ): bool {
	$users = get_users(
		array(
			'meta_key'   => '_apollo_instagram_id',
			'meta_value' => $instagram_id,
			'fields'     => 'ID',
		)
	);

	foreach ( $users as $user_id ) {
		if ( $user_id !== $exclude_user_id ) {
			return false;
		}
	}

	return true;
}
