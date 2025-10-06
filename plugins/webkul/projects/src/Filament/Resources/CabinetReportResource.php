<?php

namespace Webkul\Project\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Inventories\Filament\Clusters\Products;
use Webkul\Project\Filament\Resources\CabinetReportResource\Pages;
use Webkul\Project\Models\CabinetSpecification;

class CabinetReportResource extends Resource
{
    protected static ?string $model = CabinetSpecification::class;

    protected static ?string $cluster = Products::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Cabinet Reports';

    protected static ?string $modelLabel = 'Cabinet Analysis';

    protected static ?string $pluralModelLabel = 'Cabinet Analytics';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Spec ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('productVariant.name')
                    ->label('Cabinet Variant')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('dimensions')
                    ->label('Dimensions')
                    ->getStateUsing(fn ($record) =>
                        "{$record->length_inches}\" × {$record->width_inches}\" × {$record->depth_inches}\" × {$record->height_inches}\""
                    )
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('linear_feet')
                    ->label('Linear Feet')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->suffix(' LF'),

                Tables\Columns\TextColumn::make('size_range')
                    ->label('Size Range')
                    ->badge()
                    ->colors([
                        'success' => 'small',
                        'info' => 'medium',
                        'warning' => 'large',
                        'danger' => 'extra-large',
                    ]),

                Tables\Columns\TextColumn::make('common_size_match')
                    ->label('Common Size')
                    ->badge()
                    ->color('success')
                    ->placeholder('Custom'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('size_range')
                    ->options([
                        'small' => 'Small (12-18")',
                        'medium' => 'Medium (18-36")',
                        'large' => 'Large (36-48")',
                        'extra-large' => 'Extra Large (48"+)',
                    ]),

                Tables\Filters\Filter::make('has_common_size')
                    ->label('Common Sizes Only')
                    ->query(fn (Builder $query) => $query->whereNotNull(
                        DB::raw('(SELECT 1 WHERE length_inches IN (12,15,18,24,30,36))')
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->label('Delete')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->delete()),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Cabinet Overview')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Specification ID')
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('size_range')
                                    ->label('Size Category')
                                    ->badge()
                                    ->colors([
                                        'success' => 'small',
                                        'info' => 'medium',
                                        'warning' => 'large',
                                        'danger' => 'extra-large',
                                    ]),

                                TextEntry::make('common_size_match')
                                    ->label('Common Size')
                                    ->badge()
                                    ->color('success')
                                    ->placeholder('Custom Size'),
                            ]),
                    ])
                    ->columns(1),

                Section::make('Product Information')
                    ->schema([
                        TextEntry::make('productVariant.name')
                            ->label('Cabinet Variant')
                            ->size('lg')
                            ->weight('bold')
                            ->color('primary'),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('unit_price_per_lf')
                                    ->label('Price per Linear Foot')
                                    ->money('USD')
                                    ->size('lg'),

                                TextEntry::make('total_price')
                                    ->label('Total Price')
                                    ->money('USD')
                                    ->size('lg')
                                    ->color('success'),
                            ]),
                    ]),

                Section::make('Dimensions')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('length_inches')
                                    ->label('Length')
                                    ->suffix(' inches')
                                    ->icon('heroicon-m-arrows-right-left')
                                    ->size('lg'),

                                TextEntry::make('width_inches')
                                    ->label('Width')
                                    ->suffix(' inches')
                                    ->icon('heroicon-m-arrows-up-down')
                                    ->size('lg'),

                                TextEntry::make('depth_inches')
                                    ->label('Depth')
                                    ->suffix(' inches')
                                    ->icon('heroicon-m-arrows-pointing-in')
                                    ->size('lg'),

                                TextEntry::make('height_inches')
                                    ->label('Height')
                                    ->suffix(' inches')
                                    ->icon('heroicon-m-arrow-trending-up')
                                    ->size('lg'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('linear_feet')
                                    ->label('Linear Feet')
                                    ->suffix(' LF')
                                    ->badge()
                                    ->color('info')
                                    ->size('lg'),

                                TextEntry::make('quantity')
                                    ->label('Quantity')
                                    ->suffix(' cabinets')
                                    ->badge()
                                    ->color('warning')
                                    ->size('lg'),
                            ]),
                    ])
                    ->columns(1),

                Section::make('Fabrication Details')
                    ->schema([
                        TextEntry::make('hardware_notes')
                            ->label('Hardware Specifications')
                            ->markdown()
                            ->placeholder('No hardware notes'),

                        TextEntry::make('custom_modifications')
                            ->label('Custom Modifications')
                            ->markdown()
                            ->placeholder('No custom modifications'),

                        TextEntry::make('shop_notes')
                            ->label('Shop Floor Notes')
                            ->markdown()
                            ->placeholder('No shop notes'),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => empty($record->hardware_notes) && empty($record->custom_modifications) && empty($record->shop_notes)),

                Section::make('Links & Relationships')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('project.name')
                                    ->label('Project')
                                    ->url(fn ($record) => $record->project_id
                                        ? route('filament.admin.products.resources.projects.view', ['record' => $record->project_id])
                                        : null
                                    )
                                    ->placeholder('No project link'),

                                TextEntry::make('orderLine.id')
                                    ->label('Order Line')
                                    ->badge()
                                    ->placeholder('No order line'),
                            ]),

                        TextEntry::make('creator.name')
                            ->label('Created By')
                            ->icon('heroicon-m-user'),
                    ])
                    ->collapsible(),

                Section::make('Timestamps')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime()
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime()
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('deleted_at')
                                    ->label('Deleted')
                                    ->dateTime()
                                    ->icon('heroicon-m-trash')
                                    ->placeholder('Active'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCabinetReports::route('/'),
            'view' => Pages\ViewCabinetReport::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['productVariant', 'project', 'orderLine', 'creator']);
    }
}
