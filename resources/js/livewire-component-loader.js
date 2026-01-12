/**
 * Livewire-aware component loader for FilamentPHP
 * Ensures Alpine.js components are available during Livewire updates
 */

// Component registry for re-registration after Livewire updates
window.componentRegistry = window.componentRegistry || {};

// Track if we've already logged initial registration (reduce console noise)
let hasLoggedInitialRegistration = false;

// Register a component in the global registry
function registerComponent(name, factory) {
    window.componentRegistry[name] = factory;
    window[name] = factory;
    // Only log in development or on first registration
    if (!hasLoggedInitialRegistration && (import.meta.env?.DEV || window.location.hostname.includes('localhost'))) {
        console.log(`[ComponentLoader] Registered component: ${name}`);
    }
}

// Re-register all components (called after Livewire updates)
// Only re-registers window globals, not Alpine.data() components (those are handled separately)
function reregisterComponents() {
    Object.keys(window.componentRegistry).forEach(name => {
        window[name] = window.componentRegistry[name];
    });
    // Suppress repeated logs - only log once on initial load
    if (!hasLoggedInitialRegistration) {
        hasLoggedInitialRegistration = true;
        if (import.meta.env?.DEV || window.location.hostname.includes('localhost')) {
            console.log('[ComponentLoader] Components registered and ready');
        }
    }
}

// Listen for Livewire navigations (page changes)
document.addEventListener('livewire:navigated', reregisterComponents);

// Listen for Livewire initial load (only once)
let livewireLoaded = false;
document.addEventListener('livewire:load', () => {
    if (!livewireLoaded) {
        livewireLoaded = true;
        reregisterComponents();
    }
});

// Immediate registration for initial load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', reregisterComponents);
} else {
    reregisterComponents();
}

// Export for module usage
export { registerComponent, reregisterComponents };