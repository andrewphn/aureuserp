/**
 * Annotation Saver
 * Handles saving annotations with entity creation
 */

export async function saveAnnotationsWithEntities(pdfPageId, annotations, annotationType, context) {
    try {
        // Build context object based on annotation type
        const entityContext = buildEntityContext(annotationType, context);

        // Prepare annotations with context
        const annotationsToSave = annotations.map(ann => ({
            ...ann,
            annotation_type: annotationType,
            context: entityContext
        }));

        const response = await fetch(`/api/pdf/page/${pdfPageId}/annotations`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                annotations: annotationsToSave,
                create_entities: true // Enable entity creation
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Failed to save annotations');
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Save operation failed');
        }

        console.log('✅ Saved', data.count, 'annotations');
        console.log('✅ Created', data.entities_created_count, 'entities');

        // Log created entities
        if (data.created_entities && data.created_entities.length > 0) {
            data.created_entities.forEach(entity => {
                console.log(`   - ${entity.entity_type}: ${entity.entity.name || entity.entity.cabinet_number}`);
            });
        }

        return data;

    } catch (error) {
        console.error('Failed to save annotations:', error);
        throw error;
    }
}

function buildEntityContext(annotationType, context) {
    const entityContext = {};

    switch (annotationType) {
        case 'room':
            // Room annotations don't need context (created fresh)
            break;

        case 'room_location':
            if (context.selectedRoomId) {
                entityContext.room_id = context.selectedRoomId;
                entityContext.location_type = context.locationType || 'wall';
                entityContext.sequence = context.sequence || 0;
            }
            break;

        case 'cabinet_run':
            if (context.selectedRoomId && context.selectedRoomLocationId) {
                entityContext.room_id = context.selectedRoomId;
                entityContext.room_location_id = context.selectedRoomLocationId;
                entityContext.run_type = context.selectedRunType || 'base';
            }
            break;

        case 'cabinet':
            if (context.selectedCabinetRunId) {
                entityContext.cabinet_run_id = context.selectedCabinetRunId;
                entityContext.position_in_run = context.positionInRun || 0;
                entityContext.length_inches = context.lengthInches || 0;
                entityContext.width_inches = context.widthInches || 0;
                entityContext.depth_inches = context.depthInches || 0;
                entityContext.height_inches = context.heightInches || 0;
            }
            break;

        case 'dimension':
            if (context.selectedCabinetId) {
                entityContext.cabinet_id = context.selectedCabinetId;
                entityContext.length_inches = context.lengthInches;
                entityContext.width_inches = context.widthInches;
                entityContext.depth_inches = context.depthInches;
                entityContext.height_inches = context.heightInches;
            }
            break;
    }

    return entityContext;
}
