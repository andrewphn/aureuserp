<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Models\User;

/**
 * Incoterm Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $code
 * @property string|null $name
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class Incoterm extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'accounts_incoterms';

    protected $fillable = [
        'code',
        'name',
        'creator_id',
    ];

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
