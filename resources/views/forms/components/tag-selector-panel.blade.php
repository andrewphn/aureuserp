<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }} || [],
            searchQuery: '',
            expandedSections: {},
            viewMode: localStorage.getItem('tagSelectorViewMode') || 'accordion',
            selectedType: null,
            selectedTag: null,

            toggleTag(tagId) {
                // Ensure state is always an array
                if (!Array.isArray(this.state)) {
                    this.state = [];
                }

                if (this.state.includes(tagId)) {
                    this.state = this.state.filter(id => id !== tagId)
                } else {
                    this.state = [...this.state, tagId]
                }
            },

            toggleSection(type) {
                this.expandedSections[type] = !this.expandedSections[type]
            },

            toggleViewMode() {
                this.viewMode = this.viewMode === 'accordion' ? 'columns' : 'accordion';
                localStorage.setItem('tagSelectorViewMode', this.viewMode);
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
                        if (tagId === {{ $tag['id'] }}) return {{ \Illuminate\Support\Js::from($tag['name']) }};
                    @endforeach
                @endforeach
                return 'Tag #' + tagId;
            },

            // Columns view functions
            selectType(type) {
                this.selectedType = type;
                this.selectedTag = null;
            },

            selectTagForPreview(tag) {
                this.selectedTag = tag;
            },

            getTypeData() {
                return @json($getTagsByType());
            },

            getTypeInfo(type) {
                const labels = @json($getTypeLabels());
                return labels[type] || { label: type, icon: '📌' };
            },

            getTagsForSelectedType() {
                if (!this.selectedType) return [];
                const typeData = this.getTypeData();
                return typeData[this.selectedType] || [];
            },

            getTagCount(type) {
                const typeData = this.getTypeData();
                return (typeData[type] || []).length;
            }
        }"
    >
        <!-- Search Bar and View Switcher -->
        <div class="mb-3 flex gap-2">
            <input
                type="text"
                x-model="searchQuery"
                placeholder="🔍 Search tags..."
                class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white"
            >
            <button
                type="button"
                @click="toggleViewMode()"
                class="px-3 py-2 text-sm font-medium border border-gray-300 rounded-md hover:bg-gray-100 dark:bg-gray-900 dark:border-gray-600 dark:text-white dark:hover:bg-gray-800 transition-colors"
                :title="viewMode === 'accordion' ? 'Switch to Columns View' : 'Switch to Accordion View'"
            >
                <svg x-show="viewMode === 'accordion'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
                <svg x-show="viewMode === 'columns'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <!-- Selected Tags -->
        <div x-show="state && state.length > 0" class="mb-3 p-2 bg-gray-50 dark:bg-gray-800 rounded-md">
            <div class="flex flex-wrap gap-1">
                <template x-for="tagId in (state || [])" :key="tagId">
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
        <div class="mb-3 text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between" x-show="!searchQuery">
            <p>💡 Browse by category below, or search to filter tags</p>
            <span class="text-xs font-medium" x-text="viewMode === 'accordion' ? 'Accordion View' : 'Columns View'"></span>
        </div>

        <!-- Tags by Category - Accordion View -->
        <div x-show="viewMode === 'accordion'" class="space-y-2 max-h-96 overflow-y-auto">
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
                                        'bg-primary-500 text-white ring-2 ring-primary-500': state && state.includes(tag.id),
                                        'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700': !state || !state.includes(tag.id)
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

        <!-- Tags by Category - Columns View (Mac Finder Style) -->
        <div
            x-show="viewMode === 'columns'"
            class="max-h-96 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
        >
            <!-- Mac-Style Column Layout -->
            <div class="flex h-96 overflow-x-auto">

                <!-- Column 1: Tag Types -->
                <div class="min-w-[200px] w-1/3 border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-y-auto">
                    <div class="p-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                        <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                            Categories
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($getTagsByType() as $type => $tags)
                            @php
                                $typeInfo = $getTypeLabels()[$type] ?? ['label' => $type, 'icon' => '📌'];
                            @endphp
                            <button
                                type="button"
                                @click="selectType('{{ $type }}')"
                                :class="{
                                    'bg-primary-50 dark:bg-primary-900/20 border-l-2 border-primary-500': selectedType === '{{ $type }}',
                                    'hover:bg-gray-50 dark:hover:bg-gray-700': selectedType !== '{{ $type }}'
                                }"
                                class="w-full px-3 py-2.5 text-left transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 flex-1 min-w-0">
                                        <span class="text-base">{{ $typeInfo['icon'] }}</span>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                {{ $typeInfo['label'] }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 ml-2">
                                        <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded dark:bg-gray-700 dark:text-gray-300">
                                            {{ count($tags) }}
                                        </span>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Column 2: Tags in Selected Type -->
                <div
                    x-show="selectedType"
                    class="min-w-[250px] w-1/3 border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-y-auto"
                >
                    <div class="p-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                        <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                            <span x-text="selectedType ? getTypeInfo(selectedType).label : 'Tags'"></span>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-for="tag in getTagsForSelectedType()" :key="tag.id">
                            <button
                                type="button"
                                @click="toggleTag(tag.id); selectTagForPreview(tag)"
                                :class="{
                                    'bg-primary-50 dark:bg-primary-900/20': selectedTag && selectedTag.id === tag.id,
                                    'bg-primary-100 dark:bg-primary-900/40 ring-2 ring-inset ring-primary-500': state && state.includes(tag.id),
                                    'hover:bg-gray-50 dark:hover:bg-gray-700': (!selectedTag || selectedTag.id !== tag.id) && (!state || !state.includes(tag.id))
                                }"
                                class="w-full px-3 py-2.5 text-left transition-colors relative"
                            >
                                <!-- Selection Indicator -->
                                <div
                                    x-show="state && state.includes(tag.id)"
                                    class="absolute left-0 top-0 bottom-0 w-1 bg-primary-500"
                                ></div>

                                <div class="flex items-start gap-2 pl-2">
                                    <!-- Color Indicator -->
                                    <div
                                        x-show="tag.color"
                                        class="w-3 h-3 rounded-full mt-0.5 flex-shrink-0"
                                        :style="tag.color ? 'background-color: ' + tag.color : ''"
                                    ></div>

                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-gray-100"
                                            x-text="tag.name"
                                        ></div>
                                        <div
                                            x-show="tag.description"
                                            class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1"
                                            x-text="tag.description"
                                        ></div>
                                    </div>

                                    <!-- Selected Checkmark -->
                                    <div
                                        x-show="state && state.includes(tag.id)"
                                        class="flex-shrink-0"
                                    >
                                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Column 3: Tag Preview/Details -->
                <div
                    x-show="selectedTag"
                    class="min-w-[250px] w-1/3 bg-gray-50 dark:bg-gray-900 overflow-y-auto"
                >
                    <div class="p-4">
                        <div class="mb-4">
                            <div class="flex items-center gap-2 mb-2">
                                <div
                                    x-show="selectedTag && selectedTag.color"
                                    class="w-4 h-4 rounded-full flex-shrink-0"
                                    :style="selectedTag && selectedTag.color ? 'background-color: ' + selectedTag.color : ''"
                                ></div>
                                <h3
                                    class="text-base font-semibold text-gray-900 dark:text-gray-100"
                                    x-text="selectedTag ? selectedTag.name : ''"
                                ></h3>
                            </div>

                            <div
                                x-show="selectedTag && selectedTag.description"
                                class="text-sm text-gray-600 dark:text-gray-400 mt-2"
                                x-text="selectedTag ? selectedTag.description : ''"
                            ></div>
                        </div>

                        <!-- Tag Actions -->
                        <div class="space-y-2">
                            <button
                                type="button"
                                @click="selectedTag && toggleTag(selectedTag.id)"
                                :class="{
                                    'bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/30': selectedTag && state && state.includes(selectedTag.id),
                                    'bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-300 dark:hover:bg-primary-900/30': !selectedTag || !state || !state.includes(selectedTag.id)
                                }"
                                class="w-full px-4 py-2 text-sm font-medium rounded-md transition-colors flex items-center justify-center gap-2"
                            >
                                <template x-if="selectedTag && state && state.includes(selectedTag.id)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </template>
                                <template x-if="!selectedTag || !state || !state.includes(selectedTag.id)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                </template>
                                <span x-text="selectedTag && state && state.includes(selectedTag.id) ? 'Remove Tag' : 'Add Tag'"></span>
                            </button>
                        </div>

                        <!-- Tag Metadata -->
                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100" x-text="selectedTag ? selectedTag.type : ''"></dd>
                                </div>
                                <div x-show="selectedTag && selectedTag.color">
                                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Color</dt>
                                    <dd class="mt-1 flex items-center gap-2">
                                        <div
                                            class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600"
                                            :style="selectedTag && selectedTag.color ? 'background-color: ' + selectedTag.color : ''"
                                        ></div>
                                        <span class="text-xs text-gray-600 dark:text-gray-400" x-text="selectedTag ? selectedTag.color : ''"></span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Empty State for Column 2 -->
                <div
                    x-show="!selectedType"
                    class="min-w-[250px] w-1/3 bg-white dark:bg-gray-800 flex items-center justify-center"
                >
                    <div class="text-center p-8">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Select a category to view tags</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-dynamic-component>
