<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

/**
 * Footer Template Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property string|null $slug
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $color
 * @property array $contexts
 * @property bool $is_active
 * @property bool $is_system
 * @property string|null $created_by
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class FooterTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'contexts',
        'is_active',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'contexts' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only system templates
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get only user-created templates
     */
    public function scopeUserCreated($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Get template configuration for a specific context
     */
    public function getContextConfig(string $contextType): array
    {
        return $this->contexts[$contextType] ?? [
            'minimized_fields' => [],
            'expanded_fields' => [],
            'field_order' => [],
        ];
    }
}
