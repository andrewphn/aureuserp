# PRD 4: Tag Selector - Visual Tag Pills

## Problem
Tags need to be visually distinct and one-click to toggle. Bryan's ADHD requires clear visual states.

## Solution
Color-coded pill buttons with instant toggle.

## Requirements

### Pill Appearance
- Background: Tag color at 20% opacity
- Border: Tag color at 60% opacity, 1.5px solid
- Text: Tag color (full saturation)
- Height: 32px
- Padding: 12px horizontal
- Border radius: 8px
- Font: 13px medium weight

### States
**Unselected**:
- Standard styling
- Hover: Darker border, scale 1.02

**Selected**:
- Checkmark icon (✓) on left
- Background: 40% opacity (darker)
- Border: Solid tag color

**Click**:
- Instant toggle (no animation delay)
- No confirmation needed

### Layout
- Flex wrap with 8px gap
- Max 2 pills per row (panel width 400px)
- Text truncates with "..." if > 180px

## Technical Details

### Alpine.js Toggle
```javascript
toggleTag(tagId) {
    if (this.state.includes(tagId)) {
        this.state = this.state.filter(id => id !== tagId);
    } else {
        this.state.push(tagId);
    }
}
```

### Blade Template
```blade
<button
    type="button"
    @click="toggleTag({{ $tag->id }})"
    :class="{
        'bg-opacity-40 border-2': state.includes({{ $tag->id }}),
        'bg-opacity-20 border': !state.includes({{ $tag->id }})
    }"
    style="background-color: {{ $tag->color }}20; border-color: {{ $tag->color }}; color: {{ $tag->color }};"
>
    <svg x-show="state.includes({{ $tag->id }})" class="w-4 h-4">
        <!-- Checkmark SVG -->
    </svg>
    {{ $tag->name }}
</button>
```

### Color Examples
- Discovery: #3B82F6 (Blue)
- Design: #8B5CF6 (Purple)
- Sourcing: #F59E0B (Amber)
- Production: #10B981 (Green)
- Delivery: #14B8A6 (Teal)

## Success Criteria
- Pills clearly show selected state
- Colors match phase colors exactly
- Toggle is instant (< 50ms)
- Accessible (keyboard navigation)
