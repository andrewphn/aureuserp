<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Webkul\Partner\Models\Partner;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Facades\SaleOrder;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource;
use Webkul\Support\Concerns\HasRepeaterColumnManager;

/**
 * Create Quotation class
 *
 * @see \Filament\Resources\Resource
 */
class CreateQuotation extends CreateRecord
{
    use HasRepeaterColumnManager;

    protected static string $resource = QuotationResource::class;

    /**
     * Get the header actions for this page
     *
     * @return array<\Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewTemplate')
                ->label('Preview Template')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->modalContent(function (): View {
                    $documentTemplateId = $this->data['document_template_id'] ?? null;

                    return view('sales::filament.components.preview-wrapper', [
                        'documentTemplateId' => $documentTemplateId,
                        'formData' => $this->data
                    ]);
                })
                ->slideOver()
                ->stickyModalHeader()
                ->stickyModalFooter()
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->extraModalFooterActions([
                    Action::make('print')
                        ->label('Print')
                        ->icon('heroicon-o-printer')
                        ->color('primary')
                        ->action(fn () => null)
                        ->extraAttributes([
                            'onclick' => 'window.print()',
                        ]),
                ]),
        ];
    }

    /**
     * Get the redirect URL after creation
     *
     * @return string
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    /**
     * Get the notification to display after creation
     *
     * @return \Filament\Notifications\Notification|null
     */
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('sales::filament/clusters/orders/resources/quotation/pages/create-quotation.notification.title'))
            ->body(__('sales::filament/clusters/orders/resources/quotation/pages/create-quotation.notification.body'));
    }

    /**
     * Mutate Form Data Before Create
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['creator_id'] = $user->id;
        $data['user_id'] = $user->id;
        $data['company_id'] = $user->default_company_id;
        $data['state'] = OrderState::DRAFT;
        $data['create_date'] = now();

        if ($data['partner_id']) {
            $partner = Partner::find($data['partner_id']);
            $data['commercial_partner_id'] = $partner->id;
            $data['partner_shipping_id'] = $partner->id;
            $data['partner_invoice_id'] = $partner->id;
            $data['order_partner_id'] = $partner->id;
        }

        return $data;
    }

    /**
     * After Create
     *
     * @return void
     */
    protected function afterCreate(): void
    {
        SaleOrder::computeSaleOrder($this->getRecord());
    }
}
