{{--
    Entity Editor Sidebar Component
    Allows updating entity data from any page (annotation, review, etc.)

    Usage:
    @include('components.entity-editor-sidebar', [
        'entityType' => 'partner',
        'entityId' => $customer->id ?? null,
        'fields' => [
            ['name' => 'phone', 'label' => 'Phone Number', 'type' => 'text'],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
        ]
    ])
--}}

<div
    x-data="{
        open: false,
        entityType: '{{ $entityType ?? 'project' }}',
        entityId: {{ $entityId ?? 'null' }},
        entityData: {},
        editedFields: [],
        saving: false,

        init() {
            // Load entity data from store
            this.loadEntityData();

            // Listen for updates from other components
            window.addEventListener('entity-updated', (event) => {
                if (event.detail.entityType === this.entityType && event.detail.entityId === this.entityId) {
                    this.entityData = event.detail.data;
                }
            });
        },

        loadEntityData() {
            const data = Alpine.store('entityStore').getEntity(this.entityType, this.entityId);
            if (data) {
                this.entityData = data;
            }
        },

        updateField(fieldName, value) {
            // Update entity store
            window.updateEntityField(this.entityType, this.entityId, fieldName, value);

            // Track edited fields for visual feedback
            if (!this.editedFields.includes(fieldName)) {
                this.editedFields.push(fieldName);
            }

            // Show success toast
            this.$dispatch('notify', {
                type: 'success',
                message: `Updated ${fieldName}`,
                timeout: 2000
            });

            // Remove from edited list after 3 seconds
            setTimeout(() => {
                this.editedFields = this.editedFields.filter(f => f !== fieldName);
            }, 3000);
        },

        getFieldValue(fieldName) {
            return window.getEntityField(this.entityType, this.entityId, fieldName) || '';
        },

        clearAllData() {
            if (confirm('Clear all session data for this entity?')) {
                Alpine.store('entityStore').clearEntity(this.entityType, this.entityId);
                this.entityData = {};
                this.$dispatch('notify', {
                    type: 'info',
                    message: 'Session data cleared',
                    timeout: 2000
                });
            }
        }
    }"
    class="fixed right-0 top-0 h-full z-50"
>
    <!-- Toggle Button -->
    <button
        @click="open = !open"
        class="absolute left-0 top-1/2 -translate-x-full -translate-y-1/2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-4 rounded-l-lg shadow-lg transition-all"
        title="Entity Data Editor"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        <span
            x-show="Object.keys(entityData).length > 0"
            class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"
            x-text="Object.keys(entityData).length"
        ></span>
    </button>

    <!-- Sidebar Panel -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="bg-white dark:bg-gray-800 shadow-2xl w-96 h-full overflow-y-auto border-l border-gray-200 dark:border-gray-700"
        @click.away="open = false"
    >
        <!-- Header -->
        <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Entity Data
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <span x-text="entityType"></span>
                        <span x-show="entityId !== null">
                            #<span x-text="entityId"></span>
                        </span>
                        <span x-show="entityId === null" class="text-orange-500">
                            (New)
                        </span>
                    </p>
                </div>
                <button
                    @click="open = false"
                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4 space-y-4">
            <!-- Session Data Indicator -->
            <div
                x-show="Object.keys(entityData).length > 0"
                class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3"
            >
                <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300 text-sm">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-text="`${Object.keys(entityData).length} fields in session`"></span>
                </div>
            </div>

            <!-- Editable Fields -->
            <div class="space-y-3">
                @isset($fields)
                    @foreach($fields as $field)
                        <div
                            x-data="{ fieldName: '{{ $field['name'] }}' }"
                            :class="editedFields.includes(fieldName) ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700' : 'bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700'"
                            class="border rounded-lg p-3 transition-all duration-300"
                        >
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $field['label'] }}
                                <span
                                    x-show="editedFields.includes(fieldName)"
                                    class="text-green-600 dark:text-green-400 text-xs ml-1"
                                >
                                    ✓ Updated
                                </span>
                            </label>

                            @if(($field['type'] ?? 'text') === 'textarea')
                                <textarea
                                    :value="getFieldValue(fieldName)"
                                    @blur="updateField(fieldName, $event.target.value)"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white"
                                    rows="3"
                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                ></textarea>
                            @else
                                <input
                                    type="{{ $field['type'] ?? 'text' }}"
                                    :value="getFieldValue(fieldName)"
                                    @blur="updateField(fieldName, $event.target.value)"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white"
                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                />
                            @endif

                            @if(isset($field['helper']))
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $field['helper'] }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                @else
                    <!-- Default fields if none specified -->
                    <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm">No editable fields defined</p>
                        <p class="text-xs mt-1">Pass $fields array to enable editing</p>
                    </div>
                @endisset
            </div>

            <!-- Session Data Debug (Development) -->
            @if(config('app.debug'))
                <details class="bg-gray-100 dark:bg-gray-900 rounded-lg p-3">
                    <summary class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                        Debug: Session Data
                    </summary>
                    <pre class="mt-2 text-xs text-gray-600 dark:text-gray-400 overflow-auto max-h-40" x-text="JSON.stringify(entityData, null, 2)"></pre>
                </details>
            @endif

            <!-- Actions -->
            <div class="flex gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button
                    @click="loadEntityData()"
                    class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md text-sm font-medium transition-colors"
                >
                    Refresh
                </button>
                <button
                    @click="clearAllData()"
                    class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium transition-colors"
                >
                    Clear Data
                </button>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="sticky bottom-0 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Data syncs across all pages • Auto-saves on blur</span>
            </div>
        </div>
    </div>

    <!-- Toast Notification Area (if not using Filament notifications) -->
    <div
        x-data="{ notifications: [] }"
        @notify.window="notifications.push($event.detail); setTimeout(() => notifications.shift(), $event.detail.timeout || 3000)"
        class="fixed top-4 right-4 z-50 space-y-2"
    >
        <template x-for="(notification, index) in notifications" :key="index">
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                :class="{
                    'bg-green-500': notification.type === 'success',
                    'bg-blue-500': notification.type === 'info',
                    'bg-red-500': notification.type === 'error'
                }"
                class="px-4 py-3 rounded-lg shadow-lg text-white text-sm max-w-xs"
            >
                <p x-text="notification.message"></p>
            </div>
        </template>
    </div>
</div>
