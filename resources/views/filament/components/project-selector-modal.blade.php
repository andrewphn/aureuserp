{{--
    Project Selector Modal
    ADHD-Friendly project switcher for Bryan Patton
    Displays filtered, prioritized project list with visual health indicators
--}}

<div
    x-data="projectSelector()"
    x-cloak
    @keydown.escape.window="closeModal()"
>
    {{-- Overlay --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeModal()"
        class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm"
        style="display: none;"
    ></div>

    {{-- Modal --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <div
            @click.away="closeModal()"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Select Project</h3>
                </div>
                <button
                    @click="closeModal()"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            {{-- Search Bar --}}
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input
                        type="text"
                        x-model="searchQuery"
                        @input="filterProjects()"
                        placeholder="Search by project number, customer, or address..."
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-gray-100"
                    />
                </div>
            </div>

            {{-- Project List --}}
            <div class="flex-1 overflow-y-auto px-6 py-4">
                {{-- Loading State --}}
                <template x-if="loading">
                    <div class="flex items-center justify-center py-12">
                        <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </template>

                {{-- Empty State --}}
                <template x-if="!loading && filteredProjects.length === 0">
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No projects found</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1" x-show="searchQuery">Try adjusting your search</p>
                    </div>
                </template>

                {{-- Projects Needing Attention --}}
                <template x-if="!loading && criticalProjects.length > 0">
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="flex items-center justify-center w-5 h-5 rounded-full bg-red-100 dark:bg-red-900/30">
                                <svg class="w-3 h-3 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                Needs Attention (<span x-text="criticalProjects.length"></span>)
                            </h4>
                        </div>
                        <div class="space-y-2">
                            <template x-for="project in criticalProjects" :key="project.id">
                                <button
                                    @click="selectProject(project.id)"
                                    class="w-full text-left p-3 rounded-lg border-2 border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors group"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="flex-shrink-0 px-2 py-0.5 text-[10px] font-bold bg-red-600 text-white rounded uppercase">Critical</span>
                                                <span class="font-semibold text-gray-900 dark:text-gray-100 truncate" x-text="project.project_number"></span>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 truncate" x-text="project.customer_name"></p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate mt-0.5" x-text="project.address"></p>
                                            <div class="flex items-center gap-3 mt-2 text-xs">
                                                <span class="text-red-700 dark:text-red-300 font-medium" x-show="project.budget_variance" x-text="'Budget: ' + project.budget_variance"></span>
                                                <span class="text-red-700 dark:text-red-300 font-medium" x-show="project.schedule_variance" x-text="'Schedule: ' + project.schedule_variance"></span>
                                            </div>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Pinned Projects --}}
                <template x-if="!loading && pinnedProjects.length > 0">
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                            </svg>
                            <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                Pinned (<span x-text="pinnedProjects.length"></span>)
                            </h4>
                        </div>
                        <div class="space-y-2">
                            <template x-for="project in pinnedProjects" :key="project.id">
                                <button
                                    @click="selectProject(project.id)"
                                    class="w-full text-left p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-amber-50 dark:bg-amber-900/10 hover:bg-amber-100 dark:hover:bg-amber-900/20 transition-colors group"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="font-semibold text-gray-900 dark:text-gray-100 truncate" x-text="project.project_number"></span>
                                                <span x-show="project.status_indicator" class="flex-shrink-0 w-2 h-2 rounded-full" :class="statusColors[project.status_indicator]"></span>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 truncate" x-text="project.customer_name"></p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate mt-0.5" x-text="project.address"></p>
                                        </div>
                                        <button
                                            @click.stop="unpinProject(project.id)"
                                            class="flex-shrink-0 text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300"
                                            title="Unpin"
                                        >
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Recent Projects --}}
                <template x-if="!loading && recentProjects.length > 0">
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                Recent (<span x-text="recentProjects.length"></span>)
                            </h4>
                        </div>
                        <div class="space-y-2">
                            <template x-for="project in recentProjects" :key="project.id">
                                <button
                                    @click="selectProject(project.id)"
                                    class="w-full text-left p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="font-semibold text-gray-900 dark:text-gray-100 truncate" x-text="project.project_number"></span>
                                                <span x-show="project.status_indicator" class="flex-shrink-0 w-2 h-2 rounded-full" :class="statusColors[project.status_indicator]"></span>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 truncate" x-text="project.customer_name"></p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate mt-0.5" x-text="project.address"></p>
                                        </div>
                                        <button
                                            @click.stop="pinProject(project.id)"
                                            class="flex-shrink-0 text-gray-400 hover:text-amber-600 dark:hover:text-amber-400"
                                            title="Pin"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- All Projects (shown when searching OR when no categorized projects exist) --}}
                <template x-if="!loading && allProjects.length > 0">
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                            </svg>
                            <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                <span x-show="searchQuery">Search Results (<span x-text="allProjects.length"></span>)</span>
                                <span x-show="!searchQuery">All Projects (<span x-text="allProjects.length"></span>)</span>
                            </h4>
                        </div>
                        <div class="space-y-2">
                            <template x-for="project in allProjects" :key="project.id">
                                <button
                                    @click="selectProject(project.id)"
                                    class="w-full text-left p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="font-semibold text-gray-900 dark:text-gray-100 truncate" x-text="project.project_number"></span>
                                                <span x-show="project.status_indicator" class="flex-shrink-0 w-2 h-2 rounded-full" :class="statusColors[project.status_indicator]"></span>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 truncate" x-text="project.customer_name"></p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate mt-0.5" x-text="project.address"></p>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span>On Track</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                            <span>At Risk</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            <span>Off Track</span>
                        </div>
                    </div>
                    <span>Press <kbd class="px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 font-mono">ESC</kbd> to close</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function projectSelector() {
    return {
        isOpen: false,
        loading: false,
        searchQuery: '',
        projects: [],
        filteredProjects: [],
        pinnedProjectIds: [],
        recentProjectIds: [],

        statusColors: {
            'on_track': 'bg-green-500',
            'at_risk': 'bg-yellow-500',
            'off_track': 'bg-red-500',
            'not_started': 'bg-gray-400'
        },

        init() {
            // Load pinned projects from localStorage
            const stored = localStorage.getItem('pinned_projects');
            if (stored) {
                try {
                    this.pinnedProjectIds = JSON.parse(stored);
                } catch (e) {
                    this.pinnedProjectIds = [];
                }
            }

            // Load recent projects from localStorage
            const recentStored = localStorage.getItem('recent_projects');
            if (recentStored) {
                try {
                    this.recentProjectIds = JSON.parse(recentStored);
                } catch (e) {
                    this.recentProjectIds = [];
                }
            }

            // Listen for global event to open modal
            window.addEventListener('open-project-selector', () => {
                this.openModal();
            });
        },

        async openModal() {
            this.isOpen = true;
            this.searchQuery = '';
            await this.loadProjects();
        },

        closeModal() {
            this.isOpen = false;
        },

        async loadProjects() {
            this.loading = true;
            try {
                // Fetch projects from API
                const response = await fetch('/api/projects/list');
                const data = await response.json();
                this.projects = data.projects || [];
                this.filterProjects();
            } catch (e) {
                console.error('Failed to load projects:', e);
                this.projects = [];
                this.filteredProjects = [];
            } finally {
                this.loading = false;
            }
        },

        filterProjects() {
            const query = this.searchQuery.toLowerCase().trim();

            if (!query) {
                // No search - show only categorized lists
                // BUT if there are no categorized projects, show all projects as default
                const hasCategorizedProjects = this.criticalProjects.length > 0 ||
                                               this.pinnedProjects.length > 0 ||
                                               this.recentProjects.length > 0;

                this.filteredProjects = hasCategorizedProjects ? [] : this.projects;
                return;
            }

            // Filter all projects by search query
            this.filteredProjects = this.projects.filter(project => {
                return (
                    (project.project_number && project.project_number.toLowerCase().includes(query)) ||
                    (project.customer_name && project.customer_name.toLowerCase().includes(query)) ||
                    (project.address && project.address.toLowerCase().includes(query))
                );
            });
        },

        get criticalProjects() {
            return this.projects.filter(p => p.is_critical || p.status_indicator === 'off_track');
        },

        get pinnedProjects() {
            return this.projects.filter(p => this.pinnedProjectIds.includes(p.id));
        },

        get recentProjects() {
            // Get projects matching recentProjectIds in order, excluding pinned
            return this.recentProjectIds
                .map(id => this.projects.find(p => p.id === id))
                .filter(p => p && !this.pinnedProjectIds.includes(p.id))
                .slice(0, 10);
        },

        get allProjects() {
            return this.filteredProjects;
        },

        async selectProject(projectId) {
            try {
                // Fetch full project details from API
                const response = await fetch(`/api/projects/${projectId}`);
                if (!response.ok) {
                    console.error('Failed to fetch project details');
                    return;
                }
                const projectData = await response.json();

                // Store project data in EntityStore
                Alpine.store('entityStore').setEntity('project', projectId, projectData);

                // Add to recent projects
                this.addToRecent(projectId);

                // Set active context (this will trigger the footer update)
                Alpine.store('entityStore').setActiveContext('project', projectId);

                // Close modal
                this.closeModal();
            } catch (e) {
                console.error('Failed to select project:', e);
            }
        },

        pinProject(projectId) {
            if (!this.pinnedProjectIds.includes(projectId)) {
                this.pinnedProjectIds.push(projectId);
                localStorage.setItem('pinned_projects', JSON.stringify(this.pinnedProjectIds));
            }
        },

        unpinProject(projectId) {
            this.pinnedProjectIds = this.pinnedProjectIds.filter(id => id !== projectId);
            localStorage.setItem('pinned_projects', JSON.stringify(this.pinnedProjectIds));
        },

        addToRecent(projectId) {
            // Remove if already exists
            this.recentProjectIds = this.recentProjectIds.filter(id => id !== projectId);

            // Add to beginning
            this.recentProjectIds.unshift(projectId);

            // Keep only last 20
            this.recentProjectIds = this.recentProjectIds.slice(0, 20);

            // Save to localStorage
            localStorage.setItem('recent_projects', JSON.stringify(this.recentProjectIds));
        }
    };
}
</script>
