/**
 * Cabinet Spec Builder Alpine Component
 * 
 * A Notion-like hierarchical tree builder for cabinet specifications.
 * Hierarchy: Room → RoomLocation → CabinetRun → Cabinet
 * 
 * This component is registered globally so it's available before Alpine
 * initializes, preventing timing issues with Livewire's @script directive.
 * 
 * Accessibility Features:
 * - WCAG 2.1 AA compliant
 * - Full keyboard navigation support
 * - Screen reader announcements via aria-live regions
 * - Focus management for inline editing
 * - Keyboard shortcuts with help dialog
 */

// Define the Alpine component factory function
function createSpecAccordionBuilder(specData, expanded, isAddingCabinet, newCabinetData, pricingTiers, materialOptions, finishOptions, measurementSettings) {
    return {
        specData: specData,
        expanded: expanded,
        isAddingCabinet: isAddingCabinet || false,
        newCabinetData: newCabinetData || {},

        // Pricing data from TcsPricingService
        pricingTiers: pricingTiers || {},
        materialOptions: materialOptions || {},
        finishOptions: finishOptions || {},

        // UI state
        sidebarCollapsed: false,
        showKeyboardHelp: false,

        // Selection state (objects, not indexes for accordion)
        selectedRoomIndex: null,
        selectedRoom: null,
        selectedLocationIndex: null,
        selectedLocation: null,
        selectedRunIndex: null,
        selectedRun: null,
        selectedCabinetIndex: null,

        // Inline editing state
        editingRow: null,
        editingField: null,

        // Inline cabinet adding state
        isAddingCabinet: false,
        newCabinetData: {},

        // Validation errors for inline editing
        validationErrors: {},

        // Loading state for async operations
        isLoading: false,

        // Virtual scrolling state for large tables
        virtualScroll: {
            enabled: false,         // Auto-enable when > threshold rows
            threshold: 50,          // Enable virtual scrolling above this count
            rowHeight: 48,          // Approximate height of each row in px
            visibleStart: 0,        // First visible row index
            visibleEnd: 20,         // Last visible row index
            bufferSize: 5,          // Extra rows to render above/below viewport
            containerHeight: 400,   // Visible container height in px
            scrollTop: 0            // Current scroll position
        },

        // Field order for tab navigation
        fieldOrder: ['name', 'length_inches', 'height_inches', 'depth_inches', 'quantity'],

        init() {
            // Initialize MeasurementFormatter with settings from PHP
            if (window.MeasurementFormatter && measurementSettings) {
                window.MeasurementFormatter.init(measurementSettings);
            }

            // Watch for specData changes from Livewire
            this.$watch('specData', (value) => {
                // Re-select if current selections still valid
                if (this.selectedRoomIndex !== null && value[this.selectedRoomIndex]) {
                    this.selectedRoom = value[this.selectedRoomIndex];

                    if (this.selectedLocationIndex !== null && this.selectedRoom.children?.[this.selectedLocationIndex]) {
                        this.selectedLocation = this.selectedRoom.children[this.selectedLocationIndex];

                        if (this.selectedRunIndex !== null && this.selectedLocation.children?.[this.selectedRunIndex]) {
                            this.selectedRun = this.selectedLocation.children[this.selectedRunIndex];
                        }
                    }
                }
            });

            // Auto-select first room if available
            if (this.specData && this.specData.length > 0) {
                this.selectRoom(0);
            }

            // Add global keyboard shortcuts
            this.setupKeyboardShortcuts();
        },

        // =========================================================================
        // ACCESSIBILITY: SCREEN READER ANNOUNCEMENTS
        // =========================================================================

        /**
         * Announce a message to screen readers via aria-live region
         * @param {string} message - The message to announce
         * @param {string} priority - 'polite' or 'assertive' (default: 'polite')
         */
        announceToScreenReader(message, priority = 'polite') {
            // Try local announcements ref first, then global
            const announcementEl = this.$refs?.announcements || document.getElementById('cabinet-table-announcements');
            const globalEl = this.$refs?.globalAnnouncements || document.getElementById('global-announcements');
            
            const targetEl = announcementEl || globalEl;
            
            if (targetEl) {
                // Set priority attribute
                targetEl.setAttribute('aria-live', priority);
                
                // Clear and set message (forces re-announcement)
                targetEl.textContent = '';
                setTimeout(() => {
                    targetEl.textContent = message;
                }, 50);
            }
        },

        /**
         * Announce validation error to screen reader (assertive)
         * @param {string} fieldName - Name of the field with error
         * @param {string} errorMessage - The error message
         */
        announceValidationError(fieldName, errorMessage) {
            const friendlyNames = {
                'name': 'cabinet name',
                'length_inches': 'width',
                'height_inches': 'height',
                'depth_inches': 'depth',
                'quantity': 'quantity'
            };
            const friendlyName = friendlyNames[fieldName] || fieldName;
            this.announceToScreenReader(`Error in ${friendlyName}: ${errorMessage}`, 'assertive');
        },

        /**
         * Announce successful action to screen reader
         * @param {string} action - Description of the action
         */
        announceSuccess(action) {
            this.announceToScreenReader(action, 'polite');
        },

        // =========================================================================
        // KEYBOARD SHORTCUTS
        // =========================================================================

        /**
         * Setup global keyboard shortcuts for Excel-like editing
         */
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Ctrl/Cmd + ? to show keyboard shortcuts help
                if ((e.ctrlKey || e.metaKey) && (e.key === '?' || (e.shiftKey && e.key === '/'))) {
                    e.preventDefault();
                    this.showKeyboardHelp = !this.showKeyboardHelp;
                    if (this.showKeyboardHelp) {
                        this.announceToScreenReader('Keyboard shortcuts dialog opened');
                    }
                    return;
                }

                // F2 to edit (Excel-like)
                if (e.key === 'F2' && this.selectedRun && !this.isAddingCabinet && this.editingRow === null) {
                    e.preventDefault();
                    const cabinets = this.selectedRun.children || [];
                    if (cabinets.length > 0) {
                        // Edit first cell of first cabinet
                        this.startEdit(0, this.fieldOrder[0]);
                        this.announceToScreenReader('Editing cabinet name');
                    }
                    return;
                }

                // Arrow key navigation when editing
                if (this.editingRow !== null && !this.isAddingCabinet) {
                    this.handleArrowNavigation(e);
                }
            });
        },

        /**
         * Handle arrow key navigation in table
         */
        handleArrowNavigation(e) {
            const cabinets = this.selectedRun?.children || [];
            if (cabinets.length === 0) return;

            switch (e.key) {
                case 'ArrowRight':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        // Move to last field in row
                        this.startEdit(this.editingRow, this.fieldOrder[this.fieldOrder.length - 1]);
                        this.announceToScreenReader(`Moved to ${this.fieldOrder[this.fieldOrder.length - 1]}`);
                    }
                    break;

                case 'ArrowLeft':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        // Move to first field in row
                        this.startEdit(this.editingRow, this.fieldOrder[0]);
                        this.announceToScreenReader(`Moved to ${this.fieldOrder[0]}`);
                    }
                    break;

                case 'ArrowUp':
                    if (!e.shiftKey) {
                        e.preventDefault();
                        const prevRow = this.editingRow - 1;
                        if (prevRow >= 0) {
                            this.startEdit(prevRow, this.editingField);
                            this.announceToScreenReader(`Row ${prevRow + 1} of ${cabinets.length}`);
                        }
                    }
                    break;

                case 'ArrowDown':
                    if (!e.shiftKey) {
                        e.preventDefault();
                        const nextRow = this.editingRow + 1;
                        if (nextRow < cabinets.length) {
                            this.startEdit(nextRow, this.editingField);
                            this.announceToScreenReader(`Row ${nextRow + 1} of ${cabinets.length}`);
                        }
                    }
                    break;

                case 'Home':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        // Go to first cell of first row
                        this.startEdit(0, this.fieldOrder[0]);
                        this.announceToScreenReader('First cell of table');
                    } else {
                        e.preventDefault();
                        // Go to first cell of current row
                        this.startEdit(this.editingRow, this.fieldOrder[0]);
                        this.announceToScreenReader('First cell of row');
                    }
                    break;

                case 'End':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        // Go to last cell of last row
                        const lastRow = cabinets.length - 1;
                        this.startEdit(lastRow, this.fieldOrder[this.fieldOrder.length - 1]);
                        this.announceToScreenReader('Last cell of table');
                    } else {
                        e.preventDefault();
                        // Go to last cell of current row
                        this.startEdit(this.editingRow, this.fieldOrder[this.fieldOrder.length - 1]);
                        this.announceToScreenReader('Last cell of row');
                    }
                    break;
            }
        },

        // =========================================================================
        // SELECTION METHODS
        // =========================================================================

        selectRoom(index) {
            const room = this.specData[index];
            if (!room) return;

            this.selectedRoomIndex = index;
            this.selectedRoom = room;

            // Clear downstream selections
            this.selectedLocationIndex = null;
            this.selectedLocation = null;
            this.selectedRunIndex = null;
            this.selectedRun = null;

            // Expand this room in accordion
            if (room.id && !this.expanded.includes(room.id)) {
                this.expanded.push(room.id);
            }

            // Announce selection
            this.announceToScreenReader(`Selected room: ${room.name || 'Unnamed room'}`);
        },

        selectLocation(roomIndex, locationIndex) {
            // Make sure room is selected first
            if (this.selectedRoomIndex !== roomIndex) {
                this.selectRoom(roomIndex);
            }

            const location = this.specData[roomIndex]?.children?.[locationIndex];
            if (!location) return;

            this.selectedLocationIndex = locationIndex;
            this.selectedLocation = location;

            // Clear run selection
            this.selectedRunIndex = null;
            this.selectedRun = null;

            // Expand this location in accordion
            if (location.id && !this.expanded.includes(location.id)) {
                this.expanded.push(location.id);
            }

            // Announce selection
            this.announceToScreenReader(`Selected location: ${location.name || 'Unnamed location'}`);
        },

        selectRun(roomIndex, locationIndex, runIndex) {
            // Make sure location is selected first
            if (this.selectedRoomIndex !== roomIndex || this.selectedLocationIndex !== locationIndex) {
                this.selectLocation(roomIndex, locationIndex);
            }

            const run = this.specData[roomIndex]?.children?.[locationIndex]?.children?.[runIndex];
            if (!run) return;

            this.selectedRunIndex = runIndex;
            this.selectedRun = run;
            this.selectedCabinetIndex = null; // Clear cabinet selection when changing runs

            // Announce selection
            const cabinetCount = (run.children || []).length;
            this.announceToScreenReader(`Selected run: ${run.name || 'Unnamed run'}. Contains ${cabinetCount} cabinet${cabinetCount !== 1 ? 's' : ''}.`);
        },

        // Toggle accordion expansion
        toggleAccordion(nodeId) {
            const idx = this.expanded.indexOf(nodeId);
            if (idx > -1) {
                this.expanded.splice(idx, 1);
                this.announceToScreenReader('Section collapsed');
            } else {
                this.expanded.push(nodeId);
                this.announceToScreenReader('Section expanded');
            }
        },

        isExpanded(nodeId) {
            return this.expanded.includes(nodeId);
        },

        // Breadcrumb navigation
        clearToRoom() {
            this.selectedLocationIndex = null;
            this.selectedLocation = null;
            this.selectedRunIndex = null;
            this.selectedRun = null;
            this.announceToScreenReader('Navigated back to room level');
        },

        clearToLocation() {
            this.selectedRunIndex = null;
            this.selectedRun = null;
            this.announceToScreenReader('Navigated back to location level');
        },

        // =========================================================================
        // PRICING HELPERS
        // =========================================================================

        /**
         * Calculate price per linear foot from level, material, and finish
         * Prices are extracted from the option labels which contain the price info
         */
        getPricePerLF(level = '3', material = 'stain_grade', finish = 'unfinished') {
            // Extract base price from level label (e.g., "Level 3 - Enhanced ($192/LF)" -> 192)
            let basePrice = 192; // Default Level 3
            const levelLabel = this.pricingTiers[level] || '';
            const levelMatch = levelLabel.match(/\$(\d+(?:\.\d+)?)/);
            if (levelMatch) {
                basePrice = parseFloat(levelMatch[1]);
            } else {
                // Fallback prices if label doesn't contain price
                const fallbackPrices = {'1': 138, '2': 168, '3': 192, '4': 242, '5': 345};
                basePrice = fallbackPrices[level] || 192;
            }

            // Extract material price from label (e.g., "Stain Grade +$156/LF" -> 156)
            let materialPrice = 0;
            const materialLabel = this.materialOptions[material] || '';
            const materialMatch = materialLabel.match(/\+\$(\d+(?:\.\d+)?)/);
            if (materialMatch) {
                materialPrice = parseFloat(materialMatch[1]);
            } else if (material === 'stain_grade') {
                materialPrice = 156; // Default for stain grade
            }

            // Extract finish price from label (e.g., "Stain + Clear +$85/LF" -> 85)
            let finishPrice = 0;
            const finishLabel = this.finishOptions[finish] || '';
            const finishMatch = finishLabel.match(/\+\$(\d+(?:\.\d+)?)/);
            if (finishMatch) {
                finishPrice = parseFloat(finishMatch[1]);
            }

            return basePrice + materialPrice + finishPrice;
        },

        /**
         * Update location pricing field and sync to Livewire
         */
        updateLocationPricing(field, value) {
            if (this.selectedLocationIndex === null) return;

            // Update local state
            this.selectedLocation[field] = value || null; // null for "inherit"

            // Build path and update via Livewire
            const path = `${this.selectedRoomIndex}.children.${this.selectedLocationIndex}`;
            this.$wire.updateNodeField(path, { [field]: value || null });
        },

        /**
         * Update room pricing field and sync to Livewire
         */
        updateRoomPricing(field, value) {
            if (this.selectedRoomIndex === null) return;

            // Update local state
            this.selectedRoom[field] = value;

            // Update via Livewire
            const path = `${this.selectedRoomIndex}`;
            this.$wire.updateNodeField(path, { [field]: value });
        },

        /**
         * Update run pricing field and sync to Livewire
         */
        updateRunPricing(field, value) {
            if (this.selectedRunIndex === null) return;

            // Update local state
            this.selectedRun[field] = value || null;

            // Build path and update via Livewire
            const path = `${this.selectedRoomIndex}.children.${this.selectedLocationIndex}.children.${this.selectedRunIndex}`;
            this.$wire.updateNodeField(path, { [field]: value || null });
        },

        // =========================================================================
        // CABINET OPERATIONS
        // =========================================================================

        addCabinetFromCode(code) {
            if (!code || !this.selectedRun) return;

            code = code.trim().toUpperCase();

            // Parse cabinet code (B24, W30, SB36, etc.)
            const parsed = this.parseCabinetCode(code);

            if (!parsed.width) {
                this.announceValidationError('code', 'Invalid cabinet code format');
                return;
            }

            // Build the run path
            const runPath = `${this.selectedRoomIndex}.children.${this.selectedLocationIndex}.children.${this.selectedRunIndex}`;

            // Dispatch to Livewire to add cabinet
            this.$wire.handleAiAddCabinet({
                run_path: runPath,
                name: code,
                cabinet_type: parsed.type,
                length_inches: parsed.width,
                depth_inches: parsed.depth,
                height_inches: parsed.height,
                quantity: 1,
                source: 'user'
            });

            this.announceSuccess(`Added cabinet ${code}`);
        },

        parseCabinetCode(code) {
            const defaults = {
                base: { depth: 24, height: 34.5 },
                wall: { depth: 12, height: 30 },
                tall: { depth: 24, height: 84 },
                vanity: { depth: 21, height: 34.5 },
                sink_base: { depth: 24, height: 34.5 }
            };

            let type = 'base';
            let width = null;

            // Patterns for different cabinet types
            if (/^(SB|BBC)(\d+)$/.test(code)) {
                type = 'sink_base';
                width = parseInt(code.match(/(\d+)$/)[1]);
            } else if (/^(DB|B)(\d+)$/.test(code)) {
                type = 'base';
                width = parseInt(code.match(/(\d+)$/)[1]);
            } else if (/^W(\d+)/.test(code)) {
                type = 'wall';
                width = parseInt(code.match(/^W(\d+)/)[1]);
            } else if (/^(T|TP|P)(\d+)$/.test(code)) {
                type = 'tall';
                width = parseInt(code.match(/(\d+)$/)[1]);
            } else if (/^V(\d+)$/.test(code)) {
                type = 'vanity';
                width = parseInt(code.match(/(\d+)$/)[1]);
            } else if (/^\d+$/.test(code)) {
                type = 'base';
                width = parseInt(code);
            }

            return {
                type: type,
                width: width,
                depth: defaults[type]?.depth || 24,
                height: defaults[type]?.height || 34.5
            };
        },

        // =========================================================================
        // INLINE EDITING
        // =========================================================================

        startEdit(rowIndex, field) {
            this.editingRow = rowIndex;
            this.editingField = field;
            
            // Clear any previous validation errors
            this.validationErrors = {};

            // Announce to screen readers
            const cabinet = this.selectedRun?.children?.[rowIndex];
            if (cabinet) {
                const friendlyNames = {
                    'name': 'name',
                    'length_inches': 'width',
                    'height_inches': 'height',
                    'depth_inches': 'depth',
                    'quantity': 'quantity'
                };
                this.announceToScreenReader(`Editing ${friendlyNames[field] || field} for cabinet ${cabinet.name || 'unnamed'}`);
            }
        },

        /**
         * Handle F2 key to enter edit mode (Excel-like)
         */
        handleF2Edit(event) {
            if (event.key === 'F2' && this.selectedRun && this.selectedRun.children) {
                event.preventDefault();
                const firstCabinet = this.selectedRun.children[0];
                if (firstCabinet) {
                    this.startEdit(0, this.fieldOrder[0]);
                }
            }
        },

        cancelEdit() {
            this.editingRow = null;
            this.editingField = null;
            this.validationErrors = {};
            this.announceToScreenReader('Edit cancelled');
        },

        /**
         * Validate a field value
         * @param {string} field - Field name
         * @param {*} value - Field value
         * @returns {string|null} - Error message or null if valid
         */
        validateField(field, value) {
            if (['length_inches', 'height_inches', 'depth_inches'].includes(field)) {
                const num = parseFloat(value);
                if (isNaN(num)) {
                    return 'Please enter a valid number';
                }
                if (num <= 0) {
                    return 'Value must be greater than 0';
                }
                if (num > 120) {
                    return 'Value seems too large (max 120 inches)';
                }
            } else if (field === 'quantity') {
                const num = parseInt(value);
                if (isNaN(num)) {
                    return 'Please enter a valid number';
                }
                if (num < 1) {
                    return 'Quantity must be at least 1';
                }
                if (num > 100) {
                    return 'Quantity seems too large (max 100)';
                }
            }
            return null;
        },

        saveCabinetField(cabIndex, field, value) {
            if (!this.selectedRun || !this.selectedRun.children?.[cabIndex]) return;

            const cabinet = this.selectedRun.children[cabIndex];
            const oldValue = cabinet[field];

            // Validate the field
            const error = this.validateField(field, value);
            if (error) {
                this.validationErrors[field] = error;
                this.announceValidationError(field, error);
                return;
            }

            // Clear validation error
            delete this.validationErrors[field];

            // Parse numeric fields with validation
            if (['length_inches', 'height_inches', 'depth_inches'].includes(field)) {
                value = parseFloat(value);
            } else if (field === 'quantity') {
                value = parseInt(value);
            }

            if (value === oldValue) {
                this.cancelEdit();
                return;
            }

            cabinet[field] = value;

            const cabinetPath = `${this.selectedRoomIndex}.children.${this.selectedLocationIndex}.children.${this.selectedRunIndex}.children.${cabIndex}`;
            this.$wire.updateCabinetField(cabinetPath, field, value);

            this.announceSuccess(`Updated ${field.replace('_', ' ')} to ${value}`);
            this.cancelEdit();
        },

        moveToNextCell(rowIndex, currentField, event = null) {
            if (event) event.preventDefault();

            const currentIdx = this.fieldOrder.indexOf(currentField);
            const nextIdx = currentIdx + 1;

            if (nextIdx < this.fieldOrder.length) {
                this.$nextTick(() => {
                    this.startEdit(rowIndex, this.fieldOrder[nextIdx]);
                });
            } else {
                this.moveToNextRow(rowIndex, event);
            }
        },

        moveToPreviousCell(rowIndex, currentField, event = null) {
            if (event) event.preventDefault();

            const currentIdx = this.fieldOrder.indexOf(currentField);
            const prevIdx = currentIdx - 1;

            if (prevIdx >= 0) {
                this.$nextTick(() => {
                    this.startEdit(rowIndex, this.fieldOrder[prevIdx]);
                });
            } else {
                // Move to previous row, last field
                const prevRow = rowIndex - 1;
                if (prevRow >= 0) {
                    this.$nextTick(() => {
                        this.startEdit(prevRow, this.fieldOrder[this.fieldOrder.length - 1]);
                    });
                }
            }
        },

        moveToNextRow(rowIndex, event = null) {
            if (event) event.preventDefault();

            const cabinets = this.selectedRun?.children || [];
            const nextRow = rowIndex + 1;

            if (nextRow < cabinets.length) {
                this.$nextTick(() => {
                    this.startEdit(nextRow, this.fieldOrder[0]);
                });
            } else {
                this.cancelEdit();
                this.$nextTick(() => {
                    this.$refs.quickAddInput?.focus();
                });
            }
        },

        deleteCabinet(cabIndex) {
            if (!this.selectedRun) return;

            const cabinet = this.selectedRun.children?.[cabIndex];
            const cabinetName = cabinet?.name || 'cabinet';

            const cabinetPath = `${this.selectedRoomIndex}.children.${this.selectedLocationIndex}.children.${this.selectedRunIndex}.children.${cabIndex}`;
            this.$wire.deleteCabinetByPath(cabinetPath);

            this.announceSuccess(`Deleted ${cabinetName}`);
        },

        // =========================================================================
        // INLINE CABINET ADDING
        // =========================================================================

        /**
         * Save the inline cabinet being added
         * @param {boolean} addAnother - If true, save and prepare for another entry
         */
        saveInlineCabinet(addAnother = false) {
            if (!this.selectedRun) return;

            const runPath = `${this.selectedRoomIndex}.children.${this.selectedLocationIndex}.children.${this.selectedRunIndex}`;
            
            // Validate minimum data
            const userInput = (this.newCabinetData?.name || '').trim();
            const hasWidth = !!(this.newCabinetData?.length_inches);

            if (!userInput && !hasWidth) {
                this.announceValidationError('name', 'Enter a cabinet code or width');
                return; // Don't save empty entries
            }

            // Validate dimensions if provided
            const errors = {};
            if (this.newCabinetData.length_inches) {
                const error = this.validateField('length_inches', this.newCabinetData.length_inches);
                if (error) errors.length_inches = error;
            }
            if (this.newCabinetData.height_inches) {
                const error = this.validateField('height_inches', this.newCabinetData.height_inches);
                if (error) errors.height_inches = error;
            }
            if (this.newCabinetData.depth_inches) {
                const error = this.validateField('depth_inches', this.newCabinetData.depth_inches);
                if (error) errors.depth_inches = error;
            }
            if (this.newCabinetData.quantity) {
                const error = this.validateField('quantity', this.newCabinetData.quantity);
                if (error) errors.quantity = error;
            }

            if (Object.keys(errors).length > 0) {
                this.validationErrors = errors;
                const firstError = Object.entries(errors)[0];
                this.announceValidationError(firstError[0], firstError[1]);
                return;
            }

            // Clear validation errors
            this.validationErrors = {};

            // Default quantity to 1 if not set
            if (!this.newCabinetData.quantity) {
                this.newCabinetData.quantity = 1;
            }

            // Set loading state
            this.isLoading = true;

            // Call Livewire to save
            this.$wire.saveCabinet(addAnother).then(() => {
                this.isLoading = false;
                const cabinetName = this.newCabinetData.name || 'Cabinet';
                this.announceSuccess(`Added ${cabinetName}`);
                
                if (!addAnother) {
                    // Reset for next time
                    this.newCabinetData = {};
                } else {
                    this.announceToScreenReader('Ready to add another cabinet');
                }
            }).catch(() => {
                this.isLoading = false;
                this.announceValidationError('save', 'Failed to save cabinet');
            });
        },

        /**
         * Cancel inline cabinet adding
         */
        cancelInlineAdd() {
            this.$wire.cancelAdd();
            this.newCabinetData = {};
            this.validationErrors = {};
            this.announceToScreenReader('Cancelled adding cabinet');
        },

        /**
         * Focus next field in inline add row
         * @param {string} fieldName - Name of the next field to focus
         */
        focusNextField(fieldName) {
            this.$nextTick(() => {
                const input = document.querySelector(`input[x-model="newCabinetData.${fieldName}"]`);
                if (input) {
                    input.focus();
                    if (input.type === 'number') {
                        input.select();
                    }
                    // Announce the field change
                    const friendlyNames = {
                        'length_inches': 'width',
                        'height_inches': 'height',
                        'depth_inches': 'depth',
                        'quantity': 'quantity'
                    };
                    this.announceToScreenReader(`Now editing ${friendlyNames[fieldName] || fieldName}`);
                }
            });
        },

        /**
         * Format dimension for display (with measurement formatter if available)
         */
        formatDimension(value) {
            if (!value) return '-';
            if (window.MeasurementFormatter && window.MeasurementFormatter.format) {
                return window.MeasurementFormatter.format(value, 'inches');
            }
            return `${value}"`;
        },

        /**
         * Format linear feet for display
         */
        formatLinearFeet(value) {
            if (!value) return '0.00 LF';
            if (window.MeasurementFormatter && window.MeasurementFormatter.formatLinearFeet) {
                return window.MeasurementFormatter.formatLinearFeet(value);
            }
            return `${parseFloat(value).toFixed(2)} LF`;
        },

        /**
         * Format dimensions for display (W x H x D)
         */
        formatDimensions(width, height, depth) {
            if (!width || !height || !depth) return '-';
            const w = this.formatDimension(width);
            const h = this.formatDimension(height);
            const d = this.formatDimension(depth);
            return `${w} × ${h} × ${d}`;
        },

        // =========================================================================
        // VIRTUAL SCROLLING (Performance optimization for large tables)
        // =========================================================================

        /**
         * Check if virtual scrolling should be enabled based on row count
         * @returns {boolean}
         */
        shouldUseVirtualScroll() {
            const cabinets = this.selectedRun?.children || [];
            return cabinets.length > this.virtualScroll.threshold;
        },

        /**
         * Get the visible cabinets for virtual scrolling
         * Returns all cabinets if virtual scrolling is disabled
         * @returns {Array}
         */
        getVisibleCabinets() {
            const cabinets = this.selectedRun?.children || [];
            
            if (!this.shouldUseVirtualScroll()) {
                return cabinets;
            }

            const { visibleStart, visibleEnd, bufferSize } = this.virtualScroll;
            const start = Math.max(0, visibleStart - bufferSize);
            const end = Math.min(cabinets.length, visibleEnd + bufferSize);
            
            return cabinets.slice(start, end);
        },

        /**
         * Get the actual index of a cabinet in the virtual scroll view
         * @param {number} virtualIndex - Index within the visible subset
         * @returns {number} - Actual index in the full cabinet array
         */
        getActualCabinetIndex(virtualIndex) {
            if (!this.shouldUseVirtualScroll()) {
                return virtualIndex;
            }
            
            const bufferSize = this.virtualScroll.bufferSize;
            const start = Math.max(0, this.virtualScroll.visibleStart - bufferSize);
            return start + virtualIndex;
        },

        /**
         * Handle scroll event for virtual scrolling
         * @param {Event} event - Scroll event from the table container
         */
        handleVirtualScroll(event) {
            if (!this.shouldUseVirtualScroll()) return;
            
            const scrollTop = event.target.scrollTop;
            const { rowHeight, containerHeight, bufferSize } = this.virtualScroll;
            
            this.virtualScroll.scrollTop = scrollTop;
            this.virtualScroll.visibleStart = Math.floor(scrollTop / rowHeight);
            this.virtualScroll.visibleEnd = Math.ceil((scrollTop + containerHeight) / rowHeight);
        },

        /**
         * Get the total height of the virtual scroll spacer
         * Used to maintain proper scrollbar size
         * @returns {number}
         */
        getVirtualScrollHeight() {
            if (!this.shouldUseVirtualScroll()) return 0;
            
            const cabinets = this.selectedRun?.children || [];
            return cabinets.length * this.virtualScroll.rowHeight;
        },

        /**
         * Get the top offset for the virtual scroll content
         * Positions visible rows correctly within the scroll container
         * @returns {number}
         */
        getVirtualScrollOffset() {
            if (!this.shouldUseVirtualScroll()) return 0;
            
            const { visibleStart, bufferSize, rowHeight } = this.virtualScroll;
            const start = Math.max(0, visibleStart - bufferSize);
            return start * rowHeight;
        },

        /**
         * Scroll to a specific cabinet by index
         * Useful when editing or selecting a specific cabinet
         * @param {number} index - Cabinet index to scroll to
         */
        scrollToCabinet(index) {
            if (!this.shouldUseVirtualScroll()) return;
            
            const { rowHeight, containerHeight } = this.virtualScroll;
            const targetScroll = index * rowHeight - (containerHeight / 2);
            
            const container = this.$el.querySelector('.cabinet-table-container');
            if (container) {
                container.scrollTo({
                    top: Math.max(0, targetScroll),
                    behavior: 'smooth'
                });
            }
        },

        /**
         * Initialize virtual scroll settings based on container
         * Call this when the run is selected or container is mounted
         */
        initVirtualScroll() {
            const container = this.$el.querySelector('.cabinet-table-container');
            if (container) {
                this.virtualScroll.containerHeight = container.clientHeight;
                
                // Calculate visible rows
                const { rowHeight, containerHeight, bufferSize } = this.virtualScroll;
                this.virtualScroll.visibleEnd = Math.ceil(containerHeight / rowHeight) + bufferSize;
            }
        },

        // =========================================================================
        // CONTEXT MENU
        // =========================================================================

        contextMenu: {
            show: false,
            x: 0,
            y: 0,
            type: null, // 'room', 'location', 'run'
            roomIdx: null,
            locIdx: null,
            runIdx: null
        },

        openContextMenu(event, type, roomIdx, locIdx, runIdx) {
            // Get the node and siblings to determine position
            let node, siblings, currentIdx;

            if (type === 'room') {
                node = this.specData[roomIdx];
                siblings = this.specData;
                currentIdx = roomIdx;
            } else if (type === 'location') {
                node = this.specData[roomIdx]?.children?.[locIdx];
                siblings = this.specData[roomIdx]?.children || [];
                currentIdx = locIdx;
            } else if (type === 'run') {
                node = this.specData[roomIdx]?.children?.[locIdx]?.children?.[runIdx];
                siblings = this.specData[roomIdx]?.children?.[locIdx]?.children || [];
                currentIdx = runIdx;
            }

            this.contextMenu = {
                show: true,
                x: event.clientX,
                y: event.clientY,
                type: type,
                roomIdx: roomIdx,
                locIdx: locIdx,
                runIdx: runIdx,
                nodeName: node?.name || 'Item',
                isFirst: currentIdx === 0,
                isLast: currentIdx === siblings.length - 1,
                childType: type === 'room' ? 'Location' : (type === 'location' ? 'Run' : 'Cabinet')
            };

            // Announce context menu opened
            this.announceToScreenReader(`Context menu opened for ${node?.name || type}. Use arrow keys to navigate, Enter to select.`);

            // Close on click outside
            setTimeout(() => {
                document.addEventListener('click', this.closeContextMenu.bind(this), { once: true });
            }, 0);
        },

        closeContextMenu() {
            this.contextMenu.show = false;
        },

        contextMenuAction(action) {
            const { type, roomIdx, locIdx, runIdx, nodeName } = this.contextMenu;
            let path;

            if (type === 'room') {
                path = roomIdx.toString();
            } else if (type === 'location') {
                path = `${roomIdx}.children.${locIdx}`;
            } else if (type === 'run') {
                path = `${roomIdx}.children.${locIdx}.children.${runIdx}`;
            }

            switch (action) {
                case 'edit':
                    this.$wire.mountAction('editNode', { nodePath: path });
                    this.announceToScreenReader(`Opening editor for ${nodeName}`);
                    break;
                case 'duplicate':
                    this.$wire.mountAction('duplicateNode', { nodePath: path, nodeType: type === 'location' ? 'room_location' : (type === 'run' ? 'cabinet_run' : type) });
                    this.announceSuccess(`Duplicated ${nodeName}`);
                    break;
                case 'moveUp':
                    this.$wire.moveNode(path, 'up');
                    this.announceSuccess(`Moved ${nodeName} up`);
                    break;
                case 'moveDown':
                    this.$wire.moveNode(path, 'down');
                    this.announceSuccess(`Moved ${nodeName} down`);
                    break;
                case 'addChild':
                    if (type === 'room') {
                        this.$wire.mountAction('createLocation', { roomPath: path });
                    } else if (type === 'location') {
                        this.$wire.mountAction('createRun', { locationPath: path });
                    } else if (type === 'run') {
                        this.$wire.mountAction('createCabinet', { runPath: path });
                    }
                    break;
                case 'delete':
                    this.$wire.mountAction('deleteNode', { nodePath: path, nodeType: type === 'location' ? 'room_location' : (type === 'run' ? 'cabinet_run' : type) });
                    break;
            }

            this.closeContextMenu();
        }
    };
}

// Register Alpine component when Alpine is available
// Prevent duplicate registrations by checking if already registered
let specAccordionBuilderRegistered = false;

function registerSpecAccordionBuilder() {
    if (specAccordionBuilderRegistered) return;
    
    if (typeof Alpine !== 'undefined' && !Alpine.data('specAccordionBuilder')) {
        Alpine.data('specAccordionBuilder', createSpecAccordionBuilder);
        specAccordionBuilderRegistered = true;
        // Only log in development
        if (import.meta.env?.DEV || window.location.hostname.includes('localhost')) {
            console.log('✅ Cabinet Spec Builder Alpine component registered (with accessibility features)');
        }
    }
}

if (typeof Alpine !== 'undefined') {
    registerSpecAccordionBuilder();
} else {
    // Wait for Alpine to be ready (only register once)
    document.addEventListener('alpine:init', registerSpecAccordionBuilder, { once: true });
}

// Export for ES module imports
export default createSpecAccordionBuilder;
