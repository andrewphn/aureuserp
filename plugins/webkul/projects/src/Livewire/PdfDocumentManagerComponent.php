<?php

namespace Webkul\Project\Livewire;

use App\Models\PdfDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithFileUploads;
use Webkul\Project\Models\Project;
use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;

/**
 * PDF Document Manager Component
 *
 * Provides document management functionality for projects.
 * Supports document type categorization, versioning, notes, and tags.
 */
class PdfDocumentManagerComponent extends Component
{
    use WithFileUploads;

    /**
     * The project ID - reactive to parent changes
     */
    #[Reactive]
    public ?int $projectId = null;

    /**
     * Filter by document type
     */
    public ?string $filterDocumentType = null;

    /**
     * ID of document being edited in slide-over modal
     */
    public ?int $editingDocumentId = null;

    /**
     * Slide-over modal open state
     */
    public bool $showEditModal = false;

    /**
     * Upload modal open state
     */
    public bool $showUploadModal = false;

    /**
     * Delete confirmation modal state
     */
    public bool $showDeleteModal = false;
    public ?int $deletingDocumentId = null;
    public bool $clearEntitiesOnDelete = false;

    /**
     * Edit form fields
     */
    public string $editNotes = '';
    public ?string $editDocumentType = null;
    public array $editTags = [];
    public bool $editIsPrimaryReference = false;

    /**
     * Upload form fields
     */
    public $uploadFile = null;
    public string $uploadNotes = '';
    public ?string $uploadDocumentType = null;
    public array $uploadTags = [];

    /**
     * Available document types for categorization
     */
    public array $documentTypes = [
        'architectural' => 'Architectural Drawings',
        'elevation' => 'Elevations',
        'floor_plan' => 'Floor Plans',
        'detail' => 'Detail Drawings',
        'specification' => 'Specifications',
        'proposal' => 'Proposals',
        'contract' => 'Contracts',
        'invoice' => 'Invoices',
        'permit' => 'Permits',
        'other' => 'Other',
    ];

    /**
     * Mount the component
     */
    public function mount(?int $projectId = null): void
    {
        $this->projectId = $projectId;
    }

    /**
     * Get project's PDF documents
     */
    #[Computed]
    public function documents(): Collection
    {
        if (! $this->projectId) {
            return collect();
        }

        $query = PdfDocument::query()
            ->forModule(Project::class, $this->projectId)
            ->where('is_latest_version', true)
            ->orderByDesc('is_primary_reference')
            ->orderBy('created_at', 'desc');

        if ($this->filterDocumentType) {
            $query->where('document_type', $this->filterDocumentType);
        }

        return $query->get();
    }

    /**
     * Filter documents by type
     */
    public function filterByType(?string $type): void
    {
        $this->filterDocumentType = $type;
        unset($this->documents);
    }

    /**
     * Open upload modal
     */
    public function openUploadModal(): void
    {
        $this->uploadFile = null;
        $this->uploadNotes = '';
        $this->uploadDocumentType = null;
        $this->uploadTags = [];
        $this->showUploadModal = true;
    }

    /**
     * Close upload modal
     */
    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->uploadFile = null;
        $this->uploadNotes = '';
        $this->uploadDocumentType = null;
        $this->uploadTags = [];
    }

    /**
     * Upload a new document
     */
    public function uploadDocument(): void
    {
        $this->validate([
            'uploadFile' => 'required|file|mimes:pdf|max:51200', // 50MB max
        ]);

        if (! $this->projectId || ! $this->uploadFile) {
            return;
        }

        $file = $this->uploadFile;

        // Store the file
        $path = $file->store('pdf-documents/' . $this->projectId, 'public');

        if ($path) {
            // Extract page count and dimensions from PDF using Smalot\PdfParser
            $pageCount = 1;
            $pageData = [];
            try {
                $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
                if (file_exists($fullPath)) {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($fullPath);
                    $pages = $pdf->getPages();
                    $pageCount = count($pages);

                    // Extract page dimensions for each page
                    foreach ($pages as $index => $page) {
                        $details = $page->getDetails();
                        $pageData[] = [
                            'page_number' => $index + 1,
                            'width' => $details['MediaBox'][2] ?? null,
                            'height' => $details['MediaBox'][3] ?? null,
                            'rotation' => $details['Rotate'] ?? 0,
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Could not extract page count from PDF: ' . $e->getMessage());
            }

            $pdfDocument = PdfDocument::create([
                'module_type' => Project::class,
                'module_id' => $this->projectId,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'page_count' => $pageCount,
                'version_number' => 1,
                'is_latest_version' => true,
                'is_primary_reference' => false,
                'document_type' => $this->uploadDocumentType,
                'notes' => $this->uploadNotes ?: null,
                'uploaded_by' => auth()->id(),
                'tags' => $this->uploadTags ?: [],
            ]);

            // Create PdfPage records for each page
            if ($pageCount > 0 && count($pageData) > 0) {
                foreach ($pageData as $page) {
                    PdfPage::create([
                        'document_id' => $pdfDocument->id,
                        'page_number' => $page['page_number'],
                        'width' => $page['width'],
                        'height' => $page['height'],
                        'rotation' => $page['rotation'],
                    ]);
                }
                \Log::info("Created {$pageCount} PdfPage records for document {$pdfDocument->id}");
            }

            unset($this->documents);
            $this->closeUploadModal();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('Document uploaded successfully with ' . $pageCount . ' pages.'),
            ]);
        }
    }

    /**
     * Open the edit slide-over modal for a document
     */
    public function openEditor(int $documentId): void
    {
        $document = PdfDocument::find($documentId);

        if (! $document || $document->module_id !== $this->projectId || $document->module_type !== Project::class) {
            return;
        }

        $this->editingDocumentId = $documentId;
        $this->editNotes = $document->notes ?? '';
        $this->editDocumentType = $document->document_type;
        $this->editTags = $document->tags ?? [];
        $this->editIsPrimaryReference = $document->is_primary_reference ?? false;
        $this->showEditModal = true;
    }

    /**
     * Close the edit slide-over modal
     */
    public function closeEditor(): void
    {
        $this->showEditModal = false;
        $this->editingDocumentId = null;
        $this->editNotes = '';
        $this->editDocumentType = null;
        $this->editTags = [];
        $this->editIsPrimaryReference = false;
    }

    /**
     * Save document metadata from slide-over modal
     */
    public function saveDocumentMetadata(): void
    {
        if (! $this->editingDocumentId) {
            return;
        }

        $document = PdfDocument::find($this->editingDocumentId);

        if (! $document || $document->module_id !== $this->projectId) {
            return;
        }

        // If setting as primary reference, unset others
        if ($this->editIsPrimaryReference && ! $document->is_primary_reference) {
            PdfDocument::forModule(Project::class, $this->projectId)
                ->where('is_primary_reference', true)
                ->update(['is_primary_reference' => false]);
        }

        $document->update([
            'notes' => $this->editNotes ?: null,
            'document_type' => $this->editDocumentType,
            'tags' => $this->editTags,
            'is_primary_reference' => $this->editIsPrimaryReference,
        ]);

        unset($this->documents);
        $this->closeEditor();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Document updated successfully.'),
        ]);
    }

    /**
     * Upload a new version of a document
     */
    public function uploadNewVersion(int $documentId): void
    {
        // This would open a modal for uploading a new version
        // The new version would link to the previous via previous_version_id
        $this->dispatch('open-version-upload-modal', documentId: $documentId);
    }

    /**
     * Open delete confirmation modal
     */
    public function confirmDelete(int $documentId): void
    {
        $this->deletingDocumentId = $documentId;
        $this->clearEntitiesOnDelete = false;
        $this->showDeleteModal = true;
    }

    /**
     * Close delete confirmation modal
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingDocumentId = null;
        $this->clearEntitiesOnDelete = false;
    }

    /**
     * Get entity counts for document being deleted
     */
    #[Computed]
    public function deletingDocumentEntityCounts(): array
    {
        if (! $this->deletingDocumentId) {
            return ['annotations' => 0, 'roomRefs' => 0, 'cabinetRefs' => 0];
        }

        $document = PdfDocument::find($this->deletingDocumentId);
        if (! $document) {
            return ['annotations' => 0, 'roomRefs' => 0, 'cabinetRefs' => 0];
        }

        $pageIds = $document->pages()->pluck('id');

        return [
            'annotations' => PdfPageAnnotation::whereIn('pdf_page_id', $pageIds)->count(),
            'roomRefs' => PdfPageAnnotation::whereIn('pdf_page_id', $pageIds)
                ->where(function ($q) {
                    $q->whereNotNull('room_id')
                      ->orWhereNotNull('room_location_id');
                })->count(),
            'cabinetRefs' => PdfPageAnnotation::whereIn('pdf_page_id', $pageIds)
                ->where(function ($q) {
                    $q->whereNotNull('cabinet_id')
                      ->orWhereNotNull('cabinet_run_id');
                })->count(),
        ];
    }

    /**
     * Delete a document (called from confirmation modal)
     */
    public function deleteDocument(): void
    {
        if (! $this->deletingDocumentId) {
            return;
        }

        $document = PdfDocument::find($this->deletingDocumentId);

        if (! $document || $document->module_id !== $this->projectId) {
            $this->closeDeleteModal();
            return;
        }

        // If clearing entities, hard delete annotations and their entity refs
        if ($this->clearEntitiesOnDelete) {
            $pageIds = $document->pages()->pluck('id');
            // Force delete annotations (removes room/cabinet refs)
            PdfPageAnnotation::whereIn('pdf_page_id', $pageIds)->forceDelete();
        }

        // Delete the file from storage
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();
        unset($this->documents);

        if ($this->editingDocumentId === $this->deletingDocumentId) {
            $this->closeEditor();
        }

        $this->closeDeleteModal();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->clearEntitiesOnDelete
                ? __('Document and all entity references deleted.')
                : __('Document deleted. Entity references preserved.'),
        ]);
    }

    /**
     * Download a document
     */
    public function downloadDocument(int $documentId): mixed
    {
        $document = PdfDocument::find($documentId);

        if (! $document || $document->module_id !== $this->projectId) {
            return null;
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    /**
     * Set a document as primary reference
     */
    public function setPrimaryReference(int $documentId): void
    {
        $document = PdfDocument::find($documentId);

        if (! $document || $document->module_id !== $this->projectId) {
            return;
        }

        // Unset current primary
        PdfDocument::forModule(Project::class, $this->projectId)
            ->where('is_primary_reference', true)
            ->update(['is_primary_reference' => false]);

        // Set new primary
        $document->update(['is_primary_reference' => true]);

        unset($this->documents);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Primary reference document updated.'),
        ]);
    }

    /**
     * Listen for project ID updates from parent
     */
    #[On('project-id-updated')]
    public function handleProjectIdUpdate(int $projectId): void
    {
        $this->projectId = $projectId;
        $this->filterDocumentType = null;
        unset($this->documents);
    }

    /**
     * Get the currently editing document
     */
    #[Computed]
    public function editingDocument(): ?PdfDocument
    {
        if (! $this->editingDocumentId) {
            return null;
        }

        return PdfDocument::find($this->editingDocumentId);
    }

    /**
     * Get document type counts for filter badges
     */
    #[Computed]
    public function documentTypeCounts(): array
    {
        if (! $this->projectId) {
            return [];
        }

        return PdfDocument::query()
            ->forModule(Project::class, $this->projectId)
            ->where('is_latest_version', true)
            ->whereNotNull('document_type')
            ->selectRaw('document_type, COUNT(*) as count')
            ->groupBy('document_type')
            ->pluck('count', 'document_type')
            ->toArray();
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('webkul-project::livewire.pdf-document-manager');
    }
}
