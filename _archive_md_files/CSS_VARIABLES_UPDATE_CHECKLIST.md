# CSS Variables Update Checklist

**Quick Win #2**: Replace hardcoded CSS values with design system variables  
**Status**: Foundation Complete - Ready for Plugin Updates  
**Estimated Time**: 3-4 hours for full implementation  

---

## Pre-Update Checklist ✓

Before starting CSS updates, verify:

- [x] CSS variables file created: `/wp-content/themes/bkgt-ledare/assets/css/variables.css`
- [x] Theme stylesheet updated: `style.css` imports variables.css
- [x] Developer guide available: `CSS_VARIABLES_GUIDE.md`
- [x] Variable reference table reviewed
- [x] Browser DevTools prepared for testing
- [ ] Database backed up (before going live)
- [ ] Local environment ready for testing

---

## Plugin-by-Plugin Checklist

### Plugin 1: BKGT Inventory ⏳ READY TO START

**Files to Update**:
- [ ] `/wp-content/plugins/bkgt-inventory/assets/frontend.css` (395 lines)
- [ ] `/wp-content/plugins/bkgt-inventory/assets/admin.css` (TBD lines)

**Common Hardcoded Values** (from line review):
```
Colors:
  [ ] #e5e5e5 → var(--color-border)
  [ ] #23282d → var(--color-text-primary)
  [ ] #f0f0f0 → var(--color-bg-secondary)
  [ ] #007cba → var(--color-primary)
  [ ] #005a87 → var(--color-primary-dark)
  [ ] #ddd → var(--color-border-light)
  [ ] #f9f9f9 → var(--color-bg-light)
  [ ] #fff → var(--color-bg-primary)

Spacing:
  [ ] 20px → var(--spacing-lg)
  [ ] 30px → var(--spacing-xl)
  [ ] 5px → var(--spacing-xs)
  [ ] 10px → mix of xs/sm - context dependent
  [ ] 15px → between sm and md
  [ ] 8px → var(--spacing-sm)
  [ ] 16px → var(--spacing-md)

Borders:
  [ ] 4px → var(--border-radius-sm)
  [ ] 8px → var(--border-radius-lg)
  [ ] 1px → var(--border-width)

Shadows:
  [ ] 0 2px 4px rgba(...) → var(--shadow-sm)

Font Sizes:
  [ ] 14px → var(--font-size-body)
  [ ] 16px → var(--font-size-lg)
```

**Update Steps**:
1. [ ] Open frontend.css in editor
2. [ ] Use Find & Replace to update values systematically
3. [ ] Review each replacement for correctness
4. [ ] Save and test visually
5. [ ] Repeat for admin.css
6. [ ] Run visual regression tests
7. [ ] Test dark mode rendering
8. [ ] Commit changes

**Testing**:
- [ ] Load inventory page
- [ ] Verify all colors match design system
- [ ] Check button styles
- [ ] Verify card layouts
- [ ] Test responsive design
- [ ] Check dark mode

---

### Plugin 2: BKGT Core ⏳

**Files to Update**:
- [ ] `/wp-content/plugins/bkgt-core/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-core/assets/css/admin.css`

**Priority**: HIGH (provides shared styles)

**Steps**:
1. [ ] Review files for hardcoded values
2. [ ] Update using CSS_VARIABLES_GUIDE.md as reference
3. [ ] Test all components that depend on core
4. [ ] Verify no conflicts with other plugins

---

### Plugin 3: BKGT User Management ⏳

**Files to Update**:
- [ ] `/wp-content/plugins/bkgt-user-management/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-user-management/assets/css/admin.css`

**Priority**: HIGH (affects all pages - user interface)

---

### Plugin 4: BKGT Events ⏳

**Files to Update**:
- [ ] `/wp-content/plugins/bkgt-events/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-events/assets/css/admin.css`

**Priority**: MEDIUM

---

### Plugin 5: BKGT Document Management ⏳

**Files to Update**:
- [ ] `/wp-content/plugins/bkgt-document-management/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-document-management/assets/css/admin.css`
- [ ] `/wp-content/plugins/bkgt-document-management/admin/css/admin.css`
- [ ] `/wp-content/plugins/bkgt-document-management/admin/css/template-builder.css`
- [ ] `/wp-content/plugins/bkgt-document-management/admin/css/smart-templates.css`
- [ ] `/wp-content/plugins/bkgt-document-management/admin/css/export-engine.css`

**Priority**: MEDIUM (6 files total)

**Special Considerations**:
- [ ] Check for PDF export styles
- [ ] Verify print styles compatibility
- [ ] Test template builder interface

---

### Plugin 6: BKGT Team/Player ⏳

**Files to Update**:
- [ ] `/wp-content/plugins/bkgt-team-player/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-team-player/assets/css/admin-dashboard.css`

**Priority**: MEDIUM

---

### Plugin 7: BKGT Communication ⏳

**Files to Update**:
- [ ] `/wp-content/plugins/bkgt-communication/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-communication/assets/css/admin.css`

**Priority**: MEDIUM (likely smaller impact)

---

### Plugin 8: BKGT Data Scraping ⏳

**Files to Update**:
- [ ] `/wp-content/plugins/bkgt-data-scraping/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-data-scraping/admin/css/admin.css`

**Priority**: LOW (backend/data focused)

---

### Plugin 9: BKGT Offboarding ⏳

**Files to Update**:
- [ ] `/wp-content/plugins/bkgt-offboarding/assets/css/frontend.css`
- [ ] `/wp-content/plugins/bkgt-offboarding/assets/css/admin.css`

**Priority**: LOW (specialized feature)

---

### Theme: BKGT Ledare ⏳

**Files to Update**:
- [ ] `/wp-content/themes/bkgt-ledare/style.css` (1361 lines - LARGE)

**Priority**: LAST (after all plugins)

**Special Considerations**:
- [ ] Check for responsive breakpoints
- [ ] Verify print styles
- [ ] Test all page templates
- [ ] Check dark mode thoroughly

**Steps**:
1. [ ] Create backup of style.css
2. [ ] Systematically replace hardcoded values
3. [ ] Test every page template
4. [ ] Verify responsive breakpoints
5. [ ] Test dark mode
6. [ ] Visual QA on all pages
7. [ ] Commit changes

---

## Update Process Template

Use this process for each file:

### 1. Analysis Phase
```
[ ] Open file in editor
[ ] Search for hardcoded color values (#xxx format)
[ ] Search for hardcoded spacing values (px)
[ ] Search for hardcoded border-radius values
[ ] Search for hardcoded shadows
[ ] Create list of values to replace
```

### 2. Replacement Phase
```
[ ] Use Find & Replace for each common value
[ ] Review each replacement in context
[ ] Ensure no false positives
[ ] Check that replaced value makes sense
[ ] Verify visual results after save
```

### 3. Testing Phase
```
[ ] Load page in browser
[ ] Visual inspection (colors, spacing, alignment)
[ ] Hover states working
[ ] Focus states visible
[ ] Responsive design tested
[ ] Dark mode tested (if applicable)
[ ] No console errors
```

### 4. Completion Phase
```
[ ] All values replaced with variables
[ ] File saved and tested
[ ] No visual regressions
[ ] Mark in checklist as complete
[ ] Move to next file
```

---

## Common Replacement Patterns

### Pattern 1: Button Colors
```css
/* BEFORE */
.button {
    background: #007cba;
    color: #fff;
    border-color: #007cba;
}

.button:hover {
    background: #005a87;
    border-color: #005a87;
}

/* AFTER */
.button {
    background: var(--color-primary);
    color: var(--color-text-inverted);
    border-color: var(--color-primary);
}

.button:hover {
    background: var(--color-primary-dark);
    border-color: var(--color-primary-dark);
}
```

### Pattern 2: Spacing/Padding
```css
/* BEFORE */
.card {
    padding: 20px;
    margin-bottom: 30px;
    gap: 10px;
}

/* AFTER */
.card {
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
    gap: var(--spacing-sm);
}
```

### Pattern 3: Border Radius
```css
/* BEFORE */
.button {
    border-radius: 4px;
}

.card {
    border-radius: 6px;
}

.modal {
    border-radius: 8px;
}

/* AFTER */
.button {
    border-radius: var(--border-radius-sm);
}

.card {
    border-radius: var(--border-radius-md);
}

.modal {
    border-radius: var(--border-radius-lg);
}
```

### Pattern 4: Shadows
```css
/* BEFORE */
.card {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

/* AFTER */
.card {
    box-shadow: var(--shadow-sm);
}

.card:hover {
    box-shadow: var(--shadow-md);
}
```

### Pattern 5: Borders
```css
/* BEFORE */
.input {
    border: 1px solid #e5e5e5;
}

/* AFTER */
.input {
    border: var(--border-width) solid var(--color-border);
}
```

---

## Quality Assurance Checklist

After updating each file:

### Visual Inspection
- [ ] All colors render correctly
- [ ] Spacing looks consistent
- [ ] Borders and shadows display properly
- [ ] Font sizes readable
- [ ] No layout shift
- [ ] Responsive design works
- [ ] Dark mode functional (if supported)

### Functionality Testing
- [ ] Buttons clickable and functional
- [ ] Forms submit correctly
- [ ] Links navigate properly
- [ ] Modals open/close
- [ ] Dropdowns work
- [ ] Hover states visible
- [ ] Focus states visible

### Browser Testing
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile responsive (768px breakpoint)
- [ ] Mobile responsive (320px breakpoint)

### Performance
- [ ] Page load time acceptable
- [ ] CSS file size reasonable
- [ ] No layout thrashing
- [ ] Smooth animations

### Accessibility
- [ ] Focus indicators clear
- [ ] Colors have sufficient contrast
- [ ] Reduced motion respected
- [ ] Dark mode accessible

---

## Documentation Updates

As you complete updates, document:

### Update Log
```
[ ] Date of update
[ ] File(s) updated
[ ] Number of replacements made
[ ] Any issues encountered
[ ] Testing results
[ ] Approver name
```

### Example:
```markdown
## Update Log

### 2024-XX-XX - BKGT Inventory Plugin
- **Files Updated**: frontend.css, admin.css
- **Variables Replaced**: 32
- **Testing**: PASSED
- **Issues**: None
- **Approver**: [name]
```

---

## Rollback Plan

If issues occur during updates:

### Immediate Rollback
```
1. Git revert to last known good state
2. Reload page in browser
3. Verify functionality restored
4. Identify what went wrong
5. Fix and re-test carefully
```

### Prevention
```
[ ] Commit after each plugin update
[ ] Use feature branches for large changes
[ ] Test thoroughly before committing
[ ] Keep backups of original files
[ ] Document all changes
```

---

## Completion Tracking

### Summary Progress Table

| Plugin | Files | Status | Issues | Tested |
|--------|-------|--------|--------|--------|
| Inventory | 2 | [ ] | - | [ ] |
| Core | 2 | [ ] | - | [ ] |
| User Mgmt | 2 | [ ] | - | [ ] |
| Events | 2 | [ ] | - | [ ] |
| Documents | 6 | [ ] | - | [ ] |
| Team/Player | 2 | [ ] | - | [ ] |
| Communication | 2 | [ ] | - | [ ] |
| Data Scraping | 2 | [ ] | - | [ ] |
| Offboarding | 2 | [ ] | - | [ ] |
| Theme | 1 | [ ] | - | [ ] |
| **TOTAL** | **23** | - | - | [ ] |

### Estimated Timeline

- Plugins 1-3: 1.5-2 hours
- Plugins 4-6: 1-1.5 hours
- Plugins 7-9: 0.5-1 hour
- Theme: 1-1.5 hours
- **Total**: 4-6 hours

---

## Resources

### References
- `CSS_VARIABLES_GUIDE.md` - Variable reference
- `CSS_VARIABLES_IMPLEMENTATION.md` - Detailed tracking
- `DESIGN_SYSTEM.md` - Visual specifications
- `/wp-content/themes/bkgt-ledare/assets/css/variables.css` - Actual variables

### Tools
- Browser DevTools (F12)
- Code editor Find & Replace
- Git for version control
- Color picker extension (optional)

---

## Sign-Off

### Completion Checklist
- [ ] All 23 CSS files reviewed
- [ ] All hardcoded values identified
- [ ] All replacements made
- [ ] All files tested
- [ ] Visual consistency verified
- [ ] Dark mode tested
- [ ] Mobile responsive tested
- [ ] No regressions detected
- [ ] Performance acceptable
- [ ] Accessibility verified

### Final Sign-Off
```
Completed By: ________________
Date: ________________
Approved By: ________________
Date: ________________
```

---

## Notes Section

Use this space to track issues, decisions, or notes:

```
### Session 1 Notes:
- [Your notes here]

### Session 2 Notes:
- [Your notes here]

### Known Issues:
- [Issue 1]
- [Issue 2]

### Decisions Made:
- [Decision 1]
- [Decision 2]
```

---

**Document Status**: Ready for Implementation  
**Version**: 1.0  
**Last Updated**: 2024  
**Next Review**: After all updates complete
