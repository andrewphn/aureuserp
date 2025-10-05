# PRD 2: Tag Selector - Search & Filter

## Problem
Bryan needs instant search to find tags across 131 options. ADHD requires zero delay.

## Solution
Auto-focused search with client-side instant filtering.

## Requirements

### Search Bar
- Position: Top of panel, always visible
- Auto-focus: When panel opens
- Placeholder: "Search tags..."
- Clear button: X icon to reset

### Filtering Logic
- **Instant**: No debounce delay
- **Client-side**: Filter 131 tags in-memory
- **Fuzzy**: Match partial strings
- **Highlight**: Yellow background on matched text

### Search Behavior
```javascript
// Filter tags instantly
tags.filter(tag =>
    tag.name.toLowerCase().includes(search.toLowerCase())
)
```

### Visual Feedback
- Show/hide tags based on search
- Display "No results for '[term]'" if empty
- Show tag count: "Showing X of 131 tags"

## Technical Details

### Alpine.js Implementation
```javascript
{
    search: '',

    get filteredTags() {
        if (!this.search) return this.allTags;

        return this.allTags.filter(tag =>
            tag.name.toLowerCase().includes(this.search.toLowerCase())
        );
    }
}
```

### Performance
- Load all 131 tags once (cached)
- Filter happens client-side
- Target: < 16ms (60fps)

## Success Criteria
- Search responds in < 50ms
- Finds tags across all categories
- Clear button resets instantly
- Works with keyboard (Escape to clear)
