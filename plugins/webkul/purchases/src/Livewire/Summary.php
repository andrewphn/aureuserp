<?php

namespace Webkul\Purchase\Livewire;

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

    #[Reactive]

    public $currency = null;

    /**
     * Mount
     *
     * @param mixed $products
     * @param mixed $currency
     */
    public function mount($products, $currency = null)

    {
        $this->products = $products ?? [];

        $this->currency = $currency;
    }

    /**
     * Render
     *
     */
    public function render()
    {
        return view('purchases::livewire/summary', [
            'products' => $this->products,
            'currency' => $this->currency,
        ]);
    }
}
