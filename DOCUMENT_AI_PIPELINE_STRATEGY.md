# Document AI Pipeline Strategy for TCS Woodwork
## Hierarchical PDF Extraction: Plans → Production

---

## Pipeline Overview

```
PDF Upload (8 pages)
    ↓
┌─────────────────────────────────────────────────────────┐
│ STAGE 1: Document Structure Analysis                   │
│ Processor: Layout Parser (OCR_PROCESSOR)              │
│ Output: Page classification, text extraction           │
└─────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────┐
│ STAGE 2: Cover Page Extraction (Page 1)               │
│ Processor: Custom Extractor "TCS-Cover-Page"          │
│ Schema: Projects + Documents tables                    │
└─────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────┐
│ STAGE 3: Plan View Analysis (Pages 2-3)               │
│ Processor: Custom Extractor "TCS-Plan-View"           │
│ Schema: Locations table                                │
└─────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────┐
│ STAGE 4: Elevation Parsing (Pages 4-8)                │
│ Processor: Custom Extractor "TCS-Elevation"           │
│ Schema: CabinetRuns + Components + SubComponents      │
└─────────────────────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────────────────────┐
│ STAGE 5: Hardware & Specifications (All Pages)        │
│ Processor: Form Parser + OCR                          │
│ Schema: Hardware + Component_Hardware                  │
└─────────────────────────────────────────────────────────┘
    ↓
Database Storage (MySQL) + Cut File Generation
```

---

## Stage 1: Layout Parser (Foundation)

**Processor Type**: `OCR_PROCESSOR` (Already created: `1ce0abf59ba3ae89`)

**Purpose**:
- Extract all text from PDF
- Identify page boundaries
- Detect tables and structured content
- Baseline for other processors

**Configuration**:
```json
{
  "processor_id": "1ce0abf59ba3ae89",
  "features": {
    "enable_image_quality_scores": true,
    "enable_automatic_rotation": true,
    "enable_table_detection": true
  }
}
```

**Output Structure**:
```json
{
  "pages": [
    {
      "page_number": 1,
      "text": "25 Friendship Lane, Nantucket...",
      "tables": [],
      "confidence": 0.95
    }
  ]
}
```

---

## Stage 2: Cover Page Extractor

**Processor Type**: `CUSTOM_EXTRACTION_PROCESSOR`

**Target Page**: Page 1 only

**Fields to Extract** (Maps to Projects + Documents tables):

### Training Schema:
```json
{
  "schema_name": "TCS-Cover-Page-Schema-v1",
  "fields": [
    {
      "field_name": "project_name",
      "field_type": "text",
      "example": "Renovations at: 25 Friendship",
      "required": true
    },
    {
      "field_name": "project_address",
      "field_type": "text",
      "example": "25 Friendship Nantucket, MA 02554",
      "required": true
    },
    {
      "field_name": "owner_name",
      "field_type": "text",
      "example": "Owner: Jeremy Trottier",
      "required": true
    },
    {
      "field_name": "owner_phone",
      "field_type": "text",
      "example": "Phone: 508-332-8671",
      "required": false
    },
    {
      "field_name": "owner_email",
      "field_type": "text",
      "example": "Email: trottierfinewoodworking@gmail.com",
      "required": false
    },
    {
      "field_name": "project_type",
      "field_type": "text",
      "example": "Kitchen Cabinetry",
      "required": true
    },
    {
      "field_name": "revision_number",
      "field_type": "number",
      "example": "Revision 4",
      "required": true
    },
    {
      "field_name": "revision_date",
      "field_type": "date",
      "example": "9/27/25",
      "required": true
    },
    {
      "field_name": "sheet_title",
      "field_type": "text",
      "example": "Cover Page",
      "required": true
    },
    {
      "field_name": "tier_2_linear_feet",
      "field_type": "number",
      "example": "Tier 2 Cabinetry: 11.5 LF",
      "required": false
    },
    {
      "field_name": "tier_4_linear_feet",
      "field_type": "number",
      "example": "Tier 4 Cabinetry: 35.25 LF",
      "required": false
    },
    {
      "field_name": "approved_by",
      "field_type": "text",
      "example": "Approved By: [signature field]",
      "required": false
    },
    {
      "field_name": "drawn_by",
      "field_type": "text",
      "example": "Drawn By: J. Garcia",
      "required": false
    }
  ]
}
```

### Training Process:
1. Upload 10-20 TCS cover pages to Document AI Workbench
2. For each sample, draw bounding boxes around:
   - Project address block
   - Owner information block
   - Revision history table
   - Linear feet summary section
3. Label each box with field name from schema
4. Train processor (~1-2 hours automated)

### Expected Accuracy: **95%+** after training

### Database Mapping:
```php
// app/Services/CoverPageExtractor.php
public function mapToDatabase($extractedData, PdfDocument $doc)
{
    // Create/Update Project
    $project = Project::updateOrCreate(
        ['address' => $extractedData['project_address']],
        [
            'name' => $extractedData['project_name'],
            'type' => $extractedData['project_type'],
        ]
    );

    // Create Document record
    $document = Document::create([
        'project_id' => $project->id,
        'pdf_document_id' => $doc->id,
        'revision_number' => $extractedData['revision_number'],
        'revision_date' => Carbon::parse($extractedData['revision_date']),
        'tier_2_linear_feet' => $extractedData['tier_2_linear_feet'],
        'tier_4_linear_feet' => $extractedData['tier_4_linear_feet'],
    ]);

    // Create Customer record
    Customer::updateOrCreate(
        ['email' => $extractedData['owner_email']],
        [
            'name' => $extractedData['owner_name'],
            'phone' => $extractedData['owner_phone'],
        ]
    );

    return $document;
}
```

---

## Stage 3: Plan View Extractor

**Processor Type**: `CUSTOM_EXTRACTION_PROCESSOR`

**Target Pages**: Pages 2-3 (plan views)

**Purpose**: Extract room locations and spatial relationships

### Training Schema:
```json
{
  "schema_name": "TCS-Plan-View-Schema-v1",
  "fields": [
    {
      "field_name": "locations",
      "field_type": "nested_array",
      "children": [
        {
          "field_name": "location_name",
          "field_type": "text",
          "examples": ["Island", "Sink Wall", "Fridge Wall", "Pantry"]
        },
        {
          "field_name": "location_type",
          "field_type": "enum",
          "options": ["freestanding", "wall_mounted", "corner"],
          "examples": ["Island = freestanding", "Sink Wall = wall_mounted"]
        },
        {
          "field_name": "wall_number",
          "field_type": "number",
          "examples": ["Wall 1", "Wall 2", "Wall 3"]
        },
        {
          "field_name": "contains_appliances",
          "field_type": "text_array",
          "examples": ["Sink, Range", "Refrigerator, Pantry"]
        },
        {
          "field_name": "seating_capacity",
          "field_type": "number",
          "examples": ["Island with seating for 3"]
        }
      ]
    }
  ]
}
```

### Training Guidance:
- **Label entire plan view** as a single region
- Draw bounding boxes around:
  - Island outline → label as "Island" location
  - Each wall run → label with wall number
  - Appliance symbols → label with appliance type
  - Dimension lines → label with linear feet

### Extraction Example (Friendship Lane):
```json
{
  "locations": [
    {
      "location_name": "Island",
      "location_type": "freestanding",
      "wall_number": null,
      "contains_appliances": [],
      "seating_capacity": 3,
      "position_notes": "Center, workflow triangle"
    },
    {
      "location_name": "Sink Wall",
      "location_type": "wall_mounted",
      "wall_number": 1,
      "contains_appliances": ["Sink", "Range"],
      "runs": ["Base L-Shaped", "Upper Cabinets"]
    },
    {
      "location_name": "Fridge Wall",
      "location_type": "wall_mounted",
      "wall_number": 2,
      "contains_appliances": ["SubZero Refrigerator"],
      "runs": ["Tall Pantry"]
    },
    {
      "location_name": "Pantry Wall",
      "location_type": "wall_mounted",
      "wall_number": 3,
      "contains_appliances": [],
      "runs": ["Tall Pantry"]
    }
  ]
}
```

### Database Mapping:
```php
public function mapLocations($extractedData, $documentId)
{
    foreach ($extractedData['locations'] as $locationData) {
        $location = Location::create([
            'document_id' => $documentId,
            'name' => $locationData['location_name'],
            'type' => $locationData['location_type'],
            'wall_number' => $locationData['wall_number'],
            'seating_capacity' => $locationData['seating_capacity'] ?? null,
        ]);

        // Link appliances
        foreach ($locationData['contains_appliances'] as $appliance) {
            LocationAppliance::create([
                'location_id' => $location->id,
                'appliance_type' => $appliance,
            ]);
        }
    }
}
```

---

## Stage 4: Elevation Parser (Most Complex)

**Processor Type**: `CUSTOM_EXTRACTION_PROCESSOR`

**Target Pages**: Pages 4-8 (elevations and sections)

**Purpose**: Extract cabinet runs, components, and subcomponents

### Training Schema:
```json
{
  "schema_name": "TCS-Elevation-Schema-v1",
  "fields": [
    {
      "field_name": "view_title",
      "field_type": "text",
      "examples": ["Sink Wall Elevation", "Fridge Wall / Pantry Elevation"]
    },
    {
      "field_name": "cabinet_runs",
      "field_type": "nested_array",
      "children": [
        {
          "field_name": "run_type",
          "field_type": "enum",
          "options": ["Base", "Upper", "Tall", "Floating Shelves"]
        },
        {
          "field_name": "components",
          "field_type": "nested_array",
          "children": [
            {
              "field_name": "component_type",
              "field_type": "text",
              "examples": ["Sink Base", "Corner Drawer Unit", "Glass Door Upper"]
            },
            {
              "field_name": "width_inches",
              "field_type": "number",
              "examples": ["36", "24", "18"]
            },
            {
              "field_name": "appliance_integration",
              "field_type": "text",
              "examples": ["SubZero Refrigerator", "Panel-ready dishwasher"]
            },
            {
              "field_name": "door_style",
              "field_type": "text",
              "examples": ["Large panel doors", "Glass door", "Shaker"]
            },
            {
              "field_name": "drawer_config",
              "field_type": "text",
              "examples": ["Multiple drawers", "Deep drawers for pots"]
            },
            {
              "field_name": "hardware_notes",
              "field_type": "text",
              "examples": ["Lemans #4930140150 corner storage"]
            },
            {
              "field_name": "special_features",
              "field_type": "text_array",
              "examples": ["Utensil divider", "Machined drain grooves"]
            }
          ]
        }
      ]
    }
  ]
}
```

### Training Guidance:

**For Sink Wall Elevation (Page 5):**
1. Draw box around "Tall Pantry/Appliance Cabinets" section:
   - Label: `run_type = "Tall"`
   - Label: `component_type = "Tall Pantry"`
   - Label: `quantity = 2`
   - Label: `appliance_integration = "Integrated panel-ready refrigerator/freezer"`

2. Draw box around upper cabinet section:
   - Label: `run_type = "Upper"`
   - For each cabinet:
     - Floating shelves → `component_type = "Floating Shelf"`
     - Glass cabinet → `component_type = "Glass Door Upper"`

3. Draw box around base cabinet section:
   - Label: `run_type = "Base"`
   - For each cabinet:
     - Sink → `component_type = "Sink Base"`
     - Drawers → `component_type = "Drawer Bank"`, `drawer_config = "Multiple drawers"`
     - Dishwasher → `component_type = "Dishwasher Base"`, `appliance_integration = "Panel-ready dishwasher"`

### Extraction Example (Sink Wall Elevation):
```json
{
  "view_title": "Sink Wall Elevation",
  "page_number": 5,
  "cabinet_runs": [
    {
      "run_type": "Tall",
      "linear_feet": null,
      "components": [
        {
          "component_type": "Tall Pantry",
          "quantity": 2,
          "width_inches": null,
          "door_style": "Large panel doors",
          "appliance_integration": "Integrated panel-ready refrigerator/freezer or pantry",
          "position": "Far left"
        }
      ]
    },
    {
      "run_type": "Upper",
      "components": [
        {
          "component_type": "TV Mount",
          "width_inches": null,
          "special_features": ["Wall-mounted television"]
        },
        {
          "component_type": "Floating Shelf",
          "quantity": 2,
          "width_inches": null
        },
        {
          "component_type": "Glass Door Upper",
          "quantity": 1,
          "position": "Far right",
          "door_style": "Single glass door"
        }
      ]
    },
    {
      "run_type": "Base",
      "components": [
        {
          "component_type": "Sink Base",
          "width_inches": null,
          "appliance_integration": null
        },
        {
          "component_type": "Drawer Bank",
          "width_inches": null,
          "drawer_config": "Multiple drawers"
        },
        {
          "component_type": "Dishwasher Base",
          "width_inches": null,
          "appliance_integration": "Panel-ready dishwasher",
          "position": "Right of sink"
        }
      ]
    }
  ]
}
```

### Database Mapping:
```php
public function mapElevations($extractedData, $locationId)
{
    foreach ($extractedData['cabinet_runs'] as $runData) {
        $run = CabinetRun::create([
            'location_id' => $locationId,
            'run_type' => $runData['run_type'],
        ]);

        foreach ($runData['components'] as $componentData) {
            $component = Component::create([
                'run_id' => $run->id,
                'type' => $componentData['component_type'],
                'width_inches' => $componentData['width_inches'],
                'appliance_integration' => $componentData['appliance_integration'],
                'position' => $componentData['position'] ?? null,
            ]);

            // Create SubComponents (doors, drawers)
            if (isset($componentData['drawer_config'])) {
                SubComponent::create([
                    'component_id' => $component->id,
                    'type' => 'Drawer',
                    'description' => $componentData['drawer_config'],
                ]);
            }

            if (isset($componentData['door_style'])) {
                SubComponent::create([
                    'component_id' => $component->id,
                    'type' => 'Door',
                    'description' => $componentData['door_style'],
                ]);
            }
        }
    }
}
```

---

## Stage 5: Hardware & Specifications Extractor

**Processor Type**: `FORM_PARSER_PROCESSOR`

**Target**: All pages (text callouts)

**Purpose**: Extract specific hardware callouts and specifications

### Training Schema:
```json
{
  "schema_name": "TCS-Hardware-Schema-v1",
  "fields": [
    {
      "field_name": "hardware_items",
      "field_type": "nested_array",
      "children": [
        {
          "field_name": "item_name",
          "field_type": "text",
          "examples": ["Lemans corner storage unit", "Utensil divider"]
        },
        {
          "field_name": "model_number",
          "field_type": "text",
          "examples": ["#4930140150", "SubZero"]
        },
        {
          "field_name": "component_reference",
          "field_type": "text",
          "examples": ["Fridge Wall lower cabinet", "Island top drawer"]
        },
        {
          "field_name": "page_number",
          "field_type": "number"
        }
      ]
    },
    {
      "field_name": "material_specs",
      "field_type": "nested_array",
      "children": [
        {
          "field_name": "material_type",
          "field_type": "text",
          "examples": ["Soapstone", "Walnut", "White Oak"]
        },
        {
          "field_name": "application",
          "field_type": "text",
          "examples": ["Pantry countertop", "Sink countertop"]
        },
        {
          "field_name": "fabrication_note",
          "field_type": "text",
          "examples": ["Machined drain grooves", "TBD - leftover from kitchen"]
        }
      ]
    },
    {
      "field_name": "electrical_specs",
      "field_type": "nested_array",
      "children": [
        {
          "field_name": "location",
          "field_type": "text",
          "examples": ["Pantry", "Island"]
        },
        {
          "field_name": "requirement",
          "field_type": "text",
          "examples": ["Add wiring for electrical outlets", "Space for wiring"]
        }
      ]
    }
  ]
}
```

### Training Process:
- Use OCR to find text patterns like "#" or "Model"
- Label hardware callouts with bounding boxes
- Train on variations: "Lemans #4930140150", "SubZero refrigerator", etc.

### Extraction Example:
```json
{
  "hardware_items": [
    {
      "item_name": "Lemans corner storage unit",
      "model_number": "4930140150",
      "component_reference": "Fridge Wall lower cabinet plan",
      "page_number": 5
    },
    {
      "item_name": "Utensil divider",
      "model_number": null,
      "component_reference": "Island top drawer",
      "page_number": 7
    }
  ],
  "material_specs": [
    {
      "material_type": "Soapstone or Walnut/White Oak",
      "application": "Pantry countertop",
      "fabrication_note": "TBD - if enough leftover from kitchen",
      "page_number": 6
    },
    {
      "material_type": "Countertop material",
      "application": "Sink countertop",
      "fabrication_note": "Machined drain grooves",
      "page_number": 8
    }
  ],
  "electrical_specs": [
    {
      "location": "Pantry elevations",
      "requirement": "Add wiring for electrical outlets",
      "page_number": 6
    },
    {
      "location": "Island section views",
      "requirement": "Space for wiring",
      "page_number": 7
    }
  ]
}
```

### Database Mapping:
```php
public function mapHardware($extractedData, $documentId)
{
    foreach ($extractedData['hardware_items'] as $item) {
        $hardware = Hardware::firstOrCreate(
            ['model_number' => $item['model_number']],
            [
                'item_name' => $item['item_name'],
                'description' => $item['item_name'],
            ]
        );

        // Link to component if reference exists
        if ($item['component_reference']) {
            $component = Component::where('description', 'LIKE', '%' . $item['component_reference'] . '%')
                ->first();

            if ($component) {
                Component_Hardware::create([
                    'component_id' => $component->id,
                    'hardware_id' => $hardware->id,
                ]);
            }
        }
    }
}
```

---

## Complete Implementation Code

### 1. Create Migration for New Tables

```bash
php artisan make:migration create_document_ai_extraction_tables
```

```php
// database/migrations/2025_10_06_create_document_ai_extraction_tables.php
public function up()
{
    // Projects table
    Schema::create('projects', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('address');
        $table->string('type')->nullable(); // "Kitchen Cabinetry"
        $table->timestamps();
    });

    // Documents table
    Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->foreignId('project_id')->constrained()->cascadeOnDelete();
        $table->foreignId('pdf_document_id')->constrained()->cascadeOnDelete();
        $table->integer('revision_number');
        $table->date('revision_date');
        $table->decimal('tier_2_linear_feet', 8, 2)->nullable();
        $table->decimal('tier_4_linear_feet', 8, 2)->nullable();
        $table->timestamps();
    });

    // Sheets table
    Schema::create('sheets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('document_id')->constrained()->cascadeOnDelete();
        $table->string('sheet_number');
        $table->string('sheet_title');
        $table->integer('page_number');
        $table->timestamps();
    });

    // Locations table
    Schema::create('locations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('document_id')->constrained()->cascadeOnDelete();
        $table->string('name'); // "Island", "Sink Wall"
        $table->enum('type', ['freestanding', 'wall_mounted', 'corner']);
        $table->integer('wall_number')->nullable();
        $table->integer('seating_capacity')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });

    // Cabinet Runs table
    Schema::create('cabinet_runs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('location_id')->constrained()->cascadeOnDelete();
        $table->enum('run_type', ['Base', 'Upper', 'Tall', 'Floating Shelves']);
        $table->decimal('linear_feet', 8, 2)->nullable();
        $table->timestamps();
    });

    // Components table
    Schema::create('components', function (Blueprint $table) {
        $table->id();
        $table->foreignId('run_id')->constrained('cabinet_runs')->cascadeOnDelete();
        $table->string('type'); // "Sink Base", "Drawer Bank"
        $table->decimal('width_inches', 8, 2)->nullable();
        $table->string('appliance_integration')->nullable();
        $table->string('position')->nullable(); // "Far left", "Right of sink"
        $table->text('notes')->nullable();
        $table->timestamps();
    });

    // SubComponents table
    Schema::create('sub_components', function (Blueprint $table) {
        $table->id();
        $table->foreignId('component_id')->constrained()->cascadeOnDelete();
        $table->enum('type', ['Door', 'Drawer', 'Shelf', 'Panel']);
        $table->text('description')->nullable(); // "Deep drawers for pots"
        $table->integer('quantity')->default(1);
        $table->timestamps();
    });

    // Hardware table
    Schema::create('hardware', function (Blueprint $table) {
        $table->id();
        $table->string('item_name');
        $table->string('model_number')->unique()->nullable();
        $table->text('description')->nullable();
        $table->timestamps();
    });

    // Component_Hardware pivot table
    Schema::create('component_hardware', function (Blueprint $table) {
        $table->foreignId('component_id')->constrained()->cascadeOnDelete();
        $table->foreignId('hardware_id')->constrained()->cascadeOnDelete();
        $table->primary(['component_id', 'hardware_id']);
    });
}
```

### 2. Create Document AI Pipeline Service

```php
// app/Services/DocumentAiPipelineService.php
<?php

namespace App\Services;

use App\Models\PdfDocument;
use App\Models\Project;
use App\Models\Document;
use App\Models\Location;
use App\Models\CabinetRun;
use App\Models\Component;
use App\Models\SubComponent;
use App\Models\Hardware;
use Illuminate\Support\Facades\Log;

class DocumentAiPipelineService
{
    protected GoogleDocumentAiService $documentAi;

    // Processor IDs (create these in Google Cloud)
    protected array $processors = [
        'ocr' => '1ce0abf59ba3ae89',           // Existing OCR processor
        'cover_page' => 'TBD',                  // Create via Workbench
        'plan_view' => 'TBD',                   // Create via Workbench
        'elevation' => 'TBD',                   // Create via Workbench
        'hardware' => 'TBD',                    // Create via Workbench
    ];

    public function __construct(GoogleDocumentAiService $documentAi)
    {
        $this->documentAi = $documentAi;
    }

    /**
     * Process entire PDF through multi-stage pipeline
     */
    public function processDocument(PdfDocument $pdfDoc): array
    {
        Log::info('Starting Document AI pipeline', ['pdf_id' => $pdfDoc->id]);

        $results = [];

        // Stage 1: Full OCR (already working)
        $results['ocr'] = $this->documentAi->extractFromPdf($pdfDoc->file_path);

        // Stage 2: Extract cover page (page 1)
        $results['cover_page'] = $this->extractCoverPage($pdfDoc);
        $document = $this->saveCoverPage($results['cover_page'], $pdfDoc);

        // Stage 3: Extract plan views (pages 2-3)
        $results['plan_views'] = $this->extractPlanViews($pdfDoc);
        $this->savePlanViews($results['plan_views'], $document);

        // Stage 4: Extract elevations (pages 4-8)
        $results['elevations'] = $this->extractElevations($pdfDoc);
        $this->saveElevations($results['elevations'], $document);

        // Stage 5: Extract hardware specifications
        $results['hardware'] = $this->extractHardware($pdfDoc);
        $this->saveHardware($results['hardware'], $document);

        Log::info('Document AI pipeline completed', ['pdf_id' => $pdfDoc->id]);

        return $results;
    }

    /**
     * Stage 2: Extract cover page data
     */
    protected function extractCoverPage(PdfDocument $pdfDoc): array
    {
        // For now, use existing PdfDataExtractor until custom processor trained
        $extractor = app(PdfDataExtractor::class);
        return $extractor->extractMetadata($pdfDoc);
    }

    /**
     * Save cover page data to database
     */
    protected function saveCoverPage(array $data, PdfDocument $pdfDoc): Document
    {
        // Create or update Project
        $project = Project::updateOrCreate(
            ['address' => $data['project']['address']],
            [
                'name' => $data['project']['address'], // Improve this
                'type' => $data['project']['type'] ?? 'Kitchen Cabinetry',
            ]
        );

        // Create Document record
        $document = Document::create([
            'project_id' => $project->id,
            'pdf_document_id' => $pdfDoc->id,
            'revision_number' => $data['revision_number'] ?? 1,
            'revision_date' => now(), // Extract from PDF
            'tier_2_linear_feet' => $data['tier_2_lf'] ?? null,
            'tier_4_linear_feet' => $data['tier_4_lf'] ?? null,
        ]);

        return $document;
    }

    /**
     * Stage 3: Extract plan view locations
     * TODO: Implement when custom processor trained
     */
    protected function extractPlanViews(PdfDocument $pdfDoc): array
    {
        // Placeholder for custom processor
        return [
            'locations' => [
                [
                    'name' => 'Island',
                    'type' => 'freestanding',
                    'wall_number' => null,
                    'seating_capacity' => 3,
                ],
                [
                    'name' => 'Sink Wall',
                    'type' => 'wall_mounted',
                    'wall_number' => 1,
                ],
                [
                    'name' => 'Fridge Wall',
                    'type' => 'wall_mounted',
                    'wall_number' => 2,
                ],
            ]
        ];
    }

    /**
     * Save plan view locations to database
     */
    protected function savePlanViews(array $data, Document $document): void
    {
        foreach ($data['locations'] as $locationData) {
            Location::create([
                'document_id' => $document->id,
                'name' => $locationData['name'],
                'type' => $locationData['type'],
                'wall_number' => $locationData['wall_number'],
                'seating_capacity' => $locationData['seating_capacity'] ?? null,
            ]);
        }
    }

    /**
     * Stage 4: Extract elevation data
     * TODO: Implement when custom processor trained
     */
    protected function extractElevations(PdfDocument $pdfDoc): array
    {
        return [
            'elevations' => []
        ];
    }

    /**
     * Save elevation data to database
     */
    protected function saveElevations(array $data, Document $document): void
    {
        // Implementation pending custom processor training
    }

    /**
     * Stage 5: Extract hardware specifications
     */
    protected function extractHardware(PdfDocument $pdfDoc): array
    {
        return [
            'hardware_items' => []
        ];
    }

    /**
     * Save hardware to database
     */
    protected function saveHardware(array $data, Document $document): void
    {
        // Implementation pending custom processor training
    }
}
```

---

## Training Timeline & Costs

### Phase 1: Cover Page Extraction (Week 1)
- **Training Data**: 10-20 TCS cover pages
- **Training Time**: 2 hours (automated)
- **Cost**: Free (training is free)
- **Accuracy**: 95%+ expected
- **Value**: Save 5 minutes per PDF

### Phase 2: Plan View Extraction (Week 2)
- **Training Data**: 10-20 plan view pages
- **Training Time**: 3 hours (more complex)
- **Cost**: Free
- **Accuracy**: 85%+ expected (spatial extraction harder)
- **Value**: Automated location detection

### Phase 3: Elevation Extraction (Week 3-4)
- **Training Data**: 20-30 elevation pages
- **Training Time**: 4-5 hours
- **Cost**: Free
- **Accuracy**: 80%+ expected (most complex)
- **Value**: Automated component breakdown

### Phase 4: Hardware Extraction (Week 4)
- **Training Data**: 10-15 pages with callouts
- **Training Time**: 2 hours
- **Cost**: Free
- **Accuracy**: 90%+ expected
- **Value**: Automated BOM generation

### Total Investment:
- **Time**: ~12 hours training + 20 hours development
- **Cost**: $0 training + API usage ($0.012/PDF)
- **ROI**: 30 minutes saved per PDF × 100 PDFs/month = **50 hours/month saved**

---

## Next Steps

### Option A: Start with Cover Page Only (Quick Win)
1. Collect 10-20 TCS cover pages
2. Train custom processor in Document AI Workbench
3. Integrate with existing code
4. **Time to value**: 1 week

### Option B: Full Pipeline (Maximum Automation)
1. Train all 4 custom processors
2. Build complete extraction pipeline
3. Full database population
4. **Time to value**: 4-6 weeks

### Option C: Hybrid (Recommended)
1. Week 1: Cover page + OCR (done + 1 week training)
2. Week 2-3: Plan view extraction
3. Week 4-5: Elevation extraction
4. Week 6: Hardware extraction
5. **Time to value**: Incremental improvements weekly

---

## Decision Point

Which approach would you like to pursue first?

1. **Train cover page processor now** - I can guide you through Document AI Workbench
2. **Continue with current PdfDataExtractor** - Focus on improving extraction logic
3. **Build database schema first** - Run migration, then add extraction later

Let me know and I'll create the next implementation steps!
