<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Webkul\Partner\Models\Partner;
use Webkul\Sale\Enums\InvoiceStatus;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Mail\SaleOrderCancelQuotation;
use Webkul\Support\Services\EmailService;

class CancelQuotationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orders.sales.cancel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->color('gray')
            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.title'))
            ->modalIcon('heroicon-s-x-circle')
            ->modalHeading(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.modal.heading'))
            ->modalDescription(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.modal.description'))
            ->modalFooterActions(function ($record, $livewire): array {
                return [
                    Action::make('sendAndCancel')
                        ->label(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.footer-actions.send-and-cancel.title'))
                        ->icon('heroicon-o-envelope')
                        ->modalIcon('heroicon-s-envelope')
                        ->action(function () use ($record, $livewire) {
                            $record->update([
                                'state'          => OrderState::CANCEL,
                                'invoice_status' => InvoiceStatus::NO,
                            ]);

                            $record->lines->each(function ($line) {
                                $line->state = OrderState::CANCEL;
                                $line->save();
                            });

                            if ($livewire?->mountedActionsData[0]) {
                                $this->handleCancelAndSendEmail($record, $livewire?->mountedActionsData[0]);
                            }

                            $livewire->refreshFormData(['state']);

                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.footer-actions.send-and-cancel.notification.cancelled.title'))
                                ->body(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.footer-actions.send-and-cancel.notification.cancelled.body'))
                                ->send();
                        })
                        ->cancelParentActions(),
                    Action::make('cancel')
                        ->label(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.footer-actions.cancel.title'))
                        ->icon('heroicon-o-x-circle')
                        ->modalIcon('heroicon-s-x-circle')
                        ->action(function () use ($record, $livewire) {
                            $record->update([
                                'state'          => OrderState::CANCEL,
                                'invoice_status' => InvoiceStatus::NO,
                            ]);

                            $record->lines->each(function ($line) {
                                $line->state = OrderState::CANCEL;
                                $line->save();
                            });

                            $livewire->refreshFormData(['state']);

                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.footer-actions.cancel.notification.cancelled.title'))
                                ->body(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.footer-actions.cancel.notification.cancelled.body'))
                                ->send();
                        })
                        ->cancelParentActions(),
                    Action::make('close')
                        ->color('gray')
                        ->label(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.footer-actions.close.title'))
                        ->cancelParentActions(),
                ];
            })
            ->form(
                function (Form $form, $record) {
                    return $form->schema([
                        Forms\Components\Select::make('partners')
                            ->options(Partner::all()->pluck('name', 'id'))
                            ->multiple()
                            ->default([$record->partner_id])
                            ->searchable()
                            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.form.fields.partner'))
                            ->preload(),
                        Forms\Components\TextInput::make('subject')
                            ->default(fn () => __('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.form.fields.subject-default', [
                                'name' => $record->name,
                                'id'   => $record->id,
                            ]))
                            ->placeholder(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.form.fields.subject-placeholder'))
                            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.form.fields.subject'))
                            ->hiddenLabel(),
                        Forms\Components\RichEditor::make('description')
                            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.form.fields.description'))
                            ->default(function () use ($record) {
                                return __('sales::filament/clusters/orders/resources/quotation/actions/cancel-quotation.form.fields.description-default', [
                                    'partner_name' => $record?->partner?->name,
                                    'name'         => $record?->name,
                                ]);
                            })
                            ->hiddenLabel(),
                    ]);
                }
            )
            ->hidden(fn ($record) => ! in_array($record->state, [OrderState::DRAFT, OrderState::SENT, OrderState::SALE]));
    }

    private function preparePayloadForCancelAndSendEmail($record, $partner, $data): array
    {
        return [
            'record_name'    => $record->name,
            'model_name'     => 'Quotation',
            'subject'        => $data['subject'],
            'description'    => $data['description'],
            'to'             => [
                'address' => $partner?->email,
                'name'    => $partner?->name,
            ],
        ];
    }

    private function handleCancelAndSendEmail($record, $data)
    {
        $partners = Partner::whereIn('id', $data['partners'])->get();

        foreach ($partners as $key => $partner) {
            app(EmailService::class)->send(
                mailClass: SaleOrderCancelQuotation::class,
                view: $viewName = 'sales::mails.sale-order-cancel-quotation',
                payload: $this->preparePayloadForCancelAndSendEmail($record, $partner, $data),
            );
        }

        $messageData = [
            'from' => [
                'company' => Auth::user()->defaultCompany->toArray(),
            ],
            'body' => view($viewName, [
                'payload' => $this->preparePayloadForCancelAndSendEmail($record, $partner, $data),
            ])->render(),
            'type' => 'comment',
        ];

        $record->addMessage($messageData, Auth::user()->id);
    }
}
