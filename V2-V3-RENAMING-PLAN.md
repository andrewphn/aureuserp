# V2/V3 Naming Cleanup Plan

## Current Situation

There's confusion between "V2" and "V3" annotation systems. The current working version is actually called "V2" but uses the "V3" components internally.

## Proposed Solution

Rename everything to use a single, clear naming scheme: **just remove version numbers** or use **"annotate"** as the canonical name.

## Files to Rename/Update

### 1. PHP Files

**Class: `AnnotatePdfV2`** → **`AnnotatePdf`**
```
plugins/webkul/projects/src/Filament/Resources/ProjectResource/Pages/AnnotatePdfV2.php
→ AnnotatePdf.php
```

**Changes needed:**
```php
// Before
class AnnotatePdfV2 extends Page implements HasForms

// After
class AnnotatePdf extends Page implements HasForms
```

### 2. Blade Templates

**File: `annotate-pdf-v2.blade.php`** → **`annotate-pdf.blade.php`**
```
plugins/webkul/projects/resources/views/filament/pages/annotate-pdf-v2.blade.php
→ annotate-pdf.blade.php
```

**Component: `pdf-annotation-viewer-v3-overlay.blade.php`** → **`pdf-annotation-viewer.blade.php`**
```
plugins/webkul/projects/resources/views/filament/components/pdf-annotation-viewer-v3-overlay.blade.php
→ pdf-annotation-viewer.blade.php
```

### 3. Routes

**Route name:** `'annotate-v2'` → `'annotate'`

In `ProjectResource.php`:
```php
// Before
'annotate-v2' => AnnotatePdfV2::route('/{record}/annotate-v2/{page?}'),

// After
'annotate' => AnnotatePdf::route('/{record}/annotate/{page?}'),
```

**New URLs:**
- Before: `http://aureuserp.test/admin/project/projects/1/annotate-v2/1?pdf=1`
- After: `http://aureuserp.test/admin/project/projects/1/annotate/1?pdf=1`

### 4. Alpine Component Names

Keep `annotationSystemV3` as is in the blade file, OR rename to `annotationSystem`:

```javascript
// Option 1: Keep as is (internally it's fine)
Alpine.data('annotationSystemV3', (config) => ({...}))

// Option 2: Rename for clarity
Alpine.data('annotationSystem', (config) => ({...}))
```

### 5. Page Titles/Headings

In `AnnotatePdf.php`:
```php
// Before
public function getTitle(): string | Htmlable
{
    return "Annotate Page {$this->pageNumber} (V3 Overlay System)";
}

// After
public function getTitle(): string | Htmlable
{
    return "Annotate Page {$this->pageNumber}";
}
```

### 6. References in Other Files

Update imports and references:
```php
// Before
use Webkul\Project\Filament\Resources\ProjectResource\Pages\AnnotatePdfV2;

// After
use Webkul\Project\Filament\Resources\ProjectResource\Pages\AnnotatePdf;
```

## Migration Steps

### Step 1: Create New Files (Don't Delete Old Ones Yet)

1. Copy `AnnotatePdfV2.php` → `AnnotatePdf.php`
2. Copy `annotate-pdf-v2.blade.php` → `annotate-pdf.blade.php`
3. Copy `pdf-annotation-viewer-v3-overlay.blade.php` → `pdf-annotation-viewer.blade.php`

### Step 2: Update New Files

1. Rename class in `AnnotatePdf.php`
2. Update view path in `AnnotatePdf.php`
3. Update includes in `annotate-pdf.blade.php`
4. Update component calls in `pdf-annotation-viewer.blade.php`
5. Remove version references from titles/labels

### Step 3: Update Routes

1. Add new route `'annotate'` in `ProjectResource.php`
2. Keep old `'annotate-v2'` route temporarily for backwards compatibility

### Step 4: Update All References

1. Search for all files importing `AnnotatePdfV2`
2. Update to import `AnnotatePdf`
3. Update any URL builders using `annotate-v2` → `annotate`

### Step 5: Test

1. Test new route works: `/admin/project/projects/1/annotate/1?pdf=1`
2. Test all annotation features still work
3. Test backwards compatibility with old route

### Step 6: Deprecate Old Routes

1. Add redirect from `annotate-v2` to `annotate`
2. Log deprecation warning
3. Update documentation

### Step 7: Cleanup (Future)

After migration period:
1. Delete `AnnotatePdfV2.php`
2. Delete `annotate-pdf-v2.blade.php`
3. Remove old route
4. Remove redirect

## Backwards Compatibility

Keep old routes working with redirect:
```php
// In ProjectResource.php
public static function getPages(): array
{
    return [
        // New canonical route
        'annotate' => AnnotatePdf::route('/{record}/annotate/{page?}'),

        // Old route - redirects to new one
        'annotate-v2' => AnnotatePdfV2Legacy::route('/{record}/annotate-v2/{page?}'),
    ];
}
```

## Benefits

1. ✅ **Clearer naming**: No confusion about versions
2. ✅ **Easier onboarding**: New developers don't wonder "where's V1?"
3. ✅ **Better URLs**: `/annotate` is cleaner than `/annotate-v2`
4. ✅ **Future-proof**: When you improve the system, you don't need "V4"
5. ✅ **Less technical debt**: Removes version number artifacts

## Testing Checklist

- [ ] New route loads PDF viewer correctly
- [ ] Can create rooms and locations
- [ ] Can draw annotations (Location, Cabinet Run, Cabinet)
- [ ] Annotations save to database
- [ ] Annotations load after page refresh
- [ ] Can edit annotations
- [ ] Can delete annotations
- [ ] Tree view works (By Room / By Page)
- [ ] Zoom controls work
- [ ] Pagination works
- [ ] All existing annotations still visible

## Timeline

**Phase 1 (Now):** Create new files and routes alongside old ones
**Phase 2 (1 week):** Test and verify everything works
**Phase 3 (2 weeks):** Update all internal links to use new routes
**Phase 4 (1 month):** Deprecate old routes with redirect
**Phase 5 (3 months):** Remove old files completely

## Decision

**Recommendation:** Keep `V3` in the Alpine component name internally (`annotationSystemV3`) but remove all version numbers from:
- URLs (`/annotate` instead of `/annotate-v2`)
- Page titles ("Annotate Page 1" instead of "Annotate Page 1 (V3 Overlay System)")
- Class names (`AnnotatePdf` instead of `AnnotatePdfV2`)
- File names (where practical)

This gives us a clean external interface while maintaining internal component naming for JavaScript code.
