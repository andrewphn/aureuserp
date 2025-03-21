<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\Partner\Models\Partner;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Mail\SaleOrderQuotation;
use Webkul\Support\Services\EmailService;

class SendByEmailAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orders.sales.send-by-email';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->beforeFormFilled(function ($record, Action $action) {
                $pdf = Pdf::loadView('sales::sales.quotation', compact('record'))
                    ->setPaper('A4', 'portrait')
                    ->setOption('defaultFont', 'Arial');

                $fileName = "$record->name-".time().'.pdf';
                $filePath = 'sales-orders/'.$fileName;

                Storage::disk('public')->put($filePath, $pdf->output());

                $action->fillForm([
                    'file'        => $filePath,
                    'partners'    => [$record->partner_id],
                    'subject'     => $record->partner->name.' Quotation (Ref '.$record->name.')',
                    'description' => 'Dear '.$record->partner->name.', <br/><br/>Your quotation <strong>'.$record->name.'</strong> amounting in <strong>'.$record->currency->symbol.' '.$record->amount_total.'</strong> is ready for review.<br/><br/>Should you have any questions or require further assistance, please feel free to reach out to us.',
                ]);
            })
            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/send-by-email.title'))
            ->form(
                function (Form $form, $record) {
                    return $form->schema([
                        Forms\Components\Select::make('partners')
                            ->options(Partner::all()->pluck('name', 'id'))
                            ->multiple()
                            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/send-by-email.form.fields.partners'))
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('subject')
                            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/send-by-email.form.fields.subject'))
                            ->hiddenLabel(),
                        Forms\Components\RichEditor::make('description')
                            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/send-by-email.form.fields.description'))
                            ->hiddenLabel(),
                        Forms\Components\FileUpload::make('file')
                            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/send-by-email.form.fields.attachment'))
                            ->downloadable()
                            ->openable()
                            ->disk('public')
                            ->hiddenLabel(),
                    ]);
                }
            )
            ->modalIcon('heroicon-s-envelope')
            ->modalHeading(__('sales::filament/clusters/orders/resources/quotation/actions/send-by-email.modal.heading'))
            ->hidden(fn ($record) => $record->state != OrderState::SALE)
            ->action(function ($record, array $data) {
                $this->handleSendByEmail($record, $data);
            });
    }

    private function preparePayloadForSendByEmail($record, $partner, $data)
    {
        $modalName = OrderState::options()[$record->state];

        return [
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

        $record->state = OrderState::SENT;
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
            ->title(__('sales::filament/clusters/orders/resources/quotation/actions/send-by-email.actions.notification.title'))
            ->body(__('sales::filament/clusters/orders/resources/quotation/actions/send-by-email.actions.notification.body'))
            ->send();
    }
}
