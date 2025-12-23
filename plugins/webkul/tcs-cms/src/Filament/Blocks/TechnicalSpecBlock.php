<?php

namespace Webkul\TcsCms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class TechnicalSpecBlock
{
    public static function make(): Block
    {
        return Block::make('technical_spec')
            ->label('Technical Specifications')
            ->icon('heroicon-o-clipboard-document-list')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('spec_title')
                            ->label('Specification Title')
                            ->placeholder('Project Specifications')
                            ->required(),

                        Select::make('spec_type')
                            ->label('Specification Type')
                            ->options([
                                'project' => 'Project Specifications',
                                'material' => 'Material Specifications',
                                'tool' => 'Tool Specifications',
                                'process' => 'Process Specifications',
                                'safety' => 'Safety Specifications',
                                'quality' => 'Quality Standards',
                                'measurement' => 'Measurement Standards',
                                'finish' => 'Finish Specifications',
                            ])
                            ->default('project')
                            ->required(),
                    ]),

                RichEditor::make('spec_introduction')
                    ->label('Introduction/Overview')
                    ->toolbarButtons(['bold', 'italic', 'bulletList'])
                    ->columnSpanFull(),

                Repeater::make('specification_sections')
                    ->label('Specification Sections')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('section_title')
                                    ->label('Section Title')
                                    ->placeholder('Dimensions & Measurements')
                                    ->required(),

                                Select::make('section_category')
                                    ->label('Section Category')
                                    ->options([
                                        'dimensions' => 'Dimensions',
                                        'materials' => 'Materials',
                                        'hardware' => 'Hardware',
                                        'finishes' => 'Finishes',
                                        'tolerances' => 'Tolerances',
                                        'performance' => 'Performance',
                                        'safety' => 'Safety',
                                        'installation' => 'Installation',
                                        'maintenance' => 'Maintenance',
                                        'warranty' => 'Warranty',
                                        'compliance' => 'Code Compliance',
                                        'testing' => 'Testing Requirements',
                                    ])
                                    ->default('dimensions'),
                            ]),

                        Repeater::make('specifications')
                            ->label('Individual Specifications')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextInput::make('spec_item')
                                            ->label('Specification Item')
                                            ->placeholder('Overall Width')
                                            ->required(),

                                        TextInput::make('spec_value')
                                            ->label('Value/Measurement')
                                            ->placeholder('36 inches')
                                            ->required(),

                                        TextInput::make('tolerance')
                                            ->label('Tolerance')
                                            ->placeholder('± 1/16"'),

                                        Select::make('unit_type')
                                            ->label('Unit Type')
                                            ->options([
                                                'imperial' => 'Imperial',
                                                'metric' => 'Metric',
                                                'mixed' => 'Mixed',
                                                'percentage' => 'Percentage',
                                                'count' => 'Count/Quantity',
                                                'time' => 'Time Duration',
                                                'weight' => 'Weight',
                                                'other' => 'Other',
                                            ])
                                            ->default('imperial'),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('spec_notes')
                                            ->label('Notes/Comments')
                                            ->columnSpan(2),

                                        Select::make('criticality')
                                            ->label('Criticality Level')
                                            ->options([
                                                'critical' => 'Critical (Must Meet)',
                                                'important' => 'Important (Should Meet)',
                                                'preferred' => 'Preferred (Nice to Meet)',
                                                'reference' => 'Reference Only',
                                            ])
                                            ->default('important'),
                                    ]),

                                Grid::make(4)
                                    ->schema([
                                        Toggle::make('is_measured')
                                            ->label('Requires Measurement')
                                            ->default(true),

                                        Toggle::make('is_inspected')
                                            ->label('Requires Inspection')
                                            ->default(false),

                                        Toggle::make('is_tested')
                                            ->label('Requires Testing')
                                            ->default(false),

                                        Toggle::make('client_approval_required')
                                            ->label('Client Approval Required')
                                            ->default(false),
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->reorderable()
                            ->cloneable()
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['spec_item'] ?? 'Untitled Spec')
                            ->addActionLabel('Add Specification')
                            ->columnSpanFull(),
                    ])
                    ->defaultItems(1)
                    ->reorderable()
                    ->cloneable()
                    ->collapsible()
                    ->itemLabel(fn(array $state): ?string => $state['section_title'] ?? 'Untitled Section')
                    ->addActionLabel('Add Specification Section')
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        FileUpload::make('technical_drawings')
                            ->label('Technical Drawings/Diagrams')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->directory('tcs-cms/technical-specs'),

                        Grid::make(1)
                            ->schema([
                                RichEditor::make('general_notes')
                                    ->label('General Notes')
                                    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),

                                TextInput::make('reference_standards')
                                    ->label('Reference Standards')
                                    ->placeholder('ANSI, AWFI, Custom Standards'),

                                TextInput::make('revision_date')
                                    ->label('Specification Date')
                                    ->placeholder('March 15, 2024'),
                            ]),
                    ]),

                Grid::make(4)
                    ->schema([
                        Select::make('display_format')
                            ->label('Display Format')
                            ->options([
                                'table' => 'Data Table',
                                'cards' => 'Specification Cards',
                                'accordion' => 'Accordion Sections',
                                'tabs' => 'Tabbed Sections',
                                'list' => 'Hierarchical List',
                            ])
                            ->default('table'),

                        Toggle::make('show_tolerances')
                            ->label('Show Tolerances')
                            ->default(true),

                        Toggle::make('show_criticality')
                            ->label('Show Criticality Levels')
                            ->default(true),

                        Toggle::make('show_units')
                            ->label('Show Unit Types')
                            ->default(true),

                        Toggle::make('enable_filtering')
                            ->label('Enable Category Filtering')
                            ->default(false),

                        Toggle::make('show_verification_status')
                            ->label('Show Verification Requirements')
                            ->default(false),

                        Toggle::make('printable_format')
                            ->label('Include Print-Friendly Format')
                            ->default(false),

                        Toggle::make('client_view_mode')
                            ->label('Client-Friendly View')
                            ->default(false),
                    ]),

                Grid::make(3)
                    ->schema([
                        Select::make('measurement_system')
                            ->label('Primary Measurement System')
                            ->options([
                                'imperial' => 'Imperial (inches, feet)',
                                'metric' => 'Metric (millimeters)',
                                'dual' => 'Dual System (both shown)',
                            ])
                            ->default('imperial'),

                        Select::make('precision_level')
                            ->label('Precision Level')
                            ->options([
                                'rough' => 'Rough (± 1/8")',
                                'standard' => 'Standard (± 1/16")',
                                'fine' => 'Fine (± 1/32")',
                                'precision' => 'Precision (± 1/64")',
                                'custom' => 'Custom Tolerance',
                            ])
                            ->default('standard'),

                        TextInput::make('project_reference')
                            ->label('Project Reference Number')
                            ->placeholder('TCS-2024-001'),
                    ]),

                Grid::make(2)
                    ->schema([
                        RichEditor::make('compliance_notes')
                            ->label('Code Compliance Notes')
                            ->toolbarButtons(['bold', 'italic', 'bulletList']),

                        RichEditor::make('quality_standards')
                            ->label('Quality Standards')
                            ->toolbarButtons(['bold', 'italic', 'bulletList']),
                    ]),
            ]);
    }

    public static function mutateData(array $data): array
    {
        if (isset($data['specification_sections'])) {
            foreach ($data['specification_sections'] as &$section) {
                if (isset($section['specifications'])) {
                    $critical = [];
                    $important = [];
                    $preferred = [];
                    $reference = [];

                    foreach ($section['specifications'] as &$spec) {
                        $specClasses = ['spec-item'];

                        if (isset($spec['criticality'])) {
                            $specClasses[] = 'criticality-'.$spec['criticality'];

                            switch ($spec['criticality']) {
                                case 'critical':
                                    $critical[] = $spec;
                                    break;
                                case 'important':
                                    $important[] = $spec;
                                    break;
                                case 'preferred':
                                    $preferred[] = $spec;
                                    break;
                                case 'reference':
                                    $reference[] = $spec;
                                    break;
                            }
                        }

                        if (isset($spec['unit_type'])) {
                            $specClasses[] = 'unit-'.str_replace('_', '-', $spec['unit_type']);
                        }

                        if ($spec['is_measured'] ?? false) {
                            $specClasses[] = 'requires-measurement';
                        }
                        if ($spec['is_inspected'] ?? false) {
                            $specClasses[] = 'requires-inspection';
                        }
                        if ($spec['is_tested'] ?? false) {
                            $specClasses[] = 'requires-testing';
                        }
                        if ($spec['client_approval_required'] ?? false) {
                            $specClasses[] = 'requires-approval';
                        }

                        $spec['css_classes'] = implode(' ', $specClasses);

                        $displayParts = [$spec['spec_value'] ?? ''];
                        if (! empty($spec['tolerance'])) {
                            $displayParts[] = '('.$spec['tolerance'].')';
                        }
                        $spec['display_value'] = implode(' ', $displayParts);

                        $verification = [];
                        if ($spec['is_measured'] ?? false) {
                            $verification[] = 'Measure';
                        }
                        if ($spec['is_inspected'] ?? false) {
                            $verification[] = 'Inspect';
                        }
                        if ($spec['is_tested'] ?? false) {
                            $verification[] = 'Test';
                        }
                        if ($spec['client_approval_required'] ?? false) {
                            $verification[] = 'Client Approval';
                        }
                        $spec['verification_requirements'] = $verification;
                    }

                    $section['grouped_specs'] = [
                        'critical' => $critical,
                        'important' => $important,
                        'preferred' => $preferred,
                        'reference' => $reference,
                    ];
                }

                $sectionClasses = ['spec-section'];
                if (isset($section['section_category'])) {
                    $sectionClasses[] = 'category-'.str_replace('_', '-', $section['section_category']);
                }
                $section['css_classes'] = implode(' ', $sectionClasses);
            }
        }

        // Main CSS classes
        $classes = ['technical-spec'];
        if (isset($data['spec_type'])) {
            $classes[] = 'type-'.str_replace('_', '-', $data['spec_type']);
        }
        if (isset($data['display_format'])) {
            $classes[] = 'format-'.str_replace('_', '-', $data['display_format']);
        }
        if (isset($data['measurement_system'])) {
            $classes[] = 'measurement-'.str_replace('_', '-', $data['measurement_system']);
        }
        if ($data['enable_filtering'] ?? false) {
            $classes[] = 'filterable';
        }
        if ($data['client_view_mode'] ?? false) {
            $classes[] = 'client-view';
        }
        $data['css_classes'] = implode(' ', $classes);

        // Process technical drawings
        if (isset($data['technical_drawings'])) {
            $data['drawing_urls'] = [];
            foreach ($data['technical_drawings'] as $drawing) {
                $data['drawing_urls'][] = asset('storage/'.$drawing);
            }
        }

        // Category filters
        if ($data['enable_filtering'] ?? false) {
            $categories = [];
            foreach ($data['specification_sections'] ?? [] as $section) {
                if (isset($section['section_category'])) {
                    $categories[$section['section_category']] = ucfirst(str_replace('_', ' ', $section['section_category']));
                }
            }
            $data['category_filters'] = $categories;
        }

        // Statistics
        $totalSpecs = 0;
        $criticalCount = 0;
        $measurementRequired = 0;
        $inspectionRequired = 0;

        foreach ($data['specification_sections'] ?? [] as $section) {
            foreach ($section['specifications'] ?? [] as $spec) {
                $totalSpecs++;
                if (($spec['criticality'] ?? '') === 'critical') {
                    $criticalCount++;
                }
                if ($spec['is_measured'] ?? false) {
                    $measurementRequired++;
                }
                if ($spec['is_inspected'] ?? false) {
                    $inspectionRequired++;
                }
            }
        }

        $data['spec_statistics'] = [
            'total_specifications' => $totalSpecs,
            'critical_specifications' => $criticalCount,
            'measurement_required' => $measurementRequired,
            'inspection_required' => $inspectionRequired,
            'section_count' => count($data['specification_sections'] ?? []),
        ];

        $data['spec_id'] = 'spec-block-'.uniqid();

        // Precision descriptions
        $precisionDescriptions = [
            'rough' => 'Rough carpentry tolerances (± 1/8")',
            'standard' => 'Standard woodworking tolerances (± 1/16")',
            'fine' => 'Fine woodworking tolerances (± 1/32")',
            'precision' => 'Precision tolerances (± 1/64")',
            'custom' => 'Custom tolerance specifications',
        ];
        $data['precision_description'] = $precisionDescriptions[$data['precision_level'] ?? 'standard'] ?? '';

        // Parse reference standards
        if (isset($data['reference_standards'])) {
            $data['standards_array'] = array_map('trim', explode(',', $data['reference_standards']));
        }

        return $data;
    }
}
