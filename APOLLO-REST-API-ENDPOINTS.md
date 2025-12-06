# Apollo Plugins Ecosystem - REST API Endpoints

**Generated:** December 6, 2025  
**Plugins Analyzed:** apollo-core, apollo-social, apollo-events-manager, apollo-rio

---

## Table of Contents
1. [Apollo Core](#apollo-core)
2. [Apollo Social](#apollo-social)
3. [Apollo Events Manager](#apollo-events-manager)
4. [Apollo Rio (PWA)](#apollo-rio-pwa)

---

## Apollo Core

### Namespace: `apollo/v1`

#### Health & System

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/health` | GET | Health check - returns plugin version and active modules | None | Public |

---

#### Membership Management

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/memberships` | GET | Get all membership types | None | Public |
| `/memberships/set` | POST | Set user membership type | `user_id` (int, required), `membership_slug` (string, required) | `edit_apollo_users` |
| `/memberships/create` | POST | Create new membership type | `slug`, `label`, `frontend_label`, `color`, `text_color` (all required) | `manage_options` |
| `/memberships/update` | POST | Update existing membership type | `slug` (required), `label`, `frontend_label`, `color`, `text_color` (optional) | `manage_options` |
| `/memberships/delete` | POST | Delete membership type | `slug` (required) | `manage_options` |
| `/memberships/export` | GET | Export memberships as JSON | None | `manage_options` |
| `/memberships/import` | POST | Import memberships from JSON | `data` (string, required) | `manage_options` |

---

#### Moderation (rest-moderation.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/moderation/approve` | POST | Approve pending content | `post_id` (int, required), `note` (optional) | `moderate_apollo_content` |
| `/users/suspend` | POST | Suspend a user temporarily | `user_id` (int, required), `days` (int, required), `reason` (optional) | `suspend_users` |
| `/users/block` | POST | Block a user permanently | `user_id` (int, required), `reason` (optional) | `block_users` |

---

#### Moderation Module (class-rest-api.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/moderation/approve` | POST | Approve/publish post | `post_id` (int, required), `note` (optional) | Moderate permission |
| `/moderation/reject` | POST | Reject post | `post_id` (int, required), `note` (optional) | Moderate permission |
| `/moderation/queue` | GET | Get moderation queue | None | View queue permission |
| `/moderation/suspend-user` | POST | Suspend user | `user_id` (int, required), `days` (int, required), `reason` (optional) | Suspend permission |
| `/moderation/block-user` | POST | Block user | `user_id` (int, required), `reason` (optional) | Block permission |
| `/moderation/notify-user` | POST | Send notification to user | `user_id` (int, required), `message` (string, required) | Notify permission |

---

#### Unified Moderation Queue (class-moderation-queue-unified.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/moderation/unified-queue` | GET | Get unified moderation queue | `post_type` (optional), `source` (optional) | `view_moderation_queue` or `apollo_cena_moderate_events` or `moderate_apollo_content` or `manage_options` |
| `/moderation/pending-count` | GET | Get count of pending items | None | Same as above |

---

#### CENA-RIO Moderation (class-cena-rio-moderation.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/cena-rio/queue` | GET | Get CENA-RIO moderation queue | None | CENA-RIO moderation role |
| `/cena-rio/approve/{id}` | POST | Approve CENA-RIO event | `id` (int, path, required), `note` (optional) | CENA-RIO moderation role |
| `/cena-rio/reject/{id}` | POST | Reject CENA-RIO event | `id` (int, path, required), `reason` (optional) | CENA-RIO moderation role |

---

#### CENA-RIO Submissions (class-cena-rio-submissions.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/cena-rio/events` | GET | Get CENA-RIO internal events (for industry calendar) | None | CENA-RIO submission permission |
| `/cena-rio/submit` | POST | Submit new event (creates as 'expected') | `event_title` (required), `event_start_date` (required), `event_description`, `event_end_date`, `event_start_time`, `event_end_time`, `event_venue`, `event_lat`, `event_lng` | CENA-RIO submission permission |
| `/cena-rio/confirm/{id}` | POST | Confirm event → goes to MOD queue | `id` (int, path, required) | CENA-RIO submission permission |
| `/cena-rio/unconfirm/{id}` | POST | Unconfirm event (revert to expected) | `id` (int, path, required) | CENA-RIO submission permission |

---

#### Social Module (bootstrap.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/feed` | GET | Get unified feed (social posts + events) | `per_page` (default: 20) | Public |
| `/posts` | POST | Create social post | `content` (required) | Logged in |
| `/like` | POST | Toggle like on content | `content_id` (int, required), `content_type` (string, required) | Logged in |

---

#### Events Module (bootstrap.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/events` | GET | Get list of events | `per_page` (default: 10), `page` (default: 1) | Public |
| `/events/{id}` | GET | Get single event details | `id` (int, path, required) | Public |
| `/events` | POST | Create new event | `title` (required), `content` (optional) | Logged in |

---

#### Quiz Module (quiz/rest.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/quiz/attempt` | POST | Record a quiz attempt | `question_id` (int, required), `answers` (array, required), `form_type` (string, required) | Logged in or valid nonce |
| `/quiz/stats` | GET | Get quiz statistics | `question_id` (int, required), `form_type` (string, required) | `manage_options` |
| `/quiz/user-attempts` | GET | Get user attempts for a question | `question_id` (int, required) | Logged in |

---

#### Forms Module (forms/rest.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/forms/submit` | POST | Submit a form | `form_type` (required), `data` (object, required) | Public (validated internally) |
| `/forms/schema` | GET | Get form schema | `form_type` (required) | Public |

---

## Apollo Social

### Namespace: `apollo/v1`

#### API Documentation & Health

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/docs` | GET | Get API documentation | None | Public |
| `/health` | GET | Health check endpoint | None | Public |

---

#### Onboarding (OnboardingEndpoints.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/onboarding/options` | GET | Get onboarding options (industries, roles, memberships) | None | Logged in |
| `/onboarding/begin` | POST | Begin onboarding process | Onboarding form fields | Logged in |
| `/onboarding/complete` | POST | Complete onboarding process | Completion form fields | Logged in |
| `/onboarding/verify/request-dm` | POST | Request DM verification | None | Logged in |
| `/onboarding/verify/status` | GET | Get verification status | None | Logged in |
| `/onboarding/verify/confirm` | POST | Confirm verification (admin) | `user_id` (int, required) | Admin permission |
| `/onboarding/verify/cancel` | POST | Cancel verification (admin) | `user_id` (int, required), `reason` (optional) | Admin permission |
| `/onboarding/profile` | GET | Get user profile | None | Logged in |

---

#### Feed (FeedEndpoint.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/feed` | GET | Get unified feed | `page` (default: 1), `per_page` (default: 20), `type` (enum: all, user_post, event, ad, news) | Public |

---

#### Favorites (FavoritesEndpoint.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/favorites` | POST | Toggle favorite status | `content_id` (int, required), `content_type` (enum: event_listing, apollo_social_post, event_dj, event_local, required) | Logged in |
| `/favorites` | GET | Get user favorites | `content_type` (optional filter) | Logged in |
| `/favorites/{content_type}/{content_id}` | GET | Get favorite status for content | `content_type` (path), `content_id` (path) | Public |

---

#### Likes (LikesEndpoint.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/like` | POST | Toggle like on content | `content_type` (enum: apollo_social_post, event_listing, post, apollo_ad, required), `content_id` (int, required) | Logged in with `read` capability |
| `/like/{content_type}/{content_id}` | GET | Get like status | `content_type` (path), `content_id` (path) | Public (returns false if not logged in) |

---

#### Groups (RestRoutes.php & GroupsEndpoint.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/groups` | GET | Get list of groups | None | Public |
| `/groups` | POST | Create a group | `title` (required), `type` (enum: comunidade, nucleo, required), `description` (optional), `visibility` (enum: public, private, members_only) | Logged in |
| `/groups/{id}/join` | POST | Join a group | `id` (int, path) | Logged in |
| `/groups/{id}/invite` | POST | Invite user to group | `id` (int, path) | Logged in |
| `/groups/{id}/approve-invite` | POST | Approve group invite | `id` (int, path) | Logged in |
| `/groups/{id}/approve` | POST | Approve group (moderation) | `id` (int, path) | Moderator |
| `/groups/{id}/reject` | POST | Reject group (moderation) | `id` (int, path), `reason` (required) | Moderator |
| `/groups/{id}/resubmit` | POST | Resubmit group for review | `id` (int, path) | Owner |
| `/groups/{id}/status` | GET | Get group status | `id` (int, path) | Public |

---

#### Unions/Memberships (RestRoutes.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/unions` | GET | Get list of unions | None | Public |
| `/unions/{id}/toggle-badges` | POST | Toggle union badges | `id` (int, path) | Logged in |

---

#### Classifieds (RestRoutes.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/classifieds` | GET | Get classifieds list (WPAdverts) | `per_page`, `page`, `search` | Public |
| `/classifieds/{id}` | GET | Get single classified | `id` (int, path) | Public |
| `/classifieds` | POST | Create classified | Classified data | Logged in |

---

#### Users (RestRoutes.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/users/{id}` | GET | Get user profile | `id` (alphanumeric, path) | Public |

---

#### Documents (DocumentsEndpoint.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/documents` | GET | Get documents list | Collection params | Logged in |
| `/documents/{id}` | GET | Get single document | `id` (int, path) | Logged in |
| `/documents/{id}/export` | GET | Export document | `id` (int, path), `format` (enum: pdf, xlsx, csv) | Logged in |

---

#### CENA-RIO Events (CenaRioEventEndpoint.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/cena-rio/event` | POST | Create event as 'previsto' (draft) | `title` (required), `date` (required Y-m-d), `time`, `ticket_url`, `local_id`, `description` | CENA-RIO role |
| `/cena-rio/event/{id}/approve` | POST | Approve event | `id` (int, path) | MOD/ADMIN |

---

### Namespace: `apollo-docs/v1` (SignatureEndpoints.php)

#### Document Library

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/library/{library}` | GET | Get documents by library | `library` (enum: apollo, cenario, private, path), `status`, `type`, `search`, `page`, `per_page` | Authenticated |
| `/library/{library}/stats` | GET | Get library statistics | `library` (path) | Authenticated |
| `/document` | POST | Create new document | `library` (required), `title` (required), `type`, `content` | Authenticated |
| `/document/{file_id}` | GET | Get document | `file_id` (alphanumeric, path) | Authenticated |
| `/document/{file_id}` | PUT | Update document | `file_id` (path), `title`, `content`, `html_content` | Authenticated |
| `/document/{file_id}/finalize` | POST | Finalize document | `file_id` (path) | Authenticated |
| `/document/{file_id}/move` | POST | Move document to another library | `file_id` (path), `target_library` (required) | Authenticated |

#### Signatures

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/sign/certificate` | POST | Sign with ICP-Brasil certificate | `document_id` (int, required), `certificate` (required), `password` (required), `name`, `cpf` | Authenticated |
| `/sign/canvas` | POST | Sign with canvas (electronic) | `token` (required), `signature` (required), `name` (required), `cpf` (required), `email` | Public (token validated) |
| `/sign/request` | POST | Request signature from another person | `document_id` (int, required), `party` (required), `email` (required), `name` | Authenticated |

#### Verification (Public)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/verify/protocol/{code}` | GET | Verify by protocol code | `code` (path) | Public |
| `/verify/hash` | POST | Verify by document hash | `hash` (required) | Public |
| `/verify/file` | POST | Verify PDF file | File upload | Public |

#### Audit

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/audit/{file_id}` | GET | Get document audit log | `file_id` (path) | Authenticated |
| `/audit/{file_id}/report` | GET | Generate verification report | `file_id` (path) | Authenticated |
| `/protocol/generate` | POST | Generate protocol | `document_id` (int, required) | Authenticated |

#### Templates

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/templates` | GET | Get templates | `category` (optional) | Authenticated |
| `/templates/{file_id}/use` | POST | Create from template | `file_id` (path), `target_library` (default: private) | Authenticated |

---

### Namespace: `apollo-social/v1` (SignaturesRestController.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/documents/{id}/sign` | POST | Sign a document | `id` (int, path), signature data | Sign permission |
| `/documents/{id}/signatures` | GET | Get document signatures | `id` (int, path) | Read permission |
| `/documents/{id}/verify` | POST | Verify document signature | `id` (int, path) | Public |
| `/signatures/backends` | GET | Get available signature backends | None | Admin |
| `/signatures/backends/set` | POST | Set active backend | `backend` (required) | Admin |

---

### Namespace: `apollo-social/v1` (BuilderRestController.php)

#### Profile Builder

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/builder/layout` | GET | Get user layout | `user_id` (optional) | Logged in |
| `/builder/layout` | POST | Save user layout | `layout` (required), `user_id` (optional) | Logged in |
| `/builder/assets` | GET | Get asset catalogs (backgrounds + stickers) | None | Logged in |
| `/builder/background` | POST | Set background | `background_id` (required), `user_id` (optional) | Logged in |
| `/builder/stickers` | GET | Get stickers | `user_id` (optional) | Logged in |
| `/builder/stickers` | POST | Add sticker | `asset` (required), `x`, `y`, `scale`, `user_id` | Logged in |
| `/builder/stickers/{instance_id}` | PATCH | Update sticker | `instance_id` (path), `x`, `y`, `scale`, `rotation`, `z_index`, `user_id` | Logged in |
| `/builder/stickers/{instance_id}` | DELETE | Delete sticker | `instance_id` (path), `user_id` | Logged in |

---

## Apollo Events Manager

### Namespace: `apollo/v1`

#### Events (class-rest-api.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/events` | GET | Get list of events | `per_page`, `page`, `search`, `category`, `location`, `date_from`, `date_to` | Public |
| `/events/{id}` | GET | Get single event | `id` (int, path) | Public |
| `/categories` | GET | Get event categories | None | Public |
| `/locations` | GET | Get event locations | None | Public |
| `/my-events` | GET | Get current user's events | None | Logged in |

---

#### Analytics (admin-dashboard.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/analytics` | GET | Get analytics data | `event_id`, `start_date`, `end_date` | `view_apollo_event_stats` or `manage_options` |
| `/analytics` | POST | Record analytics event | `event_id` (int, required), `event_type` (default: pageview), `event_name` | Public |
| `/likes` | GET | Get likes data | None | `view_apollo_event_stats` or `manage_options` |
| `/likes` | POST | Like an event | `event_id` (int, required) | Public |
| `/technotes/{venue_id}` | GET | Get tech notes for venue | `venue_id` (int, path) | `view_apollo_event_stats` or `manage_options` |
| `/technotes/{venue_id}` | POST | Update tech notes for venue | `venue_id` (int, path) | `view_apollo_event_stats` or `manage_options` |

---

### Namespace: `apollo-events/v1`

#### Bookmarks (class-bookmarks.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/bookmarks` | GET | Get user bookmarks | `limit`, `offset` | Logged in |
| `/bookmarks/{id}` | POST | Toggle bookmark | `id` (int, path) | Logged in |

---

### Namespace: `wpem/v1`

#### Events Controller (wpem-rest-events-controller.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/events` | GET | Get events list | Collection params | View permission |
| `/events` | POST | Create event | Event data | Create permission |
| `/events/{id}` | GET | Get single event | `id` (int, path) | View permission |
| `/events/{id}` | PUT/PATCH | Update event | `id` (path), event data | Update permission |
| `/events/{id}` | DELETE | Delete event | `id` (path), `force` | Delete permission |
| `/events/batch` | PUT/PATCH | Batch update events | Batch data | Batch permission |
| `/events/fields` | GET | Get event fields schema | None | View permission |

---

#### Venues Controller (wpem-rest-venues-controller.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/venues` | GET | Get venues list | Collection params | View permission |
| `/venues` | POST | Create venue | Venue data | Create permission |
| `/venues/{id}` | GET | Get single venue | `id` (int, path) | View permission |
| `/venues/{id}` | PUT/PATCH | Update venue | `id` (path), venue data | Update permission |
| `/venues/{id}` | DELETE | Delete venue | `id` (path), `force` | Delete permission |
| `/venues/batch` | PUT/PATCH | Batch update venues | Batch data | Batch permission |

---

#### Organizers Controller (wpem-rest-organizers-controller.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/organizers` | GET | Get organizers list | Collection params | View permission |
| `/organizers` | POST | Create organizer | Organizer data | Create permission |
| `/organizers/{id}` | GET | Get single organizer | `id` (int, path) | View permission |
| `/organizers/{id}` | PUT/PATCH | Update organizer | `id` (path), organizer data | Update permission |
| `/organizers/{id}` | DELETE | Delete organizer | `id` (path) | Delete permission |

---

#### Ecosystem (wpem-rest-ecosystem-controller.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/ecosystem` | GET | Get ecosystem overview (plugins status) | None | Authorized user |

---

#### User Registered Events (wpem-rest-user-registered-events-controller.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/user-registered-events` | GET | Get events user is registered for | `user_id` (optional, defaults to auth user) | Authenticated |

---

#### App Branding (wpem-rest-app-branding.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/app-branding` | GET | Get app branding settings | None | Authorized user |

---

#### Authentication (wpem-rest-authentication.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/applogin` | POST | App login authentication | `username` (required), `password` (required) | Public |
| `/login` | POST | Login authentication | `username` (required), `password` (required) | Public |

---

### Namespace: `wpem` (Matchmaking)

#### Attendee Profile (wpem-rest-matchmaking-profile.php & wpem-rest-matchmaking-profile-controller.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/attendee-profile` | GET | Get matchmaking profile | `attendeeId` (optional), `user_id` (optional) | Authorized |
| `/attendee-profile` | PUT/PATCH | Update matchmaking profile | Profile data | Authorized |
| `/attendee-profile/update` | PUT/PATCH | Update matchmaking profile (alt) | `user_id` (required), profile data | Authorized |
| `/attendee-profile/search` | GET | Search/filter matchmaking users | `profession`, `company_name`, `country[]`, `city`, `experience`, `skills[]`, `interests[]`, `event_id`, `search`, `per_page`, `page` | Authorized |
| `/attendee-profile/filter` | GET/POST | Filter matchmaking users (alias) | Same as search | Authorized |
| `/upload-user-file` | POST | Upload user file (profile photo) | `user_id` (required), file | Authorized |

---

#### Matchmaking Settings (wpem-rest-matchmaking-settings-controller.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/matchmaking-settings` | GET | Get matchmaking settings | None | Authorized |

---

#### User Settings (wpem-rest-matchmaking-user-settings.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/matchmaking-attendee-settings` | GET | Get user matchmaking settings | `user_id`, `event_id` | Logged in |
| `/update-matchmaking-attendee-settings` | POST | Update user matchmaking settings | `user_id`, `enable_matchmaking`, `message_notification`, `meeting_request_mode`, `event_participation[]` | Logged in |

---

#### Profile Settings (wpem-rest-matchmaking-profile-controller.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/matchmaking-profile-settings` | GET | Get profile settings | `user_id`, `event_id` | Authorized |
| `/matchmaking-profile-settings` | POST | Update profile settings | `user_id`, `enable_matchmaking`, `message_notification`, `meeting_request_mode`, `event_participation[]` | Authorized |

---

#### Filter Users (wpem-rest-matchmaking-filter-users.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/filter-users` | POST | Filter matchmaking users | `profession`, `company_name`, `country[]`, `city`, `experience`, `skills[]`, `interests[]`, `event_id`, `user_id` (required), `search`, `per_page`, `page` | Logged in |

---

#### Meetings (wpem-rest-matchmaking-create-meetings.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/create-meeting` | POST | Create matchmaking meeting | `user_id` (required), `event_id` (required), `meeting_date` (required), `slot` (required), `meeting_participants[]` (required), `write_a_message` | Logged in |
| `/get-meetings` | POST | Get user meetings | User context | Logged in |
| `/cancel-meeting` | POST | Cancel meeting | `meeting_id` (required), `user_id` (required) | Logged in |
| `/update-meeting-status` | POST | Update meeting status | `meeting_id` (required), `user_id` (required), `status` (enum: 0, 1, required) | Logged in |
| `/get-availability-slots` | GET | Get available meeting slots | `user_id` (optional) | Logged in |
| `/update-availability-slots` | POST | Update availability slots | `availability_slots` (object, required), `available_for_meeting`, `user_id` | Logged in |
| `/common-availability-slots` | POST | Get common slots between users | `event_id` (required), `user_ids[]` (required), `date` (required, Y-m-d) | Logged in |

---

#### Meetings Controller (wpem-rest-matchmaking-meetings-controller.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/meetings` | GET | Get meetings list | Collection params | Authorized |
| `/meetings` | POST | Create meeting | Meeting data | Authorized |
| `/meetings/{id}` | GET | Get single meeting | `id` (int, path) | Authorized |
| `/meetings/{id}` | PUT/PATCH | Update meeting | `id` (path), meeting data | Authorized |
| `/meetings/{id}` | DELETE | Delete meeting | `id` (path), `force` | Authorized |
| `/meetings/{id}/participant-status` | PUT/PATCH | Update participant status | `id` (path), `status` (enum: -1, 0, 1) | Authorized |
| `/meetings/{id}/cancel` | PUT/PATCH | Cancel meeting | `id` (path) | Authorized |

---

#### Messages (wpem-rest-matchmaking-user-messages.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/send-message` | POST | Send matchmaking message | `senderId` (required), `receiverId` (required), `message`, `image` | Logged in |
| `/get-messages` | GET | Get messages between users | `senderId` (required), `receiverId` (required), `page`, `per_page` | Logged in |
| `/get-conversation-list` | GET | Get conversation list | `user_id` (required), `event_ids[]` (required), `paged`, `per_page` | Logged in |

---

#### Taxonomy (wpem-rest-matchmaking-get-texonomy.php)

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/taxonomy-list` | GET | Get taxonomy terms | `taxonomy` (required) | Authorized (matchmaking enabled) |

---

## Apollo Rio (PWA)

### Namespace: `wp/v2`

| Endpoint | Method | Purpose | Dependencies | Permission |
|----------|--------|---------|--------------|------------|
| `/web-app-manifest` | GET | Serve PWA manifest file | None | Public (no edit context) |

---

## Summary Statistics

| Plugin | Namespace Count | Endpoint Count |
|--------|-----------------|----------------|
| Apollo Core | 1 (apollo/v1) | ~35 endpoints |
| Apollo Social | 4 (apollo/v1, apollo-docs/v1, apollo-social/v1, apollo-events/v1) | ~55 endpoints |
| Apollo Events Manager | 4 (apollo/v1, apollo-events/v1, wpem/v1, wpem) | ~50 endpoints |
| Apollo Rio | 1 (wp/v2) | 1 endpoint |

**Total: ~141 REST API endpoints across the Apollo ecosystem**

---

## Authentication Notes

### Public Endpoints
- Health checks, feeds, event listings, document verification, manifest

### Logged In Required
- User profile operations, favorites, likes, social posts, bookmarks

### Role-Based Permissions
- **Moderators**: Content approval/rejection, user suspension
- **Admins**: Membership management, system settings
- **CENA-RIO Role**: Industry calendar and event submissions
- **WPEM Authorized**: Matchmaking functionality (requires API key)

### API Key Authentication (WPEM)
- Uses custom API key system via `wpem_rest_api_keys` table
- Endpoints check `wpem_check_authorized_user()` for authorization
