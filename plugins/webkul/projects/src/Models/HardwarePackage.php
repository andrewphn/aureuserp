<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Hardware Package Model
 *
 * Bundles of hardware defaults for projects.
 * Examples: "Standard", "Blum Premium", "Budget"
 */
class HardwarePackage extends Model
{
    use HasFactory;

    protected $table = 'projects_hardware_packages';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'is_default',
        'sort_order',
        'pricing_tier',
        'price_multiplier',
        'default_hinge_type',
        'default_hinge_model',
        'hinges_per_door',
        'default_slide_type',
        'default_slide_model',
        'default_soft_close',
        'default_joinery_method',
        'default_box_material',
        'color',
        'icon',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'price_multiplier' => 'float',
            'hinges_per_door' => 'integer',
            'default_soft_close' => 'boolean',
        ];
    }

    /**
     * Scope to active packages only.
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
     * Get the default package.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first()
            ?? static::active()->ordered()->first();
    }

    /**
     * Pricing tier options.
     */
    public static function pricingTierOptions(): array
    {
        return [
            'budget' => 'Budget',
            'standard' => 'Standard',
            'premium' => 'Premium',
            'luxury' => 'Luxury',
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
     * Ensure only one default package exists.
     */
    protected static function booted(): void
    {
        static::saving(function (HardwarePackage $package) {
            if ($package->is_default) {
                // Unset other defaults
                static::where('id', '!=', $package->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Apply this package's defaults to a drawer.
     */
    public function applyToDrawer(Drawer $drawer): Drawer
    {
        $drawer->fill([
            'joinery_method' => $this->default_joinery_method,
            'box_material' => $this->default_box_material,
            'slide_type' => $this->default_slide_type,
            'slide_model' => $this->default_slide_model,
            'soft_close' => $this->default_soft_close,
        ]);

        return $drawer;
    }

    /**
     * Apply this package's defaults to a door.
     */
    public function applyToDoor(Door $door): Door
    {
        $door->fill([
            'hinge_type' => $this->default_hinge_type,
            'hinge_model' => $this->default_hinge_model,
            'hinge_quantity' => $this->hinges_per_door,
        ]);

        return $door;
    }
}
