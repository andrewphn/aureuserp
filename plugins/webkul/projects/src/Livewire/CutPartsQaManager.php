<?php

namespace Webkul\Project\Livewire;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;
use Webkul\Project\Models\CncCutPart;
use Webkul\Project\Models\CncProgramPart;

/**
 * Livewire component for managing cut parts QA
 *
 * Handles pass/fail/recut/comment actions for individual cabinet parts
 */
class CutPartsQaManager extends Component implements HasForms
{
    use InteractsWithForms;

    public ?int $sheetId = null;
    public ?CncProgramPart $sheet = null;

    // Quick add form
    public string $newPartLabel = '';
    public string $newPartType = '';

    // Fail form
    public bool $showFailModal = false;
    public ?int $failingPartId = null;
    public string $failureReason = '';
    public string $failureNotes = '';

    // Comment form
    public bool $showCommentModal = false;
    public ?int $commentingPartId = null;
    public string $commentText = '';

    public function mount(?int $sheetId = null): void
    {
        $this->sheetId = $sheetId;
        $this->loadSheet();
    }

    public function loadSheet(): void
    {
        if ($this->sheetId) {
            $this->sheet = CncProgramPart::with('cutParts', 'cncProgram')->find($this->sheetId);
        }
    }

    #[On('pass-part')]
    public function passPart(int $partId): void
    {
        $part = CncCutPart::find($partId);
        if (!$part) return;

        $part->passInspection();

        Notification::make()
            ->title('Part Passed QA')
            ->body("Part {$part->part_label} passed inspection")
            ->success()
            ->send();

        $this->loadSheet();
    }

    #[On('fail-part')]
    public function openFailModal(int $partId): void
    {
        $this->failingPartId = $partId;
        $this->failureReason = '';
        $this->failureNotes = '';
        $this->showFailModal = true;
    }

    public function submitFailure(): void
    {
        if (!$this->failingPartId || !$this->failureReason) {
            Notification::make()
                ->title('Error')
                ->body('Please select a failure reason')
                ->danger()
                ->send();
            return;
        }

        $part = CncCutPart::find($this->failingPartId);
        if (!$part) return;

        $part->failInspection($this->failureReason, notes: $this->failureNotes ?: null);

        Notification::make()
            ->title('Part Failed QA')
            ->body("Part {$part->part_label} marked as failed")
            ->warning()
            ->send();

        $this->closeFailModal();
        $this->loadSheet();
    }

    public function closeFailModal(): void
    {
        $this->showFailModal = false;
        $this->failingPartId = null;
        $this->failureReason = '';
        $this->failureNotes = '';
    }

    #[On('recut-part')]
    public function recutPart(int $partId): void
    {
        $part = CncCutPart::find($partId);
        if (!$part) return;

        $recut = $part->createRecut();

        Notification::make()
            ->title('Recut Created')
            ->body("Recut part #{$recut->id} created for {$part->part_label}")
            ->success()
            ->send();

        $this->loadSheet();
    }

    #[On('comment-part')]
    public function openCommentModal(int $partId): void
    {
        $this->commentingPartId = $partId;
        $this->commentText = '';
        $this->showCommentModal = true;
    }

    public function submitComment(): void
    {
        if (!$this->commentingPartId || !$this->commentText) {
            Notification::make()
                ->title('Error')
                ->body('Please enter a comment')
                ->danger()
                ->send();
            return;
        }

        $part = CncCutPart::find($this->commentingPartId);
        if (!$part) return;

        $part->addMessage([
            'type' => 'note',
            'body' => $this->commentText,
            'is_internal' => true,
        ]);

        Notification::make()
            ->title('Comment Added')
            ->body("Comment added to {$part->part_label}")
            ->success()
            ->send();

        $this->closeCommentModal();
    }

    public function closeCommentModal(): void
    {
        $this->showCommentModal = false;
        $this->commentingPartId = null;
        $this->commentText = '';
    }

    #[On('add-quick-part')]
    public function addQuickPart(int $sheetId): void
    {
        if (!$this->newPartLabel) {
            Notification::make()
                ->title('Error')
                ->body('Please enter a part label')
                ->danger()
                ->send();
            return;
        }

        $sheet = CncProgramPart::find($sheetId);
        if (!$sheet) return;

        $sheet->cutParts()->create([
            'part_label' => $this->newPartLabel,
            'part_type' => $this->newPartType ?: null,
            'status' => CncCutPart::STATUS_PENDING,
        ]);

        Notification::make()
            ->title('Part Added')
            ->body("Part {$this->newPartLabel} added to sheet")
            ->success()
            ->send();

        $this->newPartLabel = '';
        $this->newPartType = '';
        $this->loadSheet();
    }

    public function markCut(int $partId): void
    {
        $part = CncCutPart::find($partId);
        if (!$part) return;

        $part->markCut();

        Notification::make()
            ->title('Part Marked as Cut')
            ->body("Part {$part->part_label} ready for inspection")
            ->success()
            ->send();

        $this->loadSheet();
    }

    public function markScrapped(int $partId): void
    {
        $part = CncCutPart::find($partId);
        if (!$part) return;

        $part->markScrapped('Cannot be salvaged');

        Notification::make()
            ->title('Part Scrapped')
            ->body("Part {$part->part_label} marked as scrapped")
            ->warning()
            ->send();

        $this->loadSheet();
    }

    public function deletePart(int $partId): void
    {
        $part = CncCutPart::find($partId);
        if (!$part) return;

        $label = $part->part_label;
        $part->delete();

        Notification::make()
            ->title('Part Deleted')
            ->body("Part {$label} removed")
            ->success()
            ->send();

        $this->loadSheet();
    }

    public function getFailureReasonOptions(): array
    {
        return CncCutPart::getFailureReasons();
    }

    public function getPartTypeOptions(): array
    {
        return CncCutPart::getPartTypeOptions();
    }

    public function render()
    {
        return view('webkul-project::livewire.cut-parts-qa-manager', [
            'sheet' => $this->sheet,
            'cutParts' => $this->sheet?->cutParts ?? collect(),
        ]);
    }
}
