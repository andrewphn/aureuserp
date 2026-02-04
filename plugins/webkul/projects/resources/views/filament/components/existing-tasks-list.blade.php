@props(['tasks'])

<div class="divide-y divide-gray-200 dark:divide-gray-700">
    @foreach ($tasks->where('parent_id', null) as $task)
        <div class="py-2 first:pt-0 last:pb-0">
            <div class="flex items-center gap-2">
                @if ($task->priority)
                    <x-heroicon-o-flag class="w-4 h-4 text-red-500" />
                @endif
                <span class="font-medium text-gray-900 dark:text-gray-100">
                    {{ $task->title }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    ({{ $task->allocated_hours }}h, Day {{ $task->relative_days }})
                </span>
            </div>

            @php
                $subtasks = $tasks->where('parent_id', $task->id);
            @endphp

            @if ($subtasks->isNotEmpty())
                <div class="ml-6 mt-1 space-y-1">
                    @foreach ($subtasks as $subtask)
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <span class="text-gray-400">-</span>
                            <span>{{ $subtask->title }}</span>
                            <span class="text-xs">({{ $subtask->allocated_hours }}h)</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>
