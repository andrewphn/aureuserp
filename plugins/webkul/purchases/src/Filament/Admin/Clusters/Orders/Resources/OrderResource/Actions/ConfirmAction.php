<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Models\Order;
use Webkul\Purchase\Settings\OrderSettings;
use Illuminate\Support\Facades\Auth;
use Webkul\Support\Package;
use Webkul\Inventory\Models\Receipt;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\Location;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\PurchaseOrderResource;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Enums as InventoryEnums;
use Webkul\Product\Enums\ProductType;

class ConfirmAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'purchases.orders.confirm';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('purchases::filament/admin/clusters/orders/resources/order/actions/confirm.label'))
            ->requiresConfirmation()
            ->color(fn (): string => $this->getRecord()->state === OrderState::DRAFT ? 'gray' : 'primary')
            ->action(function (Order $record, Component $livewire, OrderSettings $orderSettings): void {
                $record->update([
                    'state'       => $orderSettings->enable_lock_confirmed_orders ? OrderState::DONE : OrderState::PURCHASE,
                    'approved_at' => now(),
                ]);

                foreach ($record->lines as $move) {
                    $move->update([
                        'state' => $orderSettings->enable_lock_confirmed_orders ? OrderState::DONE : OrderState::PURCHASE,
                    ]);
                }

                $this->createReceipt($record);

                $livewire->updateForm();

                Notification::make()
                    ->title(__('purchases::filament/admin/clusters/orders/resources/order/actions/confirm.action.notification.success.title'))
                    ->body(__('purchases::filament/admin/clusters/orders/resources/order/actions/confirm.action.notification.success.body'))
                    ->success()
                    ->send();
            })
            ->visible(fn () => ! in_array($this->getRecord()->state, [
                OrderState::PURCHASE,
                OrderState::DONE,
                OrderState::CANCELED,
            ]));
    }

    protected function createReceipt(Order $record): void
    {
        if (! in_array($record->state, [OrderState::PURCHASE, OrderState::DONE])) {
            return;
        }

        if (! $record->lines->contains(fn ($line) => $line->product->type === ProductType::GOODS)) {
            return;
        }

        if (! Package::isPluginInstalled('inventories')) {
            return;
        }

        $record->operation_type_id = $this->getOperationType($record)->id;

        $record->save();

        $supplierLocation = Location::where('type', InventoryEnums\LocationType::SUPPLIER)->first();

        $operation = Receipt::create([
            'state'                   => InventoryEnums\OperationState::DRAFT,
            'move_type'               => InventoryEnums\MoveType::DIRECT,
            'origin'                  => $record->name,
            'partner_id'              => $record->partner_id,
            'partner_address_id'      => $record->partner_address_id,
            'date'                    => $record->ordered_at,
            'operation_type_id'       => $record->operation_type_id,
            'source_location_id'      => $supplierLocation->id,
            'destination_location_id' => $record->operationType->destination_location_id,
            'company_id'              => $record->company_id,
            'user_id'                 => Auth::id(),
            'creator_id'              => Auth::id(),
        ]);

        $operation->save();

        foreach ($record->lines as $line) {
            $line->update([
                'final_location_id' => $this->getFinalLocation($record)->id,
            ]);

            $move = Move::create([
                'operation_id'            => $operation->id,
                'name'                    => $operation->name,
                'reference'               => $operation->name,
                'description_picking'     => $line->product->picking_description ?? $line->name,
                'state'                   => InventoryEnums\MoveState::DRAFT,
                'scheduled_at'            => $line->planned_at,
                'deadline'                => $line->planned_at,
                'reservation_date'        => now(),
                'product_packaging_id'    => $line->product_packaging_id,
                'product_id'              => $line->product_id,
                'product_qty'             => $line->product_qty,
                'product_uom_qty'         => $line->product_uom_qty,
                'quantity'                => $line->product_uom_qty,
                'uom_id'                  => $line->uom_id,
                'partner_id'              => $operation->partner_id,
                'warehouse_id'            => $operation->destinationLocation->warehouse_id,
                'source_location_id'      => $operation->source_location_id,
                'destination_location_id' => $operation->destination_location_id,
                'operation_type_id'       => $operation->operation_type_id,
                'final_location_id'       => $line->final_location_id,
                'company_id'              => $operation->destinationLocation->company_id,
                'purchase_line_id'        => $line->id,
            ]);
        }

        $record->operations()->attach($operation->id);

        $operation->refresh();

        foreach ($operation->moves as $move) {
            OperationResource::updateOrCreateMoveLines($move);
        }

        OperationResource::updateOperationState($operation);

        $url = PurchaseOrderResource::getUrl('view', ['record' => $record]);

        $operation->addMessage([
            'body' => "This transfer has been created from <a href=\"{$url}\" target=\"_blank\" class=\"text-primary-600 dark:text-primary-400\">{$record->name}</a>.",
            'type' => 'comment',
        ]);
    }

    protected function getOperationType(Order $record): ?OperationType
    {
        $operationType = OperationType::where('type', '=', InventoryEnums\OperationType::INCOMING)
            ->whereHas('warehouse', function($query) use ($record) {
                $query->where('company_id', '=', $record->company_id);
            })
            ->first();
        
        if (! $operationType) {
            $operationType = OperationType::where('type', '=', InventoryEnums\OperationType::INCOMING)
                ->whereDoesntHave('warehouse')
                ->first();
        }

        return $operationType;
    }

    protected function getFinalLocation(Order $record): ?Location
    {
        return $record->operationType->warehouse->lotStockLocation;
    }
}
