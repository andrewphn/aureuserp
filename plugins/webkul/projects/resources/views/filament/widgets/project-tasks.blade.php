<x-filament-widgets::widget>
    @php
        $data = $this->getTasksData();
        $projectId = $this->record?->id;
    @endphp

    <div class="fi-wi-stats-overview-stat relative rounded-xl p-4 shadow-sm bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-x-2">
            <div class="flex items-center gap-x-2">
                <span class="fi-wi-stats-overview-stat-icon flex items-center justify-center rounded-full p-1.5 bg-primary-100 dark:bg-primary-500/20">
                    <x-filament::icon icon="heroicon-o-clipboard-document-list" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                </span>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Tasks</span>
            </div>
            <span class="text-xs text-gray-400">{{ $data['done'] }}/{{ $data['total'] }}</span>
        </div>

        {{-- Progress Stats --}}
        <div class="mt-2 flex items-baseline gap-x-2">
            <span class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ $data['progress'] }}%
            </span>
            <span class="text-sm text-gray-500">complete</span>
        </div>

        {{-- Progress Bar --}}
        <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
            <div class="h-1.5 rounded-full transition-all duration-300 {{ $data['progress'] >= 100 ? 'bg-success-500' : 'bg-primary-500' }}"
                 style="width: {{ $data['progress'] }}%"></div>
        </div>

        {{-- Status Summary --}}
        <div class="mt-2 flex items-center gap-x-3 text-xs">
            @if($data['in_progress'] > 0)
                <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    {{ $data['in_progress'] }} active
                </span>
            @endif
            @if($data['blocked'] > 0)
                <span class="flex items-center gap-1 text-warning-600 dark:text-warning-400">
                    <span class="w-2 h-2 rounded-full bg-warning-500"></span>
                    {{ $data['blocked'] }} blocked
                </span>
            @endif
            @if($data['pending'] > 0)
                <span class="flex items-center gap-1 text-gray-500">
                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                    {{ $data['pending'] }} pending
                </span>
            @endif
        </div>

        {{-- Task List --}}
        @if(count($data['tasks']) > 0)
            <div class="mt-3 space-y-1 max-h-32 overflow-y-auto">
                @foreach($data['tasks'] as $task)
                    <div class="flex items-center gap-2 py-1 group" wire:key="task-{{ $task['id'] }}">
                        {{-- Quick Complete Button --}}
                        <button
                            type="button"
                            wire:click="markTaskDone({{ $task['id'] }})"
                            wire:loading.attr="disabled"
                            class="flex-shrink-0 w-4 h-4 rounded border border-gray-300 dark:border-gray-600 hover:border-success-500 hover:bg-success-50 dark:hover:bg-success-500/20 transition-colors focus:outline-none focus:ring-2 focus:ring-success-500 focus:ring-offset-1"
                            title="Mark as done"
                        >
                            <span class="sr-only">Mark as done</span>
                        </button>

                        {{-- Task Info --}}
                        <div class="flex-1 min-w-0">
                            <a
                                href="{{ route('filament.admin.resources.project.tasks.edit', $task['id']) }}"
                                class="text-xs text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 truncate block"
                                title="{{ $task['title'] }}"
                            >
                                {{ \Illuminate\Support\Str::limit($task['title'], 30) }}
                            </a>
                        </div>

                        {{-- State Badge --}}
                        <span @class([
                            'flex-shrink-0 inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded',
                            'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400' => $task['state']->value === 'in_progress',
                            'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $task['state']->value === 'blocked',
                            'bg-gray-100 text-gray-600 dark:bg-gray-500/20 dark:text-gray-400' => $task['state']->value === 'pending',
                            'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' => $task['state']->value === 'change_requested',
                        ])>
                            {{ \Illuminate\Support\Str::limit($task['state_label'], 10) }}
                        </span>

                        {{-- Priority Indicator --}}
                        @if($task['priority'])
                            <x-filament::icon icon="heroicon-s-flag" class="w-3 h-3 text-danger-500 flex-shrink-0" />
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="mt-3 text-xs text-gray-400 dark:text-gray-500 text-center py-2">
                No active tasks
            </div>
        @endif

        {{-- Footer Link --}}
        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
            <a
                href="{{ route('filament.admin.resources.project.projects.tasks', $projectId) }}"
                class="text-xs text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
            >
                View all tasks
                <x-filament::icon icon="heroicon-m-arrow-right" class="w-3 h-3" />
            </a>
        </div>
    </div>
</x-filament-widgets::widget>
