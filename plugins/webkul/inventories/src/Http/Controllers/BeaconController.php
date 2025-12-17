<?php

namespace Webkul\Inventory\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Inventory\Services\BeaconPositioningService;

class BeaconController extends Controller
{
    public function __construct(
        private BeaconPositioningService $beaconService
    ) {}

    /**
     * Add a beacon
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'major' => 'required|integer|min:0|max:65535',
            'minor' => 'required|integer|min:0|max:65535',
            'name' => 'required|string|max:100',
            'floor_x' => 'required|numeric',
            'floor_y' => 'required|numeric',
            'lat' => 'nullable|numeric',
            'lon' => 'nullable|numeric',
        ]);

        $this->beaconService->registerBeacon(
            major: (int) $validated['major'],
            minor: (int) $validated['minor'],
            name: $validated['name'],
            floorX: (float) $validated['floor_x'],
            floorY: (float) $validated['floor_y'],
            lat: $validated['lat'] ?? null,
            lon: $validated['lon'] ?? null
        );

        return redirect()
            ->back()
            ->with('success', "Beacon '{$validated['name']}' added successfully!");
    }

    /**
     * Remove a beacon
     */
    public function remove(Request $request)
    {
        $validated = $request->validate([
            'major' => 'required|integer',
            'minor' => 'required|integer',
        ]);

        $this->beaconService->removeBeacon(
            major: (int) $validated['major'],
            minor: (int) $validated['minor']
        );

        return redirect()
            ->back()
            ->with('success', 'Beacon removed successfully!');
    }

    /**
     * Calculate position from beacon readings (API endpoint)
     */
    public function calculatePosition(Request $request)
    {
        $validated = $request->validate([
            'readings' => 'required|array|min:1',
            'readings.*.major' => 'required|integer',
            'readings.*.minor' => 'required|integer',
            'readings.*.rssi' => 'required|numeric|max:0',
            'readings.*.txPower' => 'nullable|numeric',
        ]);

        $position = $this->beaconService->calculatePosition($validated['readings']);

        if (!$position) {
            return response()->json([
                'success' => false,
                'error' => 'No registered beacons detected',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'position' => $position,
        ]);
    }

    /**
     * Get beacon configuration (for JS scanner)
     */
    public function config()
    {
        return response()->json($this->beaconService->getJsConfig());
    }

    /**
     * Get setup status
     */
    public function status()
    {
        return response()->json($this->beaconService->getSetupStatus());
    }
}
