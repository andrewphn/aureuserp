<?php

namespace Webkul\Purchase\Models;

use Webkul\Account\Models\Partner as BasePartner;
use Webkul\Account\Models\Move;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends BasePartner
{
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function accountMoves(): HasMany
    {
        return $this->hasMany(Move::class);
    }
}
