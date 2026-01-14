/**
 * DWG/DXF Parser Module
 * 
 * Provides client-side parsing of DWG and DXF CAD files.
 * - DXF: Parsed natively in JavaScript (text-based format)
 * - DWG: Uses WebAssembly via libredwg-web (optional, loaded on demand)
 * 
 * @module DwgParser
 */

/**
 * Entity types that can be parsed from CAD files
 */
const EntityTypes = {
    LINE: 'LINE',
    POLYLINE: 'POLYLINE',
    LWPOLYLINE: 'LWPOLYLINE',
    CIRCLE: 'CIRCLE',
    ARC: 'ARC',
    ELLIPSE: 'ELLIPSE',
    SPLINE: 'SPLINE',
    TEXT: 'TEXT',
    MTEXT: 'MTEXT',
    DIMENSION: 'DIMENSION',
    INSERT: 'INSERT',
    BLOCK: 'BLOCK',
    HATCH: 'HATCH',
    SOLID: 'SOLID',
    POINT: 'POINT',
    FACE3D: '3DFACE',
    VIEWPORT: 'VIEWPORT',
};

/**
 * DXF Group Code Meanings
 */
const DXF_CODES = {
    ENTITY_TYPE: 0,
    LAYER: 8,
    COLOR: 62,
    X_COORD: 10,
    Y_COORD: 20,
    Z_COORD: 30,
    X2_COORD: 11,
    Y2_COORD: 21,
    Z2_COORD: 31,
    RADIUS: 40,
    START_ANGLE: 50,
    END_ANGLE: 51,
    TEXT_VALUE: 1,
    TEXT_HEIGHT: 40,
    TEXT_ROTATION: 50,
    BLOCK_NAME: 2,
    LINE_TYPE: 6,
    HANDLE: 5,
};

/**
 * Parse a DXF file from text content
 */
class DxfParser {
    constructor() {
        this.entities = [];
        this.layers = {};
        this.blocks = {};
        this.header = {};
        this.bounds = {
            minX: Infinity,
            minY: Infinity,
            maxX: -Infinity,
            maxY: -Infinity,
        };
    }

    /**
     * Parse DXF content string
     * @param {string} content - DXF file content
     * @returns {Object} Parsed DXF data
     */
    parse(content) {
        const lines = content.split(/\r?\n/);
        const pairs = this._parsePairs(lines);
        
        let currentSection = null;
        let i = 0;

        while (i < pairs.length) {
            const [code, value] = pairs[i];

            if (code === 0 && value === 'SECTION') {
                i++;
                if (pairs[i] && pairs[i][0] === 2) {
                    currentSection = pairs[i][1];
                }
            } else if (code === 0 && value === 'ENDSEC') {
                currentSection = null;
            } else if (currentSection === 'HEADER') {
                i = this._parseHeader(pairs, i);
                continue;
            } else if (currentSection === 'TABLES') {
                i = this._parseTables(pairs, i);
                continue;
            } else if (currentSection === 'BLOCKS') {
                i = this._parseBlocks(pairs, i);
                continue;
            } else if (currentSection === 'ENTITIES') {
                i = this._parseEntities(pairs, i);
                continue;
            }

            i++;
        }

        return {
            header: this.header,
            layers: this.layers,
            blocks: this.blocks,
            entities: this.entities,
            bounds: this.bounds,
            stats: {
                entityCount: this.entities.length,
                layerCount: Object.keys(this.layers).length,
                blockCount: Object.keys(this.blocks).length,
            }
        };
    }

    /**
     * Parse raw lines into code-value pairs
     */
    _parsePairs(lines) {
        const pairs = [];
        for (let i = 0; i < lines.length - 1; i += 2) {
            const code = parseInt(lines[i].trim(), 10);
            const value = lines[i + 1]?.trim() || '';
            if (!isNaN(code)) {
                pairs.push([code, value]);
            }
        }
        return pairs;
    }

    /**
     * Parse HEADER section
     */
    _parseHeader(pairs, startIndex) {
        let i = startIndex;
        while (i < pairs.length) {
            const [code, value] = pairs[i];
            if (code === 0 && (value === 'ENDSEC' || value === 'SECTION')) {
                return i;
            }
            if (code === 9) {
                // Header variable name
                const varName = value;
                const values = [];
                i++;
                while (i < pairs.length && pairs[i][0] !== 9 && pairs[i][0] !== 0) {
                    values.push({ code: pairs[i][0], value: pairs[i][1] });
                    i++;
                }
                this.header[varName] = values.length === 1 ? values[0].value : values;
                continue;
            }
            i++;
        }
        return i;
    }

    /**
     * Parse TABLES section (layers, linetypes, etc.)
     */
    _parseTables(pairs, startIndex) {
        let i = startIndex;
        let currentTable = null;

        while (i < pairs.length) {
            const [code, value] = pairs[i];
            if (code === 0 && value === 'ENDSEC') {
                return i;
            }
            if (code === 0 && value === 'TABLE') {
                i++;
                if (pairs[i] && pairs[i][0] === 2) {
                    currentTable = pairs[i][1];
                }
            } else if (code === 0 && value === 'LAYER' && currentTable === 'LAYER') {
                const layer = this._parseLayer(pairs, i);
                if (layer.name) {
                    this.layers[layer.name] = layer;
                }
                i = layer.endIndex;
                continue;
            }
            i++;
        }
        return i;
    }

    /**
     * Parse a single LAYER entry
     */
    _parseLayer(pairs, startIndex) {
        const layer = { name: '', color: 7, frozen: false, locked: false };
        let i = startIndex + 1;

        while (i < pairs.length) {
            const [code, value] = pairs[i];
            if (code === 0) {
                break;
            }
            switch (code) {
                case 2: layer.name = value; break;
                case 62: layer.color = parseInt(value, 10); break;
                case 70: 
                    const flags = parseInt(value, 10);
                    layer.frozen = (flags & 1) !== 0;
                    layer.locked = (flags & 4) !== 0;
                    break;
            }
            i++;
        }
        layer.endIndex = i;
        return layer;
    }

    /**
     * Parse BLOCKS section
     */
    _parseBlocks(pairs, startIndex) {
        let i = startIndex;
        let currentBlock = null;

        while (i < pairs.length) {
            const [code, value] = pairs[i];
            if (code === 0 && value === 'ENDSEC') {
                return i;
            }
            if (code === 0 && value === 'BLOCK') {
                currentBlock = { name: '', entities: [], basePoint: { x: 0, y: 0 } };
            } else if (code === 0 && value === 'ENDBLK') {
                if (currentBlock && currentBlock.name) {
                    this.blocks[currentBlock.name] = currentBlock;
                }
                currentBlock = null;
            } else if (currentBlock) {
                if (code === 2) currentBlock.name = value;
                else if (code === 10) currentBlock.basePoint.x = parseFloat(value);
                else if (code === 20) currentBlock.basePoint.y = parseFloat(value);
            }
            i++;
        }
        return i;
    }

    /**
     * Parse ENTITIES section
     */
    _parseEntities(pairs, startIndex) {
        let i = startIndex;

        while (i < pairs.length) {
            const [code, value] = pairs[i];
            if (code === 0 && value === 'ENDSEC') {
                return i;
            }
            if (code === 0 && EntityTypes[value]) {
                const entity = this._parseEntity(pairs, i, value);
                if (entity) {
                    this.entities.push(entity);
                    this._updateBounds(entity);
                }
                i = entity ? entity.endIndex : i + 1;
                continue;
            }
            i++;
        }
        return i;
    }

    /**
     * Parse a single entity
     */
    _parseEntity(pairs, startIndex, type) {
        const entity = {
            type: type,
            layer: '0',
            color: null,
            handle: null,
        };

        let i = startIndex + 1;

        while (i < pairs.length) {
            const [code, value] = pairs[i];
            if (code === 0) {
                break;
            }

            // Common properties
            switch (code) {
                case 5: entity.handle = value; break;
                case 8: entity.layer = value; break;
                case 62: entity.color = parseInt(value, 10); break;
                case 6: entity.lineType = value; break;
            }

            // Type-specific parsing
            this._parseEntityProperty(entity, type, code, value);
            i++;
        }

        entity.endIndex = i;
        return entity;
    }

    /**
     * Parse entity-specific properties
     */
    _parseEntityProperty(entity, type, code, value) {
        switch (type) {
            case 'LINE':
                if (code === 10) entity.x1 = parseFloat(value);
                else if (code === 20) entity.y1 = parseFloat(value);
                else if (code === 11) entity.x2 = parseFloat(value);
                else if (code === 21) entity.y2 = parseFloat(value);
                break;

            case 'CIRCLE':
                if (code === 10) entity.x = parseFloat(value);
                else if (code === 20) entity.y = parseFloat(value);
                else if (code === 40) entity.radius = parseFloat(value);
                break;

            case 'ARC':
                if (code === 10) entity.x = parseFloat(value);
                else if (code === 20) entity.y = parseFloat(value);
                else if (code === 40) entity.radius = parseFloat(value);
                else if (code === 50) entity.startAngle = parseFloat(value);
                else if (code === 51) entity.endAngle = parseFloat(value);
                break;

            case 'LWPOLYLINE':
            case 'POLYLINE':
                if (!entity.vertices) entity.vertices = [];
                if (code === 10) {
                    entity.vertices.push({ x: parseFloat(value), y: 0, bulge: 0 });
                } else if (code === 20 && entity.vertices.length > 0) {
                    entity.vertices[entity.vertices.length - 1].y = parseFloat(value);
                } else if (code === 42 && entity.vertices.length > 0) {
                    entity.vertices[entity.vertices.length - 1].bulge = parseFloat(value);
                } else if (code === 70) {
                    entity.closed = (parseInt(value, 10) & 1) !== 0;
                }
                break;

            case 'TEXT':
            case 'MTEXT':
                if (code === 1) entity.text = value;
                else if (code === 10) entity.x = parseFloat(value);
                else if (code === 20) entity.y = parseFloat(value);
                else if (code === 40) entity.height = parseFloat(value);
                else if (code === 50) entity.rotation = parseFloat(value);
                else if (code === 7) entity.style = value;
                break;

            case 'INSERT':
                if (code === 2) entity.blockName = value;
                else if (code === 10) entity.x = parseFloat(value);
                else if (code === 20) entity.y = parseFloat(value);
                else if (code === 41) entity.scaleX = parseFloat(value);
                else if (code === 42) entity.scaleY = parseFloat(value);
                else if (code === 50) entity.rotation = parseFloat(value);
                break;

            case 'ELLIPSE':
                if (code === 10) entity.x = parseFloat(value);
                else if (code === 20) entity.y = parseFloat(value);
                else if (code === 11) entity.majorAxisX = parseFloat(value);
                else if (code === 21) entity.majorAxisY = parseFloat(value);
                else if (code === 40) entity.ratio = parseFloat(value);
                else if (code === 41) entity.startAngle = parseFloat(value);
                else if (code === 42) entity.endAngle = parseFloat(value);
                break;

            case 'DIMENSION':
                if (code === 10) entity.x = parseFloat(value);
                else if (code === 20) entity.y = parseFloat(value);
                else if (code === 1) entity.text = value;
                else if (code === 70) entity.dimensionType = parseInt(value, 10);
                break;

            case 'SPLINE':
                if (!entity.controlPoints) entity.controlPoints = [];
                if (!entity.fitPoints) entity.fitPoints = [];
                if (code === 10) {
                    entity.controlPoints.push({ x: parseFloat(value), y: 0 });
                } else if (code === 20 && entity.controlPoints.length > 0) {
                    entity.controlPoints[entity.controlPoints.length - 1].y = parseFloat(value);
                } else if (code === 11) {
                    entity.fitPoints.push({ x: parseFloat(value), y: 0 });
                } else if (code === 21 && entity.fitPoints.length > 0) {
                    entity.fitPoints[entity.fitPoints.length - 1].y = parseFloat(value);
                } else if (code === 71) {
                    entity.degree = parseInt(value, 10);
                }
                break;

            case 'POINT':
                if (code === 10) entity.x = parseFloat(value);
                else if (code === 20) entity.y = parseFloat(value);
                break;

            case 'SOLID':
            case '3DFACE':
                if (!entity.points) entity.points = [{}, {}, {}, {}];
                if (code === 10) entity.points[0].x = parseFloat(value);
                else if (code === 20) entity.points[0].y = parseFloat(value);
                else if (code === 11) entity.points[1].x = parseFloat(value);
                else if (code === 21) entity.points[1].y = parseFloat(value);
                else if (code === 12) entity.points[2].x = parseFloat(value);
                else if (code === 22) entity.points[2].y = parseFloat(value);
                else if (code === 13) entity.points[3].x = parseFloat(value);
                else if (code === 23) entity.points[3].y = parseFloat(value);
                break;
        }
    }

    /**
     * Update bounding box based on entity
     */
    _updateBounds(entity) {
        const points = this._getEntityPoints(entity);
        for (const point of points) {
            if (point.x !== undefined) {
                this.bounds.minX = Math.min(this.bounds.minX, point.x);
                this.bounds.maxX = Math.max(this.bounds.maxX, point.x);
            }
            if (point.y !== undefined) {
                this.bounds.minY = Math.min(this.bounds.minY, point.y);
                this.bounds.maxY = Math.max(this.bounds.maxY, point.y);
            }
        }
    }

    /**
     * Get all coordinate points from an entity
     */
    _getEntityPoints(entity) {
        const points = [];

        switch (entity.type) {
            case 'LINE':
                points.push({ x: entity.x1, y: entity.y1 });
                points.push({ x: entity.x2, y: entity.y2 });
                break;
            case 'CIRCLE':
            case 'ARC':
                points.push({ x: entity.x - entity.radius, y: entity.y - entity.radius });
                points.push({ x: entity.x + entity.radius, y: entity.y + entity.radius });
                break;
            case 'LWPOLYLINE':
            case 'POLYLINE':
                if (entity.vertices) {
                    points.push(...entity.vertices);
                }
                break;
            default:
                if (entity.x !== undefined && entity.y !== undefined) {
                    points.push({ x: entity.x, y: entity.y });
                }
        }

        return points;
    }
}

/**
 * Main DWG/DXF Parser class
 */
class DwgParser {
    constructor(options = {}) {
        this.options = {
            wasmPath: options.wasmPath || '/js/libredwg.wasm',
            enableDwg: options.enableDwg ?? true,
            ...options,
        };
        this.dxfParser = new DxfParser();
        this.wasmModule = null;
        this.wasmLoaded = false;
    }

    /**
     * Parse a file (auto-detects format from extension or content)
     * @param {File|Blob|string} input - File, Blob, or string content
     * @param {string} filename - Filename (for format detection)
     * @returns {Promise<Object>} Parsed data
     */
    async parse(input, filename = '') {
        const ext = filename.toLowerCase().split('.').pop();
        
        if (input instanceof File) {
            filename = input.name;
            const content = await this._readFile(input);
            return this._parseContent(content, filename);
        } else if (input instanceof Blob) {
            const content = await this._readBlob(input);
            return this._parseContent(content, filename);
        } else if (typeof input === 'string') {
            return this._parseContent(input, filename);
        }

        throw new Error('Invalid input type. Expected File, Blob, or string.');
    }

    /**
     * Parse content based on detected format
     */
    async _parseContent(content, filename) {
        const ext = filename.toLowerCase().split('.').pop();
        const isDwg = ext === 'dwg' || this._isDwgBinary(content);
        
        if (isDwg) {
            return this._parseDwg(content);
        } else {
            return this._parseDxf(content);
        }
    }

    /**
     * Parse DXF content
     */
    _parseDxf(content) {
        // Ensure content is string
        const textContent = typeof content === 'string' 
            ? content 
            : new TextDecoder().decode(content);
        
        return {
            format: 'DXF',
            ...this.dxfParser.parse(textContent),
        };
    }

    /**
     * Parse DWG content (requires WebAssembly module)
     */
    async _parseDwg(content) {
        if (!this.options.enableDwg) {
            throw new Error('DWG parsing is disabled. Enable it in options or use server-side parsing.');
        }

        // Try to load WASM module if not already loaded
        if (!this.wasmLoaded) {
            await this._loadWasmModule();
        }

        if (!this.wasmModule) {
            // Fallback: return error with suggestion
            return {
                format: 'DWG',
                error: 'DWG WebAssembly module not available',
                suggestion: 'Use server-side parsing via /api/dwg/parse endpoint',
                serverParseEndpoint: '/api/dwg/parse',
            };
        }

        // Parse using WASM module
        return this._parseWithWasm(content);
    }

    /**
     * Load WebAssembly module for DWG parsing
     */
    async _loadWasmModule() {
        try {
            // Check if libredwg-web is available
            if (typeof window !== 'undefined' && window.LibreDwg) {
                this.wasmModule = window.LibreDwg;
                this.wasmLoaded = true;
                return;
            }

            // Try to dynamically import
            const response = await fetch(this.options.wasmPath);
            if (response.ok) {
                const wasmModule = await WebAssembly.instantiateStreaming(response);
                this.wasmModule = wasmModule.instance.exports;
                this.wasmLoaded = true;
            }
        } catch (error) {
            console.warn('Could not load DWG WebAssembly module:', error.message);
            this.wasmLoaded = true; // Mark as attempted
        }
    }

    /**
     * Parse DWG using WebAssembly module
     */
    async _parseWithWasm(content) {
        // This would use the actual libredwg-web API
        // For now, return a placeholder
        return {
            format: 'DWG',
            error: 'Native DWG parsing not yet implemented',
            suggestion: 'Convert DWG to DXF using AutoCAD or use server-side parsing',
        };
    }

    /**
     * Check if content appears to be DWG binary format
     */
    _isDwgBinary(content) {
        if (typeof content === 'string') {
            // DWG files start with "AC10" signature
            return content.startsWith('AC10') || content.startsWith('AC21');
        }
        if (content instanceof ArrayBuffer || content instanceof Uint8Array) {
            const bytes = content instanceof ArrayBuffer 
                ? new Uint8Array(content) 
                : content;
            // Check for DWG magic bytes
            return bytes[0] === 0x41 && bytes[1] === 0x43; // "AC"
        }
        return false;
    }

    /**
     * Read File as text or ArrayBuffer
     */
    async _readFile(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            const ext = file.name.toLowerCase().split('.').pop();
            
            reader.onload = () => resolve(reader.result);
            reader.onerror = () => reject(reader.error);
            
            if (ext === 'dwg') {
                reader.readAsArrayBuffer(file);
            } else {
                reader.readAsText(file);
            }
        });
    }

    /**
     * Read Blob as text or ArrayBuffer
     */
    async _readBlob(blob) {
        return blob.text();
    }

    /**
     * Convert parsed data to SVG
     * @param {Object} data - Parsed DXF/DWG data
     * @param {Object} options - Rendering options
     * @returns {string} SVG string
     */
    toSVG(data, options = {}) {
        const {
            width = 800,
            height = 600,
            padding = 20,
            strokeColor = '#000000',
            strokeWidth = 1,
            backgroundColor = '#ffffff',
            layerColors = {},
        } = options;

        const { bounds, entities } = data;
        
        // Calculate scale to fit
        const dataWidth = bounds.maxX - bounds.minX;
        const dataHeight = bounds.maxY - bounds.minY;
        const availableWidth = width - padding * 2;
        const availableHeight = height - padding * 2;
        const scale = Math.min(availableWidth / dataWidth, availableHeight / dataHeight);

        // Transform function
        const transform = (x, y) => ({
            x: padding + (x - bounds.minX) * scale,
            y: height - padding - (y - bounds.minY) * scale, // Flip Y axis
        });

        // Build SVG elements
        const svgElements = entities.map(entity => {
            const color = layerColors[entity.layer] || strokeColor;
            return this._entityToSVG(entity, transform, { color, strokeWidth });
        }).filter(Boolean);

        return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" 
     width="${width}" height="${height}" 
     viewBox="0 0 ${width} ${height}"
     style="background-color: ${backgroundColor}">
  <g stroke="${strokeColor}" stroke-width="${strokeWidth}" fill="none">
    ${svgElements.join('\n    ')}
  </g>
</svg>`;
    }

    /**
     * Convert a single entity to SVG element
     */
    _entityToSVG(entity, transform, style) {
        const { color, strokeWidth } = style;

        switch (entity.type) {
            case 'LINE': {
                const p1 = transform(entity.x1, entity.y1);
                const p2 = transform(entity.x2, entity.y2);
                return `<line x1="${p1.x}" y1="${p1.y}" x2="${p2.x}" y2="${p2.y}" stroke="${color}"/>`;
            }

            case 'CIRCLE': {
                const center = transform(entity.x, entity.y);
                const radius = entity.radius * Math.abs(transform(1, 0).x - transform(0, 0).x);
                return `<circle cx="${center.x}" cy="${center.y}" r="${radius}" stroke="${color}"/>`;
            }

            case 'ARC': {
                const center = transform(entity.x, entity.y);
                const radius = entity.radius * Math.abs(transform(1, 0).x - transform(0, 0).x);
                const startAngle = entity.startAngle * Math.PI / 180;
                const endAngle = entity.endAngle * Math.PI / 180;
                
                const startX = center.x + radius * Math.cos(startAngle);
                const startY = center.y - radius * Math.sin(startAngle);
                const endX = center.x + radius * Math.cos(endAngle);
                const endY = center.y - radius * Math.sin(endAngle);
                
                const largeArc = Math.abs(endAngle - startAngle) > Math.PI ? 1 : 0;
                
                return `<path d="M ${startX} ${startY} A ${radius} ${radius} 0 ${largeArc} 0 ${endX} ${endY}" stroke="${color}"/>`;
            }

            case 'LWPOLYLINE':
            case 'POLYLINE': {
                if (!entity.vertices || entity.vertices.length < 2) return null;
                
                const points = entity.vertices.map(v => {
                    const p = transform(v.x, v.y);
                    return `${p.x},${p.y}`;
                }).join(' ');
                
                const tag = entity.closed ? 'polygon' : 'polyline';
                return `<${tag} points="${points}" stroke="${color}"/>`;
            }

            case 'TEXT':
            case 'MTEXT': {
                const pos = transform(entity.x, entity.y);
                const fontSize = (entity.height || 1) * Math.abs(transform(1, 0).x - transform(0, 0).x);
                const rotation = entity.rotation || 0;
                return `<text x="${pos.x}" y="${pos.y}" font-size="${fontSize}" 
                        transform="rotate(${-rotation} ${pos.x} ${pos.y})" 
                        fill="${color}" stroke="none">${this._escapeXml(entity.text || '')}</text>`;
            }

            case 'ELLIPSE': {
                const center = transform(entity.x, entity.y);
                const scaleX = Math.abs(transform(1, 0).x - transform(0, 0).x);
                const majorLen = Math.sqrt(entity.majorAxisX ** 2 + entity.majorAxisY ** 2) * scaleX;
                const minorLen = majorLen * entity.ratio;
                const rotation = Math.atan2(entity.majorAxisY, entity.majorAxisX) * 180 / Math.PI;
                
                return `<ellipse cx="${center.x}" cy="${center.y}" 
                        rx="${majorLen}" ry="${minorLen}" 
                        transform="rotate(${-rotation} ${center.x} ${center.y})" 
                        stroke="${color}"/>`;
            }

            case 'POINT': {
                const pos = transform(entity.x, entity.y);
                return `<circle cx="${pos.x}" cy="${pos.y}" r="2" fill="${color}"/>`;
            }

            default:
                return null;
        }
    }

    /**
     * Escape XML special characters
     */
    _escapeXml(str) {
        return str.replace(/[<>&'"]/g, c => ({
            '<': '&lt;',
            '>': '&gt;',
            '&': '&amp;',
            "'": '&apos;',
            '"': '&quot;',
        }[c]));
    }

    /**
     * Convert parsed data to GeoJSON
     * @param {Object} data - Parsed DXF/DWG data
     * @returns {Object} GeoJSON FeatureCollection
     */
    toGeoJSON(data) {
        const features = data.entities.map(entity => {
            const geometry = this._entityToGeoJSONGeometry(entity);
            if (!geometry) return null;

            return {
                type: 'Feature',
                properties: {
                    layer: entity.layer,
                    color: entity.color,
                    type: entity.type,
                    handle: entity.handle,
                },
                geometry,
            };
        }).filter(Boolean);

        return {
            type: 'FeatureCollection',
            features,
            crs: {
                type: 'name',
                properties: {
                    name: 'urn:ogc:def:crs:OGC:1.3:CRS84',
                },
            },
        };
    }

    /**
     * Convert entity to GeoJSON geometry
     */
    _entityToGeoJSONGeometry(entity) {
        switch (entity.type) {
            case 'POINT':
                return {
                    type: 'Point',
                    coordinates: [entity.x, entity.y],
                };

            case 'LINE':
                return {
                    type: 'LineString',
                    coordinates: [
                        [entity.x1, entity.y1],
                        [entity.x2, entity.y2],
                    ],
                };

            case 'LWPOLYLINE':
            case 'POLYLINE':
                if (!entity.vertices || entity.vertices.length < 2) return null;
                const coords = entity.vertices.map(v => [v.x, v.y]);
                if (entity.closed) {
                    coords.push(coords[0]);
                    return {
                        type: 'Polygon',
                        coordinates: [coords],
                    };
                }
                return {
                    type: 'LineString',
                    coordinates: coords,
                };

            case 'CIRCLE':
                // Approximate circle as polygon
                const circleCoords = [];
                for (let i = 0; i <= 36; i++) {
                    const angle = (i * 10) * Math.PI / 180;
                    circleCoords.push([
                        entity.x + entity.radius * Math.cos(angle),
                        entity.y + entity.radius * Math.sin(angle),
                    ]);
                }
                return {
                    type: 'Polygon',
                    coordinates: [circleCoords],
                };

            default:
                return null;
        }
    }

    /**
     * Get layer statistics
     * @param {Object} data - Parsed data
     * @returns {Object} Layer statistics
     */
    getLayerStats(data) {
        const stats = {};
        
        for (const entity of data.entities) {
            const layer = entity.layer || '0';
            if (!stats[layer]) {
                stats[layer] = {
                    name: layer,
                    entityCount: 0,
                    types: {},
                    color: data.layers[layer]?.color,
                };
            }
            stats[layer].entityCount++;
            stats[layer].types[entity.type] = (stats[layer].types[entity.type] || 0) + 1;
        }

        return stats;
    }

    /**
     * Filter entities by layer
     * @param {Object} data - Parsed data
     * @param {string[]} layers - Layer names to include
     * @returns {Object} Filtered data
     */
    filterByLayers(data, layers) {
        const layerSet = new Set(layers.map(l => l.toLowerCase()));
        
        return {
            ...data,
            entities: data.entities.filter(e => 
                layerSet.has((e.layer || '0').toLowerCase())
            ),
        };
    }

    /**
     * Filter entities by type
     * @param {Object} data - Parsed data
     * @param {string[]} types - Entity types to include
     * @returns {Object} Filtered data
     */
    filterByTypes(data, types) {
        const typeSet = new Set(types.map(t => t.toUpperCase()));
        
        return {
            ...data,
            entities: data.entities.filter(e => typeSet.has(e.type)),
        };
    }
}

// Export for different module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { DwgParser, DxfParser, EntityTypes };
} else if (typeof window !== 'undefined') {
    window.DwgParser = DwgParser;
    window.DxfParser = DxfParser;
    window.EntityTypes = EntityTypes;
}

export { DwgParser, DxfParser, EntityTypes };
export default DwgParser;
