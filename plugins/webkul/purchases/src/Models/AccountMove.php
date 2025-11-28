<?php

namespace Webkul\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Webkul\Account\Models\Move;

/**
 * Account Move Eloquent model
 *
 */
class AccountMove extends Move
{
    /**
     * Lines
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lines()
    {
        return $this->hasMany(AccountMoveLine::class, 'move_id');
    }

    /**
     * Purchase Orders
     *
     * @return BelongsToMany
     */
    public function purchaseOrders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'purchases_order_account_moves', 'move_id', 'order_id');
    }
}
