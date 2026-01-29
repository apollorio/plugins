# Apollo Project - Technical Overview

This is a sophisticated WordPress plugin suite for event management and social networking in Rio, built as a modular architecture with 4 interconnected plugins working as one unified system.

## 🏗️ Architecture Overview

**Core Philosophy:** Modular monolith - 4 plugins that function as a single platform, with clear separation of concerns but tight integration.

### Plugin Breakdown

#### 1. **apollo-core** (Foundation Layer)

- **Purpose:** Base utilities, centralized identifiers, security, and cross-plugin communication
- **Key Features:**
  - `Apollo_Identifiers` class - Single source of truth for ALL slugs, constants, and identifiers
  - Security & moderation system with audit logging
  - Unified email/communication hub (main menu at position 30)
  - Base event_listing CPT (though managed by events-manager)
  - 15+ custom database tables for logging, relationships, notifications
  - REST API namespace: `apollo/v1`

#### 2. **apollo-events-manager** (Event Management)

- **Purpose:** Complete event lifecycle management with DJs, venues, and analytics
- **Key Features:**
  - **CPTs:** event_listing, event_dj, event_local, apollo_event_stat
  - **Taxonomies:** event_listing_category, event_listing_type, event_sounds, event_season
  - **Modules:** Calendar, Interest tracking, Reviews, Speakers (DJs), Analytics
  - **Import/Export:** CSV/XML support for bulk operations
  - **Meta Keys:** 20+ event-specific fields (\_event_start_date, \_event_dj_ids array, etc.)
  - **REST API:** apollo-events/v1 namespace
  - **Shortcodes:** 19 total, including apollo_events_grid, apollo_dj_grid

#### 3. **apollo-social** (Social Features)

- **Purpose:** User engagement, profiles, communities, and content management
- **Key Features:**
  - **CPTs:** apollo_social_post, user_page, apollo_classified, apollo_supplier, apollo_document
  - **Groups System:** Custom table-based (not CPT) for Comunas (public) and Núcleos (private)
  - **User Pages:** Custom post type with layouts (apollo_userpage_layout_v1 meta)
  - **Verification System:** User verification with badges
  - **Classifieds:** Buy/sell with domains and statuses
  - **Documents:** File management with categories and signatures
  - **REST API:** apollo-social/v2 namespace
  - **Shortcodes:** 15+ including activity feeds, member directories

#### 4. **Cena Rio Module** (Integrated into apollo-social)

- **Purpose:** Specialized event planning and document management for Cena Rio cultural events
- **Key Features:**
  - **CPTs:** cena_document, cena_event_plan
  - **Moderation Queue:** Specialized content approval workflow
  - **Event Planning:** Timeline and resource management
  - **Document Categories:** Hierarchical organization
  - **Admin Icons:** Recently updated (2026-01-22) to use calendar-alt for documents, analytics for events

## 🔧 Technical Implementation

### Database Schema (25+ Custom Tables)

- **Logging:** wp_apollo_activity_log, wp_apollo_audit_log, wp_apollo_mod_log
- **Analytics:** wp*apollo_pageviews, wp_apollo_interactions, wp_apollo_stats*\*
- **Relationships:** wp_apollo_relationships, wp_apollo_groups
- **Communications:** wp*apollo_notifications, wp_apollo_email*\*
- **Events:** wp_apollo_event_queue, wp_apollo_event_bookmarks
- **Quiz System:** wp*apollo_quiz*\* (multiple tables)

### REST API Architecture

- **Namespaces:** apollo/v1, apollo-events/v1, apollo-social/v2 (being standardized)
- **50+ Routes:** GET/POST/PUT/DELETE for events, social posts, profiles, etc.
- **Authentication:** WordPress nonce + capability checks
- **Rate Limiting:** Built-in protection

### Hook System (150+ Hooks)

- **Actions:** apollo_activated, apollo_user_interested, apollo_user_verified
- **Filters:** apollo_ajax_actions, apollo_events_placeholder_defaults
- **Custom:** apollo_security_threat_detected, apollo_classified_created

### Security Features

- **Audit Logging:** Every action tracked with user_id, object_type, meta_data
- **Moderation:** Queue system with bulk actions
- **Data Sanitization:** All inputs validated
- **File Scanning:** Upload security for documents/images
- **Email Security:** SMTP configuration with logging

## 🎯 Key Differentiators

### 1. **Centralized Identifiers System**

```php
// Single class manages ALL identifiers
use Apollo_Core\Apollo_Identifiers as ID;

register_post_type( ID::CPT_EVENT_LISTING, $args );
$route = ID::rest_route( ID::REST_ROUTE_EVENTOS );
$table = ID::table( ID::TABLE_GROUPS );
```

### 2. **Groups vs Taxonomy Distinction**

- **Taxonomies:** For categorization (event_season for events)
- **Groups:** Custom table for social relationships (Comunas/Núcleos/Seasons)

### 3. **Dual Meta Key Strategy**

- **Legacy:** \_event_djs (single ID)
- **New:** \_event_dj_ids (array) - migration in progress

### 4. **Modular Architecture with Tight Coupling**

- Plugins can be updated independently
- Shared identifiers prevent conflicts
- Cross-plugin features (events in social feeds, etc.)

## 📊 Scale & Performance

### Current Metrics

- **13 CPTs** registered across plugins
- **50+ REST endpoints** for API access
- **40+ shortcodes** for frontend integration
- **100+ meta keys** for extended data
- **25+ custom tables** for specialized storage

### Performance Considerations

- **Query Optimization:** Custom table indexes for large datasets
- **Caching:** WordPress transients for expensive operations
- **Asset Management:** 50+ handles, minified where possible
- **Background Processing:** Cron jobs for heavy tasks

- **REST Namespace Standardization:** All to apollo/v2
- **event_season Clarification:** Taxonomy vs Group distinction

### Code Quality

- **PSR-4:** 100% compliant
- **PHPCS:** 0 critical errors
- **Testing:** Manual QA complete, unit tests planned

## 🔗 Integration Points

### WordPress Core

- **User System:** Extended with verification, roles, custom fields
- **Media:** Custom upload handlers with security scanning
- **Taxonomy:** Standard WP taxonomies + custom group system
- **REST API:** Native integration with custom endpoints

### External Services

- **Email:** SMTP configuration for notifications
- **Social:** Instagram integration (\_apollo_instagram_id)
- **Maps:** Latitude/longitude for venues (legacy + new fields)
- **Analytics:** Custom tracking with heatmaps

## 📚 Documentation

Complete audit available:

- **APOLLO_COMPLETE_AUDIT.md:** 1277 lines technical reference
- **INVENTORY.md:** 1193 lines with all identifiers
- **APOLLO_AUDIT_DATA.json:** Structured data for tooling

## 🎯 Why This Architecture?

**Strengths:**

- **Modularity:** Independent plugin updates
- **Scalability:** Custom tables for performance
- **Maintainability:** Centralized identifiers
- **Extensibility:** Hook system for customization

**Trade-offs:**

- **Complexity:** 4 plugins to manage
- **Coordination:** Cross-plugin features require planning
- **Learning Curve:** Understanding the ecosystem

This is a production-ready, enterprise-level WordPress platform with sophisticated social and event management capabilities. The modular design allows for independent development while maintaining tight integration through the core identifiers system.

# Inventory of - Mandatory step!

ALWAYS KEEP UPDATED CONTROL OF ALL SLUGS TAXONOMY NAMES CLASSES FUCNTIONS REST PN: INVENTORY.md of path "C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins\apollo-core\INVENTORY.md"
