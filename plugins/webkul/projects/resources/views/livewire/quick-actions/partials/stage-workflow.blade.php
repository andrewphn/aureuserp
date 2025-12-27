{{-- Stage Workflow Section (Green) --}}
@php
    $stageProgress = $this->getStageProgress();
    $stages = $stageProgress['stages'];
    $currentIndex = $stageProgress['currentIndex'];
    $canAdvance = method_exists($project, 'canAdvanceToNextStage') ? $project->canAdvanceToNextStage() : false;
    $gateStatus = method_exists($project, 'getStageGateStatus') ? $project->getStageGateStatus() : [];
@endphp

<div class="rounded-lg border border-green-200 dark:border-green-800 overflow-hidden">
    {{-- Header --}}
    <div class="bg-green-500 px-4 py-2.5 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <x-heroicon-s-arrow-path class="w-5 h-5 text-white" />
            <h4 class="text-white font-semibold">Stage Workflow</h4>
        </div>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
            {{ $project->stage?->name ?? 'No Stage' }}
        </span>
    </div>

    {{-- Content --}}
    <div class="bg-white dark:bg-gray-900 p-4 space-y-4">
        {{-- Stage Progress Indicator --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                @foreach($stages as $index => $stage)
                    <div class="flex flex-col items-center flex-1 {{ $index < count($stages) - 1 ? 'relative' : '' }}">
                        {{-- Stage dot --}}
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                            {{ $index < $currentIndex ? 'bg-green-500 text-white' : '' }}
                            {{ $index === $currentIndex ? 'bg-green-600 text-white ring-4 ring-green-200' : '' }}
                            {{ $index > $currentIndex ? 'bg-gray-200 dark:bg-gray-700 text-gray-500' : '' }}
                        ">
                            @if($index < $currentIndex)
                                <x-heroicon-s-check class="w-4 h-4" />
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>
                        {{-- Stage name --}}
                        <span class="text-[10px] mt-1 text-center leading-tight max-w-[60px] truncate
                            {{ $index === $currentIndex ? 'font-semibold text-green-700 dark:text-green-300' : 'text-gray-500' }}
                        ">
                            {{ $stage->name }}
                        </span>

                        {{-- Connector line --}}
                        @if($index < count($stages) - 1)
                            <div class="absolute top-4 left-1/2 w-full h-0.5 -z-10
                                {{ $index < $currentIndex ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}
                            "></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Gate Status (if available) --}}
        @if(!empty($gateStatus))
            <div>
                <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gate Requirements</h5>
                <div class="space-y-1">
                    @foreach($gateStatus as $gate => $status)
                        <div class="flex items-center gap-2 text-sm">
                            @if($status)
                                <x-heroicon-s-check-circle class="w-4 h-4 text-green-500" />
                            @else
                                <x-heroicon-s-x-circle class="w-4 h-4 text-red-500" />
                            @endif
                            <span class="text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $gate)) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Current Stage Info --}}
        @if($project->stage)
            <div class="p-3 rounded-lg bg-green-50 dark:bg-green-900/20">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full" style="background-color: {{ $project->stage->color ?? '#10b981' }}"></div>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $project->stage->name }}</span>
                </div>
                @if($project->stage->description)
                    <p class="text-xs text-gray-500 mt-1">{{ $project->stage->description }}</p>
                @endif
            </div>
        @endif

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
            <button
                wire:click="advanceStage"
                type="button"
                @if(!$canAdvance) disabled @endif
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500
                    {{ $canAdvance
                        ? 'text-white bg-green-600 hover:bg-green-700'
                        : 'text-gray-400 bg-gray-100 dark:bg-gray-800 cursor-not-allowed'
                    }}"
            >
                <x-heroicon-s-arrow-right class="w-4 h-4 mr-1" />
                Advance Stage
            </button>
            @if(!$canAdvance && $currentIndex < count($stages) - 1)
                <span class="text-xs text-gray-500">Complete gate requirements to advance</span>
            @elseif($currentIndex >= count($stages) - 1)
                <span class="text-xs text-green-600 font-medium">Project at final stage</span>
            @endif
        </div>
    </div>
</div>
