---
name: web-design-guidelines
description: UI/UX best practices and accessibility guidelines. Use when reviewing UI code, checking accessibility, auditing forms, or ensuring web interface best practices. Triggers on "review UI", "check accessibility", "audit design", "review UX", or "check best practices".
license: MIT
metadata:
  author: agent-skills
  version: "2.0.0"
---

# Web Design Guidelines

WCAG accessibility, semantic HTML, keyboard navigation, forms, and performance patterns for inclusive web interfaces. Contains 23 rules across 4 categories.

## Metadata

- **Version:** 2.0.0
- **Rule Count:** 23 rules across 4 categories
- **License:** MIT

## When to Apply

Reference these guidelines when:
- Reviewing UI code for accessibility (WCAG compliance)
- Implementing forms and interactions
- Ensuring keyboard navigation works
- Adding ARIA labels and live regions
- Optimizing image loading and layout stability

## Rule Categories by Priority

| Priority | Category | Impact | Prefix |
|----------|----------|--------|--------|
| 1 | Accessibility | CRITICAL | `a11y-` |
| 2 | Forms | HIGH | `form-` |
| 3 | Animation & Motion | CRITICAL | `motion-` |
| 4 | Performance & UX | MEDIUM | `perf-` |

## Quick Reference

### 1. Accessibility (CRITICAL)

- `a11y-semantic-html` - Use semantic HTML elements
- `a11y-heading-hierarchy` - Maintain proper heading hierarchy
- `a11y-screen-reader` - Optimize for screen reader compatibility
- `a11y-skip-links` - Provide skip links for navigation
- `a11y-keyboard-nav` - Ensure full keyboard navigation
- `a11y-focus-management` - Manage keyboard focus properly
- `a11y-aria-labels` - Add ARIA labels to interactive elements
- `a11y-color-contrast` - Ensure sufficient color contrast (WCAG AA)
- `a11y-alt-text` - Provide meaningful alt text for images
- `a11y-error-messages` - Make error messages accessible
- `a11y-form-labels` - Associate labels with form inputs
- `a11y-live-regions` - Announce dynamic content to screen readers

### 2. Forms (HIGH)

- `form-autocomplete` - Use autocomplete attributes
- `form-input-types` - Use correct input types
- `form-error-display` - Display form errors clearly
- `form-validation-ux` - Design user-friendly validation
- `form-inline-validation` - Implement smart inline validation
- `form-multi-step` - Design effective multi-step forms
- `form-placeholder-usage` - Use placeholders appropriately
- `form-submit-feedback` - Provide clear submission feedback

### 3. Animation & Motion (CRITICAL)

- `motion-reduced` - Respect prefers-reduced-motion (WCAG AAA)

### 4. Performance & UX (MEDIUM)

- `perf-image-loading` - Optimize image loading for UX
- `perf-layout-stability` - Prevent cumulative layout shift

## Essential Guidelines

### Semantic HTML

```tsx
// ❌ Div soup
<div className="header"><div className="nav"><div onClick={handleClick}>Home</div></div></div>

// ✅ Semantic HTML
<header><nav aria-label="Main"><a href="/">Home</a></nav></header>
<main><article><h1>Title</h1><p>Content</p></article></main>
```

### Form Accessibility

```tsx
<label htmlFor="email">Email <span aria-hidden="true">*</span></label>
<input id="email" type="email" required aria-required="true" aria-describedby="email-error" autoComplete="email" />
{error && <p id="email-error" role="alert">{error}</p>}
```

### Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

## How to Use

Read individual rule files for detailed explanations:

```
rules/a11y-semantic-html.md
rules/form-autocomplete.md
rules/motion-reduced.md
rules/perf-image-loading.md
```

## References

- [WCAG 2.2 Quick Reference](https://www.w3.org/WAI/WCAG22/quickref/)
- [WAI-ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [web.dev Accessibility](https://web.dev/accessibility/)
- [MDN Accessibility](https://developer.mozilla.org/en-US/docs/Web/Accessibility)
- [The A11Y Project](https://a11yproject.com/)

## Full Compiled Document

For the complete guide with all rules expanded: `AGENTS.md`
