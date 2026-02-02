<x-filament-panels::page>
    {{-- Enable polling for real-time sync across operators --}}
    <div wire:poll.5s="poll">

    @php
        $parts = $this->record->parts->sortBy('sheet_number');
        $pendingParts = $parts->where('status', 'pending');
        $runningParts = $parts->where('status', 'running');
        $completeParts = $parts->where('status', 'complete');
        $errorParts = $parts->where('status', 'error');
        $nextPart = $pendingParts->first();
        $currentPart = $runningParts->first();
    @endphp

    {{-- Live Status Indicator --}}
    <div class="fixed bottom-4 right-4 z-50 flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 rounded-full shadow-lg border border-gray-200 dark:border-gray-700">
        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
        <span class="text-xs text-gray-600 dark:text-gray-400">Live</span>
        <span class="text-xs text-gray-400 dark:text-gray-500" wire:loading.remove>Updated {{ \Carbon\Carbon::parse($this->lastRefresh ?? now())->diffForHumans() }}</span>
        <span class="text-xs text-blue-500" wire:loading>Syncing...</span>
    </div>

    {{-- Compact Program Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            {{-- Left: Project & Material --}}
            <div class="flex items-center gap-6">
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Project</div>
                    <div class="font-semibold text-gray-900 dark:text-white">{{ $this->record->project->name ?? 'Unknown' }}</div>
                </div>
                <div class="h-8 w-px bg-gray-200 dark:bg-gray-700"></div>
                <div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-bold
                        @switch($this->record->material_code)
                            @case('FL') bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 @break
                            @case('PreFin') bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 @break
                            @case('RiftWOPly') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 @break
                            @case('MDF_RiftWO') bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300 @break
                            @case('Medex') bg-pink-100 text-pink-800 dark:bg-pink-900/50 dark:text-pink-300 @break
                            @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                        @endswitch
                    ">
                        {{ $this->record->material_code ?? 'Unknown' }}
                    </span>
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ $this->record->sheet_size ?? '48×96' }}</span>
                </div>
            </div>

            {{-- Right: Progress Stats --}}
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $completeParts->count() }}</div>
                        <div class="text-xs text-gray-500">Done</div>
                    </div>
                    <div class="text-2xl text-gray-300 dark:text-gray-600">/</div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $parts->count() }}</div>
                        <div class="text-xs text-gray-500">Total</div>
                    </div>
                </div>
                <div class="w-32 h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-gradient-to-r from-green-400 to-green-500 transition-all duration-500"
                        style="width: {{ $this->record->completion_percentage }}%"
                    ></div>
                </div>
                <div class="text-lg font-bold {{ $this->record->completion_percentage == 100 ? 'text-green-600' : 'text-gray-700 dark:text-gray-300' }}">
                    {{ number_format($this->record->completion_percentage, 0) }}%
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content: 3-Column Layout on Desktop --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

        {{-- Column 1: Current/Next Part Action Panel (Sticky on desktop) --}}
        <div class="lg:col-span-3 space-y-4">
            {{-- Currently Running --}}
            @if($currentPart)
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white relative overflow-hidden">
                    {{-- Animated background pulse --}}
                    <div class="absolute inset-0 bg-white/10 animate-pulse"></div>

                    <div class="relative">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2 text-blue-100 text-xs uppercase tracking-wider">
                                <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                                Now Cutting
                            </div>
                            {{-- Run timer --}}
                            @if($currentPart->run_at)
                                <div class="text-blue-100 text-sm font-mono" x-data="{ elapsed: '{{ now()->diffForHumans($currentPart->run_at, ['parts' => 1, 'short' => true]) }}' }" x-init="setInterval(() => { elapsed = '{{ now()->diffInMinutes($currentPart->run_at) }}m' }, 60000)">
                                    <x-heroicon-o-clock class="w-4 h-4 inline" />
                                    <span>{{ now()->diffForHumans($currentPart->run_at, ['parts' => 1, 'short' => true]) }}</span>
                                </div>
                            @endif
                        </div>

                        <h3 class="text-xl font-bold mb-1">{{ $currentPart->file_name }}</h3>
                        <div class="flex items-center gap-2 text-blue-100 text-sm mb-4">
                            @if($currentPart->sheet_number)
                                <span class="px-2 py-0.5 bg-white/20 rounded">Sheet {{ $currentPart->sheet_number }}</span>
                            @endif
                            @if($currentPart->operation_type)
                                <span>{{ ucfirst($currentPart->operation_type) }}</span>
                            @endif
                        </div>

                        {{-- Main action button with loading state --}}
                        <button
                            wire:click="completePart({{ $currentPart->id }})"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-wait"
                            wire:target="completePart"
                            class="w-full py-4 px-4 bg-white text-blue-600 font-bold rounded-lg hover:bg-blue-50 active:scale-[0.98] transition-all flex items-center justify-center gap-2 text-lg shadow-lg"
                        >
                            <x-heroicon-s-check-circle class="w-7 h-7" wire:loading.remove wire:target="completePart" />
                            <x-heroicon-o-arrow-path class="w-7 h-7 animate-spin" wire:loading wire:target="completePart" />
                            <span wire:loading.remove wire:target="completePart">Done - Next Part</span>
                            <span wire:loading wire:target="completePart">Saving...</span>
                        </button>

                        <div class="flex gap-2 mt-2">
                            @if($currentPart->hasVCarveVisualization())
                                <button
                                    wire:click="selectPart({{ $currentPart->id }})"
                                    class="flex-1 py-2 px-4 bg-blue-400/30 text-white font-medium rounded-lg hover:bg-blue-400/50 transition-colors flex items-center justify-center gap-2 text-sm"
                                >
                                    <x-heroicon-o-eye class="w-4 h-4" />
                                    Setup Sheet
                                </button>
                            @endif
                            <button
                                wire:click="errorPart({{ $currentPart->id }})"
                                wire:confirm="Mark this part as having an error? You can reset it later."
                                class="py-2 px-3 bg-red-500/30 text-white font-medium rounded-lg hover:bg-red-500/50 transition-colors"
                                title="Report Problem"
                            >
                                <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Up Next --}}
            @if($nextPart && !$currentPart)
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
                    <div class="text-green-100 text-xs uppercase tracking-wider mb-2">Ready to Cut</div>
                    <h3 class="text-xl font-bold mb-1">{{ $nextPart->file_name }}</h3>
                    <div class="flex items-center gap-2 text-green-100 text-sm mb-4">
                        @if($nextPart->sheet_number)
                            <span class="px-2 py-0.5 bg-white/20 rounded">Sheet {{ $nextPart->sheet_number }}</span>
                        @endif
                        @if($nextPart->operation_type)
                            <span>{{ ucfirst($nextPart->operation_type) }}</span>
                        @endif
                    </div>
                    <button
                        wire:click="startPart({{ $nextPart->id }})"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-wait"
                        wire:target="startPart"
                        class="w-full py-4 px-4 bg-white text-green-600 font-bold rounded-lg hover:bg-green-50 active:scale-[0.98] transition-all flex items-center justify-center gap-2 text-lg shadow-lg"
                    >
                        <x-heroicon-s-play class="w-7 h-7" wire:loading.remove wire:target="startPart" />
                        <x-heroicon-o-arrow-path class="w-7 h-7 animate-spin" wire:loading wire:target="startPart" />
                        <span wire:loading.remove wire:target="startPart">Start Cutting</span>
                        <span wire:loading wire:target="startPart">Starting...</span>
                    </button>
                    @if($nextPart->hasVCarveVisualization())
                        <button
                            wire:click="selectPart({{ $nextPart->id }})"
                            class="w-full mt-2 py-2 px-4 bg-green-400/30 text-white font-medium rounded-lg hover:bg-green-400/50 transition-colors flex items-center justify-center gap-2 text-sm"
                        >
                            <x-heroicon-o-eye class="w-4 h-4" />
                            Preview Setup Sheet
                        </button>
                    @endif
                </div>
            @elseif($nextPart && $currentPart)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider mb-2">Up Next</div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $nextPart->file_name }}</h3>
                    <div class="flex items-center gap-2 text-gray-500 text-sm mt-1">
                        @if($nextPart->sheet_number)
                            <span>Sheet {{ $nextPart->sheet_number }}</span>
                        @endif
                    </div>
                    @if($nextPart->hasVCarveVisualization())
                        <button
                            wire:click="selectPart({{ $nextPart->id }})"
                            class="mt-2 text-xs text-primary-600 dark:text-primary-400 hover:underline"
                        >
                            Preview setup sheet
                        </button>
                    @endif
                </div>
            @endif

            {{-- Error Parts Alert --}}
            @if($errorParts->count() > 0)
                <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-4">
                    <div class="flex items-center gap-2 text-red-700 dark:text-red-400 text-sm font-medium mb-2">
                        <x-heroicon-s-exclamation-triangle class="w-5 h-5" />
                        {{ $errorParts->count() }} Part{{ $errorParts->count() > 1 ? 's' : '' }} with Issues
                    </div>
                    <div class="space-y-1">
                        @foreach($errorParts->take(3) as $errorPart)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-red-600 dark:text-red-300">{{ $errorPart->file_name }}</span>
                                <button
                                    wire:click="resetPart({{ $errorPart->id }})"
                                    class="text-xs text-red-500 hover:text-red-700 underline"
                                >
                                    Reset
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- All Done State --}}
            @if($parts->count() > 0 && $pendingParts->isEmpty() && $runningParts->isEmpty())
                <div class="bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl shadow-lg p-5 text-white text-center">
                    <x-heroicon-s-check-badge class="w-16 h-16 mx-auto mb-3 opacity-90" />
                    <h3 class="text-xl font-bold">All Parts Complete!</h3>
                    <p class="text-green-100 text-sm mt-1">{{ $parts->count() }} parts finished</p>
                </div>
            @endif

            {{-- Quick Stats --}}
            @if($parts->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <h4 class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Parts by Sheet</h4>
                    <div class="space-y-2">
                        @foreach($parts->groupBy('sheet_number') as $sheetNum => $sheetParts)
                            @php
                                $sheetComplete = $sheetParts->where('status', 'complete')->count();
                                $sheetTotal = $sheetParts->count();
                                $sheetPct = $sheetTotal > 0 ? ($sheetComplete / $sheetTotal) * 100 : 0;
                            @endphp
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-16">Sheet {{ $sheetNum ?? '?' }}</span>
                                <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500 transition-all" style="width: {{ $sheetPct }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-10 text-right">{{ $sheetComplete }}/{{ $sheetTotal }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Column 2: Parts List --}}
        <div class="lg:col-span-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden h-full flex flex-col">
                <div class="flex-shrink-0 px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-queue-list class="w-5 h-5 text-gray-500" />
                        All Parts
                    </h3>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-gray-400">{{ $pendingParts->count() }} pending</span>
                        @if($runningParts->count() > 0)
                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/50 rounded text-blue-700 dark:text-blue-300">{{ $runningParts->count() }} running</span>
                        @endif
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700/50" style="max-height: calc(100vh - 280px);">
                    @forelse($parts as $part)
                        <div
                            wire:click="selectPart({{ $part->id }})"
                            wire:key="part-{{ $part->id }}"
                            class="p-3 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50 group
                                {{ $this->selectedPartId === $part->id ? 'bg-primary-50 dark:bg-primary-900/20 border-l-4 border-l-primary-500' : '' }}
                                {{ $part->status === 'running' ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}"
                        >
                            <div class="flex items-center gap-3">
                                {{-- Status Indicator --}}
                                <div class="flex-shrink-0">
                                    @switch($part->status)
                                        @case('complete')
                                            <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                                                <x-heroicon-s-check class="w-5 h-5 text-green-600 dark:text-green-400" />
                                            </div>
                                            @break
                                        @case('running')
                                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                                                <span class="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></span>
                                            </div>
                                            @break
                                        @default
                                            <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500 text-xs font-medium">
                                                {{ $part->sheet_number ?? '?' }}
                                            </div>
                                    @endswitch
                                </div>

                                {{-- Part Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900 dark:text-white truncate text-sm">
                                            {{ $part->file_name }}
                                        </span>
                                        @if($part->hasVCarveVisualization())
                                            <x-heroicon-s-eye class="w-3.5 h-3.5 text-primary-400 flex-shrink-0" />
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        @if($part->operation_type)
                                            {{ ucfirst($part->operation_type) }}
                                        @endif
                                    </div>
                                </div>

                                {{-- Quick Action --}}
                                <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                    @if($part->status === 'pending')
                                        <button
                                            wire:click.stop="startPart({{ $part->id }})"
                                            class="p-2 rounded-lg bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/50 dark:text-green-300"
                                            title="Start"
                                        >
                                            <x-heroicon-s-play class="w-4 h-4" />
                                        </button>
                                    @elseif($part->status === 'running')
                                        <button
                                            wire:click.stop="completePart({{ $part->id }})"
                                            class="p-2 rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/50 dark:text-blue-300"
                                            title="Complete"
                                        >
                                            <x-heroicon-s-check class="w-4 h-4" />
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <x-heroicon-o-document-plus class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                            <p class="text-gray-500 dark:text-gray-400 text-sm">No parts yet</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Import VCarve files to add parts</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Column 3: VCarve Viewer --}}
        <div class="lg:col-span-5">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" style="height: calc(100vh - 220px); min-height: 500px;">
                @if($this->selectedPart)
                    @include('webkul-project::filament.components.cnc-vcarve-viewer', [
                        'part' => $this->selectedPart,
                        'svgContent' => $this->selectedPart->vcarve_svg_content,
                        'htmlUrl' => $this->selectedPart->vcarve_html_drive_url,
                        'metadata' => $this->selectedPart->vcarve_metadata ?? [],
                    ])
                @else
                    <div class="flex flex-col items-center justify-center h-full p-6 text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                            <x-heroicon-o-document-magnifying-glass class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-600 dark:text-gray-400 mb-2">VCarve Setup Sheet</h3>
                        <p class="text-sm text-gray-400 dark:text-gray-500 max-w-xs mb-4">
                            Select a part from the list to view its toolpath visualization and verify machine setup.
                        </p>

                        @if($this->record->parts->where(fn($p) => $p->hasVCarveVisualization())->isEmpty())
                            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800 max-w-sm">
                                <div class="flex items-start gap-3">
                                    <x-heroicon-o-light-bulb class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
                                    <div class="text-left">
                                        <p class="text-sm font-medium text-amber-800 dark:text-amber-200">No setup sheets imported</p>
                                        <p class="text-xs text-amber-600 dark:text-amber-300 mt-1">
                                            Use the "Import VCarve" button in the header to upload HTML setup sheets from VCarve.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Utilization Stats (collapsible, shown after nesting recorded) --}}
    @if($this->record->utilization_percentage)
        <div class="mt-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Nesting Results</h4>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->record->sheets_actual ?? '-' }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Sheets Used</div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ number_format($this->record->utilization_percentage, 1) }}%</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Utilization</div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->record->sqft_actual ?? 0, 1) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">SqFt Used</div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="text-2xl font-bold text-amber-600">{{ number_format($this->record->waste_sqft ?? 0, 1) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">SqFt Waste</div>
                </div>
            </div>
        </div>
    @endif

    </div> {{-- End polling wrapper --}}

    {{-- Keyboard shortcuts helper (shown on focus) --}}
    <div class="hidden lg:block fixed bottom-4 left-4 text-xs text-gray-400 dark:text-gray-600">
        Press <kbd class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">Space</kbd> to complete current part
    </div>

    {{-- Global keyboard handler for quick actions --}}
    <script>
        document.addEventListener('keydown', function(e) {
            // Space to complete current running part
            if (e.code === 'Space' && !e.target.matches('input, textarea, button')) {
                const completeBtn = document.querySelector('[wire\\:click^="completePart"]');
                if (completeBtn) {
                    e.preventDefault();
                    completeBtn.click();
                }
            }
            // Enter to start next pending part
            if (e.code === 'Enter' && !e.target.matches('input, textarea, button')) {
                const startBtn = document.querySelector('[wire\\:click^="startPart"]');
                if (startBtn) {
                    e.preventDefault();
                    startBtn.click();
                }
            }
        });
    </script>

</x-filament-panels::page>
