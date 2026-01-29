# QA Plan - apollo-events-manager

**Date:** 2025-01-13  
**Version:** 1.0.0  
**Target Environment:** PHP 8.3 + WordPress 6.4+

---

## Overview

This QA plan covers testing for the apollo-events-manager plugin after the CDN elimination and security hardening fixes.

---

## Pre-Test Requirements

### Environment Setup
1. WordPress 6.4+ with PHP 8.3
2. apollo-core plugin active (required dependency)
3. Test database with sample events, DJs, and venues
4. Debug logging enabled (`WP_DEBUG` and `WP_DEBUG_LOG`)

### Test User Accounts
| Role | Purpose |
|------|---------|
| Administrator | Full access testing |
| Event Manager | CPT management |
| DJ User | DJ profile access |
| Subscriber | Public user features |
| Logged-out | Anonymous access |

---

## Test Categories

### A. Asset Loading Tests

#### A1. Verify Local Asset Loading
| Test | Steps | Expected |
|------|-------|----------|
| A1.1 | Load any event page | Network tab shows NO CDN requests |
| A1.2 | Check uni.css source | Served from `/apollo-core/assets/core/uni.css` |
| A1.3 | Check remixicon source | Served from `/apollo-core/assets/vendor/remixicon/` |
| A1.4 | Check leaflet source | Served from `/apollo-core/assets/vendor/leaflet/` |
| A1.5 | Check Chart.js in admin | Served from `/apollo-core/assets/vendor/chartjs/` |

#### A2. Asset Dependencies
| Test | Steps | Expected |
|------|-------|----------|
| A2.1 | View source on event page | `wp_head()` includes remixicon before uni.css |
| A2.2 | Check script order | leaflet.js loads before map initialization |
| A2.3 | Admin dashboard | Chart.js and DataTables load correctly |

#### A3. Cache Busting
| Test | Steps | Expected |
|------|-------|----------|
| A3.1 | Check asset URLs | Contains `?ver=` with filemtime value |
| A3.2 | Modify uni.css | Version string updates on next load |

---

### B. Template Rendering Tests

#### B1. Single Event Templates
| Test | Template | Steps | Expected |
|------|----------|-------|----------|
| B1.1 | single-event_listing.php | View event | Full page renders, map displays |
| B1.2 | single-event_dj.php | View DJ profile | DJ photo placeholder works |
| B1.3 | single-event_local.php | View venue | Venue images display |
| B1.4 | single-event-standalone.php | Direct URL access | Standalone page renders |

#### B2. Archive Templates
| Test | Steps | Expected |
|------|-------|----------|
| B2.1 | Visit /eventos/ | Event archive displays |
| B2.2 | Filter by type | AJAX filtering works |
| B2.3 | Pagination | Navigate through pages |

#### B3. Shortcode Templates
| Test | Shortcode | Expected |
|------|-----------|----------|
| B3.1 | `[apollo_events]` | Events grid displays |
| B3.2 | `[apollo_cena_rio]` | CENA-RIO calendar displays |
| B3.3 | `[apollo_user_dashboard]` | User dashboard renders |
| B3.4 | `[apollo_dj_profile]` | DJ profile shortcode works |

---

### C. Admin Functionality Tests

#### C1. Dashboard
| Test | Steps | Expected |
|------|-------|----------|
| C1.1 | Navigate to Apollo Dashboard | Charts render with local Chart.js |
| C1.2 | Check statistics | Data tables load correctly |
| C1.3 | Export data | DataTables export works |

#### C2. Event Editor
| Test | Steps | Expected |
|------|-------|----------|
| C2.1 | Create new event | Metaboxes load with RemixIcon |
| C2.2 | Select venue | Venue picker modal works |
| C2.3 | Select DJs | DJ selector with motion animations |
| C2.4 | Upload banner | Media uploader functions |
| C2.5 | Set map location | Leaflet map picker works |

#### C3. Settings Pages
| Test | Steps | Expected |
|------|-------|----------|
| C3.1 | Open Shortcodes page | Placeholder list renders |
| C3.2 | View Meta Keys page | All meta keys documented |
| C3.3 | Statistics page | Charts display data |

---

### D. AJAX Functionality Tests

#### D1. Event Filtering
| Test | Steps | Expected |
|------|-------|----------|
| D1.1 | Filter events by type | `ajax_filter_events` returns filtered results |
| D1.2 | Search events | Results update dynamically |
| D1.3 | Date range filter | Events filtered by date |

#### D2. Quick Create
| Test | Steps | Expected |
|------|-------|----------|
| D2.1 | Quick create DJ from event editor | DJ created and selected |
| D2.2 | Quick create venue from event editor | Venue created and selected |

#### D3. User Interactions
| Test | Steps | Expected |
|------|-------|----------|
| D3.1 | Mark event as interested | REST API returns success |
| D3.2 | Toggle interest off | Interest removed |
| D3.3 | View interested users | Avatars display correctly |

---

### E. Map Functionality Tests

#### E1. Leaflet Map Display
| Test | Steps | Expected |
|------|-------|----------|
| E1.1 | View event with location | OSM map renders |
| E1.2 | Click marker | Popup shows venue info |
| E1.3 | Zoom controls | Map zoom works |
| E1.4 | Mobile view | Map is touch-friendly |

#### E2. Admin Map Picker
| Test | Steps | Expected |
|------|-------|----------|
| E2.1 | Edit venue location | Map picker modal opens |
| E2.2 | Click on map | Coordinates update |
| E2.3 | Search address | Geocoding returns result |

---

### F. Security Tests

#### F1. CSRF Protection
| Test | Steps | Expected |
|------|-------|----------|
| F1.1 | Submit form without nonce | Request rejected |
| F1.2 | Replay old nonce | Request rejected |
| F1.3 | Valid form submission | Request succeeds |

#### F2. Input Validation
| Test | Steps | Expected |
|------|-------|----------|
| F2.1 | Submit XSS in event title | Sanitized output |
| F2.2 | SQL injection in search | Query safely escaped |
| F2.3 | Invalid date format | Validation error shown |

---

### G. Performance Tests

#### G1. Page Load Times
| Test | Target | Metric |
|------|--------|--------|
| G1.1 | Event archive | < 2s TTFB |
| G1.2 | Single event | < 1.5s TTFB |
| G1.3 | Admin dashboard | < 3s full load |

#### G2. Asset Loading
| Test | Target | Metric |
|------|--------|--------|
| G2.1 | CSS total size | < 200KB |
| G2.2 | JS total size | < 500KB |
| G2.3 | HTTP requests | < 15 requests |

---

### H. Regression Tests

#### H1. Existing Functionality
| Test | Steps | Expected |
|------|-------|----------|
| H1.1 | Create event | Full workflow works |
| H1.2 | Edit event | All fields save correctly |
| H1.3 | Delete event | Event removed, no orphan data |
| H1.4 | Duplicate event | Copy created successfully |

#### H2. Third-Party Integration
| Test | Steps | Expected |
|------|-------|----------|
| H2.1 | WooCommerce tickets | Ticket links work |
| H2.2 | Social sharing | Meta tags correct |
| H2.3 | SEO plugins | Schema markup valid |

---

## Test Execution Checklist

### Phase 1: Smoke Tests
- [ ] A1.1 - Local asset loading
- [ ] B1.1 - Single event renders
- [ ] C1.1 - Admin dashboard loads
- [ ] E1.1 - Map displays

### Phase 2: Functional Tests
- [ ] All A tests (Asset Loading)
- [ ] All B tests (Templates)
- [ ] All C tests (Admin)
- [ ] All D tests (AJAX)
- [ ] All E tests (Maps)

### Phase 3: Security Tests
- [ ] All F tests (Security)

### Phase 4: Performance Tests
- [ ] All G tests (Performance)

### Phase 5: Regression Tests
- [ ] All H tests (Regression)

---

## Bug Reporting Template

```
**Bug ID:** APOLLO-[number]
**Severity:** Critical/High/Medium/Low
**Component:** [Template/Admin/AJAX/Map/Asset]
**Steps to Reproduce:**
1. 
2. 
3. 

**Expected Result:**

**Actual Result:**

**Screenshots/Logs:**

**Environment:**
- WordPress: 
- PHP: 
- Browser: 
```

---

## Sign-Off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| QA Lead | | | |
| Developer | | | |
| Product Owner | | | |

---

**Document Version:** 1.0.0  
**Last Updated:** 2025-01-13
