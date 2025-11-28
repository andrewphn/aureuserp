<?php

namespace Webkul\Sale\Livewire;

use Livewire\Attributes\Reactive;
use Livewire\Component;

/**
 * Summary class
 *
 */
class Summary extends Component
{
    #[Reactive]
    public $products = [];

    public $subtotal = 0;

    public $totalDiscount = 0;

    public $totalTax = 0;

    public $grandTotal = 0;

    public $amountTax = 0;

    public $enableMargin = false;

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
        return view('sales::livewire/summary', [
            'products' => $this->products,
        ]);
    }
}
