<?php

namespace Webkul\Inventory\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FloorPlanHeatMapService
{
    // Factory floor reference point (from Polycam scan)
    private const FACTORY_LAT = 41.518394;
    private const FACTORY_LON = -74.007981;

    // Building dimensions in feet (from CSV)
    private const BUILDING_WIDTH_FT = 87.66;  // 87' 7.9"
    private const BUILDING_LENGTH_FT = 48.03; // 48' 0.2"

    // Floor plan image dimensions (PNG)
    private const IMAGE_WIDTH = 1702;
    private const IMAGE_HEIGHT = 3085;

    // Floor plan content bounds (approximate pixels where the actual floor starts/ends)
    private const FLOOR_LEFT = 65;
    private const FLOOR_TOP = 95;
    private const FLOOR_RIGHT = 1640;
    private const FLOOR_BOTTOM = 2920;

    private string $floorPlanPath;

    public function __construct()
    {
        $this->floorPlanPath = base_path('FloorPlan/[Polycam Floor Plan] 12_10_2025.png');
    }

    /**
     * Extract GPS coordinates from an image's EXIF data
     */
    public function extractGpsFromImage(string $imagePath): ?array
    {
        if (!file_exists($imagePath)) {
            return null;
        }

        $exif = @exif_read_data($imagePath, 'GPS', true);

        if (!$exif || !isset($exif['GPS']['GPSLatitude'])) {
            return null;
        }

        $gps = $exif['GPS'];

        $lat = $this->gpsToDecimal(
            $gps['GPSLatitude'],
            $gps['GPSLatitudeRef'] ?? 'N'
        );

        $lon = $this->gpsToDecimal(
            $gps['GPSLongitude'],
            $gps['GPSLongitudeRef'] ?? 'W'
        );

        return [
            'latitude' => $lat,
            'longitude' => $lon,
            'altitude' => $this->parseAltitude($gps['GPSAltitude'] ?? null),
        ];
    }

    /**
     * Convert GPS DMS (degrees, minutes, seconds) to decimal
     */
    private function gpsToDecimal(array $dms, string $ref): float
    {
        $degrees = $this->rationalToFloat($dms[0]);
        $minutes = $this->rationalToFloat($dms[1]);
        $seconds = $this->rationalToFloat($dms[2]);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if ($ref === 'S' || $ref === 'W') {
            $decimal *= -1;
        }

        return $decimal;
    }

    /**
     * Parse EXIF rational number (e.g., "41/1" or "123/100")
     */
    private function rationalToFloat(mixed $rational): float
    {
        if (is_numeric($rational)) {
            return (float) $rational;
        }

        if (is_string($rational) && str_contains($rational, '/')) {
            [$num, $den] = explode('/', $rational);
            return $den != 0 ? (float) $num / (float) $den : 0;
        }

        return 0;
    }

    private function parseAltitude(mixed $altitude): ?float
    {
        if ($altitude === null) {
            return null;
        }

        return $this->rationalToFloat($altitude);
    }

    /**
     * Convert GPS coordinates to floor plan pixel position
     */
    public function gpsToFloorPlanPosition(float $lat, float $lon): ?array
    {
        // Calculate offset from factory reference point in meters
        $latOffset = ($lat - self::FACTORY_LAT) * 111320; // ~111.32km per degree latitude
        $lonOffset = ($lon - self::FACTORY_LON) * 111320 * cos(deg2rad(self::FACTORY_LAT));

        // Convert meters to feet
        $latOffsetFt = $latOffset * 3.28084;
        $lonOffsetFt = $lonOffset * 3.28084;

        // Check if point is within building bounds (with some tolerance)
        $tolerance = 10; // feet
        if (abs($latOffsetFt) > (self::BUILDING_LENGTH_FT / 2 + $tolerance) ||
            abs($lonOffsetFt) > (self::BUILDING_WIDTH_FT / 2 + $tolerance)) {
            return null; // Outside building
        }

        // Map to floor plan pixels
        // Note: The floor plan is oriented with North up
        $floorWidth = self::FLOOR_RIGHT - self::FLOOR_LEFT;
        $floorHeight = self::FLOOR_BOTTOM - self::FLOOR_TOP;

        // Normalize position (0-1 range from building center)
        $normalizedX = ($lonOffsetFt / self::BUILDING_WIDTH_FT) + 0.5;
        $normalizedY = 0.5 - ($latOffsetFt / self::BUILDING_LENGTH_FT); // Invert Y

        // Clamp to valid range
        $normalizedX = max(0, min(1, $normalizedX));
        $normalizedY = max(0, min(1, $normalizedY));

        return [
            'x' => (int) (self::FLOOR_LEFT + ($normalizedX * $floorWidth)),
            'y' => (int) (self::FLOOR_TOP + ($normalizedY * $floorHeight)),
            'normalized_x' => $normalizedX,
            'normalized_y' => $normalizedY,
        ];
    }

    /**
     * Get all products with GPS-tagged images
     */
    public function getProductsWithGps(): array
    {
        $products = \Webkul\Product\Models\Product::whereNotNull('images')
            ->where('images', '!=', '[]')
            ->get();

        $results = [];

        foreach ($products as $product) {
            $images = is_array($product->images) ? $product->images : json_decode($product->images, true);

            if (empty($images)) {
                continue;
            }

            foreach ($images as $image) {
                $imagePath = storage_path('app/private/' . $image);

                if (!file_exists($imagePath)) {
                    $imagePath = storage_path('app/public/' . $image);
                }

                $gps = $this->extractGpsFromImage($imagePath);

                if ($gps) {
                    $position = $this->gpsToFloorPlanPosition($gps['latitude'], $gps['longitude']);

                    $results[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'image' => $image,
                        'gps' => $gps,
                        'floor_position' => $position,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Generate a heat map overlay on the floor plan
     */
    public function generateHeatMap(?array $points = null, ?string $outputPath = null): string
    {
        if ($points === null) {
            $points = $this->getProductsWithGps();
        }

        if ($outputPath === null) {
            $outputPath = storage_path('app/public/floor-plan-heatmap.png');
        }

        // Load floor plan image
        $manager = new ImageManager(new Driver());
        $image = $manager->read($this->floorPlanPath);

        // Draw heat map points
        foreach ($points as $point) {
            if (!isset($point['floor_position']) || $point['floor_position'] === null) {
                continue;
            }

            $x = $point['floor_position']['x'];
            $y = $point['floor_position']['y'];

            // Draw a gradient circle (heat spot)
            $this->drawHeatSpot($image, $x, $y);
        }

        // Save the result
        $image->save($outputPath);

        return $outputPath;
    }

    /**
     * Draw a heat spot at the given coordinates
     */
    private function drawHeatSpot($image, int $x, int $y, int $radius = 40): void
    {
        // Draw concentric circles with decreasing opacity for heat effect
        $colors = [
            ['r' => 255, 'g' => 0, 'b' => 0, 'a' => 0.6],    // Red center
            ['r' => 255, 'g' => 100, 'b' => 0, 'a' => 0.4],  // Orange
            ['r' => 255, 'g' => 200, 'b' => 0, 'a' => 0.2],  // Yellow outer
        ];

        foreach ($colors as $i => $color) {
            $currentRadius = $radius - ($i * ($radius / count($colors)));

            $image->drawCircle($x, $y, function ($circle) use ($currentRadius, $color) {
                $circle->radius($currentRadius);
                $circle->background(sprintf('rgba(%d, %d, %d, %.1f)',
                    $color['r'], $color['g'], $color['b'], $color['a']));
            });
        }

        // Draw center dot
        $image->drawCircle($x, $y, function ($circle) {
            $circle->radius(5);
            $circle->background('rgba(255, 0, 0, 1)');
        });
    }

    /**
     * Generate heat map with manual coordinates (for testing)
     */
    public function generateHeatMapFromCoordinates(array $coordinates, ?string $outputPath = null): string
    {
        if ($outputPath === null) {
            $outputPath = storage_path('app/public/floor-plan-heatmap.png');
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($this->floorPlanPath);

        foreach ($coordinates as $coord) {
            $x = $coord['x'] ?? null;
            $y = $coord['y'] ?? null;
            $label = $coord['label'] ?? null;

            if ($x !== null && $y !== null) {
                $this->drawHeatSpot($image, $x, $y);

                // Add label if provided
                if ($label) {
                    $image->text($label, $x, $y - 50, function ($font) {
                        $font->size(14);
                        $font->color('rgba(0, 0, 0, 0.8)');
                        $font->align('center');
                    });
                }
            }
        }

        $image->save($outputPath);

        return $outputPath;
    }

    /**
     * Get floor plan image path
     */
    public function getFloorPlanPath(): string
    {
        return $this->floorPlanPath;
    }

    /**
     * Get building info
     */
    public function getBuildingInfo(): array
    {
        return [
            'address' => '392 N Montgomery St, Newburgh, NY',
            'latitude' => self::FACTORY_LAT,
            'longitude' => self::FACTORY_LON,
            'width_ft' => self::BUILDING_WIDTH_FT,
            'length_ft' => self::BUILDING_LENGTH_FT,
            'area_sqft' => 4252.2,
            'floor_plan_image' => $this->floorPlanPath,
        ];
    }
}
