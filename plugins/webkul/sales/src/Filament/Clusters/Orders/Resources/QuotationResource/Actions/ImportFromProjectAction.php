<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\ProjectToOrderService;
use Webkul\Sale\Facades\SaleOrder;

/**
 * Import from Project Action
 *
 * Populates order lines from a project's specifications including:
 * - Cabinet specifications with linear foot pricing
 * - Room-level charges
 * - Material costs from BOM
 */
class ImportFromProjectAction extends Action
{
    /**
     * Get the default name for this action
     *
     * @return string|null Action identifier
     */
    public static function getDefaultName(): ?string
    {
        return 'orders.sales.import-from-project';
    }

    /**
     * Configure the import action with form fields and import logic
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->color('info')
            ->icon('heroicon-o-arrow-down-tray')
            ->label('Import from Project')
            ->modalHeading('Import Order Lines from Project')
            ->modalDescription('Generate order lines from a project\'s cabinet specifications, rooms, and materials.')
            ->modalSubmitActionLabel('Import Lines')
            ->form([
                Select::make('project_id')
                    ->label('Select Project')
                    ->options(function ($record) {
                        $query = Project::query();

                        // If order has a project, show that first
                        if ($record->project_id) {
                            return Project::where('id', $record->project_id)
                                ->orWhere('partner_id', $record->partner_id)
                                ->orderByRaw("id = {$record->project_id} DESC")
                                ->pluck('name', 'id');
                        }

                        // Otherwise filter by partner
                        if ($record->partner_id) {
                            $query->where('partner_id', $record->partner_id);
                        }

                        return $query->orderBy('created_at', 'desc')
                            ->limit(50)
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->default(fn ($record) => $record->project_id)
                    ->helperText('Select the project to import specifications from'),

                Toggle::make('include_cabinets')
                    ->label('Include Cabinet Specifications')
                    ->default(true)
                    ->helperText('Import cabinet details with linear foot pricing'),

                Toggle::make('include_rooms')
                    ->label('Include Room Charges')
                    ->default(true)
                    ->helperText('Import room-level pricing if configured'),

                Toggle::make('include_materials')
                    ->label('Include BOM Materials')
                    ->default(false)
                    ->helperText('Import material costs from Bill of Materials'),

                Toggle::make('group_by_room')
                    ->label('Group by Room')
                    ->default(true)
                    ->helperText('Add section headers for each room'),

                Checkbox::make('clear_existing')
                    ->label('Clear existing lines before import')
                    ->default(false)
                    ->helperText('Warning: This will remove all existing order lines'),
            ])
            ->action(function ($record, array $data, $livewire) {
                try {
                    $project = Project::findOrFail($data['project_id']);
                    $service = app(ProjectToOrderService::class);

                    $result = $service->importFromProject($record, $project, [
                        'include_cabinets' => $data['include_cabinets'] ?? true,
                        'include_rooms' => $data['include_rooms'] ?? true,
                        'include_materials' => $data['include_materials'] ?? false,
                        'group_by_room' => $data['group_by_room'] ?? true,
                        'clear_existing' => $data['clear_existing'] ?? false,
                    ]);

                    if ($result['success']) {
                        // Link order to project if not already linked
                        if (!$record->project_id) {
                            $record->update(['project_id' => $project->id]);
                        }

                        // Recompute order totals
                        SaleOrder::computeSaleOrder($record);

                        $livewire->refreshFormData(['lines']);

                        Notification::make()
                            ->success()
                            ->title('Import Successful')
                            ->body("Imported {$result['lines']->count()} lines from project \"{$project->name}\".")
                            ->send();
                    } else {
                        Notification::make()
                            ->warning()
                            ->title('Import Completed with Errors')
                            ->body(implode("\n", $result['errors']))
                            ->send();
                    }
                } catch (Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Import Failed')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
