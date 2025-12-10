<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Product\Models\Product;

/**
 * Cabinet Section Model
 *
 * Represents a section within a cabinet (e.g., door section, drawer bank).
 * Hierarchy: Project → Room → Room Location → Cabinet Run → Cabinet → Section → Components
 *
 * @property int $id
 * @property int $cabinet_id
 * @property int|null $section_number
 * @property string|null $section_code
 * @property string|null $full_code
 * @property string|null $name
 * @property string|null $section_type
 * @property float|null $width_inches
 * @property float|null $height_inches
 * @property float|null $position_from_left_inches
 * @property float|null $position_from_bottom_inches
 * @property int|null $component_count
 * @property float|null $opening_width_inches
 * @property float|null $opening_height_inches
 * @property string|null $notes
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class CabinetSection extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projects_cabinet_sections';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cabinet_id',
        'section_number',
        'section_code',
        'full_code',
        'name',
        'section_type',
        'product_id',
        'hardware_product_id',
        'width_inches',
        'height_inches',
        'position_from_left_inches',
        'position_from_bottom_inches',
        'component_count',
        'opening_width_inches',
        'opening_height_inches',
        'notes',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section_number' => 'integer',
            'width_inches' => 'float',
            'height_inches' => 'float',
            'position_from_left_inches' => 'float',
            'position_from_bottom_inches' => 'float',
            'component_count' => 'integer',
            'opening_width_inches' => 'float',
            'opening_height_inches' => 'float',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Section types available for cabinet sections.
     */
    public const SECTION_TYPES = [
        'door' => 'Door Section',
        'drawer_bank' => 'Drawer Bank',
        'open_shelf' => 'Open Shelf',
        'appliance' => 'Appliance Opening',
        'pullout' => 'Pullout Section',
        'mixed' => 'Mixed (Doors & Drawers)',
    ];

    /**
     * Boot the model - auto-generate codes on saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($section) {
            // Auto-generate section_code if empty
            if (empty($section->section_code)) {
                $section->section_code = $section->generateSectionCode();
            }

            // Always regenerate full_code
            $section->full_code = $section->generateFullCode();
        });

        // When section_code changes, regenerate all descendant full_codes
        static::updated(function ($section) {
            if ($section->isDirty('section_code')) {
                $section->regenerateDescendantCodes();
            }
        });
    }

    /**
     * Generate the section code (letter based on position)
     * Format: A, B, C, D... (1=A, 2=B, etc.)
     */
    public function generateSectionCode(): string
    {
        $number = $this->section_number ?? $this->sort_order ?? 1;
        return chr(64 + $number); // 1=A, 2=B, 3=C, etc.
    }

    /**
     * Generate the complete hierarchical code for this section
     * Format: TCS-0554-15WSANKATY-K1-SW-U1-A
     */
    public function generateFullCode(): string
    {
        $parts = [];

        // Explicitly load relationships to ensure they're available
        // This is necessary because during boot/saving, relationships may not be loaded
        if ($this->cabinet_id && !$this->relationLoaded('cabinet')) {
            $this->load('cabinet.cabinetRun.roomLocation.room.project');
        }

        // Walk up the hierarchy
        $cabinet = $this->cabinet;
        $run = $cabinet?->cabinetRun;
        $location = $run?->roomLocation;
        $room = $location?->room ?? $cabinet?->room;
        $project = $room?->project ?? $cabinet?->project;

        // Build code from project down to section
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

        // Add the section code
        $parts[] = $this->section_code ?? $this->generateSectionCode();

        return implode('-', array_filter($parts));
    }

    /**
     * Regenerate full_codes for all descendant components
     * Called when section_code changes
     */
    public function regenerateDescendantCodes(): void
    {
        $this->load(['doors', 'drawers', 'shelves', 'pullouts']);

        foreach (['doors', 'drawers', 'shelves', 'pullouts'] as $relation) {
            foreach ($this->$relation as $component) {
                $component->full_code = $component->generateFullCode();
                $component->saveQuietly();
            }
        }
    }

    /**
     * Get the cabinet this section belongs to.
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id');
    }

    /**
     * Get the product associated with this section.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the hardware product associated with this section.
     */
    public function hardwareProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'hardware_product_id');
    }

    /**
     * Get doors for this section.
     */
    public function doors(): HasMany
    {
        return $this->hasMany(Door::class, 'section_id');
    }

    /**
     * Get drawers for this section.
     */
    public function drawers(): HasMany
    {
        return $this->hasMany(Drawer::class, 'section_id');
    }

    /**
     * Get pullouts for this section.
     */
    public function pullouts(): HasMany
    {
        return $this->hasMany(Pullout::class, 'section_id');
    }

    /**
     * Get shelves for this section.
     */
    public function shelves(): HasMany
    {
        return $this->hasMany(Shelf::class, 'section_id');
    }

    /**
     * Get all components count for this section.
     */
    public function getTotalComponentsAttribute(): int
    {
        return $this->doors()->count()
            + $this->drawers()->count()
            + $this->pullouts()->count()
            + $this->shelves()->count();
    }

    /**
     * Scope to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get the formatted dimensions string.
     */
    public function getFormattedDimensionsAttribute(): string
    {
        $width = $this->width_inches ?? '?';
        $height = $this->height_inches ?? '?';

        return "{$width}\"W x {$height}\"H";
    }
}
