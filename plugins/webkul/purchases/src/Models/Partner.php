<?php

namespace Webkul\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\Partner as BasePartner;

/**
 * Partner Eloquent model
 *
 */
class Partner extends BasePartner
{
    /**
     * Orders
     *
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Account Moves
     *
     * @return HasMany
     */
    public function accountMoves(): HasMany
    {
        return $this->hasMany(Move::class);
    }
}
