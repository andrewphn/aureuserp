/**
 * Navigation Manager
 * Handles page navigation and pagination
 */

/**
 * Go to next page
 * @param {Object} state - Component state
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function nextPage(state, callbacks) {
    if (state.navigating) {
        console.log('⏸️ Navigation already in progress');
        return;
    }

    // Get filtered page numbers
    const availablePages = callbacks.getFilteredPageNumbers ? callbacks.getFilteredPageNumbers() : getAllPages(state);

    if (availablePages.length === 0) {
        console.log('⚠️ No pages match the current filter criteria');
        return;
    }

    // Find next page in filtered list
    const currentIndex = availablePages.indexOf(state.currentPage);

    if (currentIndex < availablePages.length - 1) {
        state.navigating = true;
        try {
            const nextPage = availablePages[currentIndex + 1];
            await navigateToPage(nextPage, state, callbacks);
        } finally {
            state.navigating = false;
        }
    } else {
        console.log('📄 Already at last page matching filters');
    }
}

/**
 * Go to previous page
 * @param {Object} state - Component state
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function previousPage(state, callbacks) {
    if (state.navigating) {
        console.log('⏸️ Navigation already in progress');
        return;
    }

    // Get filtered page numbers
    const availablePages = callbacks.getFilteredPageNumbers ? callbacks.getFilteredPageNumbers() : getAllPages(state);

    if (availablePages.length === 0) {
        console.log('⚠️ No pages match the current filter criteria');
        return;
    }

    // Find previous page in filtered list
    const currentIndex = availablePages.indexOf(state.currentPage);

    if (currentIndex > 0) {
        state.navigating = true;
        try {
            const prevPage = availablePages[currentIndex - 1];
            await navigateToPage(prevPage, state, callbacks);
        } finally {
            state.navigating = false;
        }
    } else {
        console.log('📄 Already at first page matching filters');
    }
}

/**
 * Go to specific page
 * @param {Number} pageNum - Page number (1-indexed)
 * @param {Object} state - Component state
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
export async function goToPage(pageNum, state, callbacks) {
    if (state.navigating) {
        console.log('⏸️ Navigation already in progress');
        return;
    }

    if (pageNum < 1 || pageNum > state.totalPages) {
        console.warn(`⚠️ Invalid page number: ${pageNum}`);
        return;
    }

    state.navigating = true;
    try {
        await navigateToPage(pageNum, state, callbacks);
    } finally {
        state.navigating = false;
    }
}

/**
 * Navigate to page (internal helper)
 * @param {Number} pageNum - Page number
 * @param {Object} state - Component state
 * @param {Object} callbacks - Callback functions
 * @returns {Promise<void>}
 */
async function navigateToPage(pageNum, state, callbacks) {
    console.log(`📄 Navigating to page ${pageNum}`);

    // Update current page
    state.currentPage = pageNum;

    // Update pdfPageId from pageMap
    updatePdfPageId(pageNum, state);

    // Clear current annotations
    state.annotations = [];

    // Reload PDF at new page
    if (callbacks.displayPdf) {
        await callbacks.displayPdf();
    }

    // Load annotations for new page
    if (callbacks.loadAnnotations) {
        await callbacks.loadAnnotations();
    }

    console.log(`✓ Navigated to page ${pageNum}`);
}

/**
 * Update pdfPageId based on current page
 * @param {Number} pageNum - Page number
 * @param {Object} state - Component state
 */
function updatePdfPageId(pageNum, state) {
    const newPdfPageId = state.pageMap[pageNum];
    if (newPdfPageId) {
        state.pdfPageId = newPdfPageId;
        console.log(`✓ Updated pdfPageId to ${state.pdfPageId} for page ${pageNum}`);
    } else {
        console.warn(`⚠️ No pdfPageId found for page ${pageNum} in pageMap`);
    }
}

/**
 * Get all page numbers
 * @param {Object} state - Component state
 * @returns {Array<Number>} All page numbers
 */
function getAllPages(state) {
    return Array.from({ length: state.totalPages }, (_, i) => i + 1);
}

/**
 * Check if can navigate to next page
 * @param {Object} state - Component state
 * @param {Function} getFilteredPageNumbers - Function to get filtered pages
 * @returns {Boolean} True if can go next
 */
export function canGoNext(state, getFilteredPageNumbers) {
    const availablePages = getFilteredPageNumbers ? getFilteredPageNumbers() : getAllPages(state);
    const currentIndex = availablePages.indexOf(state.currentPage);
    return currentIndex < availablePages.length - 1;
}

/**
 * Check if can navigate to previous page
 * @param {Object} state - Component state
 * @param {Function} getFilteredPageNumbers - Function to get filtered pages
 * @returns {Boolean} True if can go previous
 */
export function canGoPrevious(state, getFilteredPageNumbers) {
    const availablePages = getFilteredPageNumbers ? getFilteredPageNumbers() : getAllPages(state);
    const currentIndex = availablePages.indexOf(state.currentPage);
    return currentIndex > 0;
}
