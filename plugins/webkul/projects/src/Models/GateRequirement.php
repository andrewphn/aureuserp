<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gate Requirement Eloquent model
 *
 * Defines individual conditions that must be met for a gate to pass.
 * Requirements are data-driven and can be configured without code changes.
 *
 * @property int $id
 * @property int $gate_id
 * @property string $requirement_type
 * @property string|null $target_model
 * @property string|null $target_relation
 * @property string|null $target_field
 * @property string|null $target_value
 * @property string $comparison_operator
 * @property string|null $custom_check_class
 * @property string|null $custom_check_method
 * @property string $error_message
 * @property string|null $help_text
 * @property string|null $action_label
 * @property string|null $action_route
 * @property int $sequence
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Gate $gate
 */
class GateRequirement extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'projects_gate_requirements';

    /**
     * Requirement type constants.
     */
    public const TYPE_FIELD_NOT_NULL = 'field_not_null';
    public const TYPE_FIELD_EQUALS = 'field_equals';
    public const TYPE_FIELD_GREATER_THAN = 'field_greater_than';
    public const TYPE_RELATION_EXISTS = 'relation_exists';
    public const TYPE_RELATION_COUNT = 'relation_count';
    public const TYPE_ALL_CHILDREN_PASS = 'all_children_pass';
    public const TYPE_DOCUMENT_UPLOADED = 'document_uploaded';
    public const TYPE_PAYMENT_RECEIVED = 'payment_received';
    public const TYPE_TASK_COMPLETED = 'task_completed';
    public const TYPE_CUSTOM_CHECK = 'custom_check';

    /**
     * Fillable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'gate_id',
        'requirement_type',
        'target_model',
        'target_relation',
        'target_field',
        'target_value',
        'comparison_operator',
        'custom_check_class',
        'custom_check_method',
        'error_message',
        'help_text',
        'action_label',
        'action_route',
        'sequence',
        'is_active',
    ];

    /**
     * Attribute casting.
     *
     * @var array
     */
    protected $casts = [
        'sequence' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get available requirement types.
     */
    public static function getRequirementTypes(): array
    {
        return [
            self::TYPE_FIELD_NOT_NULL => 'Field Not Null',
            self::TYPE_FIELD_EQUALS => 'Field Equals Value',
            self::TYPE_FIELD_GREATER_THAN => 'Field Greater Than',
            self::TYPE_RELATION_EXISTS => 'Relation Exists',
            self::TYPE_RELATION_COUNT => 'Relation Count',
            self::TYPE_ALL_CHILDREN_PASS => 'All Children Pass',
            self::TYPE_DOCUMENT_UPLOADED => 'Document Uploaded',
            self::TYPE_PAYMENT_RECEIVED => 'Payment Received',
            self::TYPE_TASK_COMPLETED => 'Task Completed',
            self::TYPE_CUSTOM_CHECK => 'Custom Check',
        ];
    }

    /**
     * Get the gate this requirement belongs to.
     */
    public function gate(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'gate_id');
    }

    /**
     * Scope to get only active requirements.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sequence.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence');
    }

    /**
     * Check if this is a custom check type.
     */
    public function isCustomCheck(): bool
    {
        return $this->requirement_type === self::TYPE_CUSTOM_CHECK;
    }

    /**
     * Get the target value as a decoded array if it's JSON.
     */
    public function getDecodedTargetValue()
    {
        if (empty($this->target_value)) {
            return null;
        }

        $decoded = json_decode($this->target_value, true);
        
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->target_value;
    }

    /**
     * Check if this requirement has an action configured.
     */
    public function hasAction(): bool
    {
        return !empty($this->action_label) && !empty($this->action_route);
    }

    /**
     * Get the full custom check class and method as a callable identifier.
     */
    public function getCustomCheckIdentifier(): ?string
    {
        if (!$this->isCustomCheck()) {
            return null;
        }

        return $this->custom_check_class . '@' . $this->custom_check_method;
    }
}
