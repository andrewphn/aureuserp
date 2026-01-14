<?php

namespace Webkul\Project\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Project\Filament\Resources\GateResource\Pages\CreateGate;
use Webkul\Project\Filament\Resources\GateResource\Pages\EditGate;
use Webkul\Project\Filament\Resources\GateResource\Pages\ListGates;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\ProjectStage;

/**
 * Gate Resource for configuring project gates.
 *
 * Gates define checkpoints that must pass before projects can advance stages.
 */
class GateResource extends Resource
{
    protected static ?string $model = Gate::class;

    protected static ?string $slug = 'project/gates';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return 'Gates';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-check-badge';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function getModelLabel(): string
    {
        return 'Gate';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Gates';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gate Details')
                    ->description('Define when this gate applies and what it does.')
                    ->schema([
                        Group::make()
                            ->columns(2)
                            ->schema([
                                Select::make('stage_id')
                                    ->label('Stage')
                                    ->relationship('stage', 'name')
                                    ->options(
                                        ProjectStage::whereNotNull('stage_key')
                                            ->where('stage_key', '!=', '')
                                            ->pluck('name', 'id')
                                    )
                                    ->required()
                                    ->searchable(),

                                TextInput::make('name')
                                    ->label('Gate Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('e.g., Design Lock'),

                                TextInput::make('gate_key')
                                    ->label('Gate Key')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g., design_lock')
                                    ->helperText('Unique identifier used in code'),

                                TextInput::make('sequence')
                                    ->label('Sequence')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Order within the stage'),
                            ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),

                        Group::make()
                            ->columns(3)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Inactive gates are skipped'),

                                Toggle::make('is_blocking')
                                    ->label('Blocking')
                                    ->default(true)
                                    ->helperText('Blocks stage advancement'),

                                Toggle::make('creates_tasks_on_pass')
                                    ->label('Creates Tasks')
                                    ->default(false)
                                    ->helperText('Auto-create tasks when passed'),
                            ]),
                    ]),

                Section::make('Lock Configuration')
                    ->description('What gets locked when this gate passes.')
                    ->schema([
                        Group::make()
                            ->columns(3)
                            ->schema([
                                Toggle::make('applies_design_lock')
                                    ->label('Design Lock')
                                    ->helperText('Locks cabinet specs'),

                                Toggle::make('applies_procurement_lock')
                                    ->label('Procurement Lock')
                                    ->helperText('Locks BOM quantities'),

                                Toggle::make('applies_production_lock')
                                    ->label('Production Lock')
                                    ->helperText('Locks dimensions'),
                            ]),
                    ])
                    ->collapsed(),

                Section::make('Requirements')
                    ->description('Conditions that must be met for this gate to pass.')
                    ->schema([
                        Repeater::make('requirements')
                            ->relationship()
                            ->schema([
                                Group::make()
                                    ->columns(2)
                                    ->schema([
                                        Select::make('requirement_type')
                                            ->label('Type')
                                            ->options(GateRequirement::getRequirementTypes())
                                            ->required()
                                            ->reactive(),

                                        TextInput::make('sequence')
                                            ->label('Order')
                                            ->numeric()
                                            ->default(0),
                                    ]),

                                Group::make()
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('target_model')
                                            ->label('Model')
                                            ->placeholder('e.g., Project'),

                                        TextInput::make('target_relation')
                                            ->label('Relation')
                                            ->placeholder('e.g., rooms'),

                                        TextInput::make('target_field')
                                            ->label('Field')
                                            ->placeholder('e.g., partner_id'),
                                    ]),

                                Group::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('target_value')
                                            ->label('Expected Value')
                                            ->placeholder('JSON or simple value'),

                                        Select::make('comparison_operator')
                                            ->label('Operator')
                                            ->options([
                                                '!=' => 'Not Equal (!=)',
                                                '=' => 'Equal (=)',
                                                '>' => 'Greater Than (>)',
                                                '>=' => 'Greater Or Equal (>=)',
                                                '<' => 'Less Than (<)',
                                                '<=' => 'Less Or Equal (<=)',
                                            ])
                                            ->default('!='),
                                    ]),

                                Group::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('custom_check_class')
                                            ->label('Custom Check Class')
                                            ->placeholder('Full class path')
                                            ->visible(fn ($get) => $get('requirement_type') === 'custom_check'),

                                        TextInput::make('custom_check_method')
                                            ->label('Method')
                                            ->placeholder('check')
                                            ->visible(fn ($get) => $get('requirement_type') === 'custom_check'),
                                    ]),

                                TextInput::make('error_message')
                                    ->label('Error Message')
                                    ->required()
                                    ->columnSpanFull()
                                    ->placeholder('Message shown when requirement fails'),

                                Group::make()
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('help_text')
                                            ->label('Help Text')
                                            ->placeholder('Additional guidance'),

                                        TextInput::make('action_label')
                                            ->label('Action Button')
                                            ->placeholder('e.g., Assign Client'),

                                        TextInput::make('action_route')
                                            ->label('Action Route')
                                            ->placeholder('e.g., filament.projects...'),
                                    ]),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Requirement')
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['error_message'] ?? 'New Requirement'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stage.name')
                    ->label('Stage')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Gate Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('gate_key')
                    ->label('Key')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('sequence')
                    ->label('Order')
                    ->sortable(),

                IconColumn::make('is_blocking')
                    ->label('Blocking')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open'),

                TextColumn::make('requirements_count')
                    ->label('Requirements')
                    ->counts('requirements')
                    ->badge(),

                IconColumn::make('applies_design_lock')
                    ->label('Design')
                    ->boolean()
                    ->trueIcon('heroicon-s-lock-closed')
                    ->falseIcon('heroicon-o-minus'),

                IconColumn::make('applies_procurement_lock')
                    ->label('Procurement')
                    ->boolean()
                    ->trueIcon('heroicon-s-lock-closed')
                    ->falseIcon('heroicon-o-minus'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->relationship('stage', 'name'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\TernaryFilter::make('is_blocking')
                    ->label('Blocking'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('stage_id')
            ->defaultSort('sequence');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGates::route('/'),
            'create' => CreateGate::route('/create'),
            'edit' => EditGate::route('/{record}/edit'),
        ];
    }
}
