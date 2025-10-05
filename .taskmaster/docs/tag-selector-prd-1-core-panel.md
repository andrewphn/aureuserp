# PRD 1: Tag Selector Panel - Core UI Structure

## Problem
Bryan needs a fast, visual tag selector to replace the slow dropdown. Current dropdown requires scrolling through 131 tags.

## Solution
Create a slide-in panel with search-first design.

## Requirements

### Panel Trigger
- Replace existing `Select::make('tags')` in ProjectResource.php (line 433)
- Add button: "Select Tags (X selected)"
- Click opens slide-in panel from right side

### Panel Layout
- Width: 400px
- Position: Fixed, right side of screen
- Z-index: 50 (above form)
- Background: White with shadow
- Close: Click outside or X button

### Core Structure
```
┌─────────────────────┐
│ [× Close]    Tags   │
├─────────────────────┤
│ 🔍 [Search...]      │ ← Auto-focused
├─────────────────────┤
│ Content sections    │
├─────────────────────┤
│ ✓ X tags selected   │ ← Counter
└─────────────────────┘
```

## Technical Details

### File Location
`resources/views/forms/components/tag-selector-panel.blade.php`

### Blade Pattern (FilamentPHP v4)
```blade
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="tagSelectorPanel()">
        <!-- Panel UI -->
    </div>
</x-dynamic-component>
```

### State Binding
```javascript
state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }} || []
```

### Database
- NO changes needed
- Uses existing `projects_project_tag` pivot
- Syncs via Livewire entangle

## Success Criteria
- Panel opens in < 100ms
- Replaces dropdown completely
- Works on create and edit pages
- Mobile responsive (full screen on mobile)
