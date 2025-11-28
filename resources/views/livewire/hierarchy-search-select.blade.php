<div x-data="{
    showDropdown: @entangle('showDropdown'),
    selectedIndex: @entangle('selectedIndex')
}"
    x-on:click.away="showDropdown = false"
    class="relative">

    <!-- Selected Value Display or Search Input -->
    @if($selectedPath)
        <div class="flex items-center gap-2 px-3 py-2 text-sm border border-gray-300 rounded-md bg-white dark:bg-gray-900 dark:border-gray-600 dark:text-white">
            <div class="flex-1">
                <span class="text-gray-500 dark:text-gray-400 text-xs">Selected Location:</span>
                <div class="font-medium">{{ $selectedPath }}</div>
            </div>
            <button
                type="button"
                wire:click="clearSelection"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                title="Clear selection">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    @else
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                x-on:focus="showDropdown = ($wire.search.length >= 2)"
                x-on:keydown.escape="showDropdown = false; $wire.search = ''"
                x-on:keydown.arrow-down.prevent="selectedIndex = Math.min(selectedIndex + 1, {{ max(0, ($searchResults?->count() ?? 1) - 1) }})"
                x-on:keydown.arrow-up.prevent="selectedIndex = Math.max(selectedIndex - 1, -1)"
                x-on:keydown.enter.prevent="if(selectedIndex >= 0) { $wire.selectResult(selectedIndex) }"
                placeholder="🔍 Search rooms, locations, cabinet runs, or cabinets..."
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white pl-10"
            >
            <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>
    @endif

    <!-- Dropdown Results -->
    <div
        x-show="showDropdown && !@js($selectedPath)"
        x-transition
        class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-96 overflow-y-auto">

        <!-- Recently Used Section -->
        @if(isset($recentlyUsed) && $recentlyUsed->isNotEmpty() && strlen($search) < 2)
            <div class="p-2 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                        📌 Recently Used
                    </span>
                    <button
                        type="button"
                        wire:click="clearRecentlyUsed"
                        class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        Clear
                    </button>
                </div>
                <div class="space-y-1">
                    @foreach($recentlyUsed as $index => $recent)
                        <button
                            type="button"
                            wire:click="selectRecent({{ $index }})"
                            class="w-full text-left px-3 py-2 text-sm rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <div class="flex items-center gap-2">
                                <span>{{ $recent['icon'] ?? '📍' }}</span>
                                <span class="flex-1 text-gray-900 dark:text-gray-100">{{ $recent['display_path'] }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Search Results -->
        @if(strlen($search) >= 2)
            @if(!isset($searchResults) || $searchResults->isEmpty())
                <div class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>No results found for "{{ $search }}"</p>
                    <p class="text-xs mt-1">Try a different search term</p>
                </div>
            @elseif(isset($searchResults) && $searchResults->isNotEmpty())
                <div class="p-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 px-2">
                        Found {{ $searchResults->count() }} {{ Str::plural('result', $searchResults->count()) }}
                    </div>

                    <!-- Group by Project -->
                    @php
                        $groupedResults = $searchResults->groupBy('project_name');
                    @endphp

                    @foreach($groupedResults as $projectName => $results)
                        <div class="mb-3">
                            <div class="px-2 py-1 text-xs font-semibold text-primary-600 dark:text-primary-400 uppercase tracking-wide">
                                {{ $projectName }}
                            </div>
                            <div class="space-y-1">
                                @foreach($results as $index => $result)
                                    <button
                                        type="button"
                                        wire:click="selectResult({{ $searchResults->search($result) }})"
                                        x-bind:class="{
                                            'bg-primary-100 dark:bg-primary-900': selectedIndex === {{ $searchResults->search($result) }},
                                            'hover:bg-gray-100 dark:hover:bg-gray-700': selectedIndex !== {{ $searchResults->search($result) }}
                                        }"
                                        class="w-full text-left px-3 py-2 text-sm rounded-md transition-colors">
                                        <div class="flex items-start gap-2">
                                            <span class="text-lg">{{ $result['icon'] }}</span>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $result['name'] }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                    {{ $result['display_path'] }}
                                                </div>
                                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                                    {{ ucfirst(str_replace('_', ' ', $result['level'])) }}
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @elseif(!isset($recentlyUsed) || $recentlyUsed->isEmpty())
            <div class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <p>Start typing to search...</p>
                <p class="text-xs mt-1">Search rooms, locations, runs, or cabinets</p>
            </div>
        @endif
    </div>

    <!-- Helper Text -->
    @if(!$selectedPath && strlen($search) < 2)
        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            💡 Type to search across projects, rooms, locations, cabinet runs, and cabinets
        </div>
    @endif
</div>
