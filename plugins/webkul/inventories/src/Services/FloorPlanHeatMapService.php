<?php

namespace Webkul\Inventory\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FloorPlanHeatMapService
{
    // Factory floor corner coordinates
    // UPDATED 12/10/2025: Based on Google Maps satellite measurement of full building
    // Building dimensions: ~90 ft wide (E-W) x ~130 ft long (N-S)
    // Conversion factors at lat 41.518°:
    //   1 ft ≈ 0.00000275° latitude
    //   1 ft ≈ 0.00000489° longitude

    // Bottom left corner (SW) - user provided anchor point
    private const BOTTOM_LEFT_LAT = 41.51832616862088;
    private const BOTTOM_LEFT_LON = -74.00811882010751;

    // Bottom right corner (SE) - anchor + 90ft east (90 × 0.00000489 = 0.00044° lon)
    private const BOTTOM_RIGHT_LAT = 41.51832616862088;
    private const BOTTOM_RIGHT_LON = -74.00767882010751;

    // Top left corner (NW) - anchor + 130ft north (130 × 0.00000275 = 0.000358° lat)
    private const TOP_LEFT_LAT = 41.51868416862088;
    private const TOP_LEFT_LON = -74.00811882010751;

    // Top right corner (NE)
    private const TOP_RIGHT_LAT = 41.51868416862088;
    private const TOP_RIGHT_LON = -74.00767882010751;

    // Building dimensions in feet (from Google Maps measurement)
    private const BUILDING_WIDTH_FT = 90.0;   // E-W dimension
    private const BUILDING_LENGTH_FT = 130.0; // N-S dimension (full warehouse)

    // Floor plan image dimensions (PNG)
    private const IMAGE_WIDTH = 1702;
    private const IMAGE_HEIGHT = 3085;

    // Floor plan content bounds (pixels where the building is drawn in the PNG)
    // These are calibrated to match the Polycam floor plan export
    private const FLOOR_LEFT = 40;
    private const FLOOR_TOP = 160;
    private const FLOOR_RIGHT = 290;
    private const FLOOR_BOTTOM = 860;

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
     * Uses corner coordinates for accurate mapping
     */
    public function gpsToFloorPlanPosition(float $lat, float $lon): ?array
    {
        // Building bounds from corner coordinates
        $minLat = min(self::BOTTOM_LEFT_LAT, self::BOTTOM_RIGHT_LAT);
        $maxLat = max(self::TOP_LEFT_LAT, self::TOP_RIGHT_LAT);
        $minLon = min(self::BOTTOM_LEFT_LON, self::TOP_LEFT_LON);
        $maxLon = max(self::BOTTOM_RIGHT_LON, self::TOP_RIGHT_LON);

        // Add tolerance for GPS drift (about 20 meters = ~0.0002 degrees)
        // Also accounts for interior photos being slightly outside calculated bounds
        $tolerance = 0.0003;

        // Check if point is within building bounds (with tolerance)
        if ($lat < ($minLat - $tolerance) || $lat > ($maxLat + $tolerance) ||
            $lon < ($minLon - $tolerance) || $lon > ($maxLon + $tolerance)) {
            return null; // Outside building
        }

        // Calculate normalized position (0-1 range)
        $latRange = $maxLat - $minLat;
        $lonRange = $maxLon - $minLon;

        // X: longitude (left to right, west to east)
        // Lon increases going east (right), so normalize directly
        $normalizedX = ($lon - $minLon) / $lonRange;

        // Y: latitude (top to bottom in image, but north is up)
        // Higher latitude = north = top of building = top of floor plan image
        // Floor plan Y increases downward, so invert
        $normalizedY = 1 - (($lat - $minLat) / $latRange);

        // Clamp to valid range
        $normalizedX = max(0, min(1, $normalizedX));
        $normalizedY = max(0, min(1, $normalizedY));

        // Map to floor plan pixels
        $floorWidth = self::FLOOR_RIGHT - self::FLOOR_LEFT;
        $floorHeight = self::FLOOR_BOTTOM - self::FLOOR_TOP;

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
            'latitude' => self::BOTTOM_LEFT_LAT,
            'longitude' => self::BOTTOM_LEFT_LON,
            'width_ft' => self::BUILDING_WIDTH_FT,
            'length_ft' => self::BUILDING_LENGTH_FT,
            'area_sqft' => self::BUILDING_WIDTH_FT * self::BUILDING_LENGTH_FT,
            'floor_plan_image' => $this->floorPlanPath,
        ];
    }
}
