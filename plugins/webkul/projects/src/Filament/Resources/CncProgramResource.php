<?php

namespace Webkul\Project\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Webkul\Project\Filament\Resources\CncProgramResource\Pages\CreateCncProgram;
use Webkul\Project\Filament\Resources\CncProgramResource\Pages\EditCncProgram;
use Webkul\Project\Filament\Resources\CncProgramResource\Pages\ListCncPrograms;
use Webkul\Project\Filament\Resources\CncProgramResource\Pages\ViewCncProgram;
use Webkul\Project\Filament\Resources\CncProgramResource\RelationManagers\CncProgramPartsRelationManager;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\Project;

/**
 * CNC Program Resource for managing CNC programs.
 *
 * CNC Programs track VCarve project files and G-code output for cabinet production.
 */
class CncProgramResource extends Resource
{
    protected static ?string $model = CncProgram::class;

    protected static ?string $slug = 'project/cnc-programs';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return 'CNC Programs';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cog-8-tooth';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Production';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function getModelLabel(): string
    {
        return 'CNC Program';
    }

    public static function getPluralModelLabel(): string
    {
        return 'CNC Programs';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Program Details')
                    ->description('Basic information about the CNC program')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('project_id')
                                    ->label('Project')
                                    ->relationship('project', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select a project'),

                                TextInput::make('name')
                                    ->label('Program Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Kitchen Doors - RiftWO'),

                                Select::make('material_code')
                                    ->label('Material Code')
                                    ->options(CncProgram::getMaterialCodes())
                                    ->required()
                                    ->searchable()
                                    ->native(false),

                                TextInput::make('material_type')
                                    ->label('Material Type')
                                    ->maxLength(100)
                                    ->placeholder('e.g., 3/4" Rift White Oak'),

                                Select::make('sheet_size')
                                    ->label('Sheet Size')
                                    ->options(CncProgram::getSheetSizes())
                                    ->default('48x96')
                                    ->native(false),

                                TextInput::make('sheet_count')
                                    ->label('Sheet Count')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1),

                                Select::make('status')
                                    ->label('Status')
                                    ->options(CncProgram::getStatusOptions())
                                    ->default(CncProgram::STATUS_PENDING)
                                    ->required(),

                                DatePicker::make('created_date')
                                    ->label('Created Date')
                                    ->default(now())
                                    ->native(false),
                            ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Files')
                    ->description('VCarve project and NC files')
                    ->schema([
                        FileUpload::make('vcarve_file')
                            ->label('VCarve File (.crv)')
                            ->disk('local')
                            ->directory(fn ($record) => 'cnc/vcarve/' . ($record?->project_id ?? 'new'))
                            ->acceptedFileTypes([
                                'application/octet-stream',
                                '.crv',
                            ])
                            ->maxSize(50000)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => $record !== null),

                Section::make('Nesting Results')
                    ->description('Results from VCarve nesting')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('sheets_estimated')
                                    ->label('Sheets Estimated')
                                    ->numeric()
                                    ->disabled(),

                                TextInput::make('sheets_actual')
                                    ->label('Sheets Actual')
                                    ->numeric(),

                                TextInput::make('utilization_percentage')
                                    ->label('Utilization %')
                                    ->numeric()
                                    ->suffix('%'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('name')
                    ->label('Program Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('material_code')
                    ->label('Material')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'FL' => 'amber',
                        'PreFin' => 'blue',
                        'RiftWOPly' => 'green',
                        'MDF_RiftWO' => 'purple',
                        'Medex' => 'pink',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('sheet_count')
                    ->label('Sheets')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('utilization_percentage')
                    ->label('Utilization')
                    ->suffix('%')
                    ->color(fn (?float $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 85 => 'success',
                        $state >= 75 => 'info',
                        $state >= 65 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('completion_percentage')
                    ->label('Progress')
                    ->suffix('%')
                    ->getStateUsing(fn (CncProgram $record) => $record->completion_percentage)
                    ->color(fn (float $state): string => match (true) {
                        $state >= 100 => 'success',
                        $state >= 50 => 'info',
                        $state > 0 => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'info',
                        'complete' => 'success',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                TextColumn::make('parts_count')
                    ->label('Parts')
                    ->counts('parts')
                    ->badge()
                    ->color('secondary'),

                TextColumn::make('created_date')
                    ->label('Created')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('material_code')
                    ->label('Material')
                    ->options(CncProgram::getMaterialCodes()),

                SelectFilter::make('status')
                    ->options(CncProgram::getStatusOptions()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            CncProgramPartsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            CncProgramResource\Widgets\CncProgramStatusWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCncPrograms::route('/'),
            'create' => CreateCncProgram::route('/create'),
            'view' => ViewCncProgram::route('/{record}'),
            'edit' => EditCncProgram::route('/{record}/edit'),
        ];
    }
}
