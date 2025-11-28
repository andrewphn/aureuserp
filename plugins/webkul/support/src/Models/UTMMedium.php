<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;

/**
 * UTMMedium Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class UTMMedium extends Model
{
    protected $table = 'utm_mediums';

    protected $fillable = ['name', 'creator_id'];

    /**
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
