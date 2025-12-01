<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Shelf Model
 *
 * Represents a cabinet shelf component.
 * Hierarchy: Project -> Room -> Location -> Cabinet Run -> Cabinet -> Section -> Shelf
 */
class Shelf extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects_shelves';

    protected $fillable = [
        'product_id',
        'cabinet_id',
        'section_id',
        'shelf_number',
        'shelf_name',
        'sort_order',
        'width_inches',
        'depth_inches',
        'thickness_inches',
        'shelf_type',
        'material',
        'edge_treatment',
        'pin_hole_spacing',
        'number_of_positions',
        'slide_type',
        'slide_model',
        'slide_length_inches',
        'soft_close',
        'weight_capacity_lbs',
        'finish_type',
        'paint_color',
        'stain_color',
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
            'depth_inches' => 'float',
            'thickness_inches' => 'float',
            'pin_hole_spacing' => 'float',
            'number_of_positions' => 'integer',
            'slide_length_inches' => 'float',
            'soft_close' => 'boolean',
            'weight_capacity_lbs' => 'float',
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

    public const SHELF_TYPES = [
        'fixed' => 'Fixed Shelf',
        'adjustable' => 'Adjustable Shelf',
        'roll_out' => 'Roll-Out Shelf',
        'pull_down' => 'Pull-Down Shelf',
        'corner' => 'Corner Shelf',
        'floating' => 'Floating Shelf',
    ];

    public const MATERIALS = [
        'plywood' => 'Plywood',
        'mdf' => 'MDF',
        'melamine' => 'Melamine',
        'solid_wood' => 'Solid Wood',
        'glass' => 'Glass',
        'wire' => 'Wire',
    ];

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
        $w = $this->width_inches ?? '?';
        $d = $this->depth_inches ?? '?';
        return "{$w}\"W x {$d}\"D";
    }
}
