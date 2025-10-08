/**
 * Page Navigator
 * Handles PDF page navigation (next, previous, first, last, go to page)
 */

export function createPageNavigator() {
    return {
        /**
         * Go to specific page number
         * @param {Object} pdfDocument - PDF.js document object
         * @param {number} pageNum - Page number to navigate to
         * @param {number} totalPages - Total number of pages
         * @returns {Promise<Object>} { pdfPage, pageNum } or null if invalid
         */
        async goToPage(pdfDocument, pageNum, totalPages) {
            if (!pdfDocument || pageNum < 1 || pageNum > totalPages) {
                return null;
            }

            const pdfPage = await pdfDocument.getPage(pageNum);
            return {
                pdfPage,
                pageNum
            };
        },

        /**
         * Navigate to first page
         * @param {Object} pdfDocument - PDF.js document object
         * @param {number} totalPages - Total number of pages
         * @returns {Promise<Object>} { pdfPage, pageNum }
         */
        async goToFirstPage(pdfDocument, totalPages) {
            return this.goToPage(pdfDocument, 1, totalPages);
        },

        /**
         * Navigate to last page
         * @param {Object} pdfDocument - PDF.js document object
         * @param {number} totalPages - Total number of pages
         * @returns {Promise<Object>} { pdfPage, pageNum }
         */
        async goToLastPage(pdfDocument, totalPages) {
            return this.goToPage(pdfDocument, totalPages, totalPages);
        },

        /**
         * Navigate to next page
         * @param {Object} pdfDocument - PDF.js document object
         * @param {number} currentPage - Current page number
         * @param {number} totalPages - Total number of pages
         * @returns {Promise<Object>} { pdfPage, pageNum } or null if already on last page
         */
        async goToNextPage(pdfDocument, currentPage, totalPages) {
            if (currentPage >= totalPages) {
                return null;
            }
            return this.goToPage(pdfDocument, currentPage + 1, totalPages);
        },

        /**
         * Navigate to previous page
         * @param {Object} pdfDocument - PDF.js document object
         * @param {number} currentPage - Current page number
         * @param {number} totalPages - Total number of pages
         * @returns {Promise<Object>} { pdfPage, pageNum } or null if already on first page
         */
        async goToPreviousPage(pdfDocument, currentPage, totalPages) {
            if (currentPage <= 1) {
                return null;
            }
            return this.goToPage(pdfDocument, currentPage - 1, totalPages);
        },

        /**
         * Validate page number input
         * @param {number} pageNum - Page number to validate
         * @param {number} totalPages - Total number of pages
         * @returns {boolean} True if valid, false otherwise
         */
        isValidPageNumber(pageNum, totalPages) {
            return pageNum >= 1 && pageNum <= totalPages && Number.isInteger(pageNum);
        },

        /**
         * Sanitize page number input
         * @param {string|number} input - User input
         * @param {number} totalPages - Total number of pages
         * @returns {number} Sanitized page number
         */
        sanitizePageInput(input, totalPages) {
            const num = parseInt(input, 10);
            if (isNaN(num)) return 1;
            if (num < 1) return 1;
            if (num > totalPages) return totalPages;
            return num;
        }
    };
}
