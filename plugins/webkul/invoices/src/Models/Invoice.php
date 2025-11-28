<?php

namespace Webkul\Invoice\Models;

use Webkul\Account\Models\Move as BaseMove;
use Webkul\Account\Models\MoveLine;

/**
 * Invoice Eloquent model
 *
 */
class Invoice extends BaseMove
{
    /**
     * Payment Term Line
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function paymentTermLine()
    {
        return $this->hasOne(MoveLine::class, 'move_id')
            ->where('display_type', 'payment_term');
    }
}
