# PDF Canvas Viewer Component

Universal component for displaying PDFs with canvas rendering using PDF.js.

## Location
`resources/views/components/pdf-canvas-viewer.blade.php`

## Features
- ✅ PDF.js canvas rendering (high quality)
- ✅ Page-by-page navigation
- ✅ Loading states and error handling
- ✅ Support for images (fallback)
- ✅ Customizable height and controls
- ✅ Document info display

## Usage

### Basic Usage
```blade
<x-pdf-canvas-viewer :document="$pdfDocument" />
```

### With Custom Height
```blade
<x-pdf-canvas-viewer
    :document="$pdfDocument"
    height="500px"
/>
```

### Without Navigation Controls
```blade
<x-pdf-canvas-viewer
    :document="$pdfDocument"
    :showControls="false"
/>
```

### Minimal Display (No Info Footer)
```blade
<x-pdf-canvas-viewer
    :document="$pdfDocument"
    :showDocumentInfo="false"
/>
```

### Full Customization
```blade
<x-pdf-canvas-viewer
    :document="$pdfDocument"
    height="600px"
    :showControls="true"
    :showDocumentInfo="true"
/>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `document` | PdfDocument | required | The PDF document model instance |
| `height` | string | '700px' | Height of the viewer container |
| `showControls` | boolean | true | Show Previous/Next navigation |
| `showDocumentInfo` | boolean | true | Show document name and metadata |

## Document Model Requirements

The component expects a document object with these properties:
- `file_path` - Path to the PDF file in storage
- `mime_type` - File MIME type (e.g., 'application/pdf')
- `file_name` - Display name of the file
- `page_count` - Total number of pages
- `document_type` - Optional type badge (e.g., 'drawing', 'blueprint')
- `notes` - Optional notes to display

## Examples

### In a Livewire Component
```php
// Component class
public $document;

public function mount($documentId)
{
    $this->document = PdfDocument::findOrFail($documentId);
}

// Blade view
<x-pdf-canvas-viewer :document="$document" />
```

### In a FilamentPHP Infolist
```php
ViewEntry::make('primary_reference')
    ->view('filament.infolists.components.primary-reference-gallery')
    ->state(fn ($record) => $record->primaryPdf)
```

### In a Regular Blade View
```blade
@php
    $document = \App\Models\PdfDocument::where('is_primary_reference', true)->first();
@endphp

@if($document)
    <x-pdf-canvas-viewer :document="$document" />
@endif
```

### In a Modal
```blade
<x-filament::modal id="pdf-viewer">
    <x-slot name="heading">
        Project Plans
    </x-slot>

    <x-pdf-canvas-viewer
        :document="$selectedDocument"
        height="80vh"
    />
</x-filament::modal>
```

## Dependencies

The component requires PDF.js to be loaded. It's automatically included via:
```blade
@vite('resources/js/annotations.js')
```

This is handled automatically by the component using `@once` directive.

## Technical Details

### Canvas Rendering
- Uses PDF.js `getDocument()` and `render()` API
- Automatically scales to fit container width
- Cleans up memory after each render
- Handles page changes asynchronously

### Performance
- Only renders the current page (not all pages at once)
- Destroys PDF document after rendering to free memory
- Uses Alpine.js for reactive state management

### Browser Compatibility
Works in all modern browsers that support:
- HTML5 Canvas
- ES6 Promises
- Alpine.js

## Troubleshooting

### PDF not displaying
- Ensure PDF.js is loaded (`window.pdfjsLib` exists)
- Check browser console for errors
- Verify file path is accessible via Storage::disk()

### Loading spinner stuck
- Check PDF file size (very large files take longer)
- Verify PDF is not corrupted
- Check network tab for 404 errors

### Page navigation not working
- Ensure `page_count` is set correctly on document model
- Check console for JavaScript errors
- Verify Alpine.js is initialized

## Related Files
- `/resources/js/annotations.js` - PDF.js loader
- `/plugins/webkul/projects/resources/views/filament/components/pdf-annotation-viewer.blade.php` - Full annotation system
- `/app/Models/PdfDocument.php` - Document model
