<?php

namespace Webkul\Inventory\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use BackedEnum;
use Webkul\Inventory\Filament\Clusters\Operations;
use Webkul\Inventory\Services\WifiTriangulationService;

class WifiCalibration extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = Operations::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wifi';

    protected static ?string $navigationLabel = 'WiFi Calibration';

    protected static ?int $navigationSort = 6;

    protected string $view = 'inventories::filament.pages.wifi-calibration';

    public ?array $referencePoints = [];

    public ?array $newPoint = [
        'name' => '',
        'lat' => '',
        'lon' => '',
        'wifi_signal' => '',
    ];

    public ?array $testReading = [
        'signal' => '',
    ];

    public ?array $triangulatedPosition = null;

    public function mount(): void
    {
        $service = new WifiTriangulationService();
        $this->referencePoints = $service->getReferencePoints();
    }

    public function getTitle(): string|Htmlable
    {
        return 'WiFi Triangulation Calibration';
    }

    public function getSubheading(): ?string
    {
        $count = count($this->referencePoints);

        if ($count < 3) {
            return "Need " . (3 - $count) . " more reference point(s) for triangulation";
        }

        return "System calibrated with {$count} reference points";
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Add Reference Point')
                    ->description('Stand at an outdoor location with good GPS, then enter the coordinates and WiFi signal strength.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('newPoint.name')
                                    ->label('Location Name')
                                    ->placeholder('e.g., Front Door, Loading Dock')
                                    ->required(),

                                TextInput::make('newPoint.lat')
                                    ->label('GPS Latitude')
                                    ->placeholder('41.51832')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('newPoint.lon')
                                    ->label('GPS Longitude')
                                    ->placeholder('-74.00811')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('newPoint.wifi_signal')
                                    ->label('WiFi Signal (dBm)')
                                    ->placeholder('-65')
                                    ->numeric()
                                    ->helperText('Typical: -30 (excellent) to -90 (weak)')
                                    ->required(),
                            ]),
                    ]),

                Section::make('Test Triangulation')
                    ->description('Enter a WiFi signal reading to test position calculation.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('testReading.signal')
                                    ->label('Current WiFi Signal (dBm)')
                                    ->placeholder('-55')
                                    ->numeric(),

                                Placeholder::make('result')
                                    ->label('Triangulated Position')
                                    ->content(fn () => $this->triangulatedPosition
                                        ? "Lat: {$this->triangulatedPosition['latitude']}, Lon: {$this->triangulatedPosition['longitude']}"
                                        : 'Enter signal and click Test'),
                            ]),
                    ])
                    ->visible(fn () => count($this->referencePoints) >= 3),
            ])
            ->statePath('data');
    }

    public function addReferencePoint(): void
    {
        if (empty($this->newPoint['name']) || empty($this->newPoint['lat']) ||
            empty($this->newPoint['lon']) || empty($this->newPoint['wifi_signal'])) {
            Notification::make()
                ->title('Please fill in all fields')
                ->warning()
                ->send();

            return;
        }

        $service = new WifiTriangulationService();

        $service->setReferencePoint(
            $this->newPoint['name'],
            (float) $this->newPoint['lat'],
            (float) $this->newPoint['lon'],
            (float) $this->newPoint['wifi_signal']
        );

        $this->referencePoints = $service->getReferencePoints();

        // Clear form
        $this->newPoint = [
            'name' => '',
            'lat' => '',
            'lon' => '',
            'wifi_signal' => '',
        ];

        Notification::make()
            ->title('Reference point added!')
            ->success()
            ->send();
    }

    public function removeReferencePoint(string $name): void
    {
        $service = new WifiTriangulationService();
        $service->removeReferencePoint($name);
        $this->referencePoints = $service->getReferencePoints();

        Notification::make()
            ->title('Reference point removed')
            ->success()
            ->send();
    }

    public function testTriangulation(): void
    {
        if (empty($this->testReading['signal'])) {
            Notification::make()
                ->title('Please enter a WiFi signal strength')
                ->warning()
                ->send();

            return;
        }

        $service = new WifiTriangulationService();
        $this->triangulatedPosition = $service->triangulateFromSignal((float) $this->testReading['signal']);

        if ($this->triangulatedPosition) {
            Notification::make()
                ->title('Position calculated!')
                ->body("Method: {$this->triangulatedPosition['method']}, Accuracy: ~{$this->triangulatedPosition['accuracy_meters']}m")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Could not triangulate')
                ->body('Need at least 3 reference points')
                ->warning()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addPoint')
                ->label('Add Reference Point')
                ->icon('heroicon-o-plus')
                ->action('addReferencePoint'),

            Action::make('test')
                ->label('Test Triangulation')
                ->icon('heroicon-o-map-pin')
                ->color('success')
                ->action('testTriangulation')
                ->visible(fn () => count($this->referencePoints) >= 3),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'referencePoints' => $this->referencePoints,
            'calibrationStatus' => (new WifiTriangulationService())->getCalibrationStatus(),
            'triangulatedPosition' => $this->triangulatedPosition,
        ];
    }
}
