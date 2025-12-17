<x-filament-panels::page>
    @if($viewMode === 'map')
        {{-- Map View --}}
        <div class="space-y-6">
            {{-- Building Info Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $buildingInfo['address'] ?? 'Factory Floor' }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ number_format($buildingInfo['area_sqft'] ?? 4252, 0) }} sq ft
                            &bull;
                            {{ number_format($buildingInfo['width_ft'] ?? 87.66, 1) }}' x {{ number_format($buildingInfo['length_ft'] ?? 48.03, 1) }}'
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                {{ $totalProducts ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Products with GPS</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Beacon Setup Card --}}
            @php
                $beaconService = app(\Webkul\Inventory\Services\BeaconPositioningService::class);
                $beaconStatus = $beaconService->getSetupStatus();
            @endphp
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4"
                x-data="{
                    showSetup: false,
                    showScanner: false,
                    beacons: @js($beaconStatus['beacons']),
                    newBeacon: { major: '', minor: '', name: '', floor_x: '', floor_y: '' },

                    // Web Bluetooth Scanner
                    isScanning: false,
                    isSupported: 'bluetooth' in navigator,
                    scanError: null,
                    detectedBeacons: [],

                    getUnsupportedMessage() {
                        const ua = navigator.userAgent;
                        if (/iPhone|iPad|iPod/.test(ua)) {
                            return 'iOS Safari does not support Web Bluetooth. Use Chrome on Android or Desktop.';
                        }
                        if (/Firefox/.test(ua)) {
                            return 'Firefox does not support Web Bluetooth. Please use Chrome.';
                        }
                        return 'Web Bluetooth not supported. Please use Chrome.';
                    },

                    async scanForBeacons() {
                        if (!this.isSupported) {
                            this.scanError = this.getUnsupportedMessage();
                            return;
                        }

                        this.isScanning = true;
                        this.scanError = null;
                        this.detectedBeacons = [];

                        try {
                            // Request BLE device scan
                            const device = await navigator.bluetooth.requestDevice({
                                acceptAllDevices: true,
                                optionalManufacturerData: [0x004C] // Apple iBeacon
                            });

                            console.log('Device found:', device.name || device.id);

                            // Check if device supports advertisement watching
                            if (device.watchAdvertisements) {
                                device.addEventListener('advertisementreceived', (event) => {
                                    this.processAdvertisement(event);
                                });
                                await device.watchAdvertisements();

                                // Stop after 10 seconds
                                setTimeout(() => {
                                    this.isScanning = false;
                                }, 10000);
                            } else {
                                // Basic detection without advertisement data
                                this.detectedBeacons.push({
                                    name: device.name || 'BLE Device',
                                    id: device.id,
                                    rssi: 'N/A',
                                    note: 'Connect to device to read beacon data'
                                });
                                this.isScanning = false;
                            }

                        } catch (err) {
                            console.error('Scan error:', err);
                            if (err.name === 'NotFoundError') {
                                this.scanError = 'No Bluetooth devices found. Make sure beacons are powered on.';
                            } else if (err.name === 'NotAllowedError') {
                                this.scanError = 'Bluetooth permission denied.';
                            } else {
                                this.scanError = err.message;
                            }
                            this.isScanning = false;
                        }
                    },

                    processAdvertisement(event) {
                        let beacon = {
                            name: event.device.name || 'Unknown',
                            rssi: event.rssi,
                            txPower: event.txPower
                        };

                        // Parse iBeacon data if available
                        if (event.manufacturerData) {
                            const appleData = event.manufacturerData.get(0x004C);
                            if (appleData && appleData.byteLength >= 23) {
                                const view = new DataView(appleData.buffer);
                                beacon.major = view.getUint16(18, false);
                                beacon.minor = view.getUint16(20, false);
                                beacon.isIBeacon = true;
                            }
                        }

                        // Add or update beacon
                        const existing = this.detectedBeacons.find(b =>
                            b.major === beacon.major && b.minor === beacon.minor
                        );
                        if (!existing) {
                            this.detectedBeacons.push(beacon);
                        }
                    },

                    useDetectedBeacon(beacon) {
                        if (beacon.major !== undefined) {
                            document.querySelector('[name=major]').value = beacon.major;
                            document.querySelector('[name=minor]').value = beacon.minor;
                        }
                        this.showScanner = false;
                    }
                }"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <x-heroicon-o-signal class="h-6 w-6 text-blue-500 flex-shrink-0" />
                        <div>
                            <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                iBeacon Indoor Positioning
                            </h4>
                            <p class="text-sm text-blue-600 dark:text-blue-300 mt-1">
                                {{ $beaconStatus['message'] }}
                            </p>
                            @if($beaconStatus['beacon_count'] > 0)
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach($beaconStatus['beacons'] as $key => $beacon)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300">
                                            <x-heroicon-o-map-pin class="w-3 h-3 mr-1" />
                                            {{ $beacon['name'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                    <button
                        @click="showSetup = !showSetup"
                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline flex-shrink-0"
                    >
                        <span x-text="showSetup ? 'Hide Setup' : 'Setup Beacons'"></span>
                    </button>
                </div>

                {{-- Beacon Setup Form --}}
                <div x-show="showSetup" x-transition class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-700">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        {{-- Web Bluetooth Scanner --}}
                        <div class="mb-4 p-3 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg border border-purple-200 dark:border-purple-700">
                            <div class="flex items-center justify-between mb-2">
                                <h6 class="text-sm font-medium text-purple-800 dark:text-purple-200 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M14.24 12.01l2.32 2.32c.28-.72.44-1.51.44-2.33 0-.82-.16-1.59-.43-2.31l-2.33 2.32zm5.29-5.3l-1.26 1.26c.63 1.21.98 2.57.98 4.02s-.36 2.82-.98 4.02l1.26 1.26c.86-1.53 1.36-3.28 1.36-5.28s-.5-3.75-1.36-5.28zM12 5.5L7.5 10h-4v4h4l4.5 4.5v-13zm0 13.93V17.5l-2.5-2.5H6v-6h3.5L12 6.5v2.93z"/></svg>
                                    Scan with Web Bluetooth
                                </h6>
                                <span x-show="!isSupported" class="text-xs text-red-600 dark:text-red-400">Not supported in this browser</span>
                            </div>

                            <p class="text-xs text-purple-600 dark:text-purple-300 mb-3">
                                Works on Chrome (Android/Desktop). Click scan, then select your beacon from the popup.
                            </p>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="scanForBeacons()"
                                    :disabled="isScanning || !isSupported"
                                    class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                >
                                    <svg x-show="isScanning" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="isScanning ? 'Scanning...' : 'Scan for Beacons'"></span>
                                </button>

                                <span x-show="detectedBeacons.length > 0" class="text-sm text-green-600 dark:text-green-400">
                                    Found <span x-text="detectedBeacons.length"></span> device(s)
                                </span>
                            </div>

                            {{-- Error message --}}
                            <p x-show="scanError" x-text="scanError" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>

                            {{-- Detected beacons --}}
                            <div x-show="detectedBeacons.length > 0" class="mt-3 space-y-2">
                                <template x-for="beacon in detectedBeacons" :key="beacon.id || beacon.minor">
                                    <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-700 rounded border">
                                        <div>
                                            <span class="text-sm font-medium" x-text="beacon.name"></span>
                                            <span x-show="beacon.major !== undefined" class="text-xs text-gray-500 ml-2">
                                                Major: <span x-text="beacon.major"></span>, Minor: <span x-text="beacon.minor"></span>
                                            </span>
                                            <span x-show="beacon.rssi" class="text-xs text-gray-400 ml-2">
                                                RSSI: <span x-text="beacon.rssi"></span> dBm
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="useDetectedBeacon(beacon)"
                                            x-show="beacon.major !== undefined"
                                            class="text-xs px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
                                        >
                                            Use This
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <h5 class="font-medium text-gray-900 dark:text-white mb-3">Add Beacon Manually</h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                            Or use a beacon scanner app (nRF Connect, Locate Beacon) to find your beacon's Major/Minor values.
                        </p>
                        <form
                            action="{{ route('filament.admin.beacon.add') }}"
                            method="POST"
                            class="grid grid-cols-2 md:grid-cols-6 gap-3"
                        >
                            @csrf
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Major</label>
                                <input
                                    type="number"
                                    name="major"
                                    placeholder="10"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    required
                                    min="0"
                                    max="65535"
                                >
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Minor</label>
                                <input
                                    type="number"
                                    name="minor"
                                    placeholder="1"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    required
                                    min="0"
                                    max="65535"
                                >
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Name</label>
                                <input
                                    type="text"
                                    name="name"
                                    placeholder="Front Door"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    required
                                >
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Floor X</label>
                                <input
                                    type="number"
                                    name="floor_x"
                                    placeholder="50"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    required
                                >
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Floor Y</label>
                                <input
                                    type="number"
                                    name="floor_y"
                                    placeholder="800"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    required
                                >
                            </div>
                            <div class="flex items-end">
                                <button
                                    type="submit"
                                    class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700"
                                >
                                    Add
                                </button>
                            </div>
                        </form>

                        {{-- Suggested Positions --}}
                        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Suggested Beacon Positions (click to use):</p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                                <button type="button" onclick="document.querySelector('[name=name]').value='Front Left (SW)';document.querySelector('[name=floor_x]').value='50';document.querySelector('[name=floor_y]').value='800';" class="p-2 bg-white dark:bg-gray-800 rounded border hover:border-blue-400 text-left">
                                    <span class="font-medium">Front Left (SW)</span><br>
                                    <span class="text-gray-500">X:50, Y:800</span>
                                </button>
                                <button type="button" onclick="document.querySelector('[name=name]').value='Front Right (SE)';document.querySelector('[name=floor_x]').value='250';document.querySelector('[name=floor_y]').value='800';" class="p-2 bg-white dark:bg-gray-800 rounded border hover:border-blue-400 text-left">
                                    <span class="font-medium">Front Right (SE)</span><br>
                                    <span class="text-gray-500">X:250, Y:800</span>
                                </button>
                                <button type="button" onclick="document.querySelector('[name=name]').value='Back Left (NW)';document.querySelector('[name=floor_x]').value='50';document.querySelector('[name=floor_y]').value='200';" class="p-2 bg-white dark:bg-gray-800 rounded border hover:border-blue-400 text-left">
                                    <span class="font-medium">Back Left (NW)</span><br>
                                    <span class="text-gray-500">X:50, Y:200</span>
                                </button>
                                <button type="button" onclick="document.querySelector('[name=name]').value='Back Right (NE)';document.querySelector('[name=floor_x]').value='250';document.querySelector('[name=floor_y]').value='200';" class="p-2 bg-white dark:bg-gray-800 rounded border hover:border-blue-400 text-left">
                                    <span class="font-medium">Back Right (NE)</span><br>
                                    <span class="text-gray-500">X:250, Y:200</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Legend --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Map Legend</h4>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-full bg-red-500"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Product location (GPS)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-full bg-blue-500"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Beacon position</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-full bg-orange-500 opacity-50"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Coverage area</span>
                    </div>
                </div>
            </div>

            {{-- Floor Plan with Products --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Product Location Map</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Click on markers to view product details. Products with GPS-tagged photos appear automatically.
                    </p>
                </div>

                <div
                    class="relative overflow-auto"
                    style="max-height: 70vh;"
                    x-data="{
                        selectedProduct: null,
                        showTooltip: false,
                        tooltipX: 0,
                        tooltipY: 0,
                        products: @js($productLocations),
                        beacons: @js($beaconStatus['beacons']),
                        selectProduct(product, event) {
                            this.selectedProduct = product;
                            this.showTooltip = true;
                            this.tooltipX = event.clientX;
                            this.tooltipY = event.clientY;
                        },
                        closeTooltip() {
                            this.showTooltip = false;
                            this.selectedProduct = null;
                        }
                    }"
                >
                    {{-- Floor Plan Image --}}
                    @if($floorPlanUrl)
                        <div class="relative inline-block">
                            <img
                                src="{{ $floorPlanUrl }}"
                                alt="Factory Floor Plan"
                                class="max-w-none"
                                style="width: auto; height: auto; max-height: 65vh;"
                            >

                            {{-- Beacon Markers --}}
                            @foreach($beaconStatus['beacons'] as $key => $beacon)
                                <div
                                    class="absolute transform -translate-x-1/2 -translate-y-1/2"
                                    style="left: {{ ($beacon['floor_x'] / 1702) * 100 }}%; top: {{ ($beacon['floor_y'] / 3085) * 100 }}%;"
                                    title="{{ $beacon['name'] }}"
                                >
                                    <div class="relative">
                                        {{-- Beacon range indicator --}}
                                        <div class="absolute w-16 h-16 bg-blue-400 rounded-full opacity-10 pointer-events-none" style="margin: -24px;"></div>
                                        {{-- Beacon icon --}}
                                        <div class="w-5 h-5 bg-blue-600 rounded-sm border-2 border-white shadow-lg flex items-center justify-center rotate-45">
                                            <x-heroicon-o-signal class="w-3 h-3 text-white -rotate-45" />
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Product Markers --}}
                            @foreach($productLocations as $index => $product)
                                @if(isset($product['floor_position']) && $product['floor_position'])
                                    <div
                                        class="absolute cursor-pointer transform -translate-x-1/2 -translate-y-1/2 transition-all hover:scale-125 z-10"
                                        style="left: {{ ($product['floor_position']['x'] / 1702) * 100 }}%; top: {{ ($product['floor_position']['y'] / 3085) * 100 }}%;"
                                        x-on:click="selectProduct(@js($product), $event)"
                                    >
                                        <div class="relative">
                                            {{-- Animation overlays - pointer-events-none so clicks pass through --}}
                                            <div class="absolute inset-0 w-10 h-10 bg-red-500 rounded-full opacity-30 animate-ping pointer-events-none" style="margin: -8px;"></div>
                                            <div class="absolute inset-0 w-8 h-8 bg-orange-500 rounded-full opacity-40 pointer-events-none" style="margin: -4px;"></div>
                                            {{-- Clickable marker --}}
                                            <div class="w-4 h-4 bg-red-600 rounded-full border-2 border-white shadow-lg flex items-center justify-center">
                                                <span class="text-[8px] font-bold text-white">{{ $index + 1 }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="p-12 text-center">
                            <x-heroicon-o-map class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No floor plan loaded</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Add a floor plan to the FloorPlan folder to get started.
                            </p>
                        </div>
                    @endif

                    {{-- Tooltip --}}
                    <div
                        x-show="showTooltip && selectedProduct"
                        x-transition
                        x-on:click.away="closeTooltip()"
                        class="fixed z-50 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-4 max-w-xs"
                        :style="`left: ${tooltipX + 10}px; top: ${tooltipY + 10}px;`"
                    >
                        <button
                            x-on:click="closeTooltip()"
                            class="absolute top-2 right-2 text-gray-400 hover:text-gray-600"
                        >
                            <x-heroicon-o-x-mark class="w-4 h-4" />
                        </button>

                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white" x-text="selectedProduct?.product_name"></h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Product ID: <span x-text="selectedProduct?.product_id"></span>
                            </p>
                            <template x-if="selectedProduct?.gps">
                                <div class="text-xs text-gray-400 mt-2">
                                    <p>Lat: <span x-text="selectedProduct?.gps?.latitude?.toFixed(6)"></span></p>
                                    <p>Lon: <span x-text="selectedProduct?.gps?.longitude?.toFixed(6)"></span></p>
                                </div>
                            </template>
                            <template x-if="selectedProduct?.product_id">
                                <a
                                    :href="`{{ url('/admin/inventory/products/products') }}/${selectedProduct?.product_id}`"
                                    class="mt-3 inline-flex items-center text-sm text-primary-600 hover:text-primary-500"
                                >
                                    View Product
                                    <x-heroicon-o-chevron-right class="ml-1 w-4 h-4" />
                                </a>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Product List or Empty State --}}
            @if(count($productLocations) > 0)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Products with GPS Data ({{ count($productLocations) }})</h4>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-48 overflow-auto">
                        @foreach($productLocations as $index => $product)
                            <a
                                href="{{ url('/admin/inventory/products/products/' . $product['product_id']) }}"
                                class="px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 block"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                                        <span class="text-xs font-bold text-red-600 dark:text-red-400">{{ $index + 1 }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $product['product_name'] ?? 'Unknown Product' }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            @if(isset($product['gps']))
                                                GPS: {{ number_format($product['gps']['latitude'] ?? 0, 4) }}, {{ number_format($product['gps']['longitude'] ?? 0, 4) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                @if(isset($product['floor_position']) && $product['floor_position'])
                                    <span class="text-xs text-green-600 dark:text-green-400 font-medium">Mapped</span>
                                @else
                                    <span class="text-xs text-yellow-600 dark:text-yellow-400">Outside</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-exclamation-triangle class="h-8 w-8 text-yellow-400" />
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">No products with GPS data</h3>
                            <p class="mt-1 text-sm text-yellow-600 dark:text-yellow-300">
                                Upload product images with GPS metadata enabled to see them on the map.
                            </p>
                            <p class="mt-1 text-xs text-yellow-500 dark:text-yellow-400">
                                Tip: Take photos with location services enabled on your phone.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @else
        {{-- Table View - Use parent's table rendering --}}
        {{ $this->table }}
    @endif
</x-filament-panels::page>
