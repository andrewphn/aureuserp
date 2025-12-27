{{-- Tasks & Milestones Section (Blue) --}}
@php
    $tasks = $this->getRecentTasks();
    $milestones = $this->getUpcomingMilestones();
    $counts = $this->getTaskCounts();
@endphp

<div class="rounded-lg border border-blue-200 dark:border-blue-800 overflow-hidden">
    {{-- Header --}}
    <div class="bg-blue-500 px-4 py-2.5 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <x-heroicon-s-clipboard-document-list class="w-5 h-5 text-white" />
            <h4 class="text-white font-semibold">Tasks & Milestones</h4>
        </div>
        <div class="flex items-center gap-2">
            @if($counts['overdue'] > 0)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    {{ $counts['overdue'] }} overdue
                </span>
            @endif
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ $counts['pending'] }} pending
            </span>
        </div>
    </div>

    {{-- Content --}}
    <div class="bg-white dark:bg-gray-900 p-4 space-y-4">
        {{-- Recent Tasks --}}
        <div>
            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recent Tasks</h5>
            @if($tasks->isEmpty())
                <p class="text-sm text-gray-500 italic">No tasks yet</p>
            @else
                <div class="space-y-2">
                    @foreach($tasks as $task)
                        <div class="flex items-center justify-between text-sm p-2 rounded bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center gap-2">
                                @if($task->priority)
                                    <x-heroicon-s-star class="w-4 h-4 text-yellow-500" />
                                @endif
                                <span class="text-gray-900 dark:text-gray-100 truncate max-w-[200px]">{{ $task->title }}</span>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full" style="background-color: {{ $task->stage?->color ?? '#6b7280' }}20; color: {{ $task->stage?->color ?? '#6b7280' }};">
                                {{ $task->stage?->name ?? 'No Stage' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Upcoming Milestones --}}
        <div>
            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Upcoming Milestones</h5>
            @if($milestones->isEmpty())
                <p class="text-sm text-gray-500 italic">No milestones yet</p>
            @else
                <div class="space-y-2">
                    @foreach($milestones as $milestone)
                        <div class="flex items-center justify-between text-sm p-2 rounded bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center gap-2">
                                @if($milestone->is_critical)
                                    <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-red-500" />
                                @else
                                    <x-heroicon-o-flag class="w-4 h-4 text-gray-400" />
                                @endif
                                <span class="text-gray-900 dark:text-gray-100">{{ $milestone->name }}</span>
                            </div>
                            @if($milestone->deadline)
                                <span class="text-xs text-gray-500">{{ $milestone->deadline->format('M j') }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
            <button
                wire:click="openTaskModal"
                type="button"
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <x-heroicon-s-plus class="w-4 h-4 mr-1" />
                Add Task
            </button>
            <button
                wire:click="openMilestoneModal"
                type="button"
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <x-heroicon-s-flag class="w-4 h-4 mr-1" />
                Add Milestone
            </button>
            <a
                href="{{ \Webkul\Project\Filament\Resources\TaskResource::getUrl('index', ['tableFilters' => ['project_id' => ['value' => $project->id]]]) }}"
                class="text-sm text-blue-600 hover:text-blue-800 ml-auto"
            >
                View All &rarr;
            </a>
        </div>
    </div>
</div>
