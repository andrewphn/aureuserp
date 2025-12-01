<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Door Model
 *
 * Represents a cabinet door component.
 * Hierarchy: Project -> Room -> Location -> Cabinet Run -> Cabinet -> Section -> Door
 */
class Door extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects_doors';

    protected $fillable = [
        'product_id',
        'cabinet_id',
        'section_id',
        'door_number',
        'door_name',
        'sort_order',
        'width_inches',
        'height_inches',
        'rail_width_inches',
        'style_width_inches',
        'has_check_rail',
        'check_rail_width_inches',
        'profile_type',
        'fabrication_method',
        'thickness_inches',
        'hinge_type',
        'hinge_model',
        'hinge_quantity',
        'hinge_side',
        'has_glass',
        'glass_type',
        'finish_type',
        'paint_color',
        'stain_color',
        'has_decorative_hardware',
        'decorative_hardware_model',
        'cnc_cut_at',
        'manually_cut_at',
        'edge_banded_at',
        'assembled_at',
        'sanded_at',
        'finished_at',
        'hardware_installed_at',
        'installed_in_cabinet_at',
        'qc_passed',
        'qc_notes',
        'qc_inspected_at',
        'qc_inspector_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'width_inches' => 'float',
            'height_inches' => 'float',
            'rail_width_inches' => 'float',
            'style_width_inches' => 'float',
            'has_check_rail' => 'boolean',
            'check_rail_width_inches' => 'float',
            'thickness_inches' => 'float',
            'hinge_quantity' => 'integer',
            'has_glass' => 'boolean',
            'has_decorative_hardware' => 'boolean',
            'cnc_cut_at' => 'datetime',
            'manually_cut_at' => 'datetime',
            'edge_banded_at' => 'datetime',
            'assembled_at' => 'datetime',
            'sanded_at' => 'datetime',
            'finished_at' => 'datetime',
            'hardware_installed_at' => 'datetime',
            'installed_in_cabinet_at' => 'datetime',
            'qc_passed' => 'boolean',
            'qc_inspected_at' => 'datetime',
        ];
    }

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CabinetSection::class, 'section_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getFormattedDimensionsAttribute(): string
    {
        return ($this->width_inches ?? '?') . '"W x ' . ($this->height_inches ?? '?') . '"H';
    }
}
