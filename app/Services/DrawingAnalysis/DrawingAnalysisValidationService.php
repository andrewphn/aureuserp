<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Cabinet;

/**
 * DrawingAnalysisValidationService
 *
 * Validates pipeline data at each step before database persistence.
 * Ensures data integrity and compliance with TCS woodworking standards.
 */
class DrawingAnalysisValidationService
{
    // Validation severity levels
    public const SEVERITY_ERROR = 'error';      // Blocks persistence
    public const SEVERITY_WARNING = 'warning';  // Logged but doesn't block
    public const SEVERITY_INFO = 'info';        // Informational only

    // TCS Standard Constraints
    public const CONSTRAINTS = [
        // Cabinet dimensions
        'cabinet_min_width' => 6,           // 6" minimum
        'cabinet_max_width' => 60,          // 60" maximum
        'cabinet_min_height' => 12,         // 12" minimum
        'cabinet_max_height' => 108,        // 108" maximum (9')
        'cabinet_min_depth' => 10,          // 10" minimum
        'cabinet_max_depth' => 36,          // 36" maximum

        // Face frame constraints
        'face_frame_min_width' => 1.0,      // 1" minimum stile/rail
        'face_frame_max_width' => 3.0,      // 3" maximum stile/rail
        'face_frame_gap_min' => 0.0625,     // 1/16" minimum gap
        'face_frame_gap_max' => 0.25,       // 1/4" maximum gap

        // Drawer constraints
        'drawer_min_height' => 3,           // 3" minimum front height
        'drawer_max_height' => 24,          // 24" maximum front height
        'drawer_box_min_height' => 2,       // 2" minimum box height
        'drawer_box_max_height' => 12,      // 12" maximum box height

        // Material constraints
        'box_thickness_standard' => 0.75,   // 3/4"
        'box_thickness_tolerance' => 0.03,  // Tolerance for thickness
        'back_thickness_options' => [0.25, 0.5, 0.75], // Valid back thicknesses

        // Toe kick constraints
        'toe_kick_min_height' => 3,         // 3" minimum
        'toe_kick_max_height' => 6,         // 6" maximum
        'toe_kick_standard' => 4.5,         // TCS standard

        // Stretcher constraints
        'stretcher_min_height' => 2,        // 2" minimum
        'stretcher_max_height' => 4,        // 4" maximum
        'stretcher_standard' => 3,          // TCS standard
    ];

    protected array $errors = [];
    protected array $warnings = [];
    protected array $validationResults = [];

    /**
     * Validate complete pipeline output before persistence
     *
     * @param array $pipelineOutput - Complete output from DrawingAnalysisOrchestrator
     * @return array Validation results
     */
    public function validateForPersistence(array $pipelineOutput): array
    {
        $this->errors = [];
        $this->warnings = [];
        $this->validationResults = [];

        $extractedData = $pipelineOutput['extracted_data'] ?? $pipelineOutput;

        // Validate each step's data
        $this->validateEntities($extractedData);
        $this->validateVerification($extractedData);
        $this->validateConstraints($extractedData);
        $this->validateComponents($extractedData);

        // Check referential integrity
        $this->validateReferentialIntegrity($extractedData);

        // Check TCS standards compliance
        $this->validateTcsStandards($extractedData);

        $isValid = empty($this->errors);

        return [
            'valid' => $isValid,
            'can_persist' => $isValid,
            'error_count' => count($this->errors),
            'warning_count' => count($this->warnings),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'validation_results' => $this->validationResults,
        ];
    }

    /**
     * Validate entity hierarchy (Step 5)
     */
    protected function validateEntities(array $data): void
    {
        $entities = $data['entities']['entities'] ?? $data['step_5_entity_extraction']['entities'] ?? [];

        // Validate project
        if (empty($entities['project'])) {
            $this->addError('entities', 'No project entity found in extraction data');
        }

        // Validate rooms
        $rooms = $entities['rooms'] ?? [];
        if (empty($rooms)) {
            $this->addWarning('entities', 'No rooms extracted - will create default room');
        }

        // Validate cabinets have required dimensions
        $cabinets = $entities['cabinets'] ?? [];
        foreach ($cabinets as $cabinet) {
            $this->validateCabinetEntity($cabinet);
        }

        $this->validationResults['entities'] = [
            'project_count' => $entities['project'] ? 1 : 0,
            'room_count' => count($rooms),
            'location_count' => count($entities['locations'] ?? []),
            'run_count' => count($entities['cabinet_runs'] ?? []),
            'cabinet_count' => count($cabinets),
            'section_count' => count($entities['sections'] ?? []),
        ];
    }

    /**
     * Validate cabinet entity data
     */
    protected function validateCabinetEntity(array $cabinet): void
    {
        $cabinetId = $cabinet['id'] ?? 'unknown';
        $geometry = $cabinet['bounding_geometry'] ?? [];

        // Check required dimensions
        $width = $this->extractNumeric($geometry['width'] ?? null);
        $height = $this->extractNumeric($geometry['height'] ?? null);
        $depth = $this->extractNumeric($geometry['depth'] ?? null);

        if (!$width) {
            $this->addError('cabinet', "Cabinet {$cabinetId}: Missing width dimension");
        } elseif ($width < self::CONSTRAINTS['cabinet_min_width'] || $width > self::CONSTRAINTS['cabinet_max_width']) {
            $this->addWarning('cabinet', "Cabinet {$cabinetId}: Width {$width}\" outside typical range (6-60\")");
        }

        if (!$height) {
            $this->addError('cabinet', "Cabinet {$cabinetId}: Missing height dimension");
        } elseif ($height < self::CONSTRAINTS['cabinet_min_height'] || $height > self::CONSTRAINTS['cabinet_max_height']) {
            $this->addWarning('cabinet', "Cabinet {$cabinetId}: Height {$height}\" outside typical range (12-108\")");
        }

        if (!$depth) {
            $this->addWarning('cabinet', "Cabinet {$cabinetId}: Missing depth dimension - will use standard 24\"");
        } elseif ($depth < self::CONSTRAINTS['cabinet_min_depth'] || $depth > self::CONSTRAINTS['cabinet_max_depth']) {
            $this->addWarning('cabinet', "Cabinet {$cabinetId}: Depth {$depth}\" outside typical range (10-36\")");
        }

        // Check parent reference
        if (empty($cabinet['parent_id'])) {
            $this->addError('cabinet', "Cabinet {$cabinetId}: Missing parent_id (cabinet run reference)");
        }
    }

    /**
     * Validate verification data (Step 6)
     */
    protected function validateVerification(array $data): void
    {
        $verification = $data['verification'] ?? $data['step_6_verification'] ?? [];
        $cabinetVerifications = $verification['cabinet_verifications'] ?? [];

        foreach ($cabinetVerifications as $v) {
            $cabinetId = $v['cabinet_id'] ?? 'unknown';
            $status = $v['overall_status'] ?? 'unknown';

            if ($status === 'discrepancy') {
                $this->addWarning('verification', "Cabinet {$cabinetId}: Dimension discrepancies detected - review before production");
            }

            // Check stack-ups
            $verticalStatus = $v['vertical_stackup']['status'] ?? null;
            $horizontalStatus = $v['horizontal_stackup']['status'] ?? null;

            if ($verticalStatus === 'discrepancy') {
                $discrepancy = $v['vertical_stackup']['discrepancy'] ?? 'unknown';
                $this->addWarning('verification', "Cabinet {$cabinetId}: Vertical stack-up discrepancy: {$discrepancy}");
            }

            if ($horizontalStatus === 'discrepancy') {
                $discrepancy = $v['horizontal_stackup']['discrepancy'] ?? 'unknown';
                $this->addWarning('verification', "Cabinet {$cabinetId}: Horizontal stack-up discrepancy: {$discrepancy}");
            }
        }

        $this->validationResults['verification'] = [
            'cabinets_verified' => count($cabinetVerifications),
            'discrepancies_found' => count(array_filter($cabinetVerifications, fn($v) => ($v['overall_status'] ?? '') === 'discrepancy')),
        ];
    }

    /**
     * Validate production constraints (Step 8)
     */
    protected function validateConstraints(array $data): void
    {
        $constraints = $data['constraints'] ?? $data['step_8_constraints'] ?? [];
        $constraintList = $constraints['constraints'] ?? [];

        foreach ($constraintList as $constraint) {
            $type = $constraint['type'] ?? 'unknown';
            $value = $constraint['value'] ?? null;

            // Validate gap standards
            if ($type === 'gap_standard') {
                if ($value < self::CONSTRAINTS['face_frame_gap_min'] || $value > self::CONSTRAINTS['face_frame_gap_max']) {
                    $this->addWarning('constraint', "Gap constraint {$value}\" outside typical range (1/16\" - 1/4\")");
                }
            }

            // Validate material thickness
            if ($type === 'material_thickness') {
                $tolerance = self::CONSTRAINTS['box_thickness_tolerance'];
                if (abs($value - self::CONSTRAINTS['box_thickness_standard']) > $tolerance &&
                    !in_array($value, self::CONSTRAINTS['back_thickness_options'])) {
                    $this->addWarning('constraint', "Material thickness {$value}\" is non-standard");
                }
            }
        }

        $this->validationResults['constraints'] = [
            'total_constraints' => count($constraintList),
            'inferred_count' => count(array_filter($constraintList, fn($c) => ($c['is_inferred'] ?? false))),
        ];
    }

    /**
     * Validate components (Step 9)
     */
    protected function validateComponents(array $data): void
    {
        $components = $data['components']['components'] ?? $data['step_9_components']['components'] ?? [];

        $componentCounts = [
            'drawer' => 0,
            'false_front' => 0,
            'door' => 0,
            'shelf' => 0,
            'stretcher' => 0,
        ];

        foreach ($components as $component) {
            $type = $component['type'] ?? 'unknown';
            $componentId = $component['id'] ?? 'unknown';

            if (isset($componentCounts[$type])) {
                $componentCounts[$type]++;
            }

            // Validate based on type
            switch ($type) {
                case 'drawer':
                case 'false_front':
                    $this->validateDrawerComponent($component, $componentId);
                    break;
                case 'door':
                    $this->validateDoorComponent($component, $componentId);
                    break;
                case 'stretcher':
                    $this->validateStretcherComponent($component, $componentId);
                    break;
            }

            // Check parent reference
            if (empty($component['parent_id'])) {
                $this->addError('component', "Component {$componentId}: Missing parent_id reference");
            }

            // Check for derivation confidence
            $confidence = $component['confidence'] ?? 1.0;
            if ($confidence < 0.7) {
                $this->addWarning('component', "Component {$componentId}: Low confidence ({$confidence}) - manual review recommended");
            }
        }

        $this->validationResults['components'] = [
            'total_count' => count($components),
            'by_type' => $componentCounts,
        ];
    }

    /**
     * Validate drawer/false front component
     */
    protected function validateDrawerComponent(array $component, string $id): void
    {
        $dims = $component['dimensions'] ?? [];
        $boxDims = $component['box_dimensions'] ?? [];

        // Validate front dimensions
        $frontHeight = $this->extractDimValue($dims['height'] ?? null);
        if ($frontHeight) {
            if ($frontHeight < self::CONSTRAINTS['drawer_min_height']) {
                $this->addWarning('component', "Drawer {$id}: Front height {$frontHeight}\" below minimum 3\"");
            }
            if ($frontHeight > self::CONSTRAINTS['drawer_max_height']) {
                $this->addWarning('component', "Drawer {$id}: Front height {$frontHeight}\" exceeds maximum 24\"");
            }
        }

        // Validate box dimensions for actual drawers (not false fronts)
        if ($component['type'] === 'drawer' && !empty($boxDims)) {
            $boxHeight = $this->extractDimValue($boxDims['height'] ?? null);
            if ($boxHeight) {
                if ($boxHeight < self::CONSTRAINTS['drawer_box_min_height']) {
                    $this->addWarning('component', "Drawer {$id}: Box height {$boxHeight}\" below minimum 2\"");
                }
                if ($boxHeight > self::CONSTRAINTS['drawer_box_max_height']) {
                    $this->addWarning('component', "Drawer {$id}: Box height {$boxHeight}\" exceeds maximum 12\"");
                }
            }
        }
    }

    /**
     * Validate door component
     */
    protected function validateDoorComponent(array $component, string $id): void
    {
        $dims = $component['dimensions'] ?? [];

        $width = $this->extractDimValue($dims['width'] ?? null);
        $height = $this->extractDimValue($dims['height'] ?? null);

        if (!$width || !$height) {
            $this->addWarning('component', "Door {$id}: Missing dimensions");
        }
    }

    /**
     * Validate stretcher component
     */
    protected function validateStretcherComponent(array $component, string $id): void
    {
        $dims = $component['dimensions'] ?? [];
        $height = $this->extractDimValue($dims['height'] ?? null);

        if ($height) {
            if ($height < self::CONSTRAINTS['stretcher_min_height']) {
                $this->addWarning('component', "Stretcher {$id}: Height {$height}\" below minimum 2\"");
            }
            if ($height > self::CONSTRAINTS['stretcher_max_height']) {
                $this->addWarning('component', "Stretcher {$id}: Height {$height}\" exceeds maximum 4\"");
            }
        }
    }

    /**
     * Validate referential integrity between extracted entities
     */
    protected function validateReferentialIntegrity(array $data): void
    {
        $entities = $data['entities']['entities'] ?? $data['step_5_entity_extraction']['entities'] ?? [];
        $components = $data['components']['components'] ?? $data['step_9_components']['components'] ?? [];

        // Build ID sets
        $roomIds = array_column($entities['rooms'] ?? [], 'id');
        $locationIds = array_column($entities['locations'] ?? [], 'id');
        $runIds = array_column($entities['cabinet_runs'] ?? [], 'id');
        $cabinetIds = array_column($entities['cabinets'] ?? [], 'id');
        $sectionIds = array_column($entities['sections'] ?? [], 'id');

        // Check location → room references
        foreach ($entities['locations'] ?? [] as $location) {
            if (!in_array($location['parent_id'] ?? '', $roomIds)) {
                $this->addError('integrity', "Location {$location['id']}: References non-existent room {$location['parent_id']}");
            }
        }

        // Check run → location references
        foreach ($entities['cabinet_runs'] ?? [] as $run) {
            if (!in_array($run['parent_id'] ?? '', $locationIds)) {
                $this->addError('integrity', "Run {$run['id']}: References non-existent location {$run['parent_id']}");
            }
        }

        // Check cabinet → run references
        foreach ($entities['cabinets'] ?? [] as $cabinet) {
            if (!in_array($cabinet['parent_id'] ?? '', $runIds)) {
                $this->addError('integrity', "Cabinet {$cabinet['id']}: References non-existent run {$cabinet['parent_id']}");
            }
        }

        // Check section → cabinet references
        foreach ($entities['sections'] ?? [] as $section) {
            if (!in_array($section['parent_id'] ?? '', $cabinetIds)) {
                $this->addError('integrity', "Section {$section['id']}: References non-existent cabinet {$section['parent_id']}");
            }
        }

        // Check component → cabinet/section references
        foreach ($components as $component) {
            $parentId = $component['parent_id'] ?? '';
            $sectionId = $component['parent_section_id'] ?? null;

            $validParent = in_array($parentId, $cabinetIds) || in_array($parentId, $sectionIds);
            if (!$validParent && !in_array($sectionId, $sectionIds)) {
                $this->addError('integrity', "Component {$component['id']}: References non-existent parent");
            }
        }
    }

    /**
     * Validate TCS woodworking standards compliance
     */
    protected function validateTcsStandards(array $data): void
    {
        $verification = $data['verification'] ?? $data['step_6_verification'] ?? [];
        $cabinetVerifications = $verification['cabinet_verifications'] ?? [];

        foreach ($cabinetVerifications as $v) {
            $cabinetId = $v['cabinet_id'] ?? 'unknown';
            $fixedElements = $v['fixed_elements'] ?? [];

            // Check toe kick compliance
            $verticalElements = $fixedElements['vertical'] ?? [];
            foreach ($verticalElements as $element) {
                if (($element['type'] ?? '') === 'toe_kick') {
                    $toeKickHeight = $element['value'] ?? 0;
                    if ($toeKickHeight < self::CONSTRAINTS['toe_kick_min_height'] ||
                        $toeKickHeight > self::CONSTRAINTS['toe_kick_max_height']) {
                        $this->addWarning('tcs_standards', "Cabinet {$cabinetId}: Toe kick height {$toeKickHeight}\" outside TCS range (3-6\")");
                    }
                }
            }

            // Check face frame compliance
            $horizontalElements = $fixedElements['horizontal'] ?? [];
            foreach ($horizontalElements as $element) {
                if (in_array($element['type'] ?? '', ['left_stile', 'right_stile'])) {
                    $stileWidth = $element['value'] ?? 0;
                    if ($stileWidth < self::CONSTRAINTS['face_frame_min_width'] ||
                        $stileWidth > self::CONSTRAINTS['face_frame_max_width']) {
                        $this->addWarning('tcs_standards', "Cabinet {$cabinetId}: Stile width {$stileWidth}\" outside TCS range (1-3\")");
                    }
                }
            }
        }
    }

    /**
     * Validate a single field against constraints
     */
    public function validateField(string $field, $value, array $constraints = []): array
    {
        $result = ['valid' => true, 'messages' => []];

        if (isset($constraints['min']) && $value < $constraints['min']) {
            $result['valid'] = false;
            $result['messages'][] = "{$field}: Value {$value} is below minimum {$constraints['min']}";
        }

        if (isset($constraints['max']) && $value > $constraints['max']) {
            $result['valid'] = false;
            $result['messages'][] = "{$field}: Value {$value} exceeds maximum {$constraints['max']}";
        }

        if (isset($constraints['in']) && !in_array($value, $constraints['in'])) {
            $result['valid'] = false;
            $result['messages'][] = "{$field}: Value {$value} not in allowed values";
        }

        return $result;
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function addError(string $category, string $message): void
    {
        $this->errors[] = [
            'category' => $category,
            'severity' => self::SEVERITY_ERROR,
            'message' => $message,
        ];

        Log::warning("Drawing analysis validation error", [
            'category' => $category,
            'message' => $message,
        ]);
    }

    protected function addWarning(string $category, string $message): void
    {
        $this->warnings[] = [
            'category' => $category,
            'severity' => self::SEVERITY_WARNING,
            'message' => $message,
        ];
    }

    protected function extractNumeric(?array $data): ?float
    {
        if (!$data) return null;
        return $data['numeric'] ?? $data['value'] ?? null;
    }

    protected function extractDimValue(?array $data): ?float
    {
        if (!$data) return null;
        return $data['value'] ?? null;
    }

    /**
     * Get all validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all validation warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get validation results summary
     */
    public function getResults(): array
    {
        return $this->validationResults;
    }
}
