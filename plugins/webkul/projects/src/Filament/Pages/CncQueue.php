<?php

namespace Webkul\Project\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;
use Webkul\Project\Services\CncProgramService;
use Webkul\Security\Models\User;

/**
 * CNC Queue - Shop Floor Page
 *
 * A touch-friendly interface for CNC operators to view and manage the cutting queue.
 */
class CncQueue extends Page
{
    use HasPageShield;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-queue-list';

    protected string $view = 'webkul-project::filament.pages.cnc-queue';

    protected static ?string $slug = 'cnc-queue';

    protected static ?int $navigationSort = 10;

    public ?int $activePartId = null;

    public static function getNavigationLabel(): string
    {
        return 'CNC Queue';
    }

    public static function getNavigationGroup(): string
    {
        return 'Production';
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = CncProgramPart::where('status', CncProgramPart::STATUS_PENDING)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public function getTitle(): string|Htmlable
    {
        return 'CNC Queue';
    }

    public function getHeading(): string|Htmlable
    {
        return 'CNC Queue';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Shop floor view for CNC operators';
    }

    #[Computed]
    public function pendingParts(): Collection
    {
        return CncProgramPart::with(['cncProgram.project', 'operator'])
            ->where('status', CncProgramPart::STATUS_PENDING)
            ->whereIn('material_status', [CncProgramPart::MATERIAL_READY, CncProgramPart::MATERIAL_RECEIVED, null])
            ->orderBy('cnc_program_id')
            ->orderBy('sheet_number')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function runningParts(): Collection
    {
        return CncProgramPart::with(['cncProgram.project', 'operator'])
            ->where('status', CncProgramPart::STATUS_RUNNING)
            ->orderBy('run_at')
            ->get();
    }

    #[Computed]
    public function pendingMaterialParts(): Collection
    {
        return CncProgramPart::with(['cncProgram.project'])
            ->where('material_status', CncProgramPart::MATERIAL_PENDING)
            ->orderBy('created_at')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'pending' => CncProgramPart::where('status', CncProgramPart::STATUS_PENDING)->count(),
            'running' => CncProgramPart::where('status', CncProgramPart::STATUS_RUNNING)->count(),
            'complete_today' => CncProgramPart::where('status', CncProgramPart::STATUS_COMPLETE)
                ->whereDate('completed_at', today())
                ->count(),
            'pending_material' => CncProgramPart::where('material_status', CncProgramPart::MATERIAL_PENDING)->count(),
        ];
    }

    public function startPart(int $partId): void
    {
        $part = CncProgramPart::find($partId);

        if (!$part) {
            Notification::make()
                ->title('Part not found')
                ->danger()
                ->send();
            return;
        }

        if ($part->startRunning()) {
            Notification::make()
                ->title('Part Started')
                ->body("Started: {$part->file_name}")
                ->success()
                ->send();

            $this->activePartId = $partId;
        } else {
            Notification::make()
                ->title('Cannot start part')
                ->body('Part is not in pending status')
                ->danger()
                ->send();
        }
    }

    public function completePart(int $partId): void
    {
        $part = CncProgramPart::find($partId);

        if (!$part) {
            Notification::make()
                ->title('Part not found')
                ->danger()
                ->send();
            return;
        }

        if ($part->markComplete()) {
            Notification::make()
                ->title('Part Completed')
                ->body("Completed: {$part->file_name}")
                ->success()
                ->send();

            if ($this->activePartId === $partId) {
                $this->activePartId = null;
            }
        } else {
            Notification::make()
                ->title('Cannot complete part')
                ->body('Part is not in running status')
                ->danger()
                ->send();
        }
    }

    public function markError(int $partId): void
    {
        $part = CncProgramPart::find($partId);

        if (!$part) {
            Notification::make()
                ->title('Part not found')
                ->danger()
                ->send();
            return;
        }

        if ($part->markError('Marked as error from CNC Queue')) {
            Notification::make()
                ->title('Part Marked as Error')
                ->body("Error: {$part->file_name}")
                ->warning()
                ->send();

            if ($this->activePartId === $partId) {
                $this->activePartId = null;
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->dispatch('$refresh')),
        ];
    }

    /**
     * Get the polling interval for auto-refresh (in seconds)
     */
    public function getPollingInterval(): string
    {
        return '30s';
    }
}
