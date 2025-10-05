# PRD: ADHD-Friendly Tag Selector Panel

## Problem Statement

Bryan (Owner) needs to quickly add/update project tags while juggling multiple tasks throughout the day. Current dropdown is slow for:
- Finding relevant tags (too much scrolling)
- Understanding tag context (groupings not visual enough)
- Repeated tag selection across many projects (no memory)

**Key Constraint**: Bryan has ADHD - needs "minimal clicks, visual summaries, filtered notifications"

---

## User Persona

**Bryan Patton - Owner**
- Jumps between strategic calls, shop floor QC, project reviews constantly
- Needs to tag 10-20 projects per day
- Can't afford to hunt through nested menus
- Values: Speed > Features, Visual > Text, Smart Defaults > Options

**Usage Pattern**:
- Opens project → Quickly adds phase tags → Saves → Next project
- Common flow: "In Production" stage → Add "Material Prep" + "Rush Production" tags
- Volume: 50-100 tag selections per week

---

## Solution: Smart Tag Selector Panel

### Design Principles (ADHD-Optimized)

1. **Search First** - Large, auto-focused search bar
2. **Visual Hierarchy** - Color-coded pills, clear grouping
3. **Smart Defaults** - Current phase tags always visible
4. **Minimal Clicks** - One-click toggle, no confirmations
5. **Persistent Context** - Panel stays open, shows selections
6. **Memory** - Recent tags accessible instantly

---

## Core Features

### 1. Panel Trigger & Layout
**Trigger**: Click "Select Tags" button → Slide-in panel from right (400px wide)

**Panel Structure**:
```
┌─────────────────────────────────┐
│ [× Close]     Tags               │
├─────────────────────────────────┤
│ 🔍 [Search tags...]              │ ← Auto-focused
├─────────────────────────────────┤
│ ⭐ CURRENT PHASE (Production)    │ ← Highlighted header
│ ┌─────┐ ┌─────┐ ┌─────┐         │
│ │Prep │ │Mill │ │Sand │ ...     │ ← Visual pills
│ └─────┘ └─────┘ └─────┘         │
├─────────────────────────────────┤
│ 🕐 Recent Tags                   │ ← Memory feature
│ ┌────────┐ ┌──────┐             │
│ │Rush    │ │Rework│ ...         │
│ └────────┘ └──────┘             │
├─────────────────────────────────┤
│ ▼ Priority (click to expand)     │ ← Collapsed sections
│ ▼ Health Status                  │
│ ▼ Material Tags                  │
│ ▼ Delivery Phase                 │
├─────────────────────────────────┤
│ ✓ 3 tags selected               │ ← Live counter
└─────────────────────────────────┘
```

### 2. Search Functionality
- **Auto-focus** on panel open
- **Instant filter** - No debounce delay
- **Fuzzy matching** - "mat" matches "Material Prep"
- **Highlight matches** - Yellow background on matched text
- **Clear button** - X icon to reset search

**Search Behavior**:
- Type "rush" → Shows "Rush Production" pill immediately
- Type "material" → Shows all material-related tags from any phase
- Empty search → Shows default view (current phase + recent)

### 3. Tag Pills (Visual Design)
**Pill Appearance**:
- Background: Tag color at 20% opacity
- Border: Tag color at 60% opacity, 1.5px solid
- Text: Tag color (full saturation)
- Height: 32px
- Padding: 12px horizontal
- Border radius: 8px
- Font: 13px medium weight

**States**:
- **Unselected**: Standard appearance, hover shows darker border
- **Selected**: Checkmark icon (✓) on left, darker background (40% opacity)
- **Hover**: Scale 1.02, darker border
- **Click**: Instant toggle (no animation delay)

**Layout**:
- Flex wrap, 8px gap between pills
- Max 2 pills per row (panel width ~400px)
- Each pill max-width: 180px, text truncates with "..."

### 4. Current Phase Section
**Always Visible** (no collapse):
- Header: "⭐ CURRENT PHASE → [Phase Icon] [Phase Name]"
- Header color: Matches phase color
- Shows all tags for current stage (Discovery/Design/Sourcing/Production/Delivery)
- If project has no stage: Shows "🎯 Utility Tags" instead

**Logic**:
- If stage_id = 13 (Discovery) → Show all `phase_discovery` tags
- If stage_id = 14 (Design) → Show all `phase_design` tags
- If stage_id = 15 (Sourcing) → Show all `phase_sourcing` tags
- If stage_id = 16 (Production) → Show all `phase_production` tags
- If stage_id = 17 (Delivery) → Show all `phase_delivery` tags
- Else → Show priority + health tags

### 5. Recent Tags Section
**Memory Feature**:
- Shows last 5 unique tags used across ALL projects (user-specific)
- Stored in browser localStorage: `tcs_recent_tags_user_{user_id}`
- Updates on tag selection (adds to front, removes duplicates)
- Format: `[{id: 47, name: "50% Deposit", color: "#6D28D9"}, ...]`

**Display**:
- Header: "🕐 Recent Tags"
- Horizontal scroll if > 2 tags
- Same pill styling as other tags
- Click to toggle selection

### 6. Collapsed Category Sections
**Categories** (collapsed by default):
- 🎯 Priority (5 tags)
- 💚 Health Status (6 tags)
- ⚠️ Risk Factors (5 tags)
- 📊 Complexity (3 tags)
- 🔨 Work Scope (13 tags)
- 🔍 Discovery Phase (10 tags) - Only if NOT current phase
- 🎨 Design Phase (13 tags) - Only if NOT current phase
- 📦 Sourcing Phase (24 tags) - Only if NOT current phase
- ⚙️ Production Phase (18 tags) - Only if NOT current phase
- 🚚 Delivery Phase (14 tags) - Only if NOT current phase
- ⭐ Special Status (8 tags)
- 🔄 Lifecycle (12 tags)

**Expand/Collapse**:
- Click header to toggle
- Chevron icon rotates 90° when expanded
- Smooth height animation (150ms)
- State persists during panel session (not across page loads)

### 7. Selection Counter
**Bottom Bar**:
- Sticky position at bottom of panel
- Shows: "✓ [N] tags selected" or "No tags selected"
- Updates instantly on tag toggle
- If > 5 tags: Shows warning icon "⚠️ Many tags - consider simplifying"

---

## Technical Implementation

### FilamentPHP Integration

**Custom Form Component**: `TagSelectorPanel`
Location: `app/Forms/Components/TagSelectorPanel.php`

**Blade View**: `resources/views/forms/components/tag-selector-panel.blade.php`

**Alpine.js State**:
```javascript
{
    open: false,
    search: '',
    selectedTags: @entangle('data.tags'), // Two-way binding
    recentTags: [],
    expandedSections: {},

    init() {
        this.loadRecentTags();
        this.$watch('selectedTags', () => this.updateRecentTags());
    },

    toggleTag(tagId) {
        if (this.selectedTags.includes(tagId)) {
            this.selectedTags = this.selectedTags.filter(id => id !== tagId);
        } else {
            this.selectedTags.push(tagId);
        }
    },

    loadRecentTags() {
        const stored = localStorage.getItem('tcs_recent_tags_user_{{ auth()->id() }}');
        this.recentTags = stored ? JSON.parse(stored) : [];
    },

    updateRecentTags() {
        // Logic to update recent tags in localStorage
    }
}
```

**PHP Component Class**:
```php
class TagSelectorPanel extends Field
{
    protected string $view = 'forms.components.tag-selector-panel';

    public function getCurrentPhaseTags(): Collection
    {
        $stageId = $this->getState()['stage_id'] ?? null;
        $phaseType = $this->getPhaseTypeFromStage($stageId);

        return Tag::where('type', $phaseType)->get();
    }

    public function getAllTagsGrouped(): Collection
    {
        return Tag::all()->groupBy('type');
    }
}
```

### Database Queries

**Optimized Loading**:
```php
// Single query to load all tags with grouping
$tags = Tag::select('id', 'name', 'type', 'color')
    ->orderBy('type')
    ->orderBy('name')
    ->get()
    ->groupBy('type');

// Current phase tags (already filtered)
$currentPhaseTags = $tags[$currentPhaseType] ?? collect();

// Selected tag IDs (from project relationship)
$selectedTagIds = $project->tags->pluck('id')->toArray();
```

### Performance Considerations

1. **Lazy Load**: Only load panel content when opened (not on page load)
2. **Cache Tags**: Store all tags in Livewire component property (avoid re-querying)
3. **Debounce Search**: Actually NO debounce - instant filter for ADHD needs
4. **Virtual Scroll**: If > 50 tags in a section, use virtual scrolling
5. **Minimal Repaints**: Use CSS transforms for animations, not layout changes

---

## UX Flows

### Flow 1: Quick Tag (Current Phase)
1. User opens project in Production stage
2. Clicks "Select Tags" button
3. Panel slides in, search auto-focused
4. "⭐ CURRENT PHASE → Production" section shows 18 production tags
5. User clicks "Material Prep" pill (turns selected)
6. Clicks "Rush Production" pill (turns selected)
7. Counter shows "✓ 2 tags selected"
8. User clicks anywhere outside panel → Panel closes, tags saved
9. Sticky footer updates to show 2 new tags

**Time**: < 5 seconds

### Flow 2: Search for Specific Tag
1. User needs to add "Material Shortage" tag (from Sourcing phase)
2. Opens panel, search is auto-focused
3. Types "material" (6 keystrokes)
4. All material-related tags appear immediately (cross-phase)
5. User clicks "Material Shortage" pill
6. Clicks outside → Panel closes, tag saved

**Time**: < 3 seconds

### Flow 3: Reuse Recent Tag
1. User opens new project
2. Clicks "Select Tags"
3. Panel opens, shows "🕐 Recent Tags" with "Rush Production"
4. User clicks "Rush Production" pill (from recent section)
5. Clicks outside → Tag saved

**Time**: < 2 seconds

### Flow 4: Browse for Tag
1. User wants to add risk tag but doesn't know exact name
2. Opens panel
3. Clicks "▼ Risk Factors" header
4. Section expands, shows 5 risk tags
5. Clicks "Budget Risk" pill
6. Clicks outside → Tag saved

**Time**: < 4 seconds

---

## Success Metrics

**Speed**:
- Average tag selection time: < 5 seconds (vs. 15 seconds with dropdown)
- 80% of tag selections via current phase or recent tags (no scrolling)

**Adoption**:
- Bryan uses panel for 90%+ of tag additions (vs. dropdown)
- Zero complaints about "too many clicks"

**Efficiency**:
- Recent tags feature used in 40%+ of sessions
- Search used in 30% of sessions (for cross-phase tags)

---

## Edge Cases

1. **No Current Phase**: If project has no stage_id → Show Priority + Health tags in top section
2. **Empty Search**: If search yields zero results → Show "No tags match '[search term]'" message
3. **All Tags Selected**: If user selects all tags in a category → Show checkmark on category header
4. **Panel Overflow**: If > 200 tags total → Implement virtual scrolling per section
5. **Mobile View**: Panel becomes full-screen modal on screens < 768px
6. **Keyboard Navigation**: Tab through pills, Enter to toggle, Escape to close panel

---

## Implementation Checklist

- [ ] Create `TagSelectorPanel.php` form component
- [ ] Create `tag-selector-panel.blade.php` view
- [ ] Implement Alpine.js panel state management
- [ ] Add localStorage for recent tags (user-specific)
- [ ] Style tag pills with phase colors
- [ ] Implement search filter logic
- [ ] Add expand/collapse animations for categories
- [ ] Wire up two-way binding with Filament form
- [ ] Test with Bryan's workflow (10 projects, rapid tagging)
- [ ] Add keyboard navigation
- [ ] Mobile responsive breakpoint
- [ ] Replace existing Select field in ProjectResource.php

---

## Future Enhancements (Post-MVP)

1. **Bulk Tag Actions**: "Add to all [stage] projects" button
2. **Tag Presets**: Save common tag combinations ("Rush Commercial", "High-End Residential")
3. **Smart Suggestions**: ML-based tag recommendations based on project type/customer
4. **Tag Analytics**: Show tag usage stats per phase ("Most used in Production: Material Prep")
5. **Custom Tag Colors**: Allow Bryan to override tag colors per project
6. **Tag Dependencies**: Auto-select related tags (e.g., "Rush Production" → "Priority: High")

---

## Design Mockup Reference

See: `/docs/designs/tag-selector-panel-mockup.png` (to be created)

Color palette:
- Panel background: `#FFFFFF` (light) / `#1F2937` (dark)
- Section headers: `#6B7280` text
- Search bar: `#F3F4F6` background
- Selected pill glow: Tag color at 20% opacity shadow
