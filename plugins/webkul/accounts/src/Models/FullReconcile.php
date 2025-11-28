<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;

/**
 * Full Reconcile Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $exchange_move_id
 * @property int $created_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $exchangeMove
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class FullReconcile extends Model
{
    use HasFactory;

    protected $table = 'accounts_full_reconciles';

    protected $fillable = [
        'exchange_move_id',
        'created_id',
    ];

    /**
     * Exchange Move
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function exchangeMove()
    {
        return $this->belongsTo(Move::class, 'exchange_move_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_id');
    }
}
