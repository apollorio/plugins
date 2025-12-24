# Apollo Visual Refactoring Progress

## Overview
Comprehensive refactoring of all Apollo plugin templates to match approved HTML designs, ensuring consistent visuals loaded from CDN assets (uni.css and base.js).

## Phase Status

### ✅ Phase 0: Guardrails + Definition of Done (COMPLETE)
- Created [FRONTEND_CONTRACT.md](FRONTEND_CONTRACT.md) - Non-negotiable constraints
- Created [APPROVED_VISUALS_DONE.md](APPROVED_VISUALS_DONE.md) - Acceptance checklist
- Established rules: approved templates as DOM source, no contract changes, uni.css-first loading

### ✅ Phase 1: Frontend Entry Points Inventory (COMPLETE)
- Scanned all apollo-* plugins for frontend render entrypoints
- Identified 11 shortcodes, 18+ templates, 4 template filters
- Created [FRONTEND_ENTRYPOINTS_MAP.json](FRONTEND_ENTRYPOINTS_MAP.json) - Complete mapping catalog
- Documented [PHASE1_COMPLETE.md](PHASE1_COMPLETE.md) - Detailed inventory results

### ✅ Phase 2: Extract Shared UI Partials (COMPLETE)
- Extracted 5 core UI components from approved templates
- Created reusable partials library: bottom-bar, hero-section, event-card, assets, header-nav
- Mapped all 32+ entrypoints to corresponding approved HTML templates
- Documented [PHASE2_COMPLETE.md](PHASE2_COMPLETE.md) - Shared partials and mapping results
- Updated [FRONTEND_ENTRYPOINTS_MAP.json](FRONTEND_ENTRYPOINTS_MAP.json) with complete mappings

### ✅ Phase 3: Create ViewModels + Data Transformation (COMPLETE)
- Created comprehensive ViewModel architecture (Base, Event, User, Social)
- Implemented data transformation layer maintaining all existing contracts
- Built asset loading system ensuring uni.css/base.js from CDN
- Created template loader with ViewModel integration
- Documented [PHASE3_COMPLETE.md](PHASE3_COMPLETE.md) - ViewModels and transformation layer
- Added autoloader for seamless class loading

### ✅ Phase 4: Migrate Golden Page (COMPLETE)
- Selected portal-discover.php as highest-traffic golden page
- Migrated to ViewModel architecture with shared partials
- Validated against acceptance checklist
- Established migration pattern for remaining templates
- Documented [PHASE4_COMPLETE.md](PHASE4_COMPLETE.md) - Golden page migration

### 🔄 Phase 5: Batch Rollout (NEXT)
- Systematically migrate all remaining templates
- Maintain backward compatibility during transition
- Comprehensive testing across all entrypoints

### 🔄 Phase 6: Tooltips & Polish (PENDING)
- Add contextual help and tooltips
- Final visual polish and consistency checks

### 🔄 Phase 7: Asset Unification (PENDING)
- Ensure all templates load from CDN
- Remove local CSS/JS conflicts
- Performance optimization

### 🔄 Phase 8: Regression Testing (PENDING)
- End-to-end testing across all plugins
- Performance validation
- Cross-browser compatibility

## Key Constraints
- **No contract changes**: CPT slugs, meta keys, hooks, capabilities remain unchanged
- **Approved templates as source**: DOM structure and classes must match exactly
- **CDN-first loading**: All templates must load https://assets.apollo.rio.br/uni.css and base.js
- **No SCSS nesting**: Flat CSS structure only

## Current Blockers
- Systematically migrate remaining 30+ templates using established pattern
- Ensure all templates load from CDN consistently

## Success Metrics
- All templates match approved designs exactly
- Zero visual regressions
- Consistent user experience across all plugins
- Performance maintained or improved

---
*Last updated: 2025-01-03 | Phase 4 Complete*
