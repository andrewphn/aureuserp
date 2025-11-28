<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Database\Factories\ProjectFactory;
use Webkul\Security\Models\Scopes\UserPermissionScope;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Project Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property string|null $project_number
 * @property string|null $project_type
 * @property string|null $project_type_other
 * @property string|null $lead_source
 * @property string|null $budget_range
 * @property int|null $complexity_score
 * @property string|null $tasks_label
 * @property string|null $description
 * @property string|null $visibility
 * @property string|null $color
 * @property string|null $sort
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property \Carbon\Carbon|null $desired_completion_date
 * @property float $allocated_hours
 * @property string|null $estimated_linear_feet
 * @property bool $allow_timesheets
 * @property bool $allow_milestones
 * @property bool $allow_task_dependencies
 * @property bool $is_active
 * @property int $stage_id
 * @property int $partner_id
 * @property string|null $use_customer_address
 * @property int $company_id
 * @property int $branch_id
 * @property int $user_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $currentProductionEstimate
 * @property-read \Illuminate\Database\Eloquent\Collection $taskStages
 * @property-read \Illuminate\Database\Eloquent\Collection $milestones
 * @property-read \Illuminate\Database\Eloquent\Collection $addresses
 * @property-read \Illuminate\Database\Eloquent\Collection $productionEstimates
 * @property-read \Illuminate\Database\Eloquent\Collection $tasks
 * @property-read \Illuminate\Database\Eloquent\Collection $orders
 * @property-read \Illuminate\Database\Eloquent\Collection $rooms
 * @property-read \Illuminate\Database\Eloquent\Collection $cabinets
 * @property-read \Illuminate\Database\Eloquent\Collection $cabinetSpecifications
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $stage
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $branch
 * @property-read \Illuminate\Database\Eloquent\Collection $favoriteUsers
 * @property-read \Illuminate\Database\Eloquent\Collection $tags
 * @property-read \Illuminate\Database\Eloquent\Collection $roomLocations
 * @property-read \Illuminate\Database\Eloquent\Collection $cabinetRuns
 * @property-read \Illuminate\Database\Eloquent\Collection $pdfDocuments
 *
 */
class Project extends Model implements Sortable
{
    use HasChatter, HasCustomFields, HasFactory, HasLogActivity, SoftDeletes, SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'projects_projects';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'project_number',
        'project_type',
        'project_type_other',
        'lead_source',
        'budget_range',
        'complexity_score',
        'tasks_label',
        'description',
        'visibility',
        'color',
        'sort',
        'start_date',
        'end_date',
        'desired_completion_date',
        'allocated_hours',
        'estimated_linear_feet',
        'allow_timesheets',
        'allow_milestones',
        'allow_task_dependencies',
        'is_active',
        'stage_id',
        'partner_id',
        'use_customer_address',
        'company_id',
        'warehouse_id',
        'source_quote_id',
        'branch_id',
        'user_id',
        'creator_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'start_date'              => 'date',
        'end_date'                => 'date',
        'desired_completion_date' => 'date',
        'is_active'               => 'boolean',
        'allow_timesheets'        => 'boolean',
        'allow_milestones'        => 'boolean',
        'allow_task_dependencies' => 'boolean',
    ];

    protected array $logAttributes = [
        'name',
        'tasks_label',
        'description',
        'visibility',
        'color',
        'sort',
        'start_date',
        'end_date',
        'allocated_hours',
        'allow_timesheets',
        'allow_milestones',
        'allow_task_dependencies',
        'is_active',
        'stage.name'   => 'Stage',
        'partner.name' => 'Customer',
        'company.name' => 'Company',
        'branch.name'  => 'Branch',
        'user.name'    => 'Project Manager',
        'creator.name' => 'Creator',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Get the user's first name.
     */
    protected function plannedDate(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['start_date'].' - '.$attributes['end_date'],
        );
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
     * User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Stage
     *
     * @return BelongsTo
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStage::class);
    }

    /**
     * Task Stages
     *
     * @return HasMany
     */
    public function taskStages(): HasMany
    {
        return $this->hasMany(TaskStage::class);
    }

    /**
     * Favorite Users
     *
     * @return BelongsToMany
     */
    public function favoriteUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'projects_user_project_favorites', 'project_id', 'user_id');
    }

    public function getIsFavoriteByUserAttribute(): bool
    {
        if ($this->relationLoaded('favoriteUsers')) {
            return $this->favoriteUsers->contains('id', Auth::id());
        }

        return $this->favoriteUsers()->where('user_id', Auth::id())->exists();
    }

    public function getRemainingHoursAttribute(): float
    {
        return $this->allocated_hours - $this->tasks->sum('remaining_hours');
    }

    /**
     * Milestones
     *
     * @return HasMany
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    /**
     * Addresses
     *
     * @return HasMany
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(ProjectAddress::class);
    }

    /**
     * Production Estimates
     *
     * @return HasMany
     */
    public function productionEstimates(): HasMany
    {
        return $this->hasMany(\App\Models\ProductionEstimate::class);
    }

    /**
     * Current Production Estimate
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function currentProductionEstimate()
    {
        return $this->hasOne(\App\Models\ProductionEstimate::class)->where('is_current', true);
    }

    /**
     * Tasks
     *
     * @return HasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Orders
     *
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(\Webkul\Sale\Models\Order::class);
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
     * Branch
     *
     * @return BelongsTo
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'branch_id');
    }

    /**
     * Warehouse
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Inventory\Models\Warehouse::class);
    }

    /**
     * Source Quote (the quote that originated this project)
     *
     * @return BelongsTo
     */
    public function sourceQuote(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Sale\Models\Order::class, 'source_quote_id');
    }

    /**
     * Material Reservations
     *
     * @return HasMany
     */
    public function materialReservations(): HasMany
    {
        return $this->hasMany(MaterialReservation::class);
    }

    /**
     * Tags
     *
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'projects_project_tag', 'project_id', 'tag_id')
            ->withTimestamps();
    }

    /**
     * Pdf Documents
     *
     * @return MorphMany
     */
    public function pdfDocuments(): MorphMany
    {
        return $this->morphMany(\App\Models\PdfDocument::class, 'module');
    }

    /**
     * Rooms
     *
     * @return HasMany
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Room Locations
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function roomLocations()
    {
        return $this->hasManyThrough(
            RoomLocation::class,
            Room::class,
            'project_id', // Foreign key on rooms table
            'room_id',    // Foreign key on room_locations table
            'id',         // Local key on projects table
            'id'          // Local key on rooms table
        );
    }

    /**
     * Cabinets
     *
     * @return HasMany
     */
    public function cabinets(): HasMany
    {
        return $this->hasMany(CabinetSpecification::class);
    }

    /**
     * Cabinet Specifications
     *
     * @return HasMany
     */
    public function cabinetSpecifications(): HasMany
    {
        return $this->hasMany(CabinetSpecification::class);
    }

    /**
     * Cabinet Runs
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function cabinetRuns()
    {
        // Note: This relationship doesn't work correctly for hasManyThrough across 3 levels
        // The relation manager uses modifyQueryUsing to bypass this and query correctly
        return $this->hasManyThrough(
            CabinetRun::class,
            Room::class,
            'project_id',
            'room_location_id',
            'id',
            'id'
        );
    }

    protected static function booted()
    {
        static::addGlobalScope(new UserPermissionScope('user'));

        // Auto-generate project number when project is created
        static::created(function ($project) {
            if ($project->company_id) {
                $project->generateProjectNumber();
            }
        });

        // Fire event when stage changes
        // Use static property to avoid persisting temp value to database
        static $originalStageIds = [];

        static::updating(function ($project) use (&$originalStageIds) {
            // Store original stage_id before update
            if ($project->isDirty('stage_id')) {
                $originalStageIds[$project->id] = $project->getOriginal('stage_id');
            }
        });

        static::updated(function ($project) use (&$originalStageIds) {
            // Fire stage changed event if stage_id was modified
            if (isset($originalStageIds[$project->id]) && $project->wasChanged('stage_id')) {
                $previousStage = $originalStageIds[$project->id]
                    ? ProjectStage::find($originalStageIds[$project->id])
                    : null;
                $newStage = $project->stage;

                if ($newStage) {
                    event(new \Webkul\Project\Events\ProjectStageChanged(
                        $project,
                        $previousStage,
                        $newStage
                    ));
                }

                // Clean up
                unset($originalStageIds[$project->id]);
            }
        });
    }

    /**
     * Generate unique project number based on company acronym and street address
     */
    /**
     * Generate Project Number
     *
     * @return void
     */
    protected function generateProjectNumber(): void
    {
        // Get company acronym from direct column access
        $companyAcronym = $this->company->acronym ?? null;

        if (empty($companyAcronym)) {
            // Fallback to company initials if no acronym set
            $companyAcronym = strtoupper(substr($this->company?->name ?? 'PRJ', 0, 3));
        }

        // Get street address (required for project number)
        $streetAddress = $this->street_address;

        if (empty($streetAddress)) {
            // Cannot generate project number without street address
            return;
        }

        // Find the highest existing project number for this company
        $lastProjectNumber = static::where('company_id', $this->company_id)
            ->where('id', '!=', $this->id)
            ->whereNotNull('project_number')
            ->orderBy('id', 'desc')
            ->value('project_number');

        // Calculate next number (using company's project_number_start setting, default 1)
        // Pattern matches: TCS-001-MapleAve (extracts 001)
        $startNumber = $this->company->project_number_start ?? 1;
        $nextNumber = $startNumber;
        if ($lastProjectNumber && preg_match('/^[A-Z]+-(\d+)-/', $lastProjectNumber, $matches)) {
            $nextNumber = max(intval($matches[1]) + 1, $startNumber);
        }

        // Format: TCS-001-MapleAve, TCS-002-FriendshipLane, etc.
        $projectNumber = $companyAcronym . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT) . '-' . $streetAddress;

        // Update the project_number column directly without triggering events
        $this->project_number = $projectNumber;
        $this->saveQuietly();
    }

    /**
     * New Factory
     *
     * @return ProjectFactory
     */
    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
