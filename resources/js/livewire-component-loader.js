/**
 * Livewire-aware component loader for FilamentPHP
 * Ensures Alpine.js components are available during Livewire updates
 */

// Component registry for re-registration after Livewire updates
window.componentRegistry = window.componentRegistry || {};

// Register a component in the global registry
function registerComponent(name, factory) {
    window.componentRegistry[name] = factory;
    window[name] = factory;
    console.log(`[ComponentLoader] Registered component: ${name}`);
}

// Re-register all components (called after Livewire updates)
function reregisterComponents() {
    Object.keys(window.componentRegistry).forEach(name => {
        window[name] = window.componentRegistry[name];
    });
    console.log('[ComponentLoader] Re-registered all components after Livewire update');
}

// Listen for Livewire navigations and updates
document.addEventListener('livewire:navigated', reregisterComponents);
document.addEventListener('livewire:load', reregisterComponents);

// Also listen for Alpine re-initialization
document.addEventListener('alpine:init', () => {
    console.log('[ComponentLoader] Alpine re-initialized, ensuring components are available');
    reregisterComponents();
});

// Immediate registration for initial load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', reregisterComponents);
} else {
    reregisterComponents();
}

// Also ensure components are available during Alpine initialization
document.addEventListener('alpine:before-init', () => {
    console.log('[ComponentLoader] Alpine about to initialize, ensuring components are available');
    reregisterComponents();
});

// Export for module usage
export { registerComponent, reregisterComponents };