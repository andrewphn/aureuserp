<?php

namespace Webkul\Sale\Settings;

use Spatie\LaravelSettings\Settings;
use Webkul\Invoice\Enums\InvoicePolicy;

/**
 * Invoice Settings class
 *
 */
class InvoiceSettings extends Settings
{
    public InvoicePolicy $invoice_policy;

    /**
     * Group
     *
     * @return string
     */
    public static function group(): string
    {
        return 'sales_invoicing';
    }
}
