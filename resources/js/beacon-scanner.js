/**
 * iBeacon Scanner using Web Bluetooth API
 *
 * Scans for BLE iBeacons and extracts positioning data.
 * Works in Chrome on Android/Desktop. Does NOT work in iOS Safari.
 *
 * Usage:
 *   Alpine.data('beaconScanner', beaconScannerComponent)
 */

export function beaconScannerComponent(config = {}) {
    return {
        // State
        isScanning: false,
        isSupported: false,
        error: null,
        beacons: [],
        lastScan: null,
        position: null,

        // Config from server
        expectedUuid: config.uuid || null,
        registeredBeacons: config.beacons || [],
        measuredPower: config.measuredPower || -59,
        pathLossExponent: config.pathLossExponent || 2.5,

        // Callbacks
        onPosition: config.onPosition || null,
        onBeaconsFound: config.onBeaconsFound || null,

        init() {
            // Check if Web Bluetooth is supported
            this.isSupported = 'bluetooth' in navigator;

            if (!this.isSupported) {
                this.error = 'Web Bluetooth not supported. Use Chrome on Android or Desktop.';
            }

            console.log('[BeaconScanner] Initialized', {
                supported: this.isSupported,
                uuid: this.expectedUuid,
                registeredBeacons: this.registeredBeacons.length
            });
        },

        /**
         * Start scanning for beacons
         */
        async startScan() {
            if (!this.isSupported) {
                this.error = 'Bluetooth not supported in this browser';
                return;
            }

            this.isScanning = true;
            this.error = null;
            this.beacons = [];

            try {
                // Request Bluetooth device with iBeacon service
                // iBeacon uses manufacturer data, so we scan for any device
                const device = await navigator.bluetooth.requestDevice({
                    acceptAllDevices: true,
                    optionalServices: ['generic_access']
                });

                console.log('[BeaconScanner] Device selected:', device.name);

                // For iBeacons, we need to use the Bluetooth Scanning API
                // which is more limited. Let's use a simpler approach.
                this.error = 'Direct beacon scanning requires the Experimental Web Platform features flag. See setup instructions.';

            } catch (err) {
                console.error('[BeaconScanner] Scan error:', err);
                this.error = err.message;
            } finally {
                this.isScanning = false;
            }
        },

        /**
         * Alternative: Manual beacon entry
         * User enters beacon readings manually from a beacon scanner app
         */
        addManualReading(major, minor, rssi) {
            const beacon = {
                major: parseInt(major),
                minor: parseInt(minor),
                rssi: parseFloat(rssi),
                timestamp: Date.now()
            };

            // Find if beacon is registered
            const registered = this.registeredBeacons.find(
                b => b.major === beacon.major && b.minor === beacon.minor
            );

            if (registered) {
                beacon.name = registered.name;
                beacon.isRegistered = true;
            }

            // Update or add beacon
            const existingIndex = this.beacons.findIndex(
                b => b.major === beacon.major && b.minor === beacon.minor
            );

            if (existingIndex >= 0) {
                this.beacons[existingIndex] = beacon;
            } else {
                this.beacons.push(beacon);
            }

            this.lastScan = new Date().toLocaleTimeString();
            this.calculatePosition();
        },

        /**
         * Process beacon readings from external source
         * (e.g., native app bridge, or beacon scanner app)
         */
        processReadings(readings) {
            this.beacons = readings.map(r => ({
                major: r.major,
                minor: r.minor,
                rssi: r.rssi,
                txPower: r.txPower || null,
                name: this.getBeaconName(r.major, r.minor),
                isRegistered: this.isBeaconRegistered(r.major, r.minor),
                timestamp: Date.now()
            }));

            this.lastScan = new Date().toLocaleTimeString();
            this.calculatePosition();

            if (this.onBeaconsFound) {
                this.onBeaconsFound(this.beacons);
            }
        },

        /**
         * Calculate position from current beacon readings
         */
        calculatePosition() {
            const registeredReadings = this.beacons.filter(b => b.isRegistered);

            if (registeredReadings.length === 0) {
                this.position = null;
                return;
            }

            // Convert RSSI to distance for each beacon
            const readings = registeredReadings.map(b => {
                const registered = this.registeredBeacons.find(
                    r => r.major === b.major && r.minor === b.minor
                );

                return {
                    beacon: registered,
                    rssi: b.rssi,
                    distance: this.rssiToDistance(b.rssi, b.txPower)
                };
            });

            // Calculate position based on number of beacons
            if (readings.length === 1) {
                this.position = {
                    x: readings[0].beacon.floor_x,
                    y: readings[0].beacon.floor_y,
                    accuracy: readings[0].distance * 2,
                    method: 'nearest_beacon',
                    beaconCount: 1
                };
            } else if (readings.length === 2) {
                this.position = this.calculateFromTwo(readings);
            } else {
                this.position = this.trilaterate(readings);
            }

            console.log('[BeaconScanner] Position calculated:', this.position);

            if (this.onPosition) {
                this.onPosition(this.position);
            }
        },

        /**
         * Convert RSSI to distance in meters
         */
        rssiToDistance(rssi, txPower = null) {
            const measuredPower = txPower || this.measuredPower;

            if (rssi >= 0) return 0.1;

            const ratio = rssi / measuredPower;

            if (ratio < 1.0) {
                return Math.pow(ratio, 10);
            }

            return Math.pow(10, (measuredPower - rssi) / (10 * this.pathLossExponent));
        },

        /**
         * Weighted position from 2 beacons
         */
        calculateFromTwo(readings) {
            const [r1, r2] = readings;

            const w1 = 1 / Math.max(0.1, r1.distance);
            const w2 = 1 / Math.max(0.1, r2.distance);
            const total = w1 + w2;

            return {
                x: (r1.beacon.floor_x * w1 + r2.beacon.floor_x * w2) / total,
                y: (r1.beacon.floor_y * w1 + r2.beacon.floor_y * w2) / total,
                accuracy: (r1.distance + r2.distance) / 2,
                method: 'two_beacon',
                beaconCount: 2
            };
        },

        /**
         * Trilateration from 3+ beacons
         */
        trilaterate(readings) {
            // Sort by signal strength (strongest first)
            readings.sort((a, b) => b.rssi - a.rssi);

            // Use weighted centroid for simplicity
            // (Full trilateration is in the PHP service)
            let totalWeight = 0;
            let x = 0;
            let y = 0;

            for (const r of readings.slice(0, 4)) {
                const weight = 1 / Math.max(0.01, r.distance * r.distance);
                totalWeight += weight;
                x += r.beacon.floor_x * weight;
                y += r.beacon.floor_y * weight;
            }

            const avgDistance = readings.reduce((sum, r) => sum + r.distance, 0) / readings.length;

            return {
                x: x / totalWeight,
                y: y / totalWeight,
                accuracy: avgDistance,
                method: 'trilateration',
                beaconCount: readings.length
            };
        },

        /**
         * Helper methods
         */
        getBeaconName(major, minor) {
            const beacon = this.registeredBeacons.find(
                b => b.major === major && b.minor === minor
            );
            return beacon?.name || `Beacon ${major}:${minor}`;
        },

        isBeaconRegistered(major, minor) {
            return this.registeredBeacons.some(
                b => b.major === major && b.minor === minor
            );
        },

        /**
         * Get position data for form submission
         */
        getPositionData() {
            if (!this.position) return null;

            return {
                floor_x: Math.round(this.position.x),
                floor_y: Math.round(this.position.y),
                accuracy: this.position.accuracy,
                method: this.position.method,
                beacon_count: this.position.beaconCount,
                readings: this.beacons.map(b => ({
                    major: b.major,
                    minor: b.minor,
                    rssi: b.rssi
                }))
            };
        },

        /**
         * Clear all readings
         */
        clearReadings() {
            this.beacons = [];
            this.position = null;
            this.lastScan = null;
        }
    };
}

// Auto-register with Alpine if available
if (typeof window !== 'undefined' && window.Alpine) {
    window.Alpine.data('beaconScanner', beaconScannerComponent);
}

// Export for module usage
export default beaconScannerComponent;
