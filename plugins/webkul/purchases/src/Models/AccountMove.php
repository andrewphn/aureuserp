<?php

namespace Webkul\Purchase\Models;

use Webkul\Account\Models\Move;

class AccountMove extends Move
{
    public function lines()
    {
        return $this->hasMany(AccountMoveLine::class, 'move_id');
    }
}
