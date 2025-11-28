<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

/**
 * Footer Preference Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $user_id
 * @property string|null $context_type
 * @property array $minimized_fields
 * @property array $expanded_fields
 * @property array $field_order
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 *
 */
class FooterPreference extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'footer_preferences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'context_type',
        'minimized_fields',
        'expanded_fields',
        'field_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'minimized_fields' => 'array',
        'expanded_fields' => 'array',
        'field_order' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active preferences.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get preferences for a specific context type.
     */
    public function scopeForContext($query, string $contextType)
    {
        return $query->where('context_type', $contextType);
    }

    /**
     * Get preference for specific user and context.
     */
    public static function getForUser(User $user, string $contextType): ?self
    {
        return static::where('user_id', $user->id)
            ->forContext($contextType)
            ->active()
            ->first();
    }
}
