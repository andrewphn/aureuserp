/**
 * Project Tree Sidebar Component
 *
 * Displays hierarchical project structure (Room → Location → Run → Cabinet)
 * with annotation counts and page references.
 *
 * Features:
 * - Visual hierarchy with expand/collapse
 * - Badge counts showing annotation status
 * - Click to set active context
 * - Page number indicators
 */

export function projectTreeSidebarComponent() {
    return {
        // State
        projectId: null,
        tree: null,
        expandedNodes: new Set(),
        selectedNodeId: null,
        selectedNodeType: null,
        loading: false,
        error: null,

        // Initialization
        async init(projectId) {
            this.projectId = projectId;
            await this.loadProjectTree();
        },

        // Load full project hierarchy
        async loadProjectTree() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch(`/api/project/${this.projectId}/entity-tree`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                this.tree = data.tree;

                console.log('✅ Loaded project tree:', this.tree);
            } catch (error) {
                console.error('❌ Failed to load project tree:', error);
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        // Toggle node expansion
        toggleNode(nodeId) {
            if (this.expandedNodes.has(nodeId)) {
                this.expandedNodes.delete(nodeId);
            } else {
                this.expandedNodes.add(nodeId);
            }
        },

        // Check if node is expanded
        isExpanded(nodeId) {
            return this.expandedNodes.has(nodeId);
        },

        // Select node to set as active context
        selectNode(nodeId, nodeType, nodeName) {
            this.selectedNodeId = nodeId;
            this.selectedNodeType = nodeType;

            // Emit event for context bar to pick up
            window.dispatchEvent(new CustomEvent('annotation-context-selected', {
                detail: {
                    nodeId,
                    nodeType,
                    nodeName,
                    timestamp: Date.now()
                }
            }));

            console.log('✅ Selected node:', { nodeId, nodeType, nodeName });
        },

        // Check if node is currently selected
        isSelected(nodeId) {
            return this.selectedNodeId === nodeId;
        },

        // Get icon for node type
        getNodeIcon(type) {
            const icons = {
                'room': '🏠',
                'room_location': '📍',
                'cabinet_run': '📦',
                'cabinet': '🗄️'
            };
            return icons[type] || '📄';
        },

        // Get badge variant for annotation status
        getBadgeVariant(annotationCount) {
            if (annotationCount === 0) return 'gray';
            return 'blue';
        },

        // Format page numbers for display
        formatPages(pages) {
            if (!pages || pages.length === 0) return '';
            if (pages.length === 1) return `Page ${pages[0]}`;
            if (pages.length <= 3) return `Pages ${pages.join(', ')}`;
            return `Pages ${pages[0]}–${pages[pages.length - 1]}`;
        },

        // Check if entity has annotations
        hasAnnotations(entity) {
            return entity.annotation_count > 0;
        },

        // Refresh tree after annotations are saved
        async refresh() {
            await this.loadProjectTree();
        }
    };
}
