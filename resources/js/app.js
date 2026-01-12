import './bootstrap';

// Import Chart.js for kanban and dashboard charts
import './charts.js';

// Import Masonry.js and imagesLoaded for gallery layouts
import Masonry from 'masonry-layout';
import imagesLoaded from 'imagesloaded';

// Make them available globally for Alpine components
window.Masonry = Masonry;
window.imagesLoaded = imagesLoaded;

// Import Livewire-aware component loader FIRST
import './livewire-component-loader.js';

// Import all FilamentPHP components (these will auto-register via the loader)
import './components/advanced-file-upload.js';
import './components/filament-form-components.js';
import './components/context-footer.js';

// Import Entity Store and Form Auto-populate (loaded once, prevents double-loading)
import './centralized-entity-store.js';
import './form-auto-populate.js';

// Import PDF viewer managers (exports window.PdfViewerManagers for Alpine components)
import '../../plugins/webkul/projects/resources/js/pdf-viewer.js';

// Import Measurement Formatter for dimension display (imperial/metric/fraction conversion)
import './measurement-formatter.js';

// Import Cabinet Spec Builder Alpine component
import './components/cabinet-spec-builder.js';

// Note: annotations.js is loaded separately via @vite() on PDF pages to prevent double-loading

// Ensure components are immediately available globally before Alpine starts
import { reregisterComponents } from './livewire-component-loader.js';
reregisterComponents();

// Standard Laravel + FilamentPHP app.js entry point
// Components are now registered via FilamentPHP's AlpineComponent system and Livewire-aware loader
// Only log in development to reduce console noise
if (import.meta.env?.DEV || window.location.hostname.includes('localhost')) {
    console.log('[App.js] TCS Woodwork ERP - Alpine.js initialization complete with Livewire support');
}
