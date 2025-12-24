<div>
    <div
        x-data="aiVendorLookup({
            apiEndpoint: '{{ url('/admin/vendor-ai/lookup') }}',
            fieldMappings: @js($getFieldMappings())
        })"
        class="ai-vendor-lookup"
    >
        {{-- Header with Help --}}
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                AI-Powered Vendor Lookup
            </h3>
            <button
                type="button"
                x-on:click="showHelp = !showHelp"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
                <x-heroicon-m-question-mark-circle class="w-5 h-5" />
            </button>
        </div>

        {{-- Help Text --}}
        <div
            x-show="showHelp"
            x-collapse
            class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-700 dark:text-blue-300"
        >
            <p class="mb-2"><strong>How to use:</strong></p>
            <ul class="list-disc list-inside space-y-1">
                <li><strong>By Name:</strong> Enter a company name (e.g., "Home Depot", "Blum Hardware")</li>
                <li><strong>By Website:</strong> Enter a website URL (e.g., "homedepot.com")</li>
            </ul>
            <p class="mt-2 text-xs">Results will auto-fill the form fields below. You can review and modify before saving.</p>
        </div>

        {{-- Search Controls --}}
        <div class="flex gap-2 mb-4">
            {{-- Search Type Dropdown --}}
            <select
                x-model="searchType"
                class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500"
                :disabled="isLoading"
            >
                <option value="name">By Name</option>
                <option value="website">By Website</option>
            </select>

            {{-- Search Input --}}
            <div class="flex-1 relative">
                <input
                    type="text"
                    x-model="searchQuery"
                    x-on:keydown.enter.prevent="lookup()"
                    :placeholder="searchType === 'name' ? 'Enter company name...' : 'Enter website URL...'"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500"
                    :disabled="isLoading"
                />
                <div
                    x-show="isLoading"
                    class="absolute right-3 top-1/2 -translate-y-1/2"
                >
                    <x-filament::loading-indicator class="h-5 w-5" />
                </div>
            </div>

            {{-- Search Button --}}
            <button
                type="button"
                x-on:click="lookup()"
                :disabled="isLoading || !searchQuery.trim()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
                <template x-if="!isLoading">
                    <x-heroicon-m-magnifying-glass class="w-4 h-4" />
                </template>
                <template x-if="isLoading">
                    <x-filament::loading-indicator class="h-4 w-4" />
                </template>
                <span x-text="isLoading ? 'Searching...' : 'AI Lookup'"></span>
            </button>
        </div>

        {{-- Error Message --}}
        <div
            x-show="error"
            x-transition
            class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg flex items-start gap-2"
        >
            <x-heroicon-m-exclamation-circle class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
            <div class="text-sm text-red-700 dark:text-red-300">
                <p x-text="error"></p>
            </div>
            <button
                type="button"
                x-on:click="error = null"
                class="ml-auto text-red-400 hover:text-red-600"
            >
                <x-heroicon-m-x-mark class="w-4 h-4" />
            </button>
        </div>

        {{-- Results Panel --}}
        <div
            x-show="result"
            x-transition
            class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800"
        >
            {{-- Result Header --}}
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <x-heroicon-m-check-circle class="w-5 h-5 text-green-500" />
                    <span class="font-medium text-green-800 dark:text-green-200">
                        Found: <span x-text="result?.company_name || result?.name || 'Unknown'"></span>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    {{-- Confidence Badge --}}
                    <span
                        x-show="result?.confidence"
                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                        :class="{
                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': result?.confidence >= 0.8,
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': result?.confidence >= 0.5 && result?.confidence < 0.8,
                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': result?.confidence < 0.5
                        }"
                    >
                        <span x-text="Math.round((result?.confidence || 0) * 100) + '% confidence'"></span>
                    </span>
                </div>
            </div>

            {{-- Result Details Preview --}}
            <div class="grid grid-cols-2 gap-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                <div x-show="result?.phone">
                    <span class="font-medium">Phone:</span>
                    <span x-text="result?.phone"></span>
                </div>
                <div x-show="result?.email">
                    <span class="font-medium">Email:</span>
                    <span x-text="result?.email"></span>
                </div>
                <div x-show="result?.website">
                    <span class="font-medium">Website:</span>
                    <span x-text="result?.website"></span>
                </div>
                <div x-show="result?.city || result?.state">
                    <span class="font-medium">Location:</span>
                    <span x-text="[result?.city, result?.state].filter(Boolean).join(', ')"></span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-2">
                <button
                    type="button"
                    x-on:click="applyResult()"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
                >
                    <x-heroicon-m-check class="w-4 h-4" />
                    Apply to Form
                </button>
                <button
                    type="button"
                    x-on:click="clearResult()"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                >
                    <x-heroicon-m-x-mark class="w-4 h-4" />
                    Clear
                </button>
            </div>
        </div>

        {{-- Applied Success Message --}}
        <div
            x-show="applied"
            x-transition
            class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex items-center gap-2"
        >
            <x-heroicon-m-information-circle class="w-5 h-5 text-blue-500" />
            <span class="text-sm text-blue-700 dark:text-blue-300">
                Form fields have been populated with AI data. Review and modify as needed before saving.
            </span>
        </div>
    </div>

    @once
        @push('scripts')
            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('aiVendorLookup', (config) => ({
                        // Configuration
                        apiEndpoint: config.apiEndpoint,
                        fieldMappings: config.fieldMappings,

                        // State
                        searchType: 'name',
                        searchQuery: '',
                        isLoading: false,
                        error: null,
                        result: null,
                        applied: false,
                        showHelp: false,

                        // Methods
                        async lookup() {
                            if (!this.searchQuery.trim()) {
                                this.error = 'Please enter a search query';
                                return;
                            }

                            this.isLoading = true;
                            this.error = null;
                            this.result = null;
                            this.applied = false;

                            try {
                                const response = await fetch(this.apiEndpoint, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    credentials: 'same-origin',
                                    body: JSON.stringify({
                                        type: this.searchType,
                                        query: this.searchQuery.trim()
                                    })
                                });

                                const data = await response.json();

                                if (!response.ok) {
                                    throw new Error(data.message || data.error || 'Lookup failed');
                                }

                                if (data.success && data.data) {
                                    this.result = data.data;
                                    console.log('AI Vendor Lookup Result:', this.result);
                                } else {
                                    throw new Error(data.message || 'No results found');
                                }

                            } catch (err) {
                                console.error('AI Vendor Lookup Error:', err);
                                this.error = err.message || 'Failed to look up vendor information. Please try again.';
                            } finally {
                                this.isLoading = false;
                            }
                        },

                        applyResult() {
                            if (!this.result) return;

                            // Find the Livewire component
                            const livewireComponent = this.findLivewireComponent();

                            if (livewireComponent) {
                                // Use Livewire to set values
                                this.applyViaLivewire(livewireComponent);
                            } else {
                                // Fallback: directly populate form fields
                                this.applyDirectly();
                            }

                            this.applied = true;

                            // Show success notification if Filament notifications are available
                            if (window.Filament && window.Filament.notifications) {
                                window.Filament.notifications.notification({
                                    title: 'Form Updated',
                                    description: 'Vendor information has been applied to the form.',
                                    status: 'success',
                                });
                            }
                        },

                        findLivewireComponent() {
                            // Find the closest Livewire component
                            const element = this.$el.closest('[wire\\:id]');
                            if (element) {
                                const wireId = element.getAttribute('wire:id');
                                return window.Livewire?.find(wireId);
                            }
                            return null;
                        },

                        applyViaLivewire(component) {
                            // Apply field mappings via Livewire
                            Object.entries(this.fieldMappings).forEach(([aiField, formField]) => {
                                const value = this.result[aiField];
                                if (value !== null && value !== undefined) {
                                    // Handle special cases
                                    if (aiField === 'account_type') {
                                        // Convert to enum value if needed
                                        const accountType = value === 'company' ? 'company' : 'individual';
                                        component.set(`data.${formField}`, accountType);
                                    } else {
                                        component.set(`data.${formField}`, value);
                                    }
                                    console.log(`Set ${formField} to:`, value);
                                }
                            });

                            // Force component update
                            component.$refresh();
                        },

                        applyDirectly() {
                            // Fallback: find form and populate fields directly
                            const form = this.$el.closest('form') || this.$el.closest('[wire\\:id]');
                            if (!form) {
                                console.warn('Could not find form container');
                                return;
                            }

                            Object.entries(this.fieldMappings).forEach(([aiField, formField]) => {
                                const value = this.result[aiField];
                                if (value !== null && value !== undefined) {
                                    this.setFormField(form, formField, value);
                                }
                            });
                        },

                        setFormField(form, fieldName, value) {
                            // Try various selectors to find the field
                            const selectors = [
                                `input[name*="${fieldName}"]`,
                                `select[name*="${fieldName}"]`,
                                `textarea[name*="${fieldName}"]`,
                                `[wire\\:model*="${fieldName}"]`,
                                `[wire\\:model\\.live*="${fieldName}"]`,
                                `#${fieldName}`,
                                `[id*="${fieldName}"]`,
                            ];

                            for (const selector of selectors) {
                                const field = form.querySelector(selector);
                                if (field) {
                                    if (field.tagName === 'SELECT') {
                                        // For select fields, find matching option
                                        const option = Array.from(field.options).find(opt =>
                                            opt.value == value || opt.text == value
                                        );
                                        if (option) {
                                            field.value = option.value;
                                        }
                                    } else if (field.type === 'radio') {
                                        // For radio buttons, find the right one
                                        const radio = form.querySelector(`input[name="${field.name}"][value="${value}"]`);
                                        if (radio) {
                                            radio.checked = true;
                                        }
                                    } else {
                                        field.value = value;
                                    }

                                    // Trigger events
                                    field.dispatchEvent(new Event('input', { bubbles: true }));
                                    field.dispatchEvent(new Event('change', { bubbles: true }));
                                    field.dispatchEvent(new Event('blur', { bubbles: true }));

                                    console.log(`Populated ${fieldName} with:`, value);
                                    break;
                                }
                            }
                        },

                        clearResult() {
                            this.result = null;
                            this.applied = false;
                            this.error = null;
                        },
                    }));
                });
            </script>
        @endpush
    @endonce
</div>
