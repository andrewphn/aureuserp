<x-filament-panels::page>
    <div class="space-y-6">
        @if(count($this->tasks) === 0)
            {{-- No tasks generated - show debug info --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2 text-danger-600">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                        No Tasks Generated
                    </div>
                </x-slot>
                <x-slot name="description">
                    The AI did not generate any valid tasks. This could be due to:
                </x-slot>

                <div class="space-y-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Missing or unclear milestone description</li>
                            <li>AI response parsing error</li>
                            <li>API rate limit or connection issue</li>
                        </ul>
                    </div>

                    @if($this->suggestion->ai_reasoning)
                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                            <h4 class="font-medium text-yellow-800 dark:text-yellow-200 mb-2">AI Response:</h4>
                            <pre class="text-xs text-yellow-700 dark:text-yellow-300 whitespace-pre-wrap">{{ $this->suggestion->ai_reasoning }}</pre>
                        </div>
                    @endif

                    @php
                        $context = json_decode($this->suggestion->prompt_context, true);
                    @endphp

                    @if($context)
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Generation Context:</h4>
                            <dl class="text-xs space-y-2">
                                <div>
                                    <dt class="font-medium text-gray-600 dark:text-gray-400">Milestone:</dt>
                                    <dd>{{ $context['milestone_name'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 dark:text-gray-400">Production Stage:</dt>
                                    <dd>{{ $context['production_stage'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 dark:text-gray-400">Milestone Description:</dt>
                                    <dd>{{ $context['milestone_description'] ?? '(empty)' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 dark:text-gray-400">Additional Context:</dt>
                                    <dd>{{ $context['additional_context'] ?? '(none provided)' }}</dd>
                                </div>
                                @if(!empty($context['validation_errors']))
                                    <div>
                                        <dt class="font-medium text-red-600 dark:text-red-400">Validation Errors:</dt>
                                        <dd class="text-red-600">
                                            <ul class="list-disc list-inside">
                                                @foreach($context['validation_errors'] as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </dd>
                                    </div>
                                @endif
                                @if(!empty($context['raw_response_preview']))
                                    <div>
                                        <dt class="font-medium text-gray-600 dark:text-gray-400">Raw AI Response (preview):</dt>
                                        <dd><pre class="text-xs whitespace-pre-wrap bg-gray-100 dark:bg-gray-900 p-2 rounded mt-1 max-h-64 overflow-auto">{{ $context['raw_response_preview'] }}</pre></dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif

                    <div class="text-sm">
                        <strong>Recommendation:</strong>
                        <ul class="list-disc list-inside mt-1 text-gray-600 dark:text-gray-400">
                            <li>Add a description to the milestone template explaining what tasks should be included</li>
                            <li>When generating, provide additional context in the modal (e.g., "Include QC tasks, focus on assembly steps")</li>
                            <li>Try generating again with more specific context</li>
                        </ul>
                    </div>
                </div>
            </x-filament::section>

            <div class="flex items-center justify-between gap-4 px-4 py-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    No tasks to review
                </div>

                <div class="flex items-center gap-3">
                    <x-filament::button
                        color="gray"
                        tag="a"
                        :href="\Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneTemplateResource::getUrl('edit', ['record' => $this->record])"
                    >
                        <x-slot name="icon">
                            <x-heroicon-o-arrow-left class="w-5 h-5" />
                        </x-slot>
                        Back to Template
                    </x-filament::button>

                    <x-filament::button
                        color="danger"
                        outlined
                        wire:click="reject"
                    >
                        <x-slot name="icon">
                            <x-heroicon-o-x-circle class="w-5 h-5" />
                        </x-slot>
                        Mark as Rejected
                    </x-filament::button>
                </div>
            </div>
        @else
            {{-- AI Analysis Section --}}
            <x-filament::section collapsible>
                <x-slot name="heading">AI Analysis</x-slot>
                <x-slot name="description">Review the AI's reasoning and confidence level</x-slot>

                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Confidence Score</div>
                        @include('webkul-project::filament.components.confidence-badge', [
                            'score' => $this->suggestion->confidence_score,
                            'level' => $this->suggestion->confidence_level,
                            'color' => $this->suggestion->confidence_color,
                        ])
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Suggested Tasks</div>
                        <div class="text-lg font-semibold">{{ $this->suggestion->suggested_task_count }} tasks</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">AI Model</div>
                        <div class="text-sm">{{ $this->suggestion->model_used ?? 'Unknown' }}</div>
                    </div>
                </div>

                @if($this->suggestion->ai_reasoning)
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">AI Reasoning</div>
                        <div class="text-sm">{{ $this->suggestion->ai_reasoning }}</div>
                    </div>
                @endif
            </x-filament::section>

            {{-- Existing Tasks Section --}}
            @if($this->record->taskTemplates->isNotEmpty())
                <x-filament::section collapsible collapsed>
                    <x-slot name="heading">Existing Tasks ({{ $this->record->taskTemplates->count() }})</x-slot>
                    <x-slot name="description">Tasks already defined for this milestone template</x-slot>

                    <ul class="text-sm space-y-1">
                        @foreach($this->record->taskTemplates as $task)
                            <li class="flex items-center gap-2">
                                <x-heroicon-o-check-circle class="w-4 h-4 text-success-500" />
                                {{ $task->title }}
                            </li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif

            {{-- Suggested Tasks Section --}}
            <x-filament::section>
                <x-slot name="heading">Suggested Tasks</x-slot>
                <x-slot name="description">Select tasks to approve and make any corrections</x-slot>

                <div class="space-y-4">
                    @foreach($this->tasks as $index => $task)
                        <div class="p-4 border rounded-lg {{ $task['selected'] ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800' }}">
                            <div class="flex items-start gap-4">
                                <div class="pt-1">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleTask({{ $index }})"
                                        @checked($task['selected'])
                                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                                    />
                                </div>
                                <div class="flex-1 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="text"
                                                wire:model.live="tasks.{{ $index }}.title"
                                                class="text-lg font-semibold bg-transparent border-0 border-b border-transparent hover:border-gray-300 focus:border-primary-500 focus:ring-0 p-0"
                                            />
                                            @if($task['priority'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-200">
                                                    Priority
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-4 text-sm text-gray-500">
                                            <span>{{ $task['allocated_hours'] }}h</span>
                                            <span>Day {{ $task['relative_days'] }}</span>
                                            <span class="px-2 py-0.5 rounded text-xs {{ $task['duration_type'] === 'formula' ? 'bg-info-100 text-info-800 dark:bg-info-900 dark:text-info-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                                {{ $task['duration_type'] === 'formula' ? 'Formula' : $task['duration_days'] . ' days' }}
                                            </span>
                                        </div>
                                    </div>

                                    @if($task['description'])
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $task['description'] }}</p>
                                    @endif

                                    @if($task['duration_type'] === 'formula')
                                        <div class="flex flex-wrap gap-3 text-xs text-info-600 dark:text-info-400">
                                            @if($task['duration_rate_key'])
                                                <span class="inline-flex items-center gap-1">
                                                    <x-heroicon-o-calculator class="w-3 h-3" />
                                                    Rate: {{ $this->getRateKeyOptions()[$task['duration_rate_key']] ?? $task['duration_rate_key'] }}
                                                </span>
                                            @endif
                                            @if($task['duration_unit_type'])
                                                <span>Unit: {{ ucfirst(str_replace('_', ' ', $task['duration_unit_type'])) }}</span>
                                            @endif
                                            @if($task['duration_min_days'] || $task['duration_max_days'])
                                                <span>
                                                    Bounds: {{ $task['duration_min_days'] ?? '?' }}-{{ $task['duration_max_days'] ?? '?' }} days
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                    @if(!empty($task['subtasks']))
                                        <div class="pl-4 border-l-2 border-gray-200 dark:border-gray-600 space-y-2">
                                            <div class="text-xs font-medium text-gray-500 uppercase">Subtasks</div>
                                            @foreach($task['subtasks'] as $subtask)
                                                <div class="flex items-center justify-between text-sm">
                                                    <span>{{ $subtask['title'] }}</span>
                                                    <span class="text-gray-500">{{ $subtask['allocated_hours'] }}h / {{ $subtask['duration_days'] }}d</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            {{-- Review Notes Section --}}
            <x-filament::section collapsible collapsed>
                <x-slot name="heading">Review Notes</x-slot>

                <textarea
                    wire:model="reviewerNotes"
                    rows="3"
                    placeholder="Add any notes about your review decisions..."
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                ></textarea>
            </x-filament::section>

            {{-- Action Bar --}}
            <div class="flex items-center justify-between gap-4 px-4 py-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-medium">
                        {{ collect($this->tasks)->filter(fn ($task) => $task['selected'] ?? false)->count() }}
                    </span>
                    of
                    <span class="font-medium">{{ count($this->tasks) }}</span>
                    tasks selected
                </div>

                <div class="flex items-center gap-3">
                    <x-filament::button
                        color="danger"
                        outlined
                        wire:click="reject"
                        wire:confirm="Are you sure you want to reject all AI suggestions? This action cannot be undone."
                    >
                        <x-slot name="icon">
                            <x-heroicon-o-x-circle class="w-5 h-5" />
                        </x-slot>
                        Reject All
                    </x-filament::button>

                    <x-filament::button
                        color="gray"
                        wire:click="approveAll"
                    >
                        <x-slot name="icon">
                            <x-heroicon-o-check-circle class="w-5 h-5" />
                        </x-slot>
                        Approve All
                    </x-filament::button>

                    <x-filament::button
                        color="primary"
                        wire:click="approveSelected"
                    >
                        <x-slot name="icon">
                            <x-heroicon-o-sparkles class="w-5 h-5" />
                        </x-slot>
                        Approve Selected
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
