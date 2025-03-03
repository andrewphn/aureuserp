<?php

namespace Webkul\Purchase\Livewire;

use Filament\Pages\SimplePage;
use Webkul\Purchase\Models\Order;

class RespondQuotation extends SimplePage
{
    protected static string $view = 'purchases::livewire.respond-quotation';

    public int $order;

    public string $action;

    public function mount(): void
    {
        $order = Order::findOrFail($this->order);

        $message = $order->addMessage([
            'body' => $this->action === 'accept'
                ? 'The RFQ has been acknowledged by vendor.'
                : 'The RFQ has been declined by vendor.',
            'type'=>'comment',
        ]);
    }

    public function getHeading(): string
    {
        if ($this->action === 'accept') {
            return 'Quotation Accepted';
        }

        return 'Quotation Declined';
    }
}
