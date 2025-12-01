<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Database\Factories\TaskFactory;
use Webkul\Project\Enums\TaskState;
use Webkul\Security\Models\Scopes\UserPermissionScope;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Cabinet;

/**
 * Task Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $title
 * @property string|null $description
 * @property string|null $color
 * @property bool $priority
 * @property mixed $state
 * @property string|null $sort
 * @property bool $is_active
 * @property bool $is_recurring
 * @property \Carbon\Carbon|null $deadline
 * @property float $working_hours_open
 * @property float $working_hours_close
 * @property float $allocated_hours
 * @property float $remaining_hours
 * @property float $effective_hours
 * @property float $total_hours_spent
 * @property float $subtask_effective_hours
 * @property float $overtime
 * @property string|null $progress
 * @property int $stage_id
 * @property int $project_id
 * @property int $partner_id
 * @property int $parent_id
 * @property int $company_id
 * @property int $creator_id
 * @property int $room_id
 * @property int $room_location_id
 * @property int $cabinet_run_id
 * @property int $cabinet_id
 * @property-read \Illuminate\Database\Eloquent\Collection $subTasks
 * @property-read \Illuminate\Database\Eloquent\Collection $timesheets
 * @property-read \Illuminate\Database\Eloquent\Model|null $parent
 * @property-read \Illuminate\Database\Eloquent\Model|null $project
 * @property-read \Illuminate\Database\Eloquent\Model|null $milestone
 * @property-read \Illuminate\Database\Eloquent\Model|null $stage
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $room
 * @property-read \Illuminate\Database\Eloquent\Model|null $roomLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $cabinetRun
 * @property-read \Illuminate\Database\Eloquent\Model|null $cabinet
 * @property-read \Illuminate\Database\Eloquent\Collection $users
 * @property-read \Illuminate\Database\Eloquent\Collection $tags
 *
 */
class Task extends Model implements Sortable
{
    use HasChatter, HasCustomFields, HasFactory, HasLogActivity, SoftDeletes, SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'projects_tasks';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'color',
        'priority',
        'state',
        'sort',
        'is_active',
        'is_recurring',
        'deadline',
        'working_hours_open',
        'working_hours_close',
        'allocated_hours',
        'remaining_hours',
        'effective_hours',
        'total_hours_spent',
        'subtask_effective_hours',
        'overtime',
        'progress',
        'stage_id',
        'project_id',
        'partner_id',
        'parent_id',
        'company_id',
        'creator_id',
        'room_id',
        'room_location_id',
        'cabinet_run_id',
        'cabinet_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'is_active'           => 'boolean',
        'deadline'            => 'datetime',
        'priority'            => 'boolean',
        'is_active'           => 'boolean',
        'is_recurring'        => 'boolean',
        'working_hours_open'  => 'float',
        'working_hours_close' => 'float',
        'allocated_hours'     => 'float',
        'remaining_hours'     => 'float',
        'effective_hours'     => 'float',
        'total_hours_spent'   => 'float',
        'overtime'            => 'float',
        'state'               => TaskState::class,
    ];

    protected array $logAttributes = [
        'title',
        'description',
        'color',
        'priority',
        'state',
        'sort',
        'is_active',
        'is_recurring',
        'deadline',
        'allocated_hours',
        'stage.name'   => 'Stage',
        'project.name' => 'Project',
        'partner.name' => 'Partner',
        'parent.title' => 'Parent',
        'company.name' => 'Company',
        'creator.name' => 'Creator',
        'room.name' => 'Room',
        'roomLocation.name' => 'Room Location',
        'cabinetRun.name' => 'Cabinet Run',
        'cabinet.cabinet_number' => 'Cabinet',
    ];

    public string $recordTitleAttribute = 'title';

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Parent
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    /**
     * Sub Tasks
     *
     * @return HasMany
     */
    public function subTasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Project
     *
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Milestone
     *
     * @return BelongsTo
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    /**
     * Stage
     *
     * @return BelongsTo
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(TaskStage::class);
    }

    /**
     * Partner
     *
     * @return BelongsTo
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

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
     * Users
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'projects_task_users');
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
     * Tags
     *
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'projects_task_tag', 'task_id', 'tag_id');
    }

    /**
     * Timesheets
     *
     * @return HasMany
     */
    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    /**
     * Room
     *
     * @return BelongsTo
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Room Location
     *
     * @return BelongsTo
     */
    public function roomLocation(): BelongsTo
    {
        return $this->belongsTo(RoomLocation::class);
    }

    /**
     * Cabinet Run
     *
     * @return BelongsTo
     */
    public function cabinetRun(): BelongsTo
    {
        return $this->belongsTo(CabinetRun::class);
    }

    /**
     * Cabinet
     *
     * @return BelongsTo
     */
    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }

    /**
     * Booted
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(new UserPermissionScope('users'));
    }

    /**
     * Bootstrap any application services.
     */
    /**
     * Boot
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($task) {
            $task->timesheets()->update([
                'project_id' => $task->project_id,
                'partner_id' => $task->partner_id ?? $task->project?->partner_id,
                'company_id' => $task->company_id ?? $task->project?->company_id,
            ]);
        });
    }

    /**
     * New Factory
     *
     * @return TaskFactory
     */
    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }
}
