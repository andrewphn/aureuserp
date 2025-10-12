/**
 * Centralized Entity Store
 * Cross-page session-based data pool for entities (customers, projects, orders, etc.)
 * Allows updating entity data from anywhere in the app without saving to database
 */

document.addEventListener('alpine:init', () => {
    Alpine.store('entityStore', {
        /**
         * Get storage key for an entity
         */
        getEntityKey(entityType, entityId = null) {
            return entityId
                ? `entity_${entityType}_${entityId}`
                : `entity_${entityType}_new`;
        },

        /**
         * Get entity data from session store
         */
        getEntity(entityType, entityId = null) {
            const key = this.getEntityKey(entityType, entityId);
            try {
                const stored = sessionStorage.getItem(key);
                if (!stored) return null;

                const parsed = JSON.parse(stored);

                // Clear data older than 24 hours
                if (Date.now() - parsed.timestamp > 86400000) {
                    this.clearEntity(entityType, entityId);
                    return null;
                }

                console.log(`[EntityStore] Retrieved ${entityType}:`, parsed.data);
                return parsed.data;
            } catch (e) {
                console.error('[EntityStore] Failed to retrieve:', e);
                return null;
            }
        },

        /**
         * Update entity data in session store (merge with existing)
         */
        updateEntity(entityType, entityId = null, updates = {}) {
            const key = this.getEntityKey(entityType, entityId);
            try {
                // Get existing data or start fresh
                const existing = this.getEntity(entityType, entityId) || {};

                // Deep merge updates with existing data
                const merged = this.deepMerge(existing, updates);

                sessionStorage.setItem(key, JSON.stringify({
                    data: merged,
                    timestamp: Date.now(),
                    entityType: entityType,
                    entityId: entityId
                }));

                console.log(`[EntityStore] Updated ${entityType}:`, merged);

                // Dispatch event so other components can react
                window.dispatchEvent(new CustomEvent('entity-updated', {
                    detail: { entityType, entityId, data: merged }
                }));

                return merged;
            } catch (e) {
                console.error('[EntityStore] Failed to update:', e);
                return null;
            }
        },

        /**
         * Set entity data (replace entirely)
         */
        setEntity(entityType, entityId = null, data = {}) {
            const key = this.getEntityKey(entityType, entityId);
            try {
                sessionStorage.setItem(key, JSON.stringify({
                    data: data,
                    timestamp: Date.now(),
                    entityType: entityType,
                    entityId: entityId
                }));

                console.log(`[EntityStore] Set ${entityType}:`, data);

                window.dispatchEvent(new CustomEvent('entity-updated', {
                    detail: { entityType, entityId, data }
                }));

                return data;
            } catch (e) {
                console.error('[EntityStore] Failed to set:', e);
                return null;
            }
        },

        /**
         * Clear entity data (after successful save to database)
         */
        clearEntity(entityType, entityId = null) {
            const key = this.getEntityKey(entityType, entityId);
            sessionStorage.removeItem(key);
            console.log(`[EntityStore] Cleared ${entityType}`);

            window.dispatchEvent(new CustomEvent('entity-cleared', {
                detail: { entityType, entityId }
            }));
        },

        /**
         * Clear all entity data
         */
        clearAll() {
            const keys = Object.keys(sessionStorage).filter(key => key.startsWith('entity_'));
            keys.forEach(key => sessionStorage.removeItem(key));
            console.log(`[EntityStore] Cleared all entities (${keys.length} items)`);
        },

        /**
         * Deep merge two objects
         */
        deepMerge(target, source) {
            const output = { ...target };

            for (const key in source) {
                if (source[key] instanceof Object && key in target) {
                    output[key] = this.deepMerge(target[key], source[key]);
                } else {
                    output[key] = source[key];
                }
            }

            return output;
        },

        /**
         * Get a specific field from entity
         */
        getEntityField(entityType, entityId, fieldPath) {
            const entity = this.getEntity(entityType, entityId);
            if (!entity) return null;

            // Support dot notation: "address.street"
            const keys = fieldPath.split('.');
            let value = entity;

            for (const key of keys) {
                if (value && typeof value === 'object' && key in value) {
                    value = value[key];
                } else {
                    return null;
                }
            }

            return value;
        },

        /**
         * Update a specific field in entity
         */
        updateEntityField(entityType, entityId, fieldPath, value) {
            const keys = fieldPath.split('.');
            const update = {};

            // Build nested object for deep merge
            let current = update;
            for (let i = 0; i < keys.length - 1; i++) {
                current[keys[i]] = {};
                current = current[keys[i]];
            }
            current[keys[keys.length - 1]] = value;

            return this.updateEntity(entityType, entityId, update);
        },

        /**
         * Set the active context (current entity being worked on)
         * This maintains context across page navigation
         */
        setActiveContext(entityType, entityId, data = null) {
            try {
                sessionStorage.setItem('active_context', JSON.stringify({
                    entityType,
                    entityId,
                    timestamp: Date.now()
                }));

                console.log(`[EntityStore] Set active context: ${entityType} #${entityId || 'new'}`);

                // If data provided, also update entity store
                if (data) {
                    this.setEntity(entityType, entityId, data);
                }

                // Dispatch event so UI can react
                window.dispatchEvent(new CustomEvent('active-context-changed', {
                    detail: { entityType, entityId }
                }));

                return true;
            } catch (e) {
                console.error('[EntityStore] Failed to set active context:', e);
                return false;
            }
        },

        /**
         * Get the currently active context
         */
        getActiveContext() {
            try {
                const stored = sessionStorage.getItem('active_context');
                if (!stored) return null;

                const context = JSON.parse(stored);

                // Clear context older than 24 hours
                if (Date.now() - context.timestamp > 86400000) {
                    this.clearActiveContext();
                    return null;
                }

                return context;
            } catch (e) {
                console.error('[EntityStore] Failed to get active context:', e);
                return null;
            }
        },

        /**
         * Clear the active context
         */
        clearActiveContext() {
            try {
                sessionStorage.removeItem('active_context');
                console.log('[EntityStore] Cleared active context');

                window.dispatchEvent(new CustomEvent('active-context-changed', {
                    detail: { entityType: null, entityId: null }
                }));

                return true;
            } catch (e) {
                console.error('[EntityStore] Failed to clear active context:', e);
                return false;
            }
        },

        /**
         * Check if a specific entity is the active context
         */
        isActiveContext(entityType, entityId) {
            const active = this.getActiveContext();
            if (!active) return false;

            return active.entityType === entityType && active.entityId === entityId;
        }
    });
});

document.addEventListener('livewire:init', () => {
    /**
     * Auto-sync Livewire form data to entity store
     */
    Livewire.hook('commit', ({ component }) => {
        // Detect entity type from component name
        const entityType = component.name?.split('\\').pop()?.replace(/Create|Edit|Resource/g, '').toLowerCase();

        if (!entityType) return;

        // Get entity ID if editing existing record
        const entityId = component.$wire?.record?.id || null;

        // Get form data
        const formData = component.$wire?.data || {};

        if (Object.keys(formData).length > 0) {
            Alpine.store('entityStore').updateEntity(entityType, entityId, formData);
        }
    });

    /**
     * Listen for entity updates from other pages
     */
    window.addEventListener('entity-updated', (event) => {
        const { entityType, entityId, data } = event.detail;

        // Find Livewire components that match this entity
        Livewire.all().forEach(component => {
            const componentEntityType = component.name?.split('\\').pop()?.replace(/Create|Edit|Resource/g, '').toLowerCase();
            const componentEntityId = component.$wire?.record?.id || null;

            if (componentEntityType === entityType && componentEntityId === entityId) {
                // Update Livewire component with new data
                Object.keys(data).forEach(key => {
                    if (component.$wire && component.$wire[key] !== undefined) {
                        component.$wire.set(key, data[key]);
                    }
                });

                console.log(`[EntityStore] Synced ${entityType} to Livewire component`);
            }
        });
    });

    /**
     * Clear entity store on successful save
     */
    Livewire.on('entity-saved', (event) => {
        const { entityType, entityId } = event[0] || event;
        Alpine.store('entityStore').clearEntity(entityType, entityId);
    });
});

/**
 * Auto-restore entity data on page load
 */
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        const component = Livewire.all()[0];

        if (component && component.$wire && component.name) {
            const entityType = component.name.split('\\').pop()?.replace(/Create|Edit|Resource/g, '').toLowerCase();
            const entityId = component.$wire?.record?.id || null;

            if (entityType) {
                const stored = Alpine.store('entityStore').getEntity(entityType, entityId);

                if (stored && Object.keys(stored).length > 0) {
                    // Auto-restore without prompting (since this is cross-page data)
                    Object.keys(stored).forEach(key => {
                        if (component.$wire && component.$wire[key] !== undefined) {
                            component.$wire.set(key, stored[key]);
                        }
                    });

                    console.log(`[EntityStore] Restored ${entityType} data from session`);
                }
            }
        }
    }, 500);
});

/**
 * Global helper functions for manual updates from anywhere
 */
window.updateEntityData = function(entityType, entityId, updates) {
    return Alpine.store('entityStore').updateEntity(entityType, entityId, updates);
};

window.getEntityData = function(entityType, entityId) {
    return Alpine.store('entityStore').getEntity(entityType, entityId);
};

window.updateEntityField = function(entityType, entityId, fieldPath, value) {
    return Alpine.store('entityStore').updateEntityField(entityType, entityId, fieldPath, value);
};

window.getEntityField = function(entityType, entityId, fieldPath) {
    return Alpine.store('entityStore').getEntityField(entityType, entityId, fieldPath);
};

window.setActiveContext = function(entityType, entityId, data = null) {
    return Alpine.store('entityStore').setActiveContext(entityType, entityId, data);
};

window.getActiveContext = function() {
    return Alpine.store('entityStore').getActiveContext();
};

window.clearActiveContext = function() {
    return Alpine.store('entityStore').clearActiveContext();
};
