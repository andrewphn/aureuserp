/**
 * Tree Manager
 * Handles project tree loading and navigation
 */

import { getCsrfToken } from '../utilities.js';

/**
 * Load project tree from server
 * @param {Object} state - Component state
 * @returns {Promise<void>}
 */
export async function loadTree(state) {
    state.loading = true;

    try {
        const response = await fetch(`/api/projects/${state.projectId}/tree`);
        const rawTree = await response.json();

        // Ensure all tree nodes have a children property
        state.tree = ensureChildrenProperty(rawTree);
        state.treeReady = true;
        console.log('✓ Tree loaded:', state.tree);
    } catch (error) {
        state.error = 'Failed to load project tree';
        console.error(error);
    } finally {
        state.loading = false;
    }
}

/**
 * Refresh the project tree (lockout-aware wrapper for loadTree)
 * Defers refresh if resize/move operations are in progress
 * @param {Object} state - Component state
 * @param {Object} refs - Alpine.js $refs (optional)
 * @param {Object} callbacks - Callback functions (optional)
 * @returns {Promise<void>}
 */
export async function refreshTree(state, refs = null, callbacks = null) {
    // CRITICAL: Don't refresh during active resize/move operations
    if (state._resizeLockout || state.isResizing || state.isMoving) {
        console.log('⏳ Tree refresh deferred - resize/move in progress');

        // Retry after lockout clears (poll every 100ms)
        setTimeout(() => refreshTree(state, refs, callbacks), 100);
        return;
    }

    console.log('🌳 Refreshing project tree');
    await loadTree(state);

    // After tree refresh, recalculate annotation positions
    // Tree refresh can cause DOM layout shifts that misalign annotations
    if (refs && callbacks) {
        // Wait for tree to render
        if (callbacks.$nextTick) {
            await callbacks.$nextTick();
        }

        // Re-sync overlay to canvas (in case tree changed layout)
        if (callbacks.syncOverlayToCanvas) {
            callbacks.syncOverlayToCanvas();
        }

        console.log('✓ Annotation positions recalculated after tree refresh');
    }
}

/**
 * Ensure all tree nodes have a children property
 * @param {Array} nodes - Array of tree nodes
 * @returns {Array} Nodes with children property
 */
function ensureChildrenProperty(nodes) {
    if (!Array.isArray(nodes)) return [];

    return nodes.map(node => {
        const processedNode = { ...node };

        // Ensure children property exists
        if (!processedNode.children) {
            processedNode.children = [];
        } else if (Array.isArray(processedNode.children)) {
            // Recursively process children
            processedNode.children = ensureChildrenProperty(processedNode.children);
        }

        return processedNode;
    });
}

/**
 * Toggle node expansion
 * @param {String|Number} nodeId - Node ID to toggle
 * @param {Object} state - Component state
 */
export function toggleNode(nodeId, state) {
    const index = state.expandedNodes.indexOf(nodeId);
    if (index > -1) {
        state.expandedNodes.splice(index, 1);
    } else {
        state.expandedNodes.push(nodeId);
    }
}

/**
 * Check if node is expanded
 * @param {String|Number} nodeId - Node ID to check
 * @param {Object} state - Component state
 * @returns {Boolean} True if expanded
 */
export function isExpanded(nodeId, state) {
    return state.expandedNodes.includes(nodeId);
}

/**
 * Select node in tree and set context
 * @param {Object} params - Node parameters
 * @param {Object} state - Component state
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function selectNode(params, state, callbacks) {
    const { nodeId, type, name, parentRoomId = null, parentLocationId = null, parentCabinetRunId = null } = params;

    state.selectedNodeId = nodeId;

    // Build hierarchical path
    const path = [];
    let node = null;
    let parentLocationNode = null;
    let parentRoomNode = null;

    if (type === 'room') {
        // Room is the root
        path.push(nodeId);
        state.activeRoomId = nodeId;
        state.activeRoomName = name;
        state.roomSearchQuery = name;
        state.activeLocationId = null;
        state.activeLocationName = '';
        state.locationSearchQuery = '';
        node = state.tree.find(r => r.id === nodeId);

    } else if (type === 'room_location') {
        // Location - path includes room and location
        if (parentRoomId) path.push(parentRoomId);
        path.push(nodeId);
        state.activeRoomId = parentRoomId;
        state.activeLocationId = nodeId;
        state.activeLocationName = name;
        state.locationSearchQuery = name;

        parentRoomNode = state.tree.find(r => r.id === parentRoomId);
        if (parentRoomNode) {
            state.activeRoomName = parentRoomNode.name;
            state.roomSearchQuery = parentRoomNode.name;
            node = parentRoomNode.children?.find(l => l.id === nodeId);
        }

    } else if (type === 'cabinet_run') {
        // Cabinet run - path includes room, location, and cabinet run
        if (parentRoomId) path.push(parentRoomId);
        if (parentLocationId) path.push(parentLocationId);
        path.push(nodeId);
        state.activeRoomId = parentRoomId;
        state.activeLocationId = parentLocationId;
        state.activeCabinetRunId = nodeId;  // ✅ Set active cabinet run for drawing context
        state.activeCabinetRunName = name;

        parentRoomNode = state.tree.find(r => r.id === parentRoomId);
        if (parentRoomNode) {
            state.activeRoomName = parentRoomNode.name;
            state.roomSearchQuery = parentRoomNode.name;

            parentLocationNode = parentRoomNode.children?.find(l => l.id === parentLocationId);
            if (parentLocationNode) {
                state.activeLocationName = parentLocationNode.name;
                state.locationSearchQuery = parentLocationNode.name;
                node = parentLocationNode.children?.find(run => run.id === nodeId);
            }
        }

    } else if (type === 'cabinet') {
        // Cabinet - full path
        if (parentRoomId) path.push(parentRoomId);
        if (parentLocationId) path.push(parentLocationId);
        if (parentCabinetRunId) path.push(parentCabinetRunId);
        path.push(nodeId);
        state.activeRoomId = parentRoomId;
        state.activeLocationId = parentLocationId;
        state.activeCabinetRunId = parentCabinetRunId;  // ✅ Set cabinet run when selecting a cabinet
        state.activeCabinetId = nodeId;  // ✅ Set selected cabinet ID
        state.activeCabinetName = name;

        parentRoomNode = state.tree.find(r => r.id === parentRoomId);
        if (parentRoomNode) {
            state.activeRoomName = parentRoomNode.name;
            state.roomSearchQuery = parentRoomNode.name;

            parentLocationNode = parentRoomNode.children?.find(l => l.id === parentLocationId);
            if (parentLocationNode) {
                state.activeLocationName = parentLocationNode.name;
                state.locationSearchQuery = parentLocationNode.name;

                const cabinetRunNode = parentLocationNode.children?.find(run => run.id === parentCabinetRunId);
                if (cabinetRunNode) {
                    state.activeCabinetRunName = cabinetRunNode.name;
                }
            }
        }
    }

    // Store complete hierarchical path
    state.selectedPath = path;

    // Navigate to page with matching view type
    if (callbacks.navigateToNodePage) {
        await callbacks.navigateToNodePage(node, parentLocationNode, parentRoomNode);
    }

    console.log('🌳 Selected node:', { nodeId, type, name, path });
}

/**
 * Navigate to page with matching view type (with hierarchical fallback)
 * @param {Object} node - Selected node
 * @param {Object} parentLocation - Parent location node
 * @param {Object} parentRoom - Parent room node
 * @param {Object} state - Component state
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function navigateToNodePage(node, parentLocation, parentRoom, state, callbacks) {
    const currentViewType = state.activeViewType;
    let targetPage = null;

    // Try to find page with matching view type
    // 1. Try the clicked node first
    if (node && node.pages && node.pages.length > 0) {
        const matchingPage = node.pages.find(p => p.viewType === currentViewType);
        if (matchingPage) {
            targetPage = matchingPage.page;
            console.log(`📍 Found ${currentViewType} view on ${node.name} at page ${targetPage}`);
        }
    }

    // 2. Try parent location
    if (!targetPage && parentLocation && parentLocation.pages && parentLocation.pages.length > 0) {
        const matchingPage = parentLocation.pages.find(p => p.viewType === currentViewType);
        if (matchingPage) {
            targetPage = matchingPage.page;
            console.log(`📍 Found ${currentViewType} view on parent location ${parentLocation.name} at page ${targetPage}`);
        }
    }

    // 3. Try parent room
    if (!targetPage && parentRoom && parentRoom.pages && parentRoom.pages.length > 0) {
        const matchingPage = parentRoom.pages.find(p => p.viewType === currentViewType);
        if (matchingPage) {
            targetPage = matchingPage.page;
            console.log(`📍 Found ${currentViewType} view on parent room ${parentRoom.name} at page ${targetPage}`);
        }
    }

    // 4. Fall back to first available page
    if (!targetPage && node && node.pages && node.pages.length > 0) {
        targetPage = node.pages[0].page;
        console.log(`📍 No ${currentViewType} view found, using first available page ${targetPage}`);
    }

    // Navigate to the found page
    if (targetPage && targetPage !== state.currentPage) {
        if (callbacks.goToPage) {
            await callbacks.goToPage(targetPage);
        }
    }
}

/**
 * Show context menu for tree node
 * @param {MouseEvent} event - Right-click event
 * @param {Object} params - Node parameters
 * @param {Object} state - Component state
 */
export function showContextMenu(event, params, state) {
    const { nodeId, nodeType, nodeName, parentRoomId = null, parentLocationId = null } = params;

    console.log('🖱️ Right-click detected!', { nodeId, nodeType, nodeName });

    state.contextMenu = {
        show: true,
        x: event.clientX,
        y: event.clientY,
        nodeId: nodeId,
        nodeType: nodeType,
        nodeName: nodeName,
        parentRoomId: parentRoomId,
        parentLocationId: parentLocationId
    };

    console.log('✓ Context menu state updated:', state.contextMenu);
}

/**
 * Delete tree node (room, location, or cabinet run)
 * @param {Object} state - Component state
 * @param {Function} refreshCallback - Callback to refresh tree after delete
 * @returns {Promise<void>}
 */
export async function deleteTreeNode(state, refreshCallback) {
    const { nodeId, nodeType, nodeName } = state.contextMenu;

    if (!confirm(`Are you sure you want to delete "${nodeName}"? This will also delete all associated annotations and data.`)) {
        state.contextMenu.show = false;
        return;
    }

    console.log(`🗑️ Deleting ${nodeType}:`, nodeId);

    try {
        let endpoint = '';

        if (nodeType === 'room') {
            endpoint = `/api/project/room/${nodeId}`;
        } else if (nodeType === 'room_location') {
            endpoint = `/api/project/location/${nodeId}`;
        } else if (nodeType === 'cabinet_run') {
            endpoint = `/api/project/cabinet-run/${nodeId}`;
        }

        const response = await fetch(endpoint, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            }
        });

        const data = await response.json();

        if (data.success) {
            console.log(`✓ ${nodeType} deleted successfully`);

            // Close context menu
            state.contextMenu.show = false;

            // Refresh tree
            if (refreshCallback) {
                await refreshCallback();
            }

            // Clear active context if deleted node was selected
            if (state.selectedNodeId === nodeId) {
                state.activeRoomId = null;
                state.activeRoomName = '';
                state.activeLocationId = null;
                state.activeLocationName = '';
                state.roomSearchQuery = '';
                state.locationSearchQuery = '';
                state.drawMode = null;
                state.selectedNodeId = null;
            }
        } else {
            throw new Error(data.error || `Failed to delete ${nodeType}`);
        }
    } catch (error) {
        console.error(`Failed to delete ${nodeType}:`, error);
        alert(`Error deleting ${nodeType}: ${error.message}`);
        state.contextMenu.show = false;
    }
}

/**
 * Handle double-click navigation on tree node
 * @param {Object} params - Node parameters (nodeId, nodeType, parentRoomId, parentLocationId)
 * @param {Object} state - Component state
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function navigateOnDoubleClick(params, state, callbacks) {
    const { nodeId, nodeType, parentRoomId = null, parentLocationId = null } = params;

    let node = null;
    let parentLocationNode = null;
    let parentRoomNode = null;

    // Find the node in the tree based on type
    if (nodeType === 'room') {
        node = state.tree.find(r => r.id === nodeId);
    } else if (nodeType === 'room_location') {
        parentRoomNode = state.tree.find(r => r.id === parentRoomId);
        if (parentRoomNode) {
            node = parentRoomNode.children?.find(l => l.id === nodeId);
        }
    } else if (nodeType === 'cabinet_run') {
        parentRoomNode = state.tree.find(r => r.id === parentRoomId);
        if (parentRoomNode) {
            parentLocationNode = parentRoomNode.children?.find(l => l.id === parentLocationId);
            if (parentLocationNode) {
                node = parentLocationNode.children?.find(run => run.id === nodeId);
            }
        }
    }

    // Navigate to the page with matching view type
    if (node || parentLocationNode || parentRoomNode) {
        await navigateToNodePage(node, parentLocationNode, parentRoomNode, state, callbacks);
    }
}

/**
 * Build hierarchical tree from flat annotations
 * @param {Array<Object>} annotations - Annotations array
 * @returns {Array<Object>} Tree structure
 */
export function buildAnnotationTree(annotations) {
    // Create a map for quick lookup
    const annoMap = new Map();
    annotations.forEach(anno => {
        annoMap.set(anno.id, { ...anno, children: [] });
    });

    // Build the tree by connecting children to parents
    const rootNodes = [];
    annoMap.forEach(anno => {
        if (anno.parentId && annoMap.has(anno.parentId)) {
            // This annotation has a parent - add it as a child
            annoMap.get(anno.parentId).children.push(anno);
        } else {
            // This is a root node (no parent or parent not in this page)
            rootNodes.push(anno);
        }
    });

    return rootNodes;
}

/**
 * Group annotations by page number
 * @param {Object} state - Component state
 * @returns {Array<Object>} Page-grouped annotations
 */
export function getPageGroupedAnnotations(state) {
    const pages = new Map();

    // Initialize pages from pageMap
    Object.keys(state.pageMap).forEach(pageNum => {
        pages.set(parseInt(pageNum), {
            pageNumber: parseInt(pageNum),
            annotations: []
        });
    });

    // Add filtered annotations to their respective pages
    const filtered = state.filteredAnnotations || state.annotations;
    filtered.forEach(anno => {
        const pageNum = anno.pageNumber || state.currentPage;
        if (pages.has(pageNum)) {
            pages.get(pageNum).annotations.push(anno);
        } else {
            pages.set(pageNum, {
                pageNumber: pageNum,
                annotations: [anno]
            });
        }
    });

    // Build hierarchical tree for each page
    pages.forEach((page, pageNum) => {
        page.annotations = buildAnnotationTree(page.annotations);
    });

    // Convert to array and sort by page number
    return Array.from(pages.values()).sort((a, b) => a.pageNumber - b.pageNumber);
}

/**
 * Get room name by ID from tree
 * @param {Number|String} roomId - Room ID to lookup
 * @param {Object} state - Component state
 * @returns {String} Room name or empty string
 */
export function getRoomNameById(roomId, state) {
    if (!state.tree || !roomId) return '';
    const room = state.tree.find(r => r.id === roomId);
    return room ? room.name : '';
}

/**
 * Get location name by ID from tree
 * @param {Number|String} locationId - Location ID to lookup
 * @param {Object} state - Component state
 * @returns {String} Location name or empty string
 */
export function getLocationNameById(locationId, state) {
    if (!state.tree || !locationId) return '';
    for (const room of state.tree) {
        const location = room.children?.find(l => l.id === locationId);
        if (location) return location.name;
    }
    return '';
}
