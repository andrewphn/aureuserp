<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\EditRecord;
use Filament\Tables\Concerns\HasColumnManager;
use Webkul\Chatter\Filament\Actions as ChatterActions;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Facades\SaleOrder;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions as BaseActions;
use Webkul\Support\Filament\Forms\Components\Repeater;

class EditQuotation extends EditRecord
{
    use HasColumnManager;

    protected static string $resource = QuotationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('sales::filament/clusters/orders/resources/quotation/pages/edit-quotation.notification.title'))
            ->body(__('sales::filament/clusters/orders/resources/quotation/pages/edit-quotation.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterActions\ChatterAction::make()
                ->setResource($this->getResource()),
            BaseActions\BackToQuotationAction::make(),
            BaseActions\CancelQuotationAction::make(),
            BaseActions\ConfirmAction::make(),
            BaseActions\CreateInvoiceAction::make(),
            BaseActions\PreviewAction::make(),
            BaseActions\SendByEmailAction::make(),
            BaseActions\LockAndUnlockAction::make(),
            DeleteAction::make()
                ->hidden(fn() => $this->getRecord()->state == OrderState::SALE)
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('sales::filament/clusters/orders/resources/quotation/pages/edit-quotation.header-actions.notification.delete.title'))
                        ->body(__('sales::filament/clusters/orders/resources/quotation/pages/edit-quotation.header-actions.notification.delete.body')),
                ),
        ];
    }

    protected function afterSave(): void
    {
        SaleOrder::computeSaleOrder($this->getRecord());
    }

    public function applyRepeaterColumnManager(string $repeaterKey, array $columns): void
    {
        $repeater = $this->getRepeaterComponent($repeaterKey);

        if ($repeater) {
            $repeater->applyTableColumnManager($columns);
        }
    }

    public function resetRepeaterColumnManager(string $repeaterKey): void
    {
        $repeater = $this->getRepeaterComponent($repeaterKey);

        if ($repeater) {
            $repeater->resetTableColumnManager();
        }
    }

    protected function getRepeaterComponent(string $repeaterKey): ?Repeater
    {
        $form = $this->form->getFlatComponents();

        foreach ($form as $component) {
            if ($component instanceof Repeater && $component->getStatePath() === $repeaterKey) {
                return $component;
            }

            if (method_exists($component, 'getChildComponents')) {
                $found = $this->findRepeaterInComponents($component->getChildComponents(), $repeaterKey);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    protected function findRepeaterInComponents(array $components, string $repeaterKey): ?Repeater
    {
        foreach ($components as $component) {
            if ($component instanceof Repeater && $component->getStatePath() === $repeaterKey) {
                return $component;
            }

            if (method_exists($component, 'getChildComponents')) {
                $found = $this->findRepeaterInComponents($component->getChildComponents(), $repeaterKey);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }
}
