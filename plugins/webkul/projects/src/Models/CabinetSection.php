<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cabinet Section Model
 *
 * Represents a section within a cabinet (e.g., door section, drawer bank).
 * Hierarchy: Project → Room → Room Location → Cabinet Run → Cabinet → Section → Components
 *
 * @property int $id
 * @property int $cabinet_id
 * @property int|null $section_number
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
        'name',
        'section_type',
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
     * Get the cabinet this section belongs to.
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id');
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
