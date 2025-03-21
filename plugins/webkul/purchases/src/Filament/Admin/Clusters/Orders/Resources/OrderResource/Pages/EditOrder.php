<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages;

use Filament\Actions\Action;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Purchase\Enums;
use Webkul\Purchase\Facades\PurchaseOrder;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Actions as OrderActions;
use Webkul\Purchase\Models\Order;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('purchases::filament/admin/clusters/orders/resources/order/pages/edit-order.notification.title'))
            ->body(__('purchases::filament/admin/clusters/orders/resources/order/pages/edit-order.notification.body'));
    }

    protected function configureAction(Action $action): void
    {
        if ($action instanceof ChatterAction) {
            $order = Order::find($this->getRecord()->id);

            $action
                ->record($order)
                ->recordTitle($this->getRecordTitle());

            return;
        }

        parent::configureAction($action);
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(self::$resource),
            OrderActions\SendEmailAction::make(),
            OrderActions\SendPOEmailAction::make(),
            OrderActions\PrintRFQAction::make(),
            OrderActions\DraftAction::make(),
            OrderActions\ConfirmAction::make(),
            OrderActions\ConfirmReceiptDateAction::make(),
            OrderActions\CreateBillAction::make(),
            OrderActions\LockAction::make(),
            OrderActions\UnlockAction::make(),
            OrderActions\CancelAction::make(),
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->getRecord()->state == Enums\OrderState::DONE)
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('purchases::filament/admin/clusters/orders/resources/order/pages/edit-order.header-actions.delete.notification.title'))
                        ->body(__('purchases::filament/admin/clusters/orders/resources/order/pages/edit-order.header-actions.delete.notification.body')),
                ),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['ordered_at'])) {
            $data['calendar_start_at'] = $data['ordered_at'];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        PurchaseOrder::computePurchaseOrder($this->getRecord());
    }

    public function updateForm(): void
    {
        $this->fillForm();
    }
}
