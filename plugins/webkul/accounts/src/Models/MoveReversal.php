<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Move Reversal Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $company_id
 * @property int $creator_id
 * @property string|null $reason
 * @property \Carbon\Carbon|null $date
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection $newMoves
 * @property-read \Illuminate\Database\Eloquent\Collection $moves
 *
 */
class MoveReversal extends Model
{
    protected $table = 'accounts_accounts_move_reversals';

    protected $fillable = [
        'company_id',
        'creator_id',
        'reason',
        'date',
    ];

    /**
     * New Moves
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function newMoves()
    {
        return $this->belongsToMany(Move::class, 'accounts_accounts_move_reversal_new_move', 'reversal_id', 'new_move_id');
    }

    /**
     * Moves
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function moves()
    {
        return $this->belongsToMany(Move::class, 'accounts_accounts_move_reversal_move', 'reversal_id', 'move_id');
    }

    /**
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Creator
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
