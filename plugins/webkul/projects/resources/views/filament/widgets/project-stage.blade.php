<x-filament-widgets::widget>
    @php
        $data = $this->getStageData();
        $tasksData = $this->getTasksData();
        $selectedMilestoneId = $this->selectedMilestoneId;

        // TCS Design System Stage Colors
        $stageStyles = [
            'discovery' => [
                'bg' => 'background-color: #dbeafe;',
                'border' => 'box-shadow: 0 0 0 3px #3b82f6;',
                'icon_bg' => 'background-color: #bfdbfe;',
                'icon_color' => 'color: #2563eb;',
                'progress_bg' => 'background-color: #3b82f6;',
                'text_color' => 'color: #1e40af;',
                'milestone_bg' => 'bg-blue-100 hover:bg-blue-200',
                'milestone_active' => 'bg-blue-500 text-white',
            ],
            'design' => [
                'bg' => 'background-color: #ede9fe;',
                'border' => 'box-shadow: 0 0 0 3px #8b5cf6;',
                'icon_bg' => 'background-color: #ddd6fe;',
                'icon_color' => 'color: #7c3aed;',
                'progress_bg' => 'background-color: #8b5cf6;',
                'text_color' => 'color: #5b21b6;',
                'milestone_bg' => 'bg-violet-100 hover:bg-violet-200',
                'milestone_active' => 'bg-violet-500 text-white',
            ],
            'sourcing' => [
                'bg' => 'background-color: #fef3c7;',
                'border' => 'box-shadow: 0 0 0 3px #f59e0b;',
                'icon_bg' => 'background-color: #fde68a;',
                'icon_color' => 'color: #d97706;',
                'progress_bg' => 'background-color: #f59e0b;',
                'text_color' => 'color: #92400e;',
                'milestone_bg' => 'bg-amber-100 hover:bg-amber-200',
                'milestone_active' => 'bg-amber-500 text-white',
            ],
            'production' => [
                'bg' => 'background-color: #d1fae5;',
                'border' => 'box-shadow: 0 0 0 3px #10b981;',
                'icon_bg' => 'background-color: #a7f3d0;',
                'icon_color' => 'color: #059669;',
                'progress_bg' => 'background-color: #10b981;',
                'text_color' => 'color: #065f46;',
                'milestone_bg' => 'bg-emerald-100 hover:bg-emerald-200',
                'milestone_active' => 'bg-emerald-500 text-white',
            ],
            'delivery' => [
                'bg' => 'background-color: #ccfbf1;',
                'border' => 'box-shadow: 0 0 0 3px #14b8a6;',
                'icon_bg' => 'background-color: #99f6e4;',
                'icon_color' => 'color: #0d9488;',
                'progress_bg' => 'background-color: #14b8a6;',
                'text_color' => 'color: #115e59;',
                'milestone_bg' => 'bg-teal-100 hover:bg-teal-200',
                'milestone_active' => 'bg-teal-500 text-white',
            ],
        ];

        $styles = $stageStyles[$data['stage']] ?? $stageStyles['discovery'];

        // Get tasks based on selected milestone
        $displayTasks = collect();
        $selectedMilestoneName = null;

        if ($selectedMilestoneId) {
            // Show tasks for selected milestone
            foreach ($tasksData['milestones'] as $milestone) {
                if ($milestone['id'] === $selectedMilestoneId) {
                    $selectedMilestoneName = $milestone['name'];
                    foreach ($milestone['tasks'] as $task) {
                        $displayTasks->push($task);
                    }
                    break;
                }
            }
        } else {
            // Show all tasks (combined from milestones + ungrouped)
            foreach ($tasksData['milestones'] as $milestone) {
                foreach ($milestone['tasks'] as $task) {
                    $displayTasks->push($task);
                }
            }
            foreach ($tasksData['ungrouped'] as $task) {
                $displayTasks->push($task);
            }
        }

        $displayTasks = $displayTasks->take(6);
    @endphp

    <div class="fi-wi-stats-overview-stat relative rounded-xl shadow-sm overflow-hidden" style="{{ $styles['bg'] }} {{ $styles['border'] }}">
        <div class="flex">
            {{-- Project Stage Section (Left - Narrower) --}}
            <div class="w-52 flex-shrink-0 p-4 border-r border-black/10 dark:border-white/10">
                {{-- Header --}}
                <div class="flex items-center gap-x-2 mb-1">
                    <span class="text-xs font-medium uppercase tracking-wide opacity-75" style="{{ $styles['text_color'] }}">Project Stage</span>
                </div>

                {{-- Stage label --}}
                <div class="text-xl font-bold tracking-tight mb-3" style="{{ $styles['text_color'] }}">
                    {{ $data['label'] }}
                </div>

                {{-- Gates checklist --}}
                <div class="space-y-1.5 mb-3">
                    @foreach ($data['gates'] as $gate)
                        <div class="flex items-center gap-x-2">
                            @if ($gate['completed'])
                                <svg class="w-4 h-4 flex-shrink-0 text-success-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-xs line-through text-gray-400">{{ $gate['label'] }}</span>
                            @else
                                <svg class="w-4 h-4 opacity-40 flex-shrink-0" style="{{ $styles['icon_color'] }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <circle cx="12" cy="12" r="9" />
                                </svg>
                                <span class="text-xs" style="{{ $styles['text_color'] }}">{{ $gate['label'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Milestones (clickable) --}}
                @if(count($tasksData['milestones']) > 0)
                    <div class="border-t border-black/10 dark:border-white/10 pt-3">
                        <div class="text-xs font-medium uppercase tracking-wide opacity-75 mb-2" style="{{ $styles['text_color'] }}">Milestones</div>
                        <div class="space-y-1">
                            @foreach($tasksData['milestones'] as $milestone)
                                <button
                                    type="button"
                                    wire:click="selectMilestone({{ $milestone['id'] }})"
                                    @class([
                                        'w-full flex items-center gap-2 px-2 py-1.5 rounded-md text-left text-xs transition-colors',
                                        $styles['milestone_active'] => $selectedMilestoneId === $milestone['id'],
                                        $styles['milestone_bg'] => $selectedMilestoneId !== $milestone['id'],
                                    ])
                                >
                                    @if($milestone['is_completed'])
                                        <x-filament::icon icon="heroicon-s-check-circle" class="w-3.5 h-3.5 flex-shrink-0 {{ $selectedMilestoneId === $milestone['id'] ? 'text-white' : 'text-success-500' }}" />
                                    @elseif($milestone['is_critical'])
                                        <x-filament::icon icon="heroicon-s-flag" class="w-3.5 h-3.5 flex-shrink-0 {{ $selectedMilestoneId === $milestone['id'] ? 'text-white' : 'text-danger-500' }}" />
                                    @else
                                        <x-filament::icon icon="heroicon-o-flag" class="w-3.5 h-3.5 flex-shrink-0 opacity-50" />
                                    @endif
                                    <span class="flex-1 truncate font-medium">{{ $milestone['name'] }}</span>
                                    <span class="text-[10px] opacity-75">{{ $milestone['done'] }}/{{ $milestone['total'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Progress indicator --}}
                <div class="mt-3 pt-2 border-t border-black/10 dark:border-white/10">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span style="{{ $styles['text_color'] }}">Gates</span>
                        <span class="font-medium" style="{{ $styles['text_color'] }}">{{ $data['completed'] }}/{{ $data['total'] }}</span>
                    </div>
                    <div class="w-full bg-white/50 dark:bg-gray-700/50 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full transition-all duration-300"
                             style="width: {{ $data['progress'] }}%; {{ $data['progress'] >= 100 ? 'background-color: rgb(34 197 94);' : $styles['progress_bg'] }}"></div>
                    </div>
                </div>
            </div>

            {{-- Tasks Section (Right - Wider) --}}
            <div class="flex-1 p-4 bg-white/40 dark:bg-gray-900/40">
                {{-- Tasks Header --}}
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-x-2">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ $selectedMilestoneName ? $selectedMilestoneName : 'Tasks' }}
                        </span>
                        <span class="text-xs text-gray-400">({{ $tasksData['done'] }}/{{ $tasksData['total'] }} complete)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($selectedMilestoneId)
                            <button
                                type="button"
                                wire:click="selectMilestone(null)"
                                class="text-xs text-gray-500 hover:text-gray-700"
                            >
                                Show all
                            </button>
                        @endif
                        <a
                            href="{{ route('filament.admin.resources.project.projects.tasks', $this->record?->id) }}"
                            class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
                        >
                            View all
                        </a>
                    </div>
                </div>

                {{-- Task List --}}
                <div class="space-y-1">
                    @forelse($displayTasks as $task)
                        <div class="flex items-center gap-2 py-1 group">
                            {{-- Checkbox --}}
                            @if($task['state']->value !== 'done')
                                <button
                                    type="button"
                                    wire:click="markTaskDone({{ $task['id'] }})"
                                    wire:loading.attr="disabled"
                                    class="flex-shrink-0 w-4 h-4 rounded border-2 border-gray-300 dark:border-gray-500 hover:border-success-500 hover:bg-success-50 dark:hover:bg-success-500/20 transition-colors"
                                    title="Mark as done"
                                ></button>
                            @else
                                <x-filament::icon icon="heroicon-s-check-circle" class="w-4 h-4 text-success-500 flex-shrink-0" />
                            @endif

                            {{-- Task Title --}}
                            <a
                                href="{{ route('filament.admin.resources.project.tasks.edit', $task['id']) }}"
                                class="flex-1 text-sm truncate {{ $task['state']->value === 'done' ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-300 hover:text-primary-600' }}"
                                title="{{ $task['title'] }}"
                            >
                                {{ $task['title'] }}
                            </a>

                            {{-- State Badge --}}
                            @if($task['state']->value !== 'done')
                                <span @class([
                                    'flex-shrink-0 inline-flex items-center px-2 py-0.5 text-xs font-medium rounded',
                                    'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400' => $task['state']->value === 'in_progress',
                                    'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $task['state']->value === 'blocked',
                                    'bg-gray-100 text-gray-600 dark:bg-gray-500/20 dark:text-gray-400' => $task['state']->value === 'pending',
                                    'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' => $task['state']->value === 'change_requested',
                                ])>
                                    {{ $task['state_label'] }}
                                </span>
                            @endif

                            {{-- Priority Flag --}}
                            @if($task['priority'])
                                <x-filament::icon icon="heroicon-s-flag" class="w-4 h-4 text-danger-500 flex-shrink-0" />
                            @endif
                        </div>
                    @empty
                        <div class="text-sm text-gray-400 dark:text-gray-500 text-center py-6">
                            {{ $selectedMilestoneId ? 'No tasks for this milestone' : 'No tasks for this stage' }}
                        </div>
                    @endforelse
                </div>

                {{-- Show more indicator --}}
                @if($displayTasks->count() >= 6)
                    <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700 text-center">
                        <a
                            href="{{ route('filament.admin.resources.project.projects.tasks', $this->record?->id) }}"
                            class="text-xs text-gray-500 hover:text-primary-600"
                        >
                            + more tasks
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
