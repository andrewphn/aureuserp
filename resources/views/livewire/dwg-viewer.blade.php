<div class="dwg-viewer" x-data="{ showSettings: false }">
    {{-- File Upload Section --}}
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Upload DWG/DXF File
        </label>
        <div class="flex items-center justify-center w-full">
            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                    <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                    </svg>
                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-semibold">Click to upload</span> or drag and drop
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">DWG or DXF files (MAX. 50MB)</p>
                </div>
                <input type="file" wire:model="file" class="hidden" accept=".dwg,.dxf" />
            </label>
        </div>
        
        @error('file')
            <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
        @enderror
    </div>

    {{-- Loading State --}}
    @if($isLoading)
        <div class="flex items-center justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-3 text-gray-600 dark:text-gray-400">Parsing file...</span>
        </div>
    @endif

    {{-- Error Display --}}
    @if($error)
        <div class="rounded-md bg-red-50 dark:bg-red-900/20 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Error parsing file</h3>
                    <p class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $error }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Results Section --}}
    @if($parsedData && !$isLoading)
        <div class="space-y-6">
            {{-- File Info & Stats --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $parsedData['filename'] ?? 'Parsed File' }}
                    </h3>
                    <span class="px-3 py-1 text-sm font-medium rounded-full {{ $parsedData['format'] === 'DWG' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                        {{ $parsedData['format'] ?? 'Unknown' }}
                    </span>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Entities:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ number_format($parsedData['stats']['entityCount'] ?? 0) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Layers:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ number_format($parsedData['stats']['layerCount'] ?? 0) }}</span>
                    </div>
                    @if(isset($parsedData['version']))
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Version:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $parsedData['version'] }}</span>
                        </div>
                    @endif
                    @if(isset($parsedData['filesize']))
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Size:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ number_format($parsedData['filesize'] / 1024, 1) }} KB</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- View Controls --}}
            <div class="flex flex-wrap items-center gap-4">
                {{-- View Mode Tabs --}}
                <div class="inline-flex rounded-lg shadow-sm" role="group">
                    <button wire:click="setViewMode('svg')" type="button" 
                            class="px-4 py-2 text-sm font-medium rounded-l-lg border {{ $viewMode === 'svg' ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600' }}">
                        Preview
                    </button>
                    <button wire:click="setViewMode('data')" type="button" 
                            class="px-4 py-2 text-sm font-medium border-t border-b {{ $viewMode === 'data' ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600' }}">
                        Data
                    </button>
                    <button wire:click="setViewMode('geojson')" type="button" 
                            class="px-4 py-2 text-sm font-medium rounded-r-lg border {{ $viewMode === 'geojson' ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600' }}">
                        GeoJSON
                    </button>
                </div>

                {{-- Settings Toggle --}}
                <button @click="showSettings = !showSettings" type="button"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Settings
                </button>

                {{-- Download Buttons --}}
                @if($svgContent)
                    <button wire:click="downloadSvg" type="button"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download SVG
                    </button>
                @endif
                
                <button wire:click="downloadGeoJson" type="button"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Download GeoJSON
                </button>
            </div>

            {{-- Settings Panel --}}
            <div x-show="showSettings" x-collapse class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Width</label>
                        <input type="number" wire:model.live.debounce.500ms="svgWidth" min="100" max="4000"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Height</label>
                        <input type="number" wire:model.live.debounce.500ms="svgHeight" min="100" max="4000"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Stroke Color</label>
                        <input type="color" wire:model.live="strokeColor"
                               class="w-full h-10 rounded-md border-gray-300 shadow-sm cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Background</label>
                        <input type="color" wire:model.live="backgroundColor"
                               class="w-full h-10 rounded-md border-gray-300 shadow-sm cursor-pointer">
                    </div>
                </div>
            </div>

            {{-- Layer Control Panel --}}
            @if(!empty($layerStats))
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-medium text-gray-900 dark:text-white">Layers</h4>
                        <div class="space-x-2">
                            <button wire:click="selectAllLayers" type="button" class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400">
                                Select All
                            </button>
                            <button wire:click="deselectAllLayers" type="button" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">
                                Deselect All
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($layerStats as $layerName => $stats)
                            <button wire:click="toggleLayer('{{ $layerName }}')" type="button"
                                    class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium transition-colors
                                           {{ in_array($layerName, $selectedLayers) ? 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                                {{ $layerName }}
                                <span class="ml-2 text-xs opacity-75">({{ $stats['entityCount'] }})</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Main Content Area --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                @if($viewMode === 'svg' && $svgContent)
                    <div class="p-4 overflow-auto" style="max-height: 70vh;">
                        <div class="inline-block border border-gray-200 dark:border-gray-700 rounded">
                            {!! $svgContent !!}
                        </div>
                    </div>
                @elseif($viewMode === 'data')
                    <div class="p-4 overflow-auto" style="max-height: 70vh;">
                        <pre class="text-xs text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ json_encode($parsedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @elseif($viewMode === 'geojson')
                    <div class="p-4 overflow-auto" style="max-height: 70vh;">
                        @php
                            $dwgService = app(\App\Services\DwgService::class);
                            $geoJson = $dwgService->toGeoJson($parsedData);
                        @endphp
                        <pre class="text-xs text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ json_encode($geoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
