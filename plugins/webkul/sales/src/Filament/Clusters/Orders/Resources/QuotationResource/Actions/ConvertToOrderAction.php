<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\DB;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Models\OrderLine;

/**
 * Convert to Order Action
 *
 * Creates a new Sales Order from a Quote while maintaining the original quote intact.
 * This preserves the quote for reference and creates a traceable lineage.
 */
class ConvertToOrderAction extends Action
{
    /**
     * Get the default name for this action
     *
     * @return string|null
     */
    public static function getDefaultName(): ?string
    {
        return 'orders.sales.convert-to-order';
    }

    /**
     * Set up the action configuration
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->color('success')
            ->icon('heroicon-o-document-duplicate')
            ->label('Convert to Order')
            ->requiresConfirmation()
            ->modalHeading('Convert Quote to Sales Order')
            ->modalDescription('This will create a new Sales Order based on this quote. The original quote will remain unchanged for reference.')
            ->modalSubmitActionLabel('Convert to Order')
            ->hidden(fn ($record) => $record->state !== OrderState::DRAFT && $record->state !== OrderState::SENT)
            ->action(function ($record, $livewire) {
                try {
                    $newOrder = $this->convertQuoteToOrder($record);

                    $livewire->redirect(
                        OrderResource::getUrl('edit', ['record' => $newOrder]),
                        navigate: FilamentView::hasSpaMode()
                    );

                    Notification::make()
                        ->success()
                        ->title('Quote Converted Successfully')
                        ->body("Sales Order {$newOrder->name} has been created from this quote.")
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Conversion Failed')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    /**
     * Convert a quote to a sales order
     *
     * @param Order $quote The source quote
     * @return Order The new sales order
     */
    protected function convertQuoteToOrder(Order $quote): Order
    {
        return DB::transaction(function () use ($quote) {
            // Create the new order as a copy of the quote
            $orderData = $quote->replicate([
                'id',
                'name',
                'created_at',
                'updated_at',
                'deleted_at',
            ])->toArray();

            // Set state to SALE and track lineage
            $orderData['state'] = OrderState::SALE;
            $orderData['source_quote_id'] = $quote->id;
            $orderData['converted_from_quote_at'] = now();
            $orderData['date_order'] = now();
            $orderData['creator_id'] = auth()->id();

            // Create the new order
            $newOrder = Order::create($orderData);

            // Copy all order lines
            foreach ($quote->lines as $line) {
                $lineData = $line->replicate([
                    'id',
                    'order_id',
                    'created_at',
                    'updated_at',
                ])->toArray();

                $lineData['order_id'] = $newOrder->id;

                OrderLine::create($lineData);
            }

            // Copy optional lines if any
            foreach ($quote->optionalLines as $optionalLine) {
                $optionalData = $optionalLine->replicate([
                    'id',
                    'order_id',
                    'created_at',
                    'updated_at',
                ])->toArray();

                $optionalData['order_id'] = $newOrder->id;

                $quote->optionalLines()->getRelated()::create($optionalData);
            }

            // Update the quote to indicate it's been converted
            // (Optional: you could mark it as 'sent' or add a note)

            // Regenerate the order name to get proper SO format
            $newOrder->refresh();

            return $newOrder;
        });
    }
}
