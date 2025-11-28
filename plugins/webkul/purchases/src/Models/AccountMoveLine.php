<?php

namespace Webkul\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Account\Models\MoveLine;

/**
 * Account Move Line Eloquent model
 *
 */
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

    /**
     * Move
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function move()
    {
        return $this->belongsTo(AccountMove::class);
    }

    /**
     * Purchase Order Line
     *
     * @return BelongsTo
     */
    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class, 'purchase_order_line_id');
    }
}
