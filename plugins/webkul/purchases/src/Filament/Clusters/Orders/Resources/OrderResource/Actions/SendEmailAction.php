<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Actions;

use Filament\Actions\Action;
use Webkul\Purchase\Enums;
use Filament\Forms;
use Webkul\Account\Models\Partner;
use Livewire\Component;
use Webkul\Purchase\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Webkul\Purchase\Mail\VendorPurchaseOrderMail;

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

        $this
            ->label(__('purchases::filament/clusters/orders/resources/order/actions/send-email.label'))
            ->form([
                Forms\Components\Select::make('vendors')
                    ->label(__('purchases::filament/clusters/orders/resources/order/actions/send-email.form.fields.to'))
                    ->options(Partner::get()->mapWithKeys(fn ($partner) => [
                        $partner->id => $partner->email 
                            ? "{$partner->name} <{$partner->email}>" 
                            : $partner->name
                    ])->toArray())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->default(fn () => [$this->getRecord()->partner_id]),
                Forms\Components\TextInput::make('subject')
                    ->label(__('purchases::filament/clusters/orders/resources/order/actions/send-email.form.fields.subject'))
                    ->required()
                    ->default("Purchase Order #{$this->getRecord()->name}"),
                Forms\Components\RichEditor::make('message')
                    ->label(__('purchases::filament/clusters/orders/resources/order/actions/send-email.form.fields.message'))
                    ->required()
                    ->default("<p>Dear {$this->getRecord()->partner->name} <br><br>Here is in attachment a request for quotation <strong>{$this->getRecord()->name}</strong> from My Company.
                            
                            <br><br>
                            
                            If you have any questions, please do not hesitate to contact us.
                            
                            <br><br>
                            
                            <a href=\"http://localhost:8069/my/purchase/8?access_token={$this->getRecord()->access_token}\">Accept</a>
                            
                            <a href=\"http://localhost:8069/my/purchase/8?access_token={$this->getRecord()->access_token}\">Decline</a>
                            
                            <br><br>
                            
                            Best regards,
                            
                            <br><br>
                            --<br>
                            {$userName}
                        </p>
                    "),
                Forms\Components\FileUpload::make('attachment')
                    ->hiddenLabel()
                    ->disk('public')
                    ->default(function() {
                        return $this->generatePdf($this->getRecord());
                    })
                    ->downloadable()
                    ->openable(),
            ])
            ->action(function (array $data, $record, Component $livewire) {
                $pdfPath = $this->generatePdf($record);

                foreach ($data['vendors'] as $vendorId) {
                    $vendor = Partner::find($vendorId);

                    if ($vendor?->email) {
                        Mail::to($vendor->email)->send(new VendorPurchaseOrderMail($data['subject'], $data['message'], $pdfPath));
                    }
                }

                $record->update([
                    'state' => Enums\OrderState::SENT,
                ]);

                Storage::delete($pdfPath);

                $livewire->updateForm();
            })
            ->hidden(fn () => in_array($this->getRecord()->state, [
                Enums\OrderState::DONE,
                Enums\OrderState::CANCELED,
            ]));
    }

    private function generatePdf($record)
    {
        $pdfPath = 'Purchase Order-'.str_replace('/', '_', $record->name).'.pdf';

        if (! Storage::exists($pdfPath)) {
            $pdf = PDF::loadView('purchases::filament.clusters.orders.orders.actions.print-quotation', [
                'records'  => [$record],
            ]);

            Storage::disk('public')->put($pdfPath, $pdf->output());
        }

        return $pdfPath;
    }
}
