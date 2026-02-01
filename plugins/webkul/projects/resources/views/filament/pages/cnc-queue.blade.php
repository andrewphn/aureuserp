<x-filament-panels::page>
    <div wire:poll.{{ $this->getPollingInterval() }}>
        {{-- Stats Overview --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
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
        </div>

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
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $part->cncProgram->project->name ?? 'Unknown Project' }} &bull;
                                        {{ $part->cncProgram->name ?? 'Unknown Program' }}
                                        @if($part->sheet_number)
                                            &bull; Sheet {{ $part->sheet_number }}
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
                                <div class="flex gap-2">
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
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $part->cncProgram->project->name ?? 'Unknown' }} &bull;
                                        {{ $part->cncProgram->name ?? 'Unknown' }}
                                        @if($part->sheet_number)
                                            &bull; Sheet {{ $part->sheet_number }}
                                        @endif
                                        @if($part->operation_type)
                                            &bull; {{ ucfirst($part->operation_type) }}
                                        @endif
                                    </div>
                                </div>
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
                        <div class="bg-danger-50 dark:bg-danger-900/20 rounded-lg p-3 border border-danger-200 dark:border-danger-700">
                            <div class="font-semibold text-gray-900 dark:text-white">
                                {{ $part->file_name }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $part->cncProgram->project->name ?? 'Unknown' }} &bull;
                                {{ $part->cncProgram->name ?? 'Unknown' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
