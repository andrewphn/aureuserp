<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Project\Database\Factories\GateFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gate Eloquent model
 *
 * Gates define checkpoints that must pass before a project can advance to the next stage.
 * Each gate has requirements that are evaluated to determine if it passes.
 *
 * @property int $id
 * @property int $stage_id
 * @property string $name
 * @property string $gate_key
 * @property string|null $description
 * @property int $sequence
 * @property bool $is_blocking
 * @property bool $is_active
 * @property bool $applies_design_lock
 * @property bool $applies_procurement_lock
 * @property bool $applies_production_lock
 * @property bool $creates_tasks_on_pass
 * @property array|null $task_templates_json
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read ProjectStage $stage
 * @property-read \Illuminate\Database\Eloquent\Collection|GateRequirement[] $requirements
 * @property-read \Illuminate\Database\Eloquent\Collection|GateEvaluation[] $evaluations
 */
class Gate extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'projects_gates';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): GateFactory
    {
        return GateFactory::new();
    }

    /**
     * Fillable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'stage_id',
        'name',
        'gate_key',
        'description',
        'sequence',
        'is_blocking',
        'is_active',
        'applies_design_lock',
        'applies_procurement_lock',
        'applies_production_lock',
        'creates_tasks_on_pass',
        'task_templates_json',
    ];

    /**
     * Attribute casting.
     *
     * @var array
     */
    protected $casts = [
        'sequence' => 'integer',
        'is_blocking' => 'boolean',
        'is_active' => 'boolean',
        'applies_design_lock' => 'boolean',
        'applies_procurement_lock' => 'boolean',
        'applies_production_lock' => 'boolean',
        'creates_tasks_on_pass' => 'boolean',
        'task_templates_json' => 'array',
    ];

    /**
     * Get the stage this gate belongs to.
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStage::class, 'stage_id');
    }

    /**
     * Get the requirements for this gate.
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(GateRequirement::class, 'gate_id')
            ->where('is_active', true)
            ->orderBy('sequence');
    }

    /**
     * Get all requirements including inactive ones.
     */
    public function allRequirements(): HasMany
    {
        return $this->hasMany(GateRequirement::class, 'gate_id')
            ->orderBy('sequence');
    }

    /**
     * Get all evaluations for this gate.
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(GateEvaluation::class, 'gate_id');
    }

    /**
     * Get stage transitions that used this gate.
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(StageTransition::class, 'gate_id');
    }

    /**
     * Scope to get only active gates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only blocking gates.
     */
    public function scopeBlocking($query)
    {
        return $query->where('is_blocking', true);
    }

    /**
     * Scope to order by sequence.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence');
    }

    /**
     * Scope to get gates for a specific stage.
     */
    public function scopeForStage($query, int $stageId)
    {
        return $query->where('stage_id', $stageId);
    }

    /**
     * Scope to get gates by stage key.
     */
    public function scopeForStageKey($query, string $stageKey)
    {
        return $query->whereHas('stage', function ($q) use ($stageKey) {
            $q->where('stage_key', $stageKey);
        });
    }

    /**
     * Find a gate by its key.
     */
    public static function findByKey(string $gateKey): ?self
    {
        return static::where('gate_key', $gateKey)->first();
    }

    /**
     * Check if this gate applies any locks.
     */
    public function appliesAnyLock(): bool
    {
        return $this->applies_design_lock
            || $this->applies_procurement_lock
            || $this->applies_production_lock;
    }

    /**
     * Get the lock types this gate applies.
     */
    public function getLockTypes(): array
    {
        $locks = [];
        
        if ($this->applies_design_lock) {
            $locks[] = 'design';
        }
        if ($this->applies_procurement_lock) {
            $locks[] = 'procurement';
        }
        if ($this->applies_production_lock) {
            $locks[] = 'production';
        }
        
        return $locks;
    }
}
