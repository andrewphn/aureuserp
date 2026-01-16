<?php

namespace App\Services;

use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetSection;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\FalseFront;
use Webkul\Project\Models\Stretcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Cabinet Builder Service - Master Orchestrator
 *
 * Builds cabinets from CAD data through a complete workflow:
 * 1. Parse CAD JSON data (like 9 Austin Lane project_10_full_data_map.json)
 * 2. Validate with Gates of Construction (CabinetMathAuditService)
 * 3. Create all database records in proper hierarchy order
 * 4. Generate complete cut list for shop fabrication
 *
 * TCS CONSTRUCTION RULES (Bryan Patton, Jan 2025):
 * ================================================
 *
 * BOX ASSEMBLY:
 * - Sides are SANDWICHED between top and bottom
 * - Back covers everything (used to square up the box)
 * - Build off the bottom first
 * - All materials: 3/4" prefinished maple plywood (including backs)
 *
 * COMPONENT RELATIONSHIPS:
 * - False front backing = stretcher (one piece, two functions)
 * - Equal drawer face heights for multi-drawer stacks
 * - Stretcher splits the gap between drawer faces
 * - 3" stretcher is also a divider between 2 drawers
 *
 * DIMENSIONS:
 * - Toe kick: 4.5" tall, recessed 3" from face
 * - Kitchen base cabinets: 34-3/4" (countertops are 1-1/4")
 * - Face frame: typically 1-1/2" to 1-3/4" stiles/rails
 * - Gap to door/drawer: 1/8"
 *
 * SINK LOCATIONS:
 * - Sides come up an additional 3/4" for countertop support
 *
 * CABINET TYPES:
 * - Face frame (TCS primary), European style, or hybrid
 * - Lower cabinets: 3" stretchers (no top needed)
 * - Upper/wall cabinets: can have full top
 *
 * @see docs/OPENING_CONFIGURATOR_SYSTEM.md
 */
class CabinetBuilderService
{
    /**
     * Material thickness constants
     * TCS: All cabinets built from 3/4" prefinished maple plywood
     */
    public const PLYWOOD_3_4 = 0.75;
    public const PLYWOOD_1_2 = 0.5;
    public const PLYWOOD_1_4 = 0.25;

    /**
     * TCS Standard dimensions (Bryan Patton, Jan 2025)
     */
    public const TCS_STRETCHER_DEPTH = 3.0;          // 3" stretcher depth
    public const TCS_TOE_KICK_HEIGHT = 4.5;          // 4.5" toe kick height
    public const TCS_TOE_KICK_RECESS = 3.0;          // 3" recessed from face
    public const TCS_FACE_FRAME_STILE = 1.5;         // 1-1/2" stile width
    public const TCS_FACE_FRAME_RAIL = 1.5;          // 1-1/2" rail width
    public const TCS_COMPONENT_GAP = 0.125;          // 1/8" gap to door/drawer
    public const TCS_SINK_COUNTERTOP_SUPPORT = 0.75; // +3/4" for sink locations
    public const TCS_KITCHEN_BASE_HEIGHT = 34.75;    // 34-3/4" standard kitchen base

    /**
     * Injected services
     */
    protected CabinetConfiguratorService $cabinetConfigurator;
    protected StretcherCalculator $stretcherCalculator;
    protected OpeningConfiguratorService $openingConfigurator;
    protected CabinetMathAuditService $mathAudit;
    protected DrawerConfiguratorService $drawerConfigurator;

    public function __construct(
        ?CabinetConfiguratorService $cabinetConfigurator = null,
        ?StretcherCalculator $stretcherCalculator = null,
        ?OpeningConfiguratorService $openingConfigurator = null,
        ?CabinetMathAuditService $mathAudit = null,
        ?DrawerConfiguratorService $drawerConfigurator = null,
    ) {
        $this->cabinetConfigurator = $cabinetConfigurator ?? app(CabinetConfiguratorService::class);
        $this->stretcherCalculator = $stretcherCalculator ?? app(StretcherCalculator::class);
        $this->openingConfigurator = $openingConfigurator ?? app(OpeningConfiguratorService::class);
        $this->mathAudit = $mathAudit ?? app(CabinetMathAuditService::class);
        $this->drawerConfigurator = $drawerConfigurator ?? app(DrawerConfiguratorService::class);
    }

    // =========================================================================
    // MAIN ENTRY POINT
    // =========================================================================

    /**
     * Main entry point - build cabinet from CAD data
     *
     * @param array $cadData CAD JSON data structure
     * @param int|null $cabinetRunId Optional cabinet run to attach cabinet to
     * @param bool $dryRun If true, validate and calculate without creating records
     * @return array Result with created records, validation, and cut list
     */
    public function buildFromCAD(array $cadData, ?int $cabinetRunId = null, bool $dryRun = false): array
    {
        // Parse and normalize CAD data
        $parsed = $this->parseCADData($cadData);

        // Validate input before building
        $validation = $this->validateInput($parsed);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
                'parsed_data' => $parsed,
            ];
        }

        // Run pre-build math audit
        $preAudit = $this->mathAudit->generateFullAudit($this->buildAuditSpecs($parsed));

        if ($dryRun) {
            return [
                'success' => true,
                'dry_run' => true,
                'parsed_data' => $parsed,
                'validation' => $validation,
                'pre_audit' => $preAudit,
                'cut_list' => $preAudit['cut_list'],
            ];
        }

        // Build within a transaction
        return DB::transaction(function () use ($parsed, $cabinetRunId, $preAudit) {
            // Create records in hierarchy order
            $cabinet = $this->createCabinet($parsed['cabinet'], $cabinetRunId);
            $sections = $this->createSections($cabinet, $parsed);
            $falseFronts = $this->createFalseFronts($cabinet, $sections[0] ?? null, $parsed);
            $drawers = $this->createDrawers($cabinet, $sections[0] ?? null, $parsed);
            $stretchers = $this->createStretchers($cabinet, $parsed, $falseFronts);

            // Run post-build math audit
            $postAudit = $this->runMathAudit($cabinet);

            // Generate cut list from created records
            $cutList = $this->generateCutList($cabinet);

            return [
                'success' => true,
                'cabinet' => $cabinet,
                'records_created' => [
                    'cabinet' => $cabinet,
                    'sections' => $sections,
                    'false_fronts' => $falseFronts,
                    'drawers' => $drawers,
                    'stretchers' => $stretchers,
                ],
                'validation' => $postAudit,
                'pre_audit' => $preAudit,
                'cut_list' => $cutList,
            ];
        });
    }

    // =========================================================================
    // PARSING
    // =========================================================================

    /**
     * Parse CAD JSON into normalized structure
     *
     * Accepts various CAD data formats and normalizes to internal structure.
     *
     * @param array $rawData Raw CAD JSON data
     * @return array Normalized data structure
     */
    public function parseCADData(array $rawData): array
    {
        // Handle nested cabinet data
        $cabinetData = $rawData['cabinet'] ?? $rawData;

        // Normalize cabinet dimensions
        $cabinet = [
            'width_inches' => $this->extractDimension($cabinetData, ['width_inches', 'width', 'length_inches', 'length']),
            'height_inches' => $this->extractDimension($cabinetData, ['height_inches', 'height', 'overall_height_inches']),
            'depth_inches' => $this->extractDimension($cabinetData, ['depth_inches', 'depth', 'overall_depth_inches']),
            'toe_kick_height_inches' => $this->extractDimension($cabinetData, ['toe_kick_height_inches', 'toe_kick_height', 'toe_kick'], self::TCS_TOE_KICK_HEIGHT),
            'face_frame_stile_width' => $this->extractDimension($cabinetData, ['face_frame_stile_width', 'face_frame_stile', 'stile_width'], self::TCS_FACE_FRAME_STILE),
            'face_frame_rail_width' => $this->extractDimension($cabinetData, ['face_frame_rail_width', 'face_frame_rail', 'rail_width'], self::TCS_FACE_FRAME_RAIL),
            'cabinet_type' => $cabinetData['cabinet_type'] ?? $cabinetData['type'] ?? 'base',
            'side_panel_thickness' => $this->extractDimension($cabinetData, ['side_panel_thickness'], self::PLYWOOD_3_4),
            'back_panel_thickness' => $this->extractDimension($cabinetData, ['back_panel_thickness'], self::PLYWOOD_3_4),
        ];

        // Calculate derived dimensions
        $cabinet['box_height_inches'] = $cabinet['height_inches'] - $cabinet['toe_kick_height_inches'];
        $cabinet['inside_width_inches'] = $cabinet['width_inches'] - (2 * $cabinet['side_panel_thickness']);
        $cabinet['inside_depth_inches'] = $cabinet['depth_inches'] - $cabinet['back_panel_thickness'];

        // Determine if this is a KITCHEN sink location (requires +3/4" countertop support)
        // TCS Rule: Only kitchen sink bases get the extra height, NOT bathroom vanities
        // Vanities have different sink mounting (drop-in or undermount vessel sinks)
        $isSinkLocation = in_array($cabinet['cabinet_type'], ['sink_base', 'kitchen_sink']);
        $cabinet['is_sink_location'] = $isSinkLocation;

        // TCS BOX ASSEMBLY RULE (Bryan Patton, Jan 2025):
        // "Sides are SANDWICHED between top and bottom"
        // This means side height = box height - bottom thickness - top/stretcher thickness
        //
        // For lower cabinets (stretchers instead of top):
        //   Side height = box height - bottom thickness - stretcher thickness
        //
        // For sink locations (countertop support):
        //   Sides come up an additional 3/4" for countertop support
        //
        // NOTE: The current 9 Austin Lane CAD shows sides at full 28-3/4" (box height)
        // This suggests the CAD may already account for assembly, OR TCS uses a different
        // assembly method. We'll track both values for verification.

        // Back panel height - TCS Rule:
        // Back covers everything (used to square up the box)
        // Back is FULL BOX HEIGHT regardless of cabinet type
        $cabinet['back_panel_height_inches'] = $cabinet['box_height_inches'];

        // Side panel height for CUT LIST - depends on assembly method
        // Option A: If sides are sandwiched between top/bottom:
        //   side_height = box_height - bottom_thickness - top_thickness (or stretcher)
        // Option B: If sides run full height (CAD shows this for 9 Austin Lane):
        //   side_height = box_height
        //
        // Using Option B for now (full height) to match CAD
        // TODO: Verify with Bryan which assembly method TCS uses
        $cabinet['side_panel_height_inches'] = $cabinet['box_height_inches'];

        // For sink locations: add 3/4" to side height for countertop support
        if ($isSinkLocation) {
            $cabinet['side_panel_height_inches'] += self::TCS_SINK_COUNTERTOP_SUPPORT;
            $cabinet['sink_countertop_support_inches'] = self::TCS_SINK_COUNTERTOP_SUPPORT;
        } else {
            $cabinet['sink_countertop_support_inches'] = 0;
        }

        // Toe kick dimensions
        $cabinet['toe_kick_recess_inches'] = self::TCS_TOE_KICK_RECESS;

        // Face frame opening
        $cabinet['ff_opening_width_inches'] = $cabinet['width_inches'] - (2 * $cabinet['face_frame_stile_width']);
        $cabinet['ff_opening_height_inches'] = $cabinet['box_height_inches'] - (2 * $cabinet['face_frame_rail_width']);

        // Parse false fronts
        $falseFronts = [];
        foreach ($rawData['false_fronts'] ?? [] as $ff) {
            $falseFronts[] = [
                'height_inches' => $ff['height_inches'] ?? $ff['face_height_inches'] ?? 6,
                'backing_height_inches' => $ff['backing_height_inches'] ?? (($ff['height_inches'] ?? 6) + 1),
                'backing_thickness_inches' => $ff['backing_thickness_inches'] ?? self::PLYWOOD_3_4,
                'backing_material' => $ff['backing_material'] ?? 'plywood',
                'backing_is_stretcher' => $ff['backing_is_stretcher'] ?? true, // TCS Rule
                'false_front_type' => $ff['false_front_type'] ?? $ff['type'] ?? 'fixed',
                'width_inches' => $ff['width_inches'] ?? $cabinet['ff_opening_width_inches'],
            ];
        }

        // Parse drawers
        $drawers = [];
        foreach ($rawData['drawers'] ?? [] as $index => $drawer) {
            $drawers[] = [
                'front_height_inches' => $drawer['front_height_inches'] ?? $drawer['height_inches'] ?? 6,
                'position' => $drawer['position'] ?? ($index === 0 ? 'upper' : 'lower'),
                'drawer_number' => $drawer['drawer_number'] ?? ($index + 1),
                'width_inches' => $drawer['width_inches'] ?? $drawer['front_width_inches'] ?? $cabinet['ff_opening_width_inches'],
                // Box dimensions will be calculated from opening and Blum clearances
                'box_height_inches' => $drawer['box_height_inches'] ?? null,
                'box_width_inches' => $drawer['box_width_inches'] ?? null,
                'box_depth_inches' => $drawer['box_depth_inches'] ?? null,
            ];
        }

        // Parse stretcher override
        $stretcherPositionOverride = $rawData['stretcher_position_override']
            ?? $rawData['stretcher_position_from_top']
            ?? null;

        return [
            'cabinet' => $cabinet,
            'false_fronts' => $falseFronts,
            'drawers' => $drawers,
            'stretcher_position_override' => $stretcherPositionOverride,
            'drawer_slide_length' => $rawData['drawer_slide_length'] ?? 18,
        ];
    }

    /**
     * Extract a dimension from multiple possible field names
     */
    protected function extractDimension(array $data, array $fieldNames, float $default = 0): float
    {
        foreach ($fieldNames as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                return (float) $data[$field];
            }
        }
        return $default;
    }

    // =========================================================================
    // VALIDATION
    // =========================================================================

    /**
     * Validate input before building
     *
     * @param array $parsedData Normalized CAD data
     * @return array Validation result with errors and warnings
     */
    public function validateInput(array $parsedData): array
    {
        $errors = [];
        $warnings = [];

        $cabinet = $parsedData['cabinet'];

        // Required dimensions
        if ($cabinet['width_inches'] <= 0) {
            $errors[] = 'Cabinet width must be greater than 0';
        }
        if ($cabinet['height_inches'] <= 0) {
            $errors[] = 'Cabinet height must be greater than 0';
        }
        if ($cabinet['depth_inches'] <= 0) {
            $errors[] = 'Cabinet depth must be greater than 0';
        }

        // Box height must be positive after toe kick subtraction
        if ($cabinet['box_height_inches'] <= 0) {
            $errors[] = 'Box height after toe kick subtraction must be positive';
        }

        // Validate drawer face heights equal (TCS Rule)
        $drawerHeights = array_column($parsedData['drawers'], 'front_height_inches');
        if (count(array_unique($drawerHeights)) > 1) {
            // Not all equal - check if intentional
            $uniqueHeights = array_unique($drawerHeights);
            if (count($uniqueHeights) > 2) {
                $warnings[] = 'Multiple different drawer face heights detected - verify this is intentional';
            }
        }

        // Validate false fronts have backing (TCS Rule)
        foreach ($parsedData['false_fronts'] as $index => $ff) {
            if (!($ff['backing_is_stretcher'] ?? true)) {
                $warnings[] = "False front #{$index}: TCS rule requires backing to serve as stretcher";
            }
        }

        // Validate components fit in opening
        // Note: In full overlay construction, drawer/door faces OVERLAP the face frame
        // So we compare against box height, not FF opening height
        $totalFaceHeight = 0;
        $componentCount = count($parsedData['false_fronts']) + count($parsedData['drawers']);

        foreach ($parsedData['false_fronts'] as $ff) {
            $totalFaceHeight += $ff['height_inches'];
        }
        foreach ($parsedData['drawers'] as $drawer) {
            $totalFaceHeight += $drawer['front_height_inches'];
        }

        // Add gaps between components (n-1 gaps for n components)
        // Full overlay: faces overlap rails, so minimal gap at top/bottom
        $totalConsumed = $totalFaceHeight + (max(0, $componentCount - 1) * self::TCS_COMPONENT_GAP);

        // For full overlay, compare against box height (more accurate)
        // The FF opening is what's visible, but faces overlay the rails
        $availableHeight = $cabinet['box_height_inches'];

        if ($totalConsumed > $availableHeight + 0.5) { // Allow 1/2" tolerance for overlay overlap
            $errors[] = sprintf(
                'Components (%.4f") exceed available box height (%.4f")',
                $totalConsumed,
                $availableHeight
            );
        } elseif ($totalConsumed > $availableHeight) {
            $warnings[] = sprintf(
                'Components (%.4f") are at maximum for box height (%.4f") - full overlay expected',
                $totalConsumed,
                $availableHeight
            );
        }

        // Cabinet depth validation for drawer slides
        $slideLength = $parsedData['drawer_slide_length'] ?? 18;
        $minDepth = $slideLength + 0.75; // Shop minimum
        if ($cabinet['depth_inches'] < $minDepth) {
            $warnings[] = sprintf(
                'Cabinet depth (%.4f") is less than recommended minimum (%.4f") for %d" slides',
                $cabinet['depth_inches'],
                $minDepth,
                $slideLength
            );
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    // =========================================================================
    // RECORD CREATION
    // =========================================================================

    /**
     * Create cabinet record
     *
     * @param array $cabinetData Normalized cabinet data
     * @param int|null $cabinetRunId Optional cabinet run to attach to
     * @return Cabinet Created cabinet model
     */
    protected function createCabinet(array $cabinetData, ?int $cabinetRunId): Cabinet
    {
        $cabinet = Cabinet::create([
            'cabinet_run_id' => $cabinetRunId,
            'cabinet_number' => $this->getNextCabinetNumber($cabinetRunId),
            'length_inches' => $cabinetData['width_inches'],
            'height_inches' => $cabinetData['height_inches'],
            'depth_inches' => $cabinetData['depth_inches'],
            'toe_kick_height' => $cabinetData['toe_kick_height_inches'],
            'toe_kick_depth' => 3.0, // TCS Standard
            'face_frame_stile_width' => $cabinetData['face_frame_stile_width'],
            'face_frame_rail_width' => $cabinetData['face_frame_rail_width'],
            'cabinet_type' => $cabinetData['cabinet_type'],
            'construction_type' => 'face_frame',
            'top_construction_type' => 'stretchers',
            'side_panel_thickness' => $cabinetData['side_panel_thickness'],
            'back_panel_thickness' => $cabinetData['back_panel_thickness'],
        ]);

        return $cabinet;
    }

    /**
     * Get next cabinet number for a cabinet run
     */
    protected function getNextCabinetNumber(?int $cabinetRunId): int
    {
        if (!$cabinetRunId) {
            return 1;
        }

        return Cabinet::where('cabinet_run_id', $cabinetRunId)->max('cabinet_number') + 1 ?? 1;
    }

    /**
     * Create sections based on cabinet type
     *
     * @param Cabinet $cabinet The parent cabinet
     * @param array $parsedData Parsed CAD data
     * @return array Created sections
     */
    protected function createSections(Cabinet $cabinet, array $parsedData): array
    {
        $sections = [];

        // For now, create a single section for the full opening
        // (Multi-section layouts would need additional CAD data)
        $section = CabinetSection::create([
            'cabinet_id' => $cabinet->id,
            'section_number' => 1,
            'name' => 'Main Section',
            'section_type' => $this->determineSectionType($parsedData),
            'sort_order' => 1,
            'opening_width_inches' => $parsedData['cabinet']['ff_opening_width_inches'],
            'opening_height_inches' => $parsedData['cabinet']['ff_opening_height_inches'],
            'position_from_left_inches' => $parsedData['cabinet']['face_frame_stile_width'],
            'position_from_bottom_inches' => $parsedData['cabinet']['face_frame_rail_width'],
            'section_width_ratio' => 1.0,
        ]);

        $sections[] = $section;

        return $sections;
    }

    /**
     * Determine section type from components
     */
    protected function determineSectionType(array $parsedData): string
    {
        $hasFalseFronts = !empty($parsedData['false_fronts']);
        $hasDrawers = !empty($parsedData['drawers']);

        if ($hasFalseFronts && $hasDrawers) {
            return 'drawer_bank'; // Sink base with false front above drawers
        }
        if ($hasDrawers) {
            return 'drawer_bank';
        }
        if ($hasFalseFronts) {
            return 'false_front';
        }

        return 'door';
    }

    /**
     * Create false fronts with backing as stretcher
     *
     * TCS Rule: False front backing doubles as stretcher
     *
     * @param Cabinet $cabinet Parent cabinet
     * @param CabinetSection|null $section Parent section
     * @param array $parsedData Parsed CAD data
     * @return array Created false fronts
     */
    protected function createFalseFronts(Cabinet $cabinet, ?CabinetSection $section, array $parsedData): array
    {
        $falseFronts = [];

        foreach ($parsedData['false_fronts'] as $index => $ffData) {
            $falseFront = FalseFront::create([
                'cabinet_id' => $cabinet->id,
                'section_id' => $section?->id,
                'false_front_number' => $index + 1,
                'false_front_name' => 'False Front ' . ($index + 1),
                'sort_order' => $index + 1,
                'false_front_type' => $ffData['false_front_type'],
                'width_inches' => $ffData['width_inches'],
                'height_inches' => $ffData['height_inches'],
                'thickness_inches' => self::PLYWOOD_3_4,
                // Backing (TCS: always present, serves as stretcher)
                'has_backing' => true,
                'backing_height_inches' => $ffData['backing_height_inches'],
                'backing_thickness_inches' => $ffData['backing_thickness_inches'],
                'backing_material' => $ffData['backing_material'],
                'backing_is_stretcher' => true, // TCS Rule enforced
            ]);

            $falseFronts[] = $falseFront;
        }

        return $falseFronts;
    }

    /**
     * Create drawers with calculated box dimensions
     *
     * @param Cabinet $cabinet Parent cabinet
     * @param CabinetSection|null $section Parent section
     * @param array $parsedData Parsed CAD data
     * @return array Created drawers
     */
    protected function createDrawers(Cabinet $cabinet, ?CabinetSection $section, array $parsedData): array
    {
        $drawers = [];
        $slideLength = $parsedData['drawer_slide_length'] ?? 18;

        foreach ($parsedData['drawers'] as $index => $drawerData) {
            // Calculate box dimensions using DrawerConfiguratorService
            $openingWidth = $parsedData['cabinet']['ff_opening_width_inches'];
            $openingHeight = $drawerData['front_height_inches'];
            $openingDepth = $parsedData['cabinet']['inside_depth_inches'];

            $boxCalc = $this->drawerConfigurator->calculateDrawerDimensions(
                $openingWidth,
                $openingHeight,
                $openingDepth
            );

            // Use CAD data if provided, otherwise use calculated values
            $boxWidth = $drawerData['box_width_inches'] ?? $boxCalc['drawer_box']['outside_width'];
            $boxHeight = $drawerData['box_height_inches'] ?? $boxCalc['drawer_box']['height_shop'];
            $boxDepth = $drawerData['box_depth_inches'] ?? $boxCalc['drawer_box']['depth_shop'];

            $drawer = Drawer::create([
                'cabinet_id' => $cabinet->id,
                'section_id' => $section?->id,
                'drawer_number' => $drawerData['drawer_number'],
                'drawer_name' => 'Drawer ' . $drawerData['drawer_number'],
                'sort_order' => $drawerData['drawer_number'],
                'drawer_position' => $drawerData['position'],
                // Face dimensions
                'front_width_inches' => $drawerData['width_inches'],
                'front_height_inches' => $drawerData['front_height_inches'],
                // Box dimensions (calculated from Blum clearances)
                'box_width_inches' => $boxWidth,
                'box_height_inches' => $boxHeight,
                'box_depth_inches' => $boxDepth,
                'box_height_shop_inches' => $boxCalc['drawer_box']['height_shop'],
                'box_depth_shop_inches' => $boxCalc['drawer_box']['depth_shop'],
                // Opening info
                'opening_width_inches' => $openingWidth,
                'opening_height_inches' => $openingHeight,
                // Hardware
                'slide_length_inches' => $slideLength,
            ]);

            $drawers[] = $drawer;
        }

        return $drawers;
    }

    /**
     * Create stretchers (accounting for false front backings)
     *
     * TCS Rule: False front backing replaces a stretcher
     *
     * @param Cabinet $cabinet Parent cabinet
     * @param array $parsedData Parsed CAD data
     * @param array $falseFronts Created false fronts
     * @return array Created stretchers
     */
    protected function createStretchers(Cabinet $cabinet, array $parsedData, array $falseFronts): array
    {
        $stretchers = [];
        $insideWidth = $parsedData['cabinet']['inside_width_inches'];

        // Count false fronts with backings (these replace stretchers)
        $falseFrontBackingCount = count(array_filter(
            $parsedData['false_fronts'],
            fn($ff) => ($ff['backing_is_stretcher'] ?? true)
        ));

        // Front stretcher (always)
        $stretchers[] = Stretcher::create([
            'cabinet_id' => $cabinet->id,
            'position' => Stretcher::POSITION_FRONT,
            'stretcher_number' => 1,
            'width_inches' => $insideWidth,
            'depth_inches' => self::TCS_STRETCHER_DEPTH,
            'thickness_inches' => self::PLYWOOD_3_4,
            'position_from_front_inches' => 0,
            'position_from_top_inches' => 0,
        ]);

        // Back stretcher (always)
        $stretchers[] = Stretcher::create([
            'cabinet_id' => $cabinet->id,
            'position' => Stretcher::POSITION_BACK,
            'stretcher_number' => 2,
            'width_inches' => $insideWidth,
            'depth_inches' => self::TCS_STRETCHER_DEPTH,
            'thickness_inches' => self::PLYWOOD_3_4,
            'position_from_front_inches' => $parsedData['cabinet']['depth_inches'] - self::TCS_STRETCHER_DEPTH,
            'position_from_top_inches' => 0,
        ]);

        // Drawer support stretchers
        // Count = drawer_count - 1 - false_front_backing_count
        // (Bottom drawer mounts on cabinet bottom, false front backings replace stretchers)
        $drawerCount = count($parsedData['drawers']);
        $drawerSupportCount = max(0, $drawerCount - 1 - $falseFrontBackingCount);

        // Calculate stretcher positions
        $stretcherNumber = 3;
        $boxHeight = $parsedData['cabinet']['box_height_inches'];

        for ($i = 0; $i < $drawerSupportCount; $i++) {
            // Get drawers above this stretcher position
            $drawersAbove = array_slice($parsedData['drawers'], 0, $i + 1 + count($parsedData['false_fronts']));

            // Calculate position from top
            $positionFromTop = $parsedData['stretcher_position_override'] ?? $this->calculateStretcherPositionFromTop(
                $parsedData['false_fronts'],
                $drawersAbove,
                $boxHeight
            );

            $stretchers[] = Stretcher::create([
                'cabinet_id' => $cabinet->id,
                'position' => Stretcher::POSITION_DRAWER_SUPPORT,
                'stretcher_number' => $stretcherNumber,
                'width_inches' => $insideWidth,
                'depth_inches' => self::TCS_STRETCHER_DEPTH,
                'thickness_inches' => self::PLYWOOD_3_4,
                'position_from_front_inches' => 0.5, // TCS: Set back from front
                'position_from_top_inches' => $positionFromTop,
            ]);

            $stretcherNumber++;
        }

        return $stretchers;
    }

    /**
     * Calculate stretcher position from top of box
     *
     * TCS Rule: Stretcher splits the gap between drawer faces
     *
     * @param array $falseFronts False front data
     * @param array $drawersAbove Drawers above this stretcher
     * @param float $boxHeight Box height
     * @return float Position from top in inches
     */
    protected function calculateStretcherPositionFromTop(array $falseFronts, array $drawersAbove, float $boxHeight): float
    {
        $position = 0;

        // Add false front heights
        foreach ($falseFronts as $ff) {
            $position += $ff['height_inches'] + self::TCS_COMPONENT_GAP;
        }

        // Add drawer face heights above this stretcher
        foreach ($drawersAbove as $drawer) {
            if (is_array($drawer)) {
                $position += ($drawer['front_height_inches'] ?? 0) + self::TCS_COMPONENT_GAP;
            } else {
                $position += ($drawer->front_height_inches ?? 0) + self::TCS_COMPONENT_GAP;
            }
        }

        return $position;
    }

    // =========================================================================
    // AUDIT AND CUT LIST
    // =========================================================================

    /**
     * Build specs array for CabinetMathAuditService
     */
    protected function buildAuditSpecs(array $parsedData): array
    {
        return [
            'width' => $parsedData['cabinet']['width_inches'],
            'height' => $parsedData['cabinet']['height_inches'],
            'depth' => $parsedData['cabinet']['depth_inches'],
            'toe_kick_height' => $parsedData['cabinet']['toe_kick_height_inches'],
            'face_frame_stile' => $parsedData['cabinet']['face_frame_stile_width'],
            'face_frame_rail' => $parsedData['cabinet']['face_frame_rail_width'],
            'side_panel_thickness' => $parsedData['cabinet']['side_panel_thickness'],
            'back_panel_thickness' => $parsedData['cabinet']['back_panel_thickness'],
            'drawer_heights' => array_column($parsedData['drawers'], 'front_height_inches'),
            'drawer_slide_length' => $parsedData['drawer_slide_length'] ?? 18,
            // TCS Construction Rules
            'cabinet_type' => $parsedData['cabinet']['cabinet_type'] ?? 'base',
            'is_sink_location' => $parsedData['cabinet']['is_sink_location'] ?? false,
        ];
    }

    /**
     * Run math audit validation on a cabinet model
     *
     * @param Cabinet $cabinet The cabinet to audit
     * @return array Full audit result
     */
    protected function runMathAudit(Cabinet $cabinet): array
    {
        return $this->mathAudit->generateFullAudit($cabinet);
    }

    /**
     * Generate complete cut list from cabinet and components
     *
     * @param Cabinet $cabinet The cabinet with loaded relationships
     * @return array Complete cut list organized by material type
     */
    public function generateCutList(Cabinet $cabinet): array
    {
        // Load all relationships
        $cabinet->load(['sections.drawers', 'sections.falseFronts', 'stretchers']);

        $cutList = [
            'project_info' => [
                'cabinet_code' => $cabinet->full_code,
                'cabinet_type' => $cabinet->cabinet_type,
                'dimensions' => sprintf('%.4f" W × %.4f" H × %.4f" D',
                    $cabinet->length_inches,
                    $cabinet->height_inches,
                    $cabinet->depth_inches
                ),
            ],
            'cabinet_box' => $this->generateBoxCutList($cabinet),
            'stretchers' => $this->generateStretcherCutList($cabinet),
            'face_frame' => $this->generateFaceFrameCutList($cabinet),
            'false_fronts' => $this->generateFalseFrontCutList($cabinet),
            'drawer_boxes' => $this->generateDrawerCutList($cabinet),
        ];

        return $cutList;
    }

    /**
     * Generate cabinet box cut list
     */
    protected function generateBoxCutList(Cabinet $cabinet): array
    {
        $insideWidth = $cabinet->length_inches - (2 * ($cabinet->side_panel_thickness ?? self::PLYWOOD_3_4));
        $boxHeight = $cabinet->height_inches - ($cabinet->toe_kick_height ?? self::TCS_TOE_KICK_HEIGHT);
        $insideDepth = $cabinet->depth_inches - ($cabinet->back_panel_thickness ?? self::PLYWOOD_3_4);

        return [
            'material' => '3/4" Plywood',
            'pieces' => [
                [
                    'part' => 'Left Side',
                    'qty' => 1,
                    'width' => $insideDepth,
                    'length' => $boxHeight,
                    'thickness' => self::PLYWOOD_3_4,
                    'notes' => 'Depth × Box Height',
                ],
                [
                    'part' => 'Right Side',
                    'qty' => 1,
                    'width' => $insideDepth,
                    'length' => $boxHeight,
                    'thickness' => self::PLYWOOD_3_4,
                    'notes' => 'Depth × Box Height',
                ],
                [
                    'part' => 'Bottom',
                    'qty' => 1,
                    'width' => $insideWidth,
                    'length' => $insideDepth,
                    'thickness' => self::PLYWOOD_3_4,
                    'notes' => 'Inside Width × Depth',
                ],
                [
                    'part' => 'Back',
                    'qty' => 1,
                    'width' => $insideWidth,
                    'length' => $boxHeight,
                    'thickness' => self::PLYWOOD_3_4,
                    'notes' => 'Inside Width × Box Height (TCS full 3/4" back)',
                ],
            ],
        ];
    }

    /**
     * Generate stretcher cut list
     */
    protected function generateStretcherCutList(Cabinet $cabinet): array
    {
        $pieces = [];

        foreach ($cabinet->stretchers as $stretcher) {
            $pieces[] = [
                'part' => "{$stretcher->position} Stretcher #{$stretcher->stretcher_number}",
                'qty' => 1,
                'width' => $stretcher->depth_inches,
                'length' => $stretcher->width_inches,
                'thickness' => $stretcher->thickness_inches,
                'notes' => $stretcher->position === Stretcher::POSITION_DRAWER_SUPPORT
                    ? "Supports drawer above - {$stretcher->position_from_top_inches}\" from top"
                    : 'TCS 3" standard',
            ];
        }

        return [
            'material' => '3/4" Plywood',
            'pieces' => $pieces,
        ];
    }

    /**
     * Generate face frame cut list
     */
    protected function generateFaceFrameCutList(Cabinet $cabinet): array
    {
        $stileWidth = $cabinet->face_frame_stile_width ?? self::TCS_FACE_FRAME_STILE;
        $railWidth = $cabinet->face_frame_rail_width ?? self::TCS_FACE_FRAME_RAIL;
        $boxHeight = $cabinet->height_inches - ($cabinet->toe_kick_height ?? self::TCS_TOE_KICK_HEIGHT);
        $railLength = $cabinet->length_inches - (2 * $stileWidth);

        return [
            'material' => '3/4" Hardwood/MDF',
            'pieces' => [
                [
                    'part' => 'Stiles',
                    'qty' => 2,
                    'width' => $stileWidth,
                    'length' => $boxHeight,
                    'thickness' => self::PLYWOOD_3_4,
                    'notes' => 'Left and Right stiles',
                ],
                [
                    'part' => 'Top Rail',
                    'qty' => 1,
                    'width' => $railWidth,
                    'length' => $railLength,
                    'thickness' => self::PLYWOOD_3_4,
                    'notes' => 'Top rail',
                ],
                [
                    'part' => 'Bottom Rail',
                    'qty' => 1,
                    'width' => $railWidth,
                    'length' => $railLength,
                    'thickness' => self::PLYWOOD_3_4,
                    'notes' => 'Bottom rail',
                ],
            ],
        ];
    }

    /**
     * Generate false front cut list
     */
    protected function generateFalseFrontCutList(Cabinet $cabinet): array
    {
        $pieces = [];

        // Collect false fronts from all sections
        foreach ($cabinet->sections as $section) {
            foreach ($section->falseFronts as $ff) {
                // Get cut list data from model
                if (method_exists($ff, 'getCutListDataAttribute')) {
                    foreach ($ff->cut_list_data as $part) {
                        $pieces[] = $part;
                    }
                } else {
                    // Fallback
                    $pieces[] = [
                        'part' => 'False Front Panel',
                        'qty' => 1,
                        'width' => $ff->width_inches,
                        'length' => $ff->height_inches,
                        'thickness' => $ff->thickness_inches ?? self::PLYWOOD_3_4,
                        'notes' => $ff->false_front_type ?? 'fixed',
                    ];

                    if ($ff->has_backing) {
                        $pieces[] = [
                            'part' => 'Backing/Stretcher',
                            'qty' => 1,
                            'width' => $ff->width_inches,
                            'length' => $ff->backing_height_inches,
                            'thickness' => $ff->backing_thickness_inches ?? self::PLYWOOD_3_4,
                            'notes' => 'TCS: Backing serves as stretcher',
                        ];
                    }
                }
            }
        }

        // Also check direct relationship
        if (method_exists($cabinet, 'falseFronts')) {
            try {
                foreach ($cabinet->falseFronts as $ff) {
                    if (method_exists($ff, 'getCutListDataAttribute')) {
                        foreach ($ff->cut_list_data as $part) {
                            $pieces[] = $part;
                        }
                    }
                }
            } catch (\Exception $e) {
                // No direct relationship
            }
        }

        return [
            'material' => '3/4" Panel + 3/4" Plywood (backing)',
            'pieces' => $pieces,
        ];
    }

    /**
     * Generate drawer box cut list
     */
    protected function generateDrawerCutList(Cabinet $cabinet): array
    {
        $pieces = [];

        // Collect drawers from all sections
        foreach ($cabinet->sections as $section) {
            foreach ($section->drawers as $drawer) {
                if (method_exists($drawer, 'getCutListDataAttribute')) {
                    foreach ($drawer->cut_list_data as $part) {
                        $pieces[] = $part;
                    }
                } else {
                    // Fallback - basic drawer box parts
                    $num = $drawer->drawer_number;
                    $boxHeight = $drawer->box_height_shop_inches ?? $drawer->box_height_inches;
                    $boxDepth = $drawer->box_depth_shop_inches ?? $drawer->box_depth_inches;
                    $boxWidth = $drawer->box_width_inches;

                    $pieces[] = [
                        'part' => "Drawer {$num} Sides",
                        'qty' => 2,
                        'width' => $boxHeight,
                        'length' => $boxDepth,
                        'thickness' => self::PLYWOOD_1_2,
                        'notes' => 'Dovetail tails',
                    ];

                    $frontBackWidth = $boxWidth - (2 * self::PLYWOOD_1_2);
                    $pieces[] = [
                        'part' => "Drawer {$num} Front/Back",
                        'qty' => 2,
                        'width' => $boxHeight,
                        'length' => $frontBackWidth,
                        'thickness' => self::PLYWOOD_1_2,
                        'notes' => 'Dovetail pins',
                    ];

                    $pieces[] = [
                        'part' => "Drawer {$num} Bottom",
                        'qty' => 1,
                        'width' => $frontBackWidth,
                        'length' => $boxDepth - 0.75,
                        'thickness' => self::PLYWOOD_1_4,
                        'notes' => 'In dado groove',
                    ];
                }
            }
        }

        return [
            'material' => '1/2" Plywood (sides), 1/4" Plywood (bottom)',
            'pieces' => $pieces,
        ];
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Round to nearest 1/16" (shop standard)
     */
    public static function roundToSixteenth(float $inches): float
    {
        return round($inches * 16) / 16;
    }

    /**
     * Format inches as fractional string
     */
    public static function formatInches(float $inches): string
    {
        $whole = floor($inches);
        $fraction = $inches - $whole;

        if ($fraction < 0.03125) {
            return $whole . '"';
        }

        $sixteenths = round($fraction * 16);
        if ($sixteenths == 16) {
            return ($whole + 1) . '"';
        }

        // Reduce fraction
        $numerator = $sixteenths;
        $denominator = 16;
        while ($numerator % 2 == 0 && $denominator > 1) {
            $numerator /= 2;
            $denominator /= 2;
        }

        return $whole > 0 ? "{$whole}-{$numerator}/{$denominator}\"" : "{$numerator}/{$denominator}\"";
    }
}
