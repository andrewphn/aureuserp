/**
 * Global Context Footer - Alpine Component
 * FilamentPHP v4 Compliant
 *
 * Provides reactive UI state management for the global context footer widget.
 * Syncs with Livewire for server-side state and sessionStorage for persistence.
 */

import { registerComponent } from '../livewire-component-loader.js';

function contextFooter({
    contextType = null,
    contextId = null,
    contextData = {},
    contextConfigs = {},
    isMinimized = true,
    hasActiveContext = false,
}) {
    return {
        // State (synced with Livewire via wire:model/entangle)
        isMinimized,
        hasActiveContext,
        contextType,
        contextId,
        contextData,
        contextConfigs,

        // Computed context configuration
        get contextConfig() {
            return this.contextConfigs[this.contextType] || {
                name: 'Context',
                emptyLabel: 'No Context',
                borderColor: 'rgb(156, 163, 175)', // Gray
                iconPath: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' // Info circle
            };
        },

        /**
         * Initialize the component
         */
        init() {
            // Load context from sessionStorage on mount
            this.loadContextFromStorage();

            // Listen for Livewire events
            this.setupEventListeners();

            // Sync to sessionStorage when context changes
            this.$watch('contextType', () => this.saveContextToStorage());
            this.$watch('contextId', () => this.saveContextToStorage());
        },

        /**
         * Load active context from sessionStorage
         */
        loadContextFromStorage() {
            try {
                const stored = sessionStorage.getItem('active_context');
                if (!stored) return;

                const context = JSON.parse(stored);

                // Check if context is stale (older than 24 hours)
                if (context.timestamp && (Date.now() - context.timestamp > 86400000)) {
                    sessionStorage.removeItem('active_context');
                    return;
                }

                if (context.entityType && context.entityId) {
                    // Context exists in storage, will be loaded by Livewire
                    this.hasActiveContext = true;
                }
            } catch (e) {
                console.error('[Context Footer] Error loading from storage:', e);
            }
        },

        /**
         * Save context to sessionStorage
         */
        saveContextToStorage() {
            if (this.contextType && this.contextId) {
                sessionStorage.setItem('active_context', JSON.stringify({
                    entityType: this.contextType,
                    entityId: this.contextId,
                    timestamp: Date.now(),
                }));
            } else {
                sessionStorage.removeItem('active_context');
            }
        },

        /**
         * Setup event listeners for context changes
         */
        setupEventListeners() {
            // Listen for entity updates from entityStore
            window.addEventListener('entity-updated', (event) => {
                if (event.detail.entityType === this.contextType &&
                    event.detail.entityId == this.contextId) {
                    // Context data changed, reload
                    this.contextData = event.detail.data || this.contextData;
                }
            });

            // Listen for context changes
            window.addEventListener('active-context-changed', (event) => {
                this.hasActiveContext = true;
            });

            // Listen for context cleared
            window.addEventListener('active-context-cleared', (event) => {
                this.hasActiveContext = false;
                sessionStorage.removeItem('active_context');
            });
        },

        /**
         * Handle context change event
         */
        handleContextChange(detail) {
            if (detail && detail.entityType && detail.entityId) {
                this.contextType = detail.entityType;
                this.contextId = detail.entityId;
                this.hasActiveContext = true;
            }
        },

        /**
         * Handle entity update event
         */
        handleEntityUpdate(detail) {
            if (detail.entityType === this.contextType &&
                detail.entityId == this.contextId) {
                // Trigger Livewire to reload
                if (this.$wire) {
                    this.$wire.loadActiveContext();
                }
            }
        },

        /**
         * Get minimized preview text (first 2 fields summary)
         */
        getMinimizedPreview() {
            if (!this.contextData) return '—';

            // Context-specific preview logic
            switch (this.contextType) {
                case 'project':
                    return this.contextData.project_number || 'Project';

                case 'sale':
                    return this.contextData.order_number || 'Order';

                case 'inventory':
                    return this.contextData.name || 'Item';

                case 'production':
                    return this.contextData.job_number || 'Job';

                default:
                    return this.contextConfig.name;
            }
        },

        /**
         * Save current form by triggering Filament's save button
         */
        saveCurrentForm() {
            // Try multiple strategies to find the save button
            let saveButton = null;

            // Strategy 1: Find by text content
            const allButtons = document.querySelectorAll('button');
            for (const btn of allButtons) {
                const text = btn.textContent.trim();
                const isSubmit = btn.type === 'submit';
                if (isSubmit && (text === 'Save' || text === 'Save changes') &&
                    !btn.closest('[x-data*="contextFooter"]')) {
                    saveButton = btn;
                    break;
                }
            }

            // Strategy 2: Find in Filament form actions
            if (!saveButton) {
                saveButton = document.querySelector('.fi-form-actions button[type="submit"], .fi-fo-actions button[type="submit"]');
            }

            // Strategy 3: Find in sticky footer
            if (!saveButton) {
                const stickyButton = document.querySelector('.fi-sticky button[type="submit"]');
                if (stickyButton && !stickyButton.closest('[x-data*="contextFooter"]')) {
                    saveButton = stickyButton;
                }
            }

            if (saveButton) {
                saveButton.click();
            } else {
                console.error('[Context Footer] Cannot find Filament save button');
            }
        },
    };
}

// Register component for Livewire-aware loading
registerComponent('contextFooter', contextFooter);

export default contextFooter;
