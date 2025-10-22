{{--
    Global Context Sticky Footer
    Displays active context (project, sale, inventory, etc.) across all admin pages
    Updates in real-time as entity store changes
--}}

<div
    x-data="contextFooterGlobal()"
    x-cloak
    @active-context-changed.window="handleContextChange($event.detail)"
    @entity-updated.window="handleEntityUpdate($event.detail)"
    class="fi-section rounded-t-xl shadow-lg ring-1 ring-gray-950/10 dark:ring-white/10 transition-all duration-300 ease-in-out"
    :style="`position: fixed; bottom: 0; left: 0; right: 0; z-index: 50; backdrop-filter: blur(8px); background: linear-gradient(to right, rgb(249, 250, 251), rgb(243, 244, 246)); border-top: 3px solid ${contextConfig.borderColor}; transform: translateY(${isMinimized ? 'calc(100% - 44px)' : '0'})`"
>
    {{-- Toggle Button Bar - Context Aware --}}
    <div class="flex items-center justify-between px-3 sm:px-4 md:px-6 py-2 sm:py-2.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="isMinimized = !isMinimized">
        {{-- Context Info (Left side when minimized) - Fully Responsive --}}
        <div class="flex items-center gap-2 sm:gap-3 overflow-hidden min-w-0 flex-1">
            {{-- Dynamic Icon based on context type --}}
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 dark:text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="contextConfig.iconPath"></path>
            </svg>

            {{-- No Context State --}}
            <span x-show="!hasActiveContext" class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400" x-text="contextConfig.emptyLabel"></span>

            {{-- Active Context - Responsive Layout with Smart Truncation --}}
            <template x-if="hasActiveContext && preferencesLoaded">
                <div class="flex items-center gap-1.5 sm:gap-2 min-w-0 flex-1">
                    {{-- First Field: Truncate with ellipsis --}}
                    <div class="min-w-0 max-w-[120px] xs:max-w-[140px] sm:max-w-[180px] md:max-w-[250px] lg:max-w-[350px] xl:max-w-[450px]">
                        <span
                            class="block text-xs sm:text-sm md:text-base font-semibold text-gray-900 dark:text-gray-100 truncate leading-tight"
                            x-text="getFieldValue(getFieldsForDisplay()[0])"
                            :title="getFieldValue(getFieldsForDisplay()[0])"
                        ></span>
                    </div>

                    {{-- Separator: Show on all screens --}}
                    <span class="text-gray-400 dark:text-gray-500 flex-shrink-0 text-xs sm:text-sm leading-tight">•</span>

                    {{-- Second Field: Truncate with ellipsis, responsive --}}
                    <div class="min-w-0 max-w-[80px] xs:max-w-[100px] sm:max-w-[130px] md:max-w-[200px] lg:max-w-[280px] xl:max-w-[350px]">
                        <span
                            class="block text-xs sm:text-sm md:text-base text-gray-600 dark:text-gray-400 truncate leading-tight"
                            x-text="getFieldValue(getFieldsForDisplay()[1])"
                            :title="getFieldValue(getFieldsForDisplay()[1])"
                        ></span>
                    </div>
                </div>
            </template>

            {{-- Loading state --}}
            <span x-show="hasActiveContext && !preferencesLoaded" class="text-xs sm:text-sm text-gray-500">Loading...</span>
        </div>

        {{-- Toggle Chevron (Right) - Responsive --}}
        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform duration-300 flex-shrink-0 ml-2 sm:ml-3" :class="{'rotate-180': !isMinimized}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
        </svg>
    </div>

    <div class="fi-section-content p-3" x-show="!isMinimized" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        {{-- No Project Selected State --}}
        <div x-show="!hasActiveProject" class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">No Project Selected</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Select a project to view context and details</p>
                </div>
            </div>
            <button
                type="button"
                @click="openProjectSelector()"
                class="fi-btn fi-btn-size-md fi-btn-color-primary inline-flex items-center justify-center gap-2 font-semibold rounded-lg px-4 py-2 text-sm bg-primary-600 text-white hover:bg-primary-700"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
                Select Project
            </button>
        </div>

        {{-- Active Context State - Dynamic Fields --}}
        <div x-show="hasActiveContext && preferencesLoaded" class="flex items-center justify-between gap-4">
            {{-- Dynamic Field Grid --}}
            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                <template x-for="fieldKey in getFieldsForDisplay()" :key="fieldKey">
                    <div x-html="renderField(fieldKey, fieldDefinitions[contextType]?.[fieldKey])"></div>
                </template>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-row gap-2 flex-shrink-0 items-center">
                {{-- Context-aware Save button - shown on edit pages --}}
                <button
                    type="button"
                    @click="saveCurrentForm()"
                    x-show="isOnEditPage()"
                    x-data="{ formDisabled: false }"
                    x-init="$nextTick(() => {
                        const checkDisabled = () => {
                            const allButtons = document.querySelectorAll('button');
                            for (const btn of allButtons) {
                                const text = btn.textContent.trim();
                                const isSubmit = btn.type === 'submit';
                                if (isSubmit && (text === 'Save' || text === 'Save changes') && !btn.closest('[x-data*=contextFooterGlobal]')) {
                                    formDisabled = btn.disabled;
                                    return;
                                }
                            }
                            formDisabled = false;
                        };
                        checkDisabled();
                        setInterval(checkDisabled, 500);
                    })"
                    :disabled="formDisabled"
                    :class="{
                        'opacity-50 cursor-not-allowed': formDisabled,
                        'hover:bg-green-600': !formDisabled
                    }"
                    class="fi-btn fi-btn-size-sm fi-btn-color-success inline-flex items-center justify-center gap-2 font-semibold rounded-lg px-3 py-1.5 text-xs bg-green-500 text-white transition-all"
                    style="background-color: rgb(34, 197, 94); color: white;"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save
                </button>

                <button
                    type="button"
                    @click="openProjectSelector()"
                    class="fi-btn fi-btn-size-sm fi-btn-color-primary inline-flex items-center justify-center gap-2 font-semibold rounded-lg px-3 py-1.5 text-xs bg-primary-600 text-white hover:bg-primary-700"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12M8 12h12M8 17h12M3 7h.01M3 12h.01M3 17h.01"></path>
                    </svg>
                    Switch
                </button>

                <a
                    :href="`/admin/project/projects/${activeProjectId}/edit`"
                    x-show="contextType === 'project' && activeProjectId && !isOnEditPage()"
                    class="fi-btn fi-btn-size-sm fi-btn-color-gray inline-flex items-center justify-center font-semibold rounded-lg px-3 py-1.5 text-xs bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    Edit
                </a>

                <button
                    type="button"
                    @click="clearContext()"
                    class="fi-btn fi-btn-size-sm fi-btn-color-gray inline-flex items-center justify-center font-semibold rounded-lg px-3 py-1.5 text-xs bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    Clear
                </button>
            </div>
        </div>

        {{-- Loading State --}}
        <div x-show="hasActiveContext && !preferencesLoaded" class="flex items-center justify-center py-6">
            <div class="flex items-center gap-2 text-gray-500">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm">Loading preferences...</span>
            </div>
        </div>
        </div>
    </div>

    {{-- Tags Modal --}}
    <div
        x-show="tagsModalOpen"
        @click.away="tagsModalOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        style="display: none;"
    >
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full max-h-[80vh] overflow-hidden">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Project Tags</h3>
                <button @click="tagsModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="p-6 overflow-y-auto max-h-[calc(80vh-80px)]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <template x-for="(tags, type) in tagsByType" :key="type">
                        <div class="space-y-2">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide" x-text="typeLabels[type] || type"></h4>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="tag in tags" :key="tag.id">
                                    <span
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium"
                                        :style="`background-color: ${tag.color}20; color: ${tag.color}; border: 1.5px solid ${tag.color}60;`"
                                        x-text="tag.name"
                                    ></span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function contextFooterGlobal() {
    return {
        isMinimized: true, // Start minimized by default
        hasActiveContext: false,
        contextType: null, // 'project', 'sale', 'inventory', 'production', etc.
        contextId: null,
        contextData: {},

        // Dynamic display properties
        primaryLabel: '—',
        secondaryLabel: '—',

        // Context-specific data
        projectData: {},
        saleData: {},
        inventoryData: {},
        productionData: {},

        // Tags and modal
        tags: [],
        tagsByType: {},
        tagsModalOpen: false,

        // Context Configuration Map
        contextConfigs: {
            project: {
                name: 'Project',
                emptyLabel: 'No Project',
                borderColor: 'rgb(59, 130, 246)', // Blue
                iconPath: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z', // Folder
                api: {
                    fetch: (id) => `/api/projects/${id}`,
                    tags: (id) => `/api/projects/${id}/tags`
                }
            },
            sale: {
                name: 'Sales Order',
                emptyLabel: 'No Order',
                borderColor: 'rgb(34, 197, 94)', // Green
                iconPath: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', // Shopping cart
                api: {
                    fetch: (id) => `/api/sales/orders/${id}`,
                    tags: (id) => `/api/sales/orders/${id}/tags`
                }
            },
            inventory: {
                name: 'Inventory Item',
                emptyLabel: 'No Item',
                borderColor: 'rgb(168, 85, 247)', // Purple
                iconPath: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', // Cube/Box
                api: {
                    fetch: (id) => `/api/inventory/items/${id}`,
                    tags: (id) => `/api/inventory/items/${id}/tags`
                }
            },
            production: {
                name: 'Production Job',
                emptyLabel: 'No Job',
                borderColor: 'rgb(249, 115, 22)', // Orange
                iconPath: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', // Beaker/Production
                api: {
                    fetch: (id) => `/api/production/jobs/${id}`,
                    tags: (id) => `/api/production/jobs/${id}/tags`
                }
            }
        },

        // Current context configuration
        get contextConfig() {
            return this.contextConfigs[this.contextType] || {
                name: 'Context',
                emptyLabel: 'No Context',
                borderColor: 'rgb(156, 163, 175)', // Gray
                iconPath: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' // Info circle
            };
        },

        // Timeline alert properties (for projects)
        projectDailyRate: null,
        workingDays: null,
        capacityUtilization: null,
        ratePercentage: null,
        alertLevel: null,
        alertMessage: null,
        linearFeet: null,
        estimate: null,

        // Alert styling configuration
        alertStyles: {
            green: {
                classes: 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700',
                textClass: 'text-green-700 dark:text-green-300',
                iconClass: 'text-green-600 dark:text-green-400',
                iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
            },
            amber: {
                classes: 'bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-700',
                textClass: 'text-amber-700 dark:text-amber-300',
                iconClass: 'text-amber-600 dark:text-amber-400',
                iconPath: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
            },
            red: {
                classes: 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700',
                textClass: 'text-red-700 dark:text-red-300',
                iconClass: 'text-red-600 dark:text-red-400',
                iconPath: 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z'
            },
            black: {
                classes: 'bg-gray-900 dark:bg-gray-950 border-gray-900 dark:border-gray-950',
                textClass: 'text-white',
                iconClass: 'text-white',
                iconPath: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
            }
        },

        typeLabels: {
            'priority': 'Priority',
            'health': 'Health Status',
            'risk': 'Risk Factors',
            'complexity': 'Complexity',
            'work_scope': 'Work Scope',
            'phase_discovery': 'Discovery Phase',
            'phase_design': 'Design Phase',
            'phase_sourcing': 'Sourcing Phase',
            'phase_production': 'Production Phase',
            'phase_delivery': 'Delivery Phase',
            'special_status': 'Special Status',
            'lifecycle': 'Lifecycle',
        },

        // User preferences for footer customization
        userPreferences: {},
        preferencesLoaded: false,

        // Field definitions for dynamic rendering (MUST match actual DB columns)
        fieldDefinitions: {
            project: {
                'project_number': { label: 'Project Number', type: 'text', data_key: 'project_number' },
                'customer_name': { label: 'Customer Name', type: 'text', data_key: 'partner.name' },
                'project_type': { label: 'Project Type', type: 'badge', data_key: 'project_type', color: 'blue' },
                'project_address': { label: 'Address', type: 'text', data_key: 'project_address' },
                'linear_feet': { label: 'Linear Feet', type: 'number', data_key: 'estimated_linear_feet', suffix: ' LF' },
                'estimate_hours': { label: 'Allocated Hours', type: 'number', data_key: 'allocated_hours', suffix: ' hrs' },
                'estimate_days': { label: 'days', type: 'metric', data_key: 'estimate.days', icon: 'calendar', color: 'blue' },
                'estimate_weeks': { label: 'wks', type: 'metric', data_key: 'estimate.weeks', icon: 'trending', color: 'purple' },
                'estimate_months': { label: 'mos', type: 'metric', data_key: 'estimate.months', icon: 'calendar', color: 'teal' },
                'timeline_alert': { label: 'Timeline Alert', type: 'alert', data_key: 'alertMessage' },
                'completion_date': { label: 'Completion Date', type: 'date', data_key: 'desired_completion_date' },
                'tags': { label: 'Tags', type: 'tags', data_key: 'tags' },
            },
            sale: {
                'order_number': { label: 'Order Number', type: 'text', data_key: 'order_number' },
                'quote_number': { label: 'Quote Number', type: 'text', data_key: 'quote_number' },
                'customer_name': { label: 'Customer', type: 'text', data_key: 'customer_name' },
                'order_total': { label: 'Total', type: 'currency', data_key: 'order_total' },
                'order_status': { label: 'Status', type: 'badge', data_key: 'order_status', color: 'blue' },
                'payment_status': { label: 'Payment', type: 'badge', data_key: 'payment_status', color: 'green' },
                'order_date': { label: 'Order Date', type: 'date', data_key: 'order_date' },
                'expected_delivery': { label: 'Delivery', type: 'date', data_key: 'expected_delivery' },
            },
            inventory: {
                'item_name': { label: 'Item Name', type: 'text', data_key: 'name' },
                'sku': { label: 'SKU', type: 'text', data_key: 'sku' },
                'quantity': { label: 'Quantity', type: 'number', data_key: 'quantity' },
                'unit': { label: 'Unit', type: 'text', data_key: 'unit' },
                'location': { label: 'Location', type: 'text', data_key: 'location' },
                'reorder_level': { label: 'Reorder Level', type: 'number', data_key: 'reorder_level' },
                'supplier': { label: 'Supplier', type: 'text', data_key: 'supplier' },
                'unit_cost': { label: 'Unit Cost', type: 'currency', data_key: 'unit_cost' },
            },
            production: {
                'job_number': { label: 'Job Number', type: 'text', data_key: 'job_number' },
                'project_name': { label: 'Project', type: 'text', data_key: 'project_name' },
                'customer_name': { label: 'Customer', type: 'text', data_key: 'customer_name' },
                'production_status': { label: 'Status', type: 'badge', data_key: 'production_status', color: 'orange' },
                'assigned_to': { label: 'Assigned To', type: 'text', data_key: 'assigned_to' },
                'start_date': { label: 'Start Date', type: 'date', data_key: 'start_date' },
                'due_date': { label: 'Due Date', type: 'date', data_key: 'due_date' },
            },
        },

        init() {
            // Wait for Alpine and entityStore to be fully initialized
            const waitForEntityStore = () => {
                const entityStore = Alpine.store('entityStore');
                if (entityStore) {
                    this.loadUserPreferences().then(() => {
                        this.loadActiveContext();
                    });
                } else {
                    // EntityStore not ready, retry after a short delay
                    setTimeout(waitForEntityStore, 100);
                }
            };

            // Start checking for entityStore
            waitForEntityStore();
        },

        /**
         * Load user preferences for all contexts from API
         */
        async loadUserPreferences() {
            try {
                const response = await fetch('/api/footer/preferences');
                if (response.ok) {
                    this.userPreferences = await response.json();
                    this.preferencesLoaded = true;
                } else {
                    console.warn('Failed to load footer preferences, using defaults');
                    this.preferencesLoaded = true;
                }
            } catch (e) {
                console.error('Error loading footer preferences:', e);
                this.preferencesLoaded = true;
            }
        },

        /**
         * Get fields to display for current context and state (minimized/expanded)
         */
        getFieldsForDisplay() {
            if (!this.contextType || !this.preferencesLoaded) {
                return [];
            }

            const contextPrefs = this.userPreferences[this.contextType];

            // Use defaults if preferences not available
            if (!contextPrefs) {
                const defaults = {
                    project: {
                        minimized: ['customer_name', 'project_type'],
                        expanded: ['project_number', 'customer_name', 'project_type', 'linear_feet']
                    },
                    sale: {
                        minimized: ['order_number', 'customer_name'],
                        expanded: ['order_number', 'customer_name', 'order_total', 'order_status']
                    },
                    inventory: {
                        minimized: ['item_name', 'quantity'],
                        expanded: ['item_name', 'sku', 'quantity', 'location']
                    },
                    production: {
                        minimized: ['job_number', 'project_name'],
                        expanded: ['job_number', 'project_name', 'production_status', 'assigned_to']
                    }
                };

                const contextDefaults = defaults[this.contextType];
                return this.isMinimized ? (contextDefaults?.minimized || []) : (contextDefaults?.expanded || []);
            }

            return this.isMinimized ? contextPrefs.minimized_fields : contextPrefs.expanded_fields;
        },

        /**
         * Get value for a field from current context data
         * Maps field keys to actual database values
         */
        getFieldValue(fieldKey) {
            const dataKeyMap = {
                // Project fields - map to ACTUAL database columns
                'project_number': () => this.projectData.project_number || '—',
                'customer_name': () => this.contextData._customerName || this.projectData._customerName || '—',
                'project_type': () => this.projectType,
                'project_address': () => this.projectAddress,
                'linear_feet': () => this.linearFeet ? `${this.linearFeet} LF` : '—',
                'estimate_hours': () => this.projectData.allocated_hours ? this.projectData.allocated_hours : '—',
                'estimate_days': () => this.estimate?.days ? this.estimate.days.toFixed(1) : '—',
                'estimate_weeks': () => this.estimate?.weeks ? this.estimate.weeks.toFixed(1) : '—',
                'estimate_months': () => this.estimate?.months ? this.estimate.months.toFixed(1) : '—',
                'timeline_alert': () => this.alertMessage || null,
                'completion_date': () => this.projectData.desired_completion_date || '—',
                'tags': () => this.tags.length,

                // Sale fields
                'order_number': () => this.saleData.order_number || '—',
                'quote_number': () => this.saleData.quote_number || '—',
                'order_total': () => this.saleData.order_total || '—',
                'order_status': () => this.saleData.order_status || '—',
                'payment_status': () => this.saleData.payment_status || '—',
                'order_date': () => this.saleData.order_date || '—',
                'expected_delivery': () => this.saleData.expected_delivery || '—',

                // Inventory fields
                'item_name': () => this.inventoryData.name || '—',
                'sku': () => this.inventoryData.sku || '—',
                'quantity': () => this.inventoryData.quantity || '—',
                'unit': () => this.inventoryData.unit || '—',
                'location': () => this.inventoryData.location || '—',
                'reorder_level': () => this.inventoryData.reorder_level || '—',
                'supplier': () => this.inventoryData.supplier || '—',
                'unit_cost': () => this.inventoryData.unit_cost || '—',

                // Production fields
                'job_number': () => this.productionData.job_number || '—',
                'project_name': () => this.productionData.project_name || '—',
                'production_status': () => this.productionData.production_status || '—',
                'assigned_to': () => this.productionData.assigned_to || '—',
                'start_date': () => this.productionData.start_date || '—',
                'due_date': () => this.productionData.due_date || '—',
            };

            const getValue = dataKeyMap[fieldKey];
            return getValue ? getValue() : '—';
        },

        /**
         * Render a field based on its type and value
         * Returns HTML string for the field
         */
        renderField(fieldKey, fieldDef) {
            const value = this.getFieldValue(fieldKey);
            const type = fieldDef?.type || 'text';
            const label = fieldDef?.label || fieldKey;

            switch (type) {
                case 'text':
                    return this.renderTextField(label, value);
                case 'number':
                    return this.renderNumberField(label, value, fieldDef?.suffix);
                case 'metric':
                    return this.renderMetricField(label, value, fieldDef?.icon, fieldDef?.color);
                case 'badge':
                    return this.renderBadgeField(label, value, fieldDef?.color);
                case 'currency':
                    return this.renderCurrencyField(label, value);
                case 'date':
                    return this.renderDateField(label, value);
                case 'alert':
                    return this.renderAlertField(value);
                case 'tags':
                    return this.renderTagsField(value);
                default:
                    return this.renderTextField(label, value);
            }
        },

        renderTextField(label, value) {
            return `
                <div class="flex items-center gap-2 text-xs">
                    <span class="font-medium text-gray-500 dark:text-gray-400">${label}:</span>
                    <span class="text-gray-900 dark:text-gray-100">${value}</span>
                </div>
            `;
        },

        renderNumberField(label, value, suffix = '') {
            return `
                <div class="flex items-center gap-2 text-xs">
                    <span class="font-medium text-gray-500 dark:text-gray-400">${label}:</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">${value}${suffix}</span>
                </div>
            `;
        },

        renderMetricField(label, value, icon = 'clock', color = 'amber') {
            const iconPaths = {
                'clock': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                'calendar': 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                'trending': 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'
            };

            const colorClasses = {
                'amber': 'from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 border-amber-200 dark:border-amber-700 text-amber-900 dark:text-amber-100 text-amber-600 dark:text-amber-400',
                'blue': 'from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border-blue-200 dark:border-blue-700 text-blue-900 dark:text-blue-100 text-blue-600 dark:text-blue-400',
                'purple': 'from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border-purple-200 dark:border-purple-700 text-purple-900 dark:text-purple-100 text-purple-600 dark:text-purple-400',
                'teal': 'from-teal-50 to-teal-100 dark:from-teal-900/20 dark:to-teal-800/20 border-teal-200 dark:border-teal-700 text-teal-900 dark:text-teal-100 text-teal-600 dark:text-teal-400'
            };

            const bgClass = colorClasses[color] || colorClasses['amber'];
            const iconPath = iconPaths[icon] || iconPaths['clock'];

            return `
                <div class="flex items-center gap-1.5 px-2 py-1 rounded-md bg-gradient-to-br ${bgClass} border">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconPath}"></path>
                    </svg>
                    <div class="text-xs font-bold">${value}</div>
                    <div class="text-[10px]">${label}</div>
                </div>
            `;
        },

        renderBadgeField(label, value, color = 'blue') {
            return `
                <div class="flex items-center gap-2 text-xs">
                    <span class="font-medium text-gray-500 dark:text-gray-400">${label}:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-${color}-100 text-${color}-800 dark:bg-${color}-900/20 dark:text-${color}-400">
                        ${value}
                    </span>
                </div>
            `;
        },

        renderCurrencyField(label, value) {
            const formatted = typeof value === 'number' ? `$${value.toFixed(2)}` : value;
            return `
                <div class="flex items-center gap-2 text-xs">
                    <span class="font-medium text-gray-500 dark:text-gray-400">${label}:</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">${formatted}</span>
                </div>
            `;
        },

        renderDateField(label, value) {
            const formatted = value !== '—' ? new Date(value).toLocaleDateString() : '—';
            return `
                <div class="flex items-center gap-2 text-xs">
                    <span class="font-medium text-gray-500 dark:text-gray-400">${label}:</span>
                    <span class="text-gray-900 dark:text-gray-100">${formatted}</span>
                </div>
            `;
        },

        renderAlertField(message) {
            if (!message || !this.alertLevel) return '';

            const style = this.alertStyles[this.alertLevel];
            return `
                <div class="p-2 rounded-md border ${style.classes}">
                    <div class="flex items-center gap-1.5 mb-1">
                        <svg class="h-3.5 w-3.5 ${style.iconClass}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${style.iconPath}"></path>
                        </svg>
                        <div class="text-xs font-bold uppercase ${style.textClass}">${message}</div>
                    </div>
                </div>
            `;
        },

        renderTagsField(tagCount) {
            if (!tagCount || tagCount === 0) return '';

            return `
                <button type="button" @click="tagsModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    <span>${tagCount} ${tagCount === 1 ? 'Tag' : 'Tags'}</span>
                </button>
            `;
        },

        async loadActiveContext() {
            // Check if entityStore exists before accessing it
            const entityStore = Alpine.store('entityStore');
            if (!entityStore) {
                console.warn('[Footer] EntityStore not initialized yet');
                this.hasActiveContext = false;
                return;
            }

            const context = entityStore.getActiveContext();

            if (!context || !context.entityType) {
                this.hasActiveContext = false;
                this.contextType = null;
                this.contextId = null;
                return;
            }

            this.contextType = context.entityType;
            this.contextId = context.entityId;
            let data = entityStore.getEntity(context.entityType, context.entityId);

            // If EntityStore doesn't have data (e.g., on edit/view pages), fetch from API
            if (!data && this.contextConfigs[this.contextType]?.api?.fetch) {
                try {
                    const apiUrl = this.contextConfigs[this.contextType].api.fetch(this.contextId);
                    const response = await fetch(apiUrl);
                    if (response.ok) {
                        data = await response.json();
                        console.log('[Footer] Fetched context data from API:', data);
                        // Optionally cache it in EntityStore for future use
                        entityStore.updateEntity(context.entityType, context.entityId, data, true);
                    } else {
                        console.warn('[Footer] Failed to fetch context data from API:', response.status);
                    }
                } catch (e) {
                    console.error('[Footer] Error fetching context data:', e);
                }
            }

            if (!data) {
                this.hasActiveContext = false;
                return;
            }

            this.contextData = data;
            this.hasActiveContext = true;

            // Route to appropriate update method based on context type
            switch (this.contextType) {
                case 'project':
                    this.projectData = data;
                    await this.updateProjectContext(data);
                    break;
                case 'sale':
                    this.saleData = data;
                    await this.updateSaleContext(data);
                    break;
                case 'inventory':
                    this.inventoryData = data;
                    await this.updateInventoryContext(data);
                    break;
                case 'production':
                    this.productionData = data;
                    await this.updateProductionContext(data);
                    break;
                default:
                    await this.updateGenericContext(data);
            }
        },

        // Backward compatibility - calls loadActiveContext
        async loadActiveProject() {
            await this.loadActiveContext();
        },

        // Computed properties for backward compatibility
        get hasActiveProject() {
            return this.contextType === 'project' && this.hasActiveContext;
        },

        get activeProjectId() {
            return this.contextType === 'project' ? this.contextId : null;
        },

        get projectNumber() {
            return this.projectData.project_number || '—';
        },

        get customerName() {
            if (this.contextType === 'project' && this.projectData.partner_id) {
                return this.projectData._customerName || '—';
            }
            return '—';
        },

        get projectAddress() {
            return this.formatAddress(this.projectData.project_address);
        },

        get projectType() {
            if (this.projectData.project_type) {
                return this.projectData.project_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            }
            return '—';
        },

        get projectTags() {
            return this.contextType === 'project' ? this.tags : [];
        },

        // Project Context Update
        async updateProjectContext(data) {
            // Set primary/secondary labels for minimized bar
            this.primaryLabel = data.project_number || '—';

            // Fetch customer name
            if (data.partner_id) {
                const customerName = await this.fetchCustomerName(data.partner_id);
                this.secondaryLabel = customerName;
                data._customerName = customerName; // Cache it
            } else {
                this.secondaryLabel = '—';
            }

            // Linear feet
            this.linearFeet = data.estimated_linear_feet || null;

            // Production estimate
            if (this.linearFeet && data.company_id) {
                this.estimate = await this.calculateEstimate(this.linearFeet, data.company_id);
            } else {
                this.estimate = null;
            }

            // Timeline alerts
            if (data.desired_completion_date && this.linearFeet && this.estimate) {
                await this.calculateTimelineAlert();
            } else {
                this.alertLevel = null;
            }

            // Tags
            await this.loadTags('project', this.contextId);
        },

        // Sales Context Update
        async updateSaleContext(data) {
            // Set primary/secondary labels
            this.primaryLabel = data.order_number || data.quote_number || '—';

            // Fetch customer name
            if (data.partner_id || data.customer_id) {
                const customerId = data.partner_id || data.customer_id;
                this.secondaryLabel = await this.fetchCustomerName(customerId);
            } else {
                this.secondaryLabel = '—';
            }

            // Load tags
            await this.loadTags('sale', this.contextId);
        },

        // Inventory Context Update
        async updateInventoryContext(data) {
            // Set primary/secondary labels
            this.primaryLabel = data.name || data.sku || '—';
            this.secondaryLabel = data.quantity ? `${data.quantity} ${data.unit || 'units'}` : '—';

            // Load tags
            await this.loadTags('inventory', this.contextId);
        },

        // Production Context Update
        async updateProductionContext(data) {
            // Set primary/secondary labels
            this.primaryLabel = data.job_number || '—';
            this.secondaryLabel = data.project_name || data.customer_name || '—';

            // Load tags
            await this.loadTags('production', this.contextId);
        },

        // Generic Context Update (fallback)
        async updateGenericContext(data) {
            this.primaryLabel = data.name || data.number || data.id || '—';
            this.secondaryLabel = data.description || '—';
        },

        async fetchCustomerName(partnerId) {
            try {
                const response = await fetch(`/api/admin/api/partners/${partnerId}`);
                const data = await response.json();
                return data.name || '—';
            } catch (e) {
                console.error('Failed to fetch customer name:', e);
                return '—';
            }
        },

        formatAddress(addressData) {
            if (!addressData) return '—';

            const parts = [];
            if (addressData.street1) parts.push(addressData.street1);
            if (addressData.city) parts.push(addressData.city);
            if (addressData.state) parts.push(addressData.state);

            return parts.length > 0 ? parts.join(', ') : '—';
        },

        async calculateEstimate(linearFeet, companyId) {
            try {
                const response = await fetch(`/api/admin/api/production-estimate?linear_feet=${linearFeet}&company_id=${companyId}`);
                return await response.json();
            } catch (e) {
                console.error('Failed to calculate estimate:', e);
                return null;
            }
        },

        async calculateTimelineAlert() {
            // This would need to implement the same timeline calculation logic
            // For now, set to null - can be implemented later
            this.alertLevel = null;
        },

        async loadTags(contextType, contextId) {
            try {
                const config = this.contextConfigs[contextType];
                if (!config || !config.api || !config.api.tags) {
                    this.tags = [];
                    this.tagsByType = {};
                    return;
                }

                const url = typeof config.api.tags === 'function'
                    ? config.api.tags(contextId)
                    : config.api.tags.replace('{id}', contextId);

                const response = await fetch(url);
                const tags = await response.json();
                this.tags = tags;

                // Group by type
                this.tagsByType = tags.reduce((acc, tag) => {
                    if (!acc[tag.type]) acc[tag.type] = [];
                    acc[tag.type].push(tag);
                    return acc;
                }, {});
            } catch (e) {
                console.error(`Failed to load ${contextType} tags:`, e);
                this.tags = [];
                this.tagsByType = {};
            }
        },

        // Backward compatibility
        async loadProjectTags() {
            await this.loadTags('project', this.contextId);
        },

        handleContextChange(detail) {
            this.loadActiveContext();
        },

        handleEntityUpdate(detail) {
            if (detail.entityType === this.contextType && detail.entityId === this.contextId) {
                this.loadActiveContext();
            }
        },

        clearContext() {
            const entityStore = Alpine.store('entityStore');
            if (!entityStore) {
                console.warn('[Footer] EntityStore not available');
                return;
            }

            const contextName = this.contextConfig.name || 'context';
            if (confirm(`Clear active ${contextName.toLowerCase()}?`)) {
                entityStore.clearActiveContext();
                this.hasActiveContext = false;
                this.contextType = null;
                this.contextId = null;
            }
        },

        openProjectSelector() {
            // Dispatch event to open project selector modal
            window.dispatchEvent(new CustomEvent('open-project-selector'));
        },

        /**
         * Check if currently on an edit page
         */
        isOnEditPage() {
            return window.location.pathname.includes('/edit');
        },

        /**
         * Check if Filament's save button is disabled
         */
        isSaveButtonDisabled() {
            // Find Filament's save button (not our custom footer button)
            const allButtons = document.querySelectorAll('button');
            for (const btn of allButtons) {
                const text = btn.textContent.trim();
                const isSubmit = btn.type === 'submit';

                // Check if it's Filament's save button (but not our custom one in footer)
                if (isSubmit && (text === 'Save' || text === 'Save changes') && !btn.closest('[x-data*="contextFooterGlobal"]')) {
                    return btn.disabled;
                }
            }

            // If we can't find Filament's button, default to false (enabled)
            return false;
        },

        /**
         * Save the current Filament form
         */
        saveCurrentForm() {
            console.log('[Footer] saveCurrentForm() called');

            // Try to find the Filament save button using multiple strategies
            let saveButton = null;

            // Strategy 1: Find button with text "Save" or "Save changes" (Filament's default)
            const allButtons = document.querySelectorAll('button');
            for (const btn of allButtons) {
                const text = btn.textContent.trim();
                const isSubmit = btn.type === 'submit';
                // Check if it's a save button (but not our custom one in footer)
                if (isSubmit && (text === 'Save' || text === 'Save changes') && !btn.closest('[x-data*="contextFooterGlobal"]')) {
                    saveButton = btn;
                    console.log('[Footer] Found save button by text:', text);
                    break;
                }
            }

            // Strategy 2: Find button with wire:click containing 'save'
            if (!saveButton) {
                const buttons = document.querySelectorAll('button[type="submit"]');
                for (const btn of buttons) {
                    const wireClick = btn.getAttribute('wire:click');
                    if (wireClick && wireClick.toLowerCase().includes('save') && !btn.closest('[x-data*="contextFooterGlobal"]')) {
                        saveButton = btn;
                        console.log('[Footer] Found save button via wire:click');
                        break;
                    }
                }
            }

            // Strategy 3: Find button in Filament form actions
            if (!saveButton) {
                saveButton = document.querySelector('.fi-form-actions button[type="submit"], .fi-fo-actions button[type="submit"]');
                if (saveButton) {
                    console.log('[Footer] Found save button in Filament form actions');
                }
            }

            // Strategy 4: Find any submit button in sticky footer (Filament's sticky actions)
            if (!saveButton) {
                const stickyButton = document.querySelector('.fi-sticky button[type="submit"]');
                if (stickyButton && !stickyButton.closest('[x-data*="contextFooterGlobal"]')) {
                    saveButton = stickyButton;
                    console.log('[Footer] Found save button in fi-sticky');
                }
            }

            if (saveButton) {
                console.log('[Footer] Triggering save button click');
                saveButton.click();
            } else {
                console.error('[Footer] Cannot find Filament save button - tried all strategies');
            }
        }
    };
}
</script>
