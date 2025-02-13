<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Webkul\Product\Filament\Resources\CategoryResource;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Webkul\Inventory\Filament\Clusters\Configurations;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource\Pages;
use Webkul\Inventory\Models\Category;

class ProductCategoryResource extends CategoryResource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 8;

    protected static ?string $cluster = Configurations::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        return __('inventories::filament/clusters/configurations/resources/product-category.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('inventories::filament/clusters/configurations/resources/product-category.navigation.title');
    }

    public static function form(Form $form): Form
    {
        $form = CategoryResource::form($form);

        $components = $form->getComponents();

        $childComponents = $components[1]->getChildComponents();

        $childComponents[] = Forms\Components\Section::make(__('inventories::filament/clusters/configurations/resources/product-category.form.sections.inventory.title'))
            ->schema([
                Forms\Components\Fieldset::make(__('inventories::filament/clusters/configurations/resources/product-category.form.sections.inventory.fieldsets.logistics.title'))
                    ->schema([
                        Forms\Components\Select::make('routes')
                            ->label(__('inventories::filament/clusters/configurations/resources/product-category.form.sections.inventory.fieldsets.logistics.fields.routes'))
                            ->relationship('routes', 'name')
                            ->searchable()
                            ->preload()
                            ->multiple(),
                    ])
                    ->columns(1),
            ]);

        $components[1]->childComponents($childComponents);

        $form->components($components); 

        return $form;
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        $currentRoute = request()->route()?->getName();

        if ($currentRoute === self::getRouteBaseName().'.index') {
            return SubNavigationPosition::Start;
        }

        return SubNavigationPosition::Top;
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewProductCategory::class,
            Pages\EditProductCategory::class,
            Pages\ManageProducts::class,
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
            'index'    => Pages\ListProductCategories::route('/'),
            'create'   => Pages\CreateProductCategory::route('/create'),
            'view'     => Pages\ViewProductCategory::route('/{record}'),
            'edit'     => Pages\EditProductCategory::route('/{record}/edit'),
            'products' => Pages\ManageProducts::route('/{record}/products'),
        ];
    }
}
