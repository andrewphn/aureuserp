# FilamentPHP v4 Styling Guide

This document explains how FilamentPHP handles design and styling, and how to customize it for your application.

## Table of Contents

1. [Overview](#overview)
2. [File Structure](#file-structure)
3. [CSS Architecture](#css-architecture)
4. [Filament CSS Hooks (Class Names)](#filament-css-hooks-class-names)
5. [Tailwind CSS v4 Compatibility](#tailwind-css-v4-compatibility)
6. [Theme Customization](#theme-customization)
7. [Common Gotchas](#common-gotchas)
8. [TCS-Specific Styling](#tcs-specific-styling)

---

## Overview

FilamentPHP v4 uses a CSS-first approach with:
- **Tailwind CSS** for utility classes
- **CSS Custom Properties** (CSS variables) for theming
- **Predictable CSS class hooks** prefixed with `fi-` for targeting components

Filament does NOT use:
- Inline styles (except for dynamic values)
- CSS-in-JS
- Scoped component styles

This means you can override any Filament component by targeting its CSS class.

---

## File Structure

```
resources/
├── css/
│   ├── app.css                      # Main application styles
│   ├── filament/
│   │   └── admin/
│   │       └── theme.css            # Filament panel theme overrides
│   ├── tcs-brand-tokens.css         # Brand color tokens
│   └── cabinet-spec-tokens.css      # Domain-specific tokens
├── views/
│   └── vendor/
│       └── filament-panels/         # Blade view overrides (optional)
```

### Key Files

| File | Purpose |
|------|---------|
| `resources/css/filament/admin/theme.css` | Primary Filament theme customization |
| `resources/css/app.css` | Global application styles |
| `vite.config.js` | Build configuration - must include theme.css in inputs |
| `tailwind.config.js` | Tailwind configuration with custom colors/safelist |

---

## CSS Architecture

### How Filament Loads CSS

1. **Filament's Base Theme**: Imported via `@import '../../../../vendor/filament/filament/resources/css/theme.css'`
2. **Your Custom Theme**: Added in `resources/css/filament/admin/theme.css`
3. **Vite Compilation**: Both are compiled together via `npm run build`

### Vite Configuration

```javascript
// vite.config.js
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament/admin/theme.css',  // Must be listed!
                // ... other entries
            ],
            refresh: true,
        }),
    ],
});
```

### PostCSS Configuration

```javascript
// postcss.config.js
export default {
    plugins: {
        '@tailwindcss/postcss': {},  // Tailwind v4 syntax
        autoprefixer: {},
    },
};
```

---

## Filament CSS Hooks (Class Names)

Filament uses predictable class naming with the `fi-` prefix. Here are the key classes:

### Layout Components

| Class | Component |
|-------|-----------|
| `.fi-topbar` | Top navigation bar |
| `.fi-sidebar` | Side navigation |
| `.fi-main` | Main content area |
| `.fi-footer` | Page footer |

### Navigation

| Class | Component |
|-------|-----------|
| `.fi-dropdown-panel` | Dropdown menu container |
| `.fi-topbar-item` | Navigation item in topbar |
| `.fi-topbar-item-btn` | Clickable link/button in nav item |
| `.fi-topbar-item-active` | Currently active nav item |
| `.fi-sidebar-item` | Sidebar navigation item |
| `.fi-sidebar-group` | Sidebar group container |

### Forms

| Class | Component |
|-------|-----------|
| `.fi-fo-field-wrp` | Field wrapper |
| `.fi-fo-field-wrp-label` | Field label container |
| `.fi-input-wrapper` | Input element wrapper |
| `.fi-select-input` | Select dropdown |
| `.fi-fo-checkbox-list` | Checkbox group |
| `.fi-fo-radio-list` | Radio button group |

### Tables

| Class | Component |
|-------|-----------|
| `.fi-ta-content` | Table content area |
| `.fi-ta-record` | Table row |
| `.fi-ta-cell` | Table cell |
| `.fi-ta-header-cell` | Table header cell |

### Buttons

| Class | Component |
|-------|-----------|
| `.fi-btn` | Base button |
| `.fi-btn-primary` | Primary action button |
| `.fi-btn-success` | Success/confirm button |
| `.fi-btn-danger` | Danger/delete button |
| `.fi-btn-gray` | Secondary/neutral button |

### Icons

| Class | Component |
|-------|-----------|
| `.fi-icon` | Icon wrapper |
| `.fi-size-sm` | Small icon size |
| `.fi-size-md` | Medium icon size |
| `.fi-size-lg` | Large icon size |

### Wizard

| Class | Component |
|-------|-----------|
| `.fi-sc-wizard-header` | Wizard step header |
| `.fi-sc-wizard-header-step` | Individual step |
| `.fi-sc-wizard-header-step-btn` | Step button |
| `.fi-sc-wizard-header-step-icon-ctn` | Step icon container |
| `.fi-active` | Active step state |
| `.fi-completed` | Completed step state |

### Sections

| Class | Component |
|-------|-----------|
| `.fi-section` | Content section |
| `.fi-section-header` | Section header |
| `.fi-section-content` | Section body |
| `.fi-fieldset` | Fieldset group |

---

## Tailwind CSS v4 Compatibility

### Breaking Changes from v3

Tailwind CSS v4 has significant changes that affect Filament styling:

#### 1. No `!important` with `@apply`

```css
/* WRONG - Tailwind v4 */
.my-class {
    @apply text-white !important;
}

/* CORRECT - Use raw CSS */
.my-class {
    color: white !important;
}
```

#### 2. Custom Colors Not Available in `@apply`

If you define custom colors in `tailwind.config.js`, they may not be available in `@apply`:

```css
/* WRONG - Custom color in @apply */
.kanban-stage {
    @apply bg-stage-discovery;
}

/* CORRECT - Use raw CSS value */
.kanban-stage {
    background-color: #f59e0b;  /* Amber */
}
```

#### 3. `@layer` Restrictions

Media queries cannot be nested inside `@layer components` in some configurations:

```css
/* WRONG */
@layer components {
    @media (max-width: 768px) {
        .my-class { ... }
    }
}

/* CORRECT - Move outside @layer */
@media (max-width: 768px) {
    .my-class { ... }
}
```

### Debugging Build Errors

If `npm run build` produces 0-byte CSS files, check for:

1. `@apply ... !important` usage
2. Custom utility classes in `@apply`
3. Syntax errors in CSS

Run build with verbose output:
```bash
npm run build 2>&1 | head -50
```

---

## Theme Customization

### CSS Custom Properties

Define brand colors as CSS variables:

```css
/* resources/css/tcs-brand-tokens.css */
:root {
    --tcs-gold: #D4A574;
    --tcs-gold-dark: #B8935E;

    /* Phase colors */
    --tcs-discovery-500: #3b82f6;
    --tcs-design-500: #8b5cf6;
    --tcs-sourcing-500: #f59e0b;
    --tcs-production-500: #10b981;
    --tcs-delivery-500: #14b8a6;
}
```

### Overriding Filament Components

Target Filament's CSS hooks in your theme file:

```css
/* resources/css/filament/admin/theme.css */
@import '../../../../vendor/filament/filament/resources/css/theme.css';
@import '../../tcs-brand-tokens.css';

/* Override dropdown panel */
.fi-dropdown-panel {
    border-radius: 1.25rem !important;
    background: rgba(255, 255, 255, 0.98) !important;
    backdrop-filter: blur(20px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2) !important;
}

/* Override navigation item hover */
.fi-dropdown-panel .fi-topbar-item:hover {
    background: linear-gradient(145deg, #fef7ed 0%, #fef3c7 100%);
    border-color: var(--tcs-gold);
    transform: translateY(-3px);
}
```

### Dark Mode Support

Filament uses `.dark` class on the root element:

```css
.fi-dropdown-panel {
    background: white;
}

.dark .fi-dropdown-panel {
    background: rgb(17 24 39);
    border-color: rgba(255, 255, 255, 0.1);
}
```

---

## Common Gotchas

### 1. CSS Not Loading After Build

**Symptom**: Styles not appearing despite successful build

**Causes**:
- Old CSS file cached (manifest points to different hash)
- Vite not including theme.css in inputs
- Laravel view cache

**Solutions**:
```bash
# Clear Laravel caches
php artisan view:clear
php artisan cache:clear

# Rebuild assets
npm run build

# Check manifest.json for correct file hashes
cat public/build/manifest.json
```

### 2. Specificity Issues

Filament styles may have higher specificity. Use `!important` sparingly:

```css
/* May not work due to specificity */
.fi-btn {
    background: red;
}

/* Better - more specific selector */
.fi-topbar .fi-btn {
    background: red !important;
}
```

### 3. Icons Not Displaying

**Cause**: Using non-existent icon names

**Solution**: Use Heroicons with proper prefix:
```php
// In AdminPanelProvider.php
NavigationGroup::make()
    ->icon('heroicon-o-squares-2x2')  // Outline
    ->icon('heroicon-s-squares-2x2')  // Solid
```

Available Heroicon prefixes:
- `heroicon-o-*` - Outline (24x24)
- `heroicon-s-*` - Solid (24x24)
- `heroicon-m-*` - Mini (20x20)

### 4. @source Directive

Filament v4 uses `@source` to scan for Tailwind classes:

```css
@source '../../../../app/Filament/**/*';
@source '../../../../resources/views/filament/**/*';
@source '../../../../plugins/webkul/*/resources/views/**/*';
```

If custom classes aren't being compiled, add their paths to `@source`.

---

## TCS-Specific Styling

### Brand Colors

```css
:root {
    --tcs-gold: #D4A574;
    --tcs-gold-dark: #B8935E;
}
```

### Phase Color System

Each project phase has a 5-shade gradient:

| Phase | Base Color | CSS Variable |
|-------|-----------|--------------|
| Discovery | Blue #3B82F6 | `--tcs-discovery-500` |
| Design | Purple #8B5CF6 | `--tcs-design-500` |
| Sourcing | Amber #F59E0B | `--tcs-sourcing-500` |
| Production | Green #10B981 | `--tcs-production-500` |
| Delivery | Teal #14B8A6 | `--tcs-delivery-500` |

### Navigation Menu Styling

The TCS navigation uses card-style items with hover effects:

```css
.fi-dropdown-panel .fi-topbar-item {
    border-radius: 0.875rem;
    background: linear-gradient(145deg, #fafaf9 0%, #f5f5f4 100%);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.fi-dropdown-panel .fi-topbar-item:hover {
    background: linear-gradient(145deg, #fef7ed 0%, #fef3c7 100%);
    border-color: #d4a574;
    transform: translateY(-3px);
    box-shadow: 0 12px 28px -8px rgba(212, 165, 116, 0.35);
}
```

---

## Quick Reference

### Build Commands

```bash
npm run dev      # Development with hot reload
npm run build    # Production build
```

### File Checklist for Styling Changes

1. `resources/css/filament/admin/theme.css` - Add custom styles
2. `vite.config.js` - Ensure theme.css is in inputs
3. `tailwind.config.js` - Add custom colors to safelist if needed
4. Run `npm run build`
5. Clear Laravel cache if needed
6. Deploy to server

### Finding Filament Class Names

Use browser DevTools to inspect elements, or:

```javascript
// In browser console
document.querySelector('.fi-dropdown-panel').className
```

---

## Resources

- [FilamentPHP Documentation](https://filamentphp.com/docs)
- [Tailwind CSS v4 Documentation](https://tailwindcss.com/docs)
- [Heroicons](https://heroicons.com/)
