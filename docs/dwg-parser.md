# DWG/DXF Parser Documentation

A comprehensive CAD file parsing system for handling AutoCAD DWG and DXF files in the AureusERP application.

## Overview

The DWG Parser provides both client-side (JavaScript) and server-side (PHP) parsing capabilities for DWG and DXF CAD files. It supports:

- **DXF Files**: Full native parsing in both JavaScript and PHP
- **DWG Files**: Server-side parsing via LibreDWG or ODA File Converter (optional)
- **Output Formats**: JSON, SVG, GeoJSON
- **Features**: Layer filtering, entity type filtering, coordinate transformation

## Installation

### JavaScript (Client-Side)

The JavaScript parser is automatically bundled with Vite. Import it in your code:

```javascript
import { DwgParser } from '@/js/dwg-parser.js';

// Or use from window if loaded via script tag
const parser = new DwgParser();
```

### PHP (Server-Side)

The PHP service is automatically available via Laravel's service container:

```php
use App\Services\DwgService;

$dwgService = app(DwgService::class);
// or inject via constructor
```

### Optional: LibreDWG for Full DWG Support

For full DWG file support, install LibreDWG:

**macOS (Homebrew):**
```bash
brew install libredwg
```

**Ubuntu/Debian:**
```bash
sudo apt-get install libredwg-tools
```

**From source:**
```bash
git clone https://github.com/LibreDWG/libredwg.git
cd libredwg
./configure && make && sudo make install
```

## API Reference

### JavaScript API

#### DwgParser Class

```javascript
const parser = new DwgParser(options);
```

**Options:**
- `wasmPath` (string): Path to WebAssembly module for DWG parsing
- `enableDwg` (boolean): Enable DWG format support (default: true)

#### Methods

##### `parse(input, filename)`
Parse a DWG/DXF file.

```javascript
// From File input
const fileInput = document.querySelector('input[type="file"]');
const data = await parser.parse(fileInput.files[0]);

// From string content
const dxfContent = '0\nSECTION\n2\nHEADER\n...';
const data = await parser.parse(dxfContent, 'drawing.dxf');
```

**Returns:**
```javascript
{
  format: 'DXF', // or 'DWG'
  header: { /* DXF header variables */ },
  layers: { 
    'LayerName': { name, color, frozen, locked }
  },
  entities: [
    { type: 'LINE', layer: '0', x1, y1, x2, y2 },
    { type: 'CIRCLE', layer: '0', x, y, radius },
    // ...
  ],
  bounds: { minX, minY, maxX, maxY },
  stats: { entityCount, layerCount, blockCount }
}
```

##### `toSVG(data, options)`
Convert parsed data to SVG string.

```javascript
const svg = parser.toSVG(data, {
  width: 800,
  height: 600,
  padding: 20,
  strokeColor: '#000000',
  strokeWidth: 1,
  backgroundColor: '#ffffff',
  layerColors: { 'Layer1': '#ff0000' }
});
```

##### `toGeoJSON(data)`
Convert parsed data to GeoJSON FeatureCollection.

```javascript
const geoJson = parser.toGeoJSON(data);
// Returns standard GeoJSON FeatureCollection
```

##### `filterByLayers(data, layers)`
Filter entities by layer names.

```javascript
const filtered = parser.filterByLayers(data, ['Walls', 'Doors']);
```

##### `filterByTypes(data, types)`
Filter entities by type.

```javascript
const linesOnly = parser.filterByTypes(data, ['LINE', 'POLYLINE']);
```

##### `getLayerStats(data)`
Get statistics per layer.

```javascript
const stats = parser.getLayerStats(data);
// { 'Layer1': { name, entityCount, types: { LINE: 5, CIRCLE: 2 } } }
```

### PHP API

#### DwgService Class

```php
use App\Services\DwgService;

$service = app(DwgService::class);
```

#### Methods

##### `parse(string $filePath, array $options = [])`
Parse a DWG or DXF file.

```php
$data = $service->parse('/path/to/drawing.dxf');
```

##### `parseDxf(string $filePath, array $options = [])`
Parse specifically a DXF file.

```php
$data = $service->parseDxf('/path/to/drawing.dxf');
```

##### `parseDwg(string $filePath, array $options = [])`
Parse specifically a DWG file (requires LibreDWG or ODA).

```php
$data = $service->parseDwg('/path/to/drawing.dwg');
```

##### `toSvg(array $data, array $options = [])`
Convert parsed data to SVG.

```php
$svg = $service->toSvg($data, [
    'width' => 800,
    'height' => 600,
    'strokeColor' => '#000000',
]);
```

##### `toGeoJson(array $data)`
Convert parsed data to GeoJSON.

```php
$geoJson = $service->toGeoJson($data);
```

##### `getLayerStats(array $data)`
Get layer statistics.

```php
$stats = $service->getLayerStats($data);
```

##### `filterByLayers(array $data, array $layers)`
Filter by layer names.

```php
$filtered = $service->filterByLayers($data, ['Walls', 'Doors']);
```

##### `filterByTypes(array $data, array $types)`
Filter by entity types.

```php
$filtered = $service->filterByTypes($data, ['LINE', 'CIRCLE']);
```

##### `isLibreDwgAvailable()`
Check if LibreDWG tools are installed.

```php
if ($service->isLibreDwgAvailable()) {
    // Full DWG support available
}
```

### REST API Endpoints

All endpoints require authentication (`auth:web` middleware).

#### POST `/api/dwg/parse`
Parse an uploaded DWG/DXF file.

**Request:**
```
Content-Type: multipart/form-data

file: (binary)
output_format: json|svg|geojson (optional, default: json)
width: integer (optional, for SVG)
height: integer (optional, for SVG)
layers: array (optional, filter by layers)
types: array (optional, filter by types)
```

**Response:**
```json
{
  "success": true,
  "data": {
    "format": "DXF",
    "entities": [...],
    "layers": {...},
    "bounds": {...},
    "stats": {...}
  }
}
```

#### POST `/api/dwg/parse-path`
Parse a file from storage path.

**Request:**
```json
{
  "path": "floor-plans/drawing.dxf",
  "disk": "local",
  "output_format": "json"
}
```

#### POST `/api/dwg/to-svg`
Convert uploaded file directly to SVG.

**Request:**
```
Content-Type: multipart/form-data

file: (binary)
width: 800
height: 600
stroke_color: #000000
background_color: #ffffff
```

#### POST `/api/dwg/layer-stats`
Get layer statistics from a file.

#### GET `/api/dwg/capabilities`
Check parsing capabilities.

**Response:**
```json
{
  "success": true,
  "capabilities": {
    "dxf_native": true,
    "dwg_libredwg": false,
    "dwg_oda": false,
    "supported_formats": ["dxf", "dwg"],
    "output_formats": ["json", "svg", "geojson"],
    "supported_entities": ["LINE", "CIRCLE", ...]
  }
}
```

## Livewire Component

A ready-to-use Livewire component is available for file upload and visualization:

```blade
<livewire:dwg-viewer />
```

Features:
- Drag & drop file upload
- Real-time parsing and preview
- Layer toggle controls
- SVG/Data/GeoJSON view modes
- Export to SVG and GeoJSON
- Customizable colors and dimensions

## Supported Entity Types

| Type | DXF Support | DWG Support | SVG Output | GeoJSON Output |
|------|-------------|-------------|------------|----------------|
| LINE | ✅ | ✅ | ✅ | ✅ |
| CIRCLE | ✅ | ✅ | ✅ | ✅ (polygon) |
| ARC | ✅ | ✅ | ✅ | ❌ |
| POLYLINE | ✅ | ✅ | ✅ | ✅ |
| LWPOLYLINE | ✅ | ✅ | ✅ | ✅ |
| ELLIPSE | ✅ | ✅ | ✅ | ❌ |
| SPLINE | ✅ | ⚠️ | ❌ | ❌ |
| TEXT | ✅ | ✅ | ✅ | ❌ |
| MTEXT | ✅ | ✅ | ✅ | ❌ |
| DIMENSION | ✅ | ⚠️ | ❌ | ❌ |
| INSERT | ✅ | ⚠️ | ❌ | ❌ |
| POINT | ✅ | ✅ | ✅ | ✅ |
| SOLID | ✅ | ⚠️ | ❌ | ❌ |
| HATCH | ✅ | ⚠️ | ❌ | ❌ |

Legend: ✅ Full support | ⚠️ Partial support | ❌ Not supported

## Usage Examples

### Basic File Parsing (JavaScript)

```javascript
import DwgParser from '@/js/dwg-parser.js';

async function handleFileUpload(event) {
    const file = event.target.files[0];
    const parser = new DwgParser();
    
    try {
        const data = await parser.parse(file);
        console.log(`Parsed ${data.stats.entityCount} entities`);
        
        // Generate SVG preview
        const svg = parser.toSVG(data, { width: 800, height: 600 });
        document.getElementById('preview').innerHTML = svg;
        
        // Export to GeoJSON for mapping
        const geoJson = parser.toGeoJSON(data);
        // Use with Leaflet, Mapbox, etc.
    } catch (error) {
        console.error('Parse error:', error);
    }
}
```

### Server-Side Processing (PHP)

```php
use App\Services\DwgService;
use Illuminate\Support\Facades\Storage;

class FloorPlanController extends Controller
{
    public function __construct(private DwgService $dwgService) {}
    
    public function import(Request $request)
    {
        $file = $request->file('floor_plan');
        $path = $file->store('floor-plans');
        
        // Parse the file
        $data = $this->dwgService->parse(Storage::path($path));
        
        // Extract walls layer only
        $walls = $this->dwgService->filterByLayers($data, ['Walls', 'WALLS']);
        
        // Generate thumbnail SVG
        $thumbnail = $this->dwgService->toSvg($data, [
            'width' => 200,
            'height' => 150,
        ]);
        
        // Store for later use
        Storage::put("floor-plans/{$file->hashName()}.json", json_encode($data));
        Storage::put("floor-plans/{$file->hashName()}.svg", $thumbnail);
        
        return response()->json([
            'success' => true,
            'stats' => $data['stats'],
            'layers' => array_keys($data['layers']),
        ]);
    }
}
```

### Alpine.js Integration

```blade
<div x-data="dwgViewer()" class="dwg-container">
    <input type="file" @change="parseFile($event)" accept=".dwg,.dxf">
    
    <div x-show="loading">Parsing...</div>
    
    <template x-if="parsedData">
        <div>
            <p>Entities: <span x-text="parsedData.stats.entityCount"></span></p>
            <div x-html="svgContent"></div>
        </div>
    </template>
</div>

<script type="module">
import DwgParser from '@/js/dwg-parser.js';

Alpine.data('dwgViewer', () => ({
    parser: new DwgParser(),
    loading: false,
    parsedData: null,
    svgContent: '',
    
    async parseFile(event) {
        this.loading = true;
        try {
            this.parsedData = await this.parser.parse(event.target.files[0]);
            this.svgContent = this.parser.toSVG(this.parsedData);
        } catch (e) {
            console.error(e);
        }
        this.loading = false;
    }
}));
</script>
```

## Configuration

Add to `config/services.php` for LibreDWG path customization:

```php
'libredwg' => [
    'path' => env('LIBREDWG_PATH'), // e.g., '/usr/local/bin'
],
```

## Error Handling

The parser handles various error conditions:

```javascript
try {
    const data = await parser.parse(file);
} catch (error) {
    if (error.message.includes('Unsupported')) {
        // Format not supported
    } else if (error.message.includes('not found')) {
        // File not found
    } else if (error.message.includes('WebAssembly')) {
        // WASM module not available for DWG
        // Suggest using server-side parsing
    }
}
```

## Performance Considerations

1. **Large Files**: For files > 10MB, consider server-side parsing
2. **Complex Drawings**: Files with > 100k entities may be slow in the browser
3. **Memory**: Parsed data is held in memory; release when done
4. **Caching**: Consider caching parsed results for repeated access

## Future Enhancements

- [ ] WebAssembly DWG parsing (libredwg-web integration)
- [ ] 3D entity support (3DFACE, MESH, etc.)
- [ ] Block expansion (INSERT → actual geometry)
- [ ] Dimension text extraction
- [ ] Hatch pattern rendering
- [ ] Paper space vs model space handling
