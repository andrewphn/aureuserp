@php
    use Illuminate\Support\Facades\Cache;
    use Webkul\Project\Models\Tag;

    // Cache all tags for 1 hour for performance
    $allTags = Cache::remember('project_tags_grouped', 3600, function() {
        return Tag::all()->groupBy('type');
    });

    // Map stage IDs to tag types
    $stageToTagType = [
        13 => 'phase_discovery',
        14 => 'phase_design',
        15 => 'phase_sourcing',
        16 => 'phase_production',
        17 => 'phase_delivery',
    ];

    // Get current project's stage - simplify to avoid Livewire errors
    $currentStageId = null;
    // For now, tags will work but current phase won't be auto-detected
    // This can be enhanced later with proper Livewire integration

    $currentPhaseType = $stageToTagType[$currentStageId] ?? null;
    $currentPhaseTags = $currentPhaseType ? ($allTags->get($currentPhaseType) ?? collect()) : collect();

    // Type labels with emojis
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

    // Get phase color from stage
    $phaseColor = null;
    if ($currentStageId) {
        $stage = \Webkul\Project\Models\ProjectStage::find($currentStageId);
        $phaseColor = $stage?->color;
    }
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }} || [],
            panelOpen: false,
            searchQuery: '',
            expandedSections: {},

            toggleTag(tagId) {
                if (this.state.includes(tagId)) {
                    this.state = this.state.filter(id => id !== tagId);
                } else {
                    this.state.push(tagId);
                }
                this.saveRecentTag(tagId);
            },

            saveRecentTag(tagId) {
                const tag = this.getTagById(tagId);
                if (!tag) return;

                const userId = {{ auth()->id() ?? 1 }};
                const storageKey = `tcs_recent_tags_user_${userId}`;
                let recent = JSON.parse(localStorage.getItem(storageKey) || '[]');

                // Remove if already exists
                recent = recent.filter(t => t.id !== tagId);

                // Add to front
                recent.unshift({
                    id: tagId,
                    name: tag.name,
                    color: tag.color
                });

                // Keep last 5
                recent = recent.slice(0, 5);

                localStorage.setItem(storageKey, JSON.stringify(recent));
            },

            getRecentTags() {
                const userId = {{ auth()->id() ?? 1 }};
                const storageKey = `tcs_recent_tags_user_${userId}`;
                return JSON.parse(localStorage.getItem(storageKey) || '[]');
            },

            getTagById(tagId) {
                const allTags = @js($allTags->flatten()->values());
                return allTags.find(t => t.id === tagId);
            },

            filteredTags(tags) {
                if (!this.searchQuery) return tags;
                const query = this.searchQuery.toLowerCase();
                return tags.filter(tag => tag.name.toLowerCase().includes(query));
            },

            toggleSection(section) {
                this.expandedSections[section] = !this.expandedSections[section];
            },

            highlightMatch(text) {
                if (!this.searchQuery) return text;
                const regex = new RegExp(`(${this.searchQuery})`, 'gi');
                return text.replace(regex, '<mark class=\"bg-yellow-200 dark:bg-yellow-800\">$1</mark>');
            }
        }"
        x-init="$watch('panelOpen', value => { if (value) $nextTick(() => $refs.searchInput?.focus()) })"
    >
        <!-- Trigger Button -->
        <button
            type="button"
            @click="panelOpen = true"
            class="w-full px-4 py-2 text-left bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-gray-400 dark:hover:border-gray-500 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-600"
        >
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    <span x-show="state.length === 0">Select Tags</span>
                    <span x-show="state.length > 0" x-text="`Select Tags (${state.length} selected)`"></span>
                </span>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
        </button>

        <!-- Slide-in Panel -->
        <div
            x-show="panelOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="panelOpen = false"
            class="fixed inset-0 z-50 bg-black/50"
            style="display: none;"
        >
            <div
                @click.stop
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="absolute right-0 top-0 h-full w-full md:w-[400px] bg-white dark:bg-gray-900 shadow-xl flex flex-col"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Select Tags</h3>
                    <button
                        type="button"
                        @click="panelOpen = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="relative">
                        <input
                            x-ref="searchInput"
                            x-model="searchQuery"
                            type="text"
                            placeholder="Search tags..."
                            class="w-full pl-10 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-600 focus:border-transparent"
                        >
                        <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <button
                            x-show="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Scrollable Content -->
                <div class="flex-1 overflow-y-auto px-4 py-3 space-y-4">
                    @if($currentPhaseTags->isNotEmpty())
                        <!-- Current Phase Section (Always Visible) -->
                        <div>
                            <h4 class="text-sm font-semibold mb-2 flex items-center gap-2" style="color: {{ $phaseColor ?? '#6366F1' }}">
                                <span>⭐ CURRENT PHASE →</span>
                                <span>{{ $typeLabels[$currentPhaseType]['icon'] ?? '' }}</span>
                                <span>{{ $typeLabels[$currentPhaseType]['label'] ?? ucfirst($currentPhaseType) }}</span>
                            </h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($currentPhaseTags as $tag)
                                    <button
                                        type="button"
                                        @click="toggleTag({{ $tag->id }})"
                                        :class="{
                                            'border-2': state.includes({{ $tag->id }}),
                                            'border': !state.includes({{ $tag->id }})
                                        }"
                                        style="
                                            background-color: {{ $tag->color }}20;
                                            border-color: {{ $tag->color }};
                                            color: {{ $tag->color }};
                                        "
                                        :style="{
                                            'background-color': state.includes({{ $tag->id }}) ? '{{ $tag->color }}40' : '{{ $tag->color }}20'
                                        }"
                                        class="px-3 py-1.5 rounded-lg text-sm font-medium hover:scale-102 transition-transform flex items-center gap-1"
                                    >
                                        <svg x-show="state.includes({{ $tag->id }})" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span x-html="highlightMatch('{{ $tag->name }}')">{{ $tag->name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Recent Tags Section -->
                    <div x-show="getRecentTags().length > 0">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">🕐 Recent Tags</h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="tag in getRecentTags()" :key="tag.id">
                                <button
                                    type="button"
                                    @click="toggleTag(tag.id)"
                                    :class="{
                                        'border-2': state.includes(tag.id),
                                        'border': !state.includes(tag.id)
                                    }"
                                    :style="{
                                        'background-color': state.includes(tag.id) ? tag.color + '40' : tag.color + '20',
                                        'border-color': tag.color,
                                        'color': tag.color
                                    }"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium hover:scale-102 transition-transform flex items-center gap-1"
                                >
                                    <svg x-show="state.includes(tag.id)" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span x-text="tag.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Other Tag Categories (Collapsed) -->
                    @foreach($allTags as $type => $tags)
                        @if($type !== $currentPhaseType)
                            <div>
                                <button
                                    type="button"
                                    @click="toggleSection('{{ $type }}')"
                                    class="w-full flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 hover:text-gray-900 dark:hover:text-gray-100"
                                >
                                    <span class="flex items-center gap-2">
                                        <span>{{ $typeLabels[$type]['icon'] ?? '📌' }}</span>
                                        <span>{{ $typeLabels[$type]['label'] ?? ucfirst($type) }}</span>
                                        <span class="text-xs text-gray-500">({{ $tags->count() }})</span>
                                    </span>
                                    <svg
                                        class="w-4 h-4 transition-transform"
                                        :class="{ 'rotate-180': expandedSections['{{ $type }}'] }"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="expandedSections['{{ $type }}']" x-collapse class="flex flex-wrap gap-2">
                                    @foreach($tags as $tag)
                                        <button
                                            type="button"
                                            @click="toggleTag({{ $tag->id }})"
                                            :class="{
                                                'border-2': state.includes({{ $tag->id }}),
                                                'border': !state.includes({{ $tag->id }})
                                            }"
                                            style="
                                                background-color: {{ $tag->color }}20;
                                                border-color: {{ $tag->color }};
                                                color: {{ $tag->color }};
                                            "
                                            :style="{
                                                'background-color': state.includes({{ $tag->id }}) ? '{{ $tag->color }}40' : '{{ $tag->color }}20'
                                            }"
                                            class="px-3 py-1.5 rounded-lg text-sm font-medium hover:scale-102 transition-transform flex items-center gap-1"
                                        >
                                            <svg x-show="state.includes({{ $tag->id }})" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <span x-html="highlightMatch('{{ $tag->name }}')">{{ $tag->name }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <!-- Footer with Selection Counter -->
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                <span x-show="state.length > 0">✓</span>
                                <span x-text="state.length"></span>
                                <span x-text="state.length === 1 ? 'tag' : 'tags'"></span>
                                <span>selected</span>
                            </span>
                            <div x-show="state.length > 5" class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                ⚠️ Many tags - consider simplifying
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="panelOpen = false"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium"
                        >
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
