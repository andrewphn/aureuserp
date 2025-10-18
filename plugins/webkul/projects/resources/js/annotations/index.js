/**
 * Annotation System - Main Entry Point
 * Re-exports all modules for convenient importing
 */

export { loadAnnotationContext, loadExistingAnnotations, loadCabinetRuns, loadProjectNumber, loadAllMetadata } from './context-loader.js';
export { createCascadeFilters } from './cascade-filters.js';
export { saveAnnotationsWithEntities } from './annotation-saver.js';
export { createCanvasRenderer } from './canvas-renderer.js';
export { createAnnotationDrawer } from './annotation-drawer.js';
export { createAnnotationEditor } from './annotation-editor.js';
export { createPageNavigator } from './page-navigator.js';
export { createAnnotationComponent } from './alpine-component-factory.js';

// New context-first annotation system components
export { projectTreeSidebarComponent } from './project-tree-sidebar.js';
export { contextBarComponent } from './context-bar.js';
