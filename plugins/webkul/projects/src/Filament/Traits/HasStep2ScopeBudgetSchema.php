<?php

namespace Webkul\Project\Filament\Traits;

use App\Services\ProductionEstimatorService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Enums\BudgetRange;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\TcsPricingService;
use Webkul\Project\Settings\TimeSettings;

/**
 * Trait HasStep2ScopeBudgetSchema
 *
 * Extracts Step 2 (Scope & Budget) schema into separate methods for better
 * maintainability and testability.
 *
 * Used by CreateProject wizard.
 */
trait HasStep2ScopeBudgetSchema
{
    /**
     * Get the complete Step 2 schema - orchestrates all sections
     * Called by getStep2Schema() in CreateProject
     */
    protected function buildStep2Schema(): array
    {
        return [
            $this->getPricingModeSelector(),
            ...$this->getQuickEstimateSection(),
            $this->getRoomByRoomSection(),
            $this->getDetailedSpecSection(),
            $this->getEstimateSummaryPanel(),
            $this->getComplexitySection(),
            $this->getLegacyHiddenFields(),
            $this->getCustomerHistoryPanel(),
        ];
    }

    /**
     * Pricing Mode Toggle - Quick/Rooms/Detailed
     */
    protected function getPricingModeSelector(): Radio
    {
        return Radio::make('pricing_mode')
            ->label('Pricing Mode')
            ->options([
                'quick' => 'Quick Estimate (total linear feet)',
                'rooms' => 'Room-by-Room (rooms with pricing)',
                'detailed' => 'Detailed Spec (Room → Location → Run → Cabinet)',
            ])
            ->default('quick')
            ->inline()
            ->reactive()
            ->columnSpanFull();
    }

    /**
     * Quick Estimate Mode - Linear feet input and pricing options
     * Returns array of Grid components for the quick estimate section
     */
    protected function getQuickEstimateSection(): array
    {
        $pricingService = app(TcsPricingService::class);

        return [
            // Linear feet and budget range
            Grid::make(2)
                ->schema([
                    TextInput::make('estimated_linear_feet')
                        ->label('Total Linear Feet')
                        ->suffix('LF')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $this->calculateEstimatedProductionTime($state, $get, $set);
                            if ($state && $get('company_id')) {
                                $estimate = ProductionEstimatorService::calculate($state, $get('company_id'));
                                if ($estimate) {
                                    $set('allocated_hours', $estimate['hours']);
                                }
                            }
                        })
                        ->helperText('Enter total linear feet for quick estimate'),

                    Select::make('budget_range')
                        ->label('Budget Range')
                        ->options(BudgetRange::options())
                        ->native(false)
                        ->helperText('Approximate project budget'),
                ])
                ->visible(fn (callable $get) => $get('pricing_mode') === 'quick'),

            // Pricing options (cabinet level, material, finish)
            Grid::make(3)
                ->schema([
                    Select::make('default_cabinet_level')
                        ->label('Cabinet Level')
                        ->options(fn () => $pricingService->getCabinetLevelOptions())
                        ->default('3')
                        ->native(false)
                        ->live(),

                    Select::make('default_material_category')
                        ->label('Material')
                        ->options(fn () => $pricingService->getMaterialCategoryOptions())
                        ->default('stain_grade')
                        ->native(false)
                        ->live(),

                    Select::make('default_finish_option')
                        ->label('Finish')
                        ->options(fn () => $pricingService->getFinishOptions())
                        ->default('unfinished')
                        ->native(false)
                        ->live(),
                ])
                ->visible(fn (callable $get) => $get('pricing_mode') === 'quick'),
        ];
    }

    /**
     * Room-by-Room Mode - Repeater with room fields and inline pricing
     */
    protected function getRoomByRoomSection(): Section
    {
        $pricingService = app(TcsPricingService::class);

        return Section::make('Rooms')
            ->description('Add rooms with linear feet and pricing options')
            ->icon('heroicon-o-home')
            ->schema([
                Repeater::make('rooms')
                    ->label('')
                    ->schema([
                        Grid::make(6)->schema([
                            Select::make('room_type')
                                ->label('Room')
                                ->options($this->getRoomTypeOptions())
                                ->native(false)
                                ->required()
                                ->columnSpan(1),

                            TextInput::make('name')
                                ->label('Name')
                                ->placeholder('e.g. Master Bath')
                                ->columnSpan(1),

                            TextInput::make('linear_feet')
                                ->label('LF')
                                ->numeric()
                                ->step(0.5)
                                ->suffix('LF')
                                ->required()
                                ->live(debounce: 500)
                                ->columnSpan(1),

                            Select::make('cabinet_level')
                                ->label('Level')
                                ->options(fn () => $pricingService->getCabinetLevelOptions())
                                ->default('3')
                                ->native(false)
                                ->live()
                                ->columnSpan(1),

                            Select::make('material_category')
                                ->label('Material')
                                ->options(fn () => $pricingService->getMaterialCategoryOptions())
                                ->default('stain_grade')
                                ->native(false)
                                ->live()
                                ->columnSpan(1),

                            Select::make('finish_option')
                                ->label('Finish')
                                ->options(fn () => $pricingService->getFinishOptions())
                                ->default('unfinished')
                                ->native(false)
                                ->live()
                                ->columnSpan(1),
                        ]),
                    ])
                    ->addActionLabel('+ Add Room')
                    ->reorderable()
                    ->collapsible()
                    ->cloneable()
                    ->itemLabel(function (array $state) use ($pricingService): string {
                        return $this->formatRoomItemLabel($state, $pricingService);
                    })
                    ->defaultItems(0)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $this->cascadeRoomTotalsToQuickEstimate($state, $set);
                    }),
            ])
            ->compact()
            ->visible(fn (callable $get) => $get('pricing_mode') === 'rooms');
    }

    /**
     * Format room item label with pricing calculation
     */
    protected function formatRoomItemLabel(array $state, TcsPricingService $pricingService): string
    {
        $roomType = $state['room_type'] ?? 'Room';
        $name = $state['name'] ?? '';
        $lf = $state['linear_feet'] ?? 0;

        // Calculate price for this room
        $level = $state['cabinet_level'] ?? '3';
        $material = $state['material_category'] ?? 'stain_grade';
        $finish = $state['finish_option'] ?? 'unfinished';
        $unitPrice = $pricingService->calculateUnitPrice($level, $material, $finish);
        $roomTotal = $lf * $unitPrice;

        $label = ucfirst(str_replace('_', ' ', $roomType));
        if ($name) {
            $label .= " - {$name}";
        }
        if ($lf > 0) {
            $label .= " | {$lf} LF × \${$unitPrice}/LF = \$" . number_format($roomTotal, 0);
        }

        return $label;
    }

    /**
     * Cascade room totals to quick estimate linear feet
     */
    protected function cascadeRoomTotalsToQuickEstimate($state, callable $set): void
    {
        $totalLf = 0;
        foreach ($state ?? [] as $room) {
            $totalLf += (float) ($room['linear_feet'] ?? 0);
        }
        $set('estimated_linear_feet', round($totalLf, 2));
    }

    /**
     * Detailed Spec Mode - CabinetSpecBuilder Livewire component wrapper
     */
    protected function getDetailedSpecSection(): Section
    {
        return Section::make('Cabinet Specifications')
            ->description('Build detailed specs: Room → Location → Run → Cabinet → Section → Component')
            ->icon('heroicon-o-square-3-stack-3d')
            ->schema([
                // Hidden field to store spec data (synced from Livewire component)
                Hidden::make('spec_data')
                    ->default([])
                    ->dehydrated(),

                // Miller Columns Cabinet Spec Builder (Livewire Component)
                View::make('webkul-project::filament.components.cabinet-spec-builder-wrapper')
                    ->viewData(fn (callable $get) => [
                        'specData' => $get('spec_data') ?? [],
                    ]),
            ])
            ->compact()
            ->visible(fn (callable $get) => $get('pricing_mode') === 'detailed');
    }

    /**
     * Estimate Summary Panel - Shows pricing across all modes
     */
    protected function getEstimateSummaryPanel(): Section
    {
        $pricingService = app(TcsPricingService::class);

        return Section::make('Estimate Summary')
            ->schema([
                Placeholder::make('estimate_summary')
                    ->label('')
                    ->content(function (callable $get) use ($pricingService) {
                        return $this->calculateEstimateSummary($get, $pricingService);
                    }),
            ])
            ->compact();
    }

    /**
     * Calculate estimate summary based on pricing mode
     */
    protected function calculateEstimateSummary(callable $get, TcsPricingService $pricingService): mixed
    {
        $mode = $get('pricing_mode') ?? 'quick';
        $companyId = $get('company_id');

        $linearFeet = 0;
        $totalEstimate = 0;
        $roomCount = null;

        if ($mode === 'quick') {
            return $this->calculateQuickModeEstimate($get, $pricingService, $companyId);
        } elseif ($mode === 'rooms') {
            return $this->calculateRoomModeEstimate($get, $pricingService, $companyId);
        } else {
            return $this->calculateDetailedModeEstimate($get, $pricingService, $companyId);
        }
    }

    /**
     * Calculate quick mode estimate
     */
    protected function calculateQuickModeEstimate(callable $get, TcsPricingService $pricingService, $companyId): mixed
    {
        $linearFeet = (float) ($get('estimated_linear_feet') ?: 0);
        $level = $get('default_cabinet_level') ?? '3';
        $material = $get('default_material_category') ?? 'stain_grade';
        $finish = $get('default_finish_option') ?? 'unfinished';

        if (!$linearFeet) {
            return 'Enter linear feet to see estimate';
        }

        $unitPrice = $pricingService->calculateUnitPrice($level, $material, $finish);
        $totalEstimate = $linearFeet * $unitPrice;

        return $this->renderEstimatePanel($linearFeet, $totalEstimate, $companyId, null);
    }

    /**
     * Calculate room-by-room mode estimate
     */
    protected function calculateRoomModeEstimate(callable $get, TcsPricingService $pricingService, $companyId): mixed
    {
        $rooms = $get('rooms') ?? [];
        if (empty($rooms)) {
            return 'Add rooms to see estimate';
        }

        $linearFeet = 0;
        $totalEstimate = 0;

        foreach ($rooms as $room) {
            $lf = (float) ($room['linear_feet'] ?? 0);
            $level = $room['cabinet_level'] ?? '3';
            $material = $room['material_category'] ?? 'stain_grade';
            $finish = $room['finish_option'] ?? 'unfinished';

            $unitPrice = $pricingService->calculateUnitPrice($level, $material, $finish);
            $linearFeet += $lf;
            $totalEstimate += ($lf * $unitPrice);
        }

        return $this->renderEstimatePanel($linearFeet, $totalEstimate, $companyId, count($rooms));
    }

    /**
     * Calculate detailed spec mode estimate
     */
    protected function calculateDetailedModeEstimate(callable $get, TcsPricingService $pricingService, $companyId): mixed
    {
        $specData = $get('spec_data') ?? [];
        if (empty($specData)) {
            return 'Add rooms to see estimate';
        }

        $linearFeet = 0;
        $totalEstimate = 0;
        $roomCount = count($specData);

        foreach ($specData as $roomData) {
            $roomLf = (float) ($roomData['linear_feet'] ?? 0);
            $linearFeet += $roomLf;

            // Get location-level pricing
            foreach ($roomData['children'] ?? [] as $location) {
                $level = $location['cabinet_level'] ?? '2';
                $locLf = (float) ($location['linear_feet'] ?? 0);

                $unitPrice = $pricingService->calculateUnitPrice($level, 'stain_grade', 'unfinished');
                $totalEstimate += $locLf * $unitPrice;
            }
        }

        return $this->renderEstimatePanel($linearFeet, $totalEstimate, $companyId, $roomCount);
    }

    /**
     * Render the estimate panel view
     */
    protected function renderEstimatePanel(float $linearFeet, float $totalEstimate, $companyId, ?int $roomCount): \Illuminate\Contracts\View\View
    {
        $productionTime = 'N/A';
        if ($companyId && $linearFeet > 0) {
            $estimate = ProductionEstimatorService::calculate($linearFeet, $companyId);
            $productionTime = $estimate ? $estimate['formatted'] : 'N/A';
        }

        return view('webkul-project::filament.components.quick-estimate-panel', [
            'linearFeet' => $linearFeet,
            'baseRate' => round($totalEstimate / max($linearFeet, 1), 2),
            'quickEstimate' => $totalEstimate,
            'productionTime' => $productionTime,
            'roomCount' => $roomCount,
        ]);
    }

    /**
     * Complexity Score and Allocated Hours section
     */
    protected function getComplexitySection(): Grid
    {
        return Grid::make(2)->schema([
            TextInput::make('complexity_score')
                ->label('Complexity Score')
                ->numeric()
                ->minValue(1)
                ->maxValue(10)
                ->step(1)
                ->placeholder('1-10')
                ->helperText('1 = Simple, 10 = Highly complex'),

            TextInput::make('allocated_hours')
                ->label('Allocated Hours')
                ->suffixIcon('heroicon-o-clock')
                ->numeric()
                ->minValue(0)
                ->helperText('Auto-calculated from linear feet')
                ->visible(app(TimeSettings::class)->enable_timesheets),
        ]);
    }

    /**
     * Legacy hidden fields for backward compatibility
     */
    protected function getLegacyHiddenFields(): Hidden
    {
        return Hidden::make('cabinet_spec_data')
            ->default('[]');
    }

    /**
     * Customer History Panel - shows when customer is selected
     */
    protected function getCustomerHistoryPanel(): Section
    {
        return Section::make('Customer History')
            ->schema([
                Placeholder::make('customer_history')
                    ->label('')
                    ->content(function (callable $get) {
                        return $this->renderCustomerHistory($get);
                    }),
            ])
            ->compact()
            ->collapsible()
            ->collapsed(true); // Always collapsed by default
    }

    /**
     * Render customer history content
     */
    protected function renderCustomerHistory(callable $get): mixed
    {
        $partnerId = $get('partner_id');
        if (!$partnerId) {
            return 'Select a customer to see history';
        }

        $partner = Partner::find($partnerId);
        if (!$partner) {
            return 'Customer not found';
        }

        // Get customer project history
        $projects = Project::where('partner_id', $partnerId)->get();
        $totalProjects = $projects->count();

        return view('webkul-project::filament.components.customer-history-panel', [
            'partner' => $partner,
            'totalProjects' => $totalProjects,
        ]);
    }

    /**
     * Get room type options for the repeater
     * This method should be implemented in the using class if not already present
     */
    abstract protected function getRoomTypeOptions(): array;

    /**
     * Calculate estimated production time
     * This method should be implemented in the using class if not already present
     */
    abstract protected function calculateEstimatedProductionTime($state, callable $get, callable $set): void;
}
