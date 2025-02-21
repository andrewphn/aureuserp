<?php

namespace Webkul\Sale\Traits;


use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Route;
use Webkul\Sale\Enums\OrderState;
use Webkul\Chatter\Filament\Actions as ChatterActions;
use Webkul\Partner\Models\Partner;
use Webkul\Sale\Enums\InvoiceStatus;
use Webkul\Sale\Mail\SaleOrderQuotation;
use Webkul\Support\Services\EmailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrdersResource;
use Webkul\Sale\Mail\SaleOrderCancelQuotation;

trait HasSaleOrderActions
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            ChatterActions\ChatterAction::make()
                ->setResource($this->getResource()),
            Action::make('confirm')
                ->color('gray')
                ->label(__('sales::traits/sale-order-action.header-actions.confirm.title'))
                ->hidden(fn($record) => $record->state != OrderState::DRAFT->value)
                ->action(function ($record, $livewire) {
                    $record->update([
                        'state' => OrderState::SALE->value,
                        'invoice_status' => InvoiceStatus::TO_INVOICE->value,
                    ]);

                    $this->refreshFormData(['state']);

                    $livewire->redirect(OrdersResource::getUrl('view', ['record' => $record]), navigate: FilamentView::hasSpaMode());

                    Notification::make()
                        ->success()
                        ->title(__('sales::traits/sale-order-action.header-actions.confirm.notification.confirmed.title'))
                        ->body(__('sales::traits/sale-order-action.header-actions.confirm.notification.confirmed.body'))
                        ->send();
                }),
            Action::make('backToQuotation')
                ->label(__('sales::traits/sale-order-action.header-actions.back-to-quotation.title'))
                ->color('gray')
                ->hidden(fn($record) => $record->state != OrderState::CANCEL->value)
                ->action(function ($record) {
                    $record->update([
                        'state' => OrderState::DRAFT->value,
                        'invoice_status' => InvoiceStatus::NO->value,
                    ]);

                    $this->refreshFormData(['state']);

                    Notification::make()
                        ->success()
                        ->title(__('sales::traits/sale-order-action.header-actions.back-to-quotation.notification.back-to-quotation.title'))
                        ->body(__('sales::traits/sale-order-action.header-actions.back-to-quotation.notification.back-to-quotation.body'))
                        ->send();
                }),
            Action::make('preview')
                ->modalIcon('heroicon-s-document-text')
                ->modalHeading(__('sales::traits/sale-order-action.header-actions.preview.modal.heading'))
                ->modalWidth(MaxWidth::SevenExtraLarge)
                ->modalFooterActions(function ($record) {
                    return [];
                })
                ->modalContent(function ($record) {
                    return view('sales::sales.quotation', ['record' => $record]);
                })
                ->color('gray'),
            Action::make('createInvoice')
                ->modalIcon('heroicon-s-receipt-percent')
                ->modalHeading(__('sales::traits/sale-order-action.header-actions.create-invoice.modal.heading'))
                ->hidden(fn($record) => $record->invoice_status != InvoiceStatus::TO_INVOICE->value)
                ->action(function () {})
                ->modalWidth(MaxWidth::SevenExtraLarge),
            Action::make('sendByEmail')
                ->beforeFormFilled(function ($record, Action $action) {
                    $pdf = Pdf::loadView('sales::sales.quotation', compact('record'))
                        ->setPaper('A4', 'portrait')
                        ->setOption('defaultFont', 'Arial');

                    $fileName = "$record->name-" . time() . ".pdf";
                    $filePath = 'sales-orders/' . $fileName;

                    Storage::disk('public')->put($filePath, $pdf->output());

                    $action->fillForm([
                        'file' => $filePath,
                        'partners' => [$record->partner_id],
                        'subject' => $record->partner->name . ' Quotation (Ref ' . $record->name . ')',
                        'description' => 'Dear ' . $record->partner->name . ', <br/><br/>Your quotation <strong>' . $record->name . '</strong> amounting in <strong>' . $record->currency->symbol . ' ' . $record->amount_total . '</strong> is ready for review.<br/><br/>Should you have any questions or require further assistance, please feel free to reach out to us.',
                    ]);
                })
                ->label(__('sales::traits/sale-order-action.header-actions.send-by-email.title'))
                ->form(
                    function (Form $form, $record) {
                        return $form->schema([
                            Forms\Components\Select::make('partners')
                                ->options(Partner::all()->pluck('name', 'id'))
                                ->multiple()
                                ->label(__('sales::traits/sale-order-action.header-actions.send-by-email.form.fields.partners'))
                                ->searchable()
                                ->preload(),
                            Forms\Components\TextInput::make('subject')
                                ->label(__('sales::traits/sale-order-action.header-actions.send-by-email.form.fields.subject'))
                                ->hiddenLabel(),
                            Forms\Components\RichEditor::make('description')
                                ->label(__('sales::traits/sale-order-action.header-actions.send-by-email.form.fields.description'))
                                ->hiddenLabel(),
                            Forms\Components\FileUpload::make('file')
                                ->label(__('sales::traits/sale-order-action.header-actions.send-by-email.form.fields.attachment'))
                                ->downloadable()
                                ->openable()
                                ->disk('public')
                                ->hiddenLabel(),
                        ]);
                    }
                )
                ->modalIcon('heroicon-s-envelope')
                ->modalHeading(__('sales::traits/sale-order-action.header-actions.send-by-email.modal.heading'))
                ->visible(fn($record) => $record->state != OrderState::DRAFT->value)
                ->action(function ($record, array $data) {
                    $this->handleSendByEmail($record, $data);
                }),
            Action::make('cancelQuotation')
                ->color('gray')
                ->label(__('sales::traits/sale-order-action.header-actions.cancel.title'))
                ->modalIcon('heroicon-s-x-circle')
                ->modalHeading(__('sales::traits/sale-order-action.header-actions.cancel.modal.heading'))
                ->modalDescription(__('sales::traits/sale-order-action.header-actions.cancel.modal.description'))
                ->modalFooterActions(function ($record, $livewire): array {
                    return [
                        Action::make('sendAndCancel')
                            ->label(__('sales::traits/sale-order-action.header-actions.cancel.footer-actions.send-and-cancel.title'))
                            ->icon('heroicon-o-envelope')
                            ->modalIcon('heroicon-s-envelope')
                            ->action(function () use ($record, $livewire) {
                                $record->update([
                                    'state'          => OrderState::CANCEL->value,
                                    'invoice_status' => InvoiceStatus::NO->value,
                                ]);

                                if ($livewire?->mountedActionsData[0]) {
                                    $this->handleCancelAndSendEmail($record, $livewire?->mountedActionsData[0]);
                                }

                                $this->refreshFormData(['state']);

                                Notification::make()
                                    ->success()
                                    ->title(__('sales::traits/sale-order-action.header-actions.cancel.footer-actions.send-and-cancel.notification.cancelled.title'))
                                    ->body(__('sales::traits/sale-order-action.header-actions.cancel.footer-actions.send-and-cancel.notification.cancelled.body'))
                                    ->send();
                            })
                            ->cancelParentActions(),
                        Action::make('cancel')
                            ->label(__('sales::traits/sale-order-action.header-actions.cancel.footer-actions.cancel.title'))
                            ->icon('heroicon-o-x-circle')
                            ->modalIcon('heroicon-s-x-circle')
                            ->action(function () use ($record) {
                                $record->update([
                                    'state' => OrderState::CANCEL->value,
                                    'invoice_status' => InvoiceStatus::NO->value,
                                ]);

                                $this->refreshFormData(['state']);

                                Notification::make()
                                    ->success()
                                    ->title(__('sales::traits/sale-order-action.header-actions.cancel.footer-actions.cancel.notification.cancelled.title'))
                                    ->body(__('sales::traits/sale-order-action.header-actions.cancel.footer-actions.cancel.notification.cancelled.body'))
                                    ->send();
                            })
                            ->cancelParentActions(),
                        Action::make('close')
                            ->color('gray')
                            ->label(__('sales::traits/sale-order-action.header-actions.cancel.footer-actions.close.title'))
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
                                ->label(__('sales::traits/sale-order-action.header-actions.cancel.form.fields.partner'))
                                ->preload(),
                            Forms\Components\TextInput::make('subject')
                                ->default(fn() => __('sales::traits/sale-order-action.header-actions.cancel.form.fields.subject-default', [
                                    'name' => $record->name,
                                    'id'   => $record->id,
                                ]))
                                ->placeholder(__('sales::traits/sale-order-action.header-actions.cancel.form.fields.subject-placeholder'))
                                ->label(__('sales::traits/sale-order-action.header-actions.cancel.form.fields.subject'))
                                ->hiddenLabel(),
                            Forms\Components\RichEditor::make('description')
                                ->label(__('sales::traits/sale-order-action.header-actions.cancel.form.fields.description'))
                                ->default(function () use ($record) {
                                    return __('sales::traits/sale-order-action.header-actions.cancel.form.fields.description-default', [
                                        'partner_name' => $record?->partner?->name,
                                        'name'         => $record?->name,
                                    ]);
                                })
                                ->hiddenLabel(),
                        ]);
                    }
                )
                ->hidden(fn($record) => ! in_array($record->state, [OrderState::DRAFT->value, OrderState::SENT->value, OrderState::SALE->value])),
            Actions\DeleteAction::make(),
        ];

        if (method_exists($this, 'getAdditionalHeaderActions')) {
            $actions = [
                ...$actions,
                ...$this->getAdditionalHeaderActions(),
            ];
        }

        return $actions;
    }

    private function preparePayloadForSendByEmail($record, $partner, $data)
    {
        $modalName = OrderState::options()[$record->state];

        return [
            'record_url'     => $this->getRedirectUrl() ?? '',
            'record_name'    => $record->name,
            'model_name'     => $modalName,
            'subject'        => $data['subject'],
            'description'    => $data['description'],
            'to'             => [
                'address' => $partner?->email,
                'name'    => $partner?->name,
            ],
        ];
    }

    private function handleSendByEmail($record, $data)
    {
        $partners = Partner::whereIn('id', $data['partners'])->get();

        foreach ($partners as $key => $partner) {
            app(EmailService::class)->send(
                mailClass: SaleOrderQuotation::class,
                view: $viewName = 'sales::mails.sale-order-quotation',
                payload: $this->preparePayloadForSendByEmail($record, $partner, $data),
                attachments: [
                    [
                        'path' => $path = asset(Storage::url($data['file'])),
                        'name' => basename($data['file']),
                    ],
                ]
            );
        }

        $record->state = OrderState::SENT->value;
        $record->save();

        $messageData = [
            'from' => [
                'company' => Auth::user()->defaultCompany->toArray(),
            ],
            'body' => view($viewName, [
                'payload' => $this->preparePayloadForSendByEmail($record, $partner, $data),
            ])->render(),
            'type' => 'comment',
        ];

        $record->addMessage($messageData, Auth::user()->id);

        $this->refreshFormData(['state']);

        Notification::make()
            ->success()
            ->title(__('sales::traits/sale-order-action.header-actions.send-by-email.actions.notification.title'))
            ->body(__('sales::traits/sale-order-action.header-actions.send-by-email.actions.notification.body'))
            ->send();
    }

    private function preparePayloadForCancelAndSendEmail($record, $partner, $data): array
    {
        return [
            'record_url'     => $this->getRedirectUrl() ?? '',
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
