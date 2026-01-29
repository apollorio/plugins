# S6: Data Relationships & Foreign Keys

## Overview

This phase establishes explicit relationship definitions for the implicit connections between Custom Post Types (CPTs) in the Apollo plugin ecosystem. Rather than having scattered meta key references, all relationships are now centrally defined with proper schemas, query capabilities, REST endpoints, and integrity checking.

## Files Created

| File | Purpose | Lines |
|------|---------|-------|
| `class-apollo-relationships.php` | Central schema registry for all CPT relationships | ~550 |
| `class-apollo-relationship-query.php` | Query builder for relationship operations | ~480 |
| `class-apollo-relationship-rest.php` | REST API endpoints for relationships | ~520 |
| `class-apollo-relationship-integrity.php` | Integrity checking and repair tools | ~500 |

## Relationship Types

```php
Apollo_Relationships::ONE_TO_ONE      // e.g., user → profile
Apollo_Relationships::ONE_TO_MANY     // e.g., event → classifieds
Apollo_Relationships::MANY_TO_ONE     // e.g., classified → event
Apollo_Relationships::MANY_TO_MANY    // e.g., event ↔ djs
```

## Storage Types

```php
Apollo_Relationships::STORAGE_SINGLE_ID          // Single ID in meta
Apollo_Relationships::STORAGE_SERIALIZED_ARRAY   // PHP serialized array
Apollo_Relationships::STORAGE_JSON_ARRAY         // JSON encoded array
Apollo_Relationships::STORAGE_CSV                // Comma-separated IDs
Apollo_Relationships::STORAGE_TAXONOMY           // Term relationships
Apollo_Relationships::STORAGE_TABLE              // Custom table (future)
```

## Defined Relationships

### Event Relationships

| Name | From | To | Type | Meta Key |
|------|------|-----|------|----------|
| `event_to_dj` | apollo-event | apollo-dj | many-to-many | `_event_dj_ids` |
| `event_to_local` | apollo-event | apollo-local | many-to-many | `_event_local_ids` |
| `event_to_classified` | apollo-event | apollo-classified | one-to-many | `_classified_event_id` |
| `event_to_user_rsvp` | apollo-event | user | many-to-many | `_event_rsvp_users` |
| `event_to_supplier` | apollo-event | apollo-supplier | many-to-many | `_event_supplier_ids` |

### DJ Relationships

| Name | From | To | Type | Meta Key |
|------|------|-----|------|----------|
| `dj_to_event` | apollo-dj | apollo-event | many-to-many | `_event_dj_ids` |
| `dj_to_social_post` | apollo-dj | apollo-social | one-to-many | `_social_post_dj_id` |

### Local (Venue) Relationships

| Name | From | To | Type | Meta Key |
|------|------|-----|------|----------|
| `local_to_event` | apollo-local | apollo-event | many-to-many | `_event_local_ids` |

### User Relationships

| Name | From | To | Type | User Meta Key |
|------|------|-----|------|---------------|
| `user_to_event_rsvp` | user | apollo-event | many-to-many | `_user_event_rsvps` |
| `user_to_followers` | user | user | many-to-many | `_user_followers` |
| `user_to_following` | user | user | many-to-many | `_user_following` |
| `user_to_favorites` | user | [multiple CPTs] | many-to-many | `_user_favorites` |
| `user_to_bubble` | user | user | many-to-many | `_user_bubble` |

## Usage Examples

### Querying Relationships

```php
use Apollo_Core\Apollo_Relationship_Query;

// Get all DJs for an event
$djs = Apollo_Relationship_Query::get_related( $event_id, 'event_to_dj' );

// Get just IDs
$dj_ids = Apollo_Relationship_Query::get_related( $event_id, 'event_to_dj', [
    'return' => 'ids',
] );

// Get paginated results
$result = Apollo_Relationship_Query::paginate( $event_id, 'event_to_dj', 1, 10 );
// Returns: ['items' => [...], 'total' => 25, 'page' => 1, 'pages' => 3]

// Check if connected
$is_attending = Apollo_Relationship_Query::is_connected( $user_id, $event_id, 'user_to_event_rsvp' );

// Count related items
$dj_count = Apollo_Relationship_Query::count( $event_id, 'event_to_dj' );
```

### Managing Relationships

```php
use Apollo_Core\Apollo_Relationship_Query;

// Connect items
Apollo_Relationship_Query::connect( $event_id, $dj_id, 'event_to_dj' );
// Automatically syncs inverse (dj_to_event) for bidirectional relationships

// Disconnect items
Apollo_Relationship_Query::disconnect( $event_id, $dj_id, 'event_to_dj' );

// Sync all (replace entire relationship)
Apollo_Relationship_Query::sync( $event_id, [ $dj_1, $dj_2, $dj_3 ], 'event_to_dj' );
```

### Accessing Relationship Schema

```php
use Apollo_Core\Apollo_Relationships;

// Get all relationships
$schema = Apollo_Relationships::get_schema();

// Get specific relationship
$definition = Apollo_Relationships::get( 'event_to_dj' );

// Get relationships FROM a post type
$event_rels = Apollo_Relationships::get_for_post_type( 'apollo-event' );

// Get relationships TO a post type
$pointing_to_event = Apollo_Relationships::get_pointing_to( 'apollo-event' );

// Check if bidirectional
$is_bidirectional = Apollo_Relationships::is_bidirectional( 'event_to_dj' );
```

## REST API Endpoints

### Collection Endpoints

```
GET /apollo/v1/events/{id}/djs
GET /apollo/v1/events/{id}/locals
GET /apollo/v1/events/{id}/classifieds
GET /apollo/v1/events/{id}/suppliers
GET /apollo/v1/events/{id}/rsvps

GET /apollo/v1/djs/{id}/events
GET /apollo/v1/djs/{id}/social-posts

GET /apollo/v1/locals/{id}/events

GET /apollo/v1/users/{id}/followers
GET /apollo/v1/users/{id}/following
GET /apollo/v1/users/{id}/favorites
GET /apollo/v1/users/me/followers  (current user)
```

### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 10 | Items per page (max 100) |

### Response Headers

```
X-WP-Total: 25
X-WP-TotalPages: 3
Link: <.../events/123/djs?page=2>; rel="next"
```

### Modifying Relationships (Authenticated)

```http
# Connect items
POST /apollo/v1/events/{id}/djs
Content-Type: application/json
{ "ids": [101, 102, 103] }

# Disconnect items
DELETE /apollo/v1/events/{id}/djs
Content-Type: application/json
{ "ids": [101] }

# Sync (replace all)
PUT /apollo/v1/events/{id}/djs
Content-Type: application/json
{ "ids": [101, 102] }
```

### Schema Discovery

```http
GET /apollo/v1/relationships
# Returns all relationship schemas

GET /apollo/v1/relationships/event_to_dj
# Returns specific relationship schema
```

## Integrity Checking

### Running Checks

```php
use Apollo_Core\Apollo_Relationship_Integrity;

// Check all relationships
$report = Apollo_Relationship_Integrity::check_all();

// Check specific relationship
$result = Apollo_Relationship_Integrity::check_relationship(
    'event_to_dj',
    Apollo_Relationships::get( 'event_to_dj' ),
    [ 'limit' => 500 ]
);
```

### Issue Types Detected

| Type | Severity | Description |
|------|----------|-------------|
| `orphaned_reference` | Error | Referenced post/user doesn't exist |
| `missing_inverse` | Warning | Bidirectional link not reciprocated |
| `invalid_post_type` | Error | Referenced post has wrong type |
| `duplicate_reference` | Warning | Same ID appears multiple times |
| `self_reference` | Error | Post references itself (when not allowed) |

### Repairing Issues

```php
use Apollo_Core\Apollo_Relationship_Integrity;

// Dry run (don't actually repair)
$preview = Apollo_Relationship_Integrity::repair_relationship( 'event_to_dj', [
    'dry_run' => true,
] );

// Actually repair
$result = Apollo_Relationship_Integrity::repair_relationship( 'event_to_dj' );
```

### Scheduled Checks

Integrity checks are automatically scheduled weekly:

```php
// Manually schedule
Apollo_Relationship_Integrity::schedule_checks();

// Hook into results
add_action( 'apollo.integrity.issues_found', function( $data ) {
    $report = $data['report'];
    // Send notification, etc.
} );
```

### HTML Report

```php
$report = Apollo_Relationship_Integrity::check_all();
$html = Apollo_Relationship_Integrity::generate_html_report( $report );
```

## Event Bus Integration

Relationship operations emit events via the Apollo Event Bus:

```php
// When items are connected
add_action( 'apollo.relationship.connected', function( $data ) {
    $from_id = $data['from_id'];
    $to_id = $data['to_id'];
    $relationship = $data['relationship'];
} );

// When items are disconnected
add_action( 'apollo.relationship.disconnected', function( $data ) {
    // ...
} );

// When relationships are synced
add_action( 'apollo.relationship.synced', function( $data ) {
    $added = $data['added'];
    $removed = $data['removed'];
} );

// When integrity issues are found
add_action( 'apollo.integrity.issues_found', function( $data ) {
    $report = $data['report'];
} );
```

## Extending Relationships

### Adding Custom Relationships

```php
add_filter( 'apollo_relationship_schema', function( $schema ) {
    $schema['product_to_event'] = [
        'from'          => 'product',
        'to'            => 'apollo-event',
        'type'          => Apollo_Relationships::MANY_TO_MANY,
        'storage'       => Apollo_Relationships::STORAGE_SERIALIZED_ARRAY,
        'meta_key'      => '_product_event_ids',
        'bidirectional' => true,
        'inverse'       => 'event_to_product',
        'rest_exposed'  => true,
    ];

    return $schema;
} );
```

### Custom REST Slugs

```php
$schema['event_to_dj'] = [
    // ...
    'rest_slug' => 'performers',  // GET /events/{id}/performers
];
```

## Migration Guide

### From Direct Meta Access

Before:
```php
$dj_ids = get_post_meta( $event_id, '_event_dj_ids', true );
$dj_ids = maybe_unserialize( $dj_ids );
foreach ( $dj_ids as $dj_id ) {
    $dj = get_post( $dj_id );
    // ...
}
```

After:
```php
$djs = Apollo_Relationship_Query::get_related( $event_id, 'event_to_dj' );
foreach ( $djs as $dj ) {
    // $dj is already a WP_Post object
}
```

### Benefits

1. **Type Safety**: Relationships are validated against schema
2. **Bidirectional Sync**: Inverse relationships are automatically maintained
3. **REST Exposure**: Relationships accessible via REST API
4. **Integrity Checking**: Orphaned references detected and repaired
5. **Event Emission**: Other plugins can react to relationship changes
6. **Query Optimization**: Centralized queries can be optimized

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────┐
│                    REST Layer                            │
│  /apollo/v1/events/{id}/djs                             │
│  /apollo/v1/relationships                               │
└─────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│              Apollo_Relationship_REST                    │
│  - Route registration                                    │
│  - Permission checks                                     │
│  - Response formatting                                   │
└─────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│            Apollo_Relationship_Query                     │
│  - get_related()                                         │
│  - connect() / disconnect()                              │
│  - sync()                                                │
│  - Bidirectional sync handling                           │
└─────────────────────────────────────────────────────────┘
                           │
              ┌────────────┴────────────┐
              ▼                         ▼
┌─────────────────────────┐  ┌─────────────────────────┐
│  Apollo_Relationships    │  │ Apollo_Relationship_    │
│  - Schema definitions    │  │ Integrity               │
│  - Storage types         │  │ - Check orphans         │
│  - Relationship metadata │  │ - Repair issues         │
└─────────────────────────┘  │ - Scheduled checks      │
                              └─────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│                   WordPress Meta                         │
│  - post_meta / user_meta                                 │
│  - Serialized arrays, JSON, CSV, etc.                    │
└─────────────────────────────────────────────────────────┘
```

## Related Documentation

- [S4: REST Namespace Consolidation](./S4-REST-NAMESPACE-CONSOLIDATION.md)
- [S5: Cross-Plugin Hooks](./S5-CROSS-PLUGIN-HOOKS.md)
- [S2: Taxonomy Consolidation](./S2-TAXONOMY-CONSOLIDATION.md)
- [S3: Meta Keys Consolidation](./S3-META-KEYS-CONSOLIDATION.md)
