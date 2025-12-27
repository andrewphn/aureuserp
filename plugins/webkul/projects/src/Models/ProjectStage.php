<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Project\Database\Factories\ProjectStageFactory;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Project Stage Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property string|null $color
 * @property bool $is_active
 * @property bool $is_collapsed
 * @property string|null $sort
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $projects
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 *
 */
class ProjectStage extends Model implements Sortable
{
    use HasFactory, SoftDeletes, SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'projects_project_stages';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'stage_key',
        'color',
        'is_active',
        'is_collapsed',
        'wip_limit',
        'max_days_in_stage',
        'expiry_warning_days',
        'notice_message',
        'notice_severity',
        'sort',
        'company_id',
        'creator_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'is_active'           => 'boolean',
        'is_collapsed'        => 'boolean',
        'max_days_in_stage'   => 'integer',
        'expiry_warning_days' => 'integer',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Company
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Projects
     *
     * @return HasMany
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'stage_id');
    }

    /**
     * New Factory
     *
     * @return ProjectStageFactory
     */
    protected static function newFactory(): ProjectStageFactory
    {
        return ProjectStageFactory::new();
    }
}
