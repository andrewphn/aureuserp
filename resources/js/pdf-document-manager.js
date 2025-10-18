/**
 * PDF Document Manager - Singleton Pattern
 *
 * Manages PDF.js document loading with caching to prevent duplicate requests.
 * Provides a shared worker across all thumbnail renders for optimal performance.
 *
 * Usage:
 *   import { PDFDocumentManager } from './pdf-document-manager.js';
 *   const manager = PDFDocumentManager.getInstance();
 *   const pdf = await manager.getDocument(pdfUrl);
 *   const page = await pdf.getPage(pageNumber);
 */

import * as pdfjsLib from 'pdfjs-dist';

export class PDFDocumentManager {
    static instance = null;

    constructor() {
        if (PDFDocumentManager.instance) {
            return PDFDocumentManager.instance;
        }

        // Configure PDF.js worker (shared across all documents)
        pdfjsLib.GlobalWorkerOptions.workerSrc = '/js/pdf.worker.min.js';

        // Cache for loaded PDF documents
        // Key: PDF URL, Value: { promise, document, loadedAt }
        this.documentCache = new Map();

        // Track loading promises to prevent duplicate requests
        this.loadingPromises = new Map();

        PDFDocumentManager.instance = this;

        console.log('✅ PDFDocumentManager initialized (Singleton)');
    }

    /**
     * Get singleton instance
     * @returns {PDFDocumentManager}
     */
    static getInstance() {
        if (!PDFDocumentManager.instance) {
            PDFDocumentManager.instance = new PDFDocumentManager();
        }
        return PDFDocumentManager.instance;
    }

    /**
     * Get a PDF document (from cache or load new)
     * @param {string} pdfUrl - URL to the PDF file
     * @returns {Promise<PDFDocumentProxy>}
     */
    async getDocument(pdfUrl) {
        // Return cached document if available
        if (this.documentCache.has(pdfUrl)) {
            const cached = this.documentCache.get(pdfUrl);
            console.log(`📄 Using cached PDF document: ${pdfUrl}`);
            return cached.document;
        }

        // Return existing loading promise if already loading
        if (this.loadingPromises.has(pdfUrl)) {
            console.log(`⏳ Waiting for PDF to finish loading: ${pdfUrl}`);
            return this.loadingPromises.get(pdfUrl);
        }

        // Load new PDF document
        console.log(`📥 Loading PDF document: ${pdfUrl}`);
        const loadingPromise = this.loadPdfDocument(pdfUrl);

        // Store loading promise to prevent duplicate requests
        this.loadingPromises.set(pdfUrl, loadingPromise);

        try {
            const document = await loadingPromise;

            // Cache the loaded document
            this.documentCache.set(pdfUrl, {
                document,
                loadedAt: new Date(),
                url: pdfUrl
            });

            // Remove from loading promises
            this.loadingPromises.delete(pdfUrl);

            console.log(`✅ PDF document loaded and cached: ${pdfUrl}`);
            return document;
        } catch (error) {
            // Remove failed loading promise
            this.loadingPromises.delete(pdfUrl);
            console.error(`❌ Failed to load PDF document: ${pdfUrl}`, error);
            throw error;
        }
    }

    /**
     * Internal method to load PDF document via PDF.js
     * @param {string} pdfUrl
     * @returns {Promise<PDFDocumentProxy>}
     */
    async loadPdfDocument(pdfUrl) {
        const loadingTask = pdfjsLib.getDocument(pdfUrl);
        return await loadingTask.promise;
    }

    /**
     * Get a specific page from a PDF document
     * @param {string} pdfUrl - URL to the PDF file
     * @param {number} pageNumber - Page number (1-indexed)
     * @returns {Promise<PDFPageProxy>}
     */
    async getPage(pdfUrl, pageNumber) {
        const document = await this.getDocument(pdfUrl);
        return await document.getPage(pageNumber);
    }

    /**
     * Clear cache for specific PDF or all PDFs
     * @param {string|null} pdfUrl - URL to clear, or null to clear all
     */
    clearCache(pdfUrl = null) {
        if (pdfUrl) {
            this.documentCache.delete(pdfUrl);
            this.loadingPromises.delete(pdfUrl);
            console.log(`🗑️ Cleared cache for: ${pdfUrl}`);
        } else {
            this.documentCache.clear();
            this.loadingPromises.clear();
            console.log('🗑️ Cleared all PDF document cache');
        }
    }

    /**
     * Get cache statistics
     * @returns {Object}
     */
    getCacheStats() {
        return {
            cachedDocuments: this.documentCache.size,
            loadingDocuments: this.loadingPromises.size,
            cacheKeys: Array.from(this.documentCache.keys())
        };
    }

    /**
     * Preload a PDF document (useful for anticipated navigation)
     * @param {string} pdfUrl
     * @returns {Promise<void>}
     */
    async preload(pdfUrl) {
        try {
            await this.getDocument(pdfUrl);
            console.log(`✅ Preloaded PDF: ${pdfUrl}`);
        } catch (error) {
            console.error(`❌ Failed to preload PDF: ${pdfUrl}`, error);
        }
    }
}

// Export singleton instance for convenience
export const pdfManager = PDFDocumentManager.getInstance();

// Export for debugging in console
if (typeof window !== 'undefined') {
    window.PDFDocumentManager = PDFDocumentManager;
    window.pdfManager = pdfManager;
}
