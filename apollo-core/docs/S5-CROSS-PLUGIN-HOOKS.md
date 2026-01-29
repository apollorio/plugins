# S5: Cross-Plugin Hooks Architecture - Implementation Guide

## Overview

This document describes the cross-plugin hooks architecture for the Apollo plugin ecosystem.
The architecture provides standardized communication between plugins using hook priorities,
an event bus system, and dependency resolution.

## Files Created

### 1. class-apollo-hook-priorities.php
**Path:** `apollo-core/includes/class-apollo-hook-priorities.php`

Centralized priority constants for all WordPress hooks:

```php
use Apollo_Core\Apollo_Hook_Priorities;

// Plugin loading order
add_action( 'plugins_loaded', [ $this, 'init' ], Apollo_Hook_Priorities::CORE_INIT );
add_action( 'plugins_loaded', [ $this, 'init' ], Apollo_Hook_Priorities::EVENTS_INIT );

// Registration order
add_action( 'init', [ $this, 'register_cpt' ], Apollo_Hook_Priorities::CPT_REGISTRATION );
add_action( 'init', [ $this, 'register_tax' ], Apollo_Hook_Priorities::TAXONOMY_REGISTRATION );
add_action( 'rest_api_init', [ $this, 'register_routes' ], Apollo_Hook_Priorities::REST_REGISTRATION );
```

### 2. class-apollo-event-bus.php
**Path:** `apollo-core/includes/class-apollo-event-bus.php`

Pub/Sub system for cross-plugin events:

```php
use Apollo_Core\Apollo_Event_Bus;

// Emit an event
Apollo_Event_Bus::emit( Apollo_Event_Bus::EVENT_CREATED, [
    'event_id' => $post_id,
    'title'    => $title,
]);

// Listen to an event
Apollo_Event_Bus::on( Apollo_Event_Bus::EVENT_CREATED, function( $data ) {
    // Handle event creation
    $event_id = $data['event_id'];
    // Update activity feed, send notifications, etc.
}, 10 );

// Listen to multiple events
Apollo_Event_Bus::on_any(
    [ Apollo_Event_Bus::CONTENT_LIKED, Apollo_Event_Bus::CONTENT_FAVORITED ],
    [ $this, 'handle_engagement' ]
);

// Listen once (auto-unsubscribe)
Apollo_Event_Bus::once( Apollo_Event_Bus::USER_REGISTERED, function( $data ) {
    // Send welcome email
});

// Defer event to shutdown
Apollo_Event_Bus::defer( Apollo_Event_Bus::CACHE_INVALIDATED, [
    'keys' => [ 'events_list', 'featured_events' ],
]);
```

### 3. class-apollo-dependency-resolver.php
**Path:** `apollo-core/includes/class-apollo-dependency-resolver.php`

Plugin dependency management:

```php
use Apollo_Core\Apollo_Dependency_Resolver;

// Register dependency
Apollo_Dependency_Resolver::register_dependency( 'my-addon', [ 'apollo-core', 'apollo-social' ], '1.0.0' );

// Check before activation
if ( ! Apollo_Dependency_Resolver::can_activate( 'apollo-events-manager' ) ) {
    $missing = Apollo_Dependency_Resolver::get_missing_dependencies( 'apollo-events-manager' );
    // Handle missing deps
}

// Get correct load order
$order = Apollo_Dependency_Resolver::get_load_order();
// ['apollo-core', 'apollo-events-manager', 'apollo-social', 'apollo-rio']
```

---

## Hook Priority Constants

### Plugin Loading Order (`plugins_loaded`)

| Constant | Value | Usage |
|----------|-------|-------|
| `CORE_INIT` | 1 | Apollo Core (must be first) |
| `EVENTS_INIT` | 5 | Apollo Events Manager |
| `SOCIAL_INIT` | 5 | Apollo Social |
| `RIO_INIT` | 20 | Apollo Rio (last, for optimization) |
| `THIRD_PARTY_INIT` | 50 | Third-party integrations |

### Registration Order (`init`)

| Constant | Value | Usage |
|----------|-------|-------|
| `CPT_REGISTRATION` | 5 | Custom Post Types |
| `TAXONOMY_REGISTRATION` | 6 | Taxonomies (after CPTs) |
| `META_REGISTRATION` | 7 | Meta keys (after taxonomies) |
| `REWRITE_REGISTRATION` | 8 | Rewrite rules |
| `REST_REGISTRATION` | 10 | REST API routes |
| `SHORTCODE_REGISTRATION` | 15 | Shortcodes |
| `WIDGET_REGISTRATION` | 20 | Widgets |
| `BLOCK_REGISTRATION` | 25 | Blocks |

### Template Rendering

| Constant | Value | Usage |
|----------|-------|-------|
| `BEFORE_RENDER` | 5 | Before any rendering |
| `RENDER` | 10 | Default rendering |
| `AFTER_RENDER` | 15 | After rendering |
| `TEMPLATE_OVERRIDE` | 1 | Override templates |
| `TEMPLATE_FALLBACK` | 100 | Fallback templates |

### Data Processing

| Constant | Value | Usage |
|----------|-------|-------|
| `VALIDATION` | 5 | Data validation |
| `SANITIZATION` | 10 | Data sanitization |
| `TRANSFORMATION` | 15 | Data transformation |
| `PERSISTENCE` | 20 | Data persistence |
| `CACHE_UPDATE` | 25 | Cache update |

### Event Propagation

| Constant | Value | Usage |
|----------|-------|-------|
| `EVENT_CRITICAL` | 1 | Logging, security |
| `EVENT_CORE` | 5 | Core handlers |
| `EVENT_COMPANION` | 10 | Companion plugins |
| `EVENT_ANALYTICS` | 50 | Analytics/tracking |
| `EVENT_CLEANUP` | 100 | Cleanup handlers |

---

## Event Bus Events

### Events (apollo.event.*)

| Event | Constant | Data |
|-------|----------|------|
| Event created | `EVENT_CREATED` | `event_id`, `title`, `author_id` |
| Event updated | `EVENT_UPDATED` | `event_id`, `changes` |
| Event deleted | `EVENT_DELETED` | `event_id` |
| Event published | `EVENT_PUBLISHED` | `event_id` |
| RSVP added | `RSVP_ADDED` | `event_id`, `user_id` |
| RSVP removed | `RSVP_REMOVED` | `event_id`, `user_id` |
| Event interested | `EVENT_INTERESTED` | `event_id`, `user_id` |

### Users (apollo.user.*)

| Event | Constant | Data |
|-------|----------|------|
| User registered | `USER_REGISTERED` | `user_id`, `email` |
| User updated | `USER_UPDATED` | `user_id`, `fields` |
| User followed | `USER_FOLLOWED` | `follower_id`, `followed_id` |
| User unfollowed | `USER_UNFOLLOWED` | `follower_id`, `followed_id` |
| Bubble added | `BUBBLE_ADDED` | `user_id`, `target_id` |
| Bubble removed | `BUBBLE_REMOVED` | `user_id`, `target_id` |
| User verified | `USER_VERIFIED` | `user_id`, `verifier_id` |
| User suspended | `USER_SUSPENDED` | `user_id`, `reason` |
| User banned | `USER_BANNED` | `user_id`, `reason` |

### Content (apollo.content.*)

| Event | Constant | Data |
|-------|----------|------|
| Content liked | `CONTENT_LIKED` | `content_id`, `content_type`, `user_id` |
| Content unliked | `CONTENT_UNLIKED` | `content_id`, `content_type`, `user_id` |
| Content favorited | `CONTENT_FAVORITED` | `content_id`, `content_type`, `user_id` |
| Content unfavorited | `CONTENT_UNFAVORITED` | `content_id`, `content_type`, `user_id` |
| Content commented | `CONTENT_COMMENTED` | `content_id`, `comment_id`, `user_id` |
| Content shared | `CONTENT_SHARED` | `content_id`, `user_id`, `platform` |
| Content reported | `CONTENT_REPORTED` | `content_id`, `reporter_id`, `reason` |

### Social (apollo.social.*)

| Event | Constant | Data |
|-------|----------|------|
| Activity posted | `ACTIVITY_POSTED` | `activity_id`, `user_id`, `type` |
| Activity deleted | `ACTIVITY_DELETED` | `activity_id` |
| Message sent | `MESSAGE_SENT` | `message_id`, `sender_id`, `recipient_id` |
| Message read | `MESSAGE_READ` | `message_id`, `user_id` |
| Notification sent | `NOTIFICATION_SENT` | `notification_id`, `user_id`, `type` |

### Groups (apollo.group.*)

| Event | Constant | Data |
|-------|----------|------|
| Group created | `GROUP_CREATED` | `group_id`, `creator_id`, `type` |
| Group updated | `GROUP_UPDATED` | `group_id`, `changes` |
| Group deleted | `GROUP_DELETED` | `group_id` |
| Member joined | `GROUP_JOINED` | `group_id`, `user_id` |
| Member left | `GROUP_LEFT` | `group_id`, `user_id` |
| Member invited | `GROUP_INVITED` | `group_id`, `user_id`, `inviter_id` |

### Gamification (apollo.gamification.*)

| Event | Constant | Data |
|-------|----------|------|
| Points earned | `POINTS_EARNED` | `user_id`, `points`, `reason` |
| Level up | `LEVEL_UP` | `user_id`, `new_level` |
| Badge earned | `BADGE_EARNED` | `user_id`, `badge_id` |

### System (apollo.system.*)

| Event | Constant | Data |
|-------|----------|------|
| Cache invalidated | `CACHE_INVALIDATED` | `keys`, `reason` |
| Plugin activated | `PLUGIN_ACTIVATED` | `plugin`, `version` |
| Plugin deactivated | `PLUGIN_DEACTIVATED` | `plugin` |
| Settings updated | `SETTINGS_UPDATED` | `setting_key`, `old`, `new` |
| Migration completed | `MIGRATION_COMPLETED` | `migration_id`, `records` |

---

## Plugin Integration Hooks

### apollo-events-manager

Add these hooks to `apollo-events-manager.php`:

```php
// At plugin load
do_action( 'apollo_events_manager_loaded' );

// Before/after event save
do_action( 'apollo_event_before_save', $post_id, $data );
do_action( 'apollo_event_after_save', $post_id, $data );

// Before event delete
do_action( 'apollo_event_before_delete', $post_id );

// DJ/Venue hooks
do_action( 'apollo_dj_saved', $dj_id, $data );
do_action( 'apollo_venue_saved', $venue_id, $data );

// Event Bus integration
Apollo_Event_Bus::emit( Apollo_Event_Bus::EVENT_CREATED, [
    'event_id'  => $post_id,
    'title'     => get_the_title( $post_id ),
    'author_id' => get_current_user_id(),
]);
```

### apollo-social

Add these hooks to `src/Plugin.php`:

```php
// At plugin load
do_action( 'apollo_social_loaded' );

// Social post created
do_action( 'apollo_social_post_created', $post_id, $user_id );

// Connection changes
do_action( 'apollo_user_connection_changed', $user_id, $target_id, $action );
// $action: 'follow', 'unfollow', 'bubble_add', 'bubble_remove'

// Event Bus integration
Apollo_Event_Bus::emit( Apollo_Event_Bus::USER_FOLLOWED, [
    'follower_id' => $user_id,
    'followed_id' => $target_id,
]);
```

### apollo-rio

Add these hooks to `apollo-rio.php`:

```php
// At plugin load
do_action( 'apollo_rio_loaded' );

// Cache cleared
do_action( 'apollo_rio_cache_cleared' );

// PWA events
do_action( 'apollo_rio_sw_updated', $version );
do_action( 'apollo_rio_offline_page_cached', $pages );
```

---

## Usage Examples

### Cross-Plugin Activity Feed

```php
// In apollo-social: Listen for events from apollo-events-manager
Apollo_Event_Bus::on( Apollo_Event_Bus::EVENT_CREATED, function( $data ) {
    // Create activity post for new event
    apollo_create_activity([
        'type'      => 'new_event',
        'user_id'   => $data['author_id'],
        'object_id' => $data['event_id'],
    ]);
}, Apollo_Hook_Priorities::EVENT_COMPANION );
```

### Cross-Plugin Notifications

```php
// In apollo-social: Listen for content engagement
Apollo_Event_Bus::on( Apollo_Event_Bus::CONTENT_LIKED, function( $data ) {
    $content_author = get_post_field( 'post_author', $data['content_id'] );

    if ( $content_author !== $data['user_id'] ) {
        apollo_send_notification( $content_author, 'like', [
            'liker_id'   => $data['user_id'],
            'content_id' => $data['content_id'],
        ]);
    }
});
```

### Cross-Plugin Cache Invalidation

```php
// In apollo-rio: Listen for content changes
Apollo_Event_Bus::on_any(
    [
        Apollo_Event_Bus::EVENT_CREATED,
        Apollo_Event_Bus::EVENT_UPDATED,
        Apollo_Event_Bus::EVENT_DELETED,
    ],
    function( $data ) {
        apollo_rio_clear_cache([
            'events_list',
            'featured_events',
            'event_' . $data['event_id'],
        ]);
    }
);
```

### Gamification Points

```php
// In apollo-social: Award points for user actions
Apollo_Event_Bus::on( Apollo_Event_Bus::CONTENT_LIKED, function( $data ) {
    apollo_award_points( $data['user_id'], 1, 'liked_content' );
});

Apollo_Event_Bus::on( Apollo_Event_Bus::RSVP_ADDED, function( $data ) {
    apollo_award_points( $data['user_id'], 5, 'event_rsvp' );
});

Apollo_Event_Bus::on( Apollo_Event_Bus::GROUP_JOINED, function( $data ) {
    apollo_award_points( $data['user_id'], 10, 'joined_group' );
});
```

---

## Loading Order

In `apollo-core.php`:

```php
// Load in order
require_once __DIR__ . '/includes/class-apollo-hook-priorities.php';
require_once __DIR__ . '/includes/class-apollo-event-bus.php';
require_once __DIR__ . '/includes/class-apollo-dependency-resolver.php';

// Use priorities
add_action( 'plugins_loaded', function() {
    do_action( 'apollo_core_loaded' );
}, Apollo_Hook_Priorities::CORE_INIT );
```

In companion plugins:

```php
// apollo-events-manager.php
add_action( 'plugins_loaded', function() {
    if ( ! Apollo_Dependency_Resolver::is_loaded( 'apollo-core' ) ) {
        return; // Core not ready
    }
    // Initialize plugin
    do_action( 'apollo_events_manager_loaded' );
}, Apollo_Hook_Priorities::EVENTS_INIT );

// apollo-social.php
add_action( 'plugins_loaded', function() {
    if ( ! Apollo_Dependency_Resolver::is_loaded( 'apollo-core' ) ) {
        return;
    }
    do_action( 'apollo_social_loaded' );
}, Apollo_Hook_Priorities::SOCIAL_INIT );
```

---

## Debugging

### Get Event History

```php
$bus = Apollo_Event_Bus::get_instance();
$history = $bus->get_history(); // Only in WP_DEBUG mode
```

### Get Dependency Graph

```php
$resolver = Apollo_Dependency_Resolver::get_instance();
$graph = $resolver->get_dependency_graph();
```

### Validate Dependencies

```php
$results = Apollo_Dependency_Resolver::get_instance()->validate();
if ( ! $results['valid'] ) {
    foreach ( $results['errors'] as $error ) {
        error_log( $error );
    }
}
```

---

## Checklist

- [x] Create Hook Priority Map
- [x] Create Event Bus System
- [x] Create Plugin Dependency Resolver
- [x] Create Integration Guide
- [ ] Update apollo-events-manager with event emissions
- [ ] Update apollo-social with event emissions
- [ ] Update apollo-rio with cache invalidation listeners
- [ ] Add event bus to main plugin loaders

---

## Next Steps

1. Add these files to apollo-core autoloader
2. Update each plugin to emit events
3. Connect listeners between plugins
4. Test cross-plugin communication
5. Add unit tests for event bus
