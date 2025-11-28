@php
    use Webkul\Project\Models\Tag;
    use Illuminate\Support\Facades\Cache;

    // $field is the component instance in Filament blade views
    // Get all tags grouped by type
    $allTags = Tag::all()->groupBy('type');

    // Get most used tags
    $mostUsedTags = $field->getMostUsedTags(5);

    // Type labels with emojis
    $typeLabels = $field->getTypeLabels();

    // Category groups for browse popover
    $categoryGroups = $field->getCategoryGroups();

    // Prepare all tags as JSON for Alpine.js search
    $allTagsJson = $field->getAllTagsJson();

    // Prepare most used tags as JSON
    $mostUsedTagsJson = $field->getMostUsedTagsJson(5);

    // Calculate tag counts per category
    $categoryCounts = [];
    foreach ($categoryGroups as $catKey => $catInfo) {
        $count = 0;
        foreach ($catInfo['types'] as $type) {
            $count += $allTags->get($type, collect())->count();
        }
        $categoryCounts[$catKey] = $count;
    }
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            // State
            isDropdownOpen: false,
            isBrowseOpen: false,
            searchQuery: '',
            highlightedIndex: 0,
            filteredTags: [],

            // Data from PHP
            allTags: {{ Js::from(json_decode($allTagsJson, true)) }},
            mostUsedTags: {{ Js::from(json_decode($mostUsedTagsJson, true)) }},
            typeLabels: {{ Js::from($typeLabels) }},
            categoryGroups: {{ Js::from($categoryGroups) }},

            // Browse state
            selectedCategory: 'general',
            selectedType: null,

            init() {
                if (!Array.isArray($wire.{{ $getStatePath() }})) {
                    $wire.{{ $getStatePath() }} = [];
                }

                // Close dropdown when clicking outside
                this.$watch('isDropdownOpen', value => {
                    if (value) {
                        this.highlightedIndex = 0;
                    }
                });
            },

            // Get current selection
            getState() {
                return $wire.{{ $getStatePath() }} || [];
            },

            // Get selected tag objects
            getSelectedTags() {
                const selectedIds = this.getState();
                return this.allTags.filter(tag => selectedIds.includes(tag.id));
            },

            // Toggle tag selection
            toggleTag(tagId) {
                const state = this.getState();
                if (state.includes(tagId)) {
                    $wire.{{ $getStatePath() }} = state.filter(id => id !== tagId);
                } else {
                    $wire.{{ $getStatePath() }} = [...state, tagId];
                }
                // Clear search after selection
                this.searchQuery = '';
                this.isDropdownOpen = false;
            },

            // Check if tag is selected
            isSelected(tagId) {
                return this.getState().includes(tagId);
            },

            // Remove a specific tag
            removeTag(tagId) {
                $wire.{{ $getStatePath() }} = this.getState().filter(id => id !== tagId);
            },

            // Clear all tags
            clearAll() {
                $wire.{{ $getStatePath() }} = [];
            },

            // Search/filter tags
            search(query) {
                this.searchQuery = query;
                if (query.trim() === '') {
                    this.filteredTags = [];
                    return;
                }

                const q = query.toLowerCase();
                this.filteredTags = this.allTags
                    .filter(tag =>
                        tag.name.toLowerCase().includes(q) ||
                        (tag.type && tag.type.toLowerCase().includes(q)) ||
                        (tag.description && tag.description.toLowerCase().includes(q))
                    )
                    .slice(0, 10);

                // Sort by relevance (starts with > contains)
                this.filteredTags.sort((a, b) => {
                    const aStarts = a.name.toLowerCase().startsWith(q);
                    const bStarts = b.name.toLowerCase().startsWith(q);
                    if (aStarts && !bStarts) return -1;
                    if (!aStarts && bStarts) return 1;
                    return 0;
                });

                this.highlightedIndex = 0;
            },

            // Get tags to display in dropdown
            getDropdownTags() {
                if (this.searchQuery.trim() !== '') {
                    return this.filteredTags;
                }
                return this.mostUsedTags;
            },

            // Handle input focus
            handleFocus() {
                this.isDropdownOpen = true;
            },

            // Handle input blur (with delay to allow click)
            handleBlur() {
                setTimeout(() => {
                    if (!this.isBrowseOpen) {
                        this.isDropdownOpen = false;
                    }
                }, 200);
            },

            // Keyboard navigation
            handleKeydown(event) {
                const tags = this.getDropdownTags();

                switch(event.key) {
                    case '/':
                        if (this.searchQuery === '') {
                            event.preventDefault();
                            this.openBrowse();
                        }
                        break;
                    case 'ArrowDown':
                        event.preventDefault();
                        if (!this.isDropdownOpen) {
                            this.isDropdownOpen = true;
                        } else {
                            this.highlightedIndex = Math.min(this.highlightedIndex + 1, tags.length - 1);
                        }
                        break;
                    case 'ArrowUp':
                        event.preventDefault();
                        this.highlightedIndex = Math.max(this.highlightedIndex - 1, 0);
                        break;
                    case 'Enter':
                        event.preventDefault();
                        if (this.isDropdownOpen && tags[this.highlightedIndex]) {
                            this.toggleTag(tags[this.highlightedIndex].id);
                        }
                        break;
                    case 'Tab':
                        if (this.isDropdownOpen && tags[this.highlightedIndex]) {
                            event.preventDefault();
                            this.toggleTag(tags[this.highlightedIndex].id);
                        }
                        break;
                    case 'Escape':
                        this.isDropdownOpen = false;
                        this.isBrowseOpen = false;
                        break;
                    case 'Backspace':
                        if (this.searchQuery === '' && this.getState().length > 0) {
                            // Remove last tag
                            const state = this.getState();
                            $wire.{{ $getStatePath() }} = state.slice(0, -1);
                        }
                        break;
                }
            },

            // Open browse popover
            openBrowse() {
                this.isBrowseOpen = true;
                this.isDropdownOpen = false;
                this.selectedCategory = 'general';
                this.selectedType = null;
            },

            // Close browse popover
            closeBrowse() {
                this.isBrowseOpen = false;
            },

            // Select category in browse
            selectCategory(category) {
                this.selectedCategory = category;
                this.selectedType = null;
            },

            // Select type in browse
            selectType(type) {
                this.selectedType = type;
            },

            // Get tags for current browse view
            getBrowseTags() {
                if (!this.selectedType) return [];
                return this.allTags.filter(tag => tag.type === this.selectedType);
            },

            // Get types for current category
            getCategoryTypes() {
                if (!this.selectedCategory) return [];
                const category = this.categoryGroups[this.selectedCategory];
                if (!category) return [];
                return category.types.map(type => ({
                    key: type,
                    ...this.typeLabels[type] || { label: type, icon: '📌' },
                    count: this.allTags.filter(t => t.type === type).length
                }));
            }
        }"
        class="relative"
        @keydown.escape.window="isBrowseOpen = false"
    >
        {{-- Selected Tags (Above Input) --}}
        <div x-show="getState().length > 0" class="mb-2">
            <div class="flex flex-wrap items-center gap-2 p-2 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                <template x-for="tag in getSelectedTags()" :key="tag.id">
                    <span
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                        :style="`background-color: ${tag.color || '#6B7280'}20; color: ${tag.color || '#6B7280'}; border: 1px solid ${tag.color || '#6B7280'}40;`"
                    >
                        <span
                            x-show="tag.color"
                            class="w-2 h-2 rounded-full"
                            :style="`background-color: ${tag.color}`"
                        ></span>
                        <span x-text="tag.name"></span>
                        <button
                            type="button"
                            @click="removeTag(tag.id)"
                            class="ml-0.5 hover:opacity-70 focus:outline-none"
                        >
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </span>
                </template>

                {{-- Clear All Button --}}
                <button
                    type="button"
                    @click="clearAll()"
                    class="text-xs text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors"
                >
                    × Clear All
                </button>
            </div>
        </div>

        {{-- Search Input --}}
        <div class="relative">
            <div class="relative flex items-center">
                <span class="absolute left-3 text-gray-400 pointer-events-none">
                    🏷️
                </span>
                <input
                    type="text"
                    x-model="searchQuery"
                    @input="search($event.target.value)"
                    @focus="handleFocus()"
                    @blur="handleBlur()"
                    @keydown="handleKeydown($event)"
                    placeholder="Search tags..."
                    class="w-full pl-10 pr-24 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                />
                <button
                    type="button"
                    @click="openBrowse()"
                    class="absolute right-2 px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                >
                    Browse <span class="text-gray-400">/</span>
                </button>
            </div>

            {{-- Hint Text --}}
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                💡 Press <kbd class="px-1.5 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 rounded">/</kbd> to browse, or type to search
            </p>

            {{-- Autocomplete Dropdown --}}
            <div
                x-show="isDropdownOpen && !isBrowseOpen"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-64 overflow-y-auto"
                @click.outside="isDropdownOpen = false"
            >
                {{-- Section Header --}}
                <div class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                    <span x-show="searchQuery.trim() === ''" class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                        ⭐ Most Used
                    </span>
                    <span x-show="searchQuery.trim() !== ''" class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                        🔍 Search Results
                    </span>
                </div>

                {{-- Tags List --}}
                <div class="py-1">
                    <template x-for="(tag, index) in getDropdownTags()" :key="tag.id">
                        <button
                            type="button"
                            @click="toggleTag(tag.id)"
                            @mouseenter="highlightedIndex = index"
                            :class="{
                                'bg-primary-50 dark:bg-primary-900/20': highlightedIndex === index,
                                'bg-gray-50 dark:bg-gray-700/50': isSelected(tag.id) && highlightedIndex !== index
                            }"
                            class="w-full px-3 py-2 flex items-center justify-between text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="w-3 h-3 rounded-full flex-shrink-0"
                                    :style="`background-color: ${tag.color || '#6B7280'}`"
                                ></span>
                                <span class="text-sm text-gray-900 dark:text-white" x-text="tag.name"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="tag.typeLabel"></span>
                                <svg x-show="isSelected(tag.id)" class="w-4 h-4 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </button>
                    </template>

                    {{-- Empty State --}}
                    <div
                        x-show="searchQuery.trim() !== '' && filteredTags.length === 0"
                        class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400"
                    >
                        No tags found matching "<span x-text="searchQuery"></span>"
                    </div>

                    <div
                        x-show="searchQuery.trim() === '' && mostUsedTags.length === 0"
                        class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400 italic"
                    >
                        No frequently used tags yet
                    </div>
                </div>
            </div>

            {{-- Browse Popover (3-Column) --}}
            <div
                x-show="isBrowseOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl overflow-hidden"
                @click.outside="closeBrowse()"
            >
                {{-- Browse Header --}}
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Browse Tags</span>
                    <button
                        type="button"
                        @click="closeBrowse()"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- 3-Column Browser --}}
                <div class="flex h-72 divide-x divide-gray-200 dark:divide-gray-700">
                    {{-- Column 1: Categories --}}
                    <div class="w-1/4 overflow-y-auto">
                        <div class="py-1">
                            @foreach($categoryGroups as $catKey => $catInfo)
                                <button
                                    type="button"
                                    @click="selectCategory('{{ $catKey }}')"
                                    :class="{
                                        'bg-primary-50 dark:bg-primary-900/20 border-l-4 border-l-primary-600': selectedCategory === '{{ $catKey }}',
                                        'border-l-4 border-l-transparent hover:bg-gray-100 dark:hover:bg-gray-700': selectedCategory !== '{{ $catKey }}'
                                    }"
                                    class="w-full px-3 py-2.5 flex items-center justify-between text-left transition-colors"
                                >
                                    <span class="flex items-center gap-2">
                                        <span class="text-base">{{ $catInfo['icon'] }}</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $catInfo['label'] }}</span>
                                    </span>
                                    <span class="text-xs text-gray-500 bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ $categoryCounts[$catKey] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Column 2: Types --}}
                    <div class="w-1/3 overflow-y-auto">
                        <div class="py-1">
                            <template x-for="typeInfo in getCategoryTypes()" :key="typeInfo.key">
                                <button
                                    type="button"
                                    @click="selectType(typeInfo.key)"
                                    :class="{
                                        'bg-primary-50 dark:bg-primary-900/20 border-l-4 border-l-primary-600': selectedType === typeInfo.key,
                                        'border-l-4 border-l-transparent hover:bg-gray-100 dark:hover:bg-gray-700': selectedType !== typeInfo.key
                                    }"
                                    class="w-full px-3 py-2.5 flex items-center justify-between text-left transition-colors"
                                >
                                    <span class="flex items-center gap-2">
                                        <span class="text-base" x-text="typeInfo.icon"></span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300" x-text="typeInfo.label"></span>
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs text-gray-500 bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 rounded" x-text="typeInfo.count"></span>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Column 3: Tags --}}
                    <div class="flex-1 overflow-y-auto">
                        <template x-if="selectedType">
                            <div class="p-3">
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="tag in getBrowseTags()" :key="tag.id">
                                        <button
                                            type="button"
                                            @click="toggleTag(tag.id)"
                                            :class="{
                                                'ring-2 ring-primary-500 ring-offset-1': isSelected(tag.id),
                                            }"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-all hover:scale-105"
                                            :style="`background-color: ${tag.color || '#6B7280'}20; color: ${tag.color || '#6B7280'}; border: 1px solid ${tag.color || '#6B7280'}40;`"
                                        >
                                            <span
                                                x-show="tag.color"
                                                class="w-2 h-2 rounded-full"
                                                :style="`background-color: ${tag.color}`"
                                            ></span>
                                            <span x-text="tag.name"></span>
                                            <svg x-show="isSelected(tag.id)" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Empty State --}}
                        <template x-if="!selectedType">
                            <div class="flex items-center justify-center h-full text-gray-400 dark:text-gray-500">
                                <div class="text-center">
                                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    <p class="text-sm">Select a type to view tags</p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
