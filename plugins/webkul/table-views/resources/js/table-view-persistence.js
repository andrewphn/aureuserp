// Table View State Persistence using localStorage
document.addEventListener('alpine:init', () => {
    Alpine.store('tableViews', {
        /**
         * Get stored view for a resource
         */
        getStoredView(resource) {
            const key = `table_view_${resource}`;
            return localStorage.getItem(key);
        },

        /**
         * Save view for a resource
         */
        saveView(resource, view) {
            const key = `table_view_${resource}`;
            localStorage.setItem(key, view);
            console.log(`[TableViews] Saved view "${view}" for ${resource}`);
        },

        /**
         * Clear stored view for a resource
         */
        clearView(resource) {
            const key = `table_view_${resource}`;
            localStorage.removeItem(key);
        }
    });
});

// Listen for Livewire table-view-changed events
document.addEventListener('livewire:init', () => {
    Livewire.on('table-view-changed', (event) => {
        const { resource, view } = event[0] || event;

        if (resource && view) {
            Alpine.store('tableViews').saveView(resource, view);
        }
    });
});

// Initialize stored view on page load
document.addEventListener('DOMContentLoaded', () => {
    // Wait for Livewire to be ready
    setTimeout(() => {
        const livewireComponent = Livewire.all()[0];

        if (livewireComponent && typeof livewireComponent.$wire !== 'undefined') {
            const resource = livewireComponent.$wire.__instance?.constructor?.name;

            if (resource) {
                const storedView = Alpine.store('tableViews').getStoredView(resource);

                // Only restore if no URL parameter is set
                if (storedView && !livewireComponent.$wire.activeTableView) {
                    console.log(`[TableViews] Restoring view "${storedView}" for ${resource}`);
                    livewireComponent.$wire.set('activeTableView', storedView);
                    livewireComponent.$wire.call('applyTableViewFilters');
                }
            }
        }
    }, 100);
});
