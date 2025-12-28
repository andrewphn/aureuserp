{{-- Quick Actions / Project Details Modal --}}
<x-filament::modal
    id="kanban--quick-actions-modal"
    :close-by-clicking-away="true"
    :close-button="true"
    slide-over
    width="lg"
>
    @if($quickActionsRecord ?? null)
        @php
            $project = $quickActionsRecord;
            $daysLeft = null;
            $isOverdue = false;
            if ($project->desired_completion_date) {
                $days = now()->diffInDays($project->desired_completion_date, false);
                $daysLeft = (int) $days;
                $isOverdue = $days < 0;
            }
            $blockers = $this->getProjectBlockers($project);
            $hasBlockers = !empty($blockers);
            $milestones = $project->milestones ?? collect();
            $totalMilestones = $milestones->count();
            $completedMilestones = $milestones->where('is_completed', true)->count();
            $progressPercent = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100) : 0;
            $availableUsers = $this->getAvailableUsers();
            $availableStages = $this->getAvailableStages();
        @endphp

        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $project->stage?->color ?? '#6b7280' }}"></div>
                <span class="font-medium text-gray-900 dark:text-white truncate">{{ $project->name }}</span>
            </div>
        </x-slot>

        <div class="space-y-4">
            {{-- Status Badge Row + Blocked Toggle --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    @if($hasBlockers)
                        <x-filament::badge color="gray" class="!bg-purple-100 !text-purple-700 dark:!bg-purple-900/30 dark:!text-purple-300">Blocked</x-filament::badge>
                    @endif
                    @if($isOverdue)
                        <x-filament::badge color="danger">{{ abs($daysLeft) }}d Overdue</x-filament::badge>
                    @elseif($daysLeft !== null && $daysLeft <= 7 && $daysLeft >= 0)
                        <x-filament::badge color="warning">{{ $daysLeft }}d Left</x-filament::badge>
                    @endif
                </div>
                {{-- Blocked Toggle Button --}}
                <button
                    wire:click="toggleProjectBlocked"
                    class="text-xs px-2 py-1 rounded-md transition-colors {{ $hasBlockers ? 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400' : 'bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-900/30 dark:text-purple-400' }}"
                >
                    {{ $hasBlockers ? '✓ Unblock' : '⚠ Mark Blocked' }}
                </button>
            </div>

            {{-- Stage Selector --}}
            <div class="space-y-2">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stage</h4>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($availableStages as $stage)
                        <button
                            wire:click="changeProjectStage({{ $stage->id }})"
                            @class([
                                'px-2.5 py-1 text-xs rounded-full transition-all',
                                'ring-2 ring-offset-1 ring-primary-500 font-medium' => $project->stage_id === $stage->id,
                                'hover:ring-1 hover:ring-gray-300' => $project->stage_id !== $stage->id,
                            ])
                            style="background-color: {{ $stage->color ?? '#6b7280' }}20; color: {{ $stage->color ?? '#6b7280' }}"
                        >
                            {{ $stage->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            {{-- Customer & Details Row --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <span class="text-xs text-gray-500">Customer</span>
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $project->partner?->name ?? '—' }}</p>
                </div>
                <div class="space-y-1">
                    <span class="text-xs text-gray-500">Due Date</span>
                    <p class="text-sm font-medium {{ $isOverdue ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                        {{ $project->desired_completion_date?->format('M j, Y') ?? '—' }}
                    </p>
                </div>
                <div class="space-y-1">
                    <span class="text-xs text-gray-500">Project #</span>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $project->project_number ?? '—' }}</p>
                </div>
                <div class="space-y-1">
                    <span class="text-xs text-gray-500">Linear Feet</span>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $project->estimated_linear_feet ? number_format($project->estimated_linear_feet, 0) . ' LF' : '—' }}</p>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            {{-- Team Assignment Widget --}}
            <div class="space-y-2">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Team</h4>
                <div class="space-y-2">
                    {{-- Project Manager --}}
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center flex-shrink-0">
                            @if($project->user)
                                <span class="text-xs font-bold text-primary-700 dark:text-primary-300">{{ strtoupper(substr($project->user->name, 0, 1)) }}</span>
                            @else
                                <x-heroicon-o-user class="w-3 h-3 text-gray-400" />
                            @endif
                        </div>
                        <select
                            wire:change="assignTeamMember('pm', $event.target.value)"
                            class="flex-1 text-sm border-0 bg-transparent focus:ring-0 py-0 cursor-pointer text-gray-900 dark:text-white"
                        >
                            <option value="">Unassigned PM</option>
                            @foreach($availableUsers as $user)
                                <option value="{{ $user->id }}" @selected($project->user_id == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Designer --}}
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-pink-100 dark:bg-pink-900 flex items-center justify-center flex-shrink-0">
                            @if($project->designer)
                                <span class="text-xs font-bold text-pink-700 dark:text-pink-300">{{ strtoupper(substr($project->designer->name, 0, 1)) }}</span>
                            @else
                                <x-heroicon-o-paint-brush class="w-3 h-3 text-gray-400" />
                            @endif
                        </div>
                        <select
                            wire:change="assignTeamMember('designer', $event.target.value)"
                            class="flex-1 text-sm border-0 bg-transparent focus:ring-0 py-0 cursor-pointer text-gray-900 dark:text-white"
                        >
                            <option value="">Unassigned Designer</option>
                            @foreach($availableUsers as $user)
                                <option value="{{ $user->id }}" @selected($project->designer_id == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Purchasing Manager --}}
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center flex-shrink-0">
                            @if($project->purchasingManager)
                                <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300">{{ strtoupper(substr($project->purchasingManager->name, 0, 1)) }}</span>
                            @else
                                <x-heroicon-o-shopping-cart class="w-3 h-3 text-gray-400" />
                            @endif
                        </div>
                        <select
                            wire:change="assignTeamMember('purchasing', $event.target.value)"
                            class="flex-1 text-sm border-0 bg-transparent focus:ring-0 py-0 cursor-pointer text-gray-900 dark:text-white"
                        >
                            <option value="">Unassigned Purchasing</option>
                            @foreach($availableUsers as $user)
                                <option value="{{ $user->id }}" @selected($project->purchasing_manager_id == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            {{-- Milestones Widget with Checkboxes --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Milestones
                        <span class="text-gray-400 font-normal">({{ $completedMilestones }}/{{ $totalMilestones }})</span>
                    </h4>
                </div>

                {{-- Milestone List with Checkboxes --}}
                @if($milestones->isNotEmpty())
                    <div class="space-y-1">
                        @foreach($milestones->sortBy('sort') as $milestone)
                            <label class="flex items-center gap-2 py-1 px-2 -mx-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    wire:click="toggleMilestoneStatus({{ $milestone->id }})"
                                    @checked($milestone->is_completed)
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                                <span class="text-sm flex-1 {{ $milestone->is_completed ? 'text-gray-500 line-through' : 'text-gray-900 dark:text-white' }}">
                                    {{ $milestone->name }}
                                </span>
                                @if($milestone->deadline)
                                    <span class="text-xs text-gray-400">{{ $milestone->deadline->format('M j') }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @endif

                {{-- Add Milestone Inline --}}
                <div class="flex items-center gap-2 mt-2">
                    <input
                        type="text"
                        wire:model="quickMilestoneTitle"
                        wire:keydown.enter="addQuickMilestone"
                        placeholder="+ Add milestone..."
                        class="flex-1 text-sm border-0 border-b border-dashed border-gray-300 dark:border-gray-600 bg-transparent focus:ring-0 focus:border-primary-500 px-0 py-1 placeholder-gray-400"
                    />
                    @if($this->quickMilestoneTitle)
                        <button wire:click="addQuickMilestone" class="text-primary-600 hover:text-primary-800">
                            <x-heroicon-m-plus-circle class="w-5 h-5" />
                        </button>
                    @endif
                </div>

                {{-- Progress Bar --}}
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mt-2">
                    <div
                        class="h-1.5 rounded-full transition-all duration-300 {{ $hasBlockers ? 'bg-purple-500' : ($isOverdue ? 'bg-red-500' : 'bg-primary-600') }}"
                        style="width: {{ $progressPercent }}%"
                    ></div>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            {{-- Tasks Widget with Status Dropdowns --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tasks</h4>
                    <a href="{{ \Webkul\Project\Filament\Resources\TaskResource::getUrl('index', ['tableFilters' => ['project_id' => ['value' => $project->id]]]) }}"
                       class="text-xs text-primary-600 hover:text-primary-800">
                        View all
                    </a>
                </div>

                @php
                    $recentTasks = $project->tasks->sortByDesc('updated_at')->take(5);
                @endphp

                {{-- Task List with Status Dropdowns --}}
                @if($recentTasks->isNotEmpty())
                    <div class="space-y-1">
                        @foreach($recentTasks as $task)
                            <div class="flex items-center gap-2 py-1 px-2 -mx-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800 group">
                                <select
                                    wire:change="updateTaskStatus({{ $task->id }}, $event.target.value)"
                                    @class([
                                        'text-xs border-0 rounded py-0.5 px-1.5 focus:ring-1 cursor-pointer',
                                        'bg-green-100 text-green-700' => $task->state === 'done',
                                        'bg-purple-100 text-purple-700' => $task->state === 'blocked',
                                        'bg-blue-100 text-blue-700' => $task->state === 'in_progress',
                                        'bg-gray-100 text-gray-700' => !in_array($task->state, ['done', 'blocked', 'in_progress']),
                                    ])
                                >
                                    <option value="pending" @selected($task->state === 'pending')>To Do</option>
                                    <option value="in_progress" @selected($task->state === 'in_progress')>In Progress</option>
                                    <option value="blocked" @selected($task->state === 'blocked')>Blocked</option>
                                    <option value="done" @selected($task->state === 'done')>Done</option>
                                </select>
                                <span class="text-sm flex-1 truncate {{ $task->state === 'done' ? 'text-gray-500 line-through' : 'text-gray-900 dark:text-white' }}">
                                    {{ $task->title }}
                                </span>
                                <a href="{{ \Webkul\Project\Filament\Resources\TaskResource::getUrl('edit', ['record' => $task]) }}"
                                   class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-primary-600"
                                   wire:click.stop>
                                    <x-heroicon-m-pencil class="w-3.5 h-3.5" />
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Add Task Inline --}}
                <div class="flex items-center gap-2 mt-2">
                    <input
                        type="text"
                        wire:model="quickTaskTitle"
                        wire:keydown.enter="addQuickTask"
                        placeholder="+ Add task..."
                        class="flex-1 text-sm border-0 border-b border-dashed border-gray-300 dark:border-gray-600 bg-transparent focus:ring-0 focus:border-primary-500 px-0 py-1 placeholder-gray-400"
                    />
                    @if($this->quickTaskTitle)
                        <button wire:click="addQuickTask" class="text-primary-600 hover:text-primary-800">
                            <x-heroicon-m-plus-circle class="w-5 h-5" />
                        </button>
                    @endif
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            {{-- Orders Widget --}}
            <div class="space-y-2">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Orders</h4>
                @php
                    $orders = $project->orders;
                    $totalValue = $orders->sum('grand_total');
                @endphp
                @if($orders->isEmpty())
                    <p class="text-sm text-gray-400 italic">No orders</p>
                @else
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-currency-dollar class="h-4 w-4 text-green-500 flex-shrink-0" />
                        <span class="text-lg font-semibold text-green-600 dark:text-green-400">${{ number_format($totalValue, 2) }}</span>
                        <span class="text-xs text-gray-500">({{ $orders->count() }} {{ Str::plural('order', $orders->count()) }})</span>
                    </div>
                @endif
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            {{-- Quick Comment Widget --}}
            <div class="space-y-2">
                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quick Note</h4>
                <div class="flex gap-2">
                    <input
                        type="text"
                        wire:model.live="quickComment"
                        wire:keydown.enter="postQuickComment"
                        placeholder="Add a comment..."
                        class="flex-1 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 focus:ring-primary-500 focus:border-primary-500"
                    />
                    <x-filament::button
                        wire:click="postQuickComment"
                        size="sm"
                        color="gray"
                        :disabled="empty($this->quickComment)"
                    >
                        Post
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- Footer Actions --}}
        <x-slot name="footer">
            <div class="flex items-center gap-2 w-full">
                <x-filament::button
                    tag="a"
                    href="{{ \Webkul\Project\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $project]) }}"
                    color="primary"
                    icon="heroicon-m-pencil-square"
                    size="sm"
                    class="flex-1"
                >
                    Full Edit
                </x-filament::button>

                <x-filament::icon-button
                    wire:click="openChatter('{{ $project->id }}')"
                    x-on:click="$dispatch('close-modal', { id: 'kanban--quick-actions-modal' })"
                    color="gray"
                    icon="heroicon-m-chat-bubble-left-right"
                    size="sm"
                    label="Messages"
                />

                <x-filament::icon-button
                    tag="a"
                    href="{{ route('filament.admin.resources.project.projects.view', $project) }}"
                    color="gray"
                    icon="heroicon-m-arrow-top-right-on-square"
                    size="sm"
                    label="Full Page"
                />
            </div>
        </x-slot>
    @else
        <x-slot name="heading">Project Details</x-slot>
        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
            <x-heroicon-o-folder class="w-10 h-10 mb-2 opacity-50" />
            <p class="text-sm">Select a project to view details</p>
        </div>
    @endif
</x-filament::modal>
