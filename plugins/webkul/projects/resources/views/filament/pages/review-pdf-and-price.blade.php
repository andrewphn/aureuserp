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

            {{-- Entity Builder / Relationship Tree --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-square-3-stack-3d class="w-4 h-4 text-primary-500" />
                        Entity Builder
                    </h3>
                </div>
                <div class="p-3">
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

                    {{-- Summary Stats Row --}}
                    <div class="flex items-center justify-between gap-2 mb-3 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30 text-xs font-medium text-blue-700 dark:text-blue-300">
                                <x-heroicon-o-home class="w-3 h-3" />
                                {{ $totalRooms }}
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-purple-100 dark:bg-purple-900/30 text-xs font-medium text-purple-700 dark:text-purple-300">
                                <x-heroicon-o-rectangle-stack class="w-3 h-3" />
                                {{ $totalRuns }}
                            </span>
                        </div>
                        <span class="text-sm font-bold text-primary-600 dark:text-primary-400">
                            {{ number_format($totalLf, 1) }} LF
                        </span>
                    </div>

                    {{-- Relationship Tree --}}
                    <div class="space-y-2 max-h-64 overflow-y-auto text-xs">
                        @forelse($rooms as $roomIndex => $room)
                            @php
                                $roomName = $room['room_name'] ?? $room['room_type'] ?? 'Room ' . ($roomIndex + 1);
                                $roomRuns = $room['cabinet_runs'] ?? [];
                                $roomLf = collect($roomRuns)->sum(fn($run) => (float)($run['linear_feet'] ?? 0));
                            @endphp
                            <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-2">
                                {{-- Room Header --}}
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-1.5 font-medium text-gray-900 dark:text-white">
                                        <x-heroicon-o-home class="w-3.5 h-3.5 text-blue-500" />
                                        <span class="truncate max-w-[120px]">{{ $roomName }}</span>
                                    </div>
                                    <span class="text-gray-500 dark:text-gray-400">{{ number_format($roomLf, 1) }} LF</span>
                                </div>

                                {{-- Cabinet Runs under this room --}}
                                @if(count($roomRuns) > 0)
                                    <div class="ml-3 pl-2 border-l-2 border-gray-200 dark:border-gray-600 space-y-1">
                                        @foreach($roomRuns as $runIndex => $run)
                                            @php
                                                $runName = $run['run_name'] ?? 'Run ' . ($runIndex + 1);
                                                $runLf = (float)($run['linear_feet'] ?? 0);
                                                $runLevel = $run['cabinet_level'] ?? '2';
                                            @endphp
                                            <div class="flex items-center justify-between py-0.5">
                                                <div class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                                    <x-heroicon-o-rectangle-stack class="w-3 h-3 text-purple-400" />
                                                    <span class="truncate max-w-[100px]">{{ $runName }}</span>
                                                    <span class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-[10px] text-gray-500 dark:text-gray-400">
                                                        T{{ $runLevel }}
                                                    </span>
                                                </div>
                                                <span class="text-gray-400">{{ number_format($runLf, 1) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="ml-3 pl-2 border-l-2 border-gray-200 dark:border-gray-600 py-1 text-gray-400 italic">
                                        No cabinet runs yet
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-4 text-gray-400 dark:text-gray-500">
                                <x-heroicon-o-cube-transparent class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                <p>No entities yet</p>
                                <p class="text-[10px] mt-1">Add rooms in Step 2</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Tier Breakdown (if we have runs) --}}
                    @if(count($tierTotals) > 0)
                        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">By Tier</div>
                            {{-- Use flex-wrap instead of dynamic grid-cols for Tailwind compatibility --}}
                            <div class="flex flex-wrap gap-1">
                                @foreach($tierTotals as $tier => $lf)
                                    <div class="flex-1 min-w-[3.5rem] text-center p-1.5 rounded bg-gray-50 dark:bg-gray-700/50">
                                        <div class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ number_format($lf, 1) }}</div>
                                        <div class="text-[10px] text-gray-400">Tier {{ $tier }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

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
</x-filament-panels::page>
