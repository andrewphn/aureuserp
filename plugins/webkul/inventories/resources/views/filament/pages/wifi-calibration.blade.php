<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Calibration Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-wifi class="w-6 h-6 text-primary-500" />
                        WiFi Triangulation System
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Calibrate indoor positioning using outdoor reference points with known GPS
                    </p>
                </div>
                <div class="text-right">
                    @if($calibrationStatus['is_calibrated'])
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            <x-heroicon-o-check-circle class="w-4 h-4 mr-1" />
                            Calibrated
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4 mr-1" />
                            Need {{ $calibrationStatus['needs'] }} more point(s)
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- How It Works --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6">
            <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-3">How WiFi Triangulation Works</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-200 dark:bg-blue-800 rounded-full flex items-center justify-center text-blue-800 dark:text-blue-200 font-bold text-sm">1</div>
                    <div>
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Calibrate Outside</p>
                        <p class="text-xs text-blue-600 dark:text-blue-300">Stand at 3 outdoor spots with good GPS. Record GPS coordinates + WiFi signal strength.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-200 dark:bg-blue-800 rounded-full flex items-center justify-center text-blue-800 dark:text-blue-200 font-bold text-sm">2</div>
                    <div>
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Take Photo Inside</p>
                        <p class="text-xs text-blue-600 dark:text-blue-300">When taking a product photo, scan WiFi and record the signal strength.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-200 dark:bg-blue-800 rounded-full flex items-center justify-center text-blue-800 dark:text-blue-200 font-bold text-sm">3</div>
                    <div>
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Auto-Position</p>
                        <p class="text-xs text-blue-600 dark:text-blue-300">System triangulates position based on signal strength differences from reference points.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Add Reference Point Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Add Calibration Point</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Stand at an outdoor location with good GPS signal. Use your phone to get GPS coordinates and WiFi signal strength.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location Name</label>
                    <input
                        type="text"
                        wire:model="newPoint.name"
                        placeholder="e.g., Front Door"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">GPS Latitude</label>
                    <input
                        type="number"
                        step="0.000001"
                        wire:model="newPoint.lat"
                        placeholder="41.518326"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">GPS Longitude</label>
                    <input
                        type="number"
                        step="0.000001"
                        wire:model="newPoint.lon"
                        placeholder="-74.008118"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">WiFi Signal (dBm)</label>
                    <input
                        type="number"
                        wire:model="newPoint.wifi_signal"
                        placeholder="-65"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                    <p class="text-xs text-gray-400 mt-1">-30 (strong) to -90 (weak)</p>
                </div>
            </div>

            <div class="mt-4">
                <button
                    wire:click="addReferencePoint"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition"
                >
                    <x-heroicon-o-plus class="w-4 h-4 mr-2" />
                    Add Reference Point
                </button>
            </div>
        </div>

        {{-- Reference Points List --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Reference Points ({{ count($referencePoints) }}/3 minimum)
                </h4>
            </div>

            @if(count($referencePoints) > 0)
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($referencePoints as $name => $point)
                        <div class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center">
                                    <x-heroicon-o-map-pin class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $point['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        GPS: {{ number_format($point['lat'], 6) }}, {{ number_format($point['lon'], 6) }}
                                        &bull;
                                        WiFi: {{ $point['wifi_signal'] }} dBm
                                    </p>
                                </div>
                            </div>
                            <button
                                wire:click="removeReferencePoint('{{ $name }}')"
                                class="text-red-500 hover:text-red-700 p-2"
                            >
                                <x-heroicon-o-trash class="w-5 h-5" />
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center">
                    <x-heroicon-o-wifi class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">No reference points yet</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Add at least 3 points for triangulation</p>
                </div>
            @endif
        </div>

        {{-- Test Triangulation --}}
        @if(count($referencePoints) >= 3)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Test Triangulation</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Enter a WiFi signal reading to calculate an estimated position.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current WiFi Signal (dBm)</label>
                        <div class="flex gap-2">
                            <input
                                type="number"
                                wire:model="testReading.signal"
                                placeholder="-55"
                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                            <button
                                wire:click="testTriangulation"
                                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition"
                            >
                                <x-heroicon-o-map-pin class="w-4 h-4 mr-2" />
                                Calculate
                            </button>
                        </div>
                    </div>

                    @if($triangulatedPosition)
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-green-800 dark:text-green-200 mb-2">Calculated Position</h5>
                            <p class="text-sm text-green-700 dark:text-green-300">
                                Latitude: {{ number_format($triangulatedPosition['latitude'], 6) }}<br>
                                Longitude: {{ number_format($triangulatedPosition['longitude'], 6) }}
                            </p>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                                Method: {{ $triangulatedPosition['method'] }}
                                &bull;
                                Accuracy: ~{{ number_format($triangulatedPosition['accuracy_meters'], 1) }}m
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Tips --}}
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Tips for Accurate Calibration</h4>
            <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-2">
                <li class="flex items-start gap-2">
                    <x-heroicon-o-check class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" />
                    <span>Choose outdoor spots around the building perimeter where GPS is accurate</span>
                </li>
                <li class="flex items-start gap-2">
                    <x-heroicon-o-check class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" />
                    <span>Spread reference points around different sides of the building (e.g., front, back, side)</span>
                </li>
                <li class="flex items-start gap-2">
                    <x-heroicon-o-check class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" />
                    <span>Use your phone's WiFi analyzer app to get exact signal strength in dBm</span>
                </li>
                <li class="flex items-start gap-2">
                    <x-heroicon-o-check class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" />
                    <span>Wait for stable GPS reading before recording coordinates</span>
                </li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
