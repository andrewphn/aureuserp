<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Project\Services\ComplexityScoreService;

/**
 * Door Preset Model
 *
 * Reusable door configuration templates with default hardware settings.
 */
class DoorPreset extends Model
{
    use HasFactory;

    protected $table = 'projects_door_presets';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sort_order',
        'profile_type',
        'fabrication_method',
        'hinge_type',
        'default_hinge_quantity',
        'has_glass',
        'glass_type',
        'has_check_rail',
        'default_rail_width_inches',
        'default_style_width_inches',
        'estimated_complexity_score',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'default_hinge_quantity' => 'integer',
            'has_glass' => 'boolean',
            'has_check_rail' => 'boolean',
            'default_rail_width_inches' => 'float',
            'default_style_width_inches' => 'float',
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
            'beaded' => 'Beaded',
            'raised_panel' => 'Raised Panel',
            'slab' => 'Slab',
        ];
    }

    /**
     * Fabrication method options.
     */
    public static function fabricationMethodOptions(): array
    {
        return [
            'cnc' => 'CNC',
            'five_piece_manual' => 'Five Piece Manual',
            'slab' => 'Slab',
        ];
    }

    /**
     * Hinge type options.
     */
    public static function hingeTypeOptions(): array
    {
        return [
            'blind_inset' => 'Blind Inset',
            'half_overlay' => 'Half Overlay',
            'full_overlay' => 'Full Overlay',
            'euro_concealed' => 'Euro Concealed',
            'specialty' => 'Specialty',
        ];
    }

    /**
     * Glass type options.
     */
    public static function glassTypeOptions(): array
    {
        return [
            'clear' => 'Clear',
            'seeded' => 'Seeded',
            'frosted' => 'Frosted',
            'mullioned' => 'Mullioned',
            'leaded' => 'Leaded',
        ];
    }

    /**
     * Calculate estimated complexity score based on preset options.
     */
    public function calculateComplexityScore(): float
    {
        $service = app(ComplexityScoreService::class);

        // Build a mock door data array
        $doorData = [
            'profile_type' => $this->profile_type,
            'fabrication_method' => $this->fabrication_method,
            'hinge_type' => $this->hinge_type,
            'has_glass' => $this->has_glass,
            'glass_type' => $this->glass_type,
            'has_check_rail' => $this->has_check_rail,
        ];

        return $service->calculateDoorScore($doorData);
    }

    /**
     * Apply this preset to a Door model.
     */
    public function applyTo(Door $door): Door
    {
        $door->fill([
            'profile_type' => $this->profile_type,
            'fabrication_method' => $this->fabrication_method,
            'hinge_type' => $this->hinge_type,
            'hinge_quantity' => $this->default_hinge_quantity,
            'has_glass' => $this->has_glass,
            'glass_type' => $this->glass_type,
            'has_check_rail' => $this->has_check_rail,
            'rail_width_inches' => $this->default_rail_width_inches,
            'style_width_inches' => $this->default_style_width_inches,
        ]);

        return $door;
    }

    /**
     * Boot method to auto-calculate complexity on save.
     */
    protected static function booted(): void
    {
        static::saving(function (DoorPreset $preset) {
            $preset->estimated_complexity_score = $preset->calculateComplexityScore();
        });
    }
}
