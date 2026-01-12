<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;
use Webkul\Project\Services\ComplexityScoreService;

/**
 * Pullout Preset Model
 *
 * Reusable pullout configuration templates with default hardware settings.
 */
class PulloutPreset extends Model
{
    use HasFactory;

    protected $table = 'projects_pullout_presets';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sort_order',
        'pullout_type',
        'manufacturer',
        'model_number',
        'mounting_type',
        'slide_type',
        'slide_model',
        'soft_close',
        'product_id',
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
     * Linked product from inventory.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Pullout type options.
     */
    public static function pulloutTypeOptions(): array
    {
        return [
            'trash' => 'Trash',
            'spice_rack' => 'Spice Rack',
            'lazy_susan' => 'Lazy Susan',
            'mixer_lift' => 'Mixer Lift',
            'blind_corner' => 'Blind Corner',
            'pantry' => 'Pantry',
            'tray_divider' => 'Tray Divider',
            'cookie_sheet' => 'Cookie Sheet',
        ];
    }

    /**
     * Manufacturer options.
     */
    public static function manufacturerOptions(): array
    {
        return [
            'rev_a_shelf' => 'Rev-A-Shelf',
            'hafele' => 'Hafele',
            'richelieu' => 'Richelieu',
            'blum' => 'Blum',
            'custom' => 'Custom Built',
        ];
    }

    /**
     * Mounting type options.
     */
    public static function mountingTypeOptions(): array
    {
        return [
            'bottom_mount' => 'Bottom Mount',
            'side_mount' => 'Side Mount',
            'door_mount' => 'Door Mount',
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

        // Build a mock pullout data array
        $pulloutData = [
            'pullout_type' => $this->pullout_type,
            'slide_type' => $this->slide_type,
            'soft_close' => $this->soft_close,
        ];

        return $service->calculatePulloutScore($pulloutData);
    }

    /**
     * Apply this preset to a Pullout model.
     */
    public function applyTo(Pullout $pullout): Pullout
    {
        $pullout->fill([
            'pullout_type' => $this->pullout_type,
            'manufacturer' => $this->manufacturer,
            'model_number' => $this->model_number,
            'mounting_type' => $this->mounting_type,
            'slide_type' => $this->slide_type,
            'slide_model' => $this->slide_model,
            'soft_close' => $this->soft_close,
            'product_id' => $this->product_id,
        ]);

        return $pullout;
    }

    /**
     * Boot method to auto-calculate complexity on save.
     */
    protected static function booted(): void
    {
        static::saving(function (PulloutPreset $preset) {
            $preset->estimated_complexity_score = $preset->calculateComplexityScore();
        });
    }
}
