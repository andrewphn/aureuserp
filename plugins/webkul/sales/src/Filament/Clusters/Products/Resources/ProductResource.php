<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources;

use Webkul\Sale\Filament\Clusters\Products;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource\Pages;
use Webkul\Sale\Models\Product;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Infolists\Infolist;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Support\Colors\Color;
use Filament\Tables\Table;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Enums\TypeTaxUse;
use Webkul\Account\Models\Tax;
use Webkul\Product\Enums\ProductType;
use Webkul\Field\Filament\Traits\HasCustomFields;
use Webkul\Product\Filament\Resources\ProductResource as BaseProductResource;
use Webkul\Sale\Enums\InvoicePolicy;
use Webkul\Support\Models\UOM;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\ManageProducts;
use Webkul\Sale\Models\ProductCategory;

class ProductResource extends BaseProductResource
{
    use HasCustomFields;

    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Products::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/products/resources/product.navigation.title');
    }

    public static function form(Form $form): Form
    {
        $form = BaseProductResource::form($form);

        $components = $form->getComponents();

        $firstGroupChildComponents = $components[0]->getChildComponents();

        $secondChildComponents = $firstGroupChildComponents[0]->getChildComponents();

        $favoriteAction = Forms\Components\Actions::make([
            Forms\Components\Actions\Action::make('is_favorite')
                ->hiddenLabel()
                ->outlined(false)
                ->icon(fn($record) => $record?->is_favorite >= 1 ? 'heroicon-s-star' : 'heroicon-o-star')
                ->color('warning')
                ->iconButton()
                ->size(ActionSize::Large->value)
                ->action(fn($record) => $record?->update(['is_favorite' => ! $record->is_favorite,])),
        ]);

        array_unshift($secondChildComponents, $favoriteAction);

        $firstGroupChildComponents[0]->childComponents($secondChildComponents);

        $form->components($components);

        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('')
                    ->icon(fn(Product $record): string => $record->is_favorite ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn(Product $record): string => $record->is_favorite ? 'warning' : 'gray')
                    ->action(function (Product $record): void {
                        $record->update([
                            'is_favorite' => ! $record->is_favorite,
                        ]);
                    }),
                Tables\Columns\ImageColumn::make('images')
                    ->label(__('sales::filament/clusters/products/resources/product.table.columns.images'))
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText(isSeparate: true),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('sales::filament/clusters/products/resources/product.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('sales::filament/clusters/products/resources/product.table.columns.reference'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tags.name')
                    ->label(__('sales::filament/clusters/products/resources/product.table.columns.tags'))
                    ->badge()
                    ->state(function (Product $record): array {
                        return $record->tags->map(fn($tag) => [
                            'label' => $tag->name,
                            'color' => $tag->color ?? 'primary',
                        ])->toArray();
                    })
                    ->formatStateUsing(fn($state) => $state['label'])
                    ->color(fn($state) => Color::hex($state['color']))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->label(__('sales::filament/clusters/products/resources/product.table.columns.barcode'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('sales::filament/clusters/products/resources/product.table.columns.company'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label(__('sales::filament/clusters/products/resources/product.table.columns.price'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost')
                    ->label(__('sales::filament/clusters/products/resources/product.table.columns.cost'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('sales::filament/clusters/products/resources/product.table.columns.category'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('sales::filament/clusters/products/resources/product.table.columns.type'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label(__('sales::filament/clusters/products/resources/product.table.columns.deleted-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('sales::filament/clusters/products/resources/product.table.columns.created-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('sales::filament/clusters/products/resources/product.table.columns.updated-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Tables\Grouping\Group::make('type')
                    ->label(__('sales::filament/clusters/products/resources/product.table.groups.type')),
                Tables\Grouping\Group::make('category.name')
                    ->label(__('sales::filament/clusters/products/resources/product.table.groups.category')),
                Tables\Grouping\Group::make('created_at')
                    ->label(__('sales::filament/clusters/products/resources/product.table.groups.created-at'))
                    ->date(),
            ])
            ->reorderable('sort')
            ->defaultSort('sort', 'desc')
            ->filters([
                Tables\Filters\QueryBuilder::make()
                    ->constraints(collect(static::mergeCustomTableQueryBuilderConstraints([
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('name')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.name')),
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('reference')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.reference'))
                            ->icon('heroicon-o-link'),
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('barcode')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.barcode'))
                            ->icon('heroicon-o-bars-4'),
                        Tables\Filters\QueryBuilder\Constraints\BooleanConstraint::make('is_favorite')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.is-favorite'))
                            ->icon('heroicon-o-star'),
                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('price')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.price'))
                            ->icon('heroicon-o-banknotes'),
                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('cost')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.cost'))
                            ->icon('heroicon-o-banknotes'),
                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('weight')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.weight'))
                            ->icon('heroicon-o-scale'),
                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('volume')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.volume'))
                            ->icon('heroicon-o-beaker'),
                        Tables\Filters\QueryBuilder\Constraints\SelectConstraint::make('type')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.type'))
                            ->multiple()
                            ->options(ProductType::class)
                            ->icon('heroicon-o-queue-list'),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('tags')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.tags'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-tag'),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('created_at')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.created-at')),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('updated_at')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.updated-at')),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('company')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.company'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-building-office'),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('creator')
                            ->label(__('sales::filament/clusters/products/resources/product.table.filters.creator'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-user'),
                    ]))->filter()->values()->all()),
            ], layout: \Filament\Tables\Enums\FiltersLayout::Modal)
            ->filtersTriggerAction(
                fn(Tables\Actions\Action $action) => $action
                    ->slideOver(),
            )
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    Tables\Actions\EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    Tables\Actions\RestoreAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/products/resources/product.table.actions.restore.notification.title'))
                                ->body(__('sales::filament/clusters/products/resources/product.table.actions.restore.notification.body')),
                        ),
                    Tables\Actions\DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/products/resources/product.table.actions.delete.notification.title'))
                                ->body(__('sales::filament/clusters/products/resources/product.table.actions.delete.notification.body')),
                        ),
                    Tables\Actions\ForceDeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/products/resources/product.table.actions.force-delete.notification.title'))
                                ->body(__('sales::filament/clusters/products/resources/product.table.actions.force-delete.notification.body')),
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/products/resources/product.table.bulk-actions.restore.notification.title'))
                                ->body(__('sales::filament/clusters/products/resources/product.table.bulk-actions.restore.notification.body')),
                        ),
                    Tables\Actions\DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/products/resources/product.table.bulk-actions.delete.notification.title'))
                                ->body(__('sales::filament/clusters/products/resources/product.table.bulk-actions.delete.notification.body')),
                        ),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/products/resources/product.table.bulk-actions.force-delete.notification.title'))
                                ->body(__('sales::filament/clusters/products/resources/product.table.bulk-actions.force-delete.notification.body')),
                        ),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Group::make()
                    ->schema([
                        Infolists\Components\Section::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label(__('sales::filament/clusters/products/resources/product.infolist.sections.general.entries.name')),

                                Infolists\Components\TextEntry::make('description')
                                    ->label(__('sales::filament/clusters/products/resources/product.infolist.sections.general.entries.description'))
                                    ->html()
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('tags.name')
                                    ->label(__('sales::filament/clusters/products/resources/product.infolist.sections.general.entries.tags'))
                                    ->badge()
                                    ->separator(', ')
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold),
                            ]),

                        Infolists\Components\Section::make(__('sales::filament/clusters/products/resources/product.infolist.sections.images.title'))
                            ->schema([
                                Infolists\Components\ImageEntry::make('images')
                                    ->hiddenLabel()
                                    ->circular(),
                            ])
                            ->visible(fn($record): bool => ! empty($record->images)),
                    ])
                    ->columnSpan(['lg' => 2]),

                Infolists\Components\Group::make()
                    ->schema([
                        Infolists\Components\Section::make(__('sales::filament/clusters/products/resources/product.infolist.sections.record-information.title'))
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label(__('sales::filament/clusters/products/resources/product.infolist.sections.record-information.entries.created-at'))
                                    ->dateTime()
                                    ->icon('heroicon-o-calendar'),

                                Infolists\Components\TextEntry::make('creator.name')
                                    ->label(__('sales::filament/clusters/products/resources/product.infolist.sections.record-information.entries.created-by'))
                                    ->icon('heroicon-o-user'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label(__('sales::filament/clusters/products/resources/product.infolist.sections.record-information.entries.updated-at'))
                                    ->dateTime()
                                    ->icon('heroicon-o-calendar'),
                            ]),

                        Infolists\Components\Section::make(__('sales::filament/clusters/products/resources/product.infolist.sections.settings.title'))
                            ->schema([
                                Infolists\Components\TextEntry::make('type')
                                    ->label(__('sales::filament/clusters/products/resources/product.infolist.sections.settings.entries.type'))
                                    ->placeholder('—')
                                    ->icon('heroicon-o-queue-list'),

                                Infolists\Components\TextEntry::make('reference')
                                    ->label(__('sales::filament/clusters/products/resources/product.infolist.sections.settings.entries.reference'))
                                    ->placeholder('—')
                                    ->icon('heroicon-o-identification'),

                                Infolists\Components\TextEntry::make('barcode')
                                    ->label(__('sales::filament/clusters/products/resources/product.infolist.sections.settings.entries.barcode'))
                                    ->placeholder('—')
                                    ->icon('heroicon-o-bars-4'),

                                Infolists\Components\TextEntry::make('category.full_name')
                                    ->label(__('sales::filament/clusters/products/resources/product.infolist.sections.settings.entries.category'))
                                    ->placeholder('—')
                                    ->icon('heroicon-o-folder'),

                                Infolists\Components\TextEntry::make('company.name')
                                    ->label(__('sales::filament/clusters/products/resources/product.infolist.sections.settings.entries.company'))
                                    ->placeholder('—')
                                    ->icon('heroicon-o-building-office'),
                            ]),

                        Infolists\Components\Section::make(__('sales::filament/clusters/products/resources/product.infolist.sections.pricing.title'))
                            ->schema([
                                Infolists\Components\TextEntry::make('price')
                                    ->label(__('sales::filament/clusters/products/resources/product.infolist.sections.pricing.entries.price'))
                                    ->placeholder('—')
                                    ->icon('heroicon-o-banknotes'),

                                Infolists\Components\TextEntry::make('cost')
                                    ->label(__('sales::filament/clusters/products/resources/product.infolist.sections.pricing.entries.cost'))
                                    ->placeholder('—')
                                    ->icon('heroicon-o-banknotes'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewProduct::class,
            Pages\EditProduct::class,
            Pages\ManageAttributes::class,
            Pages\ManageVariants::class,
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
            'index'      => Pages\ListProducts::route('/'),
            'create'     => Pages\CreateProduct::route('/create'),
            'view'       => Pages\ViewProduct::route('/{record}'),
            'edit'       => Pages\EditProduct::route('/{record}/edit'),
            'attributes' => Pages\ManageAttributes::route('/{record}/attributes'),
            'variants'   => Pages\ManageVariants::route('/{record}/variants'),
        ];
    }
}
