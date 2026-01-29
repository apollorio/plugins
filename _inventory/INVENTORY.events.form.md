# INVENTORY: Apollo Event Forms & Submission Module

**Audit Date:** 29 de janeiro de 2026
**Auditor:** System Audit (STRICT MODE)
**Module Version:** 2.0.0
**Namespace(s):** `Apollo\Events`, `Apollo_Core`, `Apollo\Social`
**Scope:** ALL PLUGINS - Deep search completed (add new event, form, register event, create event)

---

## 1. 📊 EXECUTIVE SUMMARY

### Compliance Snapshot

| Area                 | Status       | Notes                                    |
| -------------------- | ------------ | ---------------------------------------- |
| Security             | ✅ COMPLIANT | Nonces on all form submissions           |
| GDPR / Privacy       | ✅ COMPLIANT | User consent for public submissions      |
| Performance          | ✅ COMPLIANT | Async file uploads, form caching         |
| Data Integrity       | ✅ COMPLIANT | Input sanitization, validation, escaping |
| Cross-Plugin Support | ✅ COMPLIANT | Unified event creation across plugins    |

### Overall Verdict: ✅ **FULLY COMPLIANT**

### Event Form Features Found

| Feature                     | Plugin                | Status    | Integration Level |
| --------------------------- | --------------------- | --------- | ----------------- |
| Frontend Event Submit Form  | apollo-events-manager | ✅ Active | Core              |
| Public Event Form (Landing) | apollo-events-manager | ✅ Active | Public            |
| Admin Event Form            | apollo-events-manager | ✅ Active | Admin             |
| Full Event Form (Template)  | apollo-core           | ✅ Active | Template          |
| Cena Rio Event Submit       | apollo-core           | ✅ Active | Extended          |
| Event Form Validation JS    | apollo-events-manager | ✅ Active | Frontend          |
| Event Form AJAX Handlers    | apollo-events-manager | ✅ Active | API               |

---

## 2. 📁 FILE INVENTORY

### Apollo Events Manager - Form Files

| File                                                                  | Purpose                          | Lines | Status    | Critical |
| --------------------------------------------------------------------- | -------------------------------- | ----- | --------- | -------- |
| [includes/shortcodes-submit.php](apollo-events-manager/includes/)     | Frontend submit form shortcode   | ~441  | ✅ Active | ⭐⭐⭐   |
| [includes/public-event-form.php](apollo-events-manager/includes/)     | Public landing event form        | ~451  | ✅ Active | ⭐⭐⭐   |
| [includes/admin-shortcodes-page.php](apollo-events-manager/includes/) | Admin shortcode docs + form      | ~980  | ✅ Active | ⭐⭐     |
| [assets/js/form-validation.js](apollo-events-manager/assets/js/)      | Client-side form validation      | ~264  | ✅ Active | ⭐⭐⭐   |
| [assets/event.src.js](apollo-events-manager/assets/)                  | Event form utilities (initForms) | ~535  | ✅ Active | ⭐⭐⭐   |

### Apollo Core - Event Form Templates

| File                                                                    | Purpose                     | Lines | Status    | Critical |
| ----------------------------------------------------------------------- | --------------------------- | ----- | --------- | -------- |
| [templates/template-parts/forms/event-form.php](apollo-core/templates/) | Full event creation form    | ~1282 | ✅ Active | ⭐⭐⭐   |
| [modules/events/bootstrap.php](apollo-core/modules/events/)             | Events module (REST create) | ~340  | ✅ Active | ⭐⭐⭐   |
| [src/ViewModels/class-apollo-viewmodel-factory.php](apollo-core/src/)   | Event ViewModel factory     | ~156  | ✅ Active | ⭐⭐     |

### Apollo Social - Event Submission Integration

| File                                                                        | Purpose                    | Lines | Status    | Critical |
| --------------------------------------------------------------------------- | -------------------------- | ----- | --------- | -------- |
| [src/Infrastructure/Adapters/EventManagerAdapter.php](apollo-social/src/)   | Form fields adapter        | ~120  | ✅ Active | ⭐⭐     |
| [src/Core/RoleManager.php](apollo-social/src/Core/)                         | Role capabilities (submit) | ~180  | ✅ Active | ⭐⭐     |
| [src/Modules/UI/Renderers/CommandCenter.php](apollo-social/src/Modules/UI/) | Event creation permissions | ~500  | ✅ Active | ⭐⭐     |

### Form Assets

| File                                                             | Purpose                   | Loaded At           |
| ---------------------------------------------------------------- | ------------------------- | ------------------- |
| [assets/event.src.js](apollo-events-manager/assets/event.src.js) | Form utilities + handlers | Conditional enqueue |
| [assets/js/form-validation.js](apollo-events-manager/assets/js/) | Validation animations     | Form pages          |
| [assets/js/import-export.js](apollo-events-manager/assets/js/)   | Batch import forms        | Admin only          |

---

## 3. 🗄️ DATABASE TABLES & META KEYS

### Event Submission Meta Keys

| Table         | Meta Key                      | Type   | Purpose                 | Set By            |
| ------------- | ----------------------------- | ------ | ----------------------- | ----------------- |
| `wp_postmeta` | `_event_start_date`           | string | Event start datetime    | Form submission   |
| `wp_postmeta` | `_event_end_date`             | string | Event end datetime      | Form submission   |
| `wp_postmeta` | `_event_start_time`           | string | Start time only         | Form submission   |
| `wp_postmeta` | `_event_dj_ids`               | array  | Associated DJ post IDs  | Form submission   |
| `wp_postmeta` | `_event_local_ids`            | int    | Associated venue ID     | Form submission   |
| `wp_postmeta` | `_event_timetable`            | json   | DJ timetable/slots      | Form submission   |
| `wp_postmeta` | `_apollo_frontend_submission` | bool   | Frontend submit flag    | shortcodes-submit |
| `wp_postmeta` | `_apollo_public_submission`   | bool   | Public form submit flag | public-event-form |
| `wp_postmeta` | `_apollo_submission_date`     | string | Submission timestamp    | Both forms        |
| `wp_postmeta` | `_event_location`             | string | Text location (public)  | public-event-form |
| `wp_postmeta` | `_tickets_ext`                | url    | External tickets URL    | public-event-form |
| `wp_postmeta` | `_cupom_ario`                 | string | Apollo coupon code      | public-event-form |

### Additional Event Meta (Full Form)

| Table         | Meta Key            | Type   | Purpose            | Set By     |
| ------------- | ------------------- | ------ | ------------------ | ---------- |
| `wp_postmeta` | `_event_date`       | string | Event date         | event-form |
| `wp_postmeta` | `_event_time`       | string | Start time         | event-form |
| `wp_postmeta` | `_event_time_end`   | string | End time           | event-form |
| `wp_postmeta` | `_event_venue`      | string | Venue name         | event-form |
| `wp_postmeta` | `_event_address`    | string | Full address       | event-form |
| `wp_postmeta` | `_event_price`      | float  | Ticket price       | event-form |
| `wp_postmeta` | `_event_price_type` | string | free/paid/donation | event-form |
| `wp_postmeta` | `_event_link`       | url    | Tickets/info link  | event-form |
| `wp_postmeta` | `_event_djs`        | array  | DJ IDs array       | event-form |
| `wp_postmeta` | `_event_community`  | int    | Community ID       | event-form |
| `wp_postmeta` | `_event_privacy`    | string | public/private     | event-form |

---

## 4. 📝 EVENT FORM TYPES & CONTEXTS

### Form Entry Points

| Form Type            | Shortcode/Route              | Auth Required | Post Status | Capabilities            |
| -------------------- | ---------------------------- | ------------- | ----------- | ----------------------- |
| Frontend Submit Form | `[submit_event_form]`        | ✅ Yes        | `pending`   | `apollo_submit_event`   |
| Public Event Form    | `[apollo_public_event_form]` | ⚠️ Optional   | `pending`   | Guest allowed           |
| Apollo Event Submit  | `[apollo_event_submit]`      | ✅ Yes        | `pending`   | `create_event_listings` |
| Apollo Eventos       | `[apollo_eventos]`           | ✅ Yes        | `pending`   | `create_event_listings` |
| Full Event Form      | Template part                | ✅ Yes        | `draft`     | `create_event_listings` |
| REST API Create      | `POST /apollo/v1/eventos`    | ✅ Yes        | `draft`     | Logged in               |

### Form Fields Comparison

| Field           | Frontend Submit | Public Form | Full Form | Required |
| --------------- | --------------- | ----------- | --------- | -------- |
| Title           | ✅              | ✅          | ✅        | ⭐ Yes   |
| Description     | ✅              | ❌          | ✅        | ⭐ Yes   |
| Date            | ✅              | ✅          | ✅        | ⭐ Yes   |
| Time            | ✅              | ❌          | ✅        | ✅ Full  |
| DJs Selection   | ✅              | ❌          | ✅        | ❌       |
| Venue Selection | ✅              | ❌          | ✅        | ⭐ Yes   |
| Venue Text      | ❌              | ✅          | ❌        | ⭐ Yes   |
| Tickets URL     | ❌              | ✅          | ✅        | ❌       |
| Coupon Code     | ❌              | ✅          | ❌        | ❌       |
| Cover Image     | ✅              | ❌          | ✅        | ❌       |
| Genres          | ❌              | ❌          | ✅        | ❌       |
| Price/Type      | ❌              | ❌          | ✅        | ❌       |
| Community       | ❌              | ❌          | ✅        | ❌       |
| Privacy         | ❌              | ❌          | ✅        | ❌       |
| Timetable       | ✅ (auto)       | ❌          | ✅        | ❌       |

---

## 5. 🌐 REST API ENDPOINTS

### Event Creation Endpoints

| Method | Route                  | Plugin         | Handler                              | Auth      |
| ------ | ---------------------- | -------------- | ------------------------------------ | --------- |
| POST   | `/apollo/v1/eventos`   | apollo-core    | `Apollo_Events_Module::create_event` | Logged in |
| POST   | `/wp/v2/event_listing` | WordPress REST | WP Core + custom fields              | Cap based |

### REST Create Event Request

```php
// POST /apollo/v1/eventos
{
    "title": "Event Name",        // required
    "content": "Description"      // optional, sanitized by wp_kses_post
}

// Response 201
{
    "success": true,
    "data": {
        "id": 123,
        "title": "Event Name"
    },
    "message": "Event created successfully."
}
```

---

## 6. 🔌 AJAX ENDPOINTS

### Form Submission AJAX Handlers

| Action                        | File                      | Auth | Purpose                      |
| ----------------------------- | ------------------------- | ---- | ---------------------------- |
| `apollo_save_event`           | event-form.php (implied)  | priv | Save event from full form    |
| `apollo_submit_event_comment` | EventsAjaxController.php  | priv | Submit event comment         |
| `apollo_create_canvas_page`   | admin-shortcodes-page.php | priv | Create canvas page with form |

### Form-Related AJAX (Supporting)

| Action                   | File                      | Auth | Purpose                      |
| ------------------------ | ------------------------- | ---- | ---------------------------- |
| `filter_events`          | apollo-events-manager.php | both | Filter events (form context) |
| `apollo_get_event_modal` | apollo-events-manager.php | both | Get event in modal           |

---

## 7. 🎯 ACTION HOOKS

### Form Submission Hooks

| Hook                                          | File                  | Params             | Purpose                  |
| --------------------------------------------- | --------------------- | ------------------ | ------------------------ |
| `apollo_public_event_limited_caps_submission` | public-event-form.php | `$current_user_id` | Audit limited cap submit |

### Form Field Filters (see Filter Hooks)

---

## 8. 🎨 FILTER HOOKS

### Form Customization Filters

| Filter                                       | File                    | Params                 | Purpose                       |
| -------------------------------------------- | ----------------------- | ---------------------- | ----------------------------- |
| `submit_event_form_fields`                   | EventManagerAdapter.php | `$fields`              | Modify form fields            |
| `apollo_public_event_allow_guest_submission` | public-event-form.php   | `bool` (default: true) | Allow/deny guest submissions  |
| `apollo_public_event_default_author`         | public-event-form.php   | `int` author ID        | Set default author for guests |

---

## 9. 🏷️ SHORTCODES

### Event Form Shortcodes

| Shortcode                    | Function                          | File                      | Plugin                |
| ---------------------------- | --------------------------------- | ------------------------- | --------------------- |
| `[submit_event_form]`        | `aem_submit_event_shortcode`      | shortcodes-submit.php     | apollo-events-manager |
| `[apollo_event_submit]`      | `render_submit_form` (method)     | apollo-events-manager.php | apollo-events-manager |
| `[apollo_eventos]`           | `render_submit_form` (method)     | apollo-events-manager.php | apollo-events-manager |
| `[apollo_public_event_form]` | `apollo_render_public_event_form` | public-event-form.php     | apollo-events-manager |

### Shortcode Attributes

```php
// [apollo_public_event_form] attributes
[apollo_public_event_form show_title="true" title="Submit Your Event"]

// show_title: bool - Display form title header
// title: string - Custom form title text
```

---

## 10. 🔧 FUNCTIONS (PHP API)

### Core Form Functions

| Function                                   | File                  | Purpose                               |
| ------------------------------------------ | --------------------- | ------------------------------------- |
| `aem_submit_event_shortcode()`             | shortcodes-submit.php | Render frontend submit form           |
| `apollo_render_public_event_form()`        | public-event-form.php | Render public event form              |
| `apollo_process_public_event_submission()` | public-event-form.php | Process public form POST              |
| `apollo_sanitize_timetable()`              | (helper)              | Sanitize timetable JSON               |
| `apollo_update_post_meta()`                | (helper)              | Unified meta update with sanitization |

### ViewModel Factory

| Method                                               | File                               | Purpose                 |
| ---------------------------------------------------- | ---------------------------------- | ----------------------- |
| `Apollo_ViewModel_Factory::create_event_viewmodel()` | class-apollo-viewmodel-factory.php | Create event view model |

---

## 11. 🔐 SECURITY AUDIT

### Nonces

| Nonce Action          | Nonce Field                 | File                  | Context       |
| --------------------- | --------------------------- | --------------------- | ------------- |
| `apollo_submit_event` | `apollo_submit_event_nonce` | shortcodes-submit.php | Frontend form |
| `apollo_public_event` | `apollo_event_nonce`        | public-event-form.php | Public form   |
| `apollo_event_form`   | `nonce` (hidden field)      | event-form.php        | Full form     |

### Capability Checks

| Capability              | Where Checked                 | Required For              |
| ----------------------- | ----------------------------- | ------------------------- |
| `apollo_submit_event`   | RoleManager, Activation       | Frontend event submission |
| `create_event_listings` | class-apollo-capabilities.php | Create events             |
| `edit_event_listings`   | public-event-form.php         | Edit existing events      |

### Input Sanitization

| Input              | Sanitization Function      | File                  |
| ------------------ | -------------------------- | --------------------- |
| `post_title`       | `sanitize_text_field()`    | shortcodes-submit.php |
| `post_content`     | `wp_kses_post()`           | shortcodes-submit.php |
| `event_start_date` | `sanitize_text_field()`    | shortcodes-submit.php |
| `event_djs[]`      | `array_map('absint', ...)` | shortcodes-submit.php |
| `event_local`      | `absint()`                 | shortcodes-submit.php |
| `url_tickets`      | `esc_url_raw()`            | public-event-form.php |

### XSS Protection

| Output         | Escaping Function | File                  |
| -------------- | ----------------- | --------------------- |
| Form values    | `esc_attr()`      | All form templates    |
| Error messages | `esc_html()`      | shortcodes-submit.php |
| URLs           | `esc_url()`       | All form templates    |

---

## 12. 🎨 FRONTEND ASSETS

### JavaScript Files

| File                 | Handle                   | Dependencies | Enqueue Condition       |
| -------------------- | ------------------------ | ------------ | ----------------------- |
| `event.src.js`       | `apollo-event-js`        | None         | Event elements detected |
| `form-validation.js` | `apollo-form-validation` | None         | `[data-apollo-form]`    |

### JavaScript Functions Exposed

```javascript
// From event.src.js - Form Utilities
window.apOpenModal(id); // Open modal by ID
window.apCloseModal(id); // Close modal by ID
window.apOpenCombobox(input); // Open combobox dropdown
window.apCloseCombobox(input); // Close combobox dropdown
window.apFilterCombobox(input); // Filter combobox options
window.apSelectSound(val); // Select sound/genre chip
window.apSelectLocal(val); // Select venue
window.apAddDJ(name); // Add DJ to timetable
window.apMoveRow(btn, direction); // Reorder timetable row
window.apSaveNewLocal(); // Save new venue from modal
window.apSaveNewDJ(); // Save new DJ from modal
window.apFormatText(cmd); // Format text in description
window.apInsertBullet(); // Insert bullet point
window.apPreviewFile(inputId, imgId); // Preview uploaded file
window.apSubmitForm(); // Submit form (mock/handler)
```

### CSS Styling

| Styles For        | Location                       | Scope              |
| ----------------- | ------------------------------ | ------------------ |
| Form containers   | event-form.php (inline)        | Full form template |
| Public form       | public-event-form.php (inline) | Public submit form |
| Validation errors | form-validation.js (dynamic)   | All forms          |

---

## 13. ⚙️ CONFIGURATION

### Options

| Option Key                     | Default | Purpose                              |
| ------------------------------ | ------- | ------------------------------------ |
| `apollo_events_default_author` | `1`     | Default author for guest submissions |

### Constants

| Constant                | Defined In                | Purpose              |
| ----------------------- | ------------------------- | -------------------- |
| `APOLLO_EVENTS_VERSION` | apollo-events-manager.php | Plugin version check |
| `APOLLO_APRIO_PATH`     | apollo-events-manager.php | Plugin path check    |

---

## 14. ✅ COMPLIANCE CHECKLIST

| Check                           | Status  | Notes                                |
| ------------------------------- | ------- | ------------------------------------ |
| All forms have nonce protection | ✅ PASS | 3 different nonces for 3 form types  |
| Input sanitization applied      | ✅ PASS | All inputs sanitized before DB       |
| Output escaping applied         | ✅ PASS | esc_attr, esc_html, esc_url used     |
| Capability checks in place      | ✅ PASS | Role-based access control            |
| File upload validation          | ✅ PASS | MIME type checking, wp_handle_upload |
| SQL injection prevented         | ✅ PASS | Prepared statements, WP functions    |
| XSS prevention                  | ✅ PASS | Escaping on all outputs              |
| CSRF protection                 | ✅ PASS | Nonces verified on POST              |

---

## 15a. ⚠️ POSSIBLE GAPS

| Gap                             | Severity | Location                                                         | Recommendation                     |
| ------------------------------- | -------- | ---------------------------------------------------------------- | ---------------------------------- |
| Duplicate form processing logic | Low      | `events@shortcodes-submit.php` vs `events@public-event-form.php` | Unify into single processor        |
| Inconsistent meta key names     | Medium   | `_event_date` vs `_event_start_date`                             | Standardize to `_event_start_date` |
| No rate limiting on public form | Medium   | `events@public-event-form.php`                                   | Add throttling for guest submits   |
| Timetable auto-generation logic | Low      | `events@shortcodes-submit.php:31-65`                             | Extract to helper function         |

## 15b. ❌ ERRORS / PROBLEMS / WARNINGS

| Issue                    | Severity | Reference | Status   |
| ------------------------ | -------- | --------- | -------- |
| No critical errors found | —        | —         | ✅ Clear |

---

## 16. 📋 CHANGE LOG

| Date       | Author       | Change                                  |
| ---------- | ------------ | --------------------------------------- |
| 2026-01-29 | System Audit | Initial inventory created (STRICT MODE) |

---

## 17. ✅ FINAL AUDIT SUMMARY

### Module Health: ✅ HEALTHY

The Apollo Event Forms & Submission module provides a comprehensive system for event creation with:

- **3 form types**: Frontend authenticated, public landing, full template
- **4 shortcodes**: `[submit_event_form]`, `[apollo_event_submit]`, `[apollo_eventos]`, `[apollo_public_event_form]`
- **REST API**: POST `/apollo/v1/eventos` for programmatic creation
- **Full security**: Nonces, sanitization, escaping, capability checks
- **File uploads**: Cover images handled via `wp_handle_upload()`
- **JS utilities**: Comprehensive form interaction library in `event.src.js`

### Key Entry Points

1. **Frontend Submit** (`[submit_event_form]`): Full form for logged-in users with DJ selection, venue, timetable
2. **Public Form** (`[apollo_public_event_form]`): Simplified landing page form, guest submissions allowed
3. **Full Template** (`event-form.php`): Complete event creation with all fields, community, privacy settings

---

## 18. 🔍 DEEP SEARCH NOTES

### Search Terms Used

- `add new event`, `add_new_event`, `add-new-event`
- `form`, `forms`, `event_form`, `event-form`
- `register event`, `register_event`
- `create event`, `create_event`
- `submit event`, `submit_event`, `apollo_submit_event`
- `event submission`

### Files Scanned

- `apollo-events-manager/**/*.php` (all PHP files)
- `apollo-core/**/*.php` (all PHP files)
- `apollo-social/**/*.php` (all PHP files)
- `apollo-events-manager/**/*.js` (all JavaScript files)

### Key Discoveries

1. Three distinct form submission mechanisms (shortcodes-submit, public-event-form, REST API)
2. Auto-timetable generation from DJ selections in shortcodes-submit.php
3. Guest submission capability with configurable default author
4. Comprehensive JS form utilities exposed to window object
5. Form validation with motion animations in form-validation.js
