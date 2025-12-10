<?php

namespace Webkul\Support\Traits;

/**
 * Trait HasFullCode
 *
 * Provides auto-generation of hierarchical full codes for cabinet components.
 * Full codes follow the format: TCS-0554-15WSANKATY-K1-SW-U1-A-DOOR1
 *
 * Usage:
 * - Add `use HasFullCode;` to your model
 * - Add 'full_code' to $fillable
 * - Implement `getComponentCode(): string` method
 */
trait HasFullCode
{
    /**
     * Boot the trait - auto-generate full_code on saving
     */
    public static function bootHasFullCode(): void
    {
        static::saving(function ($model) {
            $model->full_code = $model->generateFullCode();
        });
    }

    /**
     * Generate the complete hierarchical code for this component
     */
    public function generateFullCode(): string
    {
        $parts = [];

        // Explicitly load relationships to ensure they're available
        // This is necessary because during boot/saving, relationships may not be loaded
        if ($this->section_id && !$this->relationLoaded('section')) {
            $this->load('section.cabinet.cabinetRun.roomLocation.room.project');
        } elseif ($this->cabinet_id && !$this->relationLoaded('cabinet')) {
            $this->load('cabinet.cabinetRun.roomLocation.room.project');
        }

        // Walk up the hierarchy to collect all code segments
        $section = $this->section ?? null;
        $cabinet = $this->cabinet ?? $section?->cabinet;
        $run = $cabinet?->cabinetRun;
        $location = $run?->roomLocation;
        $room = $location?->room ?? $cabinet?->room;
        $project = $room?->project ?? $cabinet?->project;

        // Build code from project down to component
        if ($project?->project_number) {
            $parts[] = $project->project_number;
        }

        if ($room?->room_code) {
            $parts[] = $room->room_code;
        }

        if ($location?->location_code) {
            $parts[] = $location->location_code;
        }

        if ($run?->run_code) {
            $parts[] = $run->run_code;
        }

        if ($section?->section_code) {
            $parts[] = $section->section_code;
        }

        // Add the component-specific code (DOOR1, DRW1, etc.)
        $parts[] = $this->getComponentCode();

        return implode('-', array_filter($parts));
    }

    /**
     * Get the component-specific code (e.g., DOOR1, DRW2, SHELF1)
     * Must be implemented by each model using this trait
     */
    abstract public function getComponentCode(): string;

    /**
     * Accessor for full_code - generates on-the-fly if not cached
     */
    public function getFullCodeAttribute($value): string
    {
        return $value ?? $this->generateFullCode();
    }
}
