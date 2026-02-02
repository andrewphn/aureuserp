<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Milestone Requirement Template
 *
 * Defines verification requirements that must be met before a milestone
 * can be completed. These are templates that get copied to actual
 * milestone requirements when a project is created.
 *
 * Requirement Types:
 * - field_check: Verify a project field has a value (config: {field: 'field_name', model: 'Project'})
 * - document_upload: Require document upload (config: {document_type: 'type', folder: 'path'})
 * - checklist_item: Manual verification checkbox (config: {instructions: 'text'})
 * - relation_exists: Verify related records exist (config: {relation: 'cabinets', min_count: 1})
 * - relation_complete: Verify all related records have field (config: {relation: 'cabinets', field: 'hardware_id'})
 * - approval_required: Require sign-off (config: {roles: ['project_manager', 'client']})
 */
class MilestoneRequirementTemplate extends Model
{
    protected $table = 'projects_milestone_requirement_templates';

    // Requirement types
    const TYPE_FIELD_CHECK = 'field_check';
    const TYPE_DOCUMENT_UPLOAD = 'document_upload';
    const TYPE_CHECKLIST_ITEM = 'checklist_item';
    const TYPE_RELATION_EXISTS = 'relation_exists';
    const TYPE_RELATION_COMPLETE = 'relation_complete';
    const TYPE_APPROVAL_REQUIRED = 'approval_required';

    protected $fillable = [
        'milestone_template_id',
        'name',
        'requirement_type',
        'description',
        'config',
        'sort_order',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function milestoneTemplate(): BelongsTo
    {
        return $this->belongsTo(MilestoneTemplate::class, 'milestone_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Get available requirement types with labels
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_FIELD_CHECK => 'Field has value',
            self::TYPE_DOCUMENT_UPLOAD => 'Document uploaded',
            self::TYPE_CHECKLIST_ITEM => 'Manual verification',
            self::TYPE_RELATION_EXISTS => 'Related records exist',
            self::TYPE_RELATION_COMPLETE => 'Related records complete',
            self::TYPE_APPROVAL_REQUIRED => 'Approval required',
        ];
    }
}
