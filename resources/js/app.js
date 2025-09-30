import './bootstrap';

// Import Livewire-aware component loader FIRST
import './livewire-component-loader.js';

// Import all FilamentPHP components (these will auto-register via the loader)
import './components/advanced-file-upload.js';
import './components/filament-form-components.js';

// Ensure components are immediately available globally before Alpine starts
import { reregisterComponents } from './livewire-component-loader.js';
reregisterComponents();

// Standard Laravel + FilamentPHP app.js entry point
// Components are now registered via FilamentPHP's AlpineComponent system and Livewire-aware loader
console.log('[App.js] TCS Woodwork ERP - Alpine.js initialization complete with Livewire support');
