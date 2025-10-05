# TCS Woodwork ERP - Design System

## Design Philosophy
Match the clean, minimal aesthetic of tcswoodwork.com while maintaining FilamentPHP functionality.

## Styling Approach

### CSS Architecture
- **Framework**: Tailwind CSS v4 with `@layer components`
- **Method**: Use `@apply` directives in `resources/css/filament/admin/theme.css`
- **Location**: All custom styles in `@layer components { }` block
- **Build**: Vite processes theme.css → outputs to `public/build/assets/theme-[hash].css`

### Dark Mode Support
- **Enabled**: `->darkMode()` in AdminPanelProvider.php
- **All styles**: Include `dark:` variants for proper theme switching
- **User control**: Toggle available in user menu
- **Logo support**: Different logos for light/dark modes configured

### Design Principles from tcswoodwork.com

#### Form Elements
- **Inputs**: Clean white backgrounds with subtle gray borders
- **Focus States**: Minimal, elegant focus indicators (not heavy rings)
- **Spacing**: Generous padding and whitespace
- **Typography**: Clean, readable font sizes
- **Checkboxes/Radio**: Card-style with borders, not heavy backgrounds

#### Colors
- **Primary Brand**: Amber/Gold (#D4A574) for accents only
- **Backgrounds**: White (`bg-white`) and light gray (`bg-gray-50`)
- **Borders**: Light gray (`border-gray-200`, `border-gray-300`)
- **Text**: Dark gray (`text-gray-900`) for headers, medium gray for body

#### Form Sections
- **Container**: White background with subtle shadow
- **Headers**: Simple border-bottom, not heavy colored bars
- **Fields**: Minimal borders, focus on content not decoration

## Implementation Pattern

### Step 1: Define in theme.css
```css
@layer components {
    .custom-component {
        @apply border rounded-lg p-4;
    }
}
```

### Step 2: Build Assets
```bash
npm run build
```

### Step 3: Verify Output
- Check `public/build/manifest.json` for new hash
- Verify styles in browser

## Current Customizations

### Project Type Cards
- Card-style checkboxes with borders
- Amber highlight when selected
- Located in `.fi-fo-checkbox-list-options.project-type-cards`

### TCS Metric Cards
- Reusable component for production metrics
- Color-coded backgrounds (amber, brown, green)
- Large typography for emphasis

### Form Enhancements
- Section headers with amber bottom border
- Input fields with rounded corners
- Enhanced focus states with amber accent
- Card-style radio/checkbox options
- Better spacing and visual hierarchy

## File Locations
- **Theme CSS**: `/resources/css/filament/admin/theme.css`
- **Build Output**: `/public/build/assets/theme-[hash].css`
- **Manifest**: `/public/build/manifest.json`
- **Vite Config**: `/vite.config.js`
