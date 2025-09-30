// FilamentPHP Form Components
// These are standard FilamentPHP components that need to be available for Livewire updates

import { registerComponent } from '../livewire-component-loader.js';

// File Upload Form Component
function fileUploadFormComponent(config = {}) {
    return {
        state: {
            files: [],
            isUploading: false,
            ...config
        },

        init() {
            console.log('[FileUploadFormComponent] Component initialized');
        }
    };
}

// Textarea Form Component
function textareaFormComponent(config = {}) {
    return {
        state: {
            value: '',
            maxLength: null,
            rows: 3,
            ...config
        },

        init() {
            console.log('[TextareaFormComponent] Component initialized');
        }
    };
}

// Error handler function
function error(message, context = {}) {
    console.error('[FormComponent Error]', message, context);
    return message;
}

// State management function
function state(key, defaultValue = null) {
    const stateKey = `form_state_${key}`;
    return window[stateKey] || defaultValue;
}

// Register all components
registerComponent('fileUploadFormComponent', fileUploadFormComponent);
registerComponent('textareaFormComponent', textareaFormComponent);
registerComponent('error', error);
registerComponent('state', state);

// Make immediately available
window.fileUploadFormComponent = fileUploadFormComponent;
window.textareaFormComponent = textareaFormComponent;
window.error = error;
window.state = state;

// Export for ES6 compatibility
export { fileUploadFormComponent, textareaFormComponent, error, state };