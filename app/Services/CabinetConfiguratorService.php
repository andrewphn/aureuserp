<?php

namespace App\Services;

use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetSection;
use Illuminate\Support\Collection;

/**
 * Cabinet Configurator Service
 *
 * Manages cabinet-level configuration including:
 * - Face frame vs frameless construction
 * - Section layout and opening calculations
 * - Bi-directional face frame ↔ opening calculations
 * - Template system for common cabinet configurations
 * - TCS construction standards (Bryan Patton, Jan 2025)
 *
 * @see docs/OPENING_CONFIGURATOR_SYSTEM.md
 */
class CabinetConfiguratorService
{
    // ===== TCS CONSTRUCTION STANDARDS (Bryan Patton, Jan 2025) =====

    /**
     * TCS Standard Heights
     * "Cabinets for kitchens are normally 34, 3 quarter tall,
     * because countertops are typically an inch of quarter"
     */
    public const TCS_BASE_CABINET_HEIGHT = 34.75;    // 34 3/4"
    public const TCS_COUNTERTOP_HEIGHT = 1.25;       // 1 1/4"
    public const TCS_FINISHED_COUNTER_HEIGHT = 36.0; // 36" final height

    /**
     * TCS Standard Toe Kick
     * "Toe kick standard is 4.5 inches tall"
     * "3 inches from the face" (recess)
     */
    public const TCS_TOE_KICK_HEIGHT = 4.5;  // 4 1/2"
    public const TCS_TOE_KICK_RECESS = 3.0;  // 3"

    /**
     * TCS Standard Stretchers
     * "3 inch stretchers"
     */
    public const TCS_STRETCHER_HEIGHT = 3.0; // 3"

    /**
     * TCS Standard Materials
     * "Our cabinets are built out of 3 quarter prefinished maple plywood"
     */
    public const TCS_BOX_MATERIAL = '3/4 prefinished maple plywood';
    public const TCS_BOX_THICKNESS = 0.75;      // 3/4"
    public const TCS_BACK_THICKNESS = 0.75;     // 3/4" (TCS uses full thickness)

    /**
     * TCS Sink Cabinet Side Extension
     * "At sink locations... sides will come up an additional 3/4 of an inch"
     */
    public const TCS_SINK_SIDE_EXTENSION = 0.75; // 3/4"

    // ===== FACE FRAME CONSTANTS =====

    /**
     * TCS Face Frame Dimensions
     * "Face frame... typically is an inch and a half or inch of 3 quarter,
     * then you have an 8th inch gap to your door"
     */
    /** Default stile width (1.5") */
    public const DEFAULT_STILE_WIDTH_INCHES = 1.5;

    /** Default rail width (1.5") */
    public const DEFAULT_RAIL_WIDTH_INCHES = 1.5;

    /** TCS Standard door gap (1/8") */
    public const TCS_DOOR_GAP = 0.125; // 1/8"

    /** Minimum stile/rail width */
    public const MIN_FRAME_MEMBER_WIDTH_INCHES = 1.0;

    /** Maximum stile/rail width */
    public const MAX_FRAME_MEMBER_WIDTH_INCHES = 3.0;

    // ===== CONSTRUCTION TYPES =====

    public const CONSTRUCTION_FACE_FRAME = 'face_frame';
    public const CONSTRUCTION_FRAMELESS = 'frameless';
    public const CONSTRUCTION_HYBRID = 'hybrid';

    // ===== TOP CONSTRUCTION TYPES =====

    public const TOP_STRETCHERS = 'stretchers';  // Base cabinets
    public const TOP_FULL_TOP = 'full_top';      // Wall cabinets
    public const TOP_NONE = 'none';              // Open-top cabinets

    // ===== SECTION LAYOUT TYPES =====

    public const LAYOUT_HORIZONTAL = 'horizontal';
    public const LAYOUT_VERTICAL = 'vertical';

    // ===== INTELLIGENT DEFAULT RATIOS =====

    /** Width ratio for drawer banks (40% of available opening) */
    public const DEFAULT_DRAWER_BANK_RATIO = 0.40;

    /** Width ratio for doors (60% of available opening) */
    public const DEFAULT_DOOR_RATIO = 0.60;

    /** Width ratio for equal sections */
    public const DEFAULT_EQUAL_RATIO = 0.50;

    /**
     * Available section templates for common cabinet configurations
     */
    public static function getTemplates(): array
    {
        return [
            'single_door' => [
                'name' => 'Single Door',
                'description' => 'One full-height door covering the opening',
                'sections' => [
                    ['type' => 'door', 'ratio' => 1.0],
                ],
            ],
            'double_door' => [
                'name' => 'Double Door (Pair)',
                'description' => 'Two equal doors side by side',
                'sections' => [
                    ['type' => 'door', 'ratio' => 0.5],
                    ['type' => 'door', 'ratio' => 0.5],
                ],
            ],
            'drawer_bank' => [
                'name' => 'Drawer Bank',
                'description' => 'Full-width drawer bank',
                'sections' => [
                    ['type' => 'drawer_bank', 'ratio' => 1.0],
                ],
            ],
            'drawer_door' => [
                'name' => 'Drawers + Door',
                'description' => 'Drawer bank on left, door on right',
                'sections' => [
                    ['type' => 'drawer_bank', 'ratio' => self::DEFAULT_DRAWER_BANK_RATIO],
                    ['type' => 'door', 'ratio' => self::DEFAULT_DOOR_RATIO],
                ],
            ],
            'door_drawer' => [
                'name' => 'Door + Drawers',
                'description' => 'Door on left, drawer bank on right',
                'sections' => [
                    ['type' => 'door', 'ratio' => self::DEFAULT_DOOR_RATIO],
                    ['type' => 'drawer_bank', 'ratio' => self::DEFAULT_DRAWER_BANK_RATIO],
                ],
            ],
            'sink_base' => [
                'name' => 'Sink Base',
                'description' => 'False front with double doors below',
                'sections' => [
                    ['type' => 'false_front', 'ratio' => 1.0, 'height_ratio' => 0.15], // Top false front
                    ['type' => 'door', 'ratio' => 0.5, 'height_ratio' => 0.85],        // Left door
                    ['type' => 'door', 'ratio' => 0.5, 'height_ratio' => 0.85],        // Right door
                ],
                'has_vertical_divisions' => true,
            ],
            'three_drawer' => [
                'name' => 'Three-Drawer Base',
                'description' => 'Three equal drawers stacked',
                'sections' => [
                    ['type' => 'drawer_bank', 'ratio' => 1.0, 'drawer_count' => 3],
                ],
            ],
            'four_drawer' => [
                'name' => 'Four-Drawer Base',
                'description' => 'Four equal drawers stacked',
                'sections' => [
                    ['type' => 'drawer_bank', 'ratio' => 1.0, 'drawer_count' => 4],
                ],
            ],
            'open_shelf' => [
                'name' => 'Open Shelves',
                'description' => 'Open shelving, no doors',
                'sections' => [
                    ['type' => 'open_shelf', 'ratio' => 1.0],
                ],
            ],
            'mixed_top_drawer' => [
                'name' => 'Drawer Over Doors',
                'description' => 'Top drawer with double doors below',
                'sections' => [
                    ['type' => 'drawer_bank', 'ratio' => 1.0, 'height_ratio' => 0.25, 'drawer_count' => 1],
                    ['type' => 'door', 'ratio' => 0.5, 'height_ratio' => 0.75],
                    ['type' => 'door', 'ratio' => 0.5, 'height_ratio' => 0.75],
                ],
                'has_vertical_divisions' => true,
            ],
        ];
    }

    /**
     * Calculate face frame dimensions from cabinet dimensions
     *
     * TCS Standard (Bryan Patton, Jan 2025):
     * "Face frame... typically is an inch and a half or inch of 3 quarter,
     * then you have an 8th inch gap to your door"
     *
     * @param Cabinet $cabinet The cabinet to calculate for
     * @return array Face frame dimensions
     */
    public function calculateFaceFrameDimensions(Cabinet $cabinet): array
    {
        $cabinetWidth = $cabinet->length_inches ?? 36;
        $cabinetHeight = $cabinet->height_inches ?? 30;

        $stileWidth = $cabinet->face_frame_stile_width_inches ?? self::DEFAULT_STILE_WIDTH_INCHES;
        $railWidth = $cabinet->face_frame_rail_width_inches ?? self::DEFAULT_RAIL_WIDTH_INCHES;
        $midStileCount = $cabinet->face_frame_mid_stile_count ?? 0;
        $doorGap = $cabinet->face_frame_door_gap_inches ?? self::TCS_DOOR_GAP;

        // Calculate total frame consumption
        $totalStilesWidth = (2 + $midStileCount) * $stileWidth;
        $totalRailsHeight = 2 * $railWidth;

        // Available opening dimensions
        $totalOpeningWidth = $cabinetWidth - $totalStilesWidth;
        $totalOpeningHeight = $cabinetHeight - $totalRailsHeight;

        // Door dimensions (opening minus gap on each side)
        $doorOpeningWidth = $totalOpeningWidth - (2 * $doorGap);
        $doorOpeningHeight = $totalOpeningHeight - (2 * $doorGap);

        return [
            'cabinet_width' => $cabinetWidth,
            'cabinet_height' => $cabinetHeight,
            'stile_width' => $stileWidth,
            'rail_width' => $railWidth,
            'mid_stile_count' => $midStileCount,
            'left_stile_width' => $stileWidth,
            'right_stile_width' => $stileWidth,
            'top_rail_width' => $railWidth,
            'bottom_rail_width' => $railWidth,
            'total_frame_width_consumed' => $totalStilesWidth,
            'total_frame_height_consumed' => $totalRailsHeight,
            'total_opening_width' => $totalOpeningWidth,
            'total_opening_height' => $totalOpeningHeight,
            'door_gap' => $doorGap,
            'door_opening_width' => $doorOpeningWidth,
            'door_opening_height' => $doorOpeningHeight,
            'opening_count' => max(1, $midStileCount + 1),
        ];
    }

    /**
     * Calculate available interior height for a cabinet
     *
     * TCS Standard (Bryan Patton, Jan 2025):
     * Interior height = Cabinet height - Toe kick - Stretcher height
     *
     * For base cabinets with stretchers:
     * 34.75" - 4.5" (toe kick) - 3" (stretcher) = 27.25" interior
     *
     * @param Cabinet $cabinet The cabinet to calculate for
     * @return array Interior dimensions and construction breakdown
     */
    public function calculateInteriorDimensions(Cabinet $cabinet): array
    {
        $cabinetHeight = $cabinet->height_inches ?? self::TCS_BASE_CABINET_HEIGHT;
        $cabinetDepth = $cabinet->depth_inches ?? 24;
        $cabinetWidth = $cabinet->length_inches ?? 36;

        // Get toe kick dimensions
        $toeKickHeight = $cabinet->toe_kick_height ?? self::TCS_TOE_KICK_HEIGHT;
        $toeKickRecess = $cabinet->toe_kick_depth ?? self::TCS_TOE_KICK_RECESS;

        // Get stretcher height (only for cabinets with stretchers)
        $topConstructionType = $cabinet->top_construction_type ?? self::TOP_STRETCHERS;
        $stretcherHeight = 0;
        if ($topConstructionType === self::TOP_STRETCHERS) {
            $stretcherHeight = $cabinet->stretcher_height_inches ?? self::TCS_STRETCHER_HEIGHT;
        }

        // Calculate interior height
        $interiorHeight = $cabinetHeight - $toeKickHeight - $stretcherHeight;

        // Interior width (minus side panels, typically 3/4" each)
        $sideThickness = self::TCS_BOX_THICKNESS;
        $interiorWidth = $cabinetWidth - (2 * $sideThickness);

        // Interior depth (full depth minus back thickness)
        $backThickness = self::TCS_BACK_THICKNESS;
        $interiorDepth = $cabinetDepth - $backThickness;

        // Check for sink cabinet side extension
        $sinkSideExtension = 0;
        if ($cabinet->sink_requires_extended_sides) {
            $sinkSideExtension = $cabinet->sink_side_extension_inches ?? self::TCS_SINK_SIDE_EXTENSION;
        }

        return [
            'cabinet_height' => $cabinetHeight,
            'cabinet_width' => $cabinetWidth,
            'cabinet_depth' => $cabinetDepth,
            'toe_kick_height' => $toeKickHeight,
            'toe_kick_recess' => $toeKickRecess,
            'stretcher_height' => $stretcherHeight,
            'top_construction_type' => $topConstructionType,
            'interior_height' => $interiorHeight,
            'interior_width' => $interiorWidth,
            'interior_depth' => $interiorDepth,
            'side_thickness' => $sideThickness,
            'back_thickness' => $backThickness,
            'sink_side_extension' => $sinkSideExtension,
            'effective_side_height' => $cabinetHeight - $toeKickHeight + $sinkSideExtension,
        ];
    }

    /**
     * Set box material from a Product
     *
     * Links the cabinet to a specific box material product (sheet goods)
     * and can auto-populate material details from product attributes.
     *
     * @param Cabinet $cabinet The cabinet to update
     * @param \Webkul\Product\Models\Product $product The box material product
     * @return Cabinet The updated cabinet
     */
    public function setBoxMaterial(Cabinet $cabinet, \Webkul\Product\Models\Product $product): Cabinet
    {
        $cabinet->box_material_product_id = $product->id;

        // Optionally update box_material description from product name
        if (empty($cabinet->box_material)) {
            $cabinet->box_material = $product->name;
        }

        $cabinet->save();

        return $cabinet;
    }

    /**
     * Set face frame material from a Product
     *
     * @param Cabinet $cabinet The cabinet to update
     * @param \Webkul\Product\Models\Product $product The face frame lumber product
     * @return Cabinet The updated cabinet
     */
    public function setFaceFrameMaterial(Cabinet $cabinet, \Webkul\Product\Models\Product $product): Cabinet
    {
        $cabinet->face_frame_material_product_id = $product->id;
        $cabinet->save();

        return $cabinet;
    }

    /**
     * Apply TCS default construction settings to a cabinet
     *
     * Sets all construction fields to TCS standards based on cabinet type.
     *
     * @param Cabinet $cabinet The cabinet to configure
     * @param string $cabinetType 'base', 'wall', 'tall', 'sink', etc.
     * @return Cabinet The configured cabinet
     */
    public function applyTcsDefaults(Cabinet $cabinet, string $cabinetType = 'base'): Cabinet
    {
        // Common TCS defaults
        $cabinet->face_frame_stile_width_inches = self::DEFAULT_STILE_WIDTH_INCHES;
        $cabinet->face_frame_rail_width_inches = self::DEFAULT_RAIL_WIDTH_INCHES;
        $cabinet->face_frame_door_gap_inches = self::TCS_DOOR_GAP;
        $cabinet->stretcher_height_inches = self::TCS_STRETCHER_HEIGHT;

        // Type-specific settings
        switch (strtolower($cabinetType)) {
            case 'base':
            case 'sink':
            case 'sink_base':
            case 'drawer_base':
                $cabinet->top_construction_type = self::TOP_STRETCHERS;
                $cabinet->height_inches = $cabinet->height_inches ?? self::TCS_BASE_CABINET_HEIGHT;
                break;

            case 'wall':
            case 'wall_corner':
                $cabinet->top_construction_type = self::TOP_FULL_TOP;
                $cabinet->stretcher_height_inches = 0; // Wall cabinets don't have stretchers
                break;

            case 'tall':
            case 'tall_pantry':
            case 'pantry':
                $cabinet->top_construction_type = self::TOP_STRETCHERS;
                $cabinet->height_inches = $cabinet->height_inches ?? 84;
                break;
        }

        // Sink cabinet side extension
        if (in_array(strtolower($cabinetType), ['sink', 'sink_base'])) {
            $cabinet->sink_requires_extended_sides = true;
            $cabinet->sink_side_extension_inches = self::TCS_SINK_SIDE_EXTENSION;
        }

        // Set toe kick defaults if not set
        if (empty($cabinet->toe_kick_height)) {
            $cabinet->toe_kick_height = self::TCS_TOE_KICK_HEIGHT;
        }
        if (empty($cabinet->toe_kick_depth)) {
            $cabinet->toe_kick_depth = self::TCS_TOE_KICK_RECESS;
        }

        $cabinet->save();

        return $cabinet;
    }

    /**
     * Calculate face frame dimensions from desired opening sizes
     * (Reverse calculation - user specifies opening, we calculate frame)
     *
     * @param float $desiredOpeningWidth Desired total opening width
     * @param float $desiredOpeningHeight Desired total opening height
     * @param int $openingCount Number of openings (determines mid-stiles)
     * @param float $stileWidth Stile width to use
     * @param float $railWidth Rail width to use
     * @return array Required cabinet dimensions
     */
    public function calculateCabinetFromOpenings(
        float $desiredOpeningWidth,
        float $desiredOpeningHeight,
        int $openingCount = 1,
        float $stileWidth = self::DEFAULT_STILE_WIDTH_INCHES,
        float $railWidth = self::DEFAULT_RAIL_WIDTH_INCHES
    ): array {
        $midStileCount = max(0, $openingCount - 1);

        // Calculate total frame consumption
        $totalStilesWidth = (2 + $midStileCount) * $stileWidth;
        $totalRailsHeight = 2 * $railWidth;

        // Required cabinet dimensions
        $requiredCabinetWidth = $desiredOpeningWidth + $totalStilesWidth;
        $requiredCabinetHeight = $desiredOpeningHeight + $totalRailsHeight;

        return [
            'required_cabinet_width' => $requiredCabinetWidth,
            'required_cabinet_height' => $requiredCabinetHeight,
            'stile_width' => $stileWidth,
            'rail_width' => $railWidth,
            'mid_stile_count' => $midStileCount,
            'opening_count' => $openingCount,
            'total_opening_width' => $desiredOpeningWidth,
            'total_opening_height' => $desiredOpeningHeight,
        ];
    }

    /**
     * Calculate opening sizes for sections
     *
     * @param Cabinet $cabinet The cabinet
     * @param array $sectionRatios Array of section ratios (e.g., [0.4, 0.6])
     * @return array Array of opening dimensions
     */
    public function calculateOpeningSizes(Cabinet $cabinet, array $sectionRatios = []): array
    {
        $frameDimensions = $this->calculateFaceFrameDimensions($cabinet);
        $totalOpeningWidth = $frameDimensions['total_opening_width'];
        $totalOpeningHeight = $frameDimensions['total_opening_height'];

        // If no ratios provided, calculate from existing sections
        if (empty($sectionRatios)) {
            $sections = $cabinet->sections()->orderBy('sort_order')->get();
            if ($sections->count() > 0) {
                $sectionRatios = $sections->pluck('section_width_ratio')->toArray();
            } else {
                $sectionRatios = [1.0]; // Single opening
            }
        }

        // Normalize ratios
        $totalRatio = array_sum($sectionRatios);
        if ($totalRatio <= 0) {
            $totalRatio = 1;
        }

        $openings = [];
        $stileWidth = $frameDimensions['stile_width'];
        $midStileCount = count($sectionRatios) - 1;

        // Adjust for mid-stiles
        $availableWidth = $totalOpeningWidth - ($midStileCount * $stileWidth);

        $positionFromLeft = $stileWidth; // Start after left stile

        foreach ($sectionRatios as $index => $ratio) {
            $normalizedRatio = $ratio / $totalRatio;
            $openingWidth = $availableWidth * $normalizedRatio;

            $openings[] = [
                'index' => $index,
                'ratio' => $normalizedRatio,
                'width' => $openingWidth,
                'height' => $totalOpeningHeight,
                'position_from_left' => $positionFromLeft,
                'position_from_bottom' => $frameDimensions['bottom_rail_width'],
            ];

            // Move position for next opening (width + mid-stile if not last)
            $positionFromLeft += $openingWidth;
            if ($index < count($sectionRatios) - 1) {
                $positionFromLeft += $stileWidth;
            }
        }

        return [
            'frame' => $frameDimensions,
            'openings' => $openings,
            'mid_stile_count' => $midStileCount,
        ];
    }

    /**
     * Apply a template to a cabinet
     *
     * @param Cabinet $cabinet The cabinet to configure
     * @param string $templateKey The template key to apply
     * @return array Result with created sections
     */
    public function applyTemplate(Cabinet $cabinet, string $templateKey): array
    {
        $templates = self::getTemplates();

        if (!isset($templates[$templateKey])) {
            return [
                'success' => false,
                'error' => "Unknown template: {$templateKey}",
            ];
        }

        $template = $templates[$templateKey];
        $frameDimensions = $this->calculateFaceFrameDimensions($cabinet);

        // Delete existing sections if any
        $cabinet->sections()->delete();

        // Calculate section ratios from template
        $sectionRatios = array_column($template['sections'], 'ratio');
        $openingSizes = $this->calculateOpeningSizes($cabinet, $sectionRatios);

        $createdSections = [];
        $sortOrder = 1;

        // Handle vertical divisions (like sink base with false front above doors)
        $hasVerticalDivisions = $template['has_vertical_divisions'] ?? false;

        if ($hasVerticalDivisions) {
            // More complex layout - process by height groups
            $createdSections = $this->createVerticallyDividedSections($cabinet, $template, $frameDimensions);
        } else {
            // Simple horizontal layout
            foreach ($template['sections'] as $index => $sectionDef) {
                $opening = $openingSizes['openings'][$index] ?? null;

                if (!$opening) {
                    continue;
                }

                $section = $this->createSection($cabinet, [
                    'section_type' => $sectionDef['type'],
                    'sort_order' => $sortOrder,
                    'opening_width_inches' => $opening['width'],
                    'opening_height_inches' => $opening['height'],
                    'position_from_left_inches' => $opening['position_from_left'],
                    'position_from_bottom_inches' => $opening['position_from_bottom'],
                    'section_width_ratio' => $sectionDef['ratio'],
                ]);

                $createdSections[] = $section;
                $sortOrder++;
            }
        }

        // Update cabinet mid-stile count
        $cabinet->face_frame_mid_stile_count = count($createdSections) > 1 ? count($createdSections) - 1 : 0;
        $cabinet->save();

        return [
            'success' => true,
            'template' => $templateKey,
            'sections' => $createdSections,
            'frame_dimensions' => $frameDimensions,
        ];
    }

    /**
     * Create sections with vertical divisions (like sink base)
     */
    protected function createVerticallyDividedSections(Cabinet $cabinet, array $template, array $frameDimensions): array
    {
        $totalOpeningHeight = $frameDimensions['total_opening_height'];
        $totalOpeningWidth = $frameDimensions['total_opening_width'];
        $stileWidth = $frameDimensions['stile_width'];

        $createdSections = [];
        $sortOrder = 1;

        // Group sections by their height_ratio (same height_ratio means same row)
        $rowGroups = [];
        foreach ($template['sections'] as $index => $sectionDef) {
            $heightRatio = $sectionDef['height_ratio'] ?? 1.0;
            $rowKey = (string) $heightRatio;

            if (!isset($rowGroups[$rowKey])) {
                $rowGroups[$rowKey] = [
                    'height_ratio' => $heightRatio,
                    'sections' => [],
                ];
            }
            $rowGroups[$rowKey]['sections'][] = $sectionDef;
        }

        // Sort by height ratio descending (top rows first if top has smaller ratio)
        // For sink base: false front (0.15) is top, doors (0.85) are bottom
        $rowGroups = array_values($rowGroups);
        usort($rowGroups, function ($a, $b) {
            return $a['height_ratio'] <=> $b['height_ratio'];
        });

        $positionFromBottom = $frameDimensions['bottom_rail_width'];

        // Process from bottom to top (lower height_ratio first for sink base false front at top)
        foreach (array_reverse($rowGroups) as $rowGroup) {
            $rowHeight = $totalOpeningHeight * $rowGroup['height_ratio'];
            $rowSections = $rowGroup['sections'];

            // Calculate ratios for this row
            $rowRatios = array_column($rowSections, 'ratio');
            $totalRowRatio = array_sum($rowRatios);

            // Account for mid-stiles in this row
            $midStilesInRow = count($rowSections) - 1;
            $availableRowWidth = $totalOpeningWidth - ($midStilesInRow * $stileWidth);

            $positionFromLeft = $stileWidth; // Start after left stile

            foreach ($rowSections as $sectionDef) {
                $normalizedRatio = $sectionDef['ratio'] / $totalRowRatio;
                $sectionWidth = $availableRowWidth * $normalizedRatio;

                $section = $this->createSection($cabinet, [
                    'section_type' => $sectionDef['type'],
                    'sort_order' => $sortOrder,
                    'opening_width_inches' => $sectionWidth,
                    'opening_height_inches' => $rowHeight,
                    'position_from_left_inches' => $positionFromLeft,
                    'position_from_bottom_inches' => $positionFromBottom,
                    'section_width_ratio' => $sectionDef['ratio'],
                ]);

                $createdSections[] = $section;
                $sortOrder++;

                $positionFromLeft += $sectionWidth + $stileWidth;
            }

            $positionFromBottom += $rowHeight;
        }

        return $createdSections;
    }

    /**
     * Create a section for a cabinet
     *
     * @param Cabinet $cabinet The parent cabinet
     * @param array $data Section data
     * @return CabinetSection The created section
     */
    public function createSection(Cabinet $cabinet, array $data): CabinetSection
    {
        $sectionNumber = ($cabinet->sections()->max('section_number') ?? 0) + 1;

        return CabinetSection::create(array_merge([
            'cabinet_id' => $cabinet->id,
            'section_number' => $sectionNumber,
            'name' => ucfirst(str_replace('_', ' ', $data['section_type'] ?? 'door')) . ' ' . $sectionNumber,
        ], $data));
    }

    /**
     * Update a section
     *
     * @param CabinetSection $section The section to update
     * @param array $data Updated data
     * @return CabinetSection The updated section
     */
    public function updateSection(CabinetSection $section, array $data): CabinetSection
    {
        $section->update($data);
        return $section->fresh();
    }

    /**
     * Delete a section
     *
     * @param CabinetSection $section The section to delete
     * @return bool Success
     */
    public function deleteSection(CabinetSection $section): bool
    {
        // Delete child components first
        $section->drawers()->delete();
        $section->doors()->delete();
        $section->shelves()->delete();
        $section->pullouts()->delete();
        $section->falseFronts()->delete();

        return $section->delete();
    }

    /**
     * Reorder sections within a cabinet
     *
     * @param Cabinet $cabinet The cabinet
     * @param array $sectionIds Ordered array of section IDs
     * @return Collection Updated sections
     */
    public function reorderSections(Cabinet $cabinet, array $sectionIds): Collection
    {
        $sortOrder = 1;
        $sections = collect();

        foreach ($sectionIds as $sectionId) {
            $section = CabinetSection::find($sectionId);
            if ($section && $section->cabinet_id === $cabinet->id) {
                $section->sort_order = $sortOrder;
                $section->section_number = $sortOrder;
                $section->save();
                $sections->push($section);
                $sortOrder++;
            }
        }

        // Recalculate positions
        $this->recalculateSectionPositions($cabinet);

        return $sections;
    }

    /**
     * Recalculate section positions after changes
     *
     * @param Cabinet $cabinet The cabinet
     * @return void
     */
    public function recalculateSectionPositions(Cabinet $cabinet): void
    {
        $cabinet->load('sections');
        $frameDimensions = $this->calculateFaceFrameDimensions($cabinet);
        $stileWidth = $frameDimensions['stile_width'];

        $sections = $cabinet->sections()->orderBy('sort_order')->get();

        if ($sections->isEmpty()) {
            return;
        }

        // Get ratios
        $ratios = $sections->pluck('section_width_ratio')->toArray();
        $totalRatio = array_sum($ratios);
        if ($totalRatio <= 0) {
            $totalRatio = $sections->count();
            $ratios = array_fill(0, $sections->count(), 1.0 / $sections->count());
        }

        // Calculate available width
        $midStileCount = $sections->count() - 1;
        $availableWidth = $frameDimensions['total_opening_width'] - ($midStileCount * $stileWidth);

        $positionFromLeft = $stileWidth;

        foreach ($sections as $index => $section) {
            $ratio = $ratios[$index] ?? (1.0 / $sections->count());
            $normalizedRatio = $ratio / $totalRatio;
            $sectionWidth = $availableWidth * $normalizedRatio;

            $section->update([
                'position_from_left_inches' => $positionFromLeft,
                'position_from_bottom_inches' => $frameDimensions['bottom_rail_width'],
                'opening_width_inches' => $sectionWidth,
                'opening_height_inches' => $frameDimensions['total_opening_height'],
            ]);

            $positionFromLeft += $sectionWidth + $stileWidth;
        }

        // Update cabinet mid-stile count
        $cabinet->update([
            'face_frame_mid_stile_count' => $midStileCount,
        ]);
    }

    /**
     * Validate the cabinet section layout
     *
     * @param Cabinet $cabinet The cabinet to validate
     * @return array Validation result
     */
    public function validateLayout(Cabinet $cabinet): array
    {
        $errors = [];
        $warnings = [];

        $cabinet->load('sections');
        $frameDimensions = $this->calculateFaceFrameDimensions($cabinet);

        // Check if sections fit within cabinet
        $totalSectionWidth = 0;
        $stileWidth = $frameDimensions['stile_width'];

        foreach ($cabinet->sections as $section) {
            $totalSectionWidth += $section->opening_width_inches ?? 0;
        }

        // Add mid-stiles
        $midStileCount = max(0, $cabinet->sections->count() - 1);
        $totalConsumed = $totalSectionWidth + ($midStileCount * $stileWidth);

        if ($totalConsumed > $frameDimensions['total_opening_width'] + 0.0625) { // Allow 1/16" tolerance
            $errors[] = sprintf(
                'Sections exceed available opening width: %.4f" used, %.4f" available',
                $totalConsumed,
                $frameDimensions['total_opening_width']
            );
        }

        // Check for overlapping sections
        $sections = $cabinet->sections()->orderBy('position_from_left_inches')->get();
        $lastEnd = $stileWidth;

        foreach ($sections as $section) {
            $sectionStart = $section->position_from_left_inches ?? 0;
            $sectionEnd = $sectionStart + ($section->opening_width_inches ?? 0);

            if ($sectionStart < $lastEnd - 0.0625) { // Allow 1/16" tolerance
                $errors[] = sprintf(
                    'Section %s overlaps with previous section at %.4f"',
                    $section->name ?? $section->id,
                    $sectionStart
                );
            }

            $lastEnd = $sectionEnd + $stileWidth;
        }

        // Warnings for unusual configurations
        if ($cabinet->sections->count() > 4) {
            $warnings[] = 'Cabinet has more than 4 sections - verify this is intentional';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'frame_dimensions' => $frameDimensions,
            'total_section_width' => $totalSectionWidth,
            'mid_stile_count' => $midStileCount,
        ];
    }

    /**
     * Get intelligent default width ratios based on section types
     *
     * @param array $sectionTypes Array of section type strings
     * @return array Calculated ratios
     */
    public function getIntelligentRatios(array $sectionTypes): array
    {
        $ratios = [];

        foreach ($sectionTypes as $type) {
            $ratios[] = match ($type) {
                'drawer_bank' => self::DEFAULT_DRAWER_BANK_RATIO,
                'door' => self::DEFAULT_DOOR_RATIO,
                'pullout' => 0.35, // Pullouts are typically narrow
                'false_front' => 1.0, // False fronts span full width typically
                'open_shelf' => 1.0,
                default => self::DEFAULT_EQUAL_RATIO,
            };
        }

        // Normalize ratios
        $total = array_sum($ratios);
        if ($total > 0) {
            $ratios = array_map(fn($r) => $r / $total, $ratios);
        }

        return $ratios;
    }

    /**
     * Get a summary of the cabinet configuration
     *
     * @param Cabinet $cabinet The cabinet
     * @return array Configuration summary
     */
    public function getConfigurationSummary(Cabinet $cabinet): array
    {
        $cabinet->load(['sections.drawers', 'sections.doors', 'sections.shelves', 'sections.pullouts', 'sections.falseFronts']);

        $frameDimensions = $this->calculateFaceFrameDimensions($cabinet);
        $validation = $this->validateLayout($cabinet);

        $componentCounts = [
            'drawers' => 0,
            'doors' => 0,
            'shelves' => 0,
            'pullouts' => 0,
            'false_fronts' => 0,
        ];

        foreach ($cabinet->sections as $section) {
            $componentCounts['drawers'] += $section->drawers->count();
            $componentCounts['doors'] += $section->doors->count();
            $componentCounts['shelves'] += $section->shelves->count();
            $componentCounts['pullouts'] += $section->pullouts->count();
            $componentCounts['false_fronts'] += $section->falseFronts->count();
        }

        return [
            'cabinet_id' => $cabinet->id,
            'cabinet_code' => $cabinet->full_code,
            'dimensions' => [
                'width' => $cabinet->length_inches,
                'height' => $cabinet->height_inches,
                'depth' => $cabinet->depth_inches,
            ],
            'construction_type' => $cabinet->construction_type ?? self::CONSTRUCTION_FACE_FRAME,
            'face_frame' => $frameDimensions,
            'sections' => $cabinet->sections->map(fn($s) => [
                'id' => $s->id,
                'type' => $s->section_type,
                'name' => $s->name,
                'width' => $s->opening_width_inches,
                'height' => $s->opening_height_inches,
                'ratio' => $s->section_width_ratio,
                'component_count' => $s->total_components,
            ])->toArray(),
            'section_count' => $cabinet->sections->count(),
            'component_counts' => $componentCounts,
            'total_components' => array_sum($componentCounts),
            'validation' => $validation,
            'needs_stretchers' => $cabinet->needsStretchers(),
            'required_stretcher_count' => $cabinet->required_stretcher_count,
            'existing_stretcher_count' => $cabinet->stretchers()->count(),
        ];
    }
}
