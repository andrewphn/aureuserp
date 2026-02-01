<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Project\Models\CncProgram;

/**
 * CNC Programs Relation Manager for Projects
 *
 * Shows CNC programs associated with a project
 */
class CncProgramsRelationManager extends RelationManager
{
    protected static string $relationship = 'cncPrograms';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
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

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
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
                    }),

                TextColumn::make('sheet_count')
                    ->label('Sheets')
                    ->numeric(),

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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(CncProgram::getStatusOptions()),

                Tables\Filters\SelectFilter::make('material_code')
                    ->label('Material')
                    ->options(CncProgram::getMaterialCodes()),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['creator_id'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn (CncProgram $record) => route('filament.admin.resources.project/cnc-programs.view', $record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
