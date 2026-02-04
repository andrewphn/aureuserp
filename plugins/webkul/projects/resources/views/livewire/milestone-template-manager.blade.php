<div class="p-4" wire:ignore.self>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Milestone Templates</h2>
        <a href="{{ route('filament.admin.project.configurations.resources.milestone-templates.create') }}"
           class="fi-btn fi-btn-size-md fi-btn-color-primary inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
            <x-heroicon-m-plus class="h-5 w-5" />
            New Template
        </a>
    </div>

    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        Drag and drop templates to reorder or move between stages.
    </p>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3" id="milestone-template-grid">
        @foreach($stages as $stageKey => $stageLabel)
            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                {{-- Stage Header --}}
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium
                            @if($stageKey === 'discovery') bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300
                            @elseif($stageKey === 'design') bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300
                            @elseif($stageKey === 'sourcing') bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300
                            @elseif($stageKey === 'production') bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300
                            @elseif($stageKey === 'delivery') bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300
                            @else bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300
                            @endif
                        ">
                            {{ $stageLabel }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400" data-count-for="{{ $stageKey }}">
                            ({{ $templatesByStage[$stageKey]->count() }} templates)
                        </span>
                    </div>
                </div>

                {{-- Templates List - Sortable Container --}}
                <div class="min-h-[100px] p-2 sortable-stage" data-stage="{{ $stageKey }}">
                    @forelse($templatesByStage[$stageKey] as $template)
                        <div
                            data-template-id="{{ $template->id }}"
                            class="group mb-2 cursor-grab rounded-lg border border-gray-200 bg-gray-50 p-3 transition hover:border-primary-300 hover:bg-primary-50 active:cursor-grabbing dark:border-gray-600 dark:bg-gray-700 dark:hover:border-primary-600 dark:hover:bg-gray-600"
                        >
                            <div class="flex items-start gap-3">
                                {{-- Drag Handle --}}
                                <div class="mt-0.5 text-gray-400 group-hover:text-gray-600 dark:text-gray-500 dark:group-hover:text-gray-300 drag-handle">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                                    </svg>
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        @if($template->is_critical)
                                            <x-heroicon-s-exclamation-triangle class="h-4 w-4 text-danger-500" />
                                        @endif
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ $template->name }}
                                        </span>
                                        @unless($template->is_active)
                                            <span class="rounded bg-gray-200 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-600 dark:text-gray-300">
                                                Inactive
                                            </span>
                                        @endunless
                                    </div>
                                    @if($template->description)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                            {{ $template->description }}
                                        </p>
                                    @endif
                                    <div class="mt-2 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                        <span>Day {{ $template->relative_days }}</span>
                                        <span>•</span>
                                        <span>Order: {{ $template->sort_order }}</span>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('filament.admin.project.configurations.resources.milestone-templates.edit', $template) }}"
                                       class="rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-300"
                                       title="Edit">
                                        <x-heroicon-m-pencil-square class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-placeholder flex h-20 items-center justify-center rounded-lg border-2 border-dashed border-gray-300 text-sm text-gray-400 dark:border-gray-600">
                            Drop templates here
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- Sortable.js Script --}}
    <script>
        document.addEventListener('livewire:navigated', initMilestoneTemplateSortable);
        document.addEventListener('DOMContentLoaded', initMilestoneTemplateSortable);

        function initMilestoneTemplateSortable() {
            // Check if Sortable is available (loaded by filament-kanban package)
            if (typeof Sortable === 'undefined') {
                console.warn('Sortable.js not loaded, loading from CDN...');
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
                script.onload = setupSortables;
                document.head.appendChild(script);
            } else {
                setupSortables();
            }
        }

        function setupSortables() {
            const stages = document.querySelectorAll('.sortable-stage');

            stages.forEach(stageEl => {
                // Destroy existing sortable if it exists
                if (stageEl.sortable) {
                    stageEl.sortable.destroy();
                }

                stageEl.sortable = Sortable.create(stageEl, {
                    group: 'milestone-templates',
                    animation: 150,
                    ghostClass: 'opacity-50',
                    chosenClass: 'ring-2 ring-primary-500',
                    dragClass: 'shadow-lg',
                    handle: '.drag-handle',
                    filter: '.empty-placeholder',

                    onStart(e) {
                        document.body.classList.add('grabbing');
                        // Hide empty placeholders when dragging
                        document.querySelectorAll('.empty-placeholder').forEach(el => {
                            el.style.display = 'none';
                        });
                    },

                    onEnd(e) {
                        document.body.classList.remove('grabbing');
                        // Show empty placeholders again
                        document.querySelectorAll('.empty-placeholder').forEach(el => {
                            el.style.display = '';
                        });
                    },

                    onAdd(e) {
                        // Template moved to a different stage
                        const templateId = e.item.dataset.templateId;
                        const newStage = e.to.dataset.stage;
                        const newIndex = e.newIndex;

                        // Call Livewire method to move template
                        @this.moveTemplate(parseInt(templateId), newStage, newIndex);
                    },

                    onUpdate(e) {
                        // Template reordered within same stage
                        const stage = e.from.dataset.stage;
                        const orderedIds = Array.from(e.from.children)
                            .filter(el => el.dataset.templateId)
                            .map(el => el.dataset.templateId);

                        // Call Livewire method to update order
                        @this.updateTemplateOrder(orderedIds, stage);
                    }
                });
            });
        }
    </script>

    <style>
        body.grabbing,
        body.grabbing * {
            cursor: grabbing !important;
        }
        .sortable-ghost {
            opacity: 0.5;
        }
        .sortable-chosen {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</div>
