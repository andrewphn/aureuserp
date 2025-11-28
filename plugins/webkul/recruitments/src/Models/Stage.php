<?php

namespace Webkul\Recruitment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Employee\Models\EmployeeJobPosition;
use Webkul\Security\Models\User;

/**
 * Stage Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property bool $is_default
 * @property int $creator_id
 * @property string|null $name
 * @property string|null $legend_blocked
 * @property string|null $legend_done
 * @property string|null $legend_normal
 * @property string|null $requirements
 * @property bool $fold
 * @property bool $hired_stage
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection $jobs
 *
 */
class Stage extends Model implements Sortable
{
    use HasFactory;
    use SortableTrait;

    protected $table = 'recruitments_stages';

    protected $fillable = [
        'sort',
        'is_default',
        'creator_id',
        'name',
        'legend_blocked',
        'legend_done',
        'legend_normal',
        'requirements',
        'fold',
        'hired_stage',
    ];

    protected $casts = [
        'is_default'  => 'boolean',
        'hired_stage' => 'boolean',
        'fold'        => 'boolean',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Jobs
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function jobs()
    {
        return $this->belongsToMany(EmployeeJobPosition::class, 'recruitments_stages_jobs', 'stage_id', 'job_id');
    }
}
