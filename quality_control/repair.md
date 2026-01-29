# RECORDS OF ERRORS, WARNINGS, PROBLEMS AND MORE.

**RESPECT ORDER OF ALL REGISTERS WHERE EACH REGISTRY IS ONE LINE (ROW) AND COLUMNS ARE SEPARATED IN THIS ROW BY '|' OF SEQUENCE BELOW:**
1 = TIMESTAMP
02 = FIXED? (empty for no or YES if corrected)
03 = IDENTIFIED SITUATION (shorten of what is the deal that needs repairs)
04 = CRITICAL? (empty for no or YES if corrected)
05 = CONNECTED TO OTHER FEATURES OR FILES WORKABILITY? (empty for no or YES if corrected)
06 = PLUGIN (origin plugin)
07 = FOLERD(S) (where this origin and possible chaos is spread)
08 = FILE MAIN (ORIGIN) (origin of issued code to repair)
09 = FILES CONSEQUENCE IN TROUBLE (issues resulted by other file's issue)
10 = SUGGESTION TO REPAIR

# TABLE OF RECORDS BELOW:

| 2026-01-27 | YES | Potential Notification Duplication | | YES | apollo-core | N/A | EventNotificationHooks.php | N/A | False positive - Multi-channel intentional. Debouncing already implemented (300s transient) |
| 2026-01-27 | YES | Message Notification Dependency in Matchmaking | | YES | apollo-events-manager | modules/rest-api/includes | aprio-rest-matchmaking-user-messages.php | N/A | Fixed - Removed blocking condition. Messages sent regardless of notification preference |
| 2026-01-27 | YES | Legacy Notification Classes | | | apollo-core | N/A | class-notification-manager.php | N/A | False positive - Notification_Manager is current canonical system for frontend alerts |
| 2026-01-27 | YES | Apollo Rio Notification Gap | | | apollo-rio | N/A | N/A | N/A | Not an issue - Correct architecture (theme uses hooks, plugin handles notifications) |
| 2026-01-27 | YES | Private Email_Integration::send_email() called | | YES | apollo-events-manager | modules/rest-api/includes | aprio-rest-matchmaking-user-messages.php | Lines 277, 294 | Fixed - Changed to use Apollo_Core\Email_Service::instance()->send() directly |
| 2026-01-27 | YES | Undefined function get_aprio_user_profile_photo() | | YES | apollo-events-manager | modules/rest-api/includes | aprio-rest-matchmaking-user-messages.php | Line 567 | Fixed - Replaced with get_user_meta($partner_id, '\_profile_photo', true) with fallback |
| 2026-01-27 | | HTML Generation Task - Missing Core Rendering Logic | | YES | ALL (apollo-\*) | templates, assets | Multiple shortcode/template files | Rendering engine | WARN: Plugins use WP template system requiring runtime PHP execution. No standalone render method found. Generated HTMLs use mock data + identified patterns but cannot guarantee 100% accuracy without live WP environment |
