/**
 * Annotation Context Loader
 * Loads available entities (rooms, locations, runs, cabinets) and metadata from API
 */

/**
 * Load annotation context (rooms, locations, runs, cabinets)
 * @param {number} pdfPageId - PDF page ID
 * @returns {Promise<Object|null>} Context data or null
 */
export async function loadAnnotationContext(pdfPageId) {
    try {
        const response = await fetch(`/api/pdf/page/${pdfPageId}/context`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (!response.ok) {
            console.warn('⚠️ Failed to load context data:', response.statusText);
            return null;
        }

        const data = await response.json();

        if (!data.success) {
            console.warn('⚠️ Context API returned error:', data.error);
            return null;
        }

        console.log('✅ Loaded context data:',
            data.context.rooms.length, 'rooms,',
            data.context.room_locations.length, 'locations,',
            data.context.cabinet_runs.length, 'runs,',
            data.context.cabinets.length, 'cabinets'
        );

        return data.context;

    } catch (error) {
        console.error('Failed to load annotation context:', error);
        return null;
    }
}

/**
 * Load existing annotations for a PDF page
 * @param {number} pdfPageId - PDF page ID
 * @returns {Promise<Array>} Array of annotations
 */
export async function loadExistingAnnotations(pdfPageId) {
    try {
        const response = await fetch(`/api/pdf/page/${pdfPageId}/annotations`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (!response.ok) {
            console.warn('⚠️ No existing annotations found');
            return [];
        }

        const data = await response.json();
        console.log('✅ Loaded existing annotations:', data.annotations.length);

        return data.annotations || [];

    } catch (error) {
        console.error('Failed to load existing annotations:', error);
        return [];
    }
}

/**
 * Load cabinet runs for a PDF page
 * @param {number} pdfPageId - PDF page ID
 * @returns {Promise<Array>} Array of cabinet runs
 */
export async function loadCabinetRuns(pdfPageId) {
    try {
        const response = await fetch(`/api/pdf/annotations/page/${pdfPageId}/cabinet-runs`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (!response.ok) {
            console.warn('⚠️ Failed to load cabinet runs');
            return [];
        }

        const data = await response.json();
        console.log('✅ Loaded cabinet runs:', data.cabinet_runs?.length || 0);

        return data.cabinet_runs || [];

    } catch (error) {
        console.error('Failed to load cabinet runs:', error);
        return [];
    }
}

/**
 * Load project number for a PDF page
 * @param {number} pdfPageId - PDF page ID
 * @returns {Promise<string>} Project number or default
 */
export async function loadProjectNumber(pdfPageId) {
    try {
        const response = await fetch(`/api/pdf/page/${pdfPageId}/project-number`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (!response.ok) {
            console.warn('⚠️ Failed to load project number, using default');
            return 'TFW-0001';
        }

        const data = await response.json();
        console.log('✅ Loaded project number:', data.project_number);

        return data.project_number || 'TFW-0001';

    } catch (error) {
        console.error('Failed to load project number:', error);
        return 'TFW-0001';
    }
}

/**
 * Load all metadata for annotation viewer
 * @param {number} pdfPageId - PDF page ID
 * @returns {Promise<Object>} Combined metadata
 */
export async function loadAllMetadata(pdfPageId) {
    try {
        // Load all data in parallel
        const [context, annotations, cabinetRuns, projectNumber] = await Promise.all([
            loadAnnotationContext(pdfPageId),
            loadExistingAnnotations(pdfPageId),
            loadCabinetRuns(pdfPageId),
            loadProjectNumber(pdfPageId)
        ]);

        return {
            rooms: context?.rooms || [],
            roomLocations: context?.room_locations || [],
            cabinets: context?.cabinets || [],
            cabinetRuns: cabinetRuns,
            projectId: context?.project_id || null,
            projectName: context?.project_name || '',
            projectNumber: projectNumber,
            annotations: annotations
        };

    } catch (error) {
        console.error('Failed to load all metadata:', error);
        return {
            rooms: [],
            roomLocations: [],
            cabinets: [],
            cabinetRuns: [],
            projectId: null,
            projectName: '',
            projectNumber: 'TFW-0001',
            annotations: []
        };
    }
}
