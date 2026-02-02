<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

/**
 * Milestone Requirement (Instance)
 *
 * Actual verification requirement for a specific project milestone.
 * Created from templates when project milestones are created.
 */
class MilestoneRequirement extends Model
{
    protected $table = 'projects_milestone_requirements';

    protected $fillable = [
        'milestone_id',
        'template_id',
        'name',
        'requirement_type',
        'description',
        'config',
        'sort_order',
        'is_required',
        'is_verified',
        'verified_at',
        'verified_by',
        'verification_notes',
    ];

    protected $casts = [
        'config' => 'array',
        'is_required' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MilestoneRequirementTemplate::class, 'template_id');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Mark requirement as verified
     */
    public function verify(?string $notes = null): self
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
            'verification_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Unverify requirement
     */
    public function unverify(): self
    {
        $this->update([
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
            'verification_notes' => null,
        ]);

        return $this;
    }

    public function scopePending($query)
    {
        return $query->where('is_verified', false);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
