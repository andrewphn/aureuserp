<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

/**
 * Faceframe Eloquent model
 *
 * Represents a face frame structure for a cabinet run.
 * One faceframe per cabinet run (1:1 relationship).
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $cabinet_run_id
 * @property string|null $face_frame_type
 * @property string|null $face_frame_thickness
 * @property float|null $face_frame_linear_feet
 * @property float|null $stile_width
 * @property float|null $rail_width
 * @property float|null $material_thickness
 * @property string|null $material
 * @property string|null $finish_option
 * @property string|null $joinery_type
 * @property bool $beaded_face_frame
 * @property string|null $overlay_type
 * @property bool $flush_with_carcass
 * @property float|null $overhang_left
 * @property float|null $overhang_right
 * @property float|null $overhang_top
 * @property float|null $overhang_bottom
 * @property string|null $notes
 * @property int|null $creator_id
 * @property-read \Webkul\Project\Models\CabinetRun $cabinetRun
 * @property-read \Webkul\Security\Models\User|null $creator
 */
class Faceframe extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity;

    protected $table = 'projects_faceframes';

    protected $fillable = [
        'cabinet_run_id',
        'face_frame_type',
        'face_frame_thickness',
        'face_frame_linear_feet',
        'stile_width',
        'rail_width',
        'material_thickness',
        'material',
        'finish_option',
        'joinery_type',
        'beaded_face_frame',
        'overlay_type',
        'flush_with_carcass',
        'overhang_left',
        'overhang_right',
        'overhang_top',
        'overhang_bottom',
        'notes',
        'creator_id',
    ];

    protected $casts = [
        'face_frame_linear_feet' => 'decimal:2',
        'stile_width' => 'decimal:3',
        'rail_width' => 'decimal:3',
        'material_thickness' => 'decimal:3',
        'beaded_face_frame' => 'boolean',
        'flush_with_carcass' => 'boolean',
        'overhang_left' => 'decimal:3',
        'overhang_right' => 'decimal:3',
        'overhang_top' => 'decimal:3',
        'overhang_bottom' => 'decimal:3',
    ];

    /**
     * Attributes to log for Chatter activity tracking
     */
    protected $logAttributes = [
        'cabinetRun.name' => 'Cabinet Run',
        'face_frame_type' => 'Face Frame Type',
        'face_frame_thickness' => 'Face Frame Thickness',
        'face_frame_linear_feet' => 'Face Frame Linear Feet',
        'stile_width' => 'Stile Width',
        'rail_width' => 'Rail Width',
        'material' => 'Material',
        'finish_option' => 'Finish Option',
        'joinery_type' => 'Joinery Type',
        'overlay_type' => 'Overlay Type',
        'notes' => 'Notes',
    ];

    /**
     * Relationships
     */

    /**
     * Cabinet run this faceframe belongs to
     *
     * @return BelongsTo
     */
    public function cabinetRun(): BelongsTo
    {
        return $this->belongsTo(CabinetRun::class, 'cabinet_run_id');
    }

    /**
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
