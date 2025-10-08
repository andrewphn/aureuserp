/**
 * Annotation Context Loader
 * Loads available entities (rooms, locations, runs, cabinets) from API
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
