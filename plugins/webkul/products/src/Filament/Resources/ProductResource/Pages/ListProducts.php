<?php

namespace Webkul\Product\Filament\Resources\ProductResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Hydrat\TableLayoutToggle\Concerns\HasToggleableTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Filament\Resources\ProductResource;
use Webkul\Product\Models\Product;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Products class
 *
 * @see \Filament\Resources\Resource
 */
class ListProducts extends ListRecords
{
    use HasTableViews;
    use HasToggleableTable;

    protected static string $resource = ProductResource::class;

    /**
     * Grid size preference (small, medium, large)
     * Persisted in URL for bookmarking/sharing
     */
    #[Url]
    public string $gridSize = 'medium';

    /**
     * Grid size configurations
     * small = more cards per row (compact)
     * medium = balanced (default)
     * large = fewer cards per row (bigger cards)
     */
    protected array $gridSizeConfigs = [
        'small' => [
            'md' => 3,
            'lg' => 4,
            'xl' => 6,
            'imageHeight' => 120,
        ],
        'medium' => [
            'md' => 2,
            'lg' => 3,
            'xl' => 4,
            'imageHeight' => 180,
        ],
        'large' => [
            'md' => 1,
            'lg' => 2,
            'xl' => 3,
            'imageHeight' => 240,
        ],
    ];

    /**
     * Default to grid layout for retail-style browsing
     */
    public function getDefaultLayoutView(): string
    {
        return 'grid';
    }

    /**
     * Set the grid size and refresh the table
     */
    public function setGridSize(string $size): void
    {
        $this->gridSize = $size;
        $this->resetTable();
    }

    /**
     * Get current grid configuration
     */
    protected function getGridConfig(): array
    {
        return $this->gridSizeConfigs[$this->gridSize] ?? $this->gridSizeConfigs['medium'];
    }

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        $gridConfig = $this->getGridConfig();

        return parent::table($table)
            ->columns(
                $this->isGridLayout()
                    ? $this->getGridTableColumns()
                    : $this->getListTableColumns()
            )
            ->contentGrid(
                fn () => $this->isListLayout()
                    ? null
                    : [
                        'md' => $gridConfig['md'],
                        'lg' => $gridConfig['lg'],
                        'xl' => $gridConfig['xl'],
                    ]
            )
            ->modifyQueryUsing(function (Builder $query) {
                $query->whereNull('parent_id');
            });
    }

    /**
     * Get columns for list layout (traditional table)
     */
    public function getListTableColumns(): array
    {
        return [
            IconColumn::make('is_favorite')
                ->label('')
                ->icon(fn (Product $record): string => $record->is_favorite ? 'heroicon-s-star' : 'heroicon-o-star')
                ->color(fn (Product $record): string => $record->is_favorite ? 'warning' : 'gray')
                ->action(function (Product $record): void {
                    $record->update(['is_favorite' => ! $record->is_favorite]);
                }),
            ImageColumn::make('images')
                ->label(__('products::filament/resources/product.table.columns.images'))
                ->placeholder('—')
                ->circular()
                ->stacked()
                ->limit(3)
                ->limitedRemainingText(),
            TextColumn::make('name')
                ->label(__('products::filament/resources/product.table.columns.name'))
                ->searchable()
                ->sortable(),
            TextColumn::make('variants_count')
                ->label(__('products::filament/resources/product.table.columns.variants'))
                ->placeholder('—')
                ->counts('variants')
                ->sortable(),
            TextColumn::make('reference')
                ->label(__('products::filament/resources/product.table.columns.reference'))
                ->placeholder('—')
                ->searchable()
                ->sortable(),
            TextColumn::make('tags.name')
                ->label(__('products::filament/resources/product.table.columns.tags'))
                ->placeholder('—')
                ->badge()
                ->toggleable(),
            TextColumn::make('price')
                ->label(__('products::filament/resources/product.table.columns.price'))
                ->sortable(),
            TextColumn::make('cost')
                ->label(__('products::filament/resources/product.table.columns.cost'))
                ->sortable(),
        ];
    }

    /**
     * Get columns for grid layout (retail card style)
     * Amazon/retail inspired: Big image, clear name, prominent price
     */
    public function getGridTableColumns(): array
    {
        $imageHeight = $this->getGridConfig()['imageHeight'];

        return [
            Stack::make([
                // Product image - hero element (dynamic height based on grid size)
                ImageColumn::make('images')
                    ->height($imageHeight)
                    ->width('100%')
                    ->extraImgAttributes(['class' => 'object-contain w-full rounded-t-lg bg-gray-50'])
                    ->defaultImageUrl(fn () => 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full text-gray-300"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>')),

                // Content section
                Stack::make([
                    // Category badge
                    TextColumn::make('category.name')
                        ->badge()
                        ->color('gray')
                        ->size('xs'),

                    // Product name - prominent
                    TextColumn::make('name')
                        ->weight(FontWeight::SemiBold)
                        ->size('md')
                        ->wrap()
                        ->lineClamp(2)
                        ->searchable(),

                    // SKU
                    TextColumn::make('reference')
                        ->size('xs')
                        ->color('gray')
                        ->prefix('SKU: ')
                        ->placeholder(''),

                    // Price + Favorite
                    Split::make([
                        TextColumn::make('price')
                            ->money('USD')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->color('primary'),
                        IconColumn::make('is_favorite')
                            ->icon(fn (Product $record): string => $record->is_favorite ? 'heroicon-s-star' : 'heroicon-o-star')
                            ->color(fn (Product $record): string => $record->is_favorite ? 'warning' : 'gray')
                            ->size('lg')
                            ->action(function (Product $record): void {
                                $record->update(['is_favorite' => ! $record->is_favorite]);
                            }),
                    ])->from('md'),

                    // Tags
                    TextColumn::make('tags.name')
                        ->badge()
                        ->color('info')
                        ->size('xs')
                        ->limit(3)
                        ->separator(' '),

                ])->space(2)->extraAttributes(['class' => 'p-3']),
            ])
            ->extraAttributes([
                'class' => 'bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow cursor-pointer',
            ]),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'goods_products' => PresetView::make(__('products::filament/resources/product/pages/list-products.tabs.goods'))
                ->icon('heroicon-s-squares-plus')
                ->favorite()
                ->setAsDefault()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', ProductType::GOODS)),

            'services_products' => PresetView::make(__('products::filament/resources/product/pages/list-products.tabs.services'))
                ->icon('heroicon-s-sparkles')
                ->favorite()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', ProductType::SERVICE)),

            'favorites_products' => PresetView::make(__('products::filament/resources/product/pages/list-products.tabs.favorites'))
                ->icon('heroicon-s-star')
                ->favorite()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_favorite', true)),

            'archived_products' => PresetView::make(__('products::filament/resources/product/pages/list-products.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->favorite()
                ->modifyQueryUsing(function ($query) {
                    return $query->onlyTrashed();
                }),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Inline grid size slider - only visible in grid layout
            Action::make('gridSizeSlider')
                ->view('products::filament.resources.product.pages.grid-size-slider', [
                    'gridSize' => $this->gridSize,
                ])
                ->visible(fn () => $this->isGridLayout()),

            CreateAction::make()
                ->label(__('products::filament/resources/product/pages/list-products.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
