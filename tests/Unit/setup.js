/**
 * Vitest Test Setup
 * Common mocks, utilities, and global test configuration
 */

import { vi } from 'vitest';

// Mock window object for happy-dom
global.window = global.window || {};
global.document = global.document || {};

// Mock console methods to reduce noise in tests (optional)
// Comment out if you want to see console output during development
// global.console = {
//     ...console,
//     log: vi.fn(),
//     warn: vi.fn(),
//     error: vi.fn(),
// };

// Mock Alpine.js $nextTick
global.$nextTick = vi.fn((callback) => {
    if (callback) {
        return Promise.resolve().then(callback);
    }
    return Promise.resolve();
});

// Mock requestAnimationFrame
global.requestAnimationFrame = vi.fn((callback) => {
    setTimeout(callback, 16); // Roughly 60fps
    return 1;
});

global.cancelAnimationFrame = vi.fn();

// Don't use fake timers globally - let tests opt-in when needed
// This prevents timeouts in async tests that rely on real timers

// Reset mocks after each test
afterEach(() => {
    vi.clearAllMocks();
    vi.useRealTimers(); // Ensure real timers for next test
});
