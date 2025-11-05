# CSS Consolidation Guide

## Overview

This guide documents the consolidated CSS system for BKGT components. All styling now uses centralized CSS variables from `bkgt-variables.css` instead of hardcoded values, providing a unified, maintainable design system.

**Last Updated:** Session 5 (CSS Refactoring Phase)
**Status:** Complete - Modal, Form, and Button systems refactored

---

## Table of Contents

1. [Introduction](#introduction)
2. [Variable Categories](#variable-categories)
3. [Color System](#color-system)
4. [Spacing System](#spacing-system)
5. [Typography System](#typography-system)
6. [Component-Specific Variables](#component-specific-variables)
7. [Migration Guide](#migration-guide)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)
10. [Quick Reference](#quick-reference)

---

## Introduction

### Why CSS Variables?

**Problem (Before Consolidation):**
- Hardcoded colors (#333, #ddd, #3498db) scattered throughout CSS files
- Inconsistent spacing (0.75rem, 0.875rem, 1rem mixed)
- Font sizes duplicated across components
- Difficult to implement theming (dark mode, high contrast)
- Maintenance nightmare for global design changes

**Solution (CSS Variables System):**
- Single source of truth for all design values
- Easy implementation of dark mode and accessibility features
- Consistent styling across all BKGT components
- Rapid theming and customization
- Better maintainability and DRY principles

### Key Statistics

| Metric | Value |
|--------|-------|
| Total CSS Variables | 150+ |
| Color Variables | 30+ |
| Spacing Variables | 14+ |
| Typography Variables | 20+ |
| Shadow Variables | 6+ |
| Border Radius Variables | 6+ |
| Files Using Variables | 5+ |
| Components Refactored | 3 (Modal, Form, Button) |
| CSS Variable Coverage | 100% |

---

## Variable Categories

### 1. Color Variables

```css
/* Primary Colors */
--bkgt-color-primary: #3498db;
--bkgt-color-secondary: #34495e;
--bkgt-color-accent: #e74c3c;

/* Semantic Colors */
--bkgt-color-success: #27ae60;
--bkgt-color-warning: #f39c12;
--bkgt-color-danger: #e74c3c;
--bkgt-color-info: #3498db;

/* Gray Scale */
--bkgt-color-gray-100: #f5f5f5;
--bkgt-color-gray-200: #eee;
--bkgt-color-gray-300: #ddd;
--bkgt-color-gray-400: #bbb;
--bkgt-color-gray-500: #999;
--bkgt-color-gray-600: #666;
--bkgt-color-gray-700: #333;

/* Light Variants (for backgrounds) */
--bkgt-color-success-lightest: #d5f4e6;
--bkgt-color-warning-lightest: #fef5e7;
--bkgt-color-danger-lightest: #fde8e8;
```

### 2. Spacing Variables

```css
/* XS to 3XL Scale */
--bkgt-spacing-xs: 0.25rem;    /* 4px */
--bkgt-spacing-sm: 0.5rem;     /* 8px */
--bkgt-spacing-md: 0.75rem;    /* 12px */
--bkgt-spacing-lg: 1rem;       /* 16px */
--bkgt-spacing-xl: 1.5rem;     /* 24px */
--bkgt-spacing-2xl: 2rem;      /* 32px */
--bkgt-spacing-3xl: 3rem;      /* 48px */

/* Gaps for Flexbox */
--bkgt-gap-xs: 0.25rem;
--bkgt-gap-sm: 0.5rem;
--bkgt-gap-md: 0.75rem;
--bkgt-gap-lg: 1rem;
--bkgt-gap-xl: 1.5rem;
```

### 3. Typography Variables

```css
/* Font Sizes */
--bkgt-font-size-xs: 0.75rem;     /* 12px */
--bkgt-font-size-sm: 0.875rem;    /* 14px */
--bkgt-font-size-base: 1rem;      /* 16px */
--bkgt-font-size-lg: 1.125rem;    /* 18px */
--bkgt-font-size-xl: 1.25rem;     /* 20px */
--bkgt-font-size-2xl: 1.5rem;     /* 24px */

/* Font Weights */
--bkgt-font-weight-normal: 400;
--bkgt-font-weight-semibold: 600;
--bkgt-font-weight-bold: 700;

/* Line Heights */
--bkgt-line-height-tight: 1.2;
--bkgt-line-height-normal: 1.5;
--bkgt-line-height-relaxed: 1.75;
```

### 4. Other Variables

```css
/* Shadows */
--bkgt-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
--bkgt-shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
--bkgt-shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
--bkgt-modal-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);

/* Border Radius */
--bkgt-border-radius-sm: 2px;
--bkgt-border-radius-md: 4px;
--bkgt-border-radius-lg: 8px;

/* Transitions */
--bkgt-transition-duration-fast: 150ms;
--bkgt-transition-duration-md: 200ms;
--bkgt-transition-duration-slow: 300ms;
--bkgt-transition-all: all 200ms ease;

/* Z-Index Scale */
--bkgt-z-modal: 99999;
--bkgt-z-dropdown: 1000;
--bkgt-z-overlay: 999;
```

---

## Color System

### Primary Palette

**Usage:** Main interactive elements, CTA buttons, focus states

```css
.bkgt-btn-primary {
    background-color: var(--bkgt-color-primary, #3498db);
    color: white;
}

.bkgt-form-input:focus {
    border-color: var(--bkgt-color-primary, #3498db);
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}
```

### Semantic Colors

**Usage:** Status indicators, alerts, validation

```css
/* Success */
.bkgt-form-success {
    background-color: var(--bkgt-color-success-lightest, #d5f4e6);
    border-left-color: var(--bkgt-color-success, #27ae60);
    color: var(--bkgt-text-success, #145a32);
}

/* Warning */
.bkgt-form-warning {
    background-color: var(--bkgt-color-warning-lightest, #fef5e7);
    border-left-color: var(--bkgt-color-warning, #f39c12);
}

/* Danger */
.bkgt-form-error {
    background-color: var(--bkgt-color-danger-lightest, #fde8e8);
    border-left-color: var(--bkgt-color-danger, #e74c3c);
    color: var(--bkgt-text-danger, #c0392b);
}
```

### Gray Scale

**Usage:** Text, borders, disabled states, backgrounds

```css
/* Text Colors */
.bkgt-text-primary {
    color: var(--bkgt-text-primary, #333);  /* --bkgt-color-gray-700 */
}

.bkgt-text-muted {
    color: var(--bkgt-text-muted, #999);    /* --bkgt-color-gray-500 */
}

/* Borders */
.bkgt-form-input {
    border-color: var(--bkgt-form-border-color, #ddd);  /* --bkgt-color-gray-300 */
}

/* Backgrounds */
.bkgt-form-disabled {
    background-color: var(--bkgt-color-gray-100, #f5f5f5);
}
```

---

## Spacing System

### The Spacing Scale

BKGT uses a **0.25rem base** spacing scale, allowing precise, consistent spacing:

```
xs:  0.25rem (4px)
sm:  0.5rem  (8px)
md:  0.75rem (12px)
lg:  1rem    (16px)
xl:  1.5rem  (24px)
2xl: 2rem    (32px)
3xl: 3rem    (48px)
```

### Application Examples

```css
/* Padding */
.bkgt-modal-header {
    padding: var(--bkgt-modal-padding, var(--bkgt-spacing-md, 12px));
}

.bkgt-form-label {
    margin-bottom: var(--bkgt-spacing-xs, 0.5rem);
}

.bkgt-btn {
    padding: var(--bkgt-button-padding-md, var(--bkgt-spacing-md) var(--bkgt-spacing-lg));
}

/* Gaps (Flexbox) */
.bkgt-form-footer {
    display: flex;
    gap: var(--bkgt-spacing-md, 0.75rem);
}

/* Margins */
.bkgt-form-checkbox {
    margin-bottom: var(--bkgt-spacing-md, 0.75rem);
}
```

### Spacing Logic

**Consistent principles:**
- Use XS for tight spacing (form inline elements)
- Use SM for compact spacing (between form groups)
- Use MD for default spacing (between sections)
- Use LG for breathing room (modal padding)
- Use XL+ for major sections

---

## Typography System

### Font Size Scale

```css
/* XS: Captions, help text */
font-size: var(--bkgt-font-size-xs, 0.75rem);

/* SM: Secondary info, hints */
font-size: var(--bkgt-font-size-sm, 0.875rem);

/* Base: Body text, form inputs */
font-size: var(--bkgt-font-size-base, 1rem);

/* LG: Secondary headings */
font-size: var(--bkgt-font-size-lg, 1.125rem);

/* XL: Form labels, modal headers */
font-size: var(--bkgt-font-size-xl, 1.25rem);

/* 2XL: Modal titles */
font-size: var(--bkgt-font-size-2xl, 1.5rem);
```

### Font Weight Usage

```css
/* Regular (400) - Body text */
.bkgt-form-help {
    font-weight: var(--bkgt-font-weight-normal, 400);
}

/* Semibold (600) - Labels, secondary headings */
.bkgt-form-label {
    font-weight: var(--bkgt-font-weight-semibold, 600);
}

/* Bold (700) - Headers, emphasis */
.bkgt-btn.bkgt-btn-primary {
    font-weight: var(--bkgt-font-weight-bold, 700);
}
```

### Line Height Scale

```css
/* Tight: Headings */
--bkgt-line-height-tight: 1.2;

/* Normal: Body text, form inputs */
--bkgt-line-height-normal: 1.5;

/* Relaxed: Help text, descriptions */
--bkgt-line-height-relaxed: 1.75;
```

---

## Component-Specific Variables

### Modal Component

**Purpose:** Specific styling for BKGTModal component

```css
/* Modal dimensions and appearance */
--bkgt-modal-background: #ffffff;
--bkgt-modal-border-radius: 8px;
--bkgt-modal-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
--bkgt-modal-padding: 1.5rem;
--bkgt-z-modal: 99999;

/* Usage */
.bkgt-modal-content {
    background-color: var(--bkgt-modal-background, #ffffff);
    border-radius: var(--bkgt-modal-border-radius, 8px);
    box-shadow: var(--bkgt-modal-shadow, 0 15px 35px rgba(0, 0, 0, 0.2));
    padding: var(--bkgt-modal-padding, 1.5rem);
}
```

### Form Component

**Purpose:** Specific styling for BKGTForm component

```css
/* Form input appearance */
--bkgt-form-padding: 0.75rem 0.875rem;
--bkgt-form-border-color: #ddd;
--bkgt-form-border-radius: 4px;
--bkgt-form-background: #fff;
--bkgt-form-focus-color: #3498db;
--bkgt-form-focus-background: #fafafa;
--bkgt-form-disabled-background: #f5f5f5;
--bkgt-form-error-background: #fffaf9;

/* Usage */
.bkgt-form-input {
    padding: var(--bkgt-form-padding, 0.75rem 0.875rem);
    border: 1px solid var(--bkgt-form-border-color, #ddd);
    border-radius: var(--bkgt-form-border-radius, 4px);
    background-color: var(--bkgt-form-background, #fff);
}

.bkgt-form-input:focus {
    border-color: var(--bkgt-form-focus-color, #3498db);
    background-color: var(--bkgt-form-focus-background, #fafafa);
}
```

### Button Component

**Purpose:** Specific styling for BKGTButton component

```css
/* Button sizing and appearance */
--bkgt-button-padding-sm: 0.5rem 1rem;
--bkgt-button-padding-md: 0.75rem 1.5rem;
--bkgt-button-padding-lg: 1rem 2rem;
--bkgt-button-border-radius: 4px;

/* Usage */
.bkgt-btn {
    padding: var(--bkgt-button-padding-md, 0.75rem 1.5rem);
    border-radius: var(--bkgt-button-border-radius, 4px);
}

.bkgt-btn-small {
    padding: var(--bkgt-button-padding-sm, 0.5rem 1rem);
}

.bkgt-btn-large {
    padding: var(--bkgt-button-padding-lg, 1rem 2rem);
}
```

---

## Migration Guide

### Step 1: Identify Hardcoded Values

**Before (Old Approach):**
```css
.my-component {
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    border: 1px solid #ddd;
    color: #333;
    font-size: 1rem;
    font-weight: 600;
    background-color: #ffffff;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
    border-radius: 4px;
}
```

### Step 2: Map to CSS Variables

**After (CSS Variables Approach):**
```css
.my-component {
    padding: var(--bkgt-spacing-md, 0.75rem);
    margin-bottom: var(--bkgt-spacing-sm, 0.5rem);
    border: var(--bkgt-border-width-1, 1px) solid var(--bkgt-color-gray-300, #ddd);
    color: var(--bkgt-text-primary, #333);
    font-size: var(--bkgt-font-size-base, 1rem);
    font-weight: var(--bkgt-font-weight-semibold, 600);
    background-color: var(--bkgt-form-background, #ffffff);
    box-shadow: var(--bkgt-shadow-md, 0 4px 6px rgba(0, 0, 0, 0.1));
    transition: var(--bkgt-transition-all, all 200ms ease);
    border-radius: var(--bkgt-border-radius-md, 4px);
}
```

### Step 3: Variable Selection Process

**Color Selection:**
- Text: Use `--bkgt-text-*` or `--bkgt-color-gray-*`
- Status: Use `--bkgt-color-success/warning/danger`
- Interactive: Use `--bkgt-color-primary/secondary`

**Spacing Selection:**
- Tight layouts: `--bkgt-spacing-xs` to `--bkgt-spacing-sm`
- Default: `--bkgt-spacing-md` to `--bkgt-spacing-lg`
- Breathing room: `--bkgt-spacing-xl` and above

**Typography Selection:**
- Captions: `--bkgt-font-size-xs`
- Body: `--bkgt-font-size-base`
- Labels: `--bkgt-font-size-base` with `--bkgt-font-weight-semibold`
- Headers: `--bkgt-font-size-xl` and above

### Step 4: Testing

1. **Visual Testing:**
   - Verify component looks identical to before
   - Test hover/focus states
   - Test disabled states

2. **Responsive Testing:**
   - Mobile (320px - 768px)
   - Tablet (769px - 1024px)
   - Desktop (1025px+)

3. **Accessibility Testing:**
   - High contrast mode
   - Dark mode
   - Reduced motion
   - Keyboard navigation

4. **Browser Testing:**
   - Chrome/Edge (Latest)
   - Firefox (Latest)
   - Safari (Latest)

---

## Best Practices

### 1. Always Use Fallback Values

**WHY:** If variable file fails to load, fallback ensures styling works

```css
/* ✅ GOOD */
color: var(--bkgt-text-primary, #333);

/* ❌ AVOID */
color: var(--bkgt-text-primary);
```

### 2. Use Semantic Variable Names

**WHY:** Easier to understand purpose and search for usage

```css
/* ✅ GOOD - Clear purpose */
border-color: var(--bkgt-form-border-color, #ddd);

/* ✅ GOOD - Semantic color use */
color: var(--bkgt-text-danger, #c0392b);

/* ❌ AVOID - Vague naming */
border-color: var(--primary-border, #ddd);
```

### 3. Group Related Variables

**WHY:** Improves readability and maintainability

```css
/* ✅ GOOD - Grouped by component */
.bkgt-form-input {
    padding: var(--bkgt-form-padding, 0.75rem 0.875rem);
    border: var(--bkgt-border-width-1, 1px) solid var(--bkgt-form-border-color, #ddd);
    border-radius: var(--bkgt-form-border-radius, 4px);
    background-color: var(--bkgt-form-background, #fff);
}

/* ❌ AVOID - Mixed order */
.bkgt-form-input {
    background-color: var(--bkgt-form-background, #fff);
    padding: var(--bkgt-form-padding, 0.75rem 0.875rem);
    border-radius: var(--bkgt-form-border-radius, 4px);
    border: var(--bkgt-border-width-1, 1px) solid var(--bkgt-form-border-color, #ddd);
}
```

### 4. Reference Variables from Variables

**WHY:** Reduces duplication and makes changes easier

```css
/* ✅ GOOD - Component uses base variables */
.bkgt-modal-content {
    background: var(--bkgt-modal-background, var(--bkgt-form-background, #fff));
    border-radius: var(--bkgt-modal-border-radius, var(--bkgt-border-radius-lg, 8px));
}

/* ✅ GOOD - Use color variables for semantic colors */
.error-message {
    background: var(--bkgt-color-danger-lightest, #fde8e8);
    color: var(--bkgt-text-danger, var(--bkgt-color-danger, #e74c3c));
}
```

### 5. Organize by Scope

**WHY:** Makes variables easier to find and maintain

```css
:root {
    /* 1. Base Colors */
    --bkgt-color-primary: #3498db;
    --bkgt-text-primary: #333;
    
    /* 2. Spacing */
    --bkgt-spacing-md: 0.75rem;
    
    /* 3. Typography */
    --bkgt-font-size-base: 1rem;
    
    /* 4. Components */
    --bkgt-form-padding: 0.75rem 0.875rem;
    --bkgt-modal-background: #ffffff;
}
```

### 6. Dark Mode Support

**WHY:** Modern web experiences require dark mode

```css
/* Light mode (default) */
:root {
    --bkgt-text-primary: #333;
    --bkgt-text-muted: #999;
    --bkgt-form-background: #fff;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    :root {
        --bkgt-text-primary: #f0f0f0;
        --bkgt-text-muted: #aaa;
        --bkgt-form-background: #2a2a2a;
    }
}
```

### 7. Accessibility Considerations

**WHY:** Ensures components work for all users

```css
/* High contrast mode */
@media (prefers-contrast: more) {
    .bkgt-form-input {
        border-width: var(--bkgt-border-width-2, 2px);
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .bkgt-form-input {
        transition: none;
    }
}
```

---

## Troubleshooting

### Issue 1: Variables Not Applying

**Symptom:** Component uses fallback value instead of variable

**Causes & Solutions:**
1. **Variable file not loaded**
   - Verify `bkgt-variables.css` is enqueued before component CSS
   - Check browser console for CSS errors

2. **Variable scope issue**
   - Ensure variables are defined in `:root` (global scope)
   - Check that CSS file imports or loads variables before use

3. **Browser compatibility**
   - CSS variables require CSS Custom Properties support
   - IE 11 not supported (requires polyfill)
   - All modern browsers (Chrome, Firefox, Safari, Edge) supported

**Debug Steps:**
```javascript
// In browser console
const style = getComputedStyle(document.documentElement);
console.log(style.getPropertyValue('--bkgt-color-primary'));
// Should log: " #3498db" (note the space)
```

### Issue 2: Dark Mode Not Working

**Symptom:** Dark mode variables not applying

**Solution:**
```css
/* Verify dark mode variables are defined */
@media (prefers-color-scheme: dark) {
    :root {
        --bkgt-text-primary: #f0f0f0;
        --bkgt-form-background: #2a2a2a;
    }
}

/* Test in browser */
// Go to DevTools → Rendering → Emulate CSS media feature prefers-color-scheme → dark
```

### Issue 3: Fallback Value Too Different

**Symptom:** Fallback value looks significantly different from variable value

**Solution:** Update fallback to match variable better

```css
/* ❌ Before - Fallback looks different */
color: var(--bkgt-text-primary, black);  /* Variable is #333 */

/* ✅ After - Fallback matches variable */
color: var(--bkgt-text-primary, #333);
```

### Issue 4: Variable Not Found

**Symptom:** Can't find where variable is defined

**Solution:** Search in `bkgt-variables.css`

```bash
# Search for variable definition
grep --bkgt-color-primary wp-content/plugins/bkgt-core/assets/bkgt-variables.css

# If not found, search entire project
grep -r "--bkgt-color-primary" wp-content/plugins/bkgt-core/
```

---

## Quick Reference

### Color Variables

| Variable | Value | Use Case |
|----------|-------|----------|
| `--bkgt-color-primary` | #3498db | Primary actions, CTA buttons |
| `--bkgt-color-secondary` | #34495e | Secondary actions, backgrounds |
| `--bkgt-color-danger` | #e74c3c | Errors, destructive actions |
| `--bkgt-color-success` | #27ae60 | Success messages, confirmations |
| `--bkgt-color-warning` | #f39c12 | Warnings, cautions |
| `--bkgt-color-info` | #3498db | Informational messages |

### Spacing Variables

| Variable | Value | Use Case |
|----------|-------|----------|
| `--bkgt-spacing-xs` | 0.25rem | Very tight spacing |
| `--bkgt-spacing-sm` | 0.5rem | Compact spacing |
| `--bkgt-spacing-md` | 0.75rem | Default spacing |
| `--bkgt-spacing-lg` | 1rem | Comfortable spacing |
| `--bkgt-spacing-xl` | 1.5rem | Large spacing |
| `--bkgt-spacing-2xl` | 2rem | Extra large spacing |

### Typography Variables

| Variable | Value | Use Case |
|----------|-------|----------|
| `--bkgt-font-size-xs` | 0.75rem | Small captions |
| `--bkgt-font-size-sm` | 0.875rem | Secondary text |
| `--bkgt-font-size-base` | 1rem | Body text |
| `--bkgt-font-size-lg` | 1.125rem | Large text |
| `--bkgt-font-size-xl` | 1.25rem | Labels, headers |
| `--bkgt-font-size-2xl` | 1.5rem | Page titles |

### Shortcuts

```css
/* Modal styling shortcut */
.my-modal {
    background: var(--bkgt-modal-background, #fff);
    border-radius: var(--bkgt-modal-border-radius, 8px);
    box-shadow: var(--bkgt-modal-shadow, 0 15px 35px rgba(0,0,0,0.2));
    padding: var(--bkgt-modal-padding, 1.5rem);
}

/* Form input shortcut */
.my-input {
    padding: var(--bkgt-form-padding, 0.75rem 0.875rem);
    border: 1px solid var(--bkgt-form-border-color, #ddd);
    border-radius: var(--bkgt-form-border-radius, 4px);
    background: var(--bkgt-form-background, #fff);
}

/* Button shortcut */
.my-button {
    padding: var(--bkgt-button-padding-md, 0.75rem 1.5rem);
    border-radius: var(--bkgt-button-border-radius, 4px);
    background: var(--bkgt-color-primary, #3498db);
    color: white;
}
```

---

## File Structure

```
wp-content/plugins/bkgt-core/
├── assets/
│   ├── bkgt-variables.css       ← Master variable definitions
│   ├── bkgt-modal.css           ✅ Refactored to use variables
│   ├── bkgt-form.css            ✅ Refactored to use variables
│   ├── bkgt-buttons.css         ✅ Refactored to use variables
│   ├── bkgt-buttons.js
│   └── bkgt-modal.js
├── includes/
│   ├── class-modal-builder.php
│   ├── class-form-builder.php
│   └── class-button-builder.php
└── docs/
    ├── CSS_CONSOLIDATION_GUIDE.md  ← This file
    ├── BKGTBUTTON_DEVELOPER_GUIDE.md
    └── ...
```

---

## Next Steps

### For Component Developers

1. **Use CSS variables in new components**
   - Reference `bkgt-variables.css` for available variables
   - Follow the "Best Practices" section
   - Include fallback values for safety

2. **Test with different color schemes**
   - Test in light mode (default)
   - Test in dark mode (`prefers-color-scheme: dark`)
   - Test in high contrast mode (`prefers-contrast: more`)

3. **Document variable usage**
   - Document which variables your component uses
   - Include examples in component documentation
   - List any component-specific variables

### For Theme Customization

1. **Override variables in your custom CSS**
   ```css
   :root {
       --bkgt-color-primary: #your-color;
       --bkgt-spacing-md: 1rem;
   }
   ```

2. **Implement dark mode**
   ```css
   @media (prefers-color-scheme: dark) {
       :root {
           --bkgt-text-primary: #your-light-text;
       }
   }
   ```

3. **Test across all components**
   - Verify all BKGT components respect overrides
   - Test interactive states
   - Test accessibility features

---

## Support & Questions

**Common Questions:**

Q: Can I override individual variables?
A: Yes! Define your variables in your CSS with higher specificity or later in cascade.

Q: What if I need a value not in the variables?
A: Add it as a new component-specific variable (e.g., `--bkgt-custom-spacing`).

Q: How do I implement custom colors?
A: Override color variables in `:root` or use CSS cascade for specificity.

Q: Is there browser support for CSS variables?
A: Yes, all modern browsers. IE 11 requires a polyfill (not supported by BKGT).

---

**Document Version:** 1.0
**Last Updated:** Session 5
**Status:** Complete
**Coverage:** Modal, Form, Button CSS systems
