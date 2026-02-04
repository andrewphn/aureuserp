<x-filament-panels::page>
    @php
        $todayStats = $this->getTodayStats();
        $capacityStats = $this->getCapacityStats();
    @endphp

    <div wire:poll.{{ $this->getPollingInterval() }}>
        {{-- Stats Overview --}}
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->stats['pending'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Pending Parts</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="text-3xl font-bold text-info-600">{{ $this->stats['running'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Running Now</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="text-3xl font-bold text-success-600">{{ $this->stats['complete_today'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Completed Today</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="text-3xl font-bold text-danger-600">{{ $this->stats['pending_material'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Pending Material</div>
            </div>

            {{-- Today's Board Feet --}}
            <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 rounded-lg p-4 shadow-sm border border-green-200 dark:border-green-700">
                <div class="text-3xl font-bold text-green-700 dark:text-green-400">{{ $todayStats['sheets_today'] }}</div>
                <div class="text-sm text-green-600 dark:text-green-500">Sheets Today</div>
                <div class="text-xs text-green-500 dark:text-green-400 mt-1">~{{ $todayStats['bf_today'] }} BF</div>
            </div>

            {{-- Daily Average --}}
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-lg p-4 shadow-sm border border-blue-200 dark:border-blue-700">
                <div class="text-3xl font-bold text-blue-700 dark:text-blue-400">{{ $capacityStats['average_sheets_per_day'] }}</div>
                <div class="text-sm text-blue-600 dark:text-blue-500">Avg/Day</div>
                <div class="text-xs text-blue-500 dark:text-blue-400 mt-1">~{{ $capacityStats['average_bf_per_day'] }} BF</div>
            </div>
        </div>

        {{-- Capacity Summary Banner --}}
        @if($capacityStats['working_days'] > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-3 flex items-center gap-2">
                    <x-heroicon-o-chart-bar class="w-4 h-4" />
                    30-Day Capacity Summary
                </h3>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Average</div>
                        <div class="font-bold text-gray-900 dark:text-white">{{ $capacityStats['average_sheets_per_day'] }}/day</div>
                        <div class="text-xs text-gray-500">~{{ $capacityStats['average_bf_per_day'] }} BF</div>
                    </div>
                    <div class="border-l border-r border-gray-200 dark:border-gray-700 px-4">
                        <div class="text-sm text-green-600 dark:text-green-400">Peak Day</div>
                        <div class="font-bold text-green-700 dark:text-green-400">{{ $capacityStats['peak_sheets'] }}/day</div>
                        <div class="text-xs text-gray-500">~{{ $capacityStats['peak_bf'] }} BF</div>
                    </div>
                    <div>
                        <div class="text-sm text-amber-600 dark:text-amber-400">Slow Day</div>
                        <div class="font-bold text-amber-700 dark:text-amber-400">{{ $capacityStats['slow_sheets'] }}/day</div>
                        <div class="text-xs text-gray-500">~{{ $capacityStats['slow_bf'] }} BF</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Currently Running --}}
        @if($this->runningParts->count() > 0)
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <x-heroicon-o-play class="w-5 h-5 text-info-500 animate-pulse" />
                    Currently Running
                </h2>
                <div class="grid gap-3">
                    @foreach($this->runningParts as $part)
                        <div class="bg-info-50 dark:bg-info-900/20 rounded-lg p-4 border-2 border-info-500">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="font-bold text-lg text-gray-900 dark:text-white">
                                        {{ $part->file_name }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1 flex-wrap">
                                        @if($part->cncProgram->project)
                                            <a href="{{ route('filament.admin.resources.project.projects.view', $part->cncProgram->project) }}"
                                               class="text-primary-600 hover:text-primary-800 dark:text-primary-400 hover:underline font-medium">
                                                {{ $part->cncProgram->project->name }}
                                            </a>
                                        @else
                                            <span>Unknown Project</span>
                                        @endif
                                        <span>&bull;</span>
                                        @if($part->cncProgram)
                                            <a href="{{ route('filament.admin.resources.project.cnc-programs.view', $part->cncProgram) }}"
                                               class="text-primary-600 hover:text-primary-800 dark:text-primary-400 hover:underline font-medium">
                                                {{ $part->cncProgram->name }}
                                            </a>
                                        @else
                                            <span>Unknown Program</span>
                                        @endif
                                        @if($part->sheet_number)
                                            <span>&bull; Sheet {{ $part->sheet_number }}</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Started: {{ $part->run_at?->diffForHumans() }}
                                        @if($part->operator)
                                            &bull; Operator: {{ $part->operator->name }}
                                        @endif
                                        @if($part->run_duration_minutes)
                                            &bull; Duration: {{ $part->run_duration_minutes }} min
                                        @endif
                                    </div>
                                </div>
                                <div class="flex gap-2 items-center">
                                    {{-- QA Summary Badge --}}
                                    @php
                                        $cutPartsTotal = $part->cutParts()->count();
                                        $cutPartsPassed = $cutPartsTotal > 0 ? $part->cutParts()->where('status', 'passed')->count() : 0;
                                        $cutPartsFailed = $cutPartsTotal > 0 ? $part->cutParts()->whereIn('status', ['failed', 'recut_needed', 'scrapped'])->count() : 0;
                                    @endphp
                                    @if($cutPartsTotal > 0)
                                        <div class="flex items-center gap-1 px-3 py-2 rounded-lg {{ $cutPartsFailed > 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : ($cutPartsPassed === $cutPartsTotal ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300') }}">
                                            <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                                            <span class="text-sm font-medium">{{ $cutPartsPassed }}/{{ $cutPartsTotal }}</span>
                                        </div>
                                    @endif

                                    <button
                                        wire:click="openPartsQaModal({{ $part->id }})"
                                        class="px-4 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold transition-colors"
                                        title="Parts QA"
                                    >
                                        <x-heroicon-o-squares-2x2 class="w-6 h-6" />
                                    </button>
                                    <a href="{{ route('filament.admin.resources.project.cnc-programs.view', $part->cncProgram) }}"
                                       class="px-4 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-semibold transition-colors"
                                       title="View Program">
                                        <x-heroicon-o-eye class="w-6 h-6" />
                                    </a>
                                    <button
                                        wire:click="completePart({{ $part->id }})"
                                        wire:loading.attr="disabled"
                                        class="px-6 py-3 bg-success-500 hover:bg-success-600 text-white rounded-lg font-semibold text-lg transition-colors min-w-[120px]"
                                    >
                                        <x-heroicon-o-check class="w-6 h-6 inline-block mr-1" />
                                        Complete
                                    </button>
                                    <button
                                        wire:click="markError({{ $part->id }})"
                                        wire:loading.attr="disabled"
                                        class="px-4 py-3 bg-danger-500 hover:bg-danger-600 text-white rounded-lg font-semibold transition-colors"
                                    >
                                        <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Pending Queue --}}
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <x-heroicon-o-queue-list class="w-5 h-5 text-gray-500" />
                Pending Queue
            </h2>

            @if($this->pendingParts->count() > 0)
                <div class="grid gap-2">
                    @foreach($this->pendingParts as $part)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700 hover:border-primary-500 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ $part->file_name }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1 flex-wrap">
                                        @if($part->cncProgram->project)
                                            <a href="{{ route('filament.admin.resources.project.projects.view', $part->cncProgram->project) }}"
                                               class="text-primary-600 hover:text-primary-800 dark:text-primary-400 hover:underline font-medium">
                                                {{ $part->cncProgram->project->name }}
                                            </a>
                                        @else
                                            <span>Unknown</span>
                                        @endif
                                        <span>&bull;</span>
                                        @if($part->cncProgram)
                                            <a href="{{ route('filament.admin.resources.project.cnc-programs.view', $part->cncProgram) }}"
                                               class="text-primary-600 hover:text-primary-800 dark:text-primary-400 hover:underline font-medium">
                                                {{ $part->cncProgram->name }}
                                            </a>
                                        @else
                                            <span>Unknown</span>
                                        @endif
                                        @if($part->sheet_number)
                                            <span>&bull; Sheet {{ $part->sheet_number }}</span>
                                        @endif
                                        @if($part->operation_type)
                                            <span>&bull; {{ ucfirst($part->operation_type) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex gap-2 items-center">
                                    {{-- QA Summary Badge --}}
                                    @php
                                        $cutPartsTotal = $part->cutParts()->count();
                                        $cutPartsPassed = $cutPartsTotal > 0 ? $part->cutParts()->where('status', 'passed')->count() : 0;
                                        $cutPartsFailed = $cutPartsTotal > 0 ? $part->cutParts()->whereIn('status', ['failed', 'recut_needed', 'scrapped'])->count() : 0;
                                    @endphp
                                    @if($cutPartsTotal > 0)
                                        <div class="flex items-center gap-1 px-2 py-1 rounded {{ $cutPartsFailed > 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : ($cutPartsPassed === $cutPartsTotal ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300') }}">
                                            <x-heroicon-o-squares-2x2 class="w-3 h-3" />
                                            <span class="text-xs font-medium">{{ $cutPartsPassed }}/{{ $cutPartsTotal }}</span>
                                        </div>
                                    @endif

                                    <button
                                        wire:click="openPartsQaModal({{ $part->id }})"
                                        class="px-3 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors"
                                        title="Parts QA"
                                    >
                                        <x-heroicon-o-squares-2x2 class="w-5 h-5" />
                                    </button>
                                    <a href="{{ route('filament.admin.resources.project.cnc-programs.view', $part->cncProgram) }}"
                                       class="px-3 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
                                       title="View Program">
                                        <x-heroicon-o-eye class="w-5 h-5" />
                                    </a>
                                    <button
                                        wire:click="startPart({{ $part->id }})"
                                        wire:loading.attr="disabled"
                                        class="px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-semibold text-lg transition-colors min-w-[100px]"
                                    >
                                        <x-heroicon-o-play class="w-5 h-5 inline-block mr-1" />
                                        Start
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-lg p-8 text-center border border-gray-200 dark:border-gray-700">
                    <x-heroicon-o-check-circle class="w-12 h-12 mx-auto text-success-500 mb-3" />
                    <p class="text-gray-500 dark:text-gray-400">All parts are complete or in progress!</p>
                </div>
            @endif
        </div>

        {{-- Pending Material --}}
        @if($this->pendingMaterialParts->count() > 0)
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-danger-500" />
                    Waiting for Material
                </h2>
                <div class="grid gap-2">
                    @foreach($this->pendingMaterialParts as $part)
                        <div class="bg-danger-50 dark:bg-danger-900/20 rounded-lg p-3 border border-danger-200 dark:border-danger-700 flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    {{ $part->file_name }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1 flex-wrap">
                                    @if($part->cncProgram->project)
                                        <a href="{{ route('filament.admin.resources.project.projects.view', $part->cncProgram->project) }}"
                                           class="text-primary-600 hover:text-primary-800 dark:text-primary-400 hover:underline font-medium">
                                            {{ $part->cncProgram->project->name }}
                                        </a>
                                    @else
                                        <span>Unknown</span>
                                    @endif
                                    <span>&bull;</span>
                                    @if($part->cncProgram)
                                        <a href="{{ route('filament.admin.resources.project.cnc-programs.view', $part->cncProgram) }}"
                                           class="text-primary-600 hover:text-primary-800 dark:text-primary-400 hover:underline font-medium">
                                            {{ $part->cncProgram->name }}
                                        </a>
                                    @else
                                        <span>Unknown</span>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('filament.admin.resources.project.cnc-programs.view', $part->cncProgram) }}"
                               class="px-3 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
                               title="View Program">
                                <x-heroicon-o-eye class="w-5 h-5" />
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Parts QA Modal --}}
    @if($showPartsQaModal && $partsQaSheetId)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
                     wire:click="closePartsQaModal"></div>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white" id="modal-title">
                                Parts QA
                            </h3>
                            <button wire:click="closePartsQaModal"
                                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                <x-heroicon-o-x-mark class="w-6 h-6" />
                            </button>
                        </div>

                        {{-- Livewire Cut Parts QA Manager --}}
                        @livewire('cut-parts-qa-manager', ['sheetId' => $partsQaSheetId], key('qa-'.$partsQaSheetId))
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="closePartsQaModal"
                                type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
