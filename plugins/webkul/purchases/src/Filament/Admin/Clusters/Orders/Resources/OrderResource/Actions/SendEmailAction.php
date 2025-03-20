<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\OrderResource\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Webkul\Account\Models\Partner;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Mail\VendorPurchaseOrderMail;
use Webkul\Purchase\Models\Order;

class SendEmailAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'purchases.orders.send-email';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $userName = Auth::user()->name;

        $acceptRespondUrl = URL::signedRoute('purchases.quotations.respond', [
            'order'  => $this->getRecord()->id,
            'action' => 'accept',
        ]);

        $declineRespondUrl = URL::signedRoute('purchases.quotations.respond', [
            'order'  => $this->getRecord()->id,
            'action' => 'decline',
        ]);

        $this
            ->label(__('purchases::filament/admin/clusters/orders/resources/order/actions/send-email.label'))
            ->label(fn () => $this->getRecord()->state === OrderState::DRAFT ? __('purchases::filament/admin/clusters/orders/resources/order/actions/send-email.label') : __('purchases::filament/admin/clusters/orders/resources/order/actions/send-email.resend-label'))
            ->form([
                Forms\Components\Select::make('vendors')
                    ->label(__('purchases::filament/admin/clusters/orders/resources/order/actions/send-email.form.fields.to'))
                    ->options(Partner::get()->mapWithKeys(fn ($partner) => [
                        $partner->id => $partner->email
                            ? "{$partner->name} <{$partner->email}>"
                            : $partner->name,
                    ])->toArray())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->default(fn () => [$this->getRecord()->partner_id]),
                Forms\Components\TextInput::make('subject')
                    ->label(__('purchases::filament/admin/clusters/orders/resources/order/actions/send-email.form.fields.subject'))
                    ->required()
                    ->default("Purchase Order #{$this->getRecord()->name}"),
                Forms\Components\MarkdownEditor::make('message')
                    ->label(__('purchases::filament/admin/clusters/orders/resources/order/actions/send-email.form.fields.message'))
                    ->required()
                    ->default(<<<MD
Dear {$this->getRecord()->partner->name}  

Here is in attachment a request for quotation **{$this->getRecord()->name}**.  

If you have any questions, please do not hesitate to contact us.  

[Accept]({$acceptRespondUrl}) | [Decline]({$declineRespondUrl})  

Best regards,  

--  
{$userName}  
MD),
                Forms\Components\FileUpload::make('attachment')
                    ->hiddenLabel()
                    ->disk('public')
                    ->default(function () {
                        return $this->generatePdf($this->getRecord());
                    })
                    ->downloadable()
                    ->openable(),
            ])
            ->action(function (array $data, Order $record, Component $livewire) {
                $pdfPath = $this->generatePdf($record);

                foreach ($data['vendors'] as $vendorId) {
                    $vendor = Partner::find($vendorId);

                    if ($vendor?->email) {
                        try {
                            Mail::to($vendor->email)->send(new VendorPurchaseOrderMail($data['subject'], $data['message'], $pdfPath));
                        } catch (\Exception $e) {
                            Notification::make()
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }
                    }
                }

                $record->update([
                    'state' => OrderState::SENT,
                ]);

                $record->lines->each(function ($line) {
                    $line->update([
                        'state' => OrderState::SENT,
                    ]);
                });

                $message = $record->addMessage([
                    'body' => $data['message'],
                    'type' => 'comment',
                ]);

                $record->addAttachments(
                    [$pdfPath],
                    ['message_id' => $message->id],
                );

                Storage::delete($pdfPath);

                $livewire->updateForm();

                Notification::make()
                    ->title(__('purchases::filament/admin/clusters/orders/resources/order/actions/send-email.action.notification.success.title'))
                    ->body(__('purchases::filament/admin/clusters/orders/resources/order/actions/send-email.action.notification.success.body'))
                    ->success()
                    ->send();
            })
            ->color(fn (): string => $this->getRecord()->state === OrderState::DRAFT ? 'primary' : 'gray')
            ->visible(fn () => in_array($this->getRecord()->state, [
                OrderState::DRAFT,
                OrderState::SENT,
            ]));
    }

    private function generatePdf($record)
    {
        $pdfPath = 'Request for Quotation-'.str_replace('/', '_', $record->name).'.pdf';

        if (! Storage::exists($pdfPath)) {
            $pdf = PDF::loadView('purchases::filament.admin.clusters.orders.orders.actions.print-quotation', [
                'records'  => [$record],
            ]);

            Storage::disk('public')->put($pdfPath, $pdf->output());
        }

        return $pdfPath;
    }
}
