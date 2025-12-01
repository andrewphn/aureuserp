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
                        // Initialize all rooms as expanded using numeric indices
                        @foreach($this->data['rooms'] ?? [] as $idx => $r)
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

                @php
                    $rooms = $this->data['rooms'] ?? [];
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

                <div class="p-2">
                    {{-- Folder Tree Container --}}
                    <div class="max-h-72 overflow-y-auto font-mono text-xs" style="font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Monaco, Consolas, monospace;">
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
                                    class="flex items-center py-1 px-1 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer group"
                                    @click="toggleRoom('{{ $roomKey }}')"
                                >
                                    {{-- Tree line prefix --}}
                                    <span class="text-gray-300 dark:text-gray-600 w-4 flex-shrink-0">
                                        {{ $isLastRoom ? '└' : '├' }}
                                    </span>

                                    {{-- Expand/collapse chevron --}}
                                    <span class="w-4 flex-shrink-0 text-gray-400">
                                        <template x-if="expandedRooms['{{ $roomKey }}']">
                                            <x-heroicon-s-chevron-down class="w-3 h-3" />
                                        </template>
                                        <template x-if="!expandedRooms['{{ $roomKey }}']">
                                            <x-heroicon-s-chevron-right class="w-3 h-3" />
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
                                    <span class="ml-1.5 text-gray-800 dark:text-gray-200 truncate flex-1" title="{{ $roomName }}">
                                        {{ Str::limit($roomName, 18) }}
                                    </span>

                                    {{-- Room LF total --}}
                                    <span class="ml-auto text-gray-400 dark:text-gray-500 tabular-nums">
                                        {{ number_format($roomLf, 1) }}
                                    </span>
                                </div>

                                {{-- Cabinet Runs (Files) --}}
                                <div x-show="expandedRooms['{{ $roomKey }}']" x-collapse>
                                    @forelse($roomRuns as $runIndex => $run)
                                        @php
                                            $runName = $run['run_name'] ?? 'Run ' . ($runIndex + 1);
                                            $runLf = (float)($run['linear_feet'] ?? 0);
                                            $runLevel = $run['cabinet_level'] ?? '2';
                                            $isLastRun = $runIndex === count($roomRuns) - 1;

                                            // Tier color coding
                                            $tierColors = [
                                                '1' => 'text-green-600 dark:text-green-400',
                                                '2' => 'text-blue-600 dark:text-blue-400',
                                                '3' => 'text-yellow-600 dark:text-yellow-400',
                                                '4' => 'text-orange-600 dark:text-orange-400',
                                                '5' => 'text-red-600 dark:text-red-400',
                                            ];
                                            $tierColor = $tierColors[$runLevel] ?? 'text-gray-500';
                                        @endphp

                                        <div class="flex items-center py-0.5 px-1 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 group">
                                            {{-- Indent for nested items --}}
                                            <span class="text-gray-300 dark:text-gray-600 w-4 flex-shrink-0">
                                                {{ $isLastRoom ? ' ' : '│' }}
                                            </span>
                                            <span class="text-gray-300 dark:text-gray-600 w-4 flex-shrink-0">
                                                {{ $isLastRun ? '└' : '├' }}
                                            </span>

                                            {{-- File icon --}}
                                            <x-heroicon-o-document-text class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />

                                            {{-- Run name --}}
                                            <span class="ml-1.5 text-gray-600 dark:text-gray-400 truncate" title="{{ $runName }}">
                                                {{ Str::limit($runName, 14) }}
                                            </span>

                                            {{-- Tier badge --}}
                                            <span class="ml-1 px-1 py-0 rounded text-[9px] font-bold {{ $tierColor }} bg-gray-100 dark:bg-gray-700">
                                                T{{ $runLevel }}
                                            </span>

                                            {{-- Linear feet --}}
                                            <span class="ml-auto text-gray-400 dark:text-gray-500 tabular-nums">
                                                {{ number_format($runLf, 1) }}
                                            </span>
                                        </div>
                                    @empty
                                        <div class="flex items-center py-0.5 px-1 text-gray-400 dark:text-gray-500 italic">
                                            <span class="w-4">{{ $isLastRoom ? ' ' : '│' }}</span>
                                            <span class="w-4">└</span>
                                            <span class="ml-1">(empty)</span>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-gray-400 dark:text-gray-500">
                                <x-heroicon-o-folder-plus class="w-10 h-10 mx-auto mb-2 opacity-40" />
                                <p class="text-sm">No rooms yet</p>
                                <p class="text-[10px] mt-1">Add rooms in Step 2 to build your proposal</p>
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
        width="lg"
    >
        <x-slot name="header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
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
            </h2>
        </x-slot>

        <div class="space-y-4">
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
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">City</label>
                                    <input type="text" wire:model="editDetailsData.cover_address_city"
                                        class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">State</label>
                                    <input type="text" wire:model="editDetailsData.cover_address_state"
                                        class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">ZIP</label>
                                    <input type="text" wire:model="editDetailsData.cover_address_zip"
                                        class="w-full mt-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm" />
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

        <x-slot name="footer">
            <div class="flex items-center justify-end gap-3">
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
</x-filament-panels::page>
