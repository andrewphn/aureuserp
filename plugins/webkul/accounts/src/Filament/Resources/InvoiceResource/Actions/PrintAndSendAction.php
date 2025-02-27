<?php

namespace Webkul\Account\Filament\Resources\InvoiceResource\Actions;

use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\Partner;
use Webkul\Support\Traits\PDFHandler;

class PrintAndSendAction extends Action
{
    use PDFHandler;

    public static function getDefaultName(): ?string
    {
        return 'customers.invoice.print-and-send';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Print & Send'))
            ->color('gray')
            ->visible(function (Move $record) {
                return
                    $record->state == MoveState::CANCEL->value
                    || $record->state == MoveState::POSTED->value;
            });

        $this->beforeFormFilled(function ($record, Action $action) {
            $description = "
                    <p>Dear {$record->partner->name},</p>
                    <p>Your invoice <strong>{$record->name}</strong> from <strong>{$record->company->name}</strong> for <strong>{$record->currency->symbol} {$record->amount_total}</strong> is now available. Kindly arrange payment at your earliest convenience.</p>
                    <p>When making the payment, please reference <strong>{$record->name}</strong> for account <strong>" . ($record->partnerBank->bank->name ?? 'N/A') . "</strong>.</p>
                    <p>If you have any questions, feel free to reach out.</p>
                    <p><strong>Best regards,</strong><br>Administrator</p>
                ";

            $action->fillForm([
                'file'        => $this->prepareInvoice($record),
                'partners'    => [$record->partner_id],
                'subject'     => $record->partner->name . ' Invoice (Ref ' . $record->name . ')',
                'description' => $description,
            ]);
        });

        $this->form(
            function (Form $form) {
                return $form->schema([
                    Forms\Components\Select::make('partners')
                        ->options(Partner::all()->pluck('name', 'id'))
                        ->multiple()
                        ->label(__('Customer'))
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('subject')
                        ->label(__('Subject'))
                        ->hiddenLabel(),
                    Forms\Components\RichEditor::make('description')
                        ->label(__('Description'))
                        ->hiddenLabel(),
                    Forms\Components\FileUpload::make('file')
                        ->label(__('Attachment'))
                        ->downloadable()
                        ->openable()
                        ->multiple()
                        ->disk('public')
                        ->hiddenLabel(),
                ]);
            }
        );

        $this->action(function ($record, array $data) {
            $this->handleSendByEmail($record, $data);
        });
    }

    private function prepareInvoice(Move $record): ?string
    {
        return $this->downloadPDF(
            view('accounts::invoice/actions/preview.index', compact('record'))->render(),
            'invoice-' . $record->created_at->format('d-m-Y')
        );
    }

    private function handleSendByEmail(Move $record, array $data): void {}
}
