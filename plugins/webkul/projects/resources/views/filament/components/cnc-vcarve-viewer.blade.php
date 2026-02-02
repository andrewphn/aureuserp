@props([
    'part' => null,
    'svgContent' => null,
    'htmlUrl' => null,
    'metadata' => [],
    'program' => null,
])

@php
    $hasSvg = $svgContent || ($part && $part->vcarve_svg_content);
    $hasVisualization = $hasSvg || ($part && ($part->vcarve_html_drive_url || $part->vcarve_html_path));

    // Process SVG if available
    $processedSvg = null;
    $layers = [];
    $processor = null;
    if ($hasSvg) {
        $processor = app(\Webkul\Project\Services\VCarve\SvgProcessor::class);
        $rawSvg = $svgContent ?? $part->vcarve_svg_content;
        $result = $processor->process($rawSvg);
        $processedSvg = $result['svg'];
        $layers = $result['layers'];
    }

    // Get metadata
    $material = $metadata['material'] ?? $part->vcarve_metadata['material'] ?? [];
    $partMeta = $part->vcarve_metadata ?? [];

    // Parse tool string
    $toolInfo = null;
    if ($processor && $part && $part->tool) {
        $toolInfo = $processor->parseToolString($part->tool);
    }

    // Get program context for sheet info
    $program = $program ?? $part?->cncProgram;
    $totalSheets = $program?->parts()->max('sheet_number') ?? 1;

    // Extract part labels from SVG (looking for text elements)
    $partLabels = [];
    if ($processedSvg) {
        preg_match_all('/<text[^>]*>([A-Z]{2,3}-\d+)<\/text>/i', $processedSvg, $matches);
        $partLabels = array_unique($matches[1] ?? []);
    }
    // Also check layer names for part patterns
    if (empty($partLabels) && !empty($layers)) {
        foreach (array_keys($layers) as $layerName) {
            if (preg_match('/^[A-Z]{2,3}-\d+$/i', $layerName)) {
                $partLabels[] = $layerName;
            }
        }
    }

    // Count hardware operations
    $hardwareOps = collect($layers)->filter(fn($l) => !empty($l['hardware']))->count();
@endphp

<div
    x-data="{
        showAdvanced: false,
        zoom: 1,
        checklist: {
            material: false,
            orientation: false,
            tool: false,
            ready: false
        },
        allChecked() {
            return this.checklist.material && this.checklist.orientation && this.checklist.tool && this.checklist.ready;
        }
    }"
    class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col h-full"
>
    @if($part && $hasVisualization)
        {{-- ============================================================ --}}
        {{-- VERIFICATION HEADER - Most Important Info                     --}}
        {{-- ============================================================ --}}

        {{-- Material Banner - LARGE and PROMINENT --}}
        <div class="flex-shrink-0 p-4 border-b-4
            @if(str_contains(strtolower($program?->material_code ?? ''), 'prefin'))
                bg-blue-500 border-blue-600 text-white
            @elseif(str_contains(strtolower($program?->material_code ?? ''), 'medex'))
                bg-amber-500 border-amber-600 text-black
            @elseif(str_contains(strtolower($program?->material_code ?? ''), 'mdf'))
                bg-purple-500 border-purple-600 text-white
            @elseif(str_contains(strtolower($program?->material_code ?? ''), 'rift'))
                bg-green-500 border-green-600 text-white
            @else
                bg-gray-700 border-gray-800 text-white
            @endif
        ">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-3xl font-black tracking-wide">
                        {{ $program?->material_code ?? 'UNKNOWN MATERIAL' }}
                    </div>
                    <div class="text-lg opacity-90 mt-1">
                        @if(isset($material['thickness']))
                            {{ $material['thickness'] }}" thick
                        @endif
                        &bull;
                        {{ $program?->sheet_size ?? '48×96' }}
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-4xl font-black">
                        {{ $part->sheet_number ?? '?' }} <span class="text-2xl opacity-75">/ {{ $totalSheets }}</span>
                    </div>
                    <div class="text-sm opacity-75 uppercase tracking-wider">Sheet</div>
                </div>
            </div>
        </div>

        {{-- Quick Info Bar --}}
        <div class="flex-shrink-0 px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap items-center gap-4 text-sm">
                {{-- Part Count --}}
                <div class="flex items-center gap-2">
                    <x-heroicon-s-squares-2x2 class="w-5 h-5 text-gray-500" />
                    <span class="font-bold text-gray-900 dark:text-white">{{ count($partLabels) ?: '?' }}</span>
                    <span class="text-gray-500">parts</span>
                </div>

                {{-- Tool Required --}}
                @if($toolInfo)
                    <div class="flex items-center gap-2 px-3 py-1 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                        <x-heroicon-s-wrench-screwdriver class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                        <span class="font-medium text-blue-800 dark:text-blue-200">{{ $toolInfo['summary'] }}</span>
                    </div>
                @endif

                {{-- Hardware Operations --}}
                @if($hardwareOps > 0)
                    <div class="flex items-center gap-2 px-3 py-1 bg-amber-100 dark:bg-amber-900/50 rounded-lg">
                        <x-heroicon-s-cog-6-tooth class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                        <span class="font-medium text-amber-800 dark:text-amber-200">{{ $hardwareOps }} hardware ops</span>
                    </div>
                @endif

                {{-- Estimated Time (if available) --}}
                @if(!empty($partMeta['toolpaths_summary']['time_estimate']))
                    <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                        <x-heroicon-o-clock class="w-4 h-4" />
                        <span>{{ $partMeta['toolpaths_summary']['time_estimate'] }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- MAIN CONTENT AREA                                            --}}
        {{-- ============================================================ --}}

        <div class="flex-1 flex overflow-hidden">
            {{-- SVG Viewer (Left/Main) --}}
            <div class="flex-1 relative overflow-hidden bg-gray-100 dark:bg-gray-800">
                @if($processedSvg)
                    <div
                        class="w-full h-full flex items-center justify-center p-4 overflow-auto"
                        :style="`transform: scale(${zoom}); transform-origin: center center;`"
                    >
                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-4 inline-block max-w-full">
                            {!! $processedSvg !!}
                        </div>
                    </div>

                    {{-- Zoom Controls (Floating) --}}
                    <div class="absolute bottom-4 left-4 flex items-center gap-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-1">
                        <button @click="zoom = Math.max(0.5, zoom - 0.25)" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                            <x-heroicon-o-minus class="w-4 h-4" />
                        </button>
                        <span class="px-2 text-sm font-medium" x-text="Math.round(zoom * 100) + '%'"></span>
                        <button @click="zoom = Math.min(3, zoom + 0.25)" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                            <x-heroicon-o-plus class="w-4 h-4" />
                        </button>
                        <button @click="zoom = 1" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded border-l">
                            <x-heroicon-o-arrows-pointing-in class="w-4 h-4" />
                        </button>
                    </div>
                @else
                    <div class="flex items-center justify-center h-full text-gray-400">
                        <div class="text-center">
                            <x-heroicon-o-photo class="w-16 h-16 mx-auto mb-3 opacity-50" />
                            <p class="text-lg">No preview available</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right Panel: Verification Checklist + Parts List --}}
            <div class="w-80 flex-shrink-0 border-l border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex flex-col">

                {{-- PRE-CUT CHECKLIST --}}
                <div class="flex-shrink-0 p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                        Pre-Cut Checklist
                    </h3>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                               :class="{ 'bg-green-50 dark:bg-green-900/20': checklist.material }">
                            <input type="checkbox" x-model="checklist.material"
                                   class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Material matches: <span class="font-bold">{{ $program?->material_code ?? '?' }}</span>
                            </span>
                        </label>

                        <label class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                               :class="{ 'bg-green-50 dark:bg-green-900/20': checklist.orientation }">
                            <input type="checkbox" x-model="checklist.orientation"
                                   class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Sheet orientation correct
                            </span>
                        </label>

                        <label class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                               :class="{ 'bg-green-50 dark:bg-green-900/20': checklist.tool }">
                            <input type="checkbox" x-model="checklist.tool"
                                   class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Tool loaded: <span class="font-bold">{{ $toolInfo['summary'] ?? 'N/A' }}</span>
                            </span>
                        </label>

                        <label class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                               :class="{ 'bg-green-50 dark:bg-green-900/20': checklist.ready }">
                            <input type="checkbox" x-model="checklist.ready"
                                   class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Spoilboard clear, ready to cut
                            </span>
                        </label>
                    </div>

                    {{-- Ready Indicator --}}
                    <div class="mt-3 p-3 rounded-lg text-center transition-all"
                         :class="allChecked() ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700'">
                        <template x-if="allChecked()">
                            <div class="flex items-center justify-center gap-2 text-green-700 dark:text-green-400 font-bold">
                                <x-heroicon-s-check-circle class="w-5 h-5" />
                                Ready to Cut
                            </div>
                        </template>
                        <template x-if="!allChecked()">
                            <div class="text-gray-500 dark:text-gray-400 text-sm">
                                Complete checklist before cutting
                            </div>
                        </template>
                    </div>
                </div>

                {{-- PARTS ON THIS SHEET --}}
                @if(!empty($partLabels))
                    <div class="flex-1 overflow-y-auto p-4">
                        <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                            Parts on This Sheet ({{ count($partLabels) }})
                        </h3>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($partLabels as $label)
                                <div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-center">
                                    <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ADVANCED TOGGLE --}}
                <div class="flex-shrink-0 border-t border-gray-200 dark:border-gray-700">
                    <button
                        @click="showAdvanced = !showAdvanced"
                        class="w-full px-4 py-3 text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-between"
                    >
                        <span>Toolpath Details</span>
                        <x-heroicon-o-chevron-down class="w-4 h-4 transition-transform" ::class="{ 'rotate-180': showAdvanced }" />
                    </button>

                    {{-- Advanced Layer Panel (Hidden by Default) --}}
                    <div x-show="showAdvanced" x-collapse class="border-t border-gray-100 dark:border-gray-700">
                        @if(!empty($layers))
                            <div class="max-h-48 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($layers as $layer)
                                    <div class="px-4 py-2 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded" style="background-color: {{ $layer['color'] }}"></span>
                                            <span class="text-gray-700 dark:text-gray-300">{{ $layer['displayName'] }}</span>
                                        </div>
                                        @if($layer['hardware'])
                                            <div class="ml-5 text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                                                {{ $layer['hardware']['note'] ?? $layer['hardware']['type'] }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="px-4 py-3 text-sm text-gray-400">No layer data available</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- ============================================================ --}}
        {{-- EMPTY STATE                                                  --}}
        {{-- ============================================================ --}}
        <div class="flex flex-col items-center justify-center flex-1 p-8 text-center">
            <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                <x-heroicon-o-document-magnifying-glass class="w-10 h-10 text-gray-400 dark:text-gray-500" />
            </div>
            <h3 class="text-xl font-medium text-gray-600 dark:text-gray-400 mb-2">VCarve Setup Sheet</h3>
            <p class="text-sm text-gray-400 dark:text-gray-500 max-w-sm">
                Select a part from the list to view its setup sheet and verify machine configuration before cutting.
            </p>
        </div>
    @endif
</div>

<style>
    /* Ensure SVG is visible and properly sized */
    .vcarve-svg-container svg {
        max-width: 100%;
        height: auto;
        display: block;
    }

    /* SVG path visibility */
    svg path, svg line, svg polyline, svg circle {
        vector-effect: non-scaling-stroke;
    }

    /* Layer interactivity (for advanced view) */
    .vcarve-layer {
        transition: opacity 0.2s ease;
    }

    .vcarve-layer.layer-hidden {
        opacity: 0.1;
    }

    /* Large touch targets for shop floor */
    input[type="checkbox"] {
        cursor: pointer;
    }

    /* High contrast for dusty environments */
    @media (prefers-contrast: more) {
        .material-banner {
            font-weight: 900;
        }
    }
</style>
