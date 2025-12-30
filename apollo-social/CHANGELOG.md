# Changelog

All notable changes to Apollo Social will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.3.0] - 2025-12-30

### Added
- **DiagnosticsCLI**: New WP-CLI commands for diagnostics (`wp apollo diag status|flags|routes|toggle`)
- **DiagnosticsAdmin**: Admin page for system diagnostics under Apollo Social menu
- **FeatureFlags::init()**: Explicit initialization method for fail-closed behavior
- **FeatureFlags::isInitialized()**: Check if feature flags system is properly initialized
- **SchemaModuleInterface::version()**: Version method added to schema interface
- **Nonces utility class**: Fully implemented nonce wrapper (`Apollo\Infrastructure\Security\Nonces`)
- **UPGRADE.md**: Comprehensive upgrade documentation

### Changed
- **Rewrite rules**: Removed runtime `flush_rewrite_rules()` from individual modules
  - `SuppliersModule::activate()` - delegated to Router
  - `CenaRioModule::activate()` - delegated to Router
  - `UserPagesServiceProvider::activate()` - delegated to Router
- **Apollo_Router**: Added `is_feed()` check to protect WordPress feed URLs
- **Schema modules**: Added VERSION constants to ChatSchema, LikesSchema, DocumentsSchema
- **Stub cleanup**: Improved documentation for placeholder classes (Groups\Hooks, MembershipsServiceProvider)

### Security
- **FeatureFlags fail-closed**: If not initialized, all features default to OFF
- **Endpoint verification**: Confirmed all REST and AJAX endpoints have proper nonce/capability checks

### Fixed
- Potential runtime crash from flush_rewrite_rules during page loads
- WordPress feed interference from Apollo routing

### Deprecated
- None

### Removed
- None (backward compatible release)

---

## [2.2.0] - 2024-XX-XX

### Added
- CoreSchema, ChatSchema, LikesSchema modular architecture
- DocumentsRepository with CPT as source of truth
- Signatures post_id column with backfill migration

---

## [2.1.0] - 2024-XX-XX

### Added
- Initial schema versioning system
- Apollo_Router centralized routing
- Feature flags system

---

## [2.0.0] - 2024-XX-XX

### Added
- Complete plugin rewrite with modular architecture
- Service provider pattern
- CLI commands infrastructure

---

*For upgrade instructions, see [UPGRADE.md](UPGRADE.md)*
