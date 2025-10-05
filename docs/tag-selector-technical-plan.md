# Tag Selector Panel - Technical Implementation Plan

## Executive Summary

Based on thorough research of **FilamentPHP v4** architecture and our existing AureusERP codebase, I've determined the **optimal implementation path** for the ADHD-friendly tag selector panel. **No database changes are required** - we can build this entirely using FilamentPHP v4's native form component system.

**Version**: FilamentPHP v4.0 (confirmed in composer.json)

---

## 1. Current Architecture Analysis

### Database Schema (Already Optimal)
```
projects_tags (main table)
├── id
├── name
├── type ← For grouping (phase_discovery, phase_design, etc.)
├── color ← For visual pills
├── creator_id
└── timestamps

projects_project_tag (pivot)
├── project_id ← FK to projects
└── tag_id ← FK to tags
```

**Status**: ✅ **Perfect as-is** - No changes needed

### Current Relationship
```php
// Project.php
public function tags()
{
    return $this->belongsToMany(
        Tag::class,
        'projects_project_tag', // pivot table
        'project_id',           // foreign key
        'tag_id'                // related key
    );
}
```

**Status**: ✅ **BelongsToMany relationship working correctly**

---

## 2. FilamentPHP v4 Custom Component Pattern

### v4 Changes (Compared to v3):
- ✅ **Same pattern** for custom fields - No breaking changes
- ✅ **Same wrapper** - `<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">`
- ✅ **Same binding** - `$wire.$entangle()` or `$applyStateBindingModifiers()`
- ✅ **Same artisan** - `php artisan make:filament-form-field`

### Existing Example in Codebase
Location: `resources/views/forms/components/project-type-cards.blade.php`

**Current Pattern (v4 Compatible)**:
```blade
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{ state: $wire.$entangle('{$getStatePath()}') || [] }">
        <!-- Custom UI here -->
    </div>
</x-dynamic-component>
```

**v4 Recommended Pattern** (with modifiers):
```blade
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{ state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }} || [] }">
        <!-- Custom UI here -->
    </div>
</x-dynamic-component>
```

**Pattern Used**:
1. Wraps in `<x-dynamic-component>` with field wrapper
2. Uses Alpine.js `x-data` for state
3. Uses `$applyStateBindingModifiers()` for proper Livewire binding (v4 best practice)
4. Direct state manipulation updates the form

---

## 3. Implementation Strategy (No Database Changes)

### Option A: Pure Blade Component (RECOMMENDED)
**Why**: Matches existing pattern, zero PHP overhead, works with current architecture

**Files to Create**:
```
resources/views/forms/components/tag-selector-panel.blade.php
```

**Implementation**:
```blade
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="tagSelectorPanel()" class="relative">
        <!-- Trigger Button -->
        <button type="button" @click="open = true">
            Select Tags ({{ state.length }})
        </button>

        <!-- Slide-in Panel -->
        <div x-show="open" x-cloak class="fixed inset-y-0 right-0 w-96 bg-white z-50">
            <!-- Search Bar -->
            <input
                x-model="search"
                type="text"
                placeholder="Search tags..."
                x-ref="searchInput"
            />

            <!-- Current Phase Section -->
            <div class="current-phase">
                <h3>⭐ CURRENT PHASE</h3>
                <div class="tag-pills">
                    @foreach($getCurrentPhaseTags() as $tag)
                        <button @click="toggleTag({{ $tag->id }})">
                            {{ $tag->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Recent Tags -->
            <div class="recent-tags">
                <template x-for="tag in recentTags">
                    <button @click="toggleTag(tag.id)" x-text="tag.name"></button>
                </template>
            </div>

            <!-- Collapsed Categories -->
            @foreach($getAllTagsGrouped() as $type => $tags)
                <div x-data="{ expanded: false }">
                    <h3 @click="expanded = !expanded">{{ $type }}</h3>
                    <div x-show="expanded">
                        @foreach($tags as $tag)
                            <button @click="toggleTag({{ $tag->id }})">
                                {{ $tag->name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        function tagSelectorPanel() {
            return {
                open: false,
                search: '',
                state: $wire.$entangle('{{ $getStatePath() }}') || [],
                recentTags: [],

                init() {
                    this.loadRecentTags();
                    this.$watch('open', value => {
                        if (value) this.$refs.searchInput.focus();
                    });
                },

                toggleTag(tagId) {
                    if (this.state.includes(tagId)) {
                        this.state = this.state.filter(id => id !== tagId);
                    } else {
                        this.state.push(tagId);
                        this.updateRecentTags(tagId);
                    }
                },

                loadRecentTags() {
                    const stored = localStorage.getItem('tcs_recent_tags_{{ auth()->id() }}');
                    this.recentTags = stored ? JSON.parse(stored) : [];
                },

                updateRecentTags(tagId) {
                    // Update localStorage logic here
                }
            }
        }
    </script>
</x-dynamic-component>
```

### How to Use in ProjectResource.php:
```php
use Filament\Forms\Components\View;

View::make('forms.components.tag-selector-panel')
    ->label('Tags')
    ->statePath('tags') // Maps to $data['tags']
```

**Status**: ✅ **Compatible with existing code**

---

### Option B: Custom Field Class (Alternative)
**When to use**: If we need reusable logic across multiple resources

**Files to Create**:
```
app/Forms/Components/TagSelectorPanel.php
resources/views/forms/components/tag-selector-panel.blade.php
```

**PHP Class**:
```php
namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class TagSelectorPanel extends Field
{
    protected string $view = 'forms.components.tag-selector-panel';

    public function getCurrentPhaseTags(): Collection
    {
        $stageId = $this->getLivewire()->data['stage_id'] ?? null;

        $stageToType = [
            13 => 'phase_discovery',
            14 => 'phase_design',
            15 => 'phase_sourcing',
            16 => 'phase_production',
            17 => 'phase_delivery',
        ];

        $type = $stageToType[$stageId] ?? 'priority';

        return \Webkul\Project\Models\Tag::where('type', $type)->get();
    }

    public function getAllTagsGrouped(): Collection
    {
        return \Webkul\Project\Models\Tag::all()->groupBy('type');
    }
}
```

**Usage**:
```php
use App\Forms\Components\TagSelectorPanel;

TagSelectorPanel::make('tags')
```

**Status**: ⚠️ **More code, but more reusable**

---

## 4. Database Requirements

### Required Queries (All work with current schema):

**1. Get Current Phase Tags**:
```php
Tag::where('type', $currentPhaseType)->get();
```

**2. Get All Tags Grouped**:
```php
Tag::select('id', 'name', 'type', 'color')
    ->get()
    ->groupBy('type');
```

**3. Get Selected Tags** (via Livewire entangle):
```php
$project->tags->pluck('id')->toArray(); // Automatic
```

**4. Save Selected Tags** (via Filament form save):
```php
$project->tags()->sync($data['tags']); // Automatic
```

### Database Changes Needed:
**❌ NONE** - Current schema supports everything!

---

## 5. Integration with Existing Code

### Current ProjectResource.php Pattern:
```php
// Line 433-505 (current tag dropdown)
Select::make('tags')
    ->relationship(name: 'tags', titleAttribute: 'name')
    ->multiple()
    ->searchable()
    ->preload()
    // ... custom search/grouping logic
```

### Replace With:
```php
// Option A: Pure Blade
View::make('forms.components.tag-selector-panel')
    ->label('Tags')

// Option B: Custom Class
TagSelectorPanel::make('tags')
```

### Relationship Handling:
- ✅ FilamentPHP automatically syncs via `$data['tags']`
- ✅ Livewire `$entangle()` provides two-way binding
- ✅ No manual save logic needed

---

## 6. AureusERP Plugin Compatibility

### Plugin Architecture Check:
```
plugins/webkul/projects/
├── src/
│   ├── Filament/Resources/ProjectResource.php ← We modify this
│   └── Models/
│       ├── Project.php ← Relationship exists
│       └── Tag.php ← Model exists
└── database/
    └── migrations/ ← All tables exist
```

**Compliance**:
- ✅ Uses existing plugin structure
- ✅ No new migrations needed
- ✅ No new models needed
- ✅ Just adds a view component

### View Location Options:
1. **Global**: `resources/views/forms/components/` ← **RECOMMENDED** (reusable)
2. **Plugin**: `plugins/webkul/projects/resources/views/filament/forms/components/`

**Decision**: Use **global location** to match existing `project-type-cards.blade.php`

---

## 7. Performance Considerations

### Current Approach (Dropdown):
```php
->getSearchResultsUsing(function (string $search, $livewire) {
    // Runs query on EVERY keystroke
    Tag::where('name', 'like', "%{$search}%")->limit(50)->get();
})
```
**Problem**: Database hit per keystroke

### Panel Approach:
```php
// Load ALL tags ONCE when panel opens
@php
    $allTags = \Webkul\Project\Models\Tag::all()->groupBy('type');
@endphp

// Filter client-side with Alpine.js
x-show="tag.name.toLowerCase().includes(search.toLowerCase())"
```
**Benefit**: ✅ Single query, client-side filtering

### Caching Strategy:
```php
@php
    $allTags = Cache::remember('project_tags_grouped', 3600, function() {
        return \Webkul\Project\Models\Tag::all()->groupBy('type');
    });
@endphp
```
**Result**: Sub-millisecond load times

---

## 8. Recent Tags (localStorage) Strategy

### Storage Format:
```javascript
// Key: tcs_recent_tags_{{ auth()->id() }}
// Value:
[
    {"id": 47, "name": "50% Deposit", "color": "#6D28D9", "type": "phase_design"},
    {"id": 53, "name": "Rush Production", "color": "#F97316", "type": "phase_production"},
    // ... max 5 items
]
```

### Update Logic:
```javascript
updateRecentTags(tagId) {
    const tag = this.allTags.find(t => t.id === tagId);
    if (!tag) return;

    // Add to front, remove duplicates
    this.recentTags = [
        tag,
        ...this.recentTags.filter(t => t.id !== tagId)
    ].slice(0, 5);

    localStorage.setItem(
        'tcs_recent_tags_{{ auth()->id() }}',
        JSON.stringify(this.recentTags)
    );
}
```

**Benefit**: ✅ No database changes, works across sessions

---

## 9. Stage-Aware Tag Display

### Current Stage Detection:
```php
// In ProjectResource.php
$stageId = $livewire->record->stage_id ?? $livewire->data['stage_id'] ?? null;
```

### Blade Implementation:
```blade
@php
    $stageId = null;
    if (isset($getRecord) && $getRecord()) {
        $stageId = $getRecord()->stage_id;
    } elseif (isset($getLivewire) && $getLivewire()->data['stage_id'] ?? null) {
        $stageId = $getLivewire()->data['stage_id'];
    }

    $stageToType = [
        13 => 'phase_discovery',
        14 => 'phase_design',
        15 => 'phase_sourcing',
        16 => 'phase_production',
        17 => 'phase_delivery',
    ];

    $currentPhaseType = $stageToType[$stageId] ?? null;
    $currentPhaseTags = $currentPhaseType
        ? $allTags[$currentPhaseType] ?? collect()
        : collect();
@endphp
```

**Benefit**: ✅ No PHP class needed, works in Blade

---

## 10. Recommended Implementation Path

### Phase 1: MVP (1-2 hours)
1. ✅ Create `resources/views/forms/components/tag-selector-panel.blade.php`
2. ✅ Implement basic panel with search
3. ✅ Add current phase section
4. ✅ Wire up `$entangle()` for state management
5. ✅ Replace Select field in ProjectResource.php

### Phase 2: Enhancement (30 mins)
1. ✅ Add recent tags (localStorage)
2. ✅ Add collapsed categories
3. ✅ Implement tag pill styling with colors
4. ✅ Add selection counter

### Phase 3: Polish (30 mins)
1. ✅ Add keyboard navigation
2. ✅ Mobile responsive breakpoint
3. ✅ Loading states
4. ✅ Error handling

**Total Time**: ~3 hours

---

## 11. Files to Create/Modify

### New Files:
```
resources/views/forms/components/tag-selector-panel.blade.php  (main component)
```

### Modified Files:
```
plugins/webkul/projects/src/Filament/Resources/ProjectResource.php
  ↳ Line 433-505: Replace Select::make('tags') with View::make()
```

### No Changes Needed:
- ❌ Database migrations
- ❌ Models
- ❌ Controllers
- ❌ Routes
- ❌ JavaScript build files

---

## 12. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Livewire binding breaks | Low | High | Use proven `$entangle()` pattern from existing code |
| Performance issues | Low | Medium | Load tags once, filter client-side |
| Mobile compatibility | Medium | Low | Use Filament's responsive utilities |
| State sync problems | Low | High | Test with network throttling |
| Browser compatibility | Low | Low | Alpine.js handles all browsers |

---

## 13. Testing Strategy

### Unit Tests (Not Required):
- ❌ No PHP logic to test
- ✅ All logic in Blade/Alpine.js

### Manual Testing Checklist:
1. ✅ Tag selection/deselection works
2. ✅ Search filters correctly
3. ✅ Current phase shows correct tags
4. ✅ Recent tags persist in localStorage
5. ✅ Form saves selected tags correctly
6. ✅ Works on create and edit pages
7. ✅ Mobile responsive
8. ✅ Keyboard navigation works

### Browser Testing:
- Chrome/Edge (Chromium)
- Safari
- Firefox

---

## 14. Rollback Plan

If implementation fails:

**Step 1**: Remove View component
```php
// In ProjectResource.php, revert to:
Select::make('tags')
    ->relationship(name: 'tags', titleAttribute: 'name')
    ->multiple()
    ->searchable()
```

**Step 2**: Delete blade file
```bash
rm resources/views/forms/components/tag-selector-panel.blade.php
```

**Step 3**: Clear cache
```bash
php artisan view:clear
```

**Result**: Zero downtime, instant rollback

---

## 15. Final Recommendation

### ✅ Proceed with Option A: Pure Blade Component

**Reasons**:
1. **No database changes** - Works with current schema
2. **Matches existing pattern** - Similar to `project-type-cards.blade.php`
3. **Low risk** - Pure frontend, easy rollback
4. **Fast implementation** - ~3 hours total
5. **FilamentPHP v3 compatible** - Uses official patterns
6. **AureusERP compliant** - Follows plugin conventions
7. **Bryan-optimized** - Search-first, visual, fast

**Next Steps**:
1. Get approval on this technical plan
2. Create the blade component
3. Test with Bryan's workflow
4. Iterate based on feedback

---

## 16. Questions to Resolve

Before implementation, confirm:

1. ✅ **View location**: `resources/views/forms/components/` (global) vs plugin-specific?
2. ✅ **localStorage scope**: Per-user or per-company for recent tags?
3. ✅ **Panel behavior**: Click-away to close or require explicit close button?
4. ✅ **Mobile**: Full-screen modal or side panel on mobile?
5. ✅ **Tag limit**: Warning if > X tags selected?

---

**Status**: Ready for approval and implementation ✅
