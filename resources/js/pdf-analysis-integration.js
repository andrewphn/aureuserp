/**
 * PDF Analysis Integration with Discovery Wizard
 * Handles the communication between PDF upload, AI analysis, and Discovery Session updates
 */

// Alpine.js integration for PDF Analysis
document.addEventListener('alpine:init', () => {
    Alpine.data('pdfAnalysisIntegration', () => ({
        isAnalyzing: false,
        analysisResults: null,
        error: null,
        progress: 0,
        
        init() {
            // Listen for PDF upload events
            this.$nextTick(() => {
                document.addEventListener('livewire:initialized', () => {
                    Livewire.on('pdf-uploaded', (filePaths) => {
                        this.handlePDFUpload(filePaths);
                    });
                    
                    Livewire.on('analysis-completed', (data) => {
                        this.handleAnalysisComplete(data);
                    });
                });
            });
        },
        
        async handlePDFUpload(filePaths) {
            if (!filePaths || filePaths.length === 0) return;
            
            try {
                this.isAnalyzing = true;
                this.error = null;
                this.progress = 10;
                
                // Get discovery session context
                const discoverySessionId = this.getDiscoverySessionId();
                
                // Start analysis
                await this.analyzeUploadedPDF(filePaths[0], discoverySessionId);
                
            } catch (error) {
                this.error = `Analysis failed: ${error.message}`;
                console.error('PDF Analysis error:', error);
            } finally {
                this.isAnalyzing = false;
                this.progress = 0;
            }
        },
        
        async analyzeUploadedPDF(filePath, discoverySessionId = null) {
            try {
                this.progress = 30;
                
                const payload = {
                    pdf_path: filePath,
                    discovery_session_id: discoverySessionId,
                    customer_name: this.getFormValue('customer_name'),
                    project_name: this.getFormValue('project_name'),
                    project_size_estimate: this.getFormValue('project_size_estimate'),
                };
                
                this.progress = 50;
                
                const response = await fetch('/api/v1/pdf-analysis/analyze', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Authorization': `Bearer ${this.getApiToken()}`
                    },
                    body: JSON.stringify(payload)
                });
                
                this.progress = 80;
                
                const data = await response.json();
                
                if (data.success) {
                    this.analysisResults = data;
                    this.progress = 100;
                    
                    // Emit analysis completed event
                    Livewire.dispatch('analysis-completed', data);
                    
                    // Show success notification
                    this.showNotification(
                        'PDF Analysis Complete',
                        `Analysis completed with ${data.confidence_score || 0}% confidence`,
                        'success'
                    );
                } else {
                    throw new Error(data.error || 'Analysis failed');
                }
                
            } catch (error) {
                this.error = error.message;
                this.showNotification('Analysis Error', error.message, 'error');
                throw error;
            }
        },
        
        handleAnalysisComplete(data) {
            console.log('Analysis completed:', data);
            
            // Update UI state
            this.analysisResults = data.results;
            this.isAnalyzing = false;
            
            // Optionally auto-apply results to discovery session
            if (this.shouldAutoApplyResults()) {
                this.applyResultsToSession(data.results);
            }
        },
        
        async applyResultsToSession(results) {
            try {
                const discoverySessionId = this.getDiscoverySessionId();
                if (!discoverySessionId) return;
                
                // Update discovery session via Livewire
                Livewire.dispatch('apply-analysis-results', {
                    sessionId: discoverySessionId,
                    results: results
                });
                
            } catch (error) {
                console.error('Failed to apply results to session:', error);
            }
        },
        
        // Helper methods
        getDiscoverySessionId() {
            // Extract from URL or Livewire component
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('session') || 
                   document.querySelector('[wire\\:id]')?.getAttribute('data-discovery-session-id') ||
                   null;
        },
        
        getFormValue(fieldName) {
            // Get value from Filament form
            const field = document.querySelector(`[name="${fieldName}"]`);
            return field?.value || '';
        },
        
        getApiToken() {
            // Get API token from meta tag or session
            return document.querySelector('meta[name="api-token"]')?.getAttribute('content') ||
                   localStorage.getItem('api_token') ||
                   '';
        },
        
        shouldAutoApplyResults() {
            // Check if auto-apply is enabled (could be a setting)
            return true; // Default to auto-apply for now
        },
        
        showNotification(title, message, type = 'info') {
            // Use Filament's notification system if available
            if (window.$wire && window.$wire.dispatch) {
                window.$wire.dispatch('notify', {
                    title: title,
                    message: message,
                    type: type
                });
            } else {
                // Fallback to browser notification or console
                console.log(`${type.toUpperCase()}: ${title} - ${message}`);
            }
        },
        
        // Progress indicator
        getProgressWidth() {
            return `${this.progress}%`;
        },
        
        getProgressColor() {
            if (this.progress < 50) return 'bg-blue-500';
            if (this.progress < 80) return 'bg-yellow-500';
            return 'bg-green-500';
        }
    }));
});

// Global PDF Analysis utilities
window.PDFAnalysisUtils = {
    /**
     * Analyze a PDF file directly
     */
    async analyzePDF(filePath, options = {}) {
        const payload = {
            pdf_path: filePath,
            ...options
        };
        
        const response = await fetch('/api/v1/pdf-analysis/analyze', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify(payload)
        });
        
        return await response.json();
    },
    
    /**
     * Get analysis health status
     */
    async getHealthStatus() {
        try {
            const response = await fetch('/api/v1/pdf-analysis/health');
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            const data = await response.json();
            return data;
        } catch (error) {
            console.warn('PDF Analysis health check failed:', error.message);
            return {
                status: 'unknown',
                error: error.message,
                timestamp: new Date().toISOString()
            };
        }
    },
    
    /**
     * Validate analysis results
     */
    async validateResults(analysisData, textContext = []) {
        const payload = {
            analysis_data: analysisData,
            text_context: textContext
        };
        
        const response = await fetch('/api/v1/pdf-analysis/validate-results', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify(payload)
        });
        
        return await response.json();
    },
    
    /**
     * Format confidence score for display
     */
    formatConfidenceScore(score) {
        const numScore = Number(score) || 0;
        
        if (numScore >= 85) {
            return { text: `${numScore}%`, color: 'text-green-600', level: 'high' };
        } else if (numScore >= 70) {
            return { text: `${numScore}%`, color: 'text-yellow-600', level: 'medium' };
        } else {
            return { text: `${numScore}%`, color: 'text-red-600', level: 'low' };
        }
    },
    
    /**
     * Format linear feet for display
     */
    formatLinearFeet(lf) {
        const numLf = Number(lf) || 0;
        return `${numLf.toFixed(1)} LF`;
    },
    
    /**
     * Format cost for display
     */
    formatCost(cost) {
        const numCost = Number(cost) || 0;
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(numCost);
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('PDF Analysis Integration loaded');

    // Check service health on load (silently)
    PDFAnalysisUtils.getHealthStatus().then(health => {
        if (health.status === 'healthy') {
            console.log('PDF Analysis Service: Healthy');
        } else if (health.status === 'unhealthy') {
            console.warn('PDF Analysis Service: Unhealthy -', health.error);
        }
        // Don't log 'unknown' status to reduce console noise
    }).catch(error => {
        console.warn('PDF Analysis health check failed silently');
    });
});