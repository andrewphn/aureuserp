<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }} || [],
            searchQuery: '',
            expandedSections: {},

            toggleTag(tagId) {
                if (this.state.includes(tagId)) {
                    this.state = this.state.filter(id => id !== tagId)
                } else {
                    this.state = [...this.state, tagId]
                }
            },

            toggleSection(type) {
                this.expandedSections[type] = !this.expandedSections[type]
            },

            filteredTags(tags) {
                if (!this.searchQuery) return tags;
                const query = this.searchQuery.toLowerCase();
                return tags.filter(tag =>
                    tag.name.toLowerCase().includes(query) ||
                    (tag.type && tag.type.toLowerCase().includes(query))
                );
            },

            hasSearchResults(tags) {
                if (!this.searchQuery) return false;
                return this.filteredTags(tags).length > 0;
            },

            getTagName(tagId) {
                @foreach($getTagsByType() as $type => $tags)
                    @foreach($tags as $tag)
                        if (tagId === {{ $tag['id'] }}) return '{{ $tag['name'] }}';
                    @endforeach
                @endforeach
                return 'Tag #' + tagId;
            }
        }"
    >
        <!-- Search Bar -->
        <div class="mb-3">
            <input
                type="text"
                x-model="searchQuery"
                placeholder="🔍 Search tags..."
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white"
            >
        </div>

        <!-- Selected Tags -->
        <div x-show="state.length > 0" class="mb-3 p-2 bg-gray-50 dark:bg-gray-800 rounded-md">
            <div class="flex flex-wrap gap-1">
                <template x-for="tagId in state" :key="tagId">
                    <button
                        type="button"
                        @click="toggleTag(tagId)"
                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-primary-100 text-primary-800 rounded-md hover:bg-primary-200 dark:bg-primary-900 dark:text-primary-200 dark:hover:bg-primary-800 transition-colors"
                    >
                        <span x-text="getTagName(tagId)"></span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </template>
            </div>
        </div>

        <!-- Helper text -->
        <div class="mb-3 text-xs text-gray-500 dark:text-gray-400" x-show="!searchQuery">
            <p>💡 Browse by category below, or search to filter tags</p>
        </div>

        <!-- Tags by Category -->
        <div class="space-y-2 max-h-96 overflow-y-auto">
            @foreach($getTagsByType() as $type => $tags)
                @php
                    $typeInfo = $getTypeLabels()[$type] ?? ['label' => $type, 'icon' => '📌'];
                @endphp

                @php
                    $tagsJson = json_encode($tags);
                @endphp

                <div
                    x-data="{
                        open: false,
                        init() {
                            // Watch for search query changes
                            this.$watch('searchQuery', value => {
                                if (value && hasSearchResults({{ $tagsJson }})) {
                                    this.open = true;
                                } else if (!value) {
                                    this.open = false;
                                }
                            });
                        }
                    }"
                    x-show="!searchQuery || hasSearchResults({{ $tagsJson }})"
                >
                    <button
                        type="button"
                        @click="open = !open"
                        x-show="!searchQuery"
                        class="w-full flex items-center justify-between text-left mb-2 text-xs font-semibold text-gray-700 uppercase tracking-wide dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                    >
                        <span class="flex items-center gap-2">
                            <span>{{ $typeInfo['icon'] }} {{ $typeInfo['label'] }}</span>
                            <span x-show="!open" class="inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-medium bg-gray-200 text-gray-700 rounded dark:bg-gray-700 dark:text-gray-300">
                                {{ count($tags) }}
                            </span>
                        </span>
                        <svg x-show="!open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                        <svg x-show="open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                        </svg>
                    </button>

                    <!-- Show category label when searching -->
                    <div x-show="searchQuery" class="mb-2 text-xs font-semibold text-primary-600 dark:text-primary-400 uppercase tracking-wide">
                        {{ $typeInfo['icon'] }} {{ $typeInfo['label'] }}
                    </div>

                    <div x-show="open || searchQuery" x-collapse>
                        <div class="flex flex-wrap gap-1 mb-3">
                            <template x-for="tag in filteredTags({{ $tagsJson }})" :key="tag.id">
                                <button
                                    type="button"
                                    @click="toggleTag(tag.id)"
                                    :class="{
                                        'bg-primary-500 text-white ring-2 ring-primary-500': state.includes(tag.id),
                                        'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700': !state.includes(tag.id)
                                    }"
                                    class="px-2 py-1 text-xs font-medium border border-gray-300 dark:border-gray-600 rounded-md transition-all"
                                    :style="tag.color ? 'border-left: 3px solid ' + tag.color : ''"
                                    :title="tag.description"
                                    x-text="tag.name"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-dynamic-component>
