# Annotation System V2 - Usage Guide

## Overview

The V2 annotation system provides a completely redesigned PDF annotation workflow with:
- **Context-first approach**: Set room/location context once, draw multiple annotations
- **Tree sidebar**: Visual hierarchy showing Room → Location → Run → Cabinet
- **Smart autocomplete**: Auto-detect existing entities vs create new
- **Auto-naming**: Sequential naming ("Run 1", "Run 2", "Run 3")
- **Persistent context**: No need to fill modal after each annotation

## Enabling V2 System

### Method 1: Environment Variable (Global Default)

Add to `.env`:
```env
ANNOTATION_SYSTEM_VERSION=v2
```

### Method 2: User Preference (Per-User)

Set in user settings:
```php
$user->settings = ['annotation_system_version' => 'v2'];
$user->save();
```

### Checking Current Version

The system will show a **purple "V2" badge** next to the "Annotate" button when V2 is enabled.

## Testing with 25 Friendship Lane Project

### Prerequisites
1. Build assets: `npm run build`
2. Enable V2 system (see above)
3. Navigate to: `/admin/project/projects/1/pdf-review?pdf=1`

### Test Workflow

#### 1. Click "Annotate" button on any page
- Should see V2 interface with:
  - Left sidebar (project tree)
  - Top context bar (Room/Location autocomplete)
  - PDF viewer in center

#### 2. Test Tree Sidebar Loading
- Tree should load automatically showing existing rooms
- Badge counts should display annotation counts
- Click room names to select context

#### 3. Test Room Autocomplete
- Type in "Room" field at top
- Should see dropdown with existing rooms
- Should see "+ Create New" option
- Select "Kitchen" (if exists)

#### 4. Test Location Autocomplete
- After selecting room, "Location" field should activate
- Type location name
- Should see existing locations for that room
- Should see "+ Create New" option
- Select or create "Sink Wall"

#### 5. Test Drawing Mode
- Click "📦 Draw Run" button
- Should enable rectangle drawing in PDF
- Draw multiple rectangles
- Each should auto-label as "Run 1", "Run 2", etc.

#### 6. Test Save
- Click "💾 Save" button
- Should save all annotations
- Tree should refresh showing new entities
- Badge counts should update

### Expected Behavior

✅ **Tree loads with existing project structure**
✅ **Autocomplete suggests existing entities**
✅ **Duplicate detection works** ("Kitchen" vs "kitchen" treated as same)
✅ **Context persists** across multiple drawings
✅ **Auto-naming sequences correctly**
✅ **Tree refreshes** after save
✅ **Badge counts update** after save

### Reverting to V1

Remove from `.env`:
```env
ANNOTATION_SYSTEM_VERSION=v1
```

Or set user preference back to `v1`.

## API Endpoints Used

V2 system uses these new endpoints:

- `GET /api/project/{projectId}/entity-tree` - Load hierarchical tree
- `GET /api/project/{projectId}/rooms` - Get rooms for autocomplete
- `POST /api/project/{projectId}/rooms` - Create new room
- `GET /api/project/room/{roomId}/locations` - Get locations for room
- `POST /api/project/room/{roomId}/locations` - Create new location

## Files Modified/Created

### New Files
- `plugins/webkul/projects/resources/js/annotations/project-tree-sidebar.js`
- `plugins/webkul/projects/resources/js/annotations/context-bar.js`
- `plugins/webkul/projects/resources/views/filament/components/pdf-annotation-viewer-v2.blade.php`
- `public/css/annotation-system-v2.css`
- `app/Http/Controllers/Api/ProjectEntityTreeController.php`

### Modified Files
- `routes/api.php` - Added entity tree routes
- `resources/css/app.css` - Imported V2 CSS
- `plugins/webkul/projects/src/Filament/Resources/ProjectResource/Pages/ReviewPdfAndPrice.php` - Added version check methods
- `plugins/webkul/projects/resources/views/filament/components/pdf-page-thumbnail-pdfjs.blade.php` - Added V2 badge

## Troubleshooting

### Tree not loading
- Check browser console for API errors
- Verify project has rooms: `php artisan tinker` then `\Webkul\Project\Models\Room::count()`
- Check API endpoint: `curl http://aureuserp.test/api/project/1/entity-tree`

### Autocomplete not working
- Clear browser cache
- Check network tab for API calls
- Verify CSRF token is present

### Annotations not saving
- Check save button console logs
- Verify PDF page exists in database
- Check `pdf_page_annotations` table

## Performance Notes

- Tree loads once on modal open (single API call)
- Autocomplete filters client-side (no additional API calls)
- Save operation batches all annotations (single API call)

## Next Steps (Future Enhancements)

- [ ] Drag-and-drop entity reordering in tree
- [ ] Bulk annotation operations
- [ ] Annotation templates
- [ ] Visual annotation badges on PDF
- [ ] Migration tool from V1 annotations
