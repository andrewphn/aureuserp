/**
 * Real-time Pricing Display Alpine.js Component
 *
 * Provides reactive pricing updates as users modify project parameters
 * in the discovery wizard. Integrates with Laravel Livewire backend.
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('realtimePricingDisplay', () => ({
        // State
        pricing: {
            grand_total: 0,
            price_per_lf: 0,
            total_linear_feet: 0,
            pricing_level: 2,
            pricing_level_name: 'Level 2',
            last_updated: null,
            confidence_level: 0,
            is_realtime: false,
            calculation_source: 'DEFAULT'
        },
        alerts: [],
        isLoading: false,
        isUpdating: false,
        lastUpdate: null,
        updateQueue: [],

        // Configuration
        config: {
            updateDebounceMs: 1000,
            maxRetries: 3,
            pollingInterval: 5000,
            enableAnimations: true
        },

        // Initialization
        initRealtimePricing() {
            console.log('Initializing real-time pricing display');

            // Set up event listeners
            this.setupEventListeners();

            // Initialize polling for updates
            this.startPolling();

            // Load initial pricing data
            this.loadInitialPricing();
        },

        // Event listener setup
        setupEventListeners() {
            // Listen for pricing-related changes from other components
            this.$watch('$store.discoveryState.linearFeet', () => {
                this.queuePricingUpdate('linear_feet_changed');
            });

            this.$watch('$store.discoveryState.pricingLevel', () => {
                this.queuePricingUpdate('pricing_level_changed');
            });

            this.$watch('$store.discoveryState.materialSelections', () => {
                this.queuePricingUpdate('materials_changed');
            });

            this.$watch('$store.discoveryState.upgrades', () => {
                this.queuePricingUpdate('upgrades_changed');
            });

            // Listen for external events
            window.addEventListener('pricing-change', (event) => {
                this.queuePricingUpdate('external_change', event.detail);
            });

            window.addEventListener('materials-updated', () => {
                this.queuePricingUpdate('materials_updated');
            });

            window.addEventListener('rooms-updated', () => {
                this.queuePricingUpdate('rooms_updated');
            });

            // Listen for form field changes
            document.addEventListener('livewire:updated', (event) => {
                if (this.shouldTriggerPricingUpdate(event.detail)) {
                    this.queuePricingUpdate('livewire_updated');
                }
            });
        },

        // Check if Livewire update should trigger pricing recalculation
        shouldTriggerPricingUpdate(detail) {
            const pricingTriggerFields = [
                'pricing_level', 'material_category', 'wood_species', 'finish_type',
                'cabinet_style', 'door_type', 'hardware_style', 'linear_feet',
                'upgrades', 'additional_products', 'rush_order'
            ];

            return detail && detail.component &&
                   pricingTriggerFields.some(field =>
                       detail.component.includes(field) ||
                       (detail.updates && detail.updates.includes(field))
                   );
        },

        // Queue pricing update with debouncing
        queuePricingUpdate(trigger, data = null) {
            if (this.isUpdating) {
                return; // Prevent concurrent updates
            }

            const updateRequest = {
                trigger,
                data,
                timestamp: Date.now()
            };

            this.updateQueue.push(updateRequest);

            // Debounce updates
            clearTimeout(this.updateTimeout);
            this.updateTimeout = setTimeout(() => {
                this.processPricingUpdates();
            }, this.config.updateDebounceMs);
        },

        // Process queued pricing updates
        async processPricingUpdates() {
            if (this.updateQueue.length === 0 || this.isUpdating) {
                return;
            }

            this.isUpdating = true;
            this.isLoading = true;

            try {
                const latestUpdate = this.updateQueue[this.updateQueue.length - 1];
                console.log('Processing pricing update:', latestUpdate.trigger);

                // Call Livewire method to recalculate pricing
                await this.$wire.call('updateRealtimePricing', {
                    trigger: latestUpdate.trigger,
                    data: latestUpdate.data
                });

                // Clear the queue
                this.updateQueue = [];
                this.lastUpdate = Date.now();

                // Trigger success animation
                if (this.config.enableAnimations) {
                    this.animatePricingUpdate();
                }

            } catch (error) {
                console.error('Pricing update failed:', error);
                this.handlePricingError(error);
            } finally {
                this.isUpdating = false;
                this.isLoading = false;
            }
        },

        // Load initial pricing data
        async loadInitialPricing() {
            this.isLoading = true;

            try {
                const response = await this.$wire.call('getInitialPricingData');
                if (response) {
                    this.updatePricingData(response);
                }
            } catch (error) {
                console.error('Failed to load initial pricing:', error);
                this.handlePricingError(error);
            } finally {
                this.isLoading = false;
            }
        },

        // Update pricing data from server response
        updatePricingData(newPricing) {
            const previousTotal = this.pricing.grand_total;

            // Update pricing object
            Object.assign(this.pricing, newPricing);

            // Update alerts if provided
            if (newPricing.pricing_alerts) {
                this.alerts = newPricing.pricing_alerts;
            }

            // Dispatch update event for other components
            this.dispatchPricingUpdateEvent(previousTotal);

            console.log('Pricing updated:', {
                grand_total: this.pricing.grand_total,
                pricing_level: this.pricing.pricing_level,
                last_updated: this.pricing.last_updated
            });
        },

        // Dispatch pricing update event
        dispatchPricingUpdateEvent(previousTotal) {
            const event = new CustomEvent('pricing-updated', {
                detail: {
                    pricing: this.pricing,
                    previous_total: previousTotal,
                    change_amount: this.pricing.grand_total - previousTotal,
                    timestamp: Date.now()
                }
            });
            window.dispatchEvent(event);
        },

        // Start polling for pricing updates
        startPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
            }

            this.pollingInterval = setInterval(() => {
                if (!this.isUpdating) {
                    this.checkForPricingUpdates();
                }
            }, this.config.pollingInterval);
        },

        // Check for pricing updates from server
        async checkForPricingUpdates() {
            try {
                const response = await this.$wire.call('checkPricingUpdates', {
                    last_update: this.lastUpdate
                });

                if (response && response.has_updates) {
                    this.updatePricingData(response.pricing);
                }
            } catch (error) {
                console.warn('Polling update failed:', error);
            }
        },

        // Handle pricing calculation errors
        handlePricingError(error) {
            this.alerts.push({
                type: 'error',
                title: 'Pricing Update Failed',
                message: 'Unable to calculate current pricing. Please refresh the page.',
                timestamp: Date.now()
            });

            // Auto-remove error after 10 seconds
            setTimeout(() => {
                this.alerts = this.alerts.filter(alert =>
                    alert.timestamp < Date.now() - 10000
                );
            }, 10000);
        },

        // Manual refresh action
        async refreshPricing() {
            if (this.isUpdating) {
                return;
            }

            console.log('Manual pricing refresh triggered');
            this.queuePricingUpdate('manual_refresh');
        },

        // Animate pricing update
        animatePricingUpdate() {
            const element = this.$el.querySelector('.pricing-total-amount');
            if (element) {
                element.classList.add('animate-pulse');
                setTimeout(() => {
                    element.classList.remove('animate-pulse');
                }, 1000);
            }
        },

        // Format currency values
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount || 0);
        },

        // Format date/time
        formatDateTime(timestamp) {
            if (!timestamp) return 'Never';

            return new Date(timestamp).toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        },

        // Get pricing level badge color
        getPricingLevelColor() {
            const levelColors = {
                1: 'gray',
                2: 'blue',
                3: 'green',
                4: 'yellow',
                5: 'red'
            };
            return levelColors[this.pricing.pricing_level] || 'gray';
        },

        // Get confidence level badge color
        getConfidenceLevelColor() {
            if (this.pricing.confidence_level >= 80) return 'green';
            if (this.pricing.confidence_level >= 60) return 'yellow';
            if (this.pricing.confidence_level >= 40) return 'orange';
            return 'red';
        },

        // Dismiss alert
        dismissAlert(index) {
            this.alerts.splice(index, 1);
        },

        // Cleanup on destroy
        destroy() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
            }
            if (this.updateTimeout) {
                clearTimeout(this.updateTimeout);
            }
        }
    }));
});

// Global helper functions for pricing display
window.PricingHelpers = {
    // Calculate percentage change
    calculatePercentageChange(current, previous) {
        if (!previous || previous === 0) return 0;
        return Math.round(((current - previous) / previous) * 100);
    },

    // Get change direction
    getChangeDirection(current, previous) {
        if (current > previous) return 'increase';
        if (current < previous) return 'decrease';
        return 'same';
    },

    // Format linear feet
    formatLinearFeet(lf) {
        return `${(lf || 0).toFixed(1)} LF`;
    },

    // Format price per linear foot
    formatPricePerLF(total, lf) {
        if (!lf || lf === 0) return '$0/LF';
        return `$${Math.round(total / lf)}/LF`;
    }
};