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
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Partner\Models\Partner;
use Webkul\Support\Traits\HasComplexityScore;
use Webkul\Project\Database\Factories\ProjectFactory;
use Webkul\Project\Models\Timesheet;
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
 * @property-read \Illuminate\Database\Eloquent\Collection $timesheets
 * @property-read \Illuminate\Database\Eloquent\Collection $orders
 * @property-read \Illuminate\Database\Eloquent\Collection $rooms
 * @property-read \Illuminate\Database\Eloquent\Collection $cabinets
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
class Project extends Model implements HasMedia, Sortable
{
    use HasChatter, HasCustomFields, HasFactory, HasLogActivity, InteractsWithMedia, SoftDeletes, SortableTrait, HasComplexityScore;

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
        'draft_number',        // Draft identifier (TCS-D001-Address) - assigned at creation
        'is_converted',        // Whether draft has been converted to official project
        'converted_at',        // When draft was converted to official project
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
        'stage_entered_at',
        'partner_id',
        'use_customer_address',
        'company_id',
        'warehouse_id',
        'construction_template_id',
        'source_quote_id',
        'branch_id',
        'user_id',
        'creator_id',
        // Stage gate timestamps
        'design_approved_at',
        'redline_approved_at',
        'materials_staged_at',
        'all_materials_received_at',
        'bol_created_at',
        'bol_signed_at',
        'delivered_at',
        'closeout_delivered_at',
        'customer_signoff_at',
        // MEDIUM priority fields
        'designer_id',
        'rhino_file_path',
        'purchasing_manager_id',
        'ferry_booking_date',
        'ferry_confirmation',
        'install_support_completed_at',
        // LOW priority fields
        'initial_consultation_date',
        'initial_consultation_notes',
        'design_revision_number',
        'design_notes',
        // Google Drive integration
        'google_drive_root_folder_id',
        'google_drive_folder_url',
        'google_drive_synced_at',
        'google_drive_enabled',
        // Lock status fields (Stage & Gate system)
        'design_locked_at',
        'design_locked_by',
        'procurement_locked_at',
        'procurement_locked_by',
        'production_locked_at',
        'production_locked_by',
        'bom_snapshot_json',
        'pricing_snapshot_json',
        // Calculated aggregate fields
        'total_base_cabinet_lf',
        'total_wall_cabinet_lf',
        'total_tall_cabinet_lf',
        'total_vanity_lf',
        'total_sheet_goods_sqft',
        'total_solid_wood_bf',
        'total_edge_banding_lf',
        'total_cabinet_count',
        'total_drawer_count',
        'total_door_count',
        'dimensions_calculated_at',
        // Change order stop action tracking
        'has_pending_change_order',
        'active_change_order_id',
        'delivery_blocked',
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
        'is_converted'            => 'boolean',
        'converted_at'            => 'datetime',
        'stage_entered_at'        => 'datetime',
        'allow_timesheets'        => 'boolean',
        'allow_milestones'        => 'boolean',
        'allow_task_dependencies' => 'boolean',
        // Stage gate timestamps
        'design_approved_at'        => 'datetime',
        'redline_approved_at'       => 'datetime',
        'materials_staged_at'       => 'datetime',
        'all_materials_received_at' => 'datetime',
        'bol_created_at'            => 'datetime',
        'bol_signed_at'             => 'datetime',
        'delivered_at'              => 'datetime',
        'closeout_delivered_at'     => 'datetime',
        'customer_signoff_at'       => 'datetime',
        // MEDIUM priority fields
        'ferry_booking_date'          => 'date',
        'install_support_completed_at' => 'datetime',
        // LOW priority fields
        'initial_consultation_date' => 'date',
        'design_revision_number'    => 'integer',
        // Google Drive integration
        'google_drive_synced_at' => 'datetime',
        'google_drive_enabled'   => 'boolean',
        // Lock status fields (Stage & Gate system)
        'design_locked_at' => 'datetime',
        'procurement_locked_at' => 'datetime',
        'production_locked_at' => 'datetime',
        'bom_snapshot_json' => 'array',
        'pricing_snapshot_json' => 'array',
        // Calculated aggregate fields
        'total_base_cabinet_lf' => 'decimal:4',
        'total_wall_cabinet_lf' => 'decimal:4',
        'total_tall_cabinet_lf' => 'decimal:4',
        'total_vanity_lf' => 'decimal:4',
        'total_sheet_goods_sqft' => 'decimal:2',
        'total_solid_wood_bf' => 'decimal:2',
        'total_edge_banding_lf' => 'decimal:2',
        'total_cabinet_count' => 'integer',
        'total_drawer_count' => 'integer',
        'total_door_count' => 'integer',
        'dimensions_calculated_at' => 'datetime',
        // Change order stop action tracking
        'has_pending_change_order' => 'boolean',
        'delivery_blocked' => 'boolean',
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
     * Designer (assigned for design stage)
     *
     * @return BelongsTo
     */
    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    /**
     * Purchasing Manager (assigned for sourcing stage)
     *
     * @return BelongsTo
     */
    public function purchasingManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchasing_manager_id');
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
     * Gate Evaluations - audit log of gate checks for this project.
     *
     * @return HasMany
     */
    public function gateEvaluations(): HasMany
    {
        return $this->hasMany(GateEvaluation::class, 'project_id');
    }

    /**
     * Stage Transitions - audit log of all stage changes for this project.
     *
     * @return HasMany
     */
    public function stageTransitions(): HasMany
    {
        return $this->hasMany(StageTransition::class, 'project_id');
    }

    /**
     * Get the most recent stage transition.
     *
     * @return HasMany
     */
    public function latestTransition(): HasMany
    {
        return $this->hasMany(StageTransition::class, 'project_id')
            ->latest('transitioned_at')
            ->limit(1);
    }

    /**
     * User who locked the design.
     *
     * @return BelongsTo
     */
    public function designLockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'design_locked_by');
    }

    /**
     * User who locked procurement.
     *
     * @return BelongsTo
     */
    public function procurementLockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'procurement_locked_by');
    }

    /**
     * User who locked production.
     *
     * @return BelongsTo
     */
    public function productionLockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'production_locked_by');
    }

    /**
     * Check if project has design lock applied.
     *
     * @return bool
     */
    public function isDesignLocked(): bool
    {
        return $this->design_locked_at !== null;
    }

    /**
     * Check if project has procurement lock applied.
     *
     * @return bool
     */
    public function isProcurementLocked(): bool
    {
        return $this->procurement_locked_at !== null;
    }

    /**
     * Check if project has production lock applied.
     *
     * @return bool
     */
    public function isProductionLocked(): bool
    {
        return $this->production_locked_at !== null;
    }

    /**
     * Check if any lock is applied.
     *
     * @return bool
     */
    public function hasAnyLock(): bool
    {
        return $this->isDesignLocked() 
            || $this->isProcurementLocked() 
            || $this->isProductionLocked();
    }

    /**
     * Get the current gate for the project's stage.
     * Returns the first blocking gate for the current stage that hasn't passed.
     *
     * @return Gate|null
     */
    public function getCurrentGate(): ?Gate
    {
        if (!$this->stage_id) {
            return null;
        }

        return Gate::where('stage_id', $this->stage_id)
            ->where('is_active', true)
            ->where('is_blocking', true)
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Get all gates for the current stage.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCurrentStageGates()
    {
        if (!$this->stage_id) {
            return collect();
        }

        return Gate::where('stage_id', $this->stage_id)
            ->where('is_active', true)
            ->orderBy('sequence')
            ->get();
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
     * Timesheets
     *
     * @return HasMany
     */
    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
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
     * Construction template for this project.
     *
     * Sets the default construction standards for all cabinets in this project.
     * Individual rooms or cabinets can override with their own template.
     *
     * @return BelongsTo
     */
    public function constructionTemplate(): BelongsTo
    {
        return $this->belongsTo(ConstructionTemplate::class, 'construction_template_id');
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
     * Active Change Order (if any)
     *
     * @return BelongsTo
     */
    public function activeChangeOrder(): BelongsTo
    {
        return $this->belongsTo(ChangeOrder::class, 'active_change_order_id');
    }

    /**
     * All Change Orders for this project.
     *
     * @return HasMany
     */
    public function changeOrders(): HasMany
    {
        return $this->hasMany(ChangeOrder::class, 'project_id');
    }

    /**
     * Check if this project has an active (approved but not applied) change order.
     *
     * @return bool
     */
    public function hasPendingChangeOrder(): bool
    {
        return $this->has_pending_change_order;
    }

    /**
     * Check if delivery is blocked due to a change order.
     *
     * @return bool
     */
    public function isDeliveryBlocked(): bool
    {
        return $this->delivery_blocked;
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
     * Projects that this project depends on (must complete before this one can start/progress)
     *
     * @return BelongsToMany
     */
    public function dependsOn(): BelongsToMany
    {
        return $this->belongsToMany(
            Project::class,
            'projects_project_dependencies',
            'project_id',
            'depends_on_id'
        )->withPivot(['dependency_type', 'lag_days'])
         ->withTimestamps();
    }

    /**
     * Projects that depend on this project (must wait for this project to complete)
     *
     * @return BelongsToMany
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Project::class,
            'projects_project_dependencies',
            'depends_on_id',
            'project_id'
        )->withPivot(['dependency_type', 'lag_days'])
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
     * CNC Programs
     *
     * @return HasMany
     */
    public function cncPrograms(): HasMany
    {
        return $this->hasMany(CncProgram::class);
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
        return $this->hasMany(Cabinet::class);
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

        // Note: Project number generation is now handled by CreateProject wizard
        // New projects get draft_number (TCS-D001-Address) at creation
        // Project numbers (TCS-501-Address) are only assigned when converting to official project
        // The generateProjectNumber() method below is kept for legacy compatibility

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

    /*
    |--------------------------------------------------------------------------
    | Media Collections
    |--------------------------------------------------------------------------
    | Using Spatie Media Library for asset management.
    |
    | Collections:
    | - inspiration: Customer inspiration images (Pinterest, references)
    | - drawings: CAD drawings, DWG files, shop drawings
    | - documents: PDFs, contracts, proposals, permits
    | - photos: Site photos, progress photos, final install photos
    | - videos: Installation videos, customer walk-throughs
    */

    /**
     * Register media collections for project assets.
     */
    public function registerMediaCollections(): void
    {
        // Inspiration images from customer (Pinterest boards, reference photos)
        $this->addMediaCollection('inspiration')
            ->useDisk('public')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
            ]);

        // CAD drawings and DWG files
        $this->addMediaCollection('drawings')
            ->useDisk('public')
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                // DWG MIME types (various browser interpretations)
                'application/acad',
                'application/x-acad',
                'application/autocad_dwg',
                'image/vnd.dwg',
                'image/x-dwg',
                'application/dwg',
                'drawing/dwg',
                'application/octet-stream', // DWG files often detected as binary
            ]);

        // Documents: PDFs, proposals, contracts, permits
        $this->addMediaCollection('documents')
            ->useDisk('public')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        // Site photos, progress photos, final install photos
        $this->addMediaCollection('photos')
            ->useDisk('public')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
                'image/heic',
            ]);

        // Videos: installation, walk-throughs
        $this->addMediaCollection('videos')
            ->useDisk('public')
            ->acceptsMimeTypes([
                'video/mp4',
                'video/quicktime',
                'video/x-msvideo',
                'video/webm',
                'video/mpeg',
            ]);
    }

    /**
     * Register media conversions for thumbnails.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->performOnCollections('inspiration', 'photos');

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->sharpen(5)
            ->performOnCollections('inspiration', 'photos');
    }

    /*
    |--------------------------------------------------------------------------
    | Stage Gate Methods
    |--------------------------------------------------------------------------
    | These methods determine if a project can advance from one production
    | stage to the next based on required milestones and conditions.
    |
    | Production Stages: Discovery → Design → Sourcing → Production → Delivery
    |
    | Two stage tracking systems:
    | 1. `current_production_stage` (enum) - The production workflow position
    | 2. `stage_id` (FK to projects_project_stages) - Kanban column for visual management
    |
    | When stage_id points to a ProjectStage with a matching stage_key,
    | the systems stay in sync. Use advanceToNextStage() to update both.
    */

    /**
     * Production stage order for workflow progression.
     */
    public const PRODUCTION_STAGES = [
        'discovery',
        'design',
        'sourcing',
        'production',
        'delivery',
    ];

    /**
     * Get the next production stage in the workflow.
     *
     * @param string|null $currentStage
     * @return string|null
     */
    public function getNextProductionStage(?string $currentStage = null): ?string
    {
        $current = $currentStage ?? $this->current_production_stage;
        $index = array_search($current, self::PRODUCTION_STAGES);

        if ($index === false || $index >= count(self::PRODUCTION_STAGES) - 1) {
            return null;
        }

        return self::PRODUCTION_STAGES[$index + 1];
    }

    /**
     * Get the previous production stage in the workflow.
     *
     * @param string|null $currentStage
     * @return string|null
     */
    public function getPreviousProductionStage(?string $currentStage = null): ?string
    {
        $current = $currentStage ?? $this->current_production_stage;
        $index = array_search($current, self::PRODUCTION_STAGES);

        if ($index === false || $index <= 0) {
            return null;
        }

        return self::PRODUCTION_STAGES[$index - 1];
    }

    /**
     * Check if project can advance from current stage to next stage.
     *
     * @return bool
     */
    public function canAdvanceToNextStage(): bool
    {
        $method = 'canAdvanceFrom' . ucfirst($this->current_production_stage);

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        return false;
    }

    /**
     * Advance project to next production stage if gates pass.
     * Updates both current_production_stage and stage_id (if matching stage exists).
     *
     * @param bool $force Skip gate checks (admin override)
     * @return bool Success
     */
    public function advanceToNextStage(bool $force = false): bool
    {
        if (!$force && !$this->canAdvanceToNextStage()) {
            return false;
        }

        $nextStage = $this->getNextProductionStage();
        if (!$nextStage) {
            return false;
        }

        $this->current_production_stage = $nextStage;

        // Sync stage_id if a matching ProjectStage exists with this stage_key
        $matchingStage = ProjectStage::where('stage_key', $nextStage)->first();
        if ($matchingStage) {
            $this->stage_id = $matchingStage->id;
        }

        return $this->save();
    }

    /**
     * Set production stage directly (with optional stage_id sync).
     *
     * @param string $stage
     * @param bool $syncStageId
     * @return bool
     */
    public function setProductionStage(string $stage, bool $syncStageId = true): bool
    {
        if (!in_array($stage, self::PRODUCTION_STAGES)) {
            return false;
        }

        $this->current_production_stage = $stage;

        if ($syncStageId) {
            $matchingStage = ProjectStage::where('stage_key', $stage)->first();
            if ($matchingStage) {
                $this->stage_id = $matchingStage->id;
            }
        }

        return $this->save();
    }

    /**
     * Check if project can advance from DISCOVERY to DESIGN stage.
     *
     * Requirements:
     * - Sales order exists with proposal accepted
     * - Deposit has been paid
     *
     * @return bool
     */
    public function canAdvanceFromDiscovery(): bool
    {
        $salesOrder = $this->orders()->first();

        return $salesOrder
            && $salesOrder->proposal_accepted_at !== null
            && $salesOrder->deposit_paid_at !== null;
    }

    /**
     * Check if project can advance from DESIGN to SOURCING stage.
     *
     * Requirements:
     * - Design has been approved by customer
     * - Final redline changes confirmed
     *
     * @return bool
     */
    public function canAdvanceFromDesign(): bool
    {
        return $this->design_approved_at !== null
            && $this->redline_approved_at !== null;
    }

    /**
     * Check if project can advance from SOURCING to PRODUCTION stage.
     *
     * Requirements:
     * - All materials have been received
     * - Materials have been staged in shop
     *
     * @return bool
     */
    public function canAdvanceFromSourcing(): bool
    {
        return $this->all_materials_received_at !== null
            && $this->materials_staged_at !== null;
    }

    /**
     * Check if project can advance from PRODUCTION to DELIVERY stage.
     *
     * Requirements:
     * - All cabinet specifications have passed QC
     *
     * @return bool
     */
    public function canAdvanceFromProduction(): bool
    {
        // Check if all cabinets have passed QC
        $totalCabinets = $this->cabinets()->count();
        if ($totalCabinets === 0) {
            return false;
        }

        $qcPassedCabinets = $this->cabinets()
            ->where('qc_passed', true)
            ->count();

        return $qcPassedCabinets === $totalCabinets;
    }

    /**
     * Check if project can advance from DELIVERY stage (mark complete).
     *
     * Requirements:
     * - Delivery confirmed
     * - Closeout package delivered
     * - Customer signoff received
     * - Final payment received
     *
     * @return bool
     */
    public function canAdvanceFromDelivery(): bool
    {
        return $this->canMarkComplete();
    }

    /**
     * Check if project can be marked as COMPLETE/CLOSED.
     *
     * Requirements:
     * - Delivery confirmed
     * - Closeout package delivered
     * - Customer signoff received
     * - Final payment received
     *
     * @return bool
     */
    public function canMarkComplete(): bool
    {
        $salesOrder = $this->orders()->first();

        return $this->delivered_at !== null
            && $this->closeout_delivered_at !== null
            && $this->customer_signoff_at !== null
            && $salesOrder
            && $salesOrder->final_paid_at !== null;
    }

    /**
     * Get the gate status for the current production stage.
     *
     * @param string|null $fromStage Defaults to current_production_stage
     * @return array ['can_advance' => bool, 'blockers' => array, 'next_stage' => string|null]
     */
    public function getStageGateStatus(?string $fromStage = null): array
    {
        $stage = $fromStage ?? $this->current_production_stage;
        $blockers = [];

        switch (strtolower($stage)) {
            case 'discovery':
                $salesOrder = $this->orders()->first();
                if (!$salesOrder) {
                    $blockers[] = 'No sales order linked to project';
                } else {
                    if (!$salesOrder->proposal_accepted_at) {
                        $blockers[] = 'Proposal not yet accepted by customer';
                    }
                    if (!$salesOrder->deposit_paid_at) {
                        $blockers[] = 'Deposit payment not received';
                    }
                }
                break;

            case 'design':
                if (!$this->design_approved_at) {
                    $blockers[] = 'Design not yet approved by customer';
                }
                if (!$this->redline_approved_at) {
                    $blockers[] = 'Final redline changes not confirmed';
                }
                break;

            case 'sourcing':
                if (!$this->all_materials_received_at) {
                    $blockers[] = 'Not all materials received';
                }
                if (!$this->materials_staged_at) {
                    $blockers[] = 'Materials not yet staged in shop';
                }
                break;

            case 'production':
                $totalCabinets = $this->cabinets()->count();
                if ($totalCabinets === 0) {
                    $blockers[] = 'No cabinets found';
                } else {
                    $qcPassedCabinets = $this->cabinets()
                        ->where('qc_passed', true)
                        ->count();
                    if ($qcPassedCabinets < $totalCabinets) {
                        $blockers[] = "{$qcPassedCabinets}/{$totalCabinets} cabinets have passed QC";
                    }
                }
                break;

            case 'delivery':
                $salesOrder = $this->orders()->first();
                if (!$this->delivered_at) {
                    $blockers[] = 'Delivery not confirmed';
                }
                if (!$this->closeout_delivered_at) {
                    $blockers[] = 'Closeout package not delivered';
                }
                if (!$this->customer_signoff_at) {
                    $blockers[] = 'Customer signoff not received';
                }
                if (!$salesOrder || !$salesOrder->final_paid_at) {
                    $blockers[] = 'Final payment not received';
                }
                break;
        }

        return [
            'current_stage' => $stage,
            'next_stage' => $this->getNextProductionStage($stage),
            'can_advance' => empty($blockers),
            'blockers' => $blockers,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Draft to Official Project Conversion
    |--------------------------------------------------------------------------
    | Projects start as drafts with draft_number (TCS-D001-Address).
    | When a project is converted to official (payment received, contract signed),
    | it gets assigned a project_number (TCS-501-Address).
    |
    | This enables tracking:
    | - Total inquiries/quotes (draft count)
    | - Actual jobs completed (project count)
    | - Conversion rate (projects / drafts)
    */

    /**
     * Check if this project is still a draft (not yet converted to official).
     *
     * @return bool
     */
    public function isDraft(): bool
    {
        return !$this->is_converted;
    }

    /**
     * Check if this project has been converted to official.
     *
     * @return bool
     */
    public function isOfficial(): bool
    {
        return $this->is_converted;
    }

    /**
     * Convert this draft to an official project.
     * Generates and assigns the official project_number.
     *
     * @return bool
     */
    public function convertToOfficial(): bool
    {
        if ($this->is_converted) {
            return false; // Already converted
        }

        // Generate official project number
        $projectNumber = $this->generateOfficialProjectNumber();

        // Update the project
        $this->project_number = $projectNumber;
        $this->is_converted = true;
        $this->converted_at = now();

        return $this->save();
    }

    /**
     * Generate the official project number for conversion.
     *
     * Format: TCS-501-123MainStreet
     *
     * @return string
     */
    protected function generateOfficialProjectNumber(): string
    {
        $company = $this->company;
        $companyAcronym = $company?->acronym ?? strtoupper(substr($company?->name ?? 'UNK', 0, 3));

        // Get the starting project number from company settings
        $startNumber = $company?->project_number_start ?? 1;

        // Find the last official project number for this company
        // (exclude draft numbers which contain -D)
        $lastProject = static::where('company_id', $this->company_id)
            ->where('id', '!=', $this->id)
            ->whereNotNull('project_number')
            ->where('project_number', 'like', "{$companyAcronym}-%")
            ->where('project_number', 'not like', "{$companyAcronym}-D%")
            ->orderBy('id', 'desc')
            ->first();

        $sequentialNumber = $startNumber;
        if ($lastProject && $lastProject->project_number) {
            // Extract number from format like TCS-501-Address
            preg_match('/-(\d+)-/', $lastProject->project_number, $matches);
            if (!empty($matches[1])) {
                $sequentialNumber = max(intval($matches[1]) + 1, $startNumber);
            }
        }

        // Get street address from project address if available
        $streetAbbr = '';
        $address = $this->addresses()->where('is_primary', true)->first();
        if ($address && $address->street1) {
            $streetAbbr = preg_replace('/[^a-zA-Z0-9]/', '', $address->street1);
        }

        return sprintf(
            '%s-%03d%s',
            $companyAcronym,
            $sequentialNumber,
            $streetAbbr ? "-{$streetAbbr}" : ''
        );
    }

    /**
     * Get the display identifier for this project.
     * Returns project_number if converted, otherwise draft_number.
     *
     * @return string|null
     */
    public function getDisplayIdentifierAttribute(): ?string
    {
        return $this->project_number ?? $this->draft_number;
    }
}
