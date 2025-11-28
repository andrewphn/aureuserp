import type { Page } from '@playwright/test';

/**
 * PDF Viewer Manager Helpers
 *
 * Utility functions for interacting with the Alpine.js-based PDF viewer
 * manager architecture in E2E tests.
 */

export interface AlpineComponentState {
    // Core state
    currentPage: number;
    totalPages: number;
    zoomLevel: number;
    annotations: any[];

    // Isolation mode state
    isolationMode: boolean;
    isolationLevel: string | null;
    isolatedRoomId: number | null;
    isolatedRoomName: string;
    isolatedLocationId: number | null;
    isolatedLocationName: string;
    isolated

CabinetRunId: number | null;
    isolatedCabinetRunName: string;

    // Tree state
    tree: any[];
    expandedNodes: number[];
    selectedNodeId: number | null;

    // Filter state
    activeFilters: any[];
    hiddenAnnotations: number[];

    // History state
    historyStack: any[];
    historyIndex: number;

    // View state
    viewType: string;
    orientation: string;
}

export class ManagerHelpers {
    constructor(private page: Page) {}

    /**
     * Wait for Alpine.js PDF viewer component to be ready
     */
    async waitForAlpineReady(timeout: number = 15000): Promise<void> {
        // Wait for Alpine.js to be loaded
        console.log('⏳ Waiting for Alpine.js...');
        await this.page.waitForFunction(
            () => window.hasOwnProperty('Alpine'),
            { timeout }
        );
        console.log('✅ Alpine.js loaded');

        // Wait for component to be ready - same check as other tests
        console.log('⏳ Waiting for component to be ready...');
        await this.page.waitForFunction(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            if (!el || !window.Alpine) return false;
            const data = Alpine.$data(el);
            return data?.systemReady === true;
        }, { timeout });

        // Wait for annotations to be loaded (fixes race condition)
        console.log('⏳ Waiting for annotations to load...');
        await this.page.waitForFunction(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            if (!el || !window.Alpine) return false;
            const data = Alpine.$data(el);
            return data.annotations && data.annotations.length > 0;
        }, { timeout: 10000 });

        // Wait for PdfViewerManagers to be available (required for manager methods)
        console.log('⏳ Waiting for PdfViewerManagers...');
        await this.page.waitForFunction(() => {
            return window.hasOwnProperty('PdfViewerManagers') &&
                   window.PdfViewerManagers &&
                   window.PdfViewerManagers.NavigationManager;
        }, { timeout: 5000 });

        console.log('✅ PDF viewer fully initialized and ready for testing');
    }

    /**
     * Get Alpine.js component state
     */
    async getComponentState(): Promise<AlpineComponentState> {
        return await this.page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]');
            if (!el) throw new Error('PDF viewer component not found');
            return (Alpine as any).$data(el);
        });
    }

    /**
     * Get component context (state, refs, callbacks)
     */
    async getComponentContext(): Promise<{ state: any; refs: any; callbacks: any }> {
        return await this.page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]') as any;
            if (!el) throw new Error('PDF viewer component not found');

            const state = (Alpine as any).$data(el);
            // Access $refs properly - it's available directly on the state in Alpine v3
            const refs = state.$refs || {};

            // Extract callbacks from the component
            const callbacks = {
                displayPdf: state.displayPdf,
                renderPage: state.renderPage,
                updateAnnotationVisibility: state.updateAnnotationVisibility,
                zoomToFitAnnotation: state.zoomToFitAnnotation,
                updateIsolationMask: state.updateIsolationMask,
                expandNode: state.expandNode,
                selectNode: state.selectNode,
                onTreeNodeSelected: state.onTreeNodeSelected,
                restorePreIsolationView: state.restorePreIsolationView,
                addToHistory: state.addToHistory,
                applyHistoryState: state.applyHistoryState,
                $nextTick: (Alpine as any).nextTick,
            };

            return { state, refs, callbacks };
        });
    }

    /**
     * Invoke a manager method
     */
    async invokeManager(
        managerName: string,
        methodName: string,
        ...args: any[]
    ): Promise<any> {
        return await this.page.evaluate(
            async ({ manager, method, args }) => {
                const el = document.querySelector('[x-data*="annotationSystemV3"]') as any;
                if (!el) throw new Error('PDF viewer component not found');

                const state = (Alpine as any).$data(el);
                // Access $refs properly - it's available directly on the state in Alpine v3
                const refs = state.$refs || {};
                const callbacks = {
                    displayPdf: state.displayPdf?.bind(state),
                    renderPage: state.renderPage?.bind(state),
                    updateAnnotationVisibility: state.updateAnnotationVisibility?.bind(state),
                    zoomToFitAnnotation: state.zoomToFitAnnotation?.bind(state),
                    $nextTick: (Alpine as any).nextTick,
                };

                const managers = (window as any).PdfViewerManagers;
                if (!managers || !managers[manager]) {
                    throw new Error(`Manager ${manager} not found`);
                }

                const managerObj = managers[manager];
                if (!managerObj[method]) {
                    throw new Error(`Method ${method} not found on ${manager}`);
                }

                return await managerObj[method](state, refs, callbacks, ...args);
            },
            { manager: managerName, method: methodName, args }
        );
    }

    /**
     * Navigate to next page
     */
    async nextPage(): Promise<void> {
        await this.invokeManager('NavigationManager', 'nextPage');
        await this.page.waitForTimeout(300); // Allow rendering
    }

    /**
     * Navigate to previous page
     */
    async previousPage(): Promise<void> {
        await this.invokeManager('NavigationManager', 'previousPage');
        await this.page.waitForTimeout(300);
    }

    /**
     * Go to specific page
     */
    async goToPage(pageNumber: number): Promise<void> {
        await this.invokeManager('NavigationManager', 'goToPage', pageNumber);
        await this.page.waitForTimeout(300);
    }

    /**
     * Zoom in
     */
    async zoomIn(): Promise<void> {
        await this.invokeManager('ZoomManager', 'zoomIn');
        await this.page.waitForTimeout(200);
    }

    /**
     * Zoom out
     */
    async zoomOut(): Promise<void> {
        await this.invokeManager('ZoomManager', 'zoomOut');
        await this.page.waitForTimeout(200);
    }

    /**
     * Set specific zoom level
     */
    async setZoom(zoomLevel: number): Promise<void> {
        await this.invokeManager('ZoomManager', 'setZoom', zoomLevel);
        await this.page.waitForTimeout(200);
    }

    /**
     * Get current zoom level
     */
    async getZoomLevel(): Promise<number> {
        const state = await this.getComponentState();
        return state.zoomLevel;
    }

    /**
     * Enter isolation mode for an annotation
     */
    async enterIsolationMode(annotationOrId: any): Promise<void> {
        await this.page.evaluate(
            async (annoId) => {
                const el = document.querySelector('[x-data*="annotationSystemV3"]') as any;
                const state = (Alpine as any).$data(el);
                const callbacks = {
                    zoomToFitAnnotation: state.zoomToFitAnnotation?.bind(state),
                    expandNode: state.expandNode?.bind(state),
                    updateAnnotationVisibility: state.updateAnnotationVisibility?.bind(state),
                    $nextTick: (Alpine as any).nextTick,
                };

                // Find annotation by ID
                const annotation = state.annotations.find((a: any) => a.id === annoId);
                if (!annotation) throw new Error(`Annotation ${annoId} not found`);

                const managers = (window as any).PdfViewerManagers;
                await managers.IsolationModeManager.enterIsolationMode(annotation, state, callbacks);
            },
            typeof annotationOrId === 'number' ? annotationOrId : annotationOrId.id
        );
        await this.page.waitForTimeout(500); // Allow animation
    }

    /**
     * Exit isolation mode
     */
    async exitIsolationMode(): Promise<void> {
        await this.invokeManager('IsolationModeManager', 'exitIsolationMode');
        await this.page.waitForTimeout(500);
    }

    /**
     * Select tree node
     */
    async selectTreeNode(nodeId: number, nodeType: string): Promise<void> {
        await this.invokeManager('TreeManager', 'selectNode', nodeId, nodeType);
        await this.page.waitForTimeout(200);
    }

    /**
     * Expand tree node
     */
    async expandTreeNode(nodeId: number): Promise<void> {
        await this.invokeManager('TreeManager', 'expandNode', nodeId);
        await this.page.waitForTimeout(100);
    }

    /**
     * Toggle room visibility
     */
    async toggleRoomVisibility(roomId: number): Promise<void> {
        await this.invokeManager('VisibilityToggleManager', 'toggleRoomVisibility', roomId);
        await this.page.waitForTimeout(100);
    }

    /**
     * Toggle location visibility
     */
    async toggleLocationVisibility(locationId: number): Promise<void> {
        await this.invokeManager('VisibilityToggleManager', 'toggleLocationVisibility', locationId);
        await this.page.waitForTimeout(100);
    }

    /**
     * Undo last action
     */
    async undo(): Promise<void> {
        await this.page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]') as any;
            const state = (Alpine as any).$data(el);
            const managers = (window as any).PdfViewerManagers;
            managers.UndoRedoManager.undo(state);
        });
        await this.page.waitForTimeout(100);
    }

    /**
     * Redo last undone action
     */
    async redo(): Promise<void> {
        await this.page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]') as any;
            const state = (Alpine as any).$data(el);
            const managers = (window as any).PdfViewerManagers;
            managers.UndoRedoManager.redo(state);
        });
        await this.page.waitForTimeout(100);
    }

    /**
     * Check if can undo
     */
    async canUndo(): Promise<boolean> {
        return await this.page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]') as any;
            const state = (Alpine as any).$data(el);
            const managers = (window as any).PdfViewerManagers;
            return managers.UndoRedoManager.canUndo(state);
        });
    }

    /**
     * Check if can redo
     */
    async canRedo(): Promise<boolean> {
        return await this.page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]') as any;
            const state = (Alpine as any).$data(el);
            const managers = (window as any).PdfViewerManagers;
            return managers.UndoRedoManager.canRedo(state);
        });
    }

    /**
     * Get all annotations
     */
    async getAnnotations(): Promise<any[]> {
        const state = await this.getComponentState();
        return state.annotations || [];
    }

    /**
     * Get visible annotations (not hidden)
     */
    async getVisibleAnnotations(): Promise<any[]> {
        return await this.page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]') as any;
            const state = (Alpine as any).$data(el);
            return state.annotations.filter((a: any) =>
                !state.hiddenAnnotations.includes(a.id)
            );
        });
    }

    /**
     * Get tree hierarchy
     */
    async getTree(): Promise<any[]> {
        const state = await this.getComponentState();
        return state.tree || [];
    }

    /**
     * Check if in isolation mode
     */
    async isInIsolationMode(): Promise<boolean> {
        const state = await this.getComponentState();
        return state.isolationMode === true;
    }

    /**
     * Get current isolation level
     */
    async getIsolationLevel(): Promise<string | null> {
        const state = await this.getComponentState();
        return state.isolationLevel;
    }

    /**
     * Wait for Livewire to finish processing
     */
    async waitForLivewire(timeout: number = 5000): Promise<void> {
        await this.page.waitForFunction(
            () => {
                if (!window.hasOwnProperty('Livewire')) return true;
                const livewire = (window as any).Livewire;
                return !livewire.components?.componentsAreProcessing();
            },
            { timeout }
        );
    }

    /**
     * Click tree node in UI
     */
    async clickTreeNode(nodeLabel: string): Promise<void> {
        await this.page.click(`[data-tree-node]:has-text("${nodeLabel}")`);
        await this.waitForLivewire();
    }

    /**
     * Apply filter
     */
    async applyFilter(filterType: string, value: any): Promise<void> {
        await this.page.evaluate(
            ({ type, val }) => {
                const el = document.querySelector('[x-data*="annotationSystemV3"]') as any;
                const state = (Alpine as any).$data(el);
                const managers = (window as any).PdfViewerManagers;
                managers.FilterSystem.applyFilter(state, type, val);
            },
            { type: filterType, val: value }
        );
        await this.page.waitForTimeout(200);
    }

    /**
     * Clear all filters
     */
    async clearAllFilters(): Promise<void> {
        await this.page.evaluate(() => {
            const el = document.querySelector('[x-data*="annotationSystemV3"]') as any;
            const state = (Alpine as any).$data(el);
            const managers = (window as any).PdfViewerManagers;
            managers.FilterSystem.clearAllFilters(state);
        });
        await this.page.waitForTimeout(200);
    }

    /**
     * Get active filters
     */
    async getActiveFilters(): Promise<any[]> {
        const state = await this.getComponentState();
        return state.activeFilters || [];
    }
}

/**
 * Create ManagerHelpers instance
 */
export function createManagerHelpers(page: Page): ManagerHelpers {
    return new ManagerHelpers(page);
}
