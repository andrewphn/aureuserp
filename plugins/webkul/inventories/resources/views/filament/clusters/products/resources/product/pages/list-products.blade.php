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

            {{-- Legend --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Map Legend</h4>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-full bg-red-500"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Product location</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-full bg-orange-500 opacity-50"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">GPS coverage area</span>
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

                            {{-- Product Markers --}}
                            @foreach($productLocations as $index => $product)
                                @if(isset($product['floor_position']) && $product['floor_position'])
                                    <div
                                        class="absolute cursor-pointer transform -translate-x-1/2 -translate-y-1/2 transition-all hover:scale-125"
                                        style="left: {{ ($product['floor_position']['x'] / 1702) * 100 }}%; top: {{ ($product['floor_position']['y'] / 3085) * 100 }}%;"
                                        x-on:click="selectProduct(@js($product), $event)"
                                    >
                                        <div class="relative">
                                            <div class="absolute inset-0 w-10 h-10 bg-red-500 rounded-full opacity-30 animate-ping" style="margin: -8px;"></div>
                                            <div class="absolute inset-0 w-8 h-8 bg-orange-500 rounded-full opacity-40" style="margin: -4px;"></div>
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
