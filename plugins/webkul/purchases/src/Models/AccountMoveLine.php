<?php

namespace Webkul\Purchase\Models;

use Webkul\Account\Models\MoveLine as MoveLine;

class AccountMoveLine extends MoveLine
{
    /**
     * Create a new Eloquent model instance.
     *
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->mergeFillable([
            'purchase_order_line_id',
        ]);

        parent::__construct($attributes);
    }

    public function move()
    {
        return $this->belongsTo(AccountMove::class);
    }

    public function orderLine()
    {
        return $this->belongsTo(OrderLine::class);
    }
}
