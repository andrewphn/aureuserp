<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Project\Services\ComplexityScoreService;

/**
 * Drawer Preset Model
 *
 * Reusable drawer configuration templates with default hardware settings.
 */
class DrawerPreset extends Model
{
    use HasFactory;

    protected $table = 'projects_drawer_presets';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sort_order',
        'profile_type',
        'box_material',
        'joinery_method',
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
     * Profile type options.
     */
    public static function profileTypeOptions(): array
    {
        return [
            'shaker' => 'Shaker',
            'flat_panel' => 'Flat Panel',
            'slab' => 'Slab',
        ];
    }

    /**
     * Box material options.
     */
    public static function boxMaterialOptions(): array
    {
        return [
            'maple' => 'Maple',
            'birch' => 'Birch',
            'baltic_birch' => 'Baltic Birch',
            'plywood' => 'Plywood',
        ];
    }

    /**
     * Joinery method options.
     */
    public static function joineryMethodOptions(): array
    {
        return [
            'dovetail' => 'Dovetail',
            'pocket_screw' => 'Pocket Screw',
            'dado' => 'Dado',
            'finger' => 'Finger Joint',
        ];
    }

    /**
     * Slide type options.
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

        // Build a mock drawer data array
        $drawerData = [
            'profile_type' => $this->profile_type,
            'joinery_method' => $this->joinery_method,
            'slide_type' => $this->slide_type,
            'soft_close' => $this->soft_close,
        ];

        return $service->calculateDrawerScore($drawerData);
    }

    /**
     * Apply this preset to a Drawer model.
     */
    public function applyTo(Drawer $drawer): Drawer
    {
        $drawer->fill([
            'profile_type' => $this->profile_type,
            'box_material' => $this->box_material,
            'joinery_method' => $this->joinery_method,
            'slide_type' => $this->slide_type,
            'slide_model' => $this->slide_model,
            'soft_close' => $this->soft_close,
        ]);

        return $drawer;
    }

    /**
     * Boot method to auto-calculate complexity on save.
     */
    protected static function booted(): void
    {
        static::saving(function (DrawerPreset $preset) {
            $preset->estimated_complexity_score = $preset->calculateComplexityScore();
        });
    }
}
