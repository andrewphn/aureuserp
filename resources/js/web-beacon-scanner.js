/**
 * Web Bluetooth Beacon Scanner
 *
 * Works on: Chrome (Android, Windows, Mac, Linux)
 * Does NOT work on: iOS Safari, Firefox
 *
 * Uses the Web Bluetooth Scanning API to detect iBeacons
 */

export function webBeaconScanner(config = {}) {
    return {
        // State
        isScanning: false,
        isSupported: false,
        error: null,
        detectedBeacons: [],
        registeredBeacons: config.beacons || [],
        position: null,
        scanDuration: config.scanDuration || 5000, // 5 seconds default

        // Callbacks
        onPosition: config.onPosition || null,
        onBeaconDetected: config.onBeaconDetected || null,

        init() {
            // Check for Web Bluetooth support
            this.isSupported = 'bluetooth' in navigator;

            if (!this.isSupported) {
                this.error = this.getUnsupportedMessage();
            }

            console.log('[WebBeaconScanner] Initialized', {
                supported: this.isSupported,
                registeredBeacons: this.registeredBeacons.length
            });
        },

        getUnsupportedMessage() {
            const ua = navigator.userAgent;
            if (/iPhone|iPad|iPod/.test(ua)) {
                return 'iOS Safari does not support Web Bluetooth. Use the manual entry form below, or scan with an Android phone.';
            }
            if (/Firefox/.test(ua)) {
                return 'Firefox does not support Web Bluetooth. Please use Chrome.';
            }
            return 'Web Bluetooth is not supported in this browser. Please use Chrome on Android or Desktop.';
        },

        /**
         * Start scanning for beacons
         * Note: Requires user gesture (button click)
         */
        async startScan() {
            if (!this.isSupported) {
                this.error = this.getUnsupportedMessage();
                return;
            }

            this.isScanning = true;
            this.error = null;
            this.detectedBeacons = [];

            try {
                // Request to scan for BLE devices
                // We use requestDevice with acceptAllDevices to see all nearby BLE
                const device = await navigator.bluetooth.requestDevice({
                    acceptAllDevices: true,
                    optionalManufacturerData: [0x004C] // Apple's company ID for iBeacon
                });

                console.log('[WebBeaconScanner] Device selected:', device.name || 'Unknown');

                // Try to get advertisement data
                if (device.watchAdvertisements) {
                    device.addEventListener('advertisementreceived', (event) => {
                        this.processAdvertisement(event);
                    });

                    await device.watchAdvertisements();

                    // Stop after scan duration
                    setTimeout(() => {
                        this.stopScan();
                    }, this.scanDuration);
                } else {
                    // Fallback: just record that we found a device
                    this.addDetectedBeacon({
                        name: device.name || 'Unknown BLE Device',
                        id: device.id,
                        rssi: -60, // Estimated
                        isIBeacon: false
                    });
                    this.isScanning = false;
                }

            } catch (err) {
                console.error('[WebBeaconScanner] Scan error:', err);

                if (err.name === 'NotFoundError') {
                    this.error = 'No Bluetooth devices found nearby. Make sure your beacons are powered on.';
                } else if (err.name === 'NotAllowedError') {
                    this.error = 'Bluetooth permission denied. Please allow Bluetooth access.';
                } else {
                    this.error = err.message;
                }

                this.isScanning = false;
            }
        },

        /**
         * Process BLE advertisement data
         */
        processAdvertisement(event) {
            console.log('[WebBeaconScanner] Advertisement received:', event);

            const beacon = {
                name: event.device.name || 'Unknown',
                rssi: event.rssi,
                txPower: event.txPower,
                timestamp: Date.now()
            };

            // Check for iBeacon manufacturer data (Apple company ID: 0x004C)
            if (event.manufacturerData) {
                const appleData = event.manufacturerData.get(0x004C);
                if (appleData) {
                    const parsed = this.parseIBeaconData(appleData);
                    if (parsed) {
                        beacon.uuid = parsed.uuid;
                        beacon.major = parsed.major;
                        beacon.minor = parsed.minor;
                        beacon.isIBeacon = true;

                        // Check if this is a registered beacon
                        beacon.isRegistered = this.registeredBeacons.some(
                            b => b.major === beacon.major && b.minor === beacon.minor
                        );
                    }
                }
            }

            this.addDetectedBeacon(beacon);
        },

        /**
         * Parse iBeacon manufacturer data
         */
        parseIBeaconData(dataView) {
            try {
                // iBeacon format:
                // Byte 0-1: Type (0x0215 for iBeacon)
                // Byte 2-17: UUID (16 bytes)
                // Byte 18-19: Major (2 bytes)
                // Byte 20-21: Minor (2 bytes)
                // Byte 22: TX Power (1 byte, signed)

                const buffer = dataView.buffer;
                const view = new DataView(buffer);

                // Check iBeacon type
                const type = view.getUint16(0, false);
                if (type !== 0x0215) {
                    return null;
                }

                // Parse UUID
                const uuidBytes = new Uint8Array(buffer, 2, 16);
                const uuid = Array.from(uuidBytes)
                    .map(b => b.toString(16).padStart(2, '0'))
                    .join('')
                    .replace(/(.{8})(.{4})(.{4})(.{4})(.{12})/, '$1-$2-$3-$4-$5');

                // Parse Major/Minor
                const major = view.getUint16(18, false);
                const minor = view.getUint16(20, false);

                // Parse TX Power (signed byte)
                const txPower = view.getInt8(22);

                return { uuid, major, minor, txPower };

            } catch (e) {
                console.error('[WebBeaconScanner] Error parsing iBeacon data:', e);
                return null;
            }
        },

        /**
         * Add or update detected beacon
         */
        addDetectedBeacon(beacon) {
            const key = beacon.major !== undefined
                ? `${beacon.major}:${beacon.minor}`
                : beacon.id || beacon.name;

            const existingIndex = this.detectedBeacons.findIndex(b => {
                if (b.major !== undefined && beacon.major !== undefined) {
                    return b.major === beacon.major && b.minor === beacon.minor;
                }
                return (b.id || b.name) === key;
            });

            if (existingIndex >= 0) {
                // Update existing (average RSSI for stability)
                const existing = this.detectedBeacons[existingIndex];
                existing.rssi = (existing.rssi + beacon.rssi) / 2;
                existing.timestamp = beacon.timestamp;
            } else {
                this.detectedBeacons.push(beacon);
            }

            if (this.onBeaconDetected) {
                this.onBeaconDetected(beacon);
            }

            // Recalculate position if we have registered beacons
            this.calculatePosition();
        },

        /**
         * Stop scanning
         */
        stopScan() {
            this.isScanning = false;
            console.log('[WebBeaconScanner] Scan stopped. Found:', this.detectedBeacons.length, 'beacons');
        },

        /**
         * Calculate position from detected beacons
         */
        calculatePosition() {
            const registeredReadings = this.detectedBeacons.filter(b => b.isRegistered);

            if (registeredReadings.length === 0) {
                this.position = null;
                return;
            }

            // Convert RSSI to distance
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

            // Weighted centroid calculation
            let totalWeight = 0;
            let x = 0;
            let y = 0;

            for (const r of readings) {
                const weight = 1 / Math.max(0.01, r.distance * r.distance);
                totalWeight += weight;
                x += r.beacon.floor_x * weight;
                y += r.beacon.floor_y * weight;
            }

            this.position = {
                x: Math.round(x / totalWeight),
                y: Math.round(y / totalWeight),
                accuracy: readings.reduce((sum, r) => sum + r.distance, 0) / readings.length,
                beaconCount: readings.length
            };

            if (this.onPosition) {
                this.onPosition(this.position);
            }
        },

        /**
         * Convert RSSI to distance (meters)
         */
        rssiToDistance(rssi, txPower = -59) {
            const measuredPower = txPower || -59;
            const n = 2.5; // Path loss exponent

            if (rssi >= 0) return 0.1;

            return Math.pow(10, (measuredPower - rssi) / (10 * n));
        },

        /**
         * Get position data for form submission
         */
        getPositionData() {
            if (!this.position) return null;

            return {
                floor_x: this.position.x,
                floor_y: this.position.y,
                accuracy: this.position.accuracy,
                beacon_count: this.position.beaconCount,
                method: 'web_bluetooth',
                readings: this.detectedBeacons
                    .filter(b => b.isRegistered)
                    .map(b => ({
                        major: b.major,
                        minor: b.minor,
                        rssi: b.rssi
                    }))
            };
        },

        /**
         * Clear detected beacons
         */
        clear() {
            this.detectedBeacons = [];
            this.position = null;
            this.error = null;
        }
    };
}

// Auto-register with Alpine if available
if (typeof window !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('webBeaconScanner', webBeaconScanner);
    });
}

export default webBeaconScanner;
