<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Product\Models\Product;
use Webkul\Security\Models\User;

/**
 * Construction Template Model
 *
 * Configurable cabinet construction standards template.
 * Follows DoorPreset pattern for reusable configuration.
 *
 * TCS Standards documented by Bryan Patton (Jan 2025)
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property bool $is_default
 * @property int $sort_order
 * @property float $base_cabinet_height
 * @property float $wall_cabinet_30_height
 * @property float $wall_cabinet_36_height
 * @property float $wall_cabinet_42_height
 * @property float $tall_cabinet_84_height
 * @property float $tall_cabinet_96_height
 * @property float $toe_kick_height
 * @property float $toe_kick_recess
 * @property float $stretcher_depth
 * @property float $stretcher_thickness
 * @property float $stretcher_min_depth
 * @property float $stretcher_max_depth
 * @property float $face_frame_stile_width
 * @property float $face_frame_rail_width
 * @property float $face_frame_door_gap
 * @property float $face_frame_thickness
 * @property int|null $default_box_material_product_id
 * @property int|null $default_back_material_product_id
 * @property int|null $default_face_frame_material_product_id
 * @property int|null $default_edge_banding_product_id
 * @property float $box_material_thickness
 * @property float $back_panel_thickness
 * @property float $side_panel_thickness
 * @property float $sink_side_extension
 * @property float $drawer_bank_ratio
 * @property float $door_section_ratio
 * @property float $equal_section_ratio
 * @property float $countertop_thickness
 * @property float $finished_counter_height
 * @property int|null $creator_id
 * @property-read Product|null $defaultBoxMaterialProduct
 * @property-read Product|null $defaultBackMaterialProduct
 * @property-read Product|null $defaultFaceFrameMaterialProduct
 * @property-read Product|null $defaultEdgeBandingProduct
 * @property-read User|null $creator
 */
class ConstructionTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects_construction_templates';

    protected $fillable = [
        // Identity
        'name',
        'description',
        'is_active',
        'is_default',
        'sort_order',
        // Cabinet Heights
        'base_cabinet_height',
        'wall_cabinet_30_height',
        'wall_cabinet_36_height',
        'wall_cabinet_42_height',
        'tall_cabinet_84_height',
        'tall_cabinet_96_height',
        // Toe Kick
        'toe_kick_height',
        'toe_kick_recess',
        // Stretchers
        'stretcher_depth',
        'stretcher_thickness',
        'stretcher_min_depth',
        'stretcher_max_depth',
        // Face Frame
        'face_frame_stile_width',
        'face_frame_rail_width',
        'face_frame_door_gap',
        'face_frame_thickness',
        // Material Products
        'default_box_material_product_id',
        'default_back_material_product_id',
        'default_face_frame_material_product_id',
        'default_edge_banding_product_id',
        // Material Thickness Overrides
        'box_material_thickness',
        'back_panel_thickness',
        'side_panel_thickness',
        // Sink Cabinet
        'sink_side_extension',
        // Section Ratios
        'drawer_bank_ratio',
        'door_section_ratio',
        'equal_section_ratio',
        // Countertop
        'countertop_thickness',
        'finished_counter_height',
        // Metadata
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            // Booleans
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            // Cabinet Heights
            'base_cabinet_height' => 'float',
            'wall_cabinet_30_height' => 'float',
            'wall_cabinet_36_height' => 'float',
            'wall_cabinet_42_height' => 'float',
            'tall_cabinet_84_height' => 'float',
            'tall_cabinet_96_height' => 'float',
            // Toe Kick
            'toe_kick_height' => 'float',
            'toe_kick_recess' => 'float',
            // Stretchers
            'stretcher_depth' => 'float',
            'stretcher_thickness' => 'float',
            'stretcher_min_depth' => 'float',
            'stretcher_max_depth' => 'float',
            // Face Frame
            'face_frame_stile_width' => 'float',
            'face_frame_rail_width' => 'float',
            'face_frame_door_gap' => 'float',
            'face_frame_thickness' => 'float',
            // Material Thickness
            'box_material_thickness' => 'float',
            'back_panel_thickness' => 'float',
            'side_panel_thickness' => 'float',
            // Sink
            'sink_side_extension' => 'float',
            // Ratios
            'drawer_bank_ratio' => 'float',
            'door_section_ratio' => 'float',
            'equal_section_ratio' => 'float',
            // Countertop
            'countertop_thickness' => 'float',
            'finished_counter_height' => 'float',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Default box material product (sheet goods).
     */
    public function defaultBoxMaterialProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'default_box_material_product_id');
    }

    /**
     * Default back material product (sheet goods).
     */
    public function defaultBackMaterialProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'default_back_material_product_id');
    }

    /**
     * Default face frame material product (lumber).
     */
    public function defaultFaceFrameMaterialProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'default_face_frame_material_product_id');
    }

    /**
     * Default edge banding product.
     */
    public function defaultEdgeBandingProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'default_edge_banding_product_id');
    }

    /**
     * Creator user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Projects using this template.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'construction_template_id');
    }

    /**
     * Rooms using this template.
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'construction_template_id');
    }

    /**
     * Cabinets using this template.
     */
    public function cabinets(): HasMany
    {
        return $this->hasMany(Cabinet::class, 'construction_template_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope to active templates only.
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
     * Scope to default templates only.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ========================================
    // HEIGHT HELPERS
    // ========================================

    /**
     * Get cabinet height by type.
     */
    public function getCabinetHeight(string $type): float
    {
        return match ($type) {
            'base' => $this->base_cabinet_height,
            'wall_30' => $this->wall_cabinet_30_height,
            'wall_36' => $this->wall_cabinet_36_height,
            'wall_42' => $this->wall_cabinet_42_height,
            'tall', 'tall_84' => $this->tall_cabinet_84_height,
            'tall_96' => $this->tall_cabinet_96_height,
            default => $this->base_cabinet_height,
        };
    }

    /**
     * Cabinet height type options.
     */
    public static function cabinetHeightTypeOptions(): array
    {
        return [
            'base' => 'Base Cabinet',
            'wall_30' => 'Wall Cabinet 30"',
            'wall_36' => 'Wall Cabinet 36"',
            'wall_42' => 'Wall Cabinet 42"',
            'tall_84' => 'Tall Cabinet 84"',
            'tall_96' => 'Tall Cabinet 96"',
        ];
    }

    // ========================================
    // MATERIAL THICKNESS HELPERS
    // ========================================

    /**
     * Get effective box material thickness.
     * Tries to pull from Product attributes, falls back to override value.
     */
    public function getEffectiveBoxMaterialThickness(): float
    {
        if ($this->defaultBoxMaterialProduct) {
            $thickness = $this->getProductThickness($this->defaultBoxMaterialProduct);
            if ($thickness !== null) {
                return $thickness;
            }
        }

        return $this->box_material_thickness ?? 0.75;
    }

    /**
     * Get effective back panel thickness.
     */
    public function getEffectiveBackPanelThickness(): float
    {
        if ($this->defaultBackMaterialProduct) {
            $thickness = $this->getProductThickness($this->defaultBackMaterialProduct);
            if ($thickness !== null) {
                return $thickness;
            }
        }

        return $this->back_panel_thickness ?? 0.75;
    }

    /**
     * Extract thickness from a Product's attributes.
     */
    protected function getProductThickness(Product $product): ?float
    {
        // Try to get thickness from product attributes
        $thicknessAttribute = $product->attribute_values()
            ->whereHas('attribute', function ($query) {
                $query->where('name', 'like', '%thickness%')
                    ->orWhere('code', 'like', '%thickness%');
            })
            ->first();

        if ($thicknessAttribute) {
            $value = $thicknessAttribute->text_value ?? $thicknessAttribute->numeric_value ?? null;
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    // ========================================
    // APPLY TO CABINET
    // ========================================

    /**
     * Apply this template's defaults to a Cabinet model.
     */
    public function applyTo(Cabinet $cabinet): Cabinet
    {
        $cabinet->fill([
            'construction_template_id' => $this->id,
            // Toe kick
            'toe_kick_height' => $this->toe_kick_height,
            'toe_kick_recess' => $this->toe_kick_recess,
            // Stretchers
            'stretcher_height_inches' => $this->stretcher_depth,
            // Face frame
            'face_frame_stile_width_inches' => $this->face_frame_stile_width,
            'face_frame_rail_width_inches' => $this->face_frame_rail_width,
            'face_frame_door_gap_inches' => $this->face_frame_door_gap,
            // Material products
            'box_material_product_id' => $this->default_box_material_product_id,
            'face_frame_material_product_id' => $this->default_face_frame_material_product_id,
            'edge_banding_product_id' => $this->default_edge_banding_product_id,
        ]);

        return $cabinet;
    }

    // ========================================
    // GET ALL STANDARDS
    // ========================================

    /**
     * Get all construction standards as an array.
     */
    public function toStandardsArray(): array
    {
        return [
            'cabinet_heights' => [
                'base' => $this->base_cabinet_height,
                'wall_30' => $this->wall_cabinet_30_height,
                'wall_36' => $this->wall_cabinet_36_height,
                'wall_42' => $this->wall_cabinet_42_height,
                'tall_84' => $this->tall_cabinet_84_height,
                'tall_96' => $this->tall_cabinet_96_height,
            ],
            'toe_kick' => [
                'height' => $this->toe_kick_height,
                'recess' => $this->toe_kick_recess,
            ],
            'stretchers' => [
                'depth' => $this->stretcher_depth,
                'thickness' => $this->stretcher_thickness,
                'min' => $this->stretcher_min_depth,
                'max' => $this->stretcher_max_depth,
            ],
            'face_frame' => [
                'stile_width' => $this->face_frame_stile_width,
                'rail_width' => $this->face_frame_rail_width,
                'door_gap' => $this->face_frame_door_gap,
                'thickness' => $this->face_frame_thickness,
            ],
            'materials' => [
                'box_product_id' => $this->default_box_material_product_id,
                'back_product_id' => $this->default_back_material_product_id,
                'face_frame_product_id' => $this->default_face_frame_material_product_id,
                'edge_banding_product_id' => $this->default_edge_banding_product_id,
                'box_thickness' => $this->getEffectiveBoxMaterialThickness(),
                'back_thickness' => $this->getEffectiveBackPanelThickness(),
                'side_thickness' => $this->side_panel_thickness,
            ],
            'sink' => [
                'side_extension' => $this->sink_side_extension,
            ],
            'ratios' => [
                'drawer_bank' => $this->drawer_bank_ratio,
                'door' => $this->door_section_ratio,
                'equal' => $this->equal_section_ratio,
            ],
            'countertop' => [
                'thickness' => $this->countertop_thickness,
                'finished_height' => $this->finished_counter_height,
            ],
        ];
    }

    // ========================================
    // BOOT
    // ========================================

    /**
     * Boot method to ensure only one default template.
     */
    protected static function booted(): void
    {
        static::saving(function (ConstructionTemplate $template) {
            // Ensure only one default
            if ($template->is_default) {
                static::where('id', '!=', $template->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }

    // ========================================
    // STATIC HELPERS
    // ========================================

    /**
     * Get the default template.
     */
    public static function getDefault(): ?self
    {
        return static::query()
            ->active()
            ->default()
            ->first();
    }

    /**
     * Get active templates for selection.
     */
    public static function getActiveOptions(): array
    {
        return static::query()
            ->active()
            ->ordered()
            ->pluck('name', 'id')
            ->toArray();
    }
}
