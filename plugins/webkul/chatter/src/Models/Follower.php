<?php

namespace Webkul\Chatter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Webkul\Partner\Models\Partner;

/**
 * Follower Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $followable_id
 * @property string|null $followable_type
 * @property int $partner_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $followable
 *
 */
class Follower extends Model
{
    protected $table = 'chatter_followers';

    protected $fillable = [
        'followable_id',
        'followable_type',
        'partner_id',
    ];

    protected $casts = [
        'followed_at' => 'datetime',
    ];

    /**
     * Followable
     *
     * @return MorphTo
     */
    public function followable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Partner
     *
     * @return BelongsTo
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
}
