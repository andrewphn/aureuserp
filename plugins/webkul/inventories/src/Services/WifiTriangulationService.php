<?php

namespace Webkul\Inventory\Services;

/**
 * WiFi Triangulation Service
 *
 * Uses 3 outdoor reference points with known GPS and WiFi signal strength
 * to triangulate indoor positions based on current WiFi signal readings.
 *
 * How it works:
 * 1. Calibrate: Stand at 3 outdoor spots, record GPS + WiFi signal strength
 * 2. Indoor: Measure WiFi signal, compare to reference points
 * 3. Triangulate: Calculate position based on signal strength ratios
 */
class WifiTriangulationService
{
    /**
     * Reference points for triangulation
     * Each point has: name, lat, lon, wifi_signal (dBm), floor_x, floor_y
     */
    private array $referencePoints = [];

    /**
     * Path loss exponent (n) for indoor environments
     * Typical values: 2 (free space), 2.5-3.5 (indoor with walls)
     */
    private float $pathLossExponent = 2.8;

    /**
     * Reference signal strength at 1 meter (TxPower)
     * Typical WiFi: -40 to -50 dBm at 1 meter
     */
    private float $txPower = -45.0;

    public function __construct()
    {
        $this->loadReferencePoints();
    }

    /**
     * Load reference points from config/database
     */
    private function loadReferencePoints(): void
    {
        // Load from config file if exists
        $configPath = base_path('FloorPlan/wifi-reference-points.json');

        if (file_exists($configPath)) {
            $data = json_decode(file_get_contents($configPath), true);
            $this->referencePoints = $data['reference_points'] ?? [];
            $this->pathLossExponent = $data['path_loss_exponent'] ?? 2.8;
            $this->txPower = $data['tx_power'] ?? -45.0;
        }
    }

    /**
     * Save reference points to config file
     */
    public function saveReferencePoints(): void
    {
        $configPath = base_path('FloorPlan/wifi-reference-points.json');

        $data = [
            'reference_points' => $this->referencePoints,
            'path_loss_exponent' => $this->pathLossExponent,
            'tx_power' => $this->txPower,
            'updated_at' => now()->toIso8601String(),
        ];

        file_put_contents($configPath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Add or update a reference point
     *
     * @param string $name Friendly name (e.g., "Front Door", "Loading Dock")
     * @param float $lat GPS latitude (from outdoor position)
     * @param float $lon GPS longitude (from outdoor position)
     * @param float $wifiSignal WiFi signal strength in dBm (e.g., -65)
     * @param string|null $ssid WiFi network name (optional)
     */
    public function setReferencePoint(
        string $name,
        float $lat,
        float $lon,
        float $wifiSignal,
        ?string $ssid = null
    ): void {
        $this->referencePoints[$name] = [
            'name' => $name,
            'lat' => $lat,
            'lon' => $lon,
            'wifi_signal' => $wifiSignal,
            'ssid' => $ssid,
            'created_at' => now()->toIso8601String(),
        ];

        $this->saveReferencePoints();
    }

    /**
     * Get all reference points
     */
    public function getReferencePoints(): array
    {
        return $this->referencePoints;
    }

    /**
     * Remove a reference point
     */
    public function removeReferencePoint(string $name): void
    {
        unset($this->referencePoints[$name]);
        $this->saveReferencePoints();
    }

    /**
     * Estimate distance from signal strength using path loss model
     *
     * Formula: distance = 10^((TxPower - RSSI) / (10 * n))
     *
     * @param float $rssi Signal strength in dBm
     * @return float Estimated distance in meters
     */
    public function signalToDistance(float $rssi): float
    {
        if ($rssi >= 0) {
            return 0; // Invalid signal
        }

        $distance = pow(10, ($this->txPower - $rssi) / (10 * $this->pathLossExponent));

        return max(0.1, $distance); // Minimum 0.1 meters
    }

    /**
     * Triangulate position from current WiFi signal strength
     *
     * @param float $currentSignal Current WiFi signal strength in dBm
     * @return array|null Position data or null if can't triangulate
     */
    public function triangulateFromSignal(float $currentSignal): ?array
    {
        if (count($this->referencePoints) < 3) {
            return null; // Need at least 3 reference points
        }

        // Calculate estimated distance from each reference point
        // based on signal strength ratio
        $points = array_values($this->referencePoints);
        $circles = [];

        foreach ($points as $point) {
            // Signal difference indicates relative distance change
            $signalDiff = $currentSignal - $point['wifi_signal'];

            // Estimate distance ratio
            // Stronger signal (less negative) = closer
            // Each ~6 dBm difference ≈ 2x distance change
            $distanceRatio = pow(10, $signalDiff / (10 * $this->pathLossExponent));

            // Assume reference point is at some base distance (e.g., 20m from center)
            $baseDistance = 20; // meters
            $estimatedDistance = $baseDistance * $distanceRatio;

            $circles[] = [
                'lat' => $point['lat'],
                'lon' => $point['lon'],
                'radius' => $estimatedDistance,
                'name' => $point['name'],
            ];
        }

        // Trilaterate using first 3 circles
        return $this->trilaterate(
            $circles[0]['lat'], $circles[0]['lon'], $circles[0]['radius'],
            $circles[1]['lat'], $circles[1]['lon'], $circles[1]['radius'],
            $circles[2]['lat'], $circles[2]['lon'], $circles[2]['radius']
        );
    }

    /**
     * Triangulate position from multiple WiFi readings
     *
     * @param array $readings Array of ['reference_name' => signal_strength]
     * @return array|null Position data
     */
    public function triangulateFromMultipleReadings(array $readings): ?array
    {
        if (count($readings) < 3) {
            return null;
        }

        $circles = [];

        foreach ($readings as $name => $signal) {
            if (!isset($this->referencePoints[$name])) {
                continue;
            }

            $point = $this->referencePoints[$name];
            $signalDiff = $signal - $point['wifi_signal'];
            $distanceRatio = pow(10, $signalDiff / (10 * $this->pathLossExponent));

            $baseDistance = 15; // meters
            $estimatedDistance = max(1, $baseDistance * $distanceRatio);

            $circles[] = [
                'lat' => $point['lat'],
                'lon' => $point['lon'],
                'radius' => $estimatedDistance,
                'name' => $name,
            ];
        }

        if (count($circles) < 3) {
            return null;
        }

        return $this->trilaterate(
            $circles[0]['lat'], $circles[0]['lon'], $circles[0]['radius'],
            $circles[1]['lat'], $circles[1]['lon'], $circles[1]['radius'],
            $circles[2]['lat'], $circles[2]['lon'], $circles[2]['radius']
        );
    }

    /**
     * Trilateration algorithm
     * Finds the intersection point of 3 circles
     *
     * @return array|null ['lat' => float, 'lon' => float, 'accuracy' => float]
     */
    private function trilaterate(
        float $lat1, float $lon1, float $r1,
        float $lat2, float $lon2, float $r2,
        float $lat3, float $lon3, float $r3
    ): ?array {
        // Convert lat/lon to meters (approximate, at this latitude)
        // 1 degree lat ≈ 111,320 meters
        // 1 degree lon ≈ 111,320 * cos(lat) meters
        $latToMeters = 111320;
        $lonToMeters = 111320 * cos(deg2rad($lat1));

        // Convert to local coordinate system (meters from point 1)
        $x1 = 0;
        $y1 = 0;
        $x2 = ($lon2 - $lon1) * $lonToMeters;
        $y2 = ($lat2 - $lat1) * $latToMeters;
        $x3 = ($lon3 - $lon1) * $lonToMeters;
        $y3 = ($lat3 - $lat1) * $latToMeters;

        // Trilateration math
        $A = 2 * $x2 - 2 * $x1;
        $B = 2 * $y2 - 2 * $y1;
        $C = $r1 * $r1 - $r2 * $r2 - $x1 * $x1 + $x2 * $x2 - $y1 * $y1 + $y2 * $y2;
        $D = 2 * $x3 - 2 * $x2;
        $E = 2 * $y3 - 2 * $y2;
        $F = $r2 * $r2 - $r3 * $r3 - $x2 * $x2 + $x3 * $x3 - $y2 * $y2 + $y3 * $y3;

        $denominator = $A * $E - $B * $D;

        if (abs($denominator) < 0.0001) {
            // Points are collinear, can't trilaterate
            // Fall back to weighted average
            return $this->weightedAverage($lat1, $lon1, $r1, $lat2, $lon2, $r2, $lat3, $lon3, $r3);
        }

        $x = ($C * $E - $F * $B) / $denominator;
        $y = ($A * $F - $C * $D) / $denominator;

        // Convert back to lat/lon
        $resultLon = $lon1 + ($x / $lonToMeters);
        $resultLat = $lat1 + ($y / $latToMeters);

        // Estimate accuracy based on circle overlap
        $accuracy = ($r1 + $r2 + $r3) / 3;

        return [
            'latitude' => $resultLat,
            'longitude' => $resultLon,
            'accuracy_meters' => $accuracy,
            'method' => 'trilateration',
        ];
    }

    /**
     * Weighted average fallback when trilateration fails
     */
    private function weightedAverage(
        float $lat1, float $lon1, float $r1,
        float $lat2, float $lon2, float $r2,
        float $lat3, float $lon3, float $r3
    ): array {
        // Weight inversely by radius (closer = more weight)
        $w1 = 1 / max(1, $r1);
        $w2 = 1 / max(1, $r2);
        $w3 = 1 / max(1, $r3);
        $total = $w1 + $w2 + $w3;

        $lat = ($lat1 * $w1 + $lat2 * $w2 + $lat3 * $w3) / $total;
        $lon = ($lon1 * $w1 + $lon2 * $w2 + $lon3 * $w3) / $total;

        return [
            'latitude' => $lat,
            'longitude' => $lon,
            'accuracy_meters' => ($r1 + $r2 + $r3) / 3,
            'method' => 'weighted_average',
        ];
    }

    /**
     * Convert triangulated GPS to floor plan position
     */
    public function toFloorPlanPosition(array $triangulatedPosition): ?array
    {
        $floorPlanService = new FloorPlanHeatMapService();

        return $floorPlanService->gpsToFloorPlanPosition(
            $triangulatedPosition['latitude'],
            $triangulatedPosition['longitude']
        );
    }

    /**
     * Get calibration status
     */
    public function getCalibrationStatus(): array
    {
        $count = count($this->referencePoints);

        return [
            'is_calibrated' => $count >= 3,
            'reference_point_count' => $count,
            'reference_points' => $this->referencePoints,
            'needs' => max(0, 3 - $count),
            'message' => $count >= 3
                ? 'System calibrated with ' . $count . ' reference points'
                : 'Need ' . (3 - $count) . ' more reference point(s) for triangulation',
        ];
    }
}
