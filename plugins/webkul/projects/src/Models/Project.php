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

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStage::class);
    }

    public function taskStages(): HasMany
    {
        return $this->hasMany(TaskStage::class);
    }

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

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(ProjectAddress::class);
    }

    public function productionEstimates(): HasMany
    {
        return $this->hasMany(\App\Models\ProductionEstimate::class);
    }

    public function currentProductionEstimate()
    {
        return $this->hasOne(\App\Models\ProductionEstimate::class)->where('is_current', true);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(\Webkul\Sale\Models\Order::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'branch_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'projects_project_tag', 'project_id', 'tag_id');
    }

    public function pdfDocuments(): MorphMany
    {
        return $this->morphMany(\App\Models\PdfDocument::class, 'module');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function cabinets(): HasMany
    {
        return $this->hasMany(CabinetSpecification::class);
    }

    public function cabinetSpecifications(): HasMany
    {
        return $this->hasMany(CabinetSpecification::class);
    }

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
    }

    /**
     * Generate unique project number based on company acronym and street address
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

        // Calculate next number (starting from 001)
        // Pattern matches: TCS-001-MapleAve (extracts 001)
        $nextNumber = 1;
        if ($lastProjectNumber && preg_match('/^[A-Z]+-(\d+)-/', $lastProjectNumber, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        }

        // Format: TCS-001-MapleAve, TCS-002-FriendshipLane, etc.
        $projectNumber = $companyAcronym . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT) . '-' . $streetAddress;

        // Update the project_number column directly without triggering events
        $this->project_number = $projectNumber;
        $this->saveQuietly();
    }

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
