<x-filament-panels::page
    x-data="{
        autoSaveInterval: null,
        lastSaveTime: null,
        displayTime: '',
        isSaving: false,
        updateInterval: null,
        draftTimestamp: '{{ $this->draft?->updated_at?->toISOString() ?? '' }}',

        init() {
            if (this.draftTimestamp) {
                this.lastSaveTime = new Date(this.draftTimestamp);
                this.updateDisplayTime();
            }

            this.autoSaveInterval = setInterval(() => {
                this.save();
            }, 30000);

            this.updateInterval = setInterval(() => {
                this.updateDisplayTime();
            }, 30000);
        },

        save() {
            this.isSaving = true;
            this.$wire.saveDraft().then(() => {
                this.lastSaveTime = new Date();
                this.displayTime = 'just now';
                this.isSaving = false;
            });
        },

        updateDisplayTime() {
            if (!this.lastSaveTime) return;
            const now = new Date();
            const diffMs = now - this.lastSaveTime;
            const diffSeconds = Math.floor(diffMs / 1000);
            const diffMinutes = Math.floor(diffSeconds / 60);
            const diffHours = Math.floor(diffMinutes / 60);

            if (diffSeconds < 60) {
                this.displayTime = 'just now';
            } else if (diffMinutes < 60) {
                this.displayTime = diffMinutes === 1 ? '1 minute ago' : diffMinutes + ' minutes ago';
            } else if (diffHours < 24) {
                this.displayTime = diffHours === 1 ? '1 hour ago' : diffHours + ' hours ago';
            } else {
                this.displayTime = this.lastSaveTime.toLocaleDateString();
            }
        },

        destroy() {
            if (this.autoSaveInterval) clearInterval(this.autoSaveInterval);
            if (this.updateInterval) clearInterval(this.updateInterval);
        }
    }"
>
    {{-- Draft Resume Banner --}}
    @if($this->draft)
        <div class="rounded-lg bg-warning-50 dark:bg-warning-500/10 p-4 border border-warning-200 dark:border-warning-500/20 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-document-duplicate class="h-5 w-5 text-warning-500" />
                    <div>
                        <p class="text-sm font-medium text-warning-800 dark:text-warning-200">
                            Resuming Draft
                        </p>
                        <p class="text-xs text-warning-600 dark:text-warning-300">
                            Last saved {{ $this->draft->updated_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
                <x-filament::button
                    wire:click="discardDraft"
                    color="gray"
                    size="sm"
                >
                    Start Fresh
                </x-filament::button>
            </div>
        </div>
    @endif

    {{-- Auto-Save Status Indicator --}}
    <div class="flex items-center justify-end mb-2 text-xs text-gray-400 dark:text-gray-500">
        <span class="flex items-center gap-1.5">
            <template x-if="isSaving">
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 animate-spin text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Saving...</span>
                </span>
            </template>
            <template x-if="!isSaving && lastSaveTime">
                <span class="flex items-center gap-1.5 transition-opacity duration-300">
                    <x-heroicon-o-cloud-arrow-up class="w-3.5 h-3.5 text-green-500" />
                    <span>Draft saved <span x-text="displayTime"></span></span>
                </span>
            </template>
            <template x-if="!isSaving && !lastSaveTime">
                <span class="flex items-center gap-1.5">
                    <x-heroicon-o-cloud class="w-3.5 h-3.5" />
                    <span>Auto-saving enabled</span>
                </span>
            </template>
        </span>
    </div>

    {{-- Two Column Layout: Wizard + Summary Sidebar (same as CreateProject) --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:gap-6">
        {{-- Main Column: Wizard Form (grows to fill available space) --}}
        <div class="w-full min-w-0 flex-1">
            <form wire:submit="createSalesOrder">
                {{ $this->form }}
            </form>
        </div>

        {{-- Sidebar Column: PDF & Entity Summary (constrained width on desktop) --}}
        <div class="w-full xl:w-80 xl:max-w-xs xl:flex-shrink-0 xl:sticky xl:top-20 space-y-4">
            {{-- PDF Document Info Card --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-document class="w-4 h-4 text-primary-500" />
                        PDF Document
                    </h3>
                </div>
                <div class="p-4">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $this->pdfDocument->file_name }}">
                        {{ $this->pdfDocument->file_name }}
                    </p>
                    <div class="mt-2 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ $this->pdfDocument->page_count ?? 1 }} pages</span>
                        @if($this->pdfDocument->version_number > 1)
                            <span class="px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                v{{ $this->pdfDocument->version_number }}
                            </span>
                        @endif
                    </div>
                    @if($this->pdfDocument->file_path)
                        <a href="{{ Storage::disk('public')->url($this->pdfDocument->file_path) }}"
                           target="_blank"
                           class="mt-3 inline-flex items-center gap-1 text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400">
                            <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" />
                            Open PDF
                        </a>
                    @endif
                </div>
            </div>

            {{-- Proposal Builder - Folder Tree --}}
            @php
                // Load rooms from database with full hierarchy BEFORE Alpine init
                // This ensures the same room data is used for both init and render
                $dbRooms = $this->getProjectRooms();

                // Transform to display format for Proposal Builder tree
                $rooms = collect($dbRooms)->map(function ($room) {
                    $cabinetRuns = [];
                    foreach ($room['locations'] ?? [] as $location) {
                        foreach ($location['runs'] ?? [] as $run) {
                            $cabinetRuns[] = [
                                'id' => $run['id'],
                                'run_name' => $run['name'] ?? $location['name'] . ' - ' . ucfirst($run['run_type'] ?? 'base'),
                                'run_type' => $run['run_type'] ?? 'base',
                                'linear_feet' => $run['linear_feet'] ?? 0,
                                'cabinet_level' => $run['pricing_tier'] ?? '2',
                                'location_id' => $location['id'],
                                'location_name' => $location['name'],
                            ];
                        }
                    }
                    return [
                        'id' => $room['id'],
                        'room_name' => $room['name'],
                        'room_type' => $room['room_type'],
                        'floor_number' => $room['floor_number'],
                        'cabinet_runs' => $cabinetRuns,
                        'locations' => collect($room['locations'] ?? [])->map(fn($loc) => [
                            'id' => $loc['id'],
                            'name' => $loc['name'],
                            'location_type' => $loc['location_type'] ?? null,
                        ])->toArray(),
                    ];
                })->toArray();

                $totalRooms = count($rooms);
                $totalRuns = collect($rooms)->sum(fn($r) => count($r['cabinet_runs'] ?? []));
                $totalLf = collect($rooms)->sum(function($r) {
                    return collect($r['cabinet_runs'] ?? [])->sum(fn($run) => (float)($run['linear_feet'] ?? 0));
                });
                // Calculate tier breakdown
                $tierTotals = [];
                foreach ($rooms as $room) {
                    foreach ($room['cabinet_runs'] ?? [] as $run) {
                        $level = $run['cabinet_level'] ?? '2';
                        $lf = (float)($run['linear_feet'] ?? 0);
                        $tierTotals[$level] = ($tierTotals[$level] ?? 0) + $lf;
                    }
                }
                ksort($tierTotals);
            @endphp
            <div
                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                x-data="{
                    expandedRooms: {},
                    allExpanded: true,
                    toggleRoom(key) {
                        this.expandedRooms[key] = !this.expandedRooms[key];
                    },
                    toggleAll() {
                        this.allExpanded = !this.allExpanded;
                        Object.keys(this.expandedRooms).forEach(key => {
                            this.expandedRooms[key] = this.allExpanded;
                        });
                    },
                    init() {
                        // Initialize all rooms as expanded using the SAME $rooms array used for rendering
                        @foreach($rooms as $idx => $r)
                            this.expandedRooms['room_{{ $loop->index }}'] = true;
                        @endforeach
                    }
                }"
            >
                {{-- Header with expand/collapse control --}}
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-s-folder class="w-4 h-4 text-amber-500" />
                        Proposal Builder
                    </h3>
                    <div class="flex items-center gap-2">
                        <button
                            wire:click="openEntityCreator('room')"
                            class="p-1 text-primary-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded"
                            title="Add Room"
                        >
                            <x-heroicon-o-plus class="w-4 h-4" />
                        </button>
                        <button
                            @click="toggleAll()"
                            class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex items-center gap-1"
                        >
                            <template x-if="allExpanded">
                                <span class="flex items-center gap-1">
                                    <x-heroicon-o-minus class="w-3 h-3" />
                                    Collapse
                                </span>
                            </template>
                            <template x-if="!allExpanded">
                                <span class="flex items-center gap-1">
                                    <x-heroicon-o-plus class="w-3 h-3" />
                                    Expand
                                </span>
                            </template>
                        </button>
                    </div>
                </div>

                <div class="p-2">
                    {{-- Folder Tree Container --}}
                    <div class="max-h-72 overflow-y-auto text-xs space-y-0.5">
                        @forelse($rooms as $roomIndex => $room)
                            @php
                                $roomName = $room['room_name'] ?? $room['room_type'] ?? 'Room ' . ($roomIndex + 1);
                                $roomRuns = $room['cabinet_runs'] ?? [];
                                $roomLf = collect($roomRuns)->sum(fn($run) => (float)($run['linear_feet'] ?? 0));
                                $isLastRoom = $roomIndex === count($rooms) - 1;
                            @endphp

                            @php $roomKey = 'room_' . $loop->index; @endphp
                            {{-- Room Row (Folder) --}}
                            <div class="select-none">
                                <div
                                    class="flex items-center gap-1.5 py-1.5 px-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 cursor-pointer group transition-colors"
                                    @if(isset($room['id']))
                                        wire:click="highlightEntity('room', {{ $room['id'] }})"
                                    @endif
                                    @dblclick.stop="toggleRoom('{{ $roomKey }}')"
                                >
                                    {{-- Expand/collapse chevron (click toggles, stops propagation) --}}
                                    <span
                                        class="w-4 h-4 flex items-center justify-center text-gray-400 flex-shrink-0 hover:text-gray-600 dark:hover:text-gray-300"
                                        @click.stop="toggleRoom('{{ $roomKey }}')"
                                    >
                                        <template x-if="expandedRooms['{{ $roomKey }}']">
                                            <x-heroicon-s-chevron-down class="w-3.5 h-3.5" />
                                        </template>
                                        <template x-if="!expandedRooms['{{ $roomKey }}']">
                                            <x-heroicon-s-chevron-right class="w-3.5 h-3.5" />
                                        </template>
                                    </span>

                                    {{-- Folder icon --}}
                                    <template x-if="expandedRooms['{{ $roomKey }}']">
                                        <x-heroicon-s-folder-open class="w-4 h-4 text-amber-500 flex-shrink-0" />
                                    </template>
                                    <template x-if="!expandedRooms['{{ $roomKey }}']">
                                        <x-heroicon-s-folder class="w-4 h-4 text-amber-500 flex-shrink-0" />
                                    </template>

                                    {{-- Room name --}}
                                    <span class="font-medium text-gray-800 dark:text-gray-200 truncate flex-1" title="{{ $roomName }}">
                                        {{ $roomName }}
                                    </span>

                                    {{-- Room LF total --}}
                                    <span class="text-gray-400 dark:text-gray-500 tabular-nums text-[11px] group-hover:hidden flex-shrink-0">
                                        {{ number_format($roomLf, 1) }} LF
                                    </span>

                                    {{-- Hover Actions --}}
                                    <div class="hidden group-hover:flex items-center gap-1 flex-shrink-0">
                                        @if(isset($room['id']))
                                            <button
                                                wire:click.stop="highlightEntity('room', {{ $room['id'] }})"
                                                class="p-1 text-gray-400 hover:text-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded transition-colors"
                                                title="Edit Room"
                                            >
                                                <x-heroicon-o-pencil class="w-3 h-3" />
                                            </button>
                                            <button
                                                wire:click.stop="openEntityCreator('room_location', {{ $room['id'] }})"
                                                class="p-1 text-gray-400 hover:text-green-500 hover:bg-green-50 dark:hover:bg-green-900/30 rounded transition-colors"
                                                title="Add Location"
                                            >
                                                <x-heroicon-o-plus class="w-3 h-3" />
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- Cabinet Runs (Files) --}}
                                <div x-show="expandedRooms['{{ $roomKey }}']" x-collapse class="ml-5 pl-3 border-l-2 border-gray-200 dark:border-gray-700 space-y-0.5 mt-0.5">
                                    @forelse($roomRuns as $runIndex => $run)
                                        @php
                                            $runName = $run['run_name'] ?? 'Run ' . ($runIndex + 1);
                                            $runLf = (float)($run['linear_feet'] ?? 0);
                                            $runLevel = $run['cabinet_level'] ?? '2';
                                            $isLastRun = $runIndex === count($roomRuns) - 1;

                                            // Tier color coding with background
                                            $tierBadgeColors = [
                                                '1' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                                '2' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                                '3' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                                '4' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                                                '5' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                                'wall' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                                'base' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
                                            ];
                                            $tierBadgeColor = $tierBadgeColors[$runLevel] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
                                        @endphp

                                        <div
                                            @if(isset($run['id']))
                                                wire:click="highlightEntity('run', {{ $run['id'] }})"
                                            @endif
                                            class="flex items-center gap-1.5 py-1 px-2 rounded hover:bg-primary-50 dark:hover:bg-primary-900/30 group/run transition-colors cursor-pointer"
                                        >
                                            {{-- File icon --}}
                                            <x-heroicon-o-document-text class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />

                                            {{-- Run name --}}
                                            <span class="text-gray-600 dark:text-gray-400 truncate flex-1 min-w-0" title="{{ $runName }}">
                                                {{ $runName }}
                                            </span>

                                            {{-- Tier badge --}}
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $tierBadgeColor }} flex-shrink-0">
                                                T{{ $runLevel }}
                                            </span>

                                            {{-- Linear feet --}}
                                            <span class="text-gray-400 dark:text-gray-500 tabular-nums text-[11px] flex-shrink-0 w-10 text-right">
                                                {{ number_format($runLf, 1) }}
                                            </span>
                                        </div>
                                    @empty
                                        <div class="flex items-center gap-1.5 py-1 px-2 text-gray-400 dark:text-gray-500 italic text-[11px]">
                                            <x-heroicon-o-inbox class="w-3.5 h-3.5" />
                                            <span>No runs yet</span>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-gray-400 dark:text-gray-500">
                                <x-heroicon-o-folder-plus class="w-10 h-10 mx-auto mb-2 opacity-40" />
                                <p class="text-sm">No rooms yet</p>
                                <p class="text-[10px] mt-1 mb-3">Create rooms to build your project structure</p>
                                <button
                                    wire:click="openEntityCreator('room')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary-600 hover:text-primary-700 bg-primary-50 hover:bg-primary-100 dark:bg-primary-900/20 dark:hover:bg-primary-900/30 rounded-lg transition-colors"
                                >
                                    <x-heroicon-o-plus class="w-3.5 h-3.5" />
                                    Create First Room
                                </button>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Summary Footer --}}
                @if($totalRooms > 0)
                    <div class="border-t border-gray-200 dark:border-gray-700 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-b-xl">
                        {{-- Totals Row --}}
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $totalRooms }} {{ Str::plural('room', $totalRooms) }}</span>
                                <span class="text-gray-300 dark:text-gray-600">•</span>
                                <span>{{ $totalRuns }} {{ Str::plural('run', $totalRuns) }}</span>
                            </div>
                            <span class="text-sm font-bold text-primary-600 dark:text-primary-400">
                                {{ number_format($totalLf, 1) }} LF
                            </span>
                        </div>

                        {{-- Tier Breakdown Pills --}}
                        @if(count($tierTotals) > 0)
                            <div class="flex flex-wrap gap-1">
                                @foreach($tierTotals as $tier => $lf)
                                    @php
                                        $tierBgColors = [
                                            '1' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                                            '2' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
                                            '3' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
                                            '4' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
                                            '5' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                                        ];
                                        $pillColor = $tierBgColors[$tier] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400';
                                    @endphp
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium {{ $pillColor }}">
                                        T{{ $tier }}: {{ number_format($lf, 1) }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Designer's Estimate Section (from cover page scope_estimate) --}}
            @php
                // Collect scope estimates from all cover pages
                $designerEstimates = [];
                $pageMetadata = $this->data['page_metadata'] ?? [];
                foreach ($pageMetadata as $page) {
                    if (($page['primary_purpose'] ?? '') === 'cover' && !empty($page['scope_estimate'])) {
                        foreach ($page['scope_estimate'] as $item) {
                            $unit = $item['unit'] ?? 'LF';
                            $qty = floatval($item['quantity'] ?? 0);
                            $type = $item['item_type'] ?? 'Unknown';
                            if ($qty > 0) {
                                $designerEstimates[] = [
                                    'type' => $type,
                                    'quantity' => $qty,
                                    'unit' => $unit,
                                ];
                            }
                        }
                    }
                }

                // Group by unit for totals
                $designerTotalsByUnit = [];
                foreach ($designerEstimates as $item) {
                    $unit = $item['unit'];
                    $designerTotalsByUnit[$unit] = ($designerTotalsByUnit[$unit] ?? 0) + $item['quantity'];
                }
            @endphp

            @if(count($designerEstimates) > 0)
                <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 shadow-sm">
                    <div class="p-3 border-b border-amber-200 dark:border-amber-800 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-200 flex items-center gap-2">
                            <x-heroicon-o-document-magnifying-glass class="w-4 h-4 text-amber-500" />
                            Designer's Estimate
                        </h3>
                        <span class="text-[10px] uppercase text-amber-600 dark:text-amber-400 font-medium">Reference</span>
                    </div>
                    <div class="p-3">
                        {{-- Line items --}}
                        <div class="space-y-1.5 text-xs">
                            @foreach($designerEstimates as $item)
                                <div class="flex items-center justify-between py-1 px-2 rounded bg-white/50 dark:bg-gray-800/50">
                                    <span class="text-gray-700 dark:text-gray-300 truncate" title="{{ $item['type'] }}">
                                        {{ Str::limit($item['type'], 20) }}
                                    </span>
                                    <span class="font-medium text-amber-700 dark:text-amber-300 tabular-nums">
                                        {{ number_format($item['quantity'], 1) }} {{ $item['unit'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Totals by unit --}}
                        <div class="mt-3 pt-2 border-t border-amber-200 dark:border-amber-700">
                            <div class="flex flex-wrap gap-2 justify-end">
                                @foreach($designerTotalsByUnit as $unit => $total)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 text-xs font-bold">
                                        {{ number_format($total, 1) }} {{ $unit }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TCS Mapped (Draft) Section --}}
            @if($totalLf > 0)
                <div class="rounded-xl border border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-900/20 shadow-sm">
                    <div class="p-3 border-b border-primary-200 dark:border-primary-800 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-primary-900 dark:text-primary-200 flex items-center gap-2">
                            <x-heroicon-o-calculator class="w-4 h-4 text-primary-500" />
                            TCS Mapped
                        </h3>
                        <span class="text-[10px] uppercase text-primary-600 dark:text-primary-400 font-medium px-1.5 py-0.5 bg-primary-200 dark:bg-primary-800 rounded">Draft</span>
                    </div>
                    <div class="p-3">
                        {{-- Comparison with Designer's Estimate --}}
                        @if(isset($designerTotalsByUnit['LF']) && $designerTotalsByUnit['LF'] > 0)
                            @php
                                $designerLf = $designerTotalsByUnit['LF'];
                                $variance = $totalLf - $designerLf;
                                $variancePercent = ($designerLf > 0) ? (($variance / $designerLf) * 100) : 0;
                                $varianceColor = abs($variancePercent) <= 5 ? 'text-green-600' : ($variancePercent > 0 ? 'text-orange-600' : 'text-blue-600');
                            @endphp
                            <div class="mb-3 p-2 rounded bg-white/50 dark:bg-gray-800/50 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">vs Designer's Est.</span>
                                    <span class="{{ $varianceColor }} font-medium">
                                        {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 1) }} LF
                                        ({{ $variance >= 0 ? '+' : '' }}{{ number_format($variancePercent, 0) }}%)
                                    </span>
                                </div>
                            </div>
                        @endif

                        {{-- LF Total --}}
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-300">Total Linear Feet</span>
                            <span class="text-lg font-bold text-primary-600 dark:text-primary-400">
                                {{ number_format($totalLf, 1) }} LF
                            </span>
                        </div>

                        {{-- Tier Breakdown --}}
                        @if(count($tierTotals) > 0)
                            <div class="space-y-1 text-xs">
                                @foreach($tierTotals as $tier => $lf)
                                    @php
                                        $tierBgColors = [
                                            '1' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                                            '2' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
                                            '3' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
                                            '4' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
                                            '5' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                                        ];
                                        $pillColor = $tierBgColors[$tier] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400';
                                    @endphp
                                    <div class="flex items-center justify-between py-1 px-2 rounded {{ $pillColor }}">
                                        <span>Tier {{ $tier }}</span>
                                        <span class="font-medium tabular-nums">{{ number_format($lf, 1) }} LF</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Quick Customer Info --}}
            @if($this->record->partner)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-4">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Customer</h4>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $this->record->partner->name }}
                    </div>
                    @if($this->record->partner->phone)
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $this->record->partner->phone }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- Quick Actions --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-4">
                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Quick Actions</h4>
                <div class="space-y-2">
                    {{-- Confirm to Project - saves draft to project record --}}
                    <x-filament::button
                        wire:click="confirmToProject"
                        wire:confirm="This will save all draft data to the project record. Continue?"
                        color="success"
                        size="sm"
                        icon="heroicon-o-check-circle"
                        class="w-full"
                    >
                        Confirm to Project
                    </x-filament::button>

                    <x-filament::button
                        wire:click="tryAutomatic"
                        color="warning"
                        size="sm"
                        icon="heroicon-o-sparkles"
                        class="w-full"
                    >
                        Auto-Parse PDF
                    </x-filament::button>

                    <x-filament::button
                        wire:click="saveRoomsAndCabinets"
                        color="info"
                        size="sm"
                        icon="heroicon-o-folder-plus"
                        class="w-full"
                    >
                        Save Entities
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        :href="\Webkul\Project\Filament\Resources\ProjectResource::getUrl('view', ['record' => $this->record])"
                        color="gray"
                        size="sm"
                        class="w-full"
                    >
                        Back to Project
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>

    {{-- Cover Page Data Conflict Resolution Modal --}}
    <x-filament::modal
        id="cover-page-conflict-modal"
        icon="heroicon-o-exclamation-triangle"
        icon-color="warning"
        heading="Existing Data Detected"
        description="Some extracted data conflicts with existing project information. Select which fields to update."
        width="2xl"
        :close-by-clicking-away="false"
    >
        <div class="space-y-4">
            {{-- Quick Action Buttons --}}
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ count($this->selectedFields) }} of {{ count($this->dataConflicts) }} fields selected
                </span>
                <div class="flex gap-2">
                    <x-filament::button
                        wire:click="selectAllFields"
                        color="gray"
                        size="xs"
                    >
                        Select All
                    </x-filament::button>
                    <x-filament::button
                        wire:click="deselectAllFields"
                        color="gray"
                        size="xs"
                    >
                        Keep All Current
                    </x-filament::button>
                </div>
            </div>

            {{-- Conflict List --}}
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse($this->dataConflicts as $fieldKey => $conflict)
                    <div
                        wire:key="conflict-{{ $fieldKey }}"
                        class="rounded-lg border {{ in_array($fieldKey, $this->selectedFields) ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' }} p-3 cursor-pointer transition-colors"
                        wire:click="toggleFieldSelection('{{ $fieldKey }}')"
                    >
                        <div class="flex items-start gap-3">
                            {{-- Checkbox --}}
                            <div class="flex-shrink-0 pt-0.5">
                                <div class="w-5 h-5 rounded border-2 flex items-center justify-center {{ in_array($fieldKey, $this->selectedFields) ? 'border-primary-500 bg-primary-500' : 'border-gray-300 dark:border-gray-600' }}">
                                    @if(in_array($fieldKey, $this->selectedFields))
                                        <x-heroicon-s-check class="w-3.5 h-3.5 text-white" />
                                    @endif
                                </div>
                            </div>

                            {{-- Field Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    @if(!empty($conflict['icon']))
                                        <x-dynamic-component :component="$conflict['icon']" class="w-4 h-4 text-gray-400" />
                                    @endif
                                    <span class="font-medium text-sm text-gray-900 dark:text-white">
                                        {{ $conflict['label'] ?? $fieldKey }}
                                    </span>
                                </div>

                                {{-- Current vs Extracted Values --}}
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    {{-- Current Value --}}
                                    <div class="rounded bg-gray-100 dark:bg-gray-700 p-2">
                                        <div class="text-gray-500 dark:text-gray-400 font-medium mb-1">Current</div>
                                        <div class="text-gray-800 dark:text-gray-200 break-words">
                                            {{ $conflict['current'] ?: '(empty)' }}
                                        </div>
                                    </div>

                                    {{-- Extracted Value --}}
                                    <div class="rounded {{ in_array($fieldKey, $this->selectedFields) ? 'bg-primary-100 dark:bg-primary-900/30' : 'bg-green-50 dark:bg-green-900/20' }} p-2">
                                        <div class="{{ in_array($fieldKey, $this->selectedFields) ? 'text-primary-600 dark:text-primary-400' : 'text-green-600 dark:text-green-400' }} font-medium mb-1">Extracted</div>
                                        <div class="text-gray-800 dark:text-gray-200 break-words">
                                            {{ $conflict['extracted'] ?: '(empty)' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                        No conflicts detected
                    </div>
                @endforelse
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex items-center justify-end gap-3">
                <x-filament::button
                    wire:click="cancelMerge"
                    color="gray"
                >
                    Keep Current Data
                </x-filament::button>
                <x-filament::button
                    wire:click="confirmMerge"
                    color="primary"
                >
                    Update Selected ({{ count($this->selectedFields) }})
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>

    {{-- Edit Details Slide-Over Modal --}}
    <x-filament::modal
        id="edit-details-modal"
        slide-over
        width="screen"
    >
        <x-slot name="heading">
            @php
                $purpose = $this->getEditingPagePurpose();
                $headings = [
                    'cover' => 'Cover Page Details',
                    'floor_plan' => 'Floor Plan Details',
                    'elevations' => 'Elevation Details',
                    'countertops' => 'Countertop Details',
                    'reference' => 'Reference Page Details',
                ];
            @endphp
            {{ $headings[$purpose] ?? 'Page Details' }}
            @if($this->editingPageNumber)
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">(Page {{ $this->editingPageNumber }})</span>
            @endif
        </x-slot>

        {{-- Three-Column Layout: PDF Viewer (Left ~45%) + Navigation (Center ~25%) + Entity Details (Right ~30%) --}}
        <div class="flex flex-row gap-4" style="min-height: calc(100vh - 200px);">

            {{-- PDF Viewer Panel (Left Side - ~45% width) --}}
            <div class="shrink-0 flex flex-col" style="width: 45%;">

                @if($this->editingPageNumber && $this->pdfDocument)
                    @php
                        $modalPdfUrl = Storage::disk('public')->url($this->pdfDocument->file_path);
                        $modalPageNumber = $this->editingPageNumber;
                    @endphp
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 overflow-hidden flex-1 flex flex-col">
                        {{-- Controls Header --}}
                        <div class="flex items-center justify-between px-4 py-2 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Page {{ $this->editingPageNumber }} of {{ $this->pdfDocument->page_count ?? 1 }}
                            </span>
                            <a href="{{ $modalPdfUrl }}#page={{ $modalPageNumber }}"
                               target="_blank"
                               class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 flex items-center gap-1">
                                <x-heroicon-m-arrow-top-right-on-square class="w-3.5 h-3.5" />
                                Open in new tab
                            </a>
                        </div>

                        {{-- PDF Viewer using iframe (scrolls to page) --}}
                        <div class="flex-1 bg-gray-100 dark:bg-gray-900 overflow-hidden flex flex-col" style="min-height: calc(100vh - 280px);">
                            <iframe
                                src="{{ $modalPdfUrl }}#page={{ $modalPageNumber }}&view=FitH"
                                class="w-full flex-1 rounded"
                                style="min-height: calc(100vh - 300px);"
                            ></iframe>
                            <p class="text-xs text-gray-500 dark:text-gray-400 text-center py-2 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                                <x-heroicon-m-information-circle class="w-3.5 h-3.5 inline -mt-0.5" />
                                Scrolled to page {{ $modalPageNumber }}. Use browser's PDF controls to navigate if needed.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-8 text-center flex items-center justify-center" style="height: calc(100vh - 260px); min-height: 500px;">
                        <div>
                            <x-heroicon-o-document class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                            <p class="text-gray-500 dark:text-gray-400">No page selected</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Navigation Panel (Center - ~25% width, scrollable) --}}
            <div class="shrink-0 overflow-y-auto border-x border-gray-200 dark:border-gray-700 px-4" style="width: 25%; max-height: calc(100vh - 200px);">
                <div class="space-y-4">
                    {{-- Quick Action Icons (AI Extract & Activity) --}}
                    <div class="flex items-center justify-end gap-2 pb-2 border-b border-gray-100 dark:border-gray-800">
                        <x-filament::icon-button
                            icon="heroicon-m-sparkles"
                            wire:click="aiExtractPageDetails"
                            wire:loading.attr="disabled"
                            wire:target="aiExtractPageDetails"
                            tooltip="AI Extract from PDF"
                            label="AI Extract"
                        />
                        <x-filament::icon-button
                            icon="heroicon-s-chat-bubble-left-right"
                            wire:click="openPageChatter"
                            tooltip="Activity & Comments"
                            label="Activity"
                        />
                    </div>

            @php $purpose = $this->getEditingPagePurpose(); @endphp

            @if($purpose === 'cover')
                {{-- Cover Page Form --}}
                <div class="space-y-4">
                    {{-- Project Address Section --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Project Address</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Street</label>
                                <input type="text" wire:model="editDetailsData.cover_address_street"
                                    class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">City</label>
                                    <input type="text" wire:model="editDetailsData.cover_address_city"
                                        class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">ZIP</label>
                                    <input type="text" wire:model="editDetailsData.cover_address_zip"
                                        class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Country</label>
                                    <select wire:model.live="editDetailsData.cover_address_country"
                                        class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                        <option value="">Select Country</option>
                                        <option value="US" @selected(($editDetailsData['cover_address_country'] ?? '') === 'US')>United States</option>
                                        <option value="CA" @selected(($editDetailsData['cover_address_country'] ?? '') === 'CA')>Canada</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">State</label>
                                    <select wire:model="editDetailsData.cover_address_state"
                                        class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                        <option value="">Select State</option>
                                        @php
                                            $selectedCountry = $editDetailsData['cover_address_country'] ?? 'US';
                                            $countryModel = \Webkul\Support\Models\Country::where('code', $selectedCountry)->first();
                                            $states = $countryModel ? \Webkul\Support\Models\State::where('country_id', $countryModel->id)->orderBy('name')->get() : collect();
                                        @endphp
                                        @foreach($states as $state)
                                            <option value="{{ $state->code }}" @selected(($editDetailsData['cover_address_state'] ?? '') === $state->code || ($editDetailsData['cover_address_state'] ?? '') === $state->name)>{{ $state->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Designer Info Section --}}
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Designer Information</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Company</label>
                                <input type="text" wire:model="editDetailsData.cover_designer_company"
                                    class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Drawn By</label>
                                <input type="text" wire:model="editDetailsData.cover_designer_drawn_by"
                                    class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Approved By</label>
                                <input type="text" wire:model="editDetailsData.cover_designer_approved_by"
                                    class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Drawing #</label>
                                <input type="text" wire:model="editDetailsData.drawing_number"
                                    class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                            </div>
                        </div>
                    </div>

                    {{-- Page Label & Notes --}}
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Page Label</label>
                        <input type="text" wire:model="editDetailsData.page_label"
                            class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="e.g., Cover Sheet" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Notes</label>
                        <textarea wire:model="editDetailsData.page_notes" rows="2"
                            class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="Any additional notes..."></textarea>
                    </div>
                </div>
            @elseif($purpose === 'floor_plan')
                {{-- Floor Plan Form --}}
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Floor/Level Label</label>
                        <input type="text" wire:model="editDetailsData.page_label"
                            class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="e.g., First Floor, Level 2" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Notes</label>
                        <textarea wire:model="editDetailsData.page_notes" rows="2"
                            class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="Any additional notes..."></textarea>
                    </div>

                    {{-- Hierarchical Entity Navigation Section --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <x-heroicon-m-squares-plus class="w-4 h-4 text-primary-500" />
                            Build Project Structure
                        </h4>

                        {{-- Breadcrumb Navigation --}}
                        {{-- Single click: open editor for that entity | Double click: jump tree to that level --}}
                        <nav class="flex items-center gap-1 mb-3 text-xs flex-wrap" aria-label="Hierarchy breadcrumb">
                            <button type="button"
                                wire:click="navigateToBreadcrumb('rooms')"
                                class="flex items-center gap-1 px-2 py-1 rounded {{ $hierarchyLevel === 'rooms' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                                title="Click to view rooms list">
                                <x-heroicon-m-home class="w-3.5 h-3.5" />
                                <span>Rooms</span>
                            </button>

                            @if($selectedRoomId)
                                <x-heroicon-m-chevron-right class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                <button type="button"
                                    wire:click="editBreadcrumbEntity('room', {{ $selectedRoomId }})"
                                    wire:dblclick="navigateToBreadcrumb('locations', {{ $selectedRoomId }})"
                                    class="flex items-center gap-1 px-2 py-1 rounded {{ $hierarchyLevel === 'locations' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                                    title="Click to edit | Double-click to jump to locations">
                                    <x-heroicon-m-map-pin class="w-3.5 h-3.5" />
                                    <span class="max-w-20 truncate">{{ $selectedRoomName }}</span>
                                </button>
                            @endif

                            @if($selectedLocationId)
                                <x-heroicon-m-chevron-right class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                <button type="button"
                                    wire:click="editBreadcrumbEntity('room_location', {{ $selectedLocationId }})"
                                    wire:dblclick="navigateToBreadcrumb('runs', {{ $selectedLocationId }})"
                                    class="flex items-center gap-1 px-2 py-1 rounded {{ $hierarchyLevel === 'runs' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                                    title="Click to edit | Double-click to jump to runs">
                                    <x-heroicon-m-rectangle-group class="w-3.5 h-3.5" />
                                    <span class="max-w-20 truncate">{{ $selectedLocationName }}</span>
                                </button>
                            @endif

                            @if($selectedRunId)
                                <x-heroicon-m-chevron-right class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                <button type="button"
                                    wire:click="editBreadcrumbEntity('cabinet_run', {{ $selectedRunId }})"
                                    wire:dblclick="navigateToBreadcrumb('cabinets', {{ $selectedRunId }})"
                                    class="flex items-center gap-1 px-2 py-1 rounded {{ $hierarchyLevel === 'cabinets' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                                    title="Click to edit | Double-click to jump to cabinets">
                                    <x-heroicon-m-cube class="w-3.5 h-3.5" />
                                    <span class="max-w-20 truncate">{{ $selectedRunName }}</span>
                                </button>
                            @endif

                            @if($selectedCabinetId)
                                <x-heroicon-m-chevron-right class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                <button type="button"
                                    wire:click="editBreadcrumbEntity('cabinet', {{ $selectedCabinetId }})"
                                    wire:dblclick="navigateToBreadcrumb('sections', {{ $selectedCabinetId }})"
                                    class="flex items-center gap-1 px-2 py-1 rounded {{ $hierarchyLevel === 'sections' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                                    title="Click to edit | Double-click to jump to sections">
                                    <x-heroicon-m-squares-2x2 class="w-3.5 h-3.5" />
                                    <span class="max-w-20 truncate">{{ $selectedCabinetName }}</span>
                                </button>
                            @endif

                            @if($selectedSectionId)
                                <x-heroicon-m-chevron-right class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                <button type="button"
                                    wire:click="editBreadcrumbEntity('section', {{ $selectedSectionId }})"
                                    class="flex items-center gap-1 px-2 py-1 rounded bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 font-medium cursor-pointer"
                                    title="Click to edit section">
                                    <x-heroicon-m-puzzle-piece class="w-3.5 h-3.5" />
                                    <span class="max-w-20 truncate">{{ $selectedSectionName }}</span>
                                </button>
                            @endif
                        </nav>

                        {{-- Back Button (when not at root) --}}
                        @if($hierarchyLevel !== 'rooms')
                            <button type="button"
                                wire:click="navigateUp"
                                class="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-2">
                                <x-heroicon-m-arrow-left class="w-3.5 h-3.5" />
                                <span>Back</span>
                            </button>
                        @endif

                        {{-- Dynamic Hierarchy List --}}
                        @php
                            $hierarchyItems = $this->getHierarchyItems() ?? [];
                        @endphp

                        {{-- Skeleton Loading State --}}
                        @if(!$this->hierarchyLoaded)
                            <div class="mb-3 space-y-1.5 animate-pulse">
                                <div class="h-4 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                @for($i = 0; $i < 3; $i++)
                                    <div class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-800">
                                        <div class="w-4 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                        <div class="flex-1">
                                            <div class="h-4 w-3/4 bg-gray-200 dark:bg-gray-700 rounded mb-1"></div>
                                            <div class="h-3 w-1/2 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                        </div>
                                        <div class="w-16 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                    </div>
                                @endfor
                            </div>
                        @elseif(!empty($hierarchyItems) && count($hierarchyItems) > 0)
                            <div class="mb-3 space-y-1.5 max-h-48 overflow-y-auto">
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                    {{ $this->getHierarchyLevelLabel() }} ({{ count($hierarchyItems) }}):
                                </span>
                                @foreach($hierarchyItems as $item)
                                    @php
                                        // Determine entity type for highlighting - use actual component type for components level
                                        $entityTypeForItem = match($hierarchyLevel) {
                                            'rooms' => 'room',
                                            'locations' => 'location',
                                            'runs' => 'run',
                                            'cabinets' => 'cabinet',
                                            'sections' => 'section',
                                            'components' => $item['component_type'] ?? 'door',
                                            default => 'room'
                                        };
                                        // Check if this item is currently highlighted
                                        $isHighlighted = $highlightedEntityType === $entityTypeForItem && $highlightedEntityId === $item['id'];
                                    @endphp
                                    <div
                                        wire:click="highlightEntity('{{ $entityTypeForItem }}', {{ $item['id'] }})"
                                        wire:dblclick="{{ $this->getDrillDownAction($item) }}"
                                        class="flex items-center justify-between p-2 rounded-lg cursor-pointer transition-colors
                                            {{ $isHighlighted
                                                ? 'bg-primary-100 dark:bg-primary-900/30 ring-2 ring-primary-500 ring-offset-1'
                                                : 'bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700'
                                            }}"
                                        title="Click to view details, double-click to drill down">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            @if($hierarchyLevel === 'rooms')
                                                <x-heroicon-m-home class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                            @elseif($hierarchyLevel === 'locations')
                                                <x-heroicon-m-map-pin class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                            @elseif($hierarchyLevel === 'runs')
                                                <x-heroicon-m-rectangle-group class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                            @elseif($hierarchyLevel === 'cabinets')
                                                <x-heroicon-m-cube class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                            @elseif($hierarchyLevel === 'sections')
                                                <x-heroicon-m-squares-2x2 class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                            @elseif($hierarchyLevel === 'components')
                                                @if(($item['component_type'] ?? '') === 'door')
                                                    <x-heroicon-m-square-2-stack class="w-4 h-4 text-blue-400 flex-shrink-0" title="Door" />
                                                @elseif(($item['component_type'] ?? '') === 'drawer')
                                                    <x-heroicon-m-inbox-stack class="w-4 h-4 text-green-400 flex-shrink-0" title="Drawer" />
                                                @elseif(($item['component_type'] ?? '') === 'shelf')
                                                    <x-heroicon-m-bars-3-bottom-left class="w-4 h-4 text-amber-400 flex-shrink-0" title="Shelf" />
                                                @elseif(($item['component_type'] ?? '') === 'pullout')
                                                    <x-heroicon-m-arrow-right-on-rectangle class="w-4 h-4 text-purple-400 flex-shrink-0" title="Pullout" />
                                                @else
                                                    <x-heroicon-m-puzzle-piece class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                                @endif
                                            @else
                                                <x-heroicon-m-puzzle-piece class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                            @endif
                                            <div class="min-w-0">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white truncate block">{{ $item['name'] }}</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    @if($hierarchyLevel === 'rooms')
                                                        {{ ucfirst($item['room_type'] ?? 'other') }}
                                                        @if($item['floor_number']) · Floor {{ $item['floor_number'] }} @endif
                                                        @if(($item['child_count'] ?? 0) > 0)
                                                            · {{ $item['child_count'] }} location(s)
                                                        @endif
                                                    @elseif($hierarchyLevel === 'locations')
                                                        @if(($item['child_count'] ?? 0) > 0)
                                                            {{ $item['child_count'] }} run(s)
                                                        @else
                                                            No runs yet
                                                        @endif
                                                    @elseif($hierarchyLevel === 'runs')
                                                        @if($item['linear_feet']) {{ number_format($item['linear_feet'], 2) }} LF @endif
                                                        @if(($item['child_count'] ?? 0) > 0)
                                                            · {{ $item['child_count'] }} cabinet(s)
                                                        @endif
                                                        {{-- Missing Measurements Notice --}}
                                                        @if($item['has_missing_measurements'] ?? false)
                                                            <span class="inline-flex items-center gap-0.5 text-warning-600 dark:text-warning-400" title="Stored LF ({{ number_format($item['stored_linear_feet'], 2) }}) is greater than calculated ({{ number_format($item['linear_feet'], 2) }}). {{ $item['cabinets_missing_width_count'] }} cabinet(s) may be missing width values.">
                                                                <x-heroicon-s-exclamation-triangle class="w-3 h-3" />
                                                                <span class="text-[10px]">{{ number_format($item['missing_linear_feet'], 1) }}' missing</span>
                                                            </span>
                                                        @elseif($item['has_discrepancy'] ?? false)
                                                            <span class="inline-flex items-center gap-0.5 text-blue-500" title="Calculated LF differs from stored value">
                                                                <x-heroicon-s-information-circle class="w-3 h-3" />
                                                            </span>
                                                        @endif
                                                    @elseif($hierarchyLevel === 'cabinets')
                                                        {{ $item['cabinet_type'] ?? 'Cabinet' }}
                                                        @if(($item['child_count'] ?? 0) > 0)
                                                            · {{ $item['child_count'] }} section(s)
                                                        @endif
                                                    @elseif($hierarchyLevel === 'sections')
                                                        {{ ucfirst(str_replace('_', ' ', $item['section_type'] ?? 'Section')) }}
                                                        @if(($item['child_count'] ?? 0) > 0)
                                                            · {{ $item['child_count'] }} component(s)
                                                        @endif
                                                    @elseif($hierarchyLevel === 'components')
                                                        {{ ucfirst($item['component_type'] ?? 'Component') }}
                                                        @if($item['dimensions'])
                                                            · {{ $item['dimensions'] }}
                                                        @endif
                                                        @if($item['component_type'] === 'door' && ($item['hinge_side'] ?? null))
                                                            · {{ ucfirst($item['hinge_side']) }} hinge
                                                        @endif
                                                        @if($item['component_type'] === 'drawer' && ($item['slide_type'] ?? null))
                                                            · {{ $item['slide_type'] }}
                                                        @endif
                                                        @if($item['component_type'] === 'shelf' && ($item['shelf_type'] ?? null))
                                                            · {{ ucfirst(str_replace('_', ' ', $item['shelf_type'])) }}
                                                        @endif
                                                        @if($item['component_type'] === 'pullout' && ($item['pullout_type'] ?? null))
                                                            · {{ ucfirst(str_replace('_', ' ', $item['pullout_type'])) }}
                                                        @endif
                                                    @else
                                                        {{ $item['component_type'] ?? 'Component' }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            {{-- Drill Down Arrow (for levels with children) --}}
                                            @if($hierarchyLevel !== 'components')
                                                <button type="button"
                                                    wire:click="{{ $this->getDrillDownAction($item) }}"
                                                    class="p-1 text-gray-400 hover:text-primary-500 rounded"
                                                    title="Drill down">
                                                    <x-heroicon-m-chevron-right class="w-4 h-4" />
                                                </button>
                                            @endif
                                            <button type="button"
                                                wire:click="highlightEntity('{{ $hierarchyLevel === 'components' ? ($item['component_type'] ?? 'door') : $this->getInlineEditTypeForLevel() }}', {{ $item['id'] }})"
                                                class="p-1 text-gray-400 hover:text-primary-500 rounded"
                                                title="Edit">
                                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                                            </button>
                                            <button type="button"
                                                wire:click="deleteEntityDirect('{{ $hierarchyLevel === 'components' ? ($item['component_type'] ?? 'door') : $this->getEntityTypeForLevel() }}', {{ $item['id'] }})"
                                                wire:confirm="Are you sure you want to delete '{{ $item['name'] }}'? This will also delete all child entities."
                                                class="p-1 text-gray-400 hover:text-danger-500 rounded"
                                                title="Delete">
                                                <x-heroicon-m-trash class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mb-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                                @if($hierarchyLevel === 'rooms')
                                    <x-heroicon-o-home class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400">No rooms yet. Add your first room below.</p>
                                @elseif($hierarchyLevel === 'locations')
                                    <x-heroicon-o-map-pin class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400">No locations in this room yet.</p>
                                @elseif($hierarchyLevel === 'runs')
                                    <x-heroicon-o-rectangle-group class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400">No cabinet runs in this location yet.</p>
                                @elseif($hierarchyLevel === 'cabinets')
                                    <x-heroicon-o-cube class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400">No cabinets in this run yet.</p>
                                @elseif($hierarchyLevel === 'sections')
                                    <x-heroicon-o-squares-2x2 class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400">No sections in this cabinet yet.</p>
                                @else
                                    <x-heroicon-o-puzzle-piece class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400">No components in this section yet.</p>
                                @endif
                            </div>
                        @endif

                        {{-- Add Button (context-aware) --}}
                        <div class="flex flex-wrap gap-2">
                            @if($this->isAtComponentsLevel())
                                {{-- At components level: show buttons for all component types --}}
                                @foreach($this->getAvailableComponentTypes() as $type => $label)
                                    <x-filament::button
                                        size="sm"
                                        color="{{ $type === 'door' ? 'primary' : ($type === 'drawer' ? 'info' : ($type === 'shelf' ? 'success' : 'warning')) }}"
                                        icon="heroicon-m-plus"
                                        wire:click="openComponentCreator('{{ $type }}')"
                                    >
                                        Add {{ $label }}
                                    </x-filament::button>
                                @endforeach
                            @else
                                {{-- Other levels: single contextual add button --}}
                                <x-filament::button
                                    size="sm"
                                    color="primary"
                                    icon="heroicon-m-plus"
                                    wire:click="openEntityCreatorForCurrentLevel"
                                >
                                    {{ $this->getAddButtonLabel() }}
                                </x-filament::button>
                            @endif
                        </div>

                        {{-- Hint Text --}}
                        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500 italic">
                            💡 Double-click an item to drill down into its children.
                        </p>
                    </div>
                </div>
            @elseif($purpose === 'elevations')
                {{-- Elevations Form --}}
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Location Label</label>
                        <input type="text" wire:model="editDetailsData.page_label"
                            class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="e.g., Kitchen North Wall" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Linear Feet</label>
                            <input type="number" step="0.1" wire:model="editDetailsData.linear_feet"
                                class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Pricing Tier</label>
                            <select wire:model="editDetailsData.pricing_tier"
                                class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                <option value="">Select tier...</option>
                                <option value="1">Level 1 - Basic</option>
                                <option value="2">Level 2 - Standard</option>
                                <option value="3">Level 3 - Enhanced</option>
                                <option value="4">Level 4 - Premium</option>
                                <option value="5">Level 5 - Custom</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Room Name</label>
                        <input type="text" wire:model="editDetailsData.room_name"
                            class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="e.g., Kitchen, Pantry" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Notes</label>
                        <textarea wire:model="editDetailsData.page_notes" rows="2"
                            class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="Any additional notes..."></textarea>
                    </div>

                    {{-- Hierarchical Entity Builder Section (Same as Floor Plan) --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4"
                         x-data="{ projectStructureOpen: false }">
                        <button type="button"
                                @click="projectStructureOpen = !projectStructureOpen"
                                class="w-full flex items-center justify-between text-sm font-semibold text-gray-900 dark:text-white mb-2">
                            <span class="flex items-center gap-2">
                                <x-heroicon-m-squares-plus class="w-4 h-4 text-primary-500" />
                                Build Project Structure
                            </span>
                            <x-heroicon-m-chevron-down class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                                       x-bind:class="{ 'rotate-180': projectStructureOpen }" />
                        </button>

                        <div x-show="projectStructureOpen"
                             x-collapse
                             x-cloak>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                Add rooms, locations, and cabinet runs. Double-click to drill down. Linked to page {{ $this->editingPageNumber }}.
                            </p>

                            @php
                                $hierarchyItems = $this->getHierarchyItems() ?? [];
                            @endphp

                            {{-- Universal Hierarchy Navigation (same as Floor Plan) --}}
                            {{-- Breadcrumb Navigation --}}
                            @if(!empty($breadcrumbs) && count($breadcrumbs) > 0)
                                <div class="flex items-center gap-1 mb-3 text-xs flex-wrap">
                                    <button type="button"
                                        wire:click="navigateToBreadcrumb(-1)"
                                        class="text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
                                        Project
                                    </button>
                                    @foreach($breadcrumbs as $index => $crumb)
                                        <x-heroicon-m-chevron-right class="w-3 h-3 text-gray-400" />
                                        <button type="button"
                                            wire:click="navigateToBreadcrumb({{ $index }})"
                                            class="{{ $index === count($breadcrumbs) - 1 ? 'text-gray-700 dark:text-gray-300 font-medium' : 'text-primary-600 hover:text-primary-700 dark:text-primary-400' }}">
                                            {{ $crumb['name'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Hierarchy Items List --}}
                            @if(!empty($hierarchyItems) && count($hierarchyItems) > 0)
                                <div class="space-y-2 mb-3 max-h-64 overflow-y-auto">
                                    @foreach($hierarchyItems as $item)
                                        @php
                                            $entityTypeForItem = $hierarchyLevel === 'components' ? ($item['component_type'] ?? 'door') : $this->getInlineEditTypeForLevel();
                                            $isHighlighted = ($highlightedEntityType === $entityTypeForItem && $highlightedEntityId === $item['id']);
                                        @endphp
                                        <div
                                            wire:click="highlightEntity('{{ $entityTypeForItem }}', {{ $item['id'] }})"
                                            wire:dblclick="{{ $this->getDrillDownAction($item) }}"
                                            class="flex items-center justify-between p-2 rounded-lg cursor-pointer transition-colors
                                                {{ $isHighlighted
                                                    ? 'bg-primary-100 dark:bg-primary-900/30 ring-2 ring-primary-500 ring-offset-1'
                                                    : 'bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700'
                                                }}"
                                            title="Click to view details, double-click to drill down">
                                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                                @if($hierarchyLevel === 'rooms')
                                                    <x-heroicon-m-home class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                                @elseif($hierarchyLevel === 'locations')
                                                    <x-heroicon-m-map-pin class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                                @elseif($hierarchyLevel === 'runs')
                                                    <x-heroicon-m-rectangle-group class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                                @elseif($hierarchyLevel === 'cabinets')
                                                    <x-heroicon-m-cube class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                                @elseif($hierarchyLevel === 'sections')
                                                    <x-heroicon-m-squares-2x2 class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                                @else
                                                    <x-heroicon-m-puzzle-piece class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                                @endif
                                                <div class="min-w-0">
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white truncate block">{{ $item['name'] }}</span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        @if($hierarchyLevel === 'rooms')
                                                            {{ ucfirst($item['room_type'] ?? 'other') }}
                                                            @if(($item['child_count'] ?? 0) > 0)
                                                                · {{ $item['child_count'] }} location(s)
                                                            @endif
                                                        @elseif($hierarchyLevel === 'locations')
                                                            @if(($item['child_count'] ?? 0) > 0)
                                                                {{ $item['child_count'] }} run(s)
                                                            @else
                                                                No runs yet
                                                            @endif
                                                        @elseif($hierarchyLevel === 'runs')
                                                            @if($item['linear_feet']) {{ number_format($item['linear_feet'], 2) }} LF @endif
                                                            @if(($item['child_count'] ?? 0) > 0)
                                                                · {{ $item['child_count'] }} cabinet(s)
                                                            @endif
                                                        @elseif($hierarchyLevel === 'cabinets')
                                                            {{ $item['cabinet_type'] ?? 'Cabinet' }}
                                                            @if(($item['child_count'] ?? 0) > 0)
                                                                · {{ $item['child_count'] }} section(s)
                                                            @endif
                                                        @elseif($hierarchyLevel === 'sections')
                                                            @if(($item['child_count'] ?? 0) > 0)
                                                                {{ $item['child_count'] }} component(s)
                                                            @endif
                                                        @else
                                                            {{ $item['component_type'] ?? 'Component' }}
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                {{-- Drill Down Arrow (for levels with children) --}}
                                                @if($hierarchyLevel !== 'components')
                                                    <button type="button"
                                                        wire:click="{{ $this->getDrillDownAction($item) }}"
                                                        class="p-1 text-gray-400 hover:text-primary-500 rounded"
                                                        title="Drill down">
                                                        <x-heroicon-m-chevron-right class="w-4 h-4" />
                                                    </button>
                                                @endif
                                                <button type="button"
                                                    wire:click="highlightEntity('{{ $hierarchyLevel === 'components' ? ($item['component_type'] ?? 'door') : $this->getInlineEditTypeForLevel() }}', {{ $item['id'] }})"
                                                    class="p-1 text-gray-400 hover:text-primary-500 rounded"
                                                    title="Edit">
                                                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                                                </button>
                                                <button type="button"
                                                    wire:click="deleteEntityDirect('{{ $hierarchyLevel === 'components' ? ($item['component_type'] ?? 'door') : $this->getEntityTypeForLevel() }}', {{ $item['id'] }})"
                                                    wire:confirm="Are you sure you want to delete '{{ $item['name'] }}'? This will also delete all child entities."
                                                    class="p-1 text-gray-400 hover:text-danger-500 rounded"
                                                    title="Delete">
                                                    <x-heroicon-m-trash class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="mb-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                                    @if($hierarchyLevel === 'rooms')
                                        <x-heroicon-o-home class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                        <p class="text-xs text-gray-500 dark:text-gray-400">No rooms yet. Add your first room below.</p>
                                    @elseif($hierarchyLevel === 'locations')
                                        <x-heroicon-o-map-pin class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                        <p class="text-xs text-gray-500 dark:text-gray-400">No locations in this room yet.</p>
                                    @elseif($hierarchyLevel === 'runs')
                                        <x-heroicon-o-rectangle-group class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                        <p class="text-xs text-gray-500 dark:text-gray-400">No cabinet runs in this location yet.</p>
                                    @elseif($hierarchyLevel === 'cabinets')
                                        <x-heroicon-o-cube class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                        <p class="text-xs text-gray-500 dark:text-gray-400">No cabinets in this run yet.</p>
                                    @elseif($hierarchyLevel === 'sections')
                                        <x-heroicon-o-squares-2x2 class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                        <p class="text-xs text-gray-500 dark:text-gray-400">No sections in this cabinet yet.</p>
                                    @else
                                        <x-heroicon-o-puzzle-piece class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                        <p class="text-xs text-gray-500 dark:text-gray-400">No components in this section yet.</p>
                                    @endif
                                </div>
                            @endif

                            {{-- Add Button (context-aware) --}}
                            <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                                @if($this->isAtComponentsLevel())
                                    {{-- At components level: show buttons for all component types --}}
                                    @foreach($this->getAvailableComponentTypes() as $type => $label)
                                        <x-filament::button
                                            size="sm"
                                            color="{{ $type === 'door' ? 'primary' : ($type === 'drawer' ? 'info' : ($type === 'shelf' ? 'success' : 'warning')) }}"
                                            icon="heroicon-m-plus"
                                            wire:click="openComponentCreator('{{ $type }}')"
                                        >
                                            Add {{ $label }}
                                        </x-filament::button>
                                    @endforeach
                                @else
                                    {{-- Other levels: single contextual add button --}}
                                    <x-filament::button
                                        size="sm"
                                        color="primary"
                                        icon="heroicon-m-plus"
                                        wire:click="openEntityCreatorForCurrentLevel"
                                    >
                                        {{ $this->getAddButtonLabel() }}
                                    </x-filament::button>
                                @endif
                            </div>

                            {{-- Hint Text --}}
                            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500 italic">
                                💡 Double-click an item to drill down into its children.
                            </p>
                        </div>{{-- End of x-collapse container --}}
                    </div>
                </div>
            @elseif($purpose === 'countertops')
                {{-- Countertops Form --}}
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Area Label</label>
                        <input type="text" wire:model="editDetailsData.page_label"
                            class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="e.g., Kitchen Island" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Notes</label>
                        <textarea wire:model="editDetailsData.page_notes" rows="2"
                            class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="Any additional notes..."></textarea>
                    </div>
                </div>
            @else
                {{-- Default Form --}}
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Page Label</label>
                        <input type="text" wire:model="editDetailsData.page_label"
                            class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="Enter a label for this page..." />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Notes</label>
                        <textarea wire:model="editDetailsData.page_notes" rows="2"
                            class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="Any additional notes..."></textarea>
                    </div>
                </div>
            @endif
                </div>
            </div>

            {{-- Entity Details Panel (Right Side - ~30% width, scrollable) --}}
            <div class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700" style="max-height: calc(100vh - 200px);">
                @php
                    $selectedEntity = $this->getSelectedEntityDetails();
                @endphp

                @if($selectedEntity)
                    {{-- Selected Entity Header --}}
                    <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                @if($selectedEntity['type'] === 'room')
                                    <x-heroicon-m-home class="w-5 h-5 text-primary-500" />
                                @elseif($selectedEntity['type'] === 'location')
                                    <x-heroicon-m-map-pin class="w-5 h-5 text-primary-500" />
                                @elseif($selectedEntity['type'] === 'run')
                                    <x-heroicon-m-rectangle-group class="w-5 h-5 text-primary-500" />
                                @elseif($selectedEntity['type'] === 'cabinet')
                                    <x-heroicon-m-cube class="w-5 h-5 text-primary-500" />
                                @elseif($selectedEntity['type'] === 'section')
                                    <x-heroicon-m-squares-2x2 class="w-5 h-5 text-primary-500" />
                                @else
                                    <x-heroicon-m-puzzle-piece class="w-5 h-5 text-primary-500" />
                                @endif
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $isEditingInline ? 'Edit: ' : '' }}{{ $selectedEntity['name'] }}
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ $selectedEntity['type'] }}</p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400">ID: {{ $selectedEntity['id'] }}</span>
                        </div>
                    </div>

                    {{-- STATS BAR - At-a-Glance Summary --}}
                    @if(!empty($selectedEntity['stats']))
                        <div class="px-4 py-2 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-750 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between gap-2">
                                @if(isset($selectedEntity['stats']['cabinets']))
                                    <div class="flex-1 text-center px-2 py-1.5 bg-white dark:bg-gray-700 rounded-md shadow-sm border border-gray-200 dark:border-gray-600">
                                        <div class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ $selectedEntity['stats']['cabinets'] }}</div>
                                        <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400 font-medium">Cabinets</div>
                                    </div>
                                @endif
                                @if(isset($selectedEntity['stats']['linear_feet']))
                                    <div class="flex-1 text-center px-2 py-1.5 bg-white dark:bg-gray-700 rounded-md shadow-sm border border-gray-200 dark:border-gray-600">
                                        <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $selectedEntity['stats']['linear_feet'] }}'</div>
                                        <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400 font-medium">Linear Ft</div>
                                    </div>
                                @endif
                                @if(isset($selectedEntity['stats']['production_days']))
                                    <div class="flex-1 text-center px-2 py-1.5 bg-white dark:bg-gray-700 rounded-md shadow-sm border border-gray-200 dark:border-gray-600">
                                        <div class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ $selectedEntity['stats']['production_days'] }}</div>
                                        <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400 font-medium">Prod Days</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($isEditingInline)
                        {{-- INLINE EDIT FORM - With Auto-Save --}}
                        <div class="p-4 space-y-4"
                            x-data="{
                                isDirty: false,
                                autoSaveTimer: null,
                                lastSaved: null,
                                saving: false,
                                markDirty() {
                                    this.isDirty = true;
                                    clearTimeout(this.autoSaveTimer);
                                    this.autoSaveTimer = setTimeout(() => {
                                        this.autoSave();
                                    }, 2000);
                                },
                                async autoSave() {
                                    if (!this.isDirty) return;
                                    this.saving = true;
                                    await $wire.saveInlineEdit();
                                    this.isDirty = false;
                                    this.lastSaved = new Date().toLocaleTimeString();
                                    this.saving = false;
                                }
                            }"
                            x-on:input.debounce.500ms="markDirty()"
                        >
                            <div class="flex items-center justify-between bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-3 py-2 mb-4">
                                <div class="flex items-center gap-2 text-amber-800 dark:text-amber-200">
                                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                                    <span class="text-sm font-medium">Editing {{ ucfirst($selectedEntity['type']) }}</span>
                                </div>
                                {{-- Auto-save indicator --}}
                                <div class="text-xs">
                                    <span x-show="saving" class="text-blue-600 dark:text-blue-400 flex items-center gap-1">
                                        <svg class="animate-spin h-3 w-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        Saving...
                                    </span>
                                    <span x-show="!saving && isDirty" class="text-amber-600 dark:text-amber-400">Unsaved</span>
                                    <span x-show="!saving && !isDirty && lastSaved" class="text-green-600 dark:text-green-400" x-text="'Saved ' + lastSaved"></span>
                                </div>
                            </div>

                            {{-- Room Edit Form --}}
                            @if($selectedEntity['type'] === 'room')
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Room Name *</label>
                                            <input type="text" wire:model="inlineEditData.name"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., Kitchen">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Room Type</label>
                                            <select wire:model="inlineEditData.room_type"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="kitchen">Kitchen</option>
                                                <option value="bathroom">Bathroom</option>
                                                <option value="laundry">Laundry</option>
                                                <option value="pantry">Pantry</option>
                                                <option value="closet">Closet</option>
                                                <option value="mudroom">Mudroom</option>
                                                <option value="office">Office</option>
                                                <option value="bedroom">Bedroom</option>
                                                <option value="living_room">Living Room</option>
                                                <option value="dining_room">Dining Room</option>
                                                <option value="garage">Garage</option>
                                                <option value="basement">Basement</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Floor #</label>
                                            <input type="number" wire:model="inlineEditData.floor_number"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="0" max="10">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">PDF Page</label>
                                            <input type="number" wire:model="inlineEditData.pdf_page_number"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="1">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Order</label>
                                            <input type="number" wire:model="inlineEditData.sort_order"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="0">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">PDF Label</label>
                                            <input type="text" wire:model="inlineEditData.pdf_room_label"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., K1">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Detail #</label>
                                            <input type="text" wire:model="inlineEditData.pdf_detail_number"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., A1">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Cab Level</label>
                                            <input type="number" wire:model="inlineEditData.cabinet_level"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="1" max="5" placeholder="1-5">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Material</label>
                                            <select wire:model="inlineEditData.material_category"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="">Select...</option>
                                                @foreach($this->getMaterialCategoryOptions() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Finish</label>
                                            <select wire:model="inlineEditData.finish_option"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="">Select...</option>
                                                @foreach($this->getFinishOptions() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Quoted Price ($)</label>
                                        <input type="number" wire:model="inlineEditData.quoted_price"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            step="0.01" min="0" placeholder="0.00">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                        <textarea wire:model="inlineEditData.notes" rows="2"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            placeholder="Any additional notes..."></textarea>
                                    </div>
                                </div>

                            {{-- Location Edit Form --}}
                            @elseif($selectedEntity['type'] === 'location')
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                                            <input type="text" wire:model="inlineEditData.name"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., Sink Wall">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                            <select wire:model="inlineEditData.location_type"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="wall">Wall</option>
                                                <option value="island">Island</option>
                                                <option value="peninsula">Peninsula</option>
                                                <option value="corner">Corner</option>
                                                <option value="closet">Closet</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sequence</label>
                                            <input type="number" wire:model="inlineEditData.sequence"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sort</label>
                                            <input type="number" wire:model="inlineEditData.sort_order"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Cab Level</label>
                                            <input type="number" wire:model="inlineEditData.cabinet_level"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="1" max="5">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Elevation Reference</label>
                                        <input type="text" wire:model="inlineEditData.elevation_reference"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            placeholder="e.g., North Wall, Detail A">
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Material</label>
                                            <select wire:model="inlineEditData.material_category"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="">Select...</option>
                                                @foreach($this->getMaterialCategoryOptions() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Finish</label>
                                            <select wire:model="inlineEditData.finish_option"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="">Select...</option>
                                                @foreach($this->getFinishOptions() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                        <textarea wire:model="inlineEditData.notes" rows="2"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            placeholder="Any additional notes..."></textarea>
                                    </div>
                                </div>

                            {{-- Cabinet Run Edit Form --}}
                            @elseif($selectedEntity['type'] === 'run')
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                                            <input type="text" wire:model="inlineEditData.name"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., Base, Upper">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                            <select wire:model="inlineEditData.run_type"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="base">Base</option>
                                                <option value="wall">Wall/Upper</option>
                                                <option value="tall">Tall</option>
                                                <option value="vanity">Vanity</option>
                                                <option value="specialty">Specialty</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Total LF</label>
                                            <input type="number" wire:model="inlineEditData.total_linear_feet"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.01" min="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Start"</label>
                                            <input type="number" wire:model="inlineEditData.start_wall_measurement"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.01" min="0" placeholder="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">End"</label>
                                            <input type="number" wire:model="inlineEditData.end_wall_measurement"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Cab Level</label>
                                            <input type="number" wire:model="inlineEditData.cabinet_level"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="1" max="5">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sort</label>
                                            <input type="number" wire:model="inlineEditData.sort_order"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Hinges</label>
                                            <input type="number" wire:model="inlineEditData.hinges_count"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="0">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Material</label>
                                            <select wire:model="inlineEditData.material_category"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="">Select...</option>
                                                @foreach($this->getMaterialCategoryOptions() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Finish</label>
                                            <select wire:model="inlineEditData.finish_option"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="">Select...</option>
                                                @foreach($this->getFinishOptions() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                        <textarea wire:model="inlineEditData.notes" rows="2"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            placeholder="Any additional notes..."></textarea>
                                    </div>
                                </div>

                            {{-- Cabinet Edit Form --}}
                            @elseif($selectedEntity['type'] === 'cabinet')
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Cab #</label>
                                            <input type="text" wire:model="inlineEditData.cabinet_number"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., B1, W2">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                                            <input type="number" wire:model="inlineEditData.position_in_run"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="0">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-4 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">W"</label>
                                            <input type="number" wire:model="inlineEditData.length_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">H"</label>
                                            <input type="number" wire:model="inlineEditData.height_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">D"</label>
                                            <input type="number" wire:model="inlineEditData.depth_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Qty</label>
                                            <input type="number" wire:model="inlineEditData.quantity"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="1">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Wall Start"</label>
                                            <input type="number" wire:model="inlineEditData.wall_position_start_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.01" min="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Cab Level</label>
                                            <input type="number" wire:model="inlineEditData.cabinet_level"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="1" max="5">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Material</label>
                                            <select wire:model="inlineEditData.material_category"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="">Select...</option>
                                                @foreach($this->getMaterialCategoryOptions() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Finish</label>
                                            <select wire:model="inlineEditData.finish_option"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="">Select...</option>
                                                @foreach($this->getFinishOptions() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">$/LF</label>
                                            <input type="number" wire:model="inlineEditData.unit_price_per_lf"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.01" min="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Total $</label>
                                            <input type="number" wire:model="inlineEditData.total_price"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.01" min="0">
                                        </div>
                                    </div>

                                    {{-- Door/Drawer Configuration --}}
                                    <div class="border-t border-gray-200 dark:border-gray-600 pt-3 mt-3">
                                        <h5 class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Door/Drawer Config</h5>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Door Style</label>
                                                <select wire:model="inlineEditData.door_style"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                    @foreach($this->getDoorStyleOptions() as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Mounting</label>
                                                <select wire:model="inlineEditData.door_mounting"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                    @foreach($this->getDoorMountingOptions() as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 mt-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"># Doors</label>
                                                <input type="number" wire:model="inlineEditData.door_count"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                    min="0">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"># Drawers</label>
                                                <input type="number" wire:model="inlineEditData.drawer_count"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                    min="0">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Hardware Selection from Products --}}
                                    <div class="border-t border-gray-200 dark:border-gray-600 pt-3 mt-3">
                                        <h5 class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Hardware (from Products)</h5>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Hinges</label>
                                                <select wire:model="inlineEditData.hinge_product_id"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                    @foreach($this->getHingeProducts() as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Hinge Qty</label>
                                                <input type="number" wire:model="inlineEditData.hinge_quantity"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                    min="0">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 mt-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Drawer Slides</label>
                                                <select wire:model="inlineEditData.slide_product_id"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                    @foreach($this->getSlideProducts() as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Slide Qty</label>
                                                <input type="number" wire:model="inlineEditData.slide_quantity"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                    min="0">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Hardware Notes</label>
                                        <input type="text" wire:model="inlineEditData.hardware_notes"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            placeholder="e.g., soft-close hinges">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Shop Notes</label>
                                        <textarea wire:model="inlineEditData.shop_notes" rows="2"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            placeholder="Notes for shop..."></textarea>
                                    </div>
                                </div>

                            {{-- Section Edit Form --}}
                            @elseif($selectedEntity['type'] === 'section')
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Section Name *</label>
                                            <input type="text" wire:model="inlineEditData.name"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., Left Door, Drawer Bank">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                            <select wire:model="inlineEditData.section_type"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="door">Door</option>
                                                <option value="drawer">Drawer Bank</option>
                                                <option value="open">Open Shelf</option>
                                                <option value="appliance">Appliance Opening</option>
                                                <option value="pullout">Pull-out</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Width (in)</label>
                                            <input type="number" wire:model="inlineEditData.width_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Height (in)</label>
                                            <input type="number" wire:model="inlineEditData.height_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Pos From Left"</label>
                                            <input type="number" wire:model="inlineEditData.position_from_left_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125" min="0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Pos From Bottom"</label>
                                            <input type="number" wire:model="inlineEditData.position_from_bottom_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125" min="0">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                        <textarea wire:model="inlineEditData.notes" rows="2"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            placeholder="Any additional notes..."></textarea>
                                    </div>
                                </div>

                            {{-- Door Edit Form --}}
                            @elseif($selectedEntity['type'] === 'door')
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Door #</label>
                                            <input type="text" wire:model="inlineEditData.door_number"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., D1">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                            <input type="text" wire:model="inlineEditData.door_name"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., Left Door">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Width (in)</label>
                                            <input type="number" wire:model="inlineEditData.width_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Height (in)</label>
                                            <input type="number" wire:model="inlineEditData.height_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Hinge Side</label>
                                            <select wire:model="inlineEditData.hinge_side"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="left">Left</option>
                                                <option value="right">Right</option>
                                                <option value="top">Top</option>
                                                <option value="bottom">Bottom</option>
                                            </select>
                                        </div>
                                        <div class="flex items-center pt-5">
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="checkbox" wire:model="inlineEditData.has_glass"
                                                    class="rounded border-gray-300 dark:border-gray-600">
                                                <span class="text-gray-700 dark:text-gray-300">Has Glass</span>
                                            </label>
                                        </div>
                                    </div>
                                    {{-- Hardware --}}
                                    <div class="border-t border-gray-200 dark:border-gray-600 pt-3 mt-3">
                                        <h5 class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Hardware</h5>
                                        <div class="grid grid-cols-1 gap-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Hinges</label>
                                                <select wire:model="inlineEditData.hinge_product_id"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                    @foreach($this->getHingeProducts() as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Knob/Pull</label>
                                                <select wire:model="inlineEditData.decorative_hardware_product_id"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                    @foreach($this->getDecorativeHardwareProducts() as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                        <textarea wire:model="inlineEditData.notes" rows="2"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            placeholder="Any additional notes..."></textarea>
                                    </div>
                                </div>

                            {{-- Drawer Edit Form --}}
                            @elseif($selectedEntity['type'] === 'drawer')
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Drawer #</label>
                                            <input type="text" wire:model="inlineEditData.drawer_number"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., DR1">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                            <input type="text" wire:model="inlineEditData.drawer_name"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., Top Drawer">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Front W"</label>
                                            <input type="number" wire:model="inlineEditData.front_width_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Front H"</label>
                                            <input type="number" wire:model="inlineEditData.front_height_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                                            <input type="number" wire:model="inlineEditData.drawer_position"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="1">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Box W"</label>
                                            <input type="number" wire:model="inlineEditData.drawer_box_width_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Box H"</label>
                                            <input type="number" wire:model="inlineEditData.drawer_box_height_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Box D"</label>
                                            <input type="number" wire:model="inlineEditData.drawer_box_depth_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                    </div>
                                    {{-- Hardware --}}
                                    <div class="border-t border-gray-200 dark:border-gray-600 pt-3 mt-3">
                                        <h5 class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Hardware</h5>
                                        <div class="grid grid-cols-1 gap-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Slides</label>
                                                <select wire:model="inlineEditData.slide_product_id"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                    @foreach($this->getSlideProducts() as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Knob/Pull</label>
                                                <select wire:model="inlineEditData.decorative_hardware_product_id"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                    @foreach($this->getDecorativeHardwareProducts() as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="flex items-center mt-2">
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="checkbox" wire:model="inlineEditData.soft_close"
                                                    class="rounded border-gray-300 dark:border-gray-600">
                                                <span class="text-gray-700 dark:text-gray-300">Soft Close</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                        <textarea wire:model="inlineEditData.notes" rows="2"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            placeholder="Any additional notes..."></textarea>
                                    </div>
                                </div>

                            {{-- Shelf Edit Form --}}
                            @elseif($selectedEntity['type'] === 'shelf')
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Shelf #</label>
                                            <input type="text" wire:model="inlineEditData.shelf_number"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., S1">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                            <input type="text" wire:model="inlineEditData.shelf_name"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., Top Shelf">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Width"</label>
                                            <input type="number" wire:model="inlineEditData.width_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Depth"</label>
                                            <input type="number" wire:model="inlineEditData.depth_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Thick"</label>
                                            <input type="number" wire:model="inlineEditData.thickness_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                            <select wire:model="inlineEditData.shelf_type"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="adjustable">Adjustable</option>
                                                <option value="fixed">Fixed</option>
                                                <option value="roll_out">Roll-Out</option>
                                                <option value="floating">Floating</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Material</label>
                                            <select wire:model="inlineEditData.material"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="plywood">Plywood</option>
                                                <option value="melamine">Melamine</option>
                                                <option value="solid_wood">Solid Wood</option>
                                                <option value="glass">Glass</option>
                                                <option value="wire">Wire</option>
                                            </select>
                                        </div>
                                    </div>
                                    {{-- Slides for roll-out --}}
                                    <div class="border-t border-gray-200 dark:border-gray-600 pt-3 mt-3">
                                        <h5 class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Hardware (for Roll-Out)</h5>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Slides</label>
                                            <select wire:model="inlineEditData.slide_product_id"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                @foreach($this->getSlideProducts() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                        <textarea wire:model="inlineEditData.notes" rows="2"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            placeholder="Any additional notes..."></textarea>
                                    </div>
                                </div>

                            {{-- Pullout Edit Form --}}
                            @elseif($selectedEntity['type'] === 'pullout')
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Pullout #</label>
                                            <input type="text" wire:model="inlineEditData.pullout_number"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., PO1">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                            <input type="text" wire:model="inlineEditData.pullout_name"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., Trash Pullout">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                            <select wire:model="inlineEditData.pullout_type"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                <option value="roll_out_tray">Roll-Out Tray</option>
                                                <option value="trash">Trash</option>
                                                <option value="recycling">Recycling</option>
                                                <option value="lazy_susan">Lazy Susan</option>
                                                <option value="spice">Spice Rack</option>
                                                <option value="mixer_lift">Mixer Lift</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Qty</label>
                                            <input type="number" wire:model="inlineEditData.quantity"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                min="1">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Width"</label>
                                            <input type="number" wire:model="inlineEditData.width_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Height"</label>
                                            <input type="number" wire:model="inlineEditData.height_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Depth"</label>
                                            <input type="number" wire:model="inlineEditData.depth_inches"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                step="0.125">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Manufacturer</label>
                                            <input type="text" wire:model="inlineEditData.manufacturer"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., Rev-A-Shelf">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Model #</label>
                                            <input type="text" wire:model="inlineEditData.model_number"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="e.g., 4WCSC-2150">
                                        </div>
                                    </div>
                                    {{-- Hardware --}}
                                    <div class="border-t border-gray-200 dark:border-gray-600 pt-3 mt-3">
                                        <h5 class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wider">Hardware</h5>
                                        <div class="grid grid-cols-1 gap-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Pullout Product</label>
                                                <select wire:model="inlineEditData.product_id"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                    @foreach($this->getPulloutProducts() as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Slides</label>
                                                <select wire:model="inlineEditData.slide_product_id"
                                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                                                    @foreach($this->getSlideProducts() as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="flex items-center mt-2">
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="checkbox" wire:model="inlineEditData.soft_close"
                                                    class="rounded border-gray-300 dark:border-gray-600">
                                                <span class="text-gray-700 dark:text-gray-300">Soft Close</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                        <textarea wire:model="inlineEditData.notes" rows="2"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            placeholder="Any additional notes..."></textarea>
                                    </div>
                                </div>
                            @endif

                            {{-- Inline Edit Actions --}}
                            <div class="flex gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <x-filament::button
                                    x-on:click="autoSave()"
                                    color="primary"
                                    icon="heroicon-m-check"
                                    class="flex-1"
                                    size="sm"
                                >
                                    <span x-show="!saving">Save</span>
                                    <span x-show="saving">Saving...</span>
                                </x-filament::button>
                                <x-filament::button
                                    wire:click="cancelInlineEdit"
                                    color="gray"
                                    icon="heroicon-m-x-mark"
                                    size="sm"
                                >
                                    Done
                                </x-filament::button>
                            </div>
                        </div>
                    @else
                        {{-- READ-ONLY VIEW --}}
                        <div class="p-4 space-y-4">
                            {{-- Database Fields --}}
                            <div class="space-y-3">
                                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Database Fields</h4>

                                @foreach($selectedEntity['fields'] ?? [] as $field => $value)
                                    <div class="flex justify-between items-start py-2 border-b border-gray-100 dark:border-gray-800">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ Str::title(str_replace('_', ' ', $field)) }}</span>
                                        <span class="text-sm text-gray-900 dark:text-white text-right max-w-[60%] truncate" title="{{ $value }}">
                                            @if(is_null($value))
                                                <span class="text-gray-400 italic">null</span>
                                            @elseif(is_bool($value))
                                                <span class="{{ $value ? 'text-green-600' : 'text-red-600' }}">{{ $value ? 'Yes' : 'No' }}</span>
                                            @else
                                                {{ $value }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Children Summary --}}
                            @if(!empty($selectedEntity['children']['count']))
                                <div class="mt-4 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Children</h4>
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $selectedEntity['children']['count'] }}</span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedEntity['children']['label'] ?? 'items' }}</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Quick Edit Actions --}}
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Actions</h4>
                                <div class="flex flex-wrap gap-2">
                                    <x-filament::button
                                        size="sm"
                                        color="gray"
                                        icon="heroicon-m-pencil-square"
                                        wire:click="editHighlightedEntity"
                                    >
                                        Edit
                                    </x-filament::button>
                                    <x-filament::button
                                        size="sm"
                                        color="danger"
                                        icon="heroicon-m-trash"
                                        wire:click="deleteHighlightedEntity"
                                        wire:confirm="Are you sure you want to delete '{{ $selectedEntity['name'] }}'? This will also delete all child entities."
                                    >
                                        Delete
                                    </x-filament::button>
                                </div>
                            </div>

                            {{-- Timestamps --}}
                            @if(!empty($selectedEntity['created_at']) || !empty($selectedEntity['updated_at']))
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-400 space-y-1">
                                    @if(!empty($selectedEntity['created_at']))
                                        <p>Created: {{ $selectedEntity['created_at'] }}</p>
                                    @endif
                                    @if(!empty($selectedEntity['updated_at']))
                                        <p>Updated: {{ $selectedEntity['updated_at'] }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                @else
                    {{-- No Selection State --}}
                    <div class="flex flex-col items-center justify-center p-6 text-center">
                        <x-heroicon-o-cursor-arrow-rays class="w-8 h-8 text-gray-300 dark:text-gray-600 mb-2" />
                        <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">No Entity Selected</h3>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 max-w-[180px]">
                            Click an item to view details
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex items-center justify-end w-full gap-3">
                <x-filament::button
                    wire:click="closeEditDetailsModal"
                    color="gray"
                >
                    Cancel
                </x-filament::button>
                <x-filament::button
                    wire:click="submitEditDetails"
                    color="primary"
                >
                    Save Details
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>

    {{-- Entity Creation Modal (Create Only - Edit uses inline panel) --}}
    <x-filament::modal
        id="entity-crud-modal"
        slide-over
        width="lg"
    >
        <x-slot name="heading">
            {{ $this->getEntityModalHeading() }}
        </x-slot>

        <div class="space-y-4">
            {{-- Context Breadcrumb - Shows what page the user is viewing --}}
            @if($entityMode === 'create' && !empty($editDetailsData['page_label']))
                <div class="flex items-center gap-2 p-3 rounded-lg bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700">
                    <x-heroicon-o-document-text class="w-4 h-4 text-primary-500 flex-shrink-0" />
                    <div class="text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Currently viewing:</span>
                        <span class="font-medium text-primary-700 dark:text-primary-300">{{ $editDetailsData['page_label'] }}</span>
                    </div>
                </div>
            @endif

            {{-- Room Form --}}
            @if($entityType === 'room')
                <div class="space-y-4">
                    {{-- Duplicate Room Warning --}}
                    @php
                        $similarRooms = $this->findSimilarRooms($entityFormData['name'] ?? '');
                    @endphp
                    @if(count($similarRooms) > 0)
                        <div class="flex items-start gap-3 p-3 rounded-lg bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-700">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500 flex-shrink-0 mt-0.5" />
                            <div class="flex-1">
                                <p class="text-sm font-medium text-warning-700 dark:text-warning-300">
                                    Similar room exists
                                </p>
                                <p class="text-xs text-warning-600 dark:text-warning-400 mt-0.5">
                                    @foreach($similarRooms as $room)
                                        "{{ $room['name'] }}"{{ !$loop->last ? ', ' : '' }}
                                    @endforeach
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Did you mean to add a location to an existing room?
                                </p>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Room Name <span class="text-danger-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="entityFormData.name"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="e.g., Kitchen, Master Bathroom"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Room Type
                            </label>
                            <select
                                wire:model="entityFormData.room_type"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            >
                                @foreach($this->getRoomTypeOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Floor Number
                            </label>
                            <input
                                type="number"
                                wire:model="entityFormData.floor_number"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                min="0"
                                max="99"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Notes
                        </label>
                        <textarea
                            wire:model="entityFormData.notes"
                            rows="2"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="Any additional notes..."
                        ></textarea>
                    </div>
                </div>
            @endif

            {{-- Room Location Form --}}
            @if($entityType === 'room_location')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Location Name <span class="text-danger-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="entityFormData.name"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="e.g., Sink Wall, Island, Pantry Alcove"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Location Type
                            </label>
                            <select
                                wire:model="entityFormData.location_type"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            >
                                @foreach($this->getLocationTypeOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Width (inches)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.overall_width_inches"
                                wire:change="updateMeasurementField('overall_width_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 120, 10', 120 1/2"
                            />
                            <p class="text-xs text-gray-400 mt-0.5">Supports fractions & feet</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Notes
                        </label>
                        <textarea
                            wire:model="entityFormData.notes"
                            rows="2"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                        ></textarea>
                    </div>
                </div>
            @endif

            {{-- Cabinet Run Form --}}
            @if($entityType === 'cabinet_run')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Run Name <span class="text-danger-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="entityFormData.name"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="e.g., Base Run 1, Upper Cabinets"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Run Type
                            </label>
                            <select
                                wire:model="entityFormData.run_type"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            >
                                @foreach($this->getRunTypeOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Linear Feet
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.linear_feet"
                                wire:change="updateMeasurementField('linear_feet', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 12, 12.5, 12 1/2"
                            />
                            <p class="text-xs text-gray-400 mt-0.5">Leave blank to auto-calc</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Cabinet Form --}}
            @if($entityType === 'cabinet')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Cabinet Name
                        </label>
                        <input
                            type="text"
                            wire:model="entityFormData.name"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="e.g., B24, W3012, SB36"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Cabinet Type
                            </label>
                            <select
                                wire:model="entityFormData.cabinet_type"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            >
                                @foreach($this->getCabinetTypeOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Quantity
                            </label>
                            <input
                                type="number"
                                wire:model="entityFormData.quantity"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                min="1"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Width (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.length_inches"
                                wire:change="updateMeasurementField('length_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 24"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Depth (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.depth_inches"
                                wire:change="updateMeasurementField('depth_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 24"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Height (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.height_inches"
                                wire:change="updateMeasurementField('height_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 30"
                            />
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 -mt-2">Supports fractions (12 1/2) and feet (2')</p>

                    {{-- Material & Finish Section --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <x-heroicon-o-paint-brush class="w-4 h-4" />
                            Material & Finish
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Material Category
                                </label>
                                <select
                                    wire:model="entityFormData.material_category"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    <option value="">-- Select Material --</option>
                                    @foreach($this->getMaterialCategoryOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Finish Option
                                </label>
                                <select
                                    wire:model="entityFormData.finish_option"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    <option value="">-- Select Finish --</option>
                                    @foreach($this->getFinishOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Door/Drawer Configuration Section --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <x-heroicon-o-rectangle-group class="w-4 h-4" />
                            Door & Drawer Configuration
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Door Style
                                </label>
                                <select
                                    wire:model="entityFormData.door_style"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getDoorStyleOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Door Mounting
                                </label>
                                <select
                                    wire:model="entityFormData.door_mounting"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getDoorMountingOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Door Count
                                </label>
                                <input
                                    type="number"
                                    wire:model="entityFormData.door_count"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    min="0"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Drawer Count
                                </label>
                                <input
                                    type="number"
                                    wire:model="entityFormData.drawer_count"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    min="0"
                                />
                            </div>
                        </div>
                    </div>

                    {{-- Hardware Selection Section --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <x-heroicon-o-wrench-screwdriver class="w-4 h-4" />
                            Hardware Selection
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Hinge Selection --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Hinge Type
                                </label>
                                <select
                                    wire:model="entityFormData.hinge_product_id"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getHingeProducts() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Hinge Qty
                                </label>
                                <input
                                    type="number"
                                    wire:model="entityFormData.hinge_quantity"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    min="0"
                                />
                            </div>
                            {{-- Slide Selection --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Drawer Slide
                                </label>
                                <select
                                    wire:model="entityFormData.slide_product_id"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getSlideProducts() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Slide Qty (pairs)
                                </label>
                                <input
                                    type="number"
                                    wire:model="entityFormData.slide_quantity"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    min="0"
                                />
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">Hardware from inventory will be reserved when project moves to sourcing.</p>
                    </div>
                </div>
            @endif

            {{-- Section Form --}}
            @if($entityType === 'section')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Section Name <span class="text-danger-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="entityFormData.name"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            placeholder="e.g., Left Door, Drawer Bank"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Section Type
                        </label>
                        <select
                            wire:model="entityFormData.section_type"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                        >
                            <option value="door">Door</option>
                            <option value="drawer">Drawer Bank</option>
                            <option value="open">Open Shelf</option>
                            <option value="appliance">Appliance Opening</option>
                            <option value="pullout">Pull-out</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Width (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.width_inches"
                                wire:change="updateMeasurementField('width_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 15"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Height (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.height_inches"
                                wire:change="updateMeasurementField('height_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 30"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Notes
                        </label>
                        <textarea
                            wire:model="entityFormData.notes"
                            rows="2"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                        ></textarea>
                    </div>
                </div>
            @endif

            {{-- Door Form --}}
            @if($entityType === 'door')
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Door Number
                            </label>
                            <input
                                type="text"
                                wire:model="entityFormData.door_number"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., D1, Left"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Door Name
                            </label>
                            <input
                                type="text"
                                wire:model="entityFormData.door_name"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., Left Door"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Width (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.width_inches"
                                wire:change="updateMeasurementField('width_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 15"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Height (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.height_inches"
                                wire:change="updateMeasurementField('height_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 30"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Hinge Side
                            </label>
                            <select
                                wire:model="entityFormData.hinge_side"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            >
                                <option value="left">Left</option>
                                <option value="right">Right</option>
                                <option value="top">Top (Flip-up)</option>
                                <option value="bottom">Bottom (Tip-out)</option>
                            </select>
                        </div>
                        <div class="flex items-center pt-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model="entityFormData.has_glass"
                                    class="rounded border-gray-300 text-primary-600"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Has Glass Insert</span>
                            </label>
                        </div>
                    </div>

                    {{-- Hardware Section --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <x-heroicon-o-wrench-screwdriver class="w-4 h-4" />
                            Hardware (from Products)
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Hinge Type
                                </label>
                                <select
                                    wire:model="entityFormData.hinge_product_id"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getHingeProducts() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Decorative Hardware
                                </label>
                                <select
                                    wire:model="entityFormData.decorative_hardware_product_id"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getDecorativeHardwareProducts() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Notes
                        </label>
                        <textarea
                            wire:model="entityFormData.notes"
                            rows="2"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                        ></textarea>
                    </div>
                </div>
            @endif

            {{-- Drawer Form --}}
            @if($entityType === 'drawer')
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Drawer Number
                            </label>
                            <input
                                type="text"
                                wire:model="entityFormData.drawer_number"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 1, Top"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Drawer Name
                            </label>
                            <input
                                type="text"
                                wire:model="entityFormData.drawer_name"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., Top Drawer, Utensil Drawer"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Front Width (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.front_width_inches"
                                wire:change="updateMeasurementField('front_width_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 24"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Front Height (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.front_height_inches"
                                wire:change="updateMeasurementField('front_height_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 6"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Position
                            </label>
                            <input
                                type="number"
                                wire:model="entityFormData.drawer_position"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                min="1"
                                placeholder="1 = top"
                            />
                        </div>
                    </div>

                    {{-- Drawer Box Section --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <x-heroicon-o-cube class="w-4 h-4" />
                            Drawer Box
                        </h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Box Width (in)
                                </label>
                                <input
                                    type="text"
                                    wire:model.blur="entityFormData.box_width_inches"
                                    wire:change="updateMeasurementField('box_width_inches', $event.target.value)"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="Auto-calc"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Box Height (in)
                                </label>
                                <input
                                    type="text"
                                    wire:model.blur="entityFormData.box_height_inches"
                                    wire:change="updateMeasurementField('box_height_inches', $event.target.value)"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., 4"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Box Depth (in)
                                </label>
                                <input
                                    type="text"
                                    wire:model.blur="entityFormData.box_depth_inches"
                                    wire:change="updateMeasurementField('box_depth_inches', $event.target.value)"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    placeholder="e.g., 21"
                                />
                            </div>
                        </div>
                    </div>

                    {{-- Hardware Section --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <x-heroicon-o-wrench-screwdriver class="w-4 h-4" />
                            Hardware (from Products)
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Drawer Slides
                                </label>
                                <select
                                    wire:model="entityFormData.slide_product_id"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getSlideProducts() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Decorative Hardware
                                </label>
                                <select
                                    wire:model="entityFormData.decorative_hardware_product_id"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getDecorativeHardwareProducts() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model="entityFormData.soft_close"
                                    class="rounded border-gray-300 text-primary-600"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Soft Close</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Notes
                        </label>
                        <textarea
                            wire:model="entityFormData.notes"
                            rows="2"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                        ></textarea>
                    </div>
                </div>
            @endif

            {{-- Shelf Form --}}
            @if($entityType === 'shelf')
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Shelf Number
                            </label>
                            <input
                                type="text"
                                wire:model="entityFormData.shelf_number"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 1, S1"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Shelf Name
                            </label>
                            <input
                                type="text"
                                wire:model="entityFormData.shelf_name"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., Middle Shelf"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Width (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.width_inches"
                                wire:change="updateMeasurementField('width_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 22"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Depth (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.depth_inches"
                                wire:change="updateMeasurementField('depth_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 11"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Thickness (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.thickness_inches"
                                wire:change="updateMeasurementField('thickness_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 0.75"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Shelf Type
                            </label>
                            <select
                                wire:model="entityFormData.shelf_type"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            >
                                <option value="adjustable">Adjustable</option>
                                <option value="fixed">Fixed</option>
                                <option value="rollout">Roll-out</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Material
                            </label>
                            <select
                                wire:model="entityFormData.material"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            >
                                <option value="plywood">Plywood</option>
                                <option value="melamine">Melamine</option>
                                <option value="solid_wood">Solid Wood</option>
                                <option value="glass">Glass</option>
                            </select>
                        </div>
                    </div>

                    {{-- Hardware for Roll-out Shelves --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <x-heroicon-o-wrench-screwdriver class="w-4 h-4" />
                            Hardware (for Roll-out Shelves)
                        </h4>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Slides (if roll-out)
                            </label>
                            <select
                                wire:model="entityFormData.slide_product_id"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            >
                                @foreach($this->getSlideProducts() as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Notes
                        </label>
                        <textarea
                            wire:model="entityFormData.notes"
                            rows="2"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                        ></textarea>
                    </div>
                </div>
            @endif

            {{-- Pullout Form --}}
            @if($entityType === 'pullout')
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Pullout Number
                            </label>
                            <input
                                type="text"
                                wire:model="entityFormData.pullout_number"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., P1"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Pullout Name
                            </label>
                            <input
                                type="text"
                                wire:model="entityFormData.pullout_name"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., Trash Pullout"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Pullout Type
                        </label>
                        <select
                            wire:model="entityFormData.pullout_type"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                        >
                            <option value="trash">Trash Pullout</option>
                            <option value="recycling">Recycling Pullout</option>
                            <option value="spice_rack">Spice Rack</option>
                            <option value="tray_divider">Tray Divider</option>
                            <option value="cutting_board">Cutting Board</option>
                            <option value="mixer_lift">Mixer Lift</option>
                            <option value="blind_corner">Blind Corner Pullout</option>
                            <option value="lazy_susan">Lazy Susan</option>
                            <option value="roll_out_tray">Roll-Out Tray</option>
                            <option value="pantry_pullout">Pantry Pullout</option>
                            <option value="utensil_divider">Utensil Divider</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Manufacturer
                            </label>
                            <input
                                type="text"
                                wire:model="entityFormData.manufacturer"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., Rev-A-Shelf"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Model Number
                            </label>
                            <input
                                type="text"
                                wire:model="entityFormData.model_number"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                placeholder="e.g., 4WCSC2135DM2"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Width (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.width_inches"
                                wire:change="updateMeasurementField('width_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Height (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.height_inches"
                                wire:change="updateMeasurementField('height_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Depth (in)
                            </label>
                            <input
                                type="text"
                                wire:model.blur="entityFormData.depth_inches"
                                wire:change="updateMeasurementField('depth_inches', $event.target.value)"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                            />
                        </div>
                    </div>

                    {{-- Hardware Section --}}
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <x-heroicon-o-wrench-screwdriver class="w-4 h-4" />
                            Hardware (from Products)
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Pullout Product
                                </label>
                                <select
                                    wire:model="entityFormData.product_id"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getPulloutProducts() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Slides
                                </label>
                                <select
                                    wire:model="entityFormData.slide_product_id"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                >
                                    @foreach($this->getSlideProducts() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Quantity
                                </label>
                                <input
                                    type="number"
                                    wire:model="entityFormData.quantity"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                    min="1"
                                />
                            </div>
                            <div class="flex items-center pt-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model="entityFormData.soft_close"
                                        class="rounded border-gray-300 text-primary-600"
                                    />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Soft Close</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Notes
                        </label>
                        <textarea
                            wire:model="entityFormData.notes"
                            rows="2"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                        ></textarea>
                    </div>
                </div>
            @endif
        </div>

        <x-slot name="footer">
            <div class="flex items-center justify-end gap-2 w-full">
                <x-filament::button
                    wire:click="closeEntityModal"
                    color="gray"
                    size="sm"
                >
                    Cancel
                </x-filament::button>

                <x-filament::button
                    wire:click="saveEntityAndContinue"
                    color="primary"
                    size="sm"
                    icon="heroicon-o-plus"
                >
                    Add & Continue
                </x-filament::button>

                <x-filament::button
                    wire:click="saveEntity"
                    color="success"
                    size="sm"
                >
                    Add & Close
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
