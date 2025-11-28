<?php

namespace Webkul\Account\Livewire;

use Livewire\Attributes\Reactive;
use Livewire\Component;

/**
 * Invoice Summary class
 *
 */
class InvoiceSummary extends Component
{
    #[Reactive]
    public $products = [];

    public $subtotal = 0;

    public $totalDiscount = 0;

    public $totalTax = 0;

    public $grandTotal = 0;

    public $amountTax = 0;

    #[Reactive]
    public $currency = null;

    /**
     * Mount
     *
     * @param mixed $currency
     * @param mixed $products
     */
    public function mount($currency, $products)
    {
        $this->currency = $currency;

        $this->products = $products ?? [];
    }

    /**
     * Render
     *
     */
    public function render()
    {
        return view('accounts::livewire/invoice-summary', [
            'products' => $this->products,
        ]);
    }
}
