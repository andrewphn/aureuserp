<?php

namespace Webkul\Project\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class AnnotationEditor extends Component
{
    public bool $showModal = false;

    // Form fields
    public ?string $label = '';
    public ?string $notes = '';
    public ?string $measurementWidth = null;
    public ?string $measurementHeight = null;

    // Context data for display
    public ?string $annotationType = null;
    public ?string $roomName = null;
    public ?string $locationName = null;

    // Store original annotation for updates (must be public for Livewire serialization)
    public ?array $originalAnnotation = null;

    public function render()
    {
        return view('webkul-project::livewire.annotation-editor');
    }

    #[On('edit-annotation')]
    public function handleEditAnnotation(array $annotation): void
    {
        $this->originalAnnotation = $annotation;

        // Extract context for display
        $this->annotationType = $annotation['type'] ?? null;
        $this->roomName = $annotation['roomName'] ?? null;
        $this->locationName = $annotation['locationName'] ?? null;

        // Fill form fields
        $this->label = $annotation['label'] ?? '';
        $this->notes = $annotation['notes'] ?? '';
        $this->measurementWidth = $annotation['measurementWidth'] ?? null;
        $this->measurementHeight = $annotation['measurementHeight'] ?? null;

        $this->showModal = true;
    }

    public function save(): void
    {
        // Basic validation
        if (empty($this->label)) {
            \Filament\Notifications\Notification::make()
                ->title('Validation Error')
                ->body('Label is required.')
                ->danger()
                ->send();
            return;
        }

        // Update annotation with form data
        $updatedAnnotation = array_merge($this->originalAnnotation, [
            'label' => $this->label,
            'notes' => $this->notes ?? '',
            'measurementWidth' => $this->measurementWidth ?? null,
            'measurementHeight' => $this->measurementHeight ?? null,
        ]);

        // Dispatch event back to Alpine.js
        $this->dispatch('annotation-updated', annotation: $updatedAnnotation);

        // Show success notification
        \Filament\Notifications\Notification::make()
            ->title('Annotation Updated')
            ->body('The annotation details have been saved.')
            ->success()
            ->send();

        // Close modal
        $this->close();
    }

    public function cancel(): void
    {
        $this->close();
    }

    private function close(): void
    {
        $this->showModal = false;
        $this->reset(['label', 'notes', 'measurementWidth', 'measurementHeight', 'annotationType', 'roomName', 'locationName']);
        $this->originalAnnotation = null;
    }
}
