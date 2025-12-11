<?php

namespace Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource\Pages;

use Filament\Actions\Action;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Inventory\Services\FloorPlanHeatMapService;
use Webkul\Product\Filament\Resources\ProductResource\Pages\ListProducts as BaseListProducts;
use Webkul\TableViews\Filament\Components\PresetView;

/**
 * List Products class with Map View toggle
 *
 * @see \Filament\Resources\Resource
 */
class ListProducts extends BaseListProducts
{
    protected static string $resource = ProductResource::class;

    /**
     * View mode: 'table' or 'map'
     */
    #[Url]
    public string $viewMode = 'table';

    /**
     * Floor plan URL for map view
     */
    public ?string $floorPlanUrl = null;

    /**
     * Products with GPS coordinates
     */
    public array $productLocations = [];

    /**
     * Building info from service
     */
    public array $buildingInfo = [];

    public function mount(): void
    {
        parent::mount();
        $this->loadMapData();
    }

    /**
     * Load floor plan and product location data for map view
     */
    protected function loadMapData(): void
    {
        $service = new FloorPlanHeatMapService();
        $this->buildingInfo = $service->getBuildingInfo();
        $this->productLocations = $service->getProductsWithGps();

        // Ensure floor plan is available
        $this->ensureFloorPlanPublic();
    }

    /**
     * Copy floor plan to public storage if not exists
     */
    protected function ensureFloorPlanPublic(): void
    {
        $sourcePath = base_path('FloorPlan/[Polycam Floor Plan] 12_10_2025.png');
        $targetPath = storage_path('app/public/floor-plans/factory-floor.png');

        if (!file_exists($targetPath) && file_exists($sourcePath)) {
            $dir = dirname($targetPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            copy($sourcePath, $targetPath);
        }

        $this->floorPlanUrl = asset('storage/floor-plans/factory-floor.png');
    }

    /**
     * Toggle to map view
     */
    public function showMapView(): void
    {
        $this->viewMode = 'map';
        $this->loadMapData();
    }

    /**
     * Toggle to table view
     */
    public function showTableView(): void
    {
        $this->viewMode = 'table';
    }

    /**
     * Refresh map data
     */
    public function refreshMapData(): void
    {
        $this->loadMapData();
    }

    public function getPresetTableViews(): array
    {
        return array_merge(parent::getPresetTableViews(), [
            'storable_products' => PresetView::make(__('inventories::filament/clusters/products/resources/product/pages/list-products.tabs.inventory-management'))
                ->icon('heroicon-s-clipboard-document-list')
                ->favorite()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_storable', true)),
        ]);
    }

    protected function getHeaderActions(): array
    {
        $parentActions = parent::getHeaderActions();

        // Add map view toggle actions at the beginning
        $mapActions = [
            Action::make('tableView')
                ->label('Table View')
                ->icon('heroicon-o-table-cells')
                ->color($this->viewMode === 'table' ? 'primary' : 'gray')
                ->action('showTableView'),

            Action::make('mapView')
                ->label('Map View')
                ->icon('heroicon-o-map')
                ->color($this->viewMode === 'map' ? 'primary' : 'gray')
                ->badge(fn () => count($this->productLocations) > 0 ? count($this->productLocations) : null)
                ->action('showMapView'),
        ];

        // Add refresh button when in map mode
        if ($this->viewMode === 'map') {
            $mapActions[] = Action::make('refreshMap')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('refreshMapData');
        }

        return array_merge($mapActions, $parentActions);
    }

    /**
     * Get the view for the page
     */
    protected string $view = 'inventories::filament.clusters.products.resources.product.pages.list-products';

    /**
     * Pass additional data to the view
     */
    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'viewMode' => $this->viewMode,
            'floorPlanUrl' => $this->floorPlanUrl,
            'productLocations' => $this->productLocations,
            'buildingInfo' => $this->buildingInfo,
            'totalProducts' => count($this->productLocations),
        ]);
    }
}
