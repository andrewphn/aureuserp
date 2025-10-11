/**
 * Form Session Data Persistence
 * Automatically saves and restores Filament form data using sessionStorage
 */

document.addEventListener('alpine:init', () => {
    Alpine.store('formPersistence', {
        /**
         * Get the storage key for a form
         */
        getFormKey(resource, recordId = null) {
            const key = `form_${resource}`;
            return recordId ? `${key}_${recordId}` : `${key}_new`;
        },

        /**
         * Save form data to sessionStorage
         */
        saveFormData(resource, data, recordId = null) {
            const key = this.getFormKey(resource, recordId);
            try {
                sessionStorage.setItem(key, JSON.stringify({
                    data: data,
                    timestamp: Date.now(),
                    url: window.location.href
                }));
                console.log(`[FormPersistence] Saved form data for ${resource}`, data);
            } catch (e) {
                console.error('[FormPersistence] Failed to save:', e);
            }
        },

        /**
         * Load form data from sessionStorage
         */
        loadFormData(resource, recordId = null) {
            const key = this.getFormKey(resource, recordId);
            try {
                const stored = sessionStorage.getItem(key);
                if (!stored) return null;

                const parsed = JSON.parse(stored);

                // Clear data older than 1 hour
                if (Date.now() - parsed.timestamp > 3600000) {
                    this.clearFormData(resource, recordId);
                    return null;
                }

                console.log(`[FormPersistence] Restored form data for ${resource}`, parsed.data);
                return parsed.data;
            } catch (e) {
                console.error('[FormPersistence] Failed to load:', e);
                return null;
            }
        },

        /**
         * Clear form data from sessionStorage
         */
        clearFormData(resource, recordId = null) {
            const key = this.getFormKey(resource, recordId);
            sessionStorage.removeItem(key);
            console.log(`[FormPersistence] Cleared form data for ${resource}`);
        }
    });
});

document.addEventListener('livewire:init', () => {
    // Auto-save form data when it changes
    Livewire.hook('commit', ({ component, commit, respond }) => {
        // Only save on create/edit pages
        if (component.name && (component.name.includes('Create') || component.name.includes('Edit'))) {
            const resource = component.name;
            const recordId = component.$wire?.record?.id || null;

            // Get form data from Livewire component
            const formData = component.$wire?.data || {};

            // Save to session
            Alpine.store('formPersistence').saveFormData(resource, formData, recordId);
        }
    });

    // Restore form data on page load
    Livewire.on('form-loaded', (event) => {
        const { resource, recordId } = event[0] || event;

        const stored = Alpine.store('formPersistence').loadFormData(resource, recordId);

        if (stored) {
            // Get Livewire component
            const component = Livewire.all()[0];

            if (component && component.$wire) {
                // Restore form data
                Object.keys(stored).forEach(key => {
                    if (component.$wire[key] !== undefined) {
                        component.$wire.set(key, stored[key]);
                    }
                });

                console.log('[FormPersistence] Form data restored');
            }
        }
    });

    // Clear session data on successful save
    Livewire.on('form-saved', (event) => {
        const { resource, recordId } = event[0] || event;
        Alpine.store('formPersistence').clearFormData(resource, recordId);
    });
});

// Auto-restore on page load for Filament forms
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        const component = Livewire.all()[0];

        if (component && component.$wire && component.name) {
            const isFormPage = component.name.includes('Create') || component.name.includes('Edit');

            if (isFormPage) {
                const resource = component.name;
                const recordId = component.$wire?.record?.id || null;
                const stored = Alpine.store('formPersistence').loadFormData(resource, recordId);

                if (stored && Object.keys(stored).length > 0) {
                    // Ask user if they want to restore
                    if (confirm('You have unsaved form data. Would you like to restore it?')) {
                        Object.keys(stored).forEach(key => {
                            if (component.$wire[key] !== undefined) {
                                component.$wire.set(key, stored[key]);
                            }
                        });

                        console.log('[FormPersistence] Form data restored from previous session');
                    } else {
                        Alpine.store('formPersistence').clearFormData(resource, recordId);
                    }
                }
            }
        }
    }, 500);
});
