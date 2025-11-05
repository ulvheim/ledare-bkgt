# BKGT Ledare - Enterprise Design System

**Version:** 1.0  
**Status:** Foundation  
**Last Updated:** November 3, 2025  

---

## 📖 Table of Contents

1. [Vision & Principles](#vision--principles)
2. [Color Palette](#color-palette)
3. [Typography](#typography)
4. [Spacing System](#spacing-system)
5. [Border & Shadows](#border--shadows)
6. [Components](#components)
7. [Layouts](#layouts)
8. [Accessibility](#accessibility)
9. [Best Practices](#best-practices)

---

## 🎯 Vision & Principles

### Design Philosophy
BKGT Ledare is an enterprise administration system that values:
- **Clarity:** Information is easy to find and understand
- **Consistency:** Predictable patterns across the system
- **Professional:** Modern, polished appearance
- **Accessible:** Works for everyone, including those with disabilities
- **Data-Driven:** Real metrics prominently displayed

### Core Principles
1. **Form follows function** - Design serves the user's needs
2. **Consistency over novelty** - Predictable patterns preferred
3. **Accessibility first** - Consider all users from the start
4. **Mobile-first approach** - Responsive on all devices
5. **Performance matters** - Lean, efficient CSS
6. **Internationalization ready** - Support Swedish and English

---

## 🎨 Color Palette

### Primary Colors

```
PRIMARY BLUE
├─ Name: Ledare Blue
├─ Hex: #0056B3
├─ RGB: 0, 86, 179
├─ Use: Primary actions, links, key elements
└─ Accessibility: AA compliant on white backgrounds

SECONDARY TEAL
├─ Name: Accent Teal
├─ Hex: #17A2B8
├─ RGB: 23, 162, 184
├─ Use: Secondary actions, highlights
└─ Accessibility: AA compliant on white backgrounds

NEUTRAL GRAY
├─ Name: Neutral Gray
├─ Hex: #6C757D
├─ RGB: 108, 117, 125
├─ Use: Secondary text, disabled states
└─ Accessibility: AA compliant on white backgrounds
```

### Semantic Colors

```
SUCCESS (Positive/Approved)
├─ Hex: #28A745
├─ RGB: 40, 167, 69
├─ Use: Completed, active, success messages
└─ Example: ✓ Event confirmed

WARNING (Caution/Attention)
├─ Hex: #FFC107
├─ RGB: 255, 193, 7
├─ Use: Pending, needs attention, caution
└─ Example: ⚠ Document awaiting review

DANGER (Critical/Error)
├─ Hex: #DC3545
├─ RGB: 220, 53, 69
├─ Use: Errors, critical issues, deletions
└─ Example: ✗ Invalid submission

INFORMATION (Informational)
├─ Hex: #0C5FF4
├─ RGB: 12, 95, 244
├─ Use: Information messages, hints
└─ Example: ℹ New feature available
```

### Neutral Colors

```
TEXT PRIMARY (Dark)
├─ Hex: #1D2327
├─ RGB: 29, 35, 39
├─ Use: Main text, headings
└─ Contrast: 22.4:1 on white

TEXT SECONDARY (Medium)
├─ Hex: #646970
├─ RGB: 100, 105, 112
├─ Use: Secondary text, labels
└─ Contrast: 10.5:1 on white

TEXT LIGHT (Light)
├─ Hex: #B5BACA
├─ RGB: 181, 186, 202
├─ Use: Disabled text, placeholders
└─ Contrast: 4.8:1 on white

BACKGROUND LIGHT
├─ Hex: #F8F9FA
├─ RGB: 248, 249, 250
├─ Use: Alternate backgrounds, light areas
└─ Contrast: 1.3:1 on white

BORDER COLOR
├─ Hex: #E1E5E9
├─ RGB: 225, 229, 233
├─ Use: Borders, dividers
└─ Visibility: Clear on light backgrounds

WHITE
├─ Hex: #FFFFFF
├─ RGB: 255, 255, 255
├─ Use: Primary background, cards
└─ Standard: Pure white
```

### Color Usage Examples

```css
/* Example: Card component */
.card {
    background-color: #FFFFFF;
    border: 1px solid #E1E5E9;
    color: #1D2327;
}

.card-title {
    color: #1D2327;
    font-weight: 600;
}

.card-text {
    color: #646970;
}

/* Example: Status badges */
.badge-success {
    background-color: #28A745;
    color: #FFFFFF;
}

.badge-warning {
    background-color: #FFC107;
    color: #1D2327; /* Dark text on yellow */
}

.badge-danger {
    background-color: #DC3545;
    color: #FFFFFF;
}
```

---

## 📝 Typography

### Font Stack

```css
/* Primary Font (Headings) */
--font-heading: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;

/* Body Font (Content) */
--font-body: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;

/* Monospace (Code) */
--font-monospace: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
```

### Typographic Scale

```
Display          48px / 700 (72px line-height)    Letter-spacing: -0.5px
├─ Use: Page titles, hero sections
├─ Example: "Inventory Management"
└─ Swedish: "Lagerhållning"

Heading 1        32px / 700 (48px line-height)    Letter-spacing: -0.3px
├─ Use: Major section headers
├─ Example: "Team Players"
└─ Swedish: "Lagspelare"

Heading 2        24px / 600 (36px line-height)    Letter-spacing: 0
├─ Use: Subsection headers
├─ Example: "Recent Activity"
└─ Swedish: "Senaste aktivitet"

Heading 3        18px / 600 (27px line-height)    Letter-spacing: 0
├─ Use: Card titles, data sections
├─ Example: "Total Events"
└─ Swedish: "Totalt antal evenemang"

Body Large       16px / 400 (24px line-height)    Letter-spacing: 0.5px
├─ Use: Important text, introductions
├─ Example: Body text in articles
└─ Max width: 75 characters

Body             14px / 400 (21px line-height)    Letter-spacing: 0.5px
├─ Use: Standard paragraph text
├─ Example: Regular content
└─ Max width: 75 characters

Small            12px / 400 (18px line-height)    Letter-spacing: 0.4px
├─ Use: Labels, metadata, captions
├─ Example: "Modified: Nov 3, 2025"
└─ Never below 12px

Code             13px / 400 (19px line-height)    monospace
├─ Use: Code snippets, data
├─ Example: API responses
└─ Slightly larger than text
```

### Font Weight Scale

```
Thin         100  (rarely used)
Light        300  (accent text, large display)
Regular      400  (body text, standard)
Medium       500  (form labels, secondary headings)
Semibold     600  (important text, heading 2-3)
Bold         700  (headings 1+, emphasis)
Extrabold    800  (rare, visual accent)
```

---

## 📏 Spacing System

### Base Unit: 4px
All spacing values are multiples of 4px for consistent rhythm.

```
xs:   4px   (1 unit)  - Tight element spacing
sm:   8px   (2 units) - Padding in small components
md:  16px   (4 units) - Standard padding/margin
lg:  24px   (6 units) - Section spacing
xl:  32px   (8 units) - Major spacing
2xl: 48px   (12 units)- Page-level spacing
3xl: 64px   (16 units)- Large section gaps
```

### Spacing Application Guide

```css
/* Margins (Vertical Spacing) */
margin-bottom: var(--spacing-md);   /* Between sections */
margin-top: var(--spacing-sm);      /* After headings */

/* Padding (Internal Spacing) */
padding: var(--spacing-md);         /* Card padding */
padding: var(--spacing-sm);         /* Button padding */

/* Gaps (Flex/Grid) */
gap: var(--spacing-md);             /* Between grid items */

/* Text Spacing */
line-height: 1.5;                   /* 1.5x font size */
letter-spacing: 0.5px;              /* For readability */
```

### Examples

```
Grid Items:      gap: 16px (1 row of cards)
Cards:           padding: 20px (1.25x standard)
Buttons:         padding: 8px 16px (sm/md)
Sections:        margin-bottom: 32px (lg)
Page Margins:    padding: 48px (2xl)
```

---

## 🎭 Border & Shadows

### Border Radius Scale

```
No Radius      0px      Rare (sharp edges)
Small          4px      Buttons, form inputs
Medium         6px      Cards, modals
Large          8px      Larger containers
Full/Circle    50%       Avatars, badges
```

### Border Styles

```css
/* Standard Border */
border: 1px solid #E1E5E9;

/* Light Border (subtle) */
border: 1px solid #E9ECEF;

/* Focus Border (interactive) */
border: 2px solid #0056B3;

/* Error Border */
border: 2px solid #DC3545;
```

### Box Shadows Scale

```
Elevation 1 (subtle):
box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);

Elevation 2 (cards):
box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);

Elevation 3 (floating):
box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);

Elevation 4 (modals/hover):
box-shadow: 0 8px 24px rgba(0, 0, 0, 0.16);

Focus State (interactive):
box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
```

---

## 🧩 Components

### Card Component

```css
.card {
    background-color: #FFFFFF;
    border: 1px solid #E1E5E9;
    border-radius: 6px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    padding: 20px;
    transition: box-shadow 0.2s ease;
}

.card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.card-header {
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #E1E5E9;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: #1D2327;
    margin: 0;
}

.card-body {
    font-size: 14px;
    color: #646970;
    line-height: 1.5;
}

.card-footer {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid #E1E5E9;
    text-align: right;
}
```

### Button Component

```css
/* Primary Button */
.btn-primary {
    background-color: #0056B3;
    color: #FFFFFF;
    border: 1px solid #0056B3;
    padding: 8px 16px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.btn-primary:hover {
    background-color: #003D82;
}

.btn-primary:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
}

/* Secondary Button */
.btn-secondary {
    background-color: transparent;
    color: #0056B3;
    border: 1px solid #0056B3;
    padding: 8px 16px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
}

.btn-secondary:hover {
    background-color: #F8F9FA;
}
```

### Status Badge

```css
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-success {
    background-color: #D4EDDA;
    color: #155724;
}

.badge-warning {
    background-color: #FFF3CD;
    color: #856404;
}

.badge-danger {
    background-color: #F8D7DA;
    color: #721C24;
}

.badge-info {
    background-color: #D1ECF1;
    color: #0C5460;
}
```

### Form Input

```css
.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #1D2327;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #E1E5E9;
    border-radius: 4px;
    font-size: 14px;
    font-family: inherit;
    transition: border-color 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: #0056B3;
    box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
}

.form-control:disabled {
    background-color: #F8F9FA;
    color: #B5BACA;
    cursor: not-allowed;
}

.form-help {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: #646970;
}

.form-error {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: #DC3545;
}
```

---

## 📐 Layouts

### Dashboard Grid

```css
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}
```

### Two Column Layout

```css
.two-column {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
}

@media (max-width: 1024px) {
    .two-column {
        grid-template-columns: 1fr;
    }
}
```

---

## ♿ Accessibility

### Color Contrast Requirements

```
AAA Compliance (all systems):
├─ Large text (18px+): 4.5:1 minimum
├─ Small text: 7:1 minimum
└─ Graphics: 3:1 minimum

Our Standard: AA Compliance
├─ Large text (18px+): 4.5:1
├─ Small text: 4.5:1
└─ Graphics: 3:1
```

### Keyboard Navigation

```css
/* Focus Indicators (Required) */
*:focus {
    outline: 2px solid #0056B3;
    outline-offset: 2px;
}

/* Focus Visible (Only on keyboard) */
*:focus-visible {
    outline: 2px solid #0056B3;
    outline-offset: 2px;
}
```

### Screen Reader Support

```html
<!-- Use semantic HTML -->
<button>Action</button>
<a href="">Link</a>

<!-- Provide aria-labels when needed -->
<button aria-label="Close">×</button>

<!-- Form labels -->
<label for="email">Email</label>
<input id="email" type="email" />
```

### Color Alone Not Sufficient

```css
/* ❌ Bad: Color alone indicates status */
.status-active { color: #28A745; }

/* ✅ Good: Icon + color indicates status */
.status-active::before {
    content: "✓ ";
    color: #28A745;
}
```

---

## ✅ Best Practices

### Do's
- ✅ Use the design system for all new components
- ✅ Maintain consistent spacing between elements
- ✅ Use semantic HTML
- ✅ Test keyboard navigation
- ✅ Check color contrast ratios
- ✅ Provide alt text for icons
- ✅ Use clear, descriptive labels
- ✅ Support multiple input methods
- ✅ Test on actual devices
- ✅ Document exceptions

### Don'ts
- ❌ Don't use multiple color schemes
- ❌ Don't override spacing system arbitrarily
- ❌ Don't use color alone for information
- ❌ Don't create unclickable elements that look clickable
- ❌ Don't remove focus indicators
- ❌ Don't rely on hover states alone
- ❌ Don't use text smaller than 12px
- ❌ Don't nest too deeply (max 3 levels)
- ❌ Don't forget mobile users
- ❌ Don't skip documentation

---

## 🔄 CSS Custom Properties Reference

```css
:root {
    /* Colors */
    --color-primary: #0056B3;
    --color-secondary: #17A2B8;
    --color-success: #28A745;
    --color-warning: #FFC107;
    --color-danger: #DC3545;
    --color-info: #0C5FF4;
    
    /* Text Colors */
    --color-text-primary: #1D2327;
    --color-text-secondary: #646970;
    --color-text-light: #B5BACA;
    
    /* Backgrounds */
    --color-bg-light: #F8F9FA;
    --color-bg-white: #FFFFFF;
    
    /* Borders */
    --color-border: #E1E5E9;
    
    /* Spacing */
    --spacing-xs: 4px;
    --spacing-sm: 8px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    --spacing-xl: 32px;
    --spacing-2xl: 48px;
    
    /* Typography */
    --font-family-heading: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    --font-family-body: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    --font-size-body: 14px;
    --line-height-body: 1.5;
    
    /* Border Radius */
    --radius-sm: 4px;
    --radius-md: 6px;
    --radius-lg: 8px;
    
    /* Shadows */
    --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
    --shadow-md: 0 2px 4px rgba(0, 0, 0, 0.08);
    --shadow-lg: 0 4px 12px rgba(0, 0, 0, 0.12);
}
```

---

## 📚 Resources

### Browser Support
- All modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile browsers (iOS Safari, Chrome Mobile)
- IE11: Limited support (no CSS variables)

### External Libraries
- None required (system is vanilla CSS)
- Optional: Accessibility testing tools (aXe, WAVE)

### Testing Checklist
- [ ] Contrast ratio verified (AA minimum)
- [ ] Keyboard navigation tested
- [ ] Screen reader tested
- [ ] Mobile responsive tested
- [ ] Touch targets adequate (48px minimum)
- [ ] Focus indicators visible
- [ ] Color not sole indicator

---

**Design System Version:** 1.0  
**Last Updated:** November 3, 2025  
**Status:** Foundation - Ready for Implementation  

This design system provides the foundation for enterprise-grade, accessible, and consistent UI across the BKGT Ledare platform. All future development should reference these guidelines.
