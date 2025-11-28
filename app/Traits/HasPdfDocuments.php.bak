<?php

namespace App\Traits;

use App\Models\PdfDocument;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait HasPdfDocuments
{
    /**
     * Get all PDF documents for this model
     */
    public function pdfDocuments(): MorphMany
    {
        return $this->morphMany(PdfDocument::class, 'module', 'module_type', 'module_id');
    }

    /**
     * Get documents by category
     */
    public function getDocumentsByCategory(?int $categoryId = null): Collection
    {
        $query = $this->pdfDocuments();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->get();
    }

    /**
     * Get documents by folder
     */
    public function getDocumentsByFolder(?int $folderId = null): Collection
    {
        $query = $this->pdfDocuments();

        if ($folderId) {
            $query->where('folder_id', $folderId);
        }

        return $query->get();
    }

    /**
     * Get documents by tag
     */
    public function getDocumentsByTag(string $tag): Collection
    {
        return $this->pdfDocuments()
            ->whereJsonContains('tags', $tag)
            ->get();
    }

    /**
     * Count total documents
     */
    public function getDocumentsCount(): int
    {
        return $this->pdfDocuments()->count();
    }

    /**
     * Count documents by category
     */
    public function getDocumentsCountByCategory(int $categoryId): int
    {
        return $this->pdfDocuments()
            ->where('category_id', $categoryId)
            ->count();
    }

    /**
     * Attach a PDF document to this model
     */
    public function attachPdfDocument(array $attributes): PdfDocument
    {
        // Ensure uploaded_by is set
        if (!isset($attributes['uploaded_by'])) {
            $attributes['uploaded_by'] = Auth::id();
        }

        return $this->pdfDocuments()->create($attributes);
    }

    /**
     * Detach a PDF document from this model
     */
    public function detachPdfDocument(int $documentId): bool
    {
        $document = $this->pdfDocuments()->find($documentId);

        if (!$document) {
            return false;
        }

        return $document->delete();
    }

    /**
     * Get the most recent document
     */
    public function getLatestDocument(): ?PdfDocument
    {
        return $this->pdfDocuments()
            ->latest()
            ->first();
    }

    /**
     * Check if model has any documents
     */
    public function hasDocuments(): bool
    {
        return $this->pdfDocuments()->exists();
    }

    /**
     * Check if model has a specific document
     */
    public function hasDocument(int $documentId): bool
    {
        return $this->pdfDocuments()
            ->where('id', $documentId)
            ->exists();
    }

    /**
     * Get public documents only
     */
    public function getPublicDocuments(): Collection
    {
        return $this->pdfDocuments()
            ->where('is_public', true)
            ->get();
    }

    /**
     * Get private documents only
     */
    public function getPrivateDocuments(): Collection
    {
        return $this->pdfDocuments()
            ->where('is_public', false)
            ->get();
    }
}
