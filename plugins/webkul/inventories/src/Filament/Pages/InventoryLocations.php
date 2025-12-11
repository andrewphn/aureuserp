<?php

namespace Webkul\Inventory\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Inventory\Filament\Clusters\Operations;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Inventory\Services\FloorPlanHeatMapService;

class InventoryLocations extends Page implements HasForms
{
    use InteractsWithForms;
    // use HasPageShield; // Temporarily disabled for testing

    protected static ?string $cluster = Operations::class;

    protected string $view = 'inventories::filament.pages.inventory-locations';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 5;

    public ?int $selectedWarehouseId = null;

    public array $productLocations = [];

    public ?string $floorPlanUrl = null;

    public array $buildingInfo = [];

    public function mount(): void
    {
        $service = new FloorPlanHeatMapService();
        $this->buildingInfo = $service->getBuildingInfo();

        // Copy floor plan to public storage if not exists
        $this->ensureFloorPlanPublic();

        // Set default warehouse
        $warehouse = Warehouse::first();
        if ($warehouse) {
            $this->selectedWarehouseId = $warehouse->id;
        }

        $this->loadProductLocations();
    }

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

    public static function getNavigationLabel(): string
    {
        return 'Inventory Heat Map';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Inventory Location Heat Map';
    }

    public function getSubheading(): ?string
    {
        return $this->buildingInfo['address'] ?? '392 N Montgomery St, Newburgh, NY';
    }

    public function loadProductLocations(): void
    {
        $service = new FloorPlanHeatMapService();
        $this->productLocations = $service->getProductsWithGps();

        // Also load locations with manually set positions
        $locations = Location::whereNotNull('position_x')
            ->whereNotNull('position_y')
            ->with(['warehouse'])
            ->get();

        foreach ($locations as $location) {
            $this->productLocations[] = [
                'type' => 'location',
                'location_id' => $location->id,
                'location_name' => $location->full_name,
                'warehouse' => $location->warehouse?->name,
                'floor_position' => [
                    'x' => (int) $location->position_x,
                    'y' => (int) $location->position_y,
                ],
            ];
        }
    }

    public function generateHeatMap(): void
    {
        $service = new FloorPlanHeatMapService();

        try {
            $outputPath = $service->generateHeatMap();

            // Copy to public storage
            $publicPath = storage_path('app/public/floor-plans/heatmap-' . time() . '.png');
            copy($outputPath, $publicPath);

            Notification::make()
                ->title('Heat map generated successfully')
                ->success()
                ->send();

            $this->floorPlanUrl = asset('storage/floor-plans/' . basename($publicPath));

        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to generate heat map')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function scanProductImages(): void
    {
        $service = new FloorPlanHeatMapService();
        $products = $service->getProductsWithGps();

        $count = count($products);

        if ($count > 0) {
            Notification::make()
                ->title("Found {$count} products with GPS data")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('No products with GPS metadata found')
                ->body('Upload product images with GPS location enabled to see them on the heat map.')
                ->warning()
                ->send();
        }

        $this->productLocations = $products;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scan')
                ->label('Scan Product Images')
                ->icon('heroicon-o-magnifying-glass')
                ->action('scanProductImages'),

            Action::make('generate')
                ->label('Generate Heat Map')
                ->icon('heroicon-o-fire')
                ->color('danger')
                ->action('generateHeatMap'),

            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action('loadProductLocations'),
        ];
    }

    public function getViewData(): array
    {
        return [
            'floorPlanUrl' => $this->floorPlanUrl,
            'productLocations' => $this->productLocations,
            'buildingInfo' => $this->buildingInfo,
            'warehouses' => Warehouse::pluck('name', 'id'),
            'totalProducts' => count($this->productLocations),
        ];
    }
}
