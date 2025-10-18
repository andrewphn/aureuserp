<div wire:ignore>
    @if($allTags->count() > 0)
    <div
        class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-300 dark:border-blue-700 rounded-lg p-4"
        x-data="{
            selectedTags: @entangle('selectedTags'),
            searchQuery: @entangle('searchQuery'),
            expandedSections: @entangle('expandedSections'),
            saving: @entangle('saving'),

            toggleTag(tagId) {
                if (this.selectedTags.includes(tagId)) {
                    this.selectedTags = this.selectedTags.filter(id => id !== tagId);
                } else {
                    this.selectedTags.push(tagId);
                }
                this.saveTags();
            },

            toggleSection(type) {
                this.expandedSections[type] = !this.expandedSections[type];
            },

            saveTags() {
                this.saving = true;
                @this.call('saveTags');
            }
        }"
        x-on:tags-saved.window="
            console.log('Tags saved successfully:', $event.detail);
            new Filament.Notification()
                .title($event.detail.message)
                .success()
                .send();
        "
        x-on:tags-error.window="
            console.error('Error saving tags:', $event.detail);
            new Filament.Notification()
                .title($event.detail.message)
                .danger()
                .send();
        "
    >
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-blue-900 dark:text-blue-300 flex items-center gap-2">
                🏷️ Project Tags
            </h3>
            <div x-show="saving" class="text-xs text-blue-600 dark:text-blue-400">
                Saving...
            </div>
        </div>

        <!-- Search -->
        <div class="mb-3">
            <input
                type="text"
                x-model="searchQuery"
                wire:model.live="searchQuery"
                placeholder="Search tags..."
                class="w-full px-3 py-2 text-sm border border-blue-300 dark:border-blue-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            />
        </div>

        <!-- Selected Tags Display -->
        <div x-show="selectedTags.length > 0" class="mb-3 flex flex-wrap gap-2">
            @foreach($allTags as $type => $tags)
                @foreach($tags as $tag)
                    <span
                        x-show="selectedTags.includes({{ $tag->id }})"
                        @click="toggleTag({{ $tag->id }})"
                        class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full cursor-pointer transition-all hover:opacity-80"
                        style="background-color: {{ $tag->color }}20; border: 1px solid {{ $tag->color }}; color: {{ $tag->color }};"
                    >
                        <span>{{ $tag->name }}</span>
                        <span>×</span>
                    </span>
                @endforeach
            @endforeach
        </div>

        <!-- Tag Groups -->
        <div class="space-y-2 max-h-96 overflow-y-auto">
            @foreach($allTags as $type => $tags)
                @php
                    $typeLabels = [
                        'priority' => ['label' => 'Priority', 'icon' => '🎯'],
                        'health' => ['label' => 'Health Status', 'icon' => '💚'],
                        'risk' => ['label' => 'Risk Factors', 'icon' => '⚠️'],
                        'complexity' => ['label' => 'Complexity', 'icon' => '📊'],
                        'work_scope' => ['label' => 'Work Scope', 'icon' => '🔨'],
                        'phase_discovery' => ['label' => 'Discovery Phase', 'icon' => '🔍'],
                        'phase_design' => ['label' => 'Design Phase', 'icon' => '🎨'],
                        'phase_sourcing' => ['label' => 'Sourcing Phase', 'icon' => '📦'],
                        'phase_production' => ['label' => 'Production Phase', 'icon' => '⚙️'],
                        'phase_delivery' => ['label' => 'Delivery Phase', 'icon' => '🚚'],
                        'special_status' => ['label' => 'Special Status', 'icon' => '⭐'],
                        'lifecycle' => ['label' => 'Lifecycle', 'icon' => '🔄'],
                    ];
                    $typeInfo = $typeLabels[$type] ?? ['label' => ucfirst(str_replace('_', ' ', $type)), 'icon' => '🏷️'];
                @endphp
                <div class="border border-blue-200 dark:border-blue-700 rounded-lg overflow-hidden">
                    <!-- Category Header -->
                    <button
                        @click="toggleSection('{{ $type }}')"
                        class="w-full px-3 py-2 bg-blue-100 dark:bg-blue-800/30 hover:bg-blue-150 dark:hover:bg-blue-800/50 transition-colors flex items-center justify-between text-left"
                    >
                        <span class="text-sm font-medium text-blue-900 dark:text-blue-200">
                            {{ $typeInfo['icon'] }} {{ $typeInfo['label'] }}
                        </span>
                        <svg
                            class="w-4 h-4 text-blue-600 dark:text-blue-300 transition-transform"
                            :class="{ 'rotate-180': expandedSections['{{ $type }}'] }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Tags List with optimized x-collapse -->
                    <div
                        x-show="expandedSections['{{ $type }}']"
                        x-collapse.duration.300ms
                        class="bg-white dark:bg-gray-800 p-2"
                    >
                        <div class="flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <button
                                    @click="toggleTag({{ $tag->id }})"
                                    :class="selectedTags.includes({{ $tag->id }}) ? 'ring-2 ring-offset-1' : ''"
                                    class="px-3 py-1.5 text-xs rounded-full transition-all hover:scale-105"
                                    style="background-color: {{ $tag->color }}20; border: 1px solid {{ $tag->color }}; color: {{ $tag->color }};"
                                    x-show="!searchQuery || '{{ strtolower($tag->name) }}'.includes(searchQuery.toLowerCase())"
                                >
                                    {{ $tag->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="p-4 text-center text-gray-500">
        <p class="text-sm">No tags available</p>
    </div>
    @endif
</div>
