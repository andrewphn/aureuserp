<?php

namespace App\Services;

use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetSection;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ConstructionTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * RhinoToCabinetMapper - Map extracted Rhino data to Cabinet models
 *
 * Transforms the raw extraction data from RhinoDataExtractor into
 * Cabinet model records, applying TCS standards and defaults.
 *
 * Mapping Strategy:
 * - Rhino group name → Cabinet name/identifier
 * - Rhino dimensions → Cabinet dimensions (with validation)
 * - Rhino components → CabinetSections
 * - Rhino fixtures → Appliance associations
 *
 * @author TCS Woodwork
 * @since January 2026
 */
class RhinoToCabinetMapper
{
    /**
     * Fields that can be auto-populated from Rhino data
     */
    protected const AUTO_POPULATE_FIELDS = [
        'cabinet_number',
        'length_inches',
        'height_inches',
        'depth_inches',
        'face_frame_stile_width_inches',
        'face_frame_rail_width_inches',
        'drawer_count',
        'door_count',
        'construction_type',
        'shop_notes',
    ];

    /**
     * Fields that require human verification/selection
     */
    protected const HUMAN_REQUIRED_FIELDS = [
        'product_variant_id',
        'unit_price_per_lf',
        'material_category',
        'finish_option',
        'door_style',
        'hinge_product_id',
        'slide_product_id',
    ];

    /**
     * Default construction template to apply
     */
    protected ?ConstructionTemplate $defaultTemplate = null;

    /**
     * Map extraction data to Cabinet model data
     *
     * @param array $extractedCabinet Cabinet data from RhinoDataExtractor
     * @param array $context Additional context (project_id, room_id, etc.)
     * @return array Mapped cabinet data ready for review/import
     */
    public function mapToCabinetData(array $extractedCabinet, array $context = []): array
    {
        $cabinetData = [
            // Identification
            'cabinet_number' => $this->generateCabinetNumber($extractedCabinet),
            'full_code' => $this->generateFullCode($extractedCabinet, $context),

            // Dimensions (from Rhino)
            'length_inches' => $this->validateDimension($extractedCabinet['width'] ?? null, 'width'),
            'height_inches' => $this->validateDimension($extractedCabinet['height'] ?? null, 'height'),
            'depth_inches' => $this->validateDimension($extractedCabinet['depth'] ?? null, 'depth'),

            // Auto-calculated
            'linear_feet' => null, // Will be calculated on save

            // Face frame
            'construction_type' => $this->determineConstructionType($extractedCabinet),
            'face_frame_stile_width_inches' => $extractedCabinet['face_frame']['stile_width'] ?? null,
            'face_frame_rail_width_inches' => $extractedCabinet['face_frame']['rail_width'] ?? null,

            // Components
            'drawer_count' => $extractedCabinet['components']['drawer_count'] ?? 0,
            'door_count' => $extractedCabinet['components']['door_count'] ?? 0,

            // Notes
            'shop_notes' => $this->buildShopNotes($extractedCabinet),

            // Context
            'project_id' => $context['project_id'] ?? null,
            'room_id' => $context['room_id'] ?? null,
            'cabinet_run_id' => $context['cabinet_run_id'] ?? null,
            'construction_template_id' => $context['construction_template_id'] ?? null,

            // Metadata
            '_rhino_source' => [
                'group_name' => $extractedCabinet['name'] ?? null,
                'confidence' => $extractedCabinet['confidence'] ?? 'low',
                'elevation_dims' => $extractedCabinet['elevation_dims'] ?? [],
                'plan_dims' => $extractedCabinet['plan_dims'] ?? [],
                'fixtures' => $extractedCabinet['fixtures'] ?? [],
            ],

            // Human-required fields (null until user selects)
            'product_variant_id' => null,
            'material_category' => $context['material_category'] ?? null,
            'finish_option' => $context['finish_option'] ?? null,
            'door_style' => null,

            // Status flags
            '_requires_review' => true,
            '_auto_populated_fields' => $this->identifyAutoPopulatedFields($extractedCabinet),
            '_missing_fields' => $this->identifyMissingFields($extractedCabinet),
        ];

        // Apply construction template defaults if available
        if (isset($context['construction_template_id'])) {
            $cabinetData = $this->applyTemplateDefaults($cabinetData, $context['construction_template_id']);
        }

        return $cabinetData;
    }

    /**
     * Map multiple extracted cabinets
     *
     * @param array $extractedData Full extraction data from RhinoDataExtractor
     * @param array $context Additional context
     * @return array Mapped cabinet data array
     */
    public function mapAllCabinets(array $extractedData, array $context = []): array
    {
        $cabinets = $extractedData['cabinets'] ?? [];
        $mapped = [];

        foreach ($cabinets as $index => $extractedCabinet) {
            $cabinetContext = array_merge($context, [
                'position_in_run' => $index + 1,
            ]);

            $mapped[] = $this->mapToCabinetData($extractedCabinet, $cabinetContext);
        }

        return [
            'cabinets' => $mapped,
            'fixtures' => $extractedData['fixtures'] ?? [],
            'views' => $extractedData['views'] ?? [],
            'summary' => [
                'total_cabinets' => count($mapped),
                'high_confidence' => count(array_filter($mapped, fn($c) => ($c['_rhino_source']['confidence'] ?? '') === 'high')),
                'requires_review' => count(array_filter($mapped, fn($c) => $c['_requires_review'] ?? true)),
            ],
        ];
    }

    /**
     * Create Cabinet records from mapped data
     *
     * @param array $mappedData Mapped cabinet data
     * @param bool $dryRun If true, return records without saving
     * @return Collection Created/prepared Cabinet records
     */
    public function createCabinets(array $mappedData, bool $dryRun = false): Collection
    {
        $cabinets = collect();

        DB::beginTransaction();

        try {
            foreach ($mappedData['cabinets'] as $cabinetData) {
                $cabinet = $this->createCabinetFromMappedData($cabinetData, $dryRun);
                if ($cabinet) {
                    $cabinets->push($cabinet);
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RhinoToCabinetMapper: Failed to create cabinets', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $cabinets;
    }

    /**
     * Create a single Cabinet from mapped data
     *
     * @param array $cabinetData Mapped cabinet data
     * @param bool $dryRun If true, don't save
     * @return Cabinet|null
     */
    protected function createCabinetFromMappedData(array $cabinetData, bool $dryRun = false): ?Cabinet
    {
        // Remove metadata fields before creating
        $cleanData = array_filter($cabinetData, function ($key) {
            return !str_starts_with($key, '_');
        }, ARRAY_FILTER_USE_KEY);

        // Remove null values to use model defaults
        $cleanData = array_filter($cleanData, fn($v) => $v !== null);

        $cabinet = new Cabinet($cleanData);

        // Attach metadata for review UI
        $cabinet->_rhino_metadata = [
            'source' => $cabinetData['_rhino_source'] ?? [],
            'auto_populated' => $cabinetData['_auto_populated_fields'] ?? [],
            'missing' => $cabinetData['_missing_fields'] ?? [],
            'requires_review' => $cabinetData['_requires_review'] ?? true,
        ];

        if (!$dryRun) {
            $cabinet->save();
        }

        return $cabinet;
    }

    /**
     * Generate cabinet number from Rhino data
     *
     * @param array $extractedCabinet
     * @return string|null
     */
    protected function generateCabinetNumber(array $extractedCabinet): ?string
    {
        $name = $extractedCabinet['name'] ?? '';
        $identifier = $extractedCabinet['identifier'] ?? '';

        // Parse identifier from group name
        if (preg_match('/([A-Z][a-z]*)-(.+)/', $name, $matches)) {
            return $matches[2]; // e.g., "Van" from "Austin-Van"
        }

        return $identifier ?: $name ?: null;
    }

    /**
     * Generate full code for cabinet
     *
     * @param array $extractedCabinet
     * @param array $context
     * @return string|null
     */
    protected function generateFullCode(array $extractedCabinet, array $context): ?string
    {
        $parts = [];

        // Get project code
        if (isset($context['project_id'])) {
            $project = Project::find($context['project_id']);
            if ($project?->project_number) {
                $parts[] = $project->project_number;
            }
        }

        // Get room code
        if (isset($context['room_id'])) {
            $room = Room::find($context['room_id']);
            if ($room?->room_code) {
                $parts[] = $room->room_code;
            }
        }

        // Add cabinet identifier
        $cabinetNumber = $this->generateCabinetNumber($extractedCabinet);
        if ($cabinetNumber) {
            $parts[] = $cabinetNumber;
        }

        return !empty($parts) ? implode('-', $parts) : null;
    }

    /**
     * Validate dimension value
     *
     * @param float|null $value
     * @param string $type 'width', 'height', or 'depth'
     * @return float|null
     */
    protected function validateDimension(?float $value, string $type): ?float
    {
        if ($value === null) {
            return null;
        }

        // Define valid ranges
        $ranges = [
            'width' => ['min' => 6, 'max' => 96],   // 6" to 96"
            'height' => ['min' => 12, 'max' => 108], // 12" to 108"
            'depth' => ['min' => 6, 'max' => 36],    // 6" to 36"
        ];

        $range = $ranges[$type] ?? ['min' => 0, 'max' => 1000];

        if ($value < $range['min'] || $value > $range['max']) {
            Log::warning("RhinoToCabinetMapper: Dimension out of range", [
                'type' => $type,
                'value' => $value,
                'range' => $range,
            ]);
            return null; // Mark as needing review
        }

        return round($value, 4);
    }

    /**
     * Determine construction type from Rhino data
     *
     * @param array $extractedCabinet
     * @return string
     */
    protected function determineConstructionType(array $extractedCabinet): string
    {
        $faceFrame = $extractedCabinet['face_frame'] ?? [];

        if ($faceFrame['detected'] ?? false) {
            return 'face_frame';
        }

        // Default to face frame for TCS
        return 'face_frame';
    }

    /**
     * Build shop notes from Rhino data
     *
     * @param array $extractedCabinet
     * @return string|null
     */
    protected function buildShopNotes(array $extractedCabinet): ?string
    {
        $notes = [];

        // Add source info
        $notes[] = "Imported from Rhino: {$extractedCabinet['name']}";

        // Add confidence
        $confidence = $extractedCabinet['confidence'] ?? 'unknown';
        $notes[] = "Extraction confidence: {$confidence}";

        // Add component info
        $components = $extractedCabinet['components'] ?? [];
        if ($components['has_u_shaped_drawer'] ?? false) {
            $notes[] = "Has U-shaped drawer (trash cabinet)";
        }
        if ($components['has_lazy_susan'] ?? false) {
            $notes[] = "Has lazy susan";
        }

        // Add fixture info
        $fixtures = $extractedCabinet['fixtures'] ?? [];
        foreach ($fixtures as $fixture) {
            $product = $fixture['product'] ?? 'Unknown fixture';
            $model = $fixture['model'] ?? '';
            $notes[] = "Fixture: {$product} {$model}";
        }

        return !empty($notes) ? implode("\n", $notes) : null;
    }

    /**
     * Identify which fields were auto-populated
     *
     * @param array $extractedCabinet
     * @return array
     */
    protected function identifyAutoPopulatedFields(array $extractedCabinet): array
    {
        $populated = [];

        if ($extractedCabinet['width'] ?? null) {
            $populated[] = 'length_inches';
        }
        if ($extractedCabinet['height'] ?? null) {
            $populated[] = 'height_inches';
        }
        if ($extractedCabinet['depth'] ?? null) {
            $populated[] = 'depth_inches';
        }
        if ($extractedCabinet['face_frame']['stile_width'] ?? null) {
            $populated[] = 'face_frame_stile_width_inches';
        }
        if (($extractedCabinet['components']['drawer_count'] ?? 0) > 0) {
            $populated[] = 'drawer_count';
        }
        if (($extractedCabinet['components']['door_count'] ?? 0) > 0) {
            $populated[] = 'door_count';
        }

        return $populated;
    }

    /**
     * Identify which required fields are missing
     *
     * @param array $extractedCabinet
     * @return array
     */
    protected function identifyMissingFields(array $extractedCabinet): array
    {
        $missing = [];

        if (!($extractedCabinet['width'] ?? null)) {
            $missing[] = [
                'field' => 'length_inches',
                'reason' => 'No width dimension found in elevation view',
            ];
        }
        if (!($extractedCabinet['height'] ?? null)) {
            $missing[] = [
                'field' => 'height_inches',
                'reason' => 'No height dimension found in elevation view',
            ];
        }
        if (!($extractedCabinet['depth'] ?? null)) {
            $missing[] = [
                'field' => 'depth_inches',
                'reason' => 'No depth dimension found in plan view',
            ];
        }

        // Always missing (require human selection)
        $missing[] = [
            'field' => 'material_category',
            'reason' => 'Material selection required',
        ];
        $missing[] = [
            'field' => 'finish_option',
            'reason' => 'Finish selection required',
        ];
        $missing[] = [
            'field' => 'door_style',
            'reason' => 'Door style selection required',
        ];

        return $missing;
    }

    /**
     * Apply construction template defaults to cabinet data
     *
     * @param array $cabinetData
     * @param int $templateId
     * @return array
     */
    protected function applyTemplateDefaults(array $cabinetData, int $templateId): array
    {
        $template = ConstructionTemplate::find($templateId);

        if (!$template) {
            return $cabinetData;
        }

        // Apply template defaults for missing values
        $templateDefaults = [
            'face_frame_stile_width_inches' => $template->face_frame_stile_width_inches,
            'face_frame_rail_width_inches' => $template->face_frame_rail_width_inches,
            'stretcher_height_inches' => $template->stretcher_height_inches,
            'face_frame_door_gap_inches' => $template->face_frame_door_gap_inches,
        ];

        foreach ($templateDefaults as $field => $defaultValue) {
            if ($defaultValue !== null && ($cabinetData[$field] ?? null) === null) {
                $cabinetData[$field] = $defaultValue;
                $cabinetData['_applied_template_defaults'][] = $field;
            }
        }

        return $cabinetData;
    }

    /**
     * Update an existing cabinet with Rhino data
     *
     * @param Cabinet $cabinet Existing cabinet to update
     * @param array $extractedCabinet Extracted Rhino data
     * @param array $fieldsToUpdate Specific fields to update (empty = all auto-populatable)
     * @return Cabinet Updated cabinet
     */
    public function updateCabinet(Cabinet $cabinet, array $extractedCabinet, array $fieldsToUpdate = []): Cabinet
    {
        $mapped = $this->mapToCabinetData($extractedCabinet, [
            'project_id' => $cabinet->project_id,
            'room_id' => $cabinet->room_id,
            'cabinet_run_id' => $cabinet->cabinet_run_id,
        ]);

        // Determine which fields to update
        $updateFields = !empty($fieldsToUpdate)
            ? $fieldsToUpdate
            : $mapped['_auto_populated_fields'] ?? [];

        foreach ($updateFields as $field) {
            if (isset($mapped[$field]) && $mapped[$field] !== null) {
                $cabinet->{$field} = $mapped[$field];
            }
        }

        // Append to shop notes
        $rhinoNote = "Updated from Rhino: {$extractedCabinet['name']} on " . now()->format('Y-m-d H:i');
        $cabinet->shop_notes = $cabinet->shop_notes
            ? $cabinet->shop_notes . "\n" . $rhinoNote
            : $rhinoNote;

        $cabinet->save();

        return $cabinet;
    }

    /**
     * Find matching cabinet by name pattern
     *
     * @param string $rhinoName Rhino group/cabinet name
     * @param int|null $projectId Optional project filter
     * @return Cabinet|null
     */
    public function findMatchingCabinet(string $rhinoName, ?int $projectId = null): ?Cabinet
    {
        $query = Cabinet::query();

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        // Try exact match on cabinet_number
        $cabinet = $query->where('cabinet_number', $rhinoName)->first();
        if ($cabinet) {
            return $cabinet;
        }

        // Try match on full_code containing the name
        $cabinet = $query->where('full_code', 'LIKE', "%{$rhinoName}%")->first();
        if ($cabinet) {
            return $cabinet;
        }

        // Try parsing identifier
        $identifier = $this->generateCabinetNumber(['name' => $rhinoName]);
        if ($identifier && $identifier !== $rhinoName) {
            return $query->where('cabinet_number', $identifier)->first();
        }

        return null;
    }

    /**
     * Generate import preview report
     *
     * @param array $mappedData Mapped cabinet data
     * @return array Preview report
     */
    public function generatePreviewReport(array $mappedData): array
    {
        $report = [
            'summary' => $mappedData['summary'] ?? [],
            'cabinets' => [],
            'warnings' => [],
            'errors' => [],
        ];

        foreach ($mappedData['cabinets'] as $index => $cabinet) {
            $cabinetReport = [
                'index' => $index,
                'name' => $cabinet['cabinet_number'] ?? 'Unknown',
                'dimensions' => [
                    'width' => $cabinet['length_inches'] ? "{$cabinet['length_inches']}\"" : 'Missing',
                    'height' => $cabinet['height_inches'] ? "{$cabinet['height_inches']}\"" : 'Missing',
                    'depth' => $cabinet['depth_inches'] ? "{$cabinet['depth_inches']}\"" : 'Missing',
                ],
                'components' => [
                    'drawers' => $cabinet['drawer_count'] ?? 0,
                    'doors' => $cabinet['door_count'] ?? 0,
                ],
                'confidence' => $cabinet['_rhino_source']['confidence'] ?? 'unknown',
                'auto_populated' => $cabinet['_auto_populated_fields'] ?? [],
                'missing' => array_map(fn($m) => $m['field'], $cabinet['_missing_fields'] ?? []),
            ];

            $report['cabinets'][] = $cabinetReport;

            // Add warnings
            if ($cabinetReport['confidence'] === 'low') {
                $report['warnings'][] = "Cabinet '{$cabinetReport['name']}' has low confidence extraction";
            }

            if (!$cabinet['length_inches'] || !$cabinet['height_inches']) {
                $report['warnings'][] = "Cabinet '{$cabinetReport['name']}' is missing critical dimensions";
            }
        }

        // Add fixture summary
        $fixtures = $mappedData['fixtures'] ?? [];
        if (!empty($fixtures)) {
            $report['fixtures'] = array_map(fn($f) => [
                'product' => $f['product'] ?? 'Unknown',
                'model' => $f['model'] ?? '',
            ], $fixtures);
        }

        return $report;
    }
}
