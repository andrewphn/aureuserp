<?php

namespace Webkul\Support\Traits;

use Webkul\Project\Services\ComplexityScoreService;

/**
 * Trait for models that have hierarchical complexity scores.
 *
 * Provides methods for accessing, displaying, and recalculating complexity scores.
 * Used by: Door, Drawer, Shelf, Pullout, CabinetSection, Cabinet, CabinetRun, RoomLocation, Room, Project
 */
trait HasComplexityScore
{
    /**
     * Initialize the trait - set up casts for complexity_breakdown.
     */
    public function initializeHasComplexityScore(): void
    {
        $this->mergeCasts([
            'complexity_breakdown' => 'array',
            'complexity_calculated_at' => 'datetime',
        ]);
    }

    /**
     * Check if complexity score needs recalculation.
     *
     * Returns true if:
     * - Score is null
     * - Calculated timestamp is null
     * - Last calculation was more than 24 hours ago
     */
    public function needsComplexityRecalculation(): bool
    {
        if ($this->complexity_score === null) {
            return true;
        }

        if ($this->complexity_calculated_at === null) {
            return true;
        }

        // Recalculate if last calculation was more than 24 hours ago
        return $this->complexity_calculated_at->diffInHours(now()) > 24;
    }

    /**
     * Force recalculation of complexity score.
     *
     * @return $this Fresh instance with updated score
     */
    public function recalculateComplexity(): self
    {
        $service = app(ComplexityScoreService::class);
        $service->recalculateAndSave($this);

        return $this->fresh();
    }

    /**
     * Cascade complexity recalculation up the hierarchy.
     *
     * Recalculates this entity and all parent entities.
     *
     * @return $this Fresh instance with updated score
     */
    public function cascadeComplexityRecalculation(): self
    {
        $service = app(ComplexityScoreService::class);
        $service->cascadeRecalculation($this);

        return $this->fresh();
    }

    /**
     * Get human-readable complexity display label.
     *
     * @return string One of: Simple, Standard, Moderate, Complex, Very Complex, Custom
     */
    public function getComplexityDisplayAttribute(): string
    {
        $service = app(ComplexityScoreService::class);

        return $service->scoreToLabel($this->complexity_score ?? 0);
    }

    /**
     * Get Filament color class for complexity display.
     *
     * @return string One of: success, info, primary, warning, danger
     */
    public function getComplexityColorAttribute(): string
    {
        $service = app(ComplexityScoreService::class);

        return $service->scoreToColor($this->complexity_score ?? 0);
    }

    /**
     * Get production time multiplier based on complexity score.
     *
     * Used by capacity widget to adjust estimated hours.
     *
     * @return float Multiplier ranging from 0.8 (simple) to 1.8 (custom)
     */
    public function getComplexityMultiplierAttribute(): float
    {
        $service = app(ComplexityScoreService::class);

        return $service->scoreToMultiplier($this->complexity_score ?? 0);
    }

    /**
     * Get formatted complexity score for display.
     *
     * @return string Score formatted to 1 decimal place with "pts" suffix
     */
    public function getFormattedComplexityScoreAttribute(): string
    {
        $score = $this->complexity_score ?? 0;

        return number_format($score, 1) . ' pts';
    }

    /**
     * Scope to filter by complexity level.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $level  One of: simple, standard, moderate, complex, very_complex, custom
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeComplexityLevel($query, string $level)
    {
        return match ($level) {
            'simple' => $query->where('complexity_score', '<', 10),
            'standard' => $query->whereBetween('complexity_score', [10, 14.99]),
            'moderate' => $query->whereBetween('complexity_score', [15, 19.99]),
            'complex' => $query->whereBetween('complexity_score', [20, 29.99]),
            'very_complex' => $query->whereBetween('complexity_score', [30, 39.99]),
            'custom' => $query->where('complexity_score', '>=', 40),
            default => $query,
        };
    }

    /**
     * Scope to filter entities needing complexity recalculation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsRecalculation($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('complexity_score')
                ->orWhereNull('complexity_calculated_at')
                ->orWhere('complexity_calculated_at', '<', now()->subDay());
        });
    }

    /**
     * Get complexity breakdown as a formatted array for display.
     *
     * @return array Array of ['label' => string, 'points' => float] items
     */
    public function getComplexityBreakdownForDisplay(): array
    {
        $breakdown = $this->complexity_breakdown ?? [];
        $formatted = [];

        // Format breakdown items with human-readable labels
        $labelMap = [
            'base' => 'Base Score',
            'soft_close' => 'Soft Close',
            'hinge_euro_concealed' => 'Euro Concealed Hinges',
            'hinge_specialty' => 'Specialty Hinges',
            'slide_blum_tandem' => 'Blum Tandem Slides',
            'slide_full_extension' => 'Full Extension Slides',
            'slide_undermount' => 'Undermount Slides',
            'has_glass' => 'Glass Panel',
            'glass_mullioned' => 'Mullioned Glass',
            'glass_leaded' => 'Leaded Glass',
            'joinery_dovetail' => 'Dovetail Joinery',
            'joinery_dado' => 'Dado Joinery',
            'joinery_finger' => 'Finger Joinery',
            'has_check_rail' => 'Check Rail',
            'profile_beaded' => 'Beaded Profile',
            'profile_raised_panel' => 'Raised Panel',
            'profile_shaker' => 'Shaker Profile',
            'non_standard_width' => 'Custom Width',
            'non_standard_height' => 'Custom Height',
            'non_standard_depth' => 'Custom Depth',
            'shelf_corner' => 'Corner Shelf',
            'shelf_floating' => 'Floating Shelf',
            'pullout_spice_rack' => 'Spice Rack',
            'pullout_lazy_susan' => 'Lazy Susan',
            'pullout_mixer_lift' => 'Mixer Lift',
            'pullout_blind_corner' => 'Blind Corner',
            'pullout_pantry' => 'Pantry Pullout',
        ];

        foreach ($breakdown as $key => $value) {
            if ($key === 'components' || $key === 'sections' || $key === 'cabinets' ||
                $key === 'runs' || $key === 'locations' || $key === 'rooms') {
                // Skip nested arrays, these are for aggregate entities
                continue;
            }

            $label = $labelMap[$key] ?? ucwords(str_replace('_', ' ', $key));
            $formatted[] = [
                'label' => $label,
                'points' => is_numeric($value) ? $value : 0,
            ];
        }

        return $formatted;
    }
}
