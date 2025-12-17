<?php

namespace Webkul\Inventory\Services;

/**
 * Beacon Positioning Service
 *
 * Uses iBeacon BLE signals to triangulate indoor positions.
 * Works with nRF51822 and similar BLE beacon modules.
 *
 * iBeacon Format:
 * - UUID: Unique identifier (usually same for all your beacons)
 * - Major: Building/floor identifier (16-bit)
 * - Minor: Specific beacon identifier (16-bit)
 * - RSSI: Signal strength in dBm (-30 strong to -100 weak)
 */
class BeaconPositioningService
{
    /**
     * Registered beacons with known positions
     * Key: "major:minor" identifier
     */
    private array $beacons = [];

    /**
     * Expected UUID for your beacons (set this to match your beacons)
     */
    private ?string $expectedUuid = null;

    /**
     * Path loss exponent for signal-to-distance conversion
     * Typical: 2.0 (free space) to 4.0 (indoor with obstacles)
     */
    private float $pathLossExponent = 2.5;

    /**
     * Measured power at 1 meter (calibration value)
     * Most iBeacons broadcast this, typically -59 to -65 dBm
     */
    private float $measuredPower = -59.0;

    public function __construct()
    {
        $this->loadConfiguration();
    }

    /**
     * Load beacon configuration from file
     */
    private function loadConfiguration(): void
    {
        $configPath = base_path('FloorPlan/beacon-config.json');

        if (file_exists($configPath)) {
            $data = json_decode(file_get_contents($configPath), true);
            $this->beacons = $data['beacons'] ?? [];
            $this->expectedUuid = $data['uuid'] ?? null;
            $this->pathLossExponent = $data['path_loss_exponent'] ?? 2.5;
            $this->measuredPower = $data['measured_power'] ?? -59.0;
        }
    }

    /**
     * Save beacon configuration
     */
    public function saveConfiguration(): void
    {
        $configPath = base_path('FloorPlan/beacon-config.json');

        $data = [
            'uuid' => $this->expectedUuid,
            'beacons' => $this->beacons,
            'path_loss_exponent' => $this->pathLossExponent,
            'measured_power' => $this->measuredPower,
            'updated_at' => now()->toIso8601String(),
        ];

        // Ensure directory exists
        $dir = dirname($configPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($configPath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Set the expected UUID for your beacons
     */
    public function setUuid(string $uuid): void
    {
        $this->expectedUuid = strtolower($uuid);
        $this->saveConfiguration();
    }

    /**
     * Register a beacon at a known position
     *
     * @param int $major Major identifier (0-65535)
     * @param int $minor Minor identifier (0-65535)
     * @param string $name Friendly name (e.g., "Front Door")
     * @param float $floorX X position on floor plan (pixels)
     * @param float $floorY Y position on floor plan (pixels)
     * @param float|null $lat Optional GPS latitude for reference
     * @param float|null $lon Optional GPS longitude for reference
     */
    public function registerBeacon(
        int $major,
        int $minor,
        string $name,
        float $floorX,
        float $floorY,
        ?float $lat = null,
        ?float $lon = null
    ): void {
        $key = "{$major}:{$minor}";

        $this->beacons[$key] = [
            'major' => $major,
            'minor' => $minor,
            'name' => $name,
            'floor_x' => $floorX,
            'floor_y' => $floorY,
            'lat' => $lat,
            'lon' => $lon,
            'created_at' => now()->toIso8601String(),
        ];

        $this->saveConfiguration();
    }

    /**
     * Remove a beacon
     */
    public function removeBeacon(int $major, int $minor): void
    {
        $key = "{$major}:{$minor}";
        unset($this->beacons[$key]);
        $this->saveConfiguration();
    }

    /**
     * Get all registered beacons
     */
    public function getBeacons(): array
    {
        return $this->beacons;
    }

    /**
     * Get beacon by major:minor
     */
    public function getBeacon(int $major, int $minor): ?array
    {
        $key = "{$major}:{$minor}";
        return $this->beacons[$key] ?? null;
    }

    /**
     * Convert RSSI signal strength to estimated distance
     *
     * Formula: distance = 10^((measuredPower - rssi) / (10 * n))
     *
     * @param float $rssi Signal strength in dBm
     * @param float|null $txPower Beacon's transmitted power (if available)
     * @return float Estimated distance in meters
     */
    public function rssiToDistance(float $rssi, ?float $txPower = null): float
    {
        $measuredPower = $txPower ?? $this->measuredPower;

        if ($rssi >= 0) {
            return 0.1; // Invalid reading
        }

        // RSSI-based distance estimation
        $ratio = $rssi / $measuredPower;

        if ($ratio < 1.0) {
            return pow($ratio, 10);
        }

        // More accurate formula for longer distances
        $distance = pow(10, ($measuredPower - $rssi) / (10 * $this->pathLossExponent));

        return max(0.1, min(100, $distance)); // Clamp between 0.1m and 100m
    }

    /**
     * Calculate position from multiple beacon readings
     *
     * @param array $readings Array of ['major' => int, 'minor' => int, 'rssi' => float, 'txPower' => float|null]
     * @return array|null ['x' => float, 'y' => float, 'accuracy' => float, 'method' => string]
     */
    public function calculatePosition(array $readings): ?array
    {
        // Filter to only known beacons
        $validReadings = [];

        foreach ($readings as $reading) {
            $key = "{$reading['major']}:{$reading['minor']}";

            if (isset($this->beacons[$key])) {
                $beacon = $this->beacons[$key];
                $distance = $this->rssiToDistance(
                    $reading['rssi'],
                    $reading['txPower'] ?? null
                );

                $validReadings[] = [
                    'beacon' => $beacon,
                    'rssi' => $reading['rssi'],
                    'distance' => $distance,
                ];
            }
        }

        $count = count($validReadings);

        if ($count === 0) {
            return null;
        }

        if ($count === 1) {
            // Single beacon - return its position with large uncertainty
            return [
                'x' => $validReadings[0]['beacon']['floor_x'],
                'y' => $validReadings[0]['beacon']['floor_y'],
                'accuracy' => $validReadings[0]['distance'] * 2,
                'method' => 'nearest_beacon',
                'beacon_count' => 1,
            ];
        }

        if ($count === 2) {
            // Two beacons - weighted average
            return $this->calculateFromTwoBeacons($validReadings);
        }

        // Three or more beacons - trilateration
        return $this->trilaterate($validReadings);
    }

    /**
     * Calculate position from two beacons using weighted average
     */
    private function calculateFromTwoBeacons(array $readings): array
    {
        $b1 = $readings[0];
        $b2 = $readings[1];

        // Weight inversely by distance (closer beacon has more influence)
        $w1 = 1 / max(0.1, $b1['distance']);
        $w2 = 1 / max(0.1, $b2['distance']);
        $total = $w1 + $w2;

        $x = ($b1['beacon']['floor_x'] * $w1 + $b2['beacon']['floor_x'] * $w2) / $total;
        $y = ($b1['beacon']['floor_y'] * $w1 + $b2['beacon']['floor_y'] * $w2) / $total;

        return [
            'x' => $x,
            'y' => $y,
            'accuracy' => ($b1['distance'] + $b2['distance']) / 2,
            'method' => 'two_beacon_weighted',
            'beacon_count' => 2,
        ];
    }

    /**
     * Trilateration from 3+ beacons
     */
    private function trilaterate(array $readings): array
    {
        // Sort by signal strength (strongest first = most reliable)
        usort($readings, fn($a, $b) => $b['rssi'] <=> $a['rssi']);

        // Use top 3-4 readings for better accuracy
        $readings = array_slice($readings, 0, 4);

        if (count($readings) >= 3) {
            // Trilateration using least squares
            $result = $this->leastSquaresTrilateration($readings);

            if ($result) {
                return $result;
            }
        }

        // Fallback to weighted centroid
        return $this->weightedCentroid($readings);
    }

    /**
     * Least squares trilateration
     */
    private function leastSquaresTrilateration(array $readings): ?array
    {
        $n = count($readings);

        if ($n < 3) {
            return null;
        }

        // Build matrices for least squares solution
        // We solve: Ax = b where x = [px, py]

        $A = [];
        $b = [];

        // Use first beacon as reference
        $ref = $readings[0];
        $x1 = $ref['beacon']['floor_x'];
        $y1 = $ref['beacon']['floor_y'];
        $r1 = $ref['distance'];

        for ($i = 1; $i < $n; $i++) {
            $xi = $readings[$i]['beacon']['floor_x'];
            $yi = $readings[$i]['beacon']['floor_y'];
            $ri = $readings[$i]['distance'];

            $A[] = [2 * ($xi - $x1), 2 * ($yi - $y1)];
            $b[] = ($ri * $ri - $r1 * $r1) - ($xi * $xi - $x1 * $x1) - ($yi * $yi - $y1 * $y1);
        }

        // Solve using normal equations: (A^T * A) * x = A^T * b
        $result = $this->solveLinearSystem($A, $b);

        if (!$result) {
            return null;
        }

        // Calculate accuracy as average distance error
        $totalError = 0;
        foreach ($readings as $reading) {
            $dx = $result[0] - $reading['beacon']['floor_x'];
            $dy = $result[1] - $reading['beacon']['floor_y'];
            $calculatedDist = sqrt($dx * $dx + $dy * $dy);
            $totalError += abs($calculatedDist - $reading['distance']);
        }

        return [
            'x' => $result[0],
            'y' => $result[1],
            'accuracy' => $totalError / $n,
            'method' => 'trilateration',
            'beacon_count' => $n,
        ];
    }

    /**
     * Solve 2-variable linear system using least squares
     */
    private function solveLinearSystem(array $A, array $b): ?array
    {
        $n = count($A);

        if ($n < 2) {
            return null;
        }

        // Compute A^T * A
        $ata = [[0, 0], [0, 0]];
        $atb = [0, 0];

        for ($i = 0; $i < $n; $i++) {
            $ata[0][0] += $A[$i][0] * $A[$i][0];
            $ata[0][1] += $A[$i][0] * $A[$i][1];
            $ata[1][0] += $A[$i][1] * $A[$i][0];
            $ata[1][1] += $A[$i][1] * $A[$i][1];

            $atb[0] += $A[$i][0] * $b[$i];
            $atb[1] += $A[$i][1] * $b[$i];
        }

        // Solve 2x2 system
        $det = $ata[0][0] * $ata[1][1] - $ata[0][1] * $ata[1][0];

        if (abs($det) < 0.0001) {
            return null; // Singular matrix
        }

        $x = ($ata[1][1] * $atb[0] - $ata[0][1] * $atb[1]) / $det;
        $y = ($ata[0][0] * $atb[1] - $ata[1][0] * $atb[0]) / $det;

        return [$x, $y];
    }

    /**
     * Weighted centroid fallback
     */
    private function weightedCentroid(array $readings): array
    {
        $totalWeight = 0;
        $x = 0;
        $y = 0;

        foreach ($readings as $reading) {
            // Weight by inverse square of distance (closer = much more influence)
            $weight = 1 / max(0.01, $reading['distance'] * $reading['distance']);
            $totalWeight += $weight;
            $x += $reading['beacon']['floor_x'] * $weight;
            $y += $reading['beacon']['floor_y'] * $weight;
        }

        $avgDistance = array_sum(array_column($readings, 'distance')) / count($readings);

        return [
            'x' => $x / $totalWeight,
            'y' => $y / $totalWeight,
            'accuracy' => $avgDistance,
            'method' => 'weighted_centroid',
            'beacon_count' => count($readings),
        ];
    }

    /**
     * Get configuration for JavaScript beacon scanner
     */
    public function getJsConfig(): array
    {
        return [
            'uuid' => $this->expectedUuid,
            'beacons' => array_values($this->beacons),
            'measuredPower' => $this->measuredPower,
            'pathLossExponent' => $this->pathLossExponent,
        ];
    }

    /**
     * Get setup status
     */
    public function getSetupStatus(): array
    {
        $count = count($this->beacons);

        return [
            'is_configured' => $count >= 3,
            'beacon_count' => $count,
            'has_uuid' => !empty($this->expectedUuid),
            'uuid' => $this->expectedUuid,
            'beacons' => $this->beacons,
            'recommended_count' => 4,
            'message' => match (true) {
                $count === 0 => 'No beacons configured. Add your 4 iBeacons to get started.',
                $count < 3 => "Need " . (3 - $count) . " more beacon(s) for triangulation.",
                $count === 3 => 'Minimum beacons configured. Consider adding a 4th for better accuracy.',
                default => "Excellent! {$count} beacons configured for accurate positioning.",
            },
        ];
    }
}
