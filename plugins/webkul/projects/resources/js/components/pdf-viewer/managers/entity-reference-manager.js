/**
 * Entity Reference Manager
 *
 * Manages entity references (rooms, locations, cabinet runs, cabinets)
 * associated with annotations.
 *
 * @module entity-reference-manager
 */

/**
 * Add entity reference to an annotation
 * @param {number} annotationId - ID of the annotation
 * @param {string} entityType - 'room', 'location', 'cabinet_run', 'cabinet'
 * @param {number} entityId - ID of the entity
 * @param {string} referenceType - 'primary', 'secondary', 'context'
 * @param {Object} state - Component state
 */
export function addEntityReference(annotationId, entityType, entityId, referenceType = 'primary', state) {
    if (!state.annotationReferences[annotationId]) {
        state.annotationReferences[annotationId] = [];
    }

    // Check if reference already exists
    const exists = state.annotationReferences[annotationId].some(
        ref => ref.entity_type === entityType && ref.entity_id === entityId
    );

    if (!exists) {
        state.annotationReferences[annotationId].push({
            entity_type: entityType,
            entity_id: entityId,
            reference_type: referenceType
        });

        console.log(`✓ Added ${referenceType} reference: ${entityType} #${entityId} to annotation #${annotationId}`);
    }
}

/**
 * Remove entity reference from an annotation
 * @param {number} annotationId - ID of the annotation
 * @param {string} entityType - 'room', 'location', 'cabinet_run', 'cabinet'
 * @param {number} entityId - ID of the entity
 * @param {Object} state - Component state
 */
export function removeEntityReference(annotationId, entityType, entityId, state) {
    if (state.annotationReferences[annotationId]) {
        state.annotationReferences[annotationId] = state.annotationReferences[annotationId].filter(
            ref => !(ref.entity_type === entityType && ref.entity_id === entityId)
        );

        console.log(`✓ Removed reference: ${entityType} #${entityId} from annotation #${annotationId}`);
    }
}

/**
 * Get all entity references for an annotation
 * @param {number} annotationId - ID of the annotation
 * @param {Object} state - Component state
 * @returns {Array} Array of entity references
 */
export function getEntityReferences(annotationId, state) {
    return state.annotationReferences[annotationId] || [];
}

/**
 * Get references by entity type
 * @param {number} annotationId - ID of the annotation
 * @param {string} entityType - 'room', 'location', 'cabinet_run', 'cabinet'
 * @param {Object} state - Component state
 * @returns {Array} Array of entity references matching the type
 */
export function getReferencesByType(annotationId, entityType, state) {
    const allReferences = getEntityReferences(annotationId, state);
    return allReferences.filter(ref => ref.entity_type === entityType);
}

/**
 * Check if annotation has reference to specific entity
 * @param {number} annotationId - ID of the annotation
 * @param {string} entityType - 'room', 'location', 'cabinet_run', 'cabinet'
 * @param {number} entityId - ID of the entity
 * @param {Object} state - Component state
 * @returns {boolean}
 */
export function hasEntityReference(annotationId, entityType, entityId, state) {
    const references = getEntityReferences(annotationId, state);
    return references.some(ref => ref.entity_type === entityType && ref.entity_id === entityId);
}

/**
 * Clear all references for an annotation
 * @param {number} annotationId - ID of the annotation
 * @param {Object} state - Component state
 */
export function clearAnnotationReferences(annotationId, state) {
    if (state.annotationReferences[annotationId]) {
        const count = state.annotationReferences[annotationId].length;
        delete state.annotationReferences[annotationId];
        console.log(`✓ Cleared ${count} references from annotation #${annotationId}`);
    }
}
