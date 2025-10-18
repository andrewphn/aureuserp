/**
 * Form Auto-Population from Active Context
 *
 * When Bryan selects a project in the footer/dashboard, automatically populate
 * related fields in forms across the system (tasks, POs, designs, etc.)
 *
 * This reduces repetitive data entry and supports Bryan's ADHD-friendly workflow
 */

document.addEventListener('alpine:init', () => {
    // Listen for active context changes (when Bryan selects a project)
    window.addEventListener('active-context-changed', async (event) => {
        const { entityType, entityId } = event.detail;

        // If a project is selected, fetch its details and populate forms
        if (entityType === 'project' && entityId) {
            await populateFormsFromProject(entityId);
        }
    });

    // Also check on page load if there's an active project context
    setTimeout(() => {
        const activeContext = Alpine.store('entityStore').getActiveContext();
        if (activeContext && activeContext.entityType === 'project') {
            populateFormsFromProject(activeContext.entityId);
        }
    }, 1000);
});

/**
 * Fetch project details and populate all forms on the page
 */
async function populateFormsFromProject(projectId) {
    try {
        // Skip auto-population on project edit pages entirely
        // (prevents circular updates when EditProject sets itself as active context)
        if (window.location.pathname.includes('/project/projects/') && window.location.pathname.includes('/edit')) {
            console.log('[FormAutoPopulate] Skipped - on project edit page');
            return;
        }

        // Fetch project details from API with credentials
        const response = await fetch(`/api/projects/${projectId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });
        if (!response.ok) {
            console.warn('[FormAutoPopulate] Failed to fetch project details:', response.status);
            return;
        }

        const project = await response.json();
        console.log('[FormAutoPopulate] Loaded project:', project);

        // Wait for Livewire components to be ready
        await new Promise(resolve => setTimeout(resolve, 500));

        // Populate all Livewire forms on the page
        Livewire.all().forEach(component => {
            populateLivewireForm(component, project);
        });

        // Also dispatch event for Alpine components
        window.dispatchEvent(new CustomEvent('project-context-loaded', {
            detail: { project }
        }));

    } catch (error) {
        console.error('[FormAutoPopulate] Error:', error);
    }
}

/**
 * Populate a Livewire form component with project data
 */
function populateLivewireForm(component, project) {
    if (!component.$wire) return;

    const wire = component.$wire;

    // Skip if this component is already editing this specific project
    // (prevents circular updates when EditProject page sets itself as active context)
    if (wire.record && wire.record.id === project.id) {
        console.log('[FormAutoPopulate] Skipped - component already editing this project');
        return;
    }

    // Common field mappings from project to form
    const fieldMappings = {
        // Direct mappings
        'data.project_id': project.id,
        'project_id': project.id,
        'data.customer_id': project.customer_id,
        'customer_id': project.customer_id,
        'data.partner_id': project.customer_id, // Some forms use partner_id
        'partner_id': project.customer_id,

        // Indirect mappings
        'data.location': project.location,
        'location': project.location,
        'data.project_name': project.name,
        'project_name': project.name,
    };

    let populated = false;

    // Try to set each field if it exists in the component
    Object.entries(fieldMappings).forEach(([fieldPath, value]) => {
        if (value !== null && value !== undefined) {
            try {
                // Navigate nested paths (e.g., "data.project_id")
                const parts = fieldPath.split('.');
                let target = wire;

                // Navigate to the parent object
                for (let i = 0; i < parts.length - 1; i++) {
                    if (target[parts[i]] === undefined) {
                        return; // Field doesn't exist, skip
                    }
                    target = target[parts[i]];
                }

                const finalKey = parts[parts.length - 1];

                // Only set if field exists and is currently empty
                if (target[finalKey] !== undefined && (target[finalKey] === null || target[finalKey] === '' || target[finalKey] === undefined)) {
                    wire.set(fieldPath, value);
                    populated = true;
                    console.log(`[FormAutoPopulate] Set ${fieldPath} = ${value}`);
                }
            } catch (e) {
                // Field doesn't exist or not accessible, skip silently
            }
        }
    });

    if (populated) {
        console.log('[FormAutoPopulate] Auto-populated form fields from project context');
    }
}

/**
 * Global helper to manually trigger form population
 * Usage: window.populateFormFromActiveProject()
 */
window.populateFormFromActiveProject = async function() {
    const activeContext = Alpine.store('entityStore').getActiveContext();
    if (activeContext && activeContext.entityType === 'project') {
        await populateFormsFromProject(activeContext.entityId);
        return true;
    }
    console.warn('[FormAutoPopulate] No active project context');
    return false;
};

/**
 * Global helper to set project as active context and populate forms
 * Usage: window.setActiveProjectAndPopulate(projectId)
 */
window.setActiveProjectAndPopulate = async function(projectId) {
    // Set as active context
    Alpine.store('entityStore').setActiveContext('project', projectId);

    // Populate forms
    await populateFormsFromProject(projectId);

    return true;
};
