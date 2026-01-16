<?php

namespace App\Services;

use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\ConstructionTemplate;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;

/**
 * Construction Standards Service
 *
 * Resolves the effective construction template for entities with inheritance.
 * Hierarchy: Cabinet -> Room -> Project -> Global Default -> Fallback
 *
 * Used by CabinetConfiguratorService and StretcherCalculator to get configurable standards.
 */
class ConstructionStandardsService
{
    /**
     * Cache of resolved templates per request.
     */
    private array $resolvedTemplates = [];

    /**
     * Fallback defaults if no template exists (TCS Standards).
     */
    private const FALLBACK_DEFAULTS = [
        // Cabinet Heights
        'base_cabinet_height' => 34.75,
        'wall_cabinet_30_height' => 30.0,
        'wall_cabinet_36_height' => 36.0,
        'wall_cabinet_42_height' => 42.0,
        'tall_cabinet_84_height' => 84.0,
        'tall_cabinet_96_height' => 96.0,
        // Toe Kick
        'toe_kick_height' => 4.5,
        'toe_kick_recess' => 3.0,
        // Stretchers
        'stretcher_depth' => 3.0,
        'stretcher_thickness' => 0.75,
        'stretcher_min_depth' => 2.5,
        'stretcher_max_depth' => 4.0,
        // Face Frame
        'face_frame_stile_width' => 1.5,
        'face_frame_rail_width' => 1.5,
        'face_frame_door_gap' => 0.125,
        'face_frame_thickness' => 0.75,
        // Material Thickness
        'box_material_thickness' => 0.75,
        'back_panel_thickness' => 0.75,
        'side_panel_thickness' => 0.75,
        // Sink
        'sink_side_extension' => 0.75,
        // Finished End Panel (edge cabinets)
        'finished_end_gap' => 0.25,              // 1/4" gap between panel and cabinet
        'finished_end_wall_extension' => 0.5,   // 1/2" extension toward wall for scribe
        // Back Wall Gap (cabinet depth calculation)
        'back_wall_gap' => 0.25,                 // 1/4" gap from back wall for safety
        // Ratios
        'drawer_bank_ratio' => 0.40,
        'door_section_ratio' => 0.60,
        'equal_section_ratio' => 0.50,
        // Countertop
        'countertop_thickness' => 1.25,
        'finished_counter_height' => 36.0,
    ];

    /**
     * Resolve the effective template for an entity.
     * Follows inheritance: Cabinet -> Room -> Project -> Global Default
     */
    public function resolveTemplate(Cabinet|Room|Project $entity): ConstructionTemplate
    {
        $cacheKey = $this->getCacheKey($entity);

        if (isset($this->resolvedTemplates[$cacheKey])) {
            return $this->resolvedTemplates[$cacheKey];
        }

        $template = $this->findTemplate($entity);
        $this->resolvedTemplates[$cacheKey] = $template;

        return $template;
    }

    /**
     * Find the effective template by walking up the hierarchy.
     */
    private function findTemplate(Cabinet|Room|Project $entity): ConstructionTemplate
    {
        // Check entity's own template
        if ($entity->construction_template_id) {
            $template = ConstructionTemplate::find($entity->construction_template_id);
            if ($template?->is_active) {
                return $template;
            }
        }

        // Walk up hierarchy for Cabinet
        if ($entity instanceof Cabinet) {
            // Check room's template
            $room = $entity->room ?? ($entity->section?->cabinet?->room);
            if ($room?->construction_template_id) {
                $template = ConstructionTemplate::find($room->construction_template_id);
                if ($template?->is_active) {
                    return $template;
                }
            }

            // Check project's template
            $project = $entity->project ?? $room?->project ?? $entity->cabinetRun?->roomLocation?->room?->project;
            if ($project?->construction_template_id) {
                $template = ConstructionTemplate::find($project->construction_template_id);
                if ($template?->is_active) {
                    return $template;
                }
            }
        }

        // Walk up hierarchy for Room
        if ($entity instanceof Room) {
            if ($entity->project?->construction_template_id) {
                $template = ConstructionTemplate::find($entity->project->construction_template_id);
                if ($template?->is_active) {
                    return $template;
                }
            }
        }

        // Get global default
        $defaultTemplate = ConstructionTemplate::getDefault();
        if ($defaultTemplate) {
            return $defaultTemplate;
        }

        // Create a fallback non-persisted template
        return $this->createFallbackTemplate();
    }

    /**
     * Create a fallback template with TCS defaults (not persisted).
     */
    private function createFallbackTemplate(): ConstructionTemplate
    {
        $template = new ConstructionTemplate();
        $template->name = 'TCS Standard (Fallback)';
        $template->is_active = true;
        $template->is_default = true;

        foreach (self::FALLBACK_DEFAULTS as $key => $value) {
            $template->{$key} = $value;
        }

        return $template;
    }

    /**
     * Get cache key for an entity.
     */
    private function getCacheKey($entity): string
    {
        return get_class($entity) . ':' . ($entity->id ?? 'new');
    }

    /**
     * Clear the template cache.
     */
    public function clearCache(): void
    {
        $this->resolvedTemplates = [];
    }

    // ========================================
    // CONVENIENCE METHODS FOR SERVICES
    // ========================================

    /**
     * Get stretcher depth for a cabinet.
     */
    public function getStretcherDepth(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->stretcher_depth;
    }

    /**
     * Get stretcher thickness for a cabinet.
     */
    public function getStretcherThickness(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->stretcher_thickness;
    }

    /**
     * Get stretcher min depth for a cabinet.
     */
    public function getStretcherMinDepth(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->stretcher_min_depth;
    }

    /**
     * Get stretcher max depth for a cabinet.
     */
    public function getStretcherMaxDepth(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->stretcher_max_depth;
    }

    /**
     * Get toe kick height for a cabinet.
     */
    public function getToeKickHeight(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->toe_kick_height;
    }

    /**
     * Get toe kick recess for a cabinet.
     */
    public function getToeKickRecess(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->toe_kick_recess;
    }

    /**
     * Get face frame stile width for a cabinet.
     */
    public function getFaceFrameStileWidth(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->face_frame_stile_width;
    }

    /**
     * Get face frame rail width for a cabinet.
     */
    public function getFaceFrameRailWidth(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->face_frame_rail_width;
    }

    /**
     * Get face frame door gap for a cabinet.
     */
    public function getFaceFrameDoorGap(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->face_frame_door_gap;
    }

    /**
     * Get face frame thickness for a cabinet.
     */
    public function getFaceFrameThickness(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->face_frame_thickness;
    }

    /**
     * Get cabinet height by type.
     */
    public function getCabinetHeight(Cabinet $cabinet, string $type = 'base'): float
    {
        return $this->resolveTemplate($cabinet)->getCabinetHeight($type);
    }

    /**
     * Get box material thickness (from product or fallback).
     */
    public function getBoxMaterialThickness(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->getEffectiveBoxMaterialThickness();
    }

    /**
     * Get back panel thickness (from product or fallback).
     */
    public function getBackPanelThickness(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->getEffectiveBackPanelThickness();
    }

    /**
     * Get side panel thickness.
     */
    public function getSidePanelThickness(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->side_panel_thickness;
    }

    /**
     * Get sink side extension.
     */
    public function getSinkSideExtension(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->sink_side_extension;
    }

    /**
     * Get drawer bank ratio.
     */
    public function getDrawerBankRatio(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->drawer_bank_ratio;
    }

    /**
     * Get finished end panel gap (space between panel and cabinet box).
     */
    public function getFinishedEndGap(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->finished_end_gap ?? 0.25;
    }

    /**
     * Get finished end panel wall extension (for scribe fitting).
     */
    public function getFinishedEndWallExtension(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->finished_end_wall_extension ?? 0.5;
    }

    /**
     * Get back wall gap (cabinet sits this far from the wall).
     */
    public function getBackWallGap(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->back_wall_gap ?? 0.25;
    }

    /**
     * Get door section ratio.
     */
    public function getDoorSectionRatio(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->door_section_ratio;
    }

    /**
     * Get equal section ratio.
     */
    public function getEqualSectionRatio(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->equal_section_ratio;
    }

    /**
     * Get countertop thickness.
     */
    public function getCountertopThickness(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->countertop_thickness;
    }

    /**
     * Get finished counter height.
     */
    public function getFinishedCounterHeight(Cabinet $cabinet): float
    {
        return $this->resolveTemplate($cabinet)->finished_counter_height;
    }

    /**
     * Get all construction standards as an array.
     */
    public function getAllStandards(Cabinet $cabinet): array
    {
        return $this->resolveTemplate($cabinet)->toStandardsArray();
    }

    /**
     * Get the template name for display.
     */
    public function getTemplateName(Cabinet $cabinet): string
    {
        return $this->resolveTemplate($cabinet)->name;
    }
}
