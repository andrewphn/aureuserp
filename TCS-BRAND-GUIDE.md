# TCS Woodwork Brand Design System

## Overview
This document defines the design tokens, color palette, typography, and spacing system for TCS Woodwork's AureusERP implementation, aligned with the brand identity from tcswoodwork.com.

## Brand Values
- **Craftsmanship**: Meticulous attention to detail
- **Heritage**: Time-honored woodworking traditions
- **Precision**: Exact specifications and quality
- **Natural Materials**: Wood-inspired aesthetics

---

## Color Palette

### Primary Brand Colors

#### TCS Gold (Primary)
The signature brand color representing warmth, craftsmanship, and premium quality.

- **Main**: `#D4A574` - Primary gold
- **Dark**: `#B8935E` - Darker gold accent
- **Light**: `#F5E6D3` - Soft cream

**Usage:**
- Primary buttons and CTAs
- Brand accents and highlights
- Key metrics and important data
- Active/selected states

### Wood Tone Palette

#### Walnut Brown
Rich, dark wood tones for depth and sophistication.

- **Primary**: `#5C4033`
- **Light**: `#6B4E3D`
- **Dark**: `#4A3328`

**Usage:**
- Secondary cards and containers
- Alternate metric displays
- Navigation elements
- Dark mode accents

#### Maple Cream
Light, warm wood tones for backgrounds and subtle elements.

- **Primary**: `#F5E6D3`
- **Variants**: `#EDD9BE`, `#E5CBA9`

**Usage:**
- Light backgrounds
- Subtle containers
- Hover states
- Light mode surfaces

### Accent Colors

#### Forest Green
Natural, earthy accent for balance and growth.

- **Primary**: `#2D5016`
- **Light**: `#3D6B20`
- **Soft**: `#4D8327`

**Usage:**
- Success states
- Environmental/sustainability features
- Natural material indicators
- Secondary actions

---

## Typography

### Font Stack
```css
/* Display & Body */
font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;

/* Monospace (code/data) */
font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
```

### Type Scale

| Use Case | Size | Weight | Line Height |
|----------|------|--------|-------------|
| Hero Text | 48px (3rem) | 800 | 1.25 |
| Display | 36px (2.25rem) | 700 | 1.25 |
| H1 | 30px (1.875rem) | 700 | 1.25 |
| H2 | 24px (1.5rem) | 600 | 1.25 |
| H3 | 20px (1.25rem) | 600 | 1.5 |
| Large Body | 18px (1.125rem) | 500 | 1.5 |
| Body | 16px (1rem) | 400 | 1.5 |
| Small Body | 14px (0.875rem) | 400 | 1.5 |
| Labels | 12px (0.75rem) | 600 | 1.25 |

### Font Weights
- **Normal**: 400 (body text)
- **Medium**: 500 (emphasized text)
- **Semibold**: 600 (headings, labels)
- **Bold**: 700 (major headings)
- **Extrabold**: 800 (display text)

### Letter Spacing
- **Tight**: -0.025em (large headings)
- **Normal**: 0 (body text)
- **Wide**: 0.025em (subheadings)
- **Wider**: 0.05em (buttons, labels)
- **Widest**: 0.1em (small caps, badges)

---

## Spacing System

### Base Unit: 4px (0.25rem)

| Token | Value | Use Case |
|-------|-------|----------|
| `space-1` | 4px | Tight spacing, icon gaps |
| `space-2` | 8px | Small gaps, compact layouts |
| `space-3` | 12px | Default inline spacing |
| `space-4` | 16px | Standard spacing |
| `space-5` | 20px | Comfortable spacing |
| `space-6` | 24px | Large spacing |
| `space-8` | 32px | Section spacing |
| `space-10` | 40px | Large sections |
| `space-12` | 48px | Major sections |
| `space-16` | 64px | Hero sections |

### Spacing Principles
1. **Consistent rhythm**: Use multiples of 4px
2. **Optical balance**: Increase spacing for larger elements
3. **Breathing room**: Don't crowd important elements
4. **Visual hierarchy**: More space = more importance

---

## Border Radius

Inspired by woodworking's clean lines and precise edges:

| Token | Value | Use Case |
|-------|-------|----------|
| `none` | 0 | Sharp edges, tables |
| `sm` | 4px | Subtle rounding |
| `base` | 8px | Buttons, inputs |
| `md` | 12px | Small cards |
| `lg` | 16px | Cards, containers |
| `xl` | 24px | Feature cards |
| `2xl` | 32px | Hero elements |
| `full` | 9999px | Pills, avatars |

---

## Shadows & Elevation

### Standard Shadows
- **sm**: Subtle lift for inputs
- **base**: Default cards
- **md**: Elevated cards
- **lg**: Modals, popovers
- **xl**: Major overlays
- **2xl**: Hero sections

### Brand-Specific Shadows
```css
/* Gold glow for highlights */
--tcs-shadow-gold: 0 0 0 4px rgba(212, 165, 116, 0.2);
--tcs-shadow-gold-lg: 0 0 0 6px rgba(212, 165, 116, 0.15);
```

---

## Gradients

### Primary Gradients
```css
/* Gold - Premium feel */
background: linear-gradient(135deg, #D4A574 0%, #C9995F 100%);

/* Dark Gold - Rich accent */
background: linear-gradient(135deg, #B8935E 0%, #A67F4A 100%);

/* Walnut - Sophisticated depth */
background: linear-gradient(135deg, #6B4E3D 0%, #5C4033 100%);

/* Forest - Natural accent */
background: linear-gradient(135deg, #3D6B20 0%, #2D5016 100%);
```

### Gradient Direction
- **135deg**: Standard diagonal (top-left to bottom-right)
- **to bottom**: Vertical gradients for headers
- **to right**: Horizontal progress indicators

---

## Component Patterns

### Metric Cards (Production Estimate Example)

**Hours Card** - Primary Gold
```css
background: linear-gradient(135deg, #D4A574 0%, #C9995F 100%);
padding: 1.5rem;
border-radius: 1rem;
box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
```

**Days Card** - Dark Gold
```css
background: linear-gradient(135deg, #B8935E 0%, #A67F4A 100%);
```

**Weeks Card** - Walnut Brown
```css
background: linear-gradient(135deg, #6B4E3D 0%, #5C4033 100%);
```

**Months Card** - Forest Green
```css
background: linear-gradient(135deg, #3D6B20 0%, #2D5016 100%);
```

### Alert System Colors

Based on FilamentPHP status colors with TCS theming:

- **Success** (Green): Comfortable timelines, achieved goals
- **Warning** (Amber): Slight pressure, attention needed
- **Danger** (Red): Extreme pressure, critical
- **Error** (Black/Charcoal): Impossible, system errors

---

## Implementation

### CSS Variables
All design tokens are available as CSS custom properties:

```css
/* Colors */
var(--tcs-gold-400)
var(--tcs-walnut-600)
var(--tcs-forest-600)

/* Typography */
var(--tcs-font-display)
var(--tcs-text-2xl)
var(--tcs-font-bold)

/* Spacing */
var(--tcs-space-6)

/* Effects */
var(--tcs-shadow-lg)
var(--tcs-gradient-gold)
```

### Utility Classes
Quick-use classes for common patterns:

```css
/* Backgrounds */
.bg-tcs-gold
.bg-tcs-walnut
.bg-tcs-forest

/* Text */
.text-tcs-gold
.text-tcs-walnut

/* Gradients */
.bg-gradient-tcs-gold
.bg-gradient-tcs-walnut
```

---

## Usage Guidelines

### Do's ✅
- Use TCS Gold for primary actions and key metrics
- Maintain consistent spacing using the 4px grid
- Apply wood tones for secondary elements
- Use gradients sparingly for premium feel
- Ensure sufficient contrast for accessibility

### Don'ts ❌
- Don't mix generic blues with brand colors
- Don't use overly bright or neon colors
- Don't ignore the spacing system
- Don't use too many gradients on one page
- Don't compromise readability for aesthetics

---

## Accessibility

### Contrast Requirements
- **Text on Gold (#D4A574)**: Use white text
- **Text on Walnut (#5C4033)**: Use white text
- **Text on Forest (#2D5016)**: Use white text
- **Text on Maple (#F5E6D3)**: Use dark text

### Minimum Sizes
- Touch targets: 44×44px minimum
- Body text: 16px minimum
- Small text: 14px minimum (with proper contrast)

---

## File Locations

### Design Tokens
- **CSS Variables**: `/resources/css/tcs-brand-tokens.css`
- **Theme File**: `/resources/css/filament/admin/theme.css`
- **Panel Config**: `/app/Providers/Filament/AdminPanelProvider.php`

### Component Examples
- **Production Estimate**: `/resources/views/filament/forms/components/production-estimate-card.blade.php`
- **Project Type Cards**: `/resources/css/filament/admin/theme.css` (lines 6-51)

---

## Version
**Version**: 1.0.0
**Last Updated**: 2025
**Maintained by**: TCS Development Team
