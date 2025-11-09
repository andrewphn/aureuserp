/**
 * Annotation Manager
 * Handles annotation loading, saving, and CRUD operations
 */

import { getColorForType } from './state-manager.js';
import { pdfToScreen } from './coordinate-transform.js';
import { getCsrfToken } from '../utilities.js';

/**
 * Load annotations for current page
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @returns {Promise<void>}
 */
export async function loadAnnotations(state, refs) {
    console.log(`📥 Loading annotations for page ${state.currentPage} (pdfPageId: ${state.pdfPageId})...`);

    // Clear existing annotations
    state.annotations = [];

    try {
        const response = await fetch(`/api/pdf/page/${state.pdfPageId}/annotations`);
        const data = await response.json();

        if (data.success && data.annotations) {
            // Convert loaded annotations to screen coordinates
            state.annotations = data.annotations.map(anno => transformAnnotationFromAPI(anno, state, refs));

            // Post-process: Recursively populate parent entity connections
            state.annotations.forEach(anno => populateParentConnections(anno, state));

            console.log(`✓ Loaded ${state.annotations.length} annotations with parent connections`);

            // Debug log entity connections
            state.annotations.forEach(anno => {
                if (anno.type === 'cabinet_run' || anno.type === 'cabinet') {
                    console.log(`  ${anno.type === 'cabinet_run' ? '📦' : '🗄️'} ${anno.label}:`,
                        `roomId=${anno.roomId}, locationId=${anno.locationId}, cabinetRunId=${anno.cabinetRunId}`);
                }
            });

            // If in isolation mode, re-apply visibility filter
            if (state.isolationMode) {
                console.log('🔍 [LOAD] Re-applying isolation filter to newly loaded annotations');
                applyIsolationFilter(state);
            }
        }
    } catch (error) {
        console.error('Failed to load annotations:', error);
        state.error = error.message;
    }
}

/**
 * Transform annotation data from API to internal format
 * @param {Object} anno - Annotation from API
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs
 * @returns {Object} Transformed annotation
 */
function transformAnnotationFromAPI(anno, state, refs) {
    // Transform normalized coordinates to screen position
    const screenPos = pdfToScreen(
        anno.x * state.pageDimensions.width,
        (1 - anno.y) * state.pageDimensions.height,  // Invert Y
        anno.width * state.pageDimensions.width,
        anno.height * state.pageDimensions.height,
        refs,
        state
    );

    return {
        id: anno.id,
        type: anno.annotation_type,
        parentId: anno.parent_annotation_id,
        pdfX: anno.x * state.pageDimensions.width,
        pdfY: (1 - anno.y) * state.pageDimensions.height,
        pdfWidth: anno.width * state.pageDimensions.width,
        pdfHeight: anno.height * state.pageDimensions.height,
        normalizedX: anno.x,
        normalizedY: anno.y,
        screenX: screenPos.x,
        screenY: screenPos.y,
        screenWidth: screenPos.width,
        screenHeight: screenPos.height,
        roomId: anno.room_id,
        roomLocationId: anno.room_location_id,
        cabinetRunId: anno.cabinet_run_id,
        cabinetSpecId: anno.cabinet_specification_id,
        viewType: anno.view_type,
        label: anno.text || 'Annotation',
        color: anno.color || getColorForType(anno.annotation_type),
        notes: anno.notes,
        pageNumber: state.currentPage,
        pdfPageId: state.pdfPageId,
        projectId: state.projectId
    };
}

/**
 * Recursively populate parent entity connections for annotation
 * @param {Object} anno - Annotation to populate
 * @param {Object} state - Component state
 * @param {Set} visited - Set of visited annotation IDs (prevents loops)
 */
function populateParentConnections(anno, state, visited = new Set()) {
    // Prevent infinite loops
    if (visited.has(anno.id)) return;
    visited.add(anno.id);

    // Add roomName using helper function
    if (anno.roomId && !anno.roomName) {
        anno.roomName = getRoomNameById(anno.roomId, state);
    }

    // If has parent, inherit properties recursively
    if (anno.parentId) {
        const parentAnno = state.annotations.find(a => a.id === anno.parentId);
        if (parentAnno) {
            // First, ensure parent has its connections populated
            populateParentConnections(parentAnno, state, visited);

            // Inherit room context from any parent
            if (!anno.roomId && parentAnno.roomId) {
                anno.roomId = parentAnno.roomId;
                anno.roomName = parentAnno.roomName;
            }

            // Inherit location context based on parent type
            if (parentAnno.type === 'location') {
                anno.locationId = parentAnno.roomLocationId;
                anno.locationName = parentAnno.label;
            } else if (parentAnno.locationId) {
                anno.locationId = parentAnno.locationId;
                anno.locationName = parentAnno.locationName;
            }

            // Inherit cabinet run context
            if (parentAnno.type === 'cabinet_run') {
                anno.cabinetRunId = parentAnno.cabinetRunId;
                anno.cabinetRunName = parentAnno.label;
            } else if (parentAnno.cabinetRunId) {
                anno.cabinetRunId = parentAnno.cabinetRunId;
                anno.cabinetRunName = parentAnno.cabinetRunName;
            }
        }
    }
}

/**
 * Apply isolation filter to annotations
 * @param {Object} state - Component state
 */
function applyIsolationFilter(state) {
    state.hiddenAnnotations = [];
    state.annotations.forEach(a => {
        if (!isAnnotationVisibleInIsolation(a, state)) {
            console.log(`👁️ [LOAD] Hiding annotation ${a.id} (${a.label} - type: ${a.type})`);
            state.hiddenAnnotations.push(a.id);
        }
    });
    console.log(`👁️ [LOAD] Hidden annotations after filter: [${state.hiddenAnnotations.join(', ')}]`);
}

/**
 * Save annotations to server
 * @param {Object} state - Component state
 * @param {Function} reloadCallback - Callback to reload annotations after save
 * @param {Boolean} silent - If true, don't show success alert (for auto-save)
 * @returns {Promise<void>}
 */
export async function saveAnnotations(state, reloadCallback, silent = false) {
    console.log('💾 Saving annotations...', state.annotations);

    try {
        // Transform annotations to API format
        const annotationsData = state.annotations.map(anno => {
            const isNewAnnotation = anno.id && anno.id.toString().startsWith('temp_');

            const annotationData = {
                annotation_type: anno.type,
                parent_annotation_id: anno.parentId || null,
                x: anno.normalizedX,
                y: anno.normalizedY,
                width: anno.pdfWidth / state.pageDimensions.width,
                height: anno.pdfHeight / state.pageDimensions.height,
                text: anno.label,
                color: anno.color,
                view_type: anno.viewType || 'plan',
                notes: anno.notes || null,
                room_type: anno.type
            };

            // For NEW annotations, check if we should link to existing entities or create new ones
            if (isNewAnnotation) {
                // If a cabinet is selected in tree, link this annotation to that cabinet
                if (anno.type === 'cabinet' && state.activeCabinetId) {
                    annotationData.cabinet_specification_id = state.activeCabinetId;
                    annotationData.cabinet_run_id = state.activeCabinetRunId;
                    annotationData.room_location_id = state.activeLocationId;
                    annotationData.room_id = state.activeRoomId;
                    console.log(`🔗 Linking new annotation to existing cabinet ${state.activeCabinetId}`);
                } else {
                    // Set hierarchy IDs from annotation or active context
                    annotationData.room_id = anno.roomId || state.activeRoomId || null;
                    annotationData.room_location_id = anno.roomLocationId || state.activeLocationId || null;
                    annotationData.cabinet_run_id = anno.cabinetRunId || state.activeCabinetRunId || null;
                    annotationData.cabinet_specification_id = anno.cabinetSpecId || null;

                    // Add context for entity creation
                    annotationData.context = {
                        project_id: state.projectId,
                        room_id: annotationData.room_id,
                        room_location_id: annotationData.room_location_id,
                        cabinet_run_id: annotationData.cabinet_run_id,
                        location_type: 'wall',
                        run_type: 'base',
                        position_in_run: 0,
                        product_variant_id: 1,
                    };

                    console.log('🆕 New annotation will create entity:', annotationData.context);
                }
            } else {
                // Existing annotation - preserve current entity links
                annotationData.room_id = anno.roomId || null;
                annotationData.room_location_id = anno.roomLocationId || null;
                annotationData.cabinet_run_id = anno.cabinetRunId || null;
                annotationData.cabinet_specification_id = anno.cabinetSpecId || null;
            }

            return annotationData;
        });

        const response = await fetch(`/api/pdf/page/${state.pdfPageId}/annotations`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({
                annotations: annotationsData,
                create_entities: true
            })
        });

        // Check if response is ok before parsing
        if (!response.ok) {
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('text/html')) {
                const html = await response.text();
                console.error('Server returned HTML error page:', html.substring(0, 500));
                throw new Error(`Server error (${response.status}): Check console for details`);
            }
            throw new Error(`Server error: ${response.status} ${response.statusText}`);
        }

        const data = await response.json();

        if (data.success) {
            console.log(`✓ Saved ${data.count} annotations`);

            // Only show alert if not silent (manual save)
            if (!silent) {
                alert(`Successfully saved ${data.count} annotations!`);
            }

            // Reload annotations to get server-assigned IDs
            if (reloadCallback) {
                await reloadCallback();
            }
        } else {
            throw new Error(data.error || 'Failed to save annotations');
        }
    } catch (error) {
        console.error('Failed to save annotations:', error);
        alert(`Error saving annotations: ${error.message}`);
        throw error;
    }
}

/**
 * Delete annotation
 * @param {Object} annotation - Annotation to delete
 * @param {Object} state - Component state
 * @param {Function} refreshTreeCallback - Callback to refresh tree after delete
 * @returns {Promise<void>}
 */
export async function deleteAnnotation(annotation, state, refreshTreeCallback) {
    if (!confirm(`Delete "${annotation.label}"?`)) {
        return;
    }

    console.log('🗑️ Deleting annotation:', annotation);

    // If temporary annotation, just remove from array
    if (annotation.id.toString().startsWith('temp_')) {
        state.annotations = state.annotations.filter(a => a.id !== annotation.id);
        console.log('✓ Temporary annotation removed from local state');
        return;
    }

    // Delete from server
    try {
        const response = await fetch(`/api/pdf/page/annotations/${annotation.id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            }
        });

        const data = await response.json();

        if (data.success) {
            // Remove from local state
            state.annotations = state.annotations.filter(a => a.id !== annotation.id);
            console.log('✓ Annotation deleted successfully');

            // Refresh tree to update counts
            if (refreshTreeCallback) {
                await refreshTreeCallback();
            }
        } else {
            throw new Error(data.error || 'Failed to delete annotation');
        }
    } catch (error) {
        console.error('Failed to delete annotation:', error);
        alert(`Error deleting annotation: ${error.message}`);
        throw error;
    }
}

/**
 * Edit annotation (open Livewire modal)
 * @param {Object} annotation - Annotation to edit
 * @param {Object} state - Component state
 */
export function editAnnotation(annotation, state) {
    console.log('✏️ Editing annotation:', annotation);

    // Add context for Livewire modal
    const annotationWithContext = {
        ...annotation,
        pdfPageId: state.pdfPageId,
        projectId: state.projectId
    };

    // Dispatch to Livewire component
    window.Livewire.dispatch('edit-annotation', { annotation: annotationWithContext });
}

/**
 * Find annotation by entity type and ID
 * @param {String} entityType - Entity type (room, room_location, cabinet_run)
 * @param {Number} entityId - Entity ID
 * @param {Object} state - Component state
 * @returns {Object|null} Found annotation or null
 */
export function findAnnotationByEntity(entityType, entityId, state) {
    if (!entityId || !state.annotations) return null;

    console.log(`🔍 [findAnnotationByEntity] Looking for ${entityType} with ID ${entityId}`);

    for (const anno of state.annotations) {
        if (entityType === 'room' && anno.roomId === entityId && anno.type === 'room') {
            console.log(`✅ Found room annotation:`, anno);
            return anno;
        } else if (entityType === 'room_location' && anno.roomLocationId === entityId && anno.type === 'location') {
            console.log(`✅ Found location annotation:`, anno);
            return anno;
        } else if (entityType === 'cabinet_run' && anno.cabinetRunId === entityId && anno.type === 'cabinet_run') {
            console.log(`✅ Found cabinet run annotation:`, anno);
            return anno;
        }
    }

    console.log(`❌ No annotation found for ${entityType} with ID ${entityId}`);
    return null;
}

/**
 * Check if entity already has annotation on current page
 * @param {String} drawMode - Current draw mode
 * @param {Object} state - Component state
 * @returns {Object|null} Existing annotation or null
 */
export function checkForDuplicateEntity(drawMode, state) {
    if (!state.annotations) return null;

    console.log(`🔍 [checkForDuplicateEntity] Checking for duplicates - mode: ${drawMode}`);

    // Determine entity type and ID based on draw mode
    let entityType = null;
    let entityId = null;

    if (drawMode === 'room') {
        entityType = 'room';
        entityId = state.activeRoomId;
    } else if (drawMode === 'location') {
        entityType = 'room_location';
        entityId = state.activeLocationId;
    } else if (drawMode === 'cabinet_run') {
        entityType = 'cabinet_run';
        entityId = state.activeLocationId;
    } else if (drawMode === 'cabinet') {
        // Allow multiple cabinets
        return null;
    }

    // If no entity selected (creating new), allow drawing
    if (!entityId) {
        console.log(`✅ No entity selected - allowing new entity creation`);
        return null;
    }

    // Search for existing annotation
    const existing = findAnnotationByEntity(entityType, entityId, state);

    if (existing) {
        console.log(`⚠️ Duplicate found! Entity ${entityId} already has annotation:`, existing);
        return existing;
    }

    console.log(`✅ No duplicate found - safe to draw`);
    return null;
}

/**
 * Helper functions
 */

function getRoomNameById(roomId, state) {
    if (!state.tree || !roomId) return '';
    const room = state.tree.find(r => r.id === roomId);
    return room ? room.name : '';
}

function isAnnotationVisibleInIsolation(anno, state) {
    // This is a placeholder - actual implementation is in isolation-mode-manager.js
    // We'll import and use that function later
    return true;
}

/**
 * Highlight annotation temporarily
 * @param {Object} annotation - Annotation to highlight
 * @param {Object} state - Component state
 */
export function highlightAnnotation(annotation, state) {
    const originalColor = annotation.color;
    annotation.color = '#ff0000'; // Red highlight

    // Restore after 2 seconds
    setTimeout(() => {
        annotation.color = originalColor;
    }, 2000);

    console.log(`🎯 Highlighted annotation: ${annotation.label}`);
}

/**
 * Get z-index for annotation (selected annotations come to front for easier interaction)
 * @param {Object} annotation - Annotation object
 * @param {Object} state - Component state
 * @returns {Number} Z-index value
 */
export function getAnnotationZIndex(annotation, state) {
    // Bring selected/active annotations to the front for easy resizing
    return (state.activeAnnotationId === annotation.id || state.selectedAnnotation?.id === annotation.id) ? 100 : 10;
}

/**
 * Toggle lock state for annotation
 * @param {Object} annotation - Annotation to lock/unlock
 * @param {Object} state - Component state
 */
export function toggleLockAnnotation(annotation, state) {
    annotation.locked = !annotation.locked;
    console.log(`${annotation.locked ? '🔒' : '🔓'} ${annotation.locked ? 'Locked' : 'Unlocked'} annotation: ${annotation.label}`);

    // If locking, clear active state
    if (annotation.locked && state.activeAnnotationId === annotation.id) {
        state.activeAnnotationId = null;
        state.selectedAnnotation = null;
    }
}
