<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Project\Services\ComplexityScoreService;

/**
 * Shelf Preset Model
 *
 * Reusable shelf configuration templates with default settings.
 */
class ShelfPreset extends Model
{
    use HasFactory;

    protected $table = 'projects_shelf_presets';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sort_order',
        'shelf_type',
        'material',
        'edge_treatment',
        'slide_type',
        'slide_model',
        'soft_close',
        'estimated_complexity_score',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'soft_close' => 'boolean',
            'estimated_complexity_score' => 'float',
        ];
    }

    /**
     * Scope to active presets only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Shelf type options.
     */
    public static function shelfTypeOptions(): array
    {
        return [
            'fixed' => 'Fixed',
            'adjustable' => 'Adjustable',
            'roll_out' => 'Roll Out',
            'pull_down' => 'Pull Down',
            'corner' => 'Corner',
            'floating' => 'Floating',
        ];
    }

    /**
     * Material options.
     */
    public static function materialOptions(): array
    {
        return [
            'plywood' => 'Plywood',
            'melamine' => 'Melamine',
            'solid_wood' => 'Solid Wood',
        ];
    }

    /**
     * Edge treatment options.
     */
    public static function edgeTreatmentOptions(): array
    {
        return [
            'edge_banded' => 'Edge Banded',
            'solid_edge' => 'Solid Edge',
            'veneer' => 'Veneer',
        ];
    }

    /**
     * Slide type options (for roll-out/pull-down shelves).
     */
    public static function slideTypeOptions(): array
    {
        return [
            'blum_tandem' => 'Blum Tandem',
            'undermount' => 'Undermount',
            'full_extension' => 'Full Extension',
            'side_mount' => 'Side Mount',
        ];
    }

    /**
     * Calculate estimated complexity score based on preset options.
     */
    public function calculateComplexityScore(): float
    {
        $service = app(ComplexityScoreService::class);

        // Build a mock shelf data array
        $shelfData = [
            'shelf_type' => $this->shelf_type,
            'slide_type' => $this->slide_type,
            'soft_close' => $this->soft_close,
        ];

        return $service->calculateShelfScore($shelfData);
    }

    /**
     * Apply this preset to a Shelf model.
     */
    public function applyTo(Shelf $shelf): Shelf
    {
        $shelf->fill([
            'shelf_type' => $this->shelf_type,
            'material' => $this->material,
            'edge_treatment' => $this->edge_treatment,
            'slide_type' => $this->slide_type,
            'slide_model' => $this->slide_model,
            'soft_close' => $this->soft_close,
        ]);

        return $shelf;
    }

    /**
     * Boot method to auto-calculate complexity on save.
     */
    protected static function booted(): void
    {
        static::saving(function (ShelfPreset $preset) {
            $preset->estimated_complexity_score = $preset->calculateComplexityScore();
        });
    }
}
