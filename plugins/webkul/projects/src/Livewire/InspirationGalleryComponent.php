<?php

namespace Webkul\Project\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithFileUploads;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectInspirationImage;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\Tag;

/**
 * Inspiration Gallery Component
 *
 * Provides a card-based gallery for managing project inspiration images.
 * Features:
 * - Card layout with image, title, and description visible
 * - Inline quick edit for title/description on cards
 * - Full slide-over modal for complete editing (room, tags, etc.)
 * - Room filtering and batch upload
 */
class InspirationGalleryComponent extends Component
{
    use WithFileUploads;

    /**
     * The project ID - reactive to parent changes
     */
    #[Reactive]
    public ?int $projectId = null;

    /**
     * Currently selected room filter
     */
    public ?int $selectedRoomId = null;

    /**
     * ID of image being edited in slide-over modal
     */
    public ?int $editingImageId = null;

    /**
     * ID of image being quick-edited inline
     */
    public ?int $quickEditImageId = null;

    /**
     * Slide-over modal open state
     */
    public bool $showEditModal = false;

    /**
     * Edit form fields (full slide-over)
     */
    public string $editTitle = '';
    public string $editDescription = '';
    public ?int $editRoomId = null;
    public array $editTags = [];

    /**
     * Quick edit form fields (inline on card)
     */
    public string $quickEditTitle = '';
    public string $quickEditDescription = '';

    /**
     * Pending uploads for batch save
     */
    public array $pendingUploads = [];

    /**
     * Temporary file uploads
     */
    public $newImages = [];

    /**
     * Mount the component
     */
    public function mount(?int $projectId = null): void
    {
        $this->projectId = $projectId;
    }

    /**
     * Get project's inspiration images
     */
    #[Computed]
    public function images(): Collection
    {
        if (! $this->projectId) {
            return collect();
        }

        return ProjectInspirationImage::query()
            ->forProject($this->projectId)
            ->forRoom($this->selectedRoomId)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get project's rooms for filtering
     */
    #[Computed]
    public function rooms(): Collection
    {
        if (! $this->projectId) {
            return collect();
        }

        return Room::query()
            ->where('project_id', $this->projectId)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'room_type']);
    }

    /**
     * Filter images by room
     */
    public function filterByRoom(?int $roomId): void
    {
        $this->selectedRoomId = $roomId;
        unset($this->images);
    }

    /**
     * Open the edit slide-over modal for an image
     */
    public function openEditor(int $imageId): void
    {
        $image = ProjectInspirationImage::find($imageId);

        if (! $image || $image->project_id !== $this->projectId) {
            return;
        }

        $this->editingImageId = $imageId;
        $this->editTitle = $image->title ?? '';
        $this->editDescription = $image->description ?? '';
        $this->editRoomId = $image->room_id;
        $this->editTags = $image->tags ?? [];
        $this->showEditModal = true;
    }

    /**
     * Close the edit slide-over modal
     */
    public function closeEditor(): void
    {
        $this->showEditModal = false;
        $this->editingImageId = null;
        $this->editTitle = '';
        $this->editDescription = '';
        $this->editRoomId = null;
        $this->editTags = [];
    }

    /**
     * Save image metadata from slide-over modal
     */
    public function saveImageMetadata(): void
    {
        if (! $this->editingImageId) {
            return;
        }

        $image = ProjectInspirationImage::find($this->editingImageId);

        if (! $image || $image->project_id !== $this->projectId) {
            return;
        }

        $image->update([
            'title' => $this->editTitle ?: null,
            'description' => $this->editDescription ?: null,
            'room_id' => $this->editRoomId,
            'tags' => $this->editTags,
        ]);

        unset($this->images);
        $this->closeEditor();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Image metadata saved successfully.'),
        ]);
    }

    /**
     * Delete an image
     */
    public function deleteImage(int $imageId): void
    {
        $image = ProjectInspirationImage::find($imageId);

        if (! $image || $image->project_id !== $this->projectId) {
            return;
        }

        // Delete the file from storage
        if ($image->file_path && Storage::disk('public')->exists($image->file_path)) {
            Storage::disk('public')->delete($image->file_path);
        }

        $image->delete();
        unset($this->images);

        if ($this->editingImageId === $imageId) {
            $this->closeEditor();
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Image deleted successfully.'),
        ]);
    }

    /**
     * Update image sort order after drag-drop
     */
    public function updateOrder(array $order): void
    {
        foreach ($order as $index => $imageId) {
            ProjectInspirationImage::where('id', $imageId)
                ->where('project_id', $this->projectId)
                ->update(['sort_order' => $index]);
        }

        unset($this->images);
    }

    /**
     * Handle new image uploads
     */
    public function updatedNewImages(): void
    {
        foreach ($this->newImages as $file) {
            $this->pendingUploads[] = [
                'file' => $file,
                'name' => $file->getClientOriginalName(),
                'preview' => $file->temporaryUrl(),
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'description' => '',
                'room_id' => $this->selectedRoomId,
                'tags' => [],
            ];
        }

        $this->newImages = [];
    }

    /**
     * Remove a pending upload
     */
    public function removePendingUpload(int $index): void
    {
        if (isset($this->pendingUploads[$index])) {
            unset($this->pendingUploads[$index]);
            $this->pendingUploads = array_values($this->pendingUploads);
        }
    }

    /**
     * Update pending upload metadata
     */
    public function updatePendingUpload(int $index, string $field, mixed $value): void
    {
        if (isset($this->pendingUploads[$index])) {
            $this->pendingUploads[$index][$field] = $value;
        }
    }

    /**
     * Save all pending uploads (batch save)
     */
    public function savePendingUploads(): void
    {
        if (! $this->projectId || empty($this->pendingUploads)) {
            return;
        }

        $savedCount = 0;
        $maxSortOrder = ProjectInspirationImage::forProject($this->projectId)->max('sort_order') ?? 0;

        foreach ($this->pendingUploads as $upload) {
            $file = $upload['file'];

            // Store the file
            $path = $file->store('inspiration-images/' . $this->projectId, 'public');

            if ($path) {
                // Get image dimensions
                $dimensions = @getimagesize($file->getRealPath());

                ProjectInspirationImage::create([
                    'project_id' => $this->projectId,
                    'room_id' => $upload['room_id'],
                    'file_name' => $upload['name'],
                    'title' => $upload['title'] ?: null,
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'width' => $dimensions[0] ?? null,
                    'height' => $dimensions[1] ?? null,
                    'uploaded_by' => auth()->id(),
                    'description' => $upload['description'] ?: null,
                    'tags' => $upload['tags'] ?: [],
                    'sort_order' => ++$maxSortOrder,
                ]);

                $savedCount++;
            }
        }

        $this->pendingUploads = [];
        unset($this->images);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __(':count image(s) uploaded successfully.', ['count' => $savedCount]),
        ]);
    }

    /**
     * Discard all pending uploads
     */
    public function discardPendingUploads(): void
    {
        $this->pendingUploads = [];
    }

    /**
     * Listen for project ID updates from parent
     */
    #[On('project-id-updated')]
    public function handleProjectIdUpdate(int $projectId): void
    {
        $this->projectId = $projectId;
        $this->selectedRoomId = null;
        unset($this->images, $this->rooms);
    }

    /**
     * Get the currently editing image
     */
    #[Computed]
    public function editingImage(): ?ProjectInspirationImage
    {
        if (! $this->editingImageId) {
            return null;
        }

        return ProjectInspirationImage::find($this->editingImageId);
    }

    /**
     * Get available tags for selection
     */
    #[Computed]
    public function availableTags(): Collection
    {
        return Tag::query()
            ->where('type', 'image')
            ->orWhereNull('type')
            ->orderBy('name')
            ->get(['id', 'name', 'color']);
    }

    /**
     * Start quick editing an image inline on the card
     */
    public function startQuickEdit(int $imageId): void
    {
        $image = ProjectInspirationImage::find($imageId);

        if (! $image || $image->project_id !== $this->projectId) {
            return;
        }

        $this->quickEditImageId = $imageId;
        $this->quickEditTitle = $image->title ?? '';
        $this->quickEditDescription = $image->description ?? '';
    }

    /**
     * Save quick edit changes (title and description only)
     */
    public function saveQuickEdit(): void
    {
        if (! $this->quickEditImageId) {
            return;
        }

        $image = ProjectInspirationImage::find($this->quickEditImageId);

        if (! $image || $image->project_id !== $this->projectId) {
            return;
        }

        $image->update([
            'title' => $this->quickEditTitle ?: null,
            'description' => $this->quickEditDescription ?: null,
        ]);

        unset($this->images);
        $this->cancelQuickEdit();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Image updated.'),
        ]);
    }

    /**
     * Cancel quick edit mode
     */
    public function cancelQuickEdit(): void
    {
        $this->quickEditImageId = null;
        $this->quickEditTitle = '';
        $this->quickEditDescription = '';
    }

    /**
     * Toggle a tag on the currently editing image
     */
    public function toggleTag(int $tagId): void
    {
        if (in_array($tagId, $this->editTags)) {
            $this->editTags = array_values(array_diff($this->editTags, [$tagId]));
        } else {
            $this->editTags[] = $tagId;
        }
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('webkul-project::livewire.inspiration-gallery');
    }
}
