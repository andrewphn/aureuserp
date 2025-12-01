<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\DB;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;

/**
 * Clone Project Action
 *
 * Creates a copy of a project with options to:
 * - Clone for same customer (new version/revision)
 * - Clone for different customer (use as template)
 * - Include/exclude rooms, cabinets, specifications
 */
class CloneProjectAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'projects.clone';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->color('warning')
            ->icon('heroicon-o-document-duplicate')
            ->label('Clone Project')
            ->modalHeading('Clone Project')
            ->modalDescription('Create a new project based on this one. You can use the same customer or assign a different one.')
            ->modalSubmitActionLabel('Clone Project')
            ->form([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('New Project Name')
                            ->required()
                            ->default(fn ($record) => $record->name . ' (Copy)')
                            ->helperText('Name for the cloned project'),

                        Select::make('partner_id')
                            ->label('Customer')
                            ->options(fn () => Partner::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn ($record) => $record->partner_id)
                            ->helperText('Select the customer for the new project'),
                    ]),

                Grid::make(2)
                    ->schema([
                        Select::make('stage_id')
                            ->label('Initial Stage')
                            ->options(fn () => ProjectStage::orderBy('sort')->pluck('name', 'id'))
                            ->required()
                            ->default(fn () => ProjectStage::orderBy('sort')->first()?->id)
                            ->helperText('Stage for the new project'),

                        Select::make('company_id')
                            ->label('Company')
                            ->options(fn () => \Webkul\Support\Models\Company::pluck('name', 'id'))
                            ->required()
                            ->default(fn ($record) => $record->company_id)
                            ->helperText('Company for the new project'),
                    ]),

                Grid::make(3)
                    ->schema([
                        Checkbox::make('include_rooms')
                            ->label('Include Rooms')
                            ->default(true)
                            ->helperText('Clone room structure'),

                        Checkbox::make('include_cabinets')
                            ->label('Include Cabinet Specs')
                            ->default(true)
                            ->helperText('Clone cabinet specifications'),

                        Checkbox::make('include_addresses')
                            ->label('Include Addresses')
                            ->default(false)
                            ->helperText('Copy project addresses'),
                    ]),

                Grid::make(2)
                    ->schema([
                        Checkbox::make('reset_dates')
                            ->label('Reset Dates')
                            ->default(true)
                            ->helperText('Clear start/end dates on new project'),

                        Checkbox::make('reset_pricing')
                            ->label('Reset Pricing')
                            ->default(false)
                            ->helperText('Clear pricing on cloned items'),
                    ]),
            ])
            ->action(function ($record, array $data, $livewire) {
                try {
                    $newProject = $this->cloneProject($record, $data);

                    $livewire->redirect(
                        ProjectResource::getUrl('edit', ['record' => $newProject]),
                        navigate: FilamentView::hasSpaMode()
                    );

                    Notification::make()
                        ->success()
                        ->title('Project Cloned Successfully')
                        ->body("New project \"{$newProject->name}\" has been created.")
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Clone Failed')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    /**
     * Clone a project with all its specifications
     */
    protected function cloneProject(Project $source, array $data): Project
    {
        return DB::transaction(function () use ($source, $data) {
            // Clone base project data
            $projectData = $source->replicate([
                'id',
                'project_number',
                'created_at',
                'updated_at',
                'deleted_at',
            ])->toArray();

            // Apply form overrides
            $projectData['name'] = $data['name'];
            $projectData['partner_id'] = $data['partner_id'];
            $projectData['stage_id'] = $data['stage_id'];
            $projectData['company_id'] = $data['company_id'];
            $projectData['creator_id'] = auth()->id();
            $projectData['user_id'] = auth()->id();

            // Reset dates if requested
            if ($data['reset_dates'] ?? false) {
                $projectData['start_date'] = null;
                $projectData['end_date'] = null;
                $projectData['desired_completion_date'] = null;
            }

            // Clear source quote reference for clones
            $projectData['source_quote_id'] = null;

            // Create the new project
            $newProject = Project::create($projectData);

            // Clone rooms if requested
            if ($data['include_rooms'] ?? false) {
                $this->cloneRooms($source, $newProject, $data);
            }

            // Clone addresses if requested
            if ($data['include_addresses'] ?? false) {
                $this->cloneAddresses($source, $newProject);
            }

            // Clone tags
            $newProject->tags()->sync($source->tags->pluck('id'));

            return $newProject;
        });
    }

    /**
     * Clone rooms and their child entities
     */
    protected function cloneRooms(Project $source, Project $target, array $data): void
    {
        foreach ($source->rooms as $room) {
            $roomData = $room->replicate([
                'id',
                'project_id',
                'created_at',
                'updated_at',
            ])->toArray();

            $roomData['project_id'] = $target->id;

            // Reset pricing if requested
            if ($data['reset_pricing'] ?? false) {
                $roomData['total_price'] = null;
                $roomData['estimated_cabinet_value'] = null;
            }

            $newRoom = $target->rooms()->create($roomData);

            // Clone room locations
            foreach ($room->roomLocations as $location) {
                $locationData = $location->replicate([
                    'id',
                    'room_id',
                    'created_at',
                    'updated_at',
                ])->toArray();

                $locationData['room_id'] = $newRoom->id;

                $newLocation = $newRoom->roomLocations()->create($locationData);

                // Clone cabinet runs
                foreach ($location->cabinetRuns as $run) {
                    $runData = $run->replicate([
                        'id',
                        'room_location_id',
                        'created_at',
                        'updated_at',
                    ])->toArray();

                    $runData['room_location_id'] = $newLocation->id;

                    $newRun = $newLocation->cabinetRuns()->create($runData);

                    // Clone cabinets if requested
                    if ($data['include_cabinets'] ?? false) {
                        foreach ($run->cabinets as $cabinet) {
                            $this->cloneCabinet($cabinet, $target, $newRoom, $newRun, $data);
                        }
                    }
                }
            }

            // Clone cabinets directly on room (not in runs)
            if ($data['include_cabinets'] ?? false) {
                $directCabinets = $source->cabinets()
                    ->where('room_id', $room->id)
                    ->whereNull('cabinet_run_id')
                    ->get();

                foreach ($directCabinets as $cabinet) {
                    $this->cloneCabinet($cabinet, $target, $newRoom, null, $data);
                }
            }
        }

        // Clone standalone cabinets (not in any room)
        if ($data['include_cabinets'] ?? false) {
            $standaloneCabinets = $source->cabinets()
                ->whereNull('room_id')
                ->get();

            foreach ($standaloneCabinets as $cabinet) {
                $this->cloneCabinet($cabinet, $target, null, null, $data);
            }
        }
    }

    /**
     * Clone a single cabinet specification
     */
    protected function cloneCabinet(
        $cabinet,
        Project $target,
        $newRoom = null,
        $newRun = null,
        array $data = []
    ): void {
        $cabinetData = $cabinet->replicate([
            'id',
            'project_id',
            'room_id',
            'cabinet_run_id',
            'order_line_id',
            'created_at',
            'updated_at',
        ])->toArray();

        $cabinetData['project_id'] = $target->id;
        $cabinetData['room_id'] = $newRoom?->id;
        $cabinetData['cabinet_run_id'] = $newRun?->id;
        $cabinetData['order_line_id'] = null; // Reset order line reference
        $cabinetData['creator_id'] = auth()->id();

        // Reset pricing if requested
        if ($data['reset_pricing'] ?? false) {
            $cabinetData['unit_price_per_lf'] = null;
            $cabinetData['total_price'] = null;
        }

        $target->cabinets()->create($cabinetData);
    }

    /**
     * Clone project addresses
     */
    protected function cloneAddresses(Project $source, Project $target): void
    {
        foreach ($source->addresses as $address) {
            $addressData = $address->replicate([
                'id',
                'project_id',
                'created_at',
                'updated_at',
            ])->toArray();

            $addressData['project_id'] = $target->id;

            $target->addresses()->create($addressData);
        }
    }
}
