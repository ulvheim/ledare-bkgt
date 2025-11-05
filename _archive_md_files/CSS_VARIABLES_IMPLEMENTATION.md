# CSS Variables Implementation - Quick Win #2

**Status**: IN PROGRESS  
**Created**: 2024  
**Effort**: 4-6 hours  
**Priority**: HIGH

## Overview

CSS variables have been fully implemented in the design system. This document tracks the progressive replacement of hardcoded CSS values with design system variables throughout all plugin and theme files.

## Completed ✅

### Design System Foundation
- ✅ Created `/wp-content/themes/bkgt-ledare/assets/css/variables.css` (450+ lines)
  - 100+ CSS custom properties defined
  - Coverage: Colors, Typography, Spacing, Borders, Shadows, Transitions, Z-index
  - Accessibility: Dark mode, high contrast, reduced motion support
  - Responsive: Print media optimizations

- ✅ Updated `style.css` to import `variables.css`
  - Added `@import url('./assets/css/variables.css');` at the top
  - Variables now available throughout theme

## Implementation Checklist

### Phase 1: Plugin CSS Updates (Current - 4-6 hours)

Track the progressive replacement of hardcoded values with CSS variables.

#### [ ] BKGT Inventory Plugin
**Files:**
- [ ] `/wp-content/plugins/bkgt-inventory/assets/frontend.css` (395 lines)
- [ ] `/wp-content/plugins/bkgt-inventory/assets/admin.css` (TBD)

**Hardcoded Values Found:**
- Colors: `#e5e5e5`, `#23282d`, `#f0f0f0`, `#007cba`, `#005a87`, `#ddd`, `#f9f9f9`, `#fff`
- Spacing: `20px`, `30px`, `5px`, `10px`, `15px`, `8px`, `16px`
- Border-radius: `4px`, `8px`
- Font sizes: `14px`, `16px`

**Variable Mapping:**
- `#007cba` → `var(--color-primary)` 
- `#e5e5e5` → `var(--color-border)`
- `#f0f0f0` → `var(--color-bg-secondary)`
- `#fff` → `var(--color-bg-primary)`
- `20px` → `var(--spacing-lg)`
- `16px` → `var(--spacing-md)`
- `4px` → `var(--border-radius-sm)`

#### [ ] BKGT Document Management Plugin
**Files:**
- [ ] `/wp-content/plugins/bkgt-document-management/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-document-management/assets/css/admin.css`
- [ ] `/wp-content/plugins/bkgt-document-management/admin/css/admin.css`
- [ ] `/wp-content/plugins/bkgt-document-management/admin/css/template-builder.css`
- [ ] `/wp-content/plugins/bkgt-document-management/admin/css/smart-templates.css`
- [ ] `/wp-content/plugins/bkgt-document-management/admin/css/export-engine.css`

#### [ ] BKGT Team/Player Plugin
**Files:**
- [ ] `/wp-content/plugins/bkgt-team-player/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-team-player/assets/css/admin-dashboard.css`

#### [ ] BKGT Communication Plugin
**Files:**
- [ ] `/wp-content/plugins/bkgt-communication/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-communication/assets/css/admin.css`

#### [ ] BKGT Events Plugin
**Files:**
- [ ] `/wp-content/plugins/bkgt-events/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-events/assets/css/admin.css`

#### [ ] BKGT User Management Plugin
**Files:**
- [ ] `/wp-content/plugins/bkgt-user-management/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-user-management/assets/css/admin.css`

#### [ ] BKGT Core Plugin
**Files:**
- [ ] `/wp-content/plugins/bkgt-core/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-core/assets/css/admin.css`

#### [ ] BKGT Data Scraping Plugin
**Files:**
- [ ] `/wp-content/plugins/bkgt-data-scraping/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-data-scraping/admin/css/admin.css`

#### [ ] BKGT Offboarding Plugin
**Files:**
- [ ] `/wp-content/plugins/bkgt-offboarding/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-offboarding/assets/css/admin.css`

### Phase 2: Theme CSS Updates (After plugins)

#### [ ] Main Theme Stylesheet
**File:** `/wp-content/themes/bkgt-ledare/style.css` (1361 lines)
- Review and update theme-specific overrides
- Ensure variables take precedence where needed

## Standard Variable Replacements

### Colors

| Hardcoded | Variable | Usage |
|-----------|----------|-------|
| `#0056B3` | `var(--color-primary)` | Main buttons, links, active states |
| `#17A2B8` | `var(--color-secondary)` | Accent elements, secondary buttons |
| `#28A745` | `var(--color-success)` | Success messages, approved states |
| `#FFC107` | `var(--color-warning)` | Warnings, pending states |
| `#DC3545` | `var(--color-danger)` | Errors, critical states |
| `#1D2327` | `var(--color-text-primary)` | Main text |
| `#646970` | `var(--color-text-secondary)` | Secondary text, metadata |
| `#FFFFFF` | `var(--color-bg-primary)` | White backgrounds |
| `#F8F9FA` | `var(--color-bg-secondary)` | Light backgrounds, alternates |
| `#E1E5E9` | `var(--color-border)` | Borders, dividers |

### Spacing

| Hardcoded | Variable | Usage |
|-----------|----------|-------|
| `4px` | `var(--spacing-xs)` | Micro-spacing |
| `8px` | `var(--spacing-sm)` | Small gaps, compact layouts |
| `16px` | `var(--spacing-md)` | Standard padding, margins |
| `24px` | `var(--spacing-lg)` | Large sections |
| `32px` | `var(--spacing-xl)` | Extra large sections |

### Typography

| Hardcoded | Variable | Usage |
|-----------|----------|-------|
| `48px` | `var(--font-size-display)` | Display heading |
| `32px` | `var(--font-size-h1)` | Main headings |
| `24px` | `var(--font-size-h2)` | Secondary headings |
| `18px` | `var(--font-size-h3)` | Tertiary headings |
| `16px` | `var(--font-size-lg)` | Large body text |
| `14px` | `var(--font-size-body)` | Regular body text |
| `12px` | `var(--font-size-sm)` | Small text, metadata |
| `700` | `var(--font-weight-bold)` | Bold text |
| `600` | `var(--font-weight-semibold)` | Semibold text |
| `500` | `var(--font-weight-medium)` | Medium weight |
| `400` | `var(--font-weight-regular)` | Regular weight |

### Border Radius

| Hardcoded | Variable | Usage |
|-----------|----------|-------|
| `0px` | `var(--border-radius-none)` | Sharp corners |
| `4px` | `var(--border-radius-sm)` | Buttons, inputs |
| `6px` | `var(--border-radius-md)` | Cards, containers |
| `8px` | `var(--border-radius-lg)` | Modals, large containers |
| `50%` | `var(--border-radius-full)` | Avatars, badges |

### Shadows

| Hardcoded | Variable | Usage |
|-----------|----------|-------|
| `0 1px 2px rgba(0, 0, 0, 0.04)` | `var(--shadow-xs)` | Subtle shadow |
| `0 2px 4px rgba(0, 0, 0, 0.08)` | `var(--shadow-sm)` | Card shadow |
| `0 4px 12px rgba(0, 0, 0, 0.12)` | `var(--shadow-md)` | Floating element |
| `0 8px 24px rgba(0, 0, 0, 0.16)` | `var(--shadow-lg)` | Modal shadow |
| `0 12px 32px rgba(0, 0, 0, 0.20)` | `var(--shadow-xl)` | Maximum elevation |

### Transitions

| Hardcoded | Variable | Usage |
|-----------|----------|-------|
| `150ms ease-in-out` | `var(--transition-fast)` | Quick interactions |
| `200ms ease-in-out` | `var(--transition)` | Standard transitions |
| `300ms ease-in-out` | `var(--transition-slow)` | Slower animations |

## Implementation Pattern

### Before (Hardcoded)
```css
.button {
    padding: 8px 16px;
    background: #007cba;
    color: #fff;
    border-radius: 4px;
    font-size: 14px;
    border: 1px solid #e5e5e5;
    transition: all 0.2s ease;
}

.button:hover {
    background: #005a87;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}
```

### After (Variables)
```css
.button {
    padding: var(--button-padding-md);
    background: var(--color-primary);
    color: var(--color-text-inverted);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-body);
    border: var(--border-width) solid var(--color-border);
    transition: var(--transition);
}

.button:hover {
    background: var(--color-primary-dark);
    box-shadow: var(--shadow-sm);
}
```

## Benefits

✅ **Consistency**: Unified design system across entire platform  
✅ **Maintainability**: Update colors/spacing globally  
✅ **Theme Support**: Easy light/dark mode implementation  
✅ **Accessibility**: Built-in support for high contrast and reduced motion  
✅ **Performance**: Faster cascading style resolution  
✅ **Developer Experience**: Clear naming conventions  

## Progress Tracking

**Estimated Total Time**: 4-6 hours  
**Estimated Plugins**: 10 plugins + theme  
**Estimated CSS Files**: 20+ files  

### Effort Breakdown

| Phase | Task | Estimate |
|-------|------|----------|
| 1 | Review each CSS file for hardcoded values | 1-1.5 hours |
| 2 | Create variable replacement mapping | 0.5 hours |
| 3 | Update all plugin CSS files | 2-3 hours |
| 4 | Update theme CSS file | 0.5-1 hours |
| 5 | Visual testing across all pages | 1 hour |
| 6 | Update developer guide | 0.5 hours |

## Success Criteria

✅ All hardcoded colors replaced with `var(--color-*)` variables  
✅ All hardcoded spacing replaced with `var(--spacing-*)` variables  
✅ All hardcoded typography replaced with `var(--font-*)` variables  
✅ All hardcoded borders replaced with `var(--border-*)` variables  
✅ All hardcoded shadows replaced with `var(--shadow-*)` variables  
✅ All hardcoded transitions replaced with `var(--transition-*)` variables  
✅ Visual consistency verified across all pages  
✅ Dark mode works correctly with all updates  
✅ No visual regressions detected  
✅ CSS file size remains reasonable  

## Next Steps

1. ✅ Design system variables created and imported
2. ⏳ Begin updating plugin CSS files (starting with bkgt-inventory)
3. ⏳ Update theme style.css
4. ⏳ Verify visual consistency
5. ⏳ Create CSS variable usage guide for developers

## Related Documentation

- `/wp-content/themes/bkgt-ledare/assets/css/variables.css` - Complete variable definitions
- `DESIGN_SYSTEM.md` - Design system specifications
- `QUICK_WINS.md` - Quick win #2 details
- `/wp-content/themes/bkgt-ledare/style.css` - Theme stylesheet (updated with imports)

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2024 | Initial CSS variables implementation and plugin import setup |

---

**Note**: This is a living document that tracks CSS variables implementation progress. Update as changes are made.
