# CSS Variables Developer Guide

**For**: BKGT Ledare Developers  
**Version**: 1.0  
**Last Updated**: 2024  
**Status**: Active

---

## Quick Reference

### Using CSS Variables in Your Code

```css
/* Colors */
color: var(--color-primary);
background-color: var(--color-bg-secondary);
border-color: var(--color-border);

/* Spacing */
padding: var(--spacing-md);
margin: var(--spacing-lg);
gap: var(--spacing-sm);

/* Typography */
font-size: var(--font-size-body);
font-weight: var(--font-weight-semibold);
line-height: var(--line-height-normal);

/* Borders & Shadows */
border-radius: var(--border-radius-md);
box-shadow: var(--shadow-lg);
border: var(--border-width) solid var(--color-border);

/* Transitions */
transition: color var(--transition);
```

---

## Color Variables

### Primary & Secondary

| Variable | Value | Usage |
|----------|-------|-------|
| `--color-primary` | #0056B3 | Main brand color, primary buttons, links |
| `--color-primary-light` | #0070E0 | Hover states, lighter accents |
| `--color-primary-dark` | #003D82 | Active states, darker emphasis |
| `--color-primary-bg` | #F0F5FF | Primary button backgrounds, light fills |

### Secondary Teal

| Variable | Value | Usage |
|----------|-------|-------|
| `--color-secondary` | #17A2B8 | Accent elements, secondary buttons |
| `--color-secondary-light` | #20C9A6 | Hover states |
| `--color-secondary-dark` | #0E7C86 | Active states |
| `--color-secondary-bg` | #F0FFFE | Light fill backgrounds |

### Status Colors

**Success** (Positive/Completed)
```css
--color-success: #28A745;        /* Green */
--color-success-light: #D4EDDA;  /* Light green bg */
--color-success-dark: #1E8449;   /* Dark green */
--color-success-text: #155724;   /* Text color */
```

**Warning** (Caution/Attention)
```css
--color-warning: #FFC107;        /* Amber */
--color-warning-light: #FFF3CD;  /* Light amber bg */
--color-warning-dark: #E0A800;   /* Dark amber */
--color-warning-text: #856404;   /* Text color */
```

**Danger** (Critical/Error)
```css
--color-danger: #DC3545;         /* Red */
--color-danger-light: #F8D7DA;   /* Light red bg */
--color-danger-dark: #BD2130;    /* Dark red */
--color-danger-text: #721C24;    /* Text color */
```

**Information** (Informational)
```css
--color-info: #0C5FF4;           /* Blue */
--color-info-light: #CFE2FF;     /* Light blue bg */
--color-info-dark: #084298;      /* Dark blue */
--color-info-text: #084298;      /* Text color */
```

### Text & Background

| Variable | Color | Usage |
|----------|-------|-------|
| `--color-text-primary` | #1D2327 | Main text content, headings |
| `--color-text-secondary` | #646970 | Secondary text, metadata, timestamps |
| `--color-text-light` | #B5BACA | Disabled text, placeholders, muted |
| `--color-text-inverted` | #FFFFFF | Text on dark backgrounds |
| `--color-bg-primary` | #FFFFFF | Main background |
| `--color-bg-secondary` | #F8F9FA | Alternate sections |
| `--color-bg-tertiary` | #F1F3F5 | Subtle alternation |
| `--color-border` | #E1E5E9 | Standard borders |
| `--color-border-light` | #E9ECEF | Subtle borders |
| `--color-border-dark` | #ADB5BD | Emphasis borders |

### Interactions

| Variable | Color | Usage |
|----------|-------|-------|
| `--color-focus` | #0056B3 | Focus ring color |
| `--color-focus-ring` | rgba(0, 86, 179, 0.1) | Focus ring background |
| `--color-hover-overlay` | rgba(0, 0, 0, 0.05) | Hover background overlay |
| `--color-active` | #003D82 | Active/pressed state |

---

## Spacing Variables

### System: 4px Base Unit

| Variable | Size | Units | Common Usage |
|----------|------|-------|--------------|
| `--spacing-xs` | 4px | 1 unit | Micro spacing between elements |
| `--spacing-sm` | 8px | 2 units | Small gaps, compact layouts |
| `--spacing-md` | 16px | 4 units | **STANDARD** - buttons, form inputs |
| `--spacing-lg` | 24px | 6 units | Section spacing |
| `--spacing-xl` | 32px | 8 units | Large section spacing |
| `--spacing-2xl` | 48px | 12 units | Between major sections |
| `--spacing-3xl` | 64px | 16 units | Large gaps, full page spacing |

### Examples

```css
/* Button padding */
.button {
    padding: var(--spacing-sm) var(--spacing-md);  /* 8px 16px */
}

/* Card padding */
.card {
    padding: var(--spacing-lg);  /* 24px */
}

/* Form field spacing */
.form-group {
    margin-bottom: var(--spacing-md);  /* 16px */
}

/* Page margins */
.container {
    padding: var(--spacing-xl);  /* 32px */
}

/* Gap between items */
.list {
    gap: var(--spacing-sm);  /* 8px */
}
```

---

## Typography Variables

### Font Families

```css
--font-family-heading: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, ...
--font-family-body: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, ...
--font-family-monospace: SFMono-Regular, Menlo, Monaco, Consolas, ...
```

### Font Sizes (6-Point Scale)

| Variable | Size | Line Height | Weight | Usage |
|----------|------|-------------|--------|-------|
| `--font-size-display` | 48px | 72px | 700 | Large display heading |
| `--font-size-h1` | 32px | 48px | 700 | Main page heading |
| `--font-size-h2` | 24px | 36px | 600 | Section heading |
| `--font-size-h3` | 18px | 27px | 600 | Subsection heading |
| `--font-size-lg` | 16px | 24px | 400 | Large body text, emphasis |
| `--font-size-body` | 14px | 21px | 400 | **STANDARD** - main content |
| `--font-size-sm` | 12px | 18px | 400 | Small text, metadata, labels |
| `--font-size-code` | 13px | - | 400 | Code blocks, monospace |

### Font Weights

| Variable | Weight | Usage |
|----------|--------|-------|
| `--font-weight-thin` | 100 | Rarely used |
| `--font-weight-light` | 300 | Delicate text |
| `--font-weight-regular` | 400 | Body text (DEFAULT) |
| `--font-weight-medium` | 500 | Slightly emphasized |
| `--font-weight-semibold` | 600 | Headings, strong emphasis |
| `--font-weight-bold` | 700 | Important text, display |
| `--font-weight-extrabold` | 800 | Maximum emphasis |

### Line Height

| Variable | Value | Usage |
|----------|-------|-------|
| `--line-height-tight` | 1.2 | Headings, compact text |
| `--line-height-normal` | 1.5 | **STANDARD** - body text |
| `--line-height-relaxed` | 1.75 | Long-form content, accessibility |

### Letter Spacing

| Variable | Value | Usage |
|----------|-------|-------|
| `--letter-spacing-tight` | -0.5px | Headings, tight text |
| `--letter-spacing-normal` | 0px | **STANDARD** - most text |
| `--letter-spacing-wide` | 0.5px | Emphasis, special text |

### Examples

```css
/* Main heading */
h1 {
    font-family: var(--font-family-heading);
    font-size: var(--font-size-h1);
    font-weight: var(--font-weight-bold);
    line-height: var(--line-height-tight);
}

/* Body text */
p {
    font-family: var(--font-family-body);
    font-size: var(--font-size-body);
    font-weight: var(--font-weight-regular);
    line-height: var(--line-height-normal);
}

/* Code blocks */
code {
    font-family: var(--font-family-monospace);
    font-size: var(--font-size-code);
}

/* Labels */
label {
    font-weight: var(--font-weight-semibold);
    font-size: var(--font-size-body);
}
```

---

## Border & Shadow Variables

### Border Radius

| Variable | Size | Usage |
|----------|------|-------|
| `--border-radius-none` | 0px | Sharp corners |
| `--border-radius-sm` | 4px | Buttons, small inputs |
| `--border-radius-md` | 6px | Cards, containers |
| `--border-radius-lg` | 8px | Modals, large containers |
| `--border-radius-full` | 50% | Avatars, circular elements, badges |

### Shadow Elevations

| Variable | Shadow | Usage |
|----------|--------|-------|
| `--shadow-xs` | 0 1px 2px rgba(...) | Subtle elevation |
| `--shadow-sm` | 0 2px 4px rgba(...) | Card shadow |
| `--shadow-md` | 0 4px 12px rgba(...) | Floating element |
| `--shadow-lg` | 0 8px 24px rgba(...) | Modal, dropdown |
| `--shadow-xl` | 0 12px 32px rgba(...) | Maximum elevation |
| `--shadow-focus` | 0 0 0 3px rgba(...) | Focus ring |

### Examples

```css
/* Card with shadow */
.card {
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-sm);
}

.card:hover {
    box-shadow: var(--shadow-md);
}

/* Button */
.button {
    border-radius: var(--border-radius-sm);
}

/* Modal */
.modal {
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-lg);
}

/* Avatar */
.avatar {
    border-radius: var(--border-radius-full);
}

/* Focus state */
input:focus {
    outline: none;
    box-shadow: var(--shadow-focus);
}
```

---

## Transition Variables

| Variable | Duration | Easing | Usage |
|----------|----------|--------|-------|
| `--transition-fast` | 150ms | ease-in-out | Quick interactions |
| `--transition` | 200ms | ease-in-out | **STANDARD** - most transitions |
| `--transition-slow` | 300ms | ease-in-out | Slower animations |

### Examples

```css
/* Button hover */
.button {
    transition: background-color var(--transition),
                box-shadow var(--transition);
}

/* Color change */
a {
    transition: color var(--transition);
}

/* Modal appear */
.modal {
    transition: opacity var(--transition-slow),
                transform var(--transition-slow);
}

/* Quick feedback */
.input:focus {
    transition: box-shadow var(--transition-fast);
}
```

---

## Component-Specific Variables

### Buttons

```css
--button-padding-sm: var(--spacing-sm) var(--spacing-md);      /* 8px 16px */
--button-padding-md: var(--spacing-md) var(--spacing-lg);      /* 16px 24px */
--button-padding-lg: var(--spacing-lg) var(--spacing-xl);      /* 24px 32px */
--button-border-radius: var(--border-radius-sm);               /* 4px */
--button-font-weight: var(--font-weight-medium);               /* 500 */
--button-transition: var(--transition);                        /* 200ms */
```

**Usage:**
```css
.button {
    padding: var(--button-padding-md);
    border-radius: var(--button-border-radius);
    font-weight: var(--button-font-weight);
    transition: var(--button-transition);
}

.button-sm { padding: var(--button-padding-sm); }
.button-lg { padding: var(--button-padding-lg); }
```

### Cards

```css
--card-padding: var(--spacing-lg);           /* 24px */
--card-padding-sm: var(--spacing-md);        /* 16px */
--card-border-radius: var(--border-radius-md); /* 6px */
--card-shadow: var(--shadow-sm);
--card-shadow-hover: var(--shadow-md);
```

**Usage:**
```css
.card {
    padding: var(--card-padding);
    border-radius: var(--card-border-radius);
    box-shadow: var(--card-shadow);
    transition: var(--transition);
}

.card:hover {
    box-shadow: var(--card-shadow-hover);
}
```

### Forms

```css
--form-input-padding: 8px 12px;
--form-input-border-radius: var(--border-radius-sm);
--form-input-font-size: var(--font-size-body);
--form-input-line-height: var(--line-height-normal);
--form-label-font-weight: var(--font-weight-semibold);
--form-label-margin-bottom: var(--spacing-sm);
```

**Usage:**
```css
label {
    font-weight: var(--form-label-font-weight);
    margin-bottom: var(--form-label-margin-bottom);
}

input, select, textarea {
    padding: var(--form-input-padding);
    border-radius: var(--form-input-border-radius);
    font-size: var(--form-input-font-size);
    line-height: var(--form-input-line-height);
}
```

### Modals

```css
--modal-padding: var(--spacing-lg);          /* 24px */
--modal-border-radius: var(--border-radius-lg); /* 8px */
--modal-shadow: var(--shadow-lg);
--modal-backdrop: rgba(0, 0, 0, 0.5);
```

**Usage:**
```css
.modal {
    padding: var(--modal-padding);
    border-radius: var(--modal-border-radius);
    box-shadow: var(--modal-shadow);
}

.modal-backdrop {
    background-color: var(--modal-backdrop);
}
```

---

## Z-Index Scale

| Variable | Value | Usage |
|----------|-------|-------|
| `--z-index-default` | 1 | Regular elements |
| `--z-index-sticky` | 50 | Fixed/sticky header |
| `--z-index-dropdown` | 100 | Dropdown menus |
| `--z-index-modal` | 1000 | Modal backdrop |
| `--z-index-modal-content` | 1001 | Modal content (above backdrop) |
| `--z-index-notification` | 1050 | Toast notifications |
| `--z-index-tooltip` | 1100 | Tooltips (highest) |

**Usage:**
```css
.header {
    position: sticky;
    z-index: var(--z-index-sticky);
}

.dropdown {
    z-index: var(--z-index-dropdown);
}

.modal-backdrop {
    z-index: var(--z-index-modal);
}

.modal {
    z-index: var(--z-index-modal-content);
}

.toast {
    z-index: var(--z-index-notification);
}

.tooltip {
    z-index: var(--z-index-tooltip);
}
```

---

## Accessibility Variables

### Reduced Motion

Automatically applies when user prefers reduced motion:

```css
@media (prefers-reduced-motion: reduce) {
    /* All transitions become 0ms */
    --transition-fast: 0ms;
    --transition: 0ms;
    --transition-slow: 0ms;
}
```

### Dark Mode

Automatically applies in dark mode preference:

```css
@media (prefers-color-scheme: dark) {
    /* Colors are automatically adjusted */
    --color-text-primary: #E8EAED;
    --color-bg-primary: #202124;
    /* etc. */
}
```

### High Contrast

Enhanced contrast for accessibility:

```css
@media (prefers-contrast: more) {
    --border-width: 2px;
    --focus-outline-width: 3px;
    --color-text-primary: #000000;
}
```

---

## Best Practices

### ✅ DO

```css
/* ✅ Good: Use variables */
.button {
    padding: var(--spacing-md);
    background: var(--color-primary);
    border-radius: var(--border-radius-sm);
}

/* ✅ Good: Compose component-specific variables */
.card {
    padding: var(--card-padding);
    box-shadow: var(--card-shadow);
}

/* ✅ Good: Use transitions for smooth interactions */
a {
    transition: color var(--transition);
}
```

### ❌ DON'T

```css
/* ❌ Bad: Hardcoded values */
.button {
    padding: 8px 16px;
    background: #0056B3;
    border-radius: 4px;
}

/* ❌ Bad: Using wrong variables */
.button {
    padding: var(--font-size-lg);  /* Wrong! Use spacing */
}

/* ❌ Bad: Skipping transitions */
.link {
    color: #0056B3;  /* No transition */
}

/* ❌ Bad: Creating new colors */
.badge {
    background: #FF6B6B;  /* Use semantic colors */
}
```

---

## Common Patterns

### Button State Pattern

```css
/* Default */
.button {
    background-color: var(--color-primary);
    color: var(--color-text-inverted);
    transition: var(--transition);
}

/* Hover */
.button:hover:not(:disabled) {
    background-color: var(--color-primary-dark);
    box-shadow: var(--shadow-sm);
}

/* Focus */
.button:focus-visible {
    outline: var(--focus-outline-width) solid var(--color-focus);
    outline-offset: var(--focus-outline-offset);
}

/* Active/Pressed */
.button:active:not(:disabled) {
    background-color: var(--color-active);
}

/* Disabled */
.button:disabled {
    background-color: var(--color-bg-secondary);
    color: var(--color-text-light);
    cursor: not-allowed;
}
```

### Input Field Pattern

```css
input, select, textarea {
    padding: var(--form-input-padding);
    font-size: var(--font-size-body);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--form-input-border-radius);
    transition: var(--transition);
}

input:hover,
select:hover,
textarea:hover {
    border-color: var(--color-border-dark);
}

input:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px var(--color-focus-ring);
}

input:disabled,
select:disabled,
textarea:disabled {
    background-color: var(--color-bg-secondary);
    color: var(--color-text-light);
    cursor: not-allowed;
}
```

### Card Pattern

```css
.card {
    padding: var(--card-padding);
    background: var(--color-bg-primary);
    border-radius: var(--card-border-radius);
    border: var(--border-width) solid var(--color-border);
    box-shadow: var(--card-shadow);
    transition: var(--transition);
}

.card:hover {
    box-shadow: var(--card-shadow-hover);
    border-color: var(--color-border-dark);
}

.card-header {
    padding-bottom: var(--spacing-md);
    border-bottom: var(--border-width) solid var(--color-border);
    margin-bottom: var(--spacing-md);
}

.card-footer {
    padding-top: var(--spacing-md);
    border-top: var(--border-width) solid var(--color-border);
    margin-top: var(--spacing-md);
}
```

---

## Troubleshooting

### Variables Not Working

**Problem**: CSS variables showing as fallback or not applying  
**Solution**: 
1. Ensure `variables.css` is imported FIRST in `style.css`
2. Check CSS file isn't loaded before main stylesheet
3. Verify variables are defined in `:root` selector
4. Check for typos in variable names

### Colors Look Wrong

**Problem**: Colors not matching design system  
**Solution**:
1. Verify you're using correct variable (e.g., `--color-primary` not custom color)
2. Check if dark mode is active (colors are different)
3. Use browser DevTools to inspect actual values
4. Compare against `DESIGN_SYSTEM.md` specifications

### Transitions Not Smooth

**Problem**: Animations feel jerky or laggy  
**Solution**:
1. Use `var(--transition)` (200ms) for standard transitions
2. Use `var(--transition-fast)` (150ms) for quick feedback
3. Check for conflicting transition properties
4. Verify hardware acceleration is enabled (use `will-change` sparingly)

---

## When to Add New Variables

Consider adding a new variable when:
- ✅ Value appears in 2+ files
- ✅ Value is part of the design system
- ✅ Value might need to change (dark mode, themes, etc.)
- ✅ Value affects multiple elements

Don't add variables for:
- ❌ One-time specific values
- ❌ Highly contextual values
- ❌ Dynamic values (use SCSS if needed)

---

## Reference

**File Location**: `/wp-content/themes/bkgt-ledare/assets/css/variables.css`  
**Documentation**: `DESIGN_SYSTEM.md`  
**Implementation Track**: `CSS_VARIABLES_IMPLEMENTATION.md`

---

## Support

For questions or issues:
1. Check this guide first
2. Review `DESIGN_SYSTEM.md` for detailed specifications
3. Search CSS variables file for variable definitions
4. Contact development team

---

**Version**: 1.0  
**Last Updated**: 2024  
**Status**: Active & Maintained
