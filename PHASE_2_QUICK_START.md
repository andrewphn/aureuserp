# Phase 2 Quick Start Guide - FilamentPHP Resources

**Prerequisites**: Phase 1 Complete ✅
**Next Step**: FilamentPHP Resource Implementation

---

## What's Already Done (Phase 1)

### Database Tables
- ✅ `pdf_documents` - Main document storage
- ✅ `pdf_pages` - Individual page data
- ✅ `pdf_annotations` - User annotations
- ✅ `pdf_document_activities` - Activity logging

### Eloquent Models
- ✅ `App\Models\PdfDocument` - Full relationships and scopes
- ✅ `App\Models\PdfPage` - Page management
- ✅ `App\Models\PdfAnnotation` - Annotation handling
- ✅ `App\Models\PdfDocumentActivity` - Activity tracking

### Sample Data
- 5 PDF documents with 42 pages
- 14 annotations across documents
- 47 activity logs
- Run seeder: `php artisan db:seed --class=PdfDocumentSeeder`

---

## Phase 2 Implementation Checklist

### Step 1: Create FilamentPHP Resource
```bash
php artisan make:filament-resource PdfDocument --generate
```

**Configure Resource** (`app/Filament/Resources/PdfDocumentResource.php`):

#### Form Schema
```php
Forms\Components\Select::make('module_type')
    ->options([
        'Partner' => 'Partner',
        'Project' => 'Project',
        'Quotation' => 'Quotation',
    ])
    ->required(),

Forms\Components\Select::make('module_id')
    ->relationship('module', 'name') // Configure based on module_type
    ->required(),

Forms\Components\FileUpload::make('file_path')
    ->label('PDF Document')
    ->acceptedFileTypes(['application/pdf'])
    ->directory('pdfs')
    ->required(),

Forms\Components\TagsInput::make('tags'),

Forms\Components\KeyValue::make('metadata'),
```

#### Table Columns
```php
Tables\Columns\TextColumn::make('file_name')
    ->searchable()
    ->sortable(),

Tables\Columns\TextColumn::make('module_type')
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'Partner' => 'success',
        'Project' => 'info',
        default => 'gray',
    }),

Tables\Columns\TextColumn::make('uploader.name')
    ->label('Uploaded By'),

Tables\Columns\TextColumn::make('page_count')
    ->label('Pages'),

Tables\Columns\TextColumn::make('formatted_file_size')
    ->label('Size'),

Tables\Columns\TagsColumn::make('tags'),

Tables\Columns\TextColumn::make('created_at')
    ->dateTime()
    ->sortable(),
```

#### Filters
```php
Tables\Filters\SelectFilter::make('module_type')
    ->options([
        'Partner' => 'Partner',
        'Project' => 'Project',
        'Quotation' => 'Quotation',
    ]),

Tables\Filters\Filter::make('created_at')
    ->form([
        Forms\Components\DatePicker::make('created_from'),
        Forms\Components\DatePicker::make('created_until'),
    ])
    ->query(function (Builder $query, array $data): Builder {
        return $query
            ->when(
                $data['created_from'],
                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
            )
            ->when(
                $data['created_until'],
                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
            );
    }),
```

---

### Step 2: File Upload Handler

Create a custom action to handle PDF uploads and process pages:

```php
// In PdfDocumentResource.php

use Illuminate\Support\Facades\Storage;
use Spatie\PdfToImage\Pdf; // Install: composer require spatie/pdf-to-image

protected static function afterCreate(PdfDocument $record): void
{
    // Get uploaded file
    $filePath = Storage::path($record->file_path);

    // Get page count
    $pdf = new Pdf($filePath);
    $pageCount = $pdf->getNumberOfPages();

    $record->update(['page_count' => $pageCount]);

    // Create page records
    for ($i = 1; $i <= $pageCount; $i++) {
        $thumbnailPath = "pdfs/thumbnails/{$record->id}/page_{$i}.jpg";

        // Generate thumbnail
        $pdf->setPage($i)
            ->saveImage(Storage::path($thumbnailPath));

        PdfPage::create([
            'document_id' => $record->id,
            'page_number' => $i,
            'thumbnail_path' => $thumbnailPath,
        ]);
    }

    // Log upload activity
    PdfDocumentActivity::log(
        $record->id,
        auth()->id(),
        PdfDocumentActivity::ACTION_UPLOADED
    );
}
```

---

### Step 3: Custom Pages & Components

#### Document Viewer Page
```bash
php artisan make:filament-page ViewPdfDocument --resource=PdfDocumentResource --type=custom
```

**Location**: `app/Filament/Resources/PdfDocumentResource/Pages/ViewPdfDocument.php`

**Blade Template**: `resources/views/filament/resources/pdf-document-resource/pages/view-pdf-document.blade.php`

#### Annotation Component
Create a Livewire component for annotations:

```bash
php artisan make:livewire PdfAnnotator
```

**Features to Implement**:
- Canvas-based PDF rendering
- Annotation tools (highlight, text, drawing)
- Real-time annotation saving
- Multi-user annotation visibility

---

### Step 4: Relations Manager

Create a RelationManager for annotations:

```bash
php artisan make:filament-relation-manager PdfDocumentResource annotations
```

**Configure** (`app/Filament/Resources/PdfDocumentResource/RelationManagers/AnnotationsRelationManager.php`):

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('page_number')
                ->sortable(),
            Tables\Columns\TextColumn::make('annotation_type')
                ->badge(),
            Tables\Columns\TextColumn::make('author.name')
                ->label('Author'),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('annotation_type'),
        ]);
}
```

---

### Step 5: Activity Tracking Widget

Create a widget to display document activities:

```bash
php artisan make:filament-widget DocumentActivitiesWidget --resource=PdfDocumentResource
```

**Display recent activities**:
- Who viewed the document
- Who added annotations
- Who downloaded the document
- Timeline view

---

## Required Packages

Install these packages for Phase 2:

```bash
# PDF processing
composer require spatie/pdf-to-image

# Image manipulation
composer require intervention/image

# OCR (optional, for text extraction)
composer require thiagoalessio/tesseract_ocr
```

---

## Database Queries Examples

### Get document with all related data
```php
$document = PdfDocument::with([
    'pages',
    'annotations.author',
    'activities.user',
    'uploader'
])->find($id);
```

### Get annotations for specific page
```php
$annotations = PdfAnnotation::forPage($pageNumber)
    ->where('document_id', $documentId)
    ->with('author')
    ->get();
```

### Get recent activities
```php
$activities = PdfDocumentActivity::where('document_id', $documentId)
    ->recentActivity(20)
    ->with('user')
    ->get();
```

### Get documents by module
```php
$documents = PdfDocument::forModule('Project', $projectId)
    ->with('uploader')
    ->get();
```

---

## FilamentPHP Best Practices

### 1. Authorization
Add policies for PdfDocument:

```bash
php artisan make:policy PdfDocumentPolicy --model=PdfDocument
```

### 2. Notifications
Send notifications when documents are:
- Uploaded
- Annotated by others
- Shared with users

### 3. Bulk Actions
Add bulk actions:
- Download multiple PDFs
- Delete multiple documents
- Tag multiple documents

### 4. Custom Actions
- Preview document
- Download document
- Share document
- Print document

---

## Testing Checklist

- [ ] Upload PDF and verify page creation
- [ ] View document in viewer
- [ ] Add annotations (highlight, text, drawing)
- [ ] View annotation list
- [ ] Track activities
- [ ] Filter documents by module
- [ ] Search documents
- [ ] Download document
- [ ] Soft delete document
- [ ] Restore deleted document

---

## API Endpoints (Future - Phase 3)

If creating API endpoints:

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('pdf-documents', PdfDocumentController::class);
    Route::get('pdf-documents/{id}/pages', [PdfDocumentController::class, 'pages']);
    Route::get('pdf-documents/{id}/annotations', [PdfDocumentController::class, 'annotations']);
    Route::post('pdf-documents/{id}/annotations', [PdfDocumentController::class, 'addAnnotation']);
});
```

---

## Resources & Documentation

### FilamentPHP Documentation
- [Forms](https://filamentphp.com/docs/3.x/forms/getting-started)
- [Tables](https://filamentphp.com/docs/3.x/tables/getting-started)
- [Resources](https://filamentphp.com/docs/3.x/panels/resources)
- [Custom Pages](https://filamentphp.com/docs/3.x/panels/pages)
- [Relation Managers](https://filamentphp.com/docs/3.x/panels/resources/relation-managers)

### PDF Libraries
- [Spatie PDF to Image](https://github.com/spatie/pdf-to-image)
- [PDF.js](https://mozilla.github.io/pdf.js/) - Frontend PDF rendering
- [Annotator.js](http://annotatorjs.org/) - Annotation library

### Laravel Resources
- [Eloquent Relationships](https://laravel.com/docs/11.x/eloquent-relationships)
- [File Storage](https://laravel.com/docs/11.x/filesystem)
- [Policies](https://laravel.com/docs/11.x/authorization#creating-policies)

---

## Contact & Support

**Phase 1 Developer**: Claude Code
**Phase 1 Completion**: October 1, 2025
**Phase 1 Report**: See `PHASE_1_COMPLETION_REPORT.md`
**Verification Script**: `test-pdf-models.php`

**Ready to start Phase 2!** 🚀
