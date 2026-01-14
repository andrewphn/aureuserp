<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources\ReceiptResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Filament\Clusters\Operations\Actions as OperationActions;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ReceiptResource;
use Webkul\Inventory\Models\Receipt;
use Webkul\Purchase\Models\Order as PurchaseOrder;
use Webkul\Support\Concerns\HasRepeaterColumnManager;
use App\Filament\Forms\Components\AiDocumentScanner;

/**
 * Edit Receipt class
 *
 * @see \Filament\Resources\Resource
 */
class EditReceipt extends EditRecord
{
    use HasRepeaterColumnManager;

    protected static string $resource = ReceiptResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('inventories::filament/clusters/operations/resources/receipt/pages/edit-receipt.notification.title'))
            ->body(__('inventories::filament/clusters/operations/resources/receipt/pages/edit-receipt.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            $this->getScanDocumentAction(),
            OperationActions\TodoAction::make(),
            OperationActions\ValidateAction::make(),
            OperationActions\CancelAction::make(),
            OperationActions\ReturnAction::make(),
            ActionGroup::make([
                OperationActions\Print\PickingOperationAction::make(),
                OperationActions\Print\DeliverySlipAction::make(),
                OperationActions\Print\PackageAction::make(),
                OperationActions\Print\LabelsAction::make(),
            ])
                ->label(__('inventories::filament/clusters/operations/resources/receipt/pages/edit-receipt.header-actions.print.label'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->button(),
            DeleteAction::make()
                ->hidden(fn () => $this->getRecord()->state == OperationState::DONE)
                ->action(function (DeleteAction $action, Receipt $record) {
                    try {
                        $record->delete();

                        $action->success();
                    } catch (QueryException $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('inventories::filament/clusters/operations/resources/receipt/pages/edit-receipt.header-actions.delete.notification.error.title'))
                            ->body(__('inventories::filament/clusters/operations/resources/receipt/pages/edit-receipt.header-actions.delete.notification.error.body'))
                            ->send();

                        $action->failure();
                    }
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('inventories::filament/clusters/operations/resources/receipt/pages/edit-receipt.header-actions.delete.notification.success.title'))
                        ->body(__('inventories::filament/clusters/operations/resources/receipt/pages/edit-receipt.header-actions.delete.notification.success.body')),
                ),
        ];
    }

    /**
     * Update Form
     *
     * @return void
     */
    public function updateForm(): void
    {
        $this->fillForm();
    }

    /**
     * Get the AI Document Scanner action for receiving
     *
     * @return Action
     */
    protected function getScanDocumentAction(): Action
    {
        $record = $this->getRecord();
        $partnerId = $record->partner_id;

        return Action::make('scanDocument')
            ->label('Scan Document')
            ->icon('heroicon-o-camera')
            ->color('info')
            ->visible(fn () => !in_array($record->state, [OperationState::DONE, OperationState::CANCELED]))
            ->modalHeading('AI Document Scanner')
            ->modalDescription('Upload a packing slip or invoice to auto-populate receiving quantities. Select a PO first to help match products.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalWidth('5xl')
            ->slideOver()
            ->form([
                Section::make('Pre-Scan Options')
                    ->description('Select a purchase order to help the AI match products more accurately')
                    ->schema([
                        Select::make('pre_selected_po_id')
                            ->label('Purchase Order (Optional)')
                            ->placeholder('Select a PO to filter expected products...')
                            ->options(function () use ($partnerId) {
                                $query = PurchaseOrder::query()
                                    ->whereIn('state', ['purchase', 'done'])
                                    ->whereHas('lines', function (Builder $q) {
                                        $q->whereRaw('product_qty > COALESCE(qty_received, 0)');
                                    });

                                // Filter by vendor if we have one set on the receipt
                                if ($partnerId) {
                                    $query->where('partner_id', $partnerId);
                                }

                                return $query->orderByDesc('created_at')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($po) {
                                        $pendingLines = $po->lines->filter(fn ($l) => $l->product_qty > ($l->qty_received ?? 0))->count();
                                        return [$po->id => "{$po->name} - {$po->partner->name} ({$pendingLines} pending lines)"];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Selecting a PO helps match products and verify quantities')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $po = PurchaseOrder::with(['lines.product', 'partner'])->find($state);
                                    if ($po) {
                                        $set('po_info', $this->formatPoInfo($po));
                                    }
                                } else {
                                    $set('po_info', null);
                                }
                            }),
                        Placeholder::make('po_info')
                            ->label('PO Details')
                            ->content(fn ($get) => $get('po_info') ?? 'No PO selected')
                            ->visible(fn ($get) => filled($get('pre_selected_po_id'))),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->columns(1),

                Section::make('Document Scanner')
                    ->schema([
                        AiDocumentScanner::make()
                            ->forReceiving(),
                    ]),
            ]);
    }

    /**
     * Format PO information for display
     */
    protected function formatPoInfo(PurchaseOrder $po): \Illuminate\Contracts\Support\Htmlable
    {
        $pendingLines = $po->lines->filter(fn ($l) => $l->product_qty > ($l->qty_received ?? 0));

        $html = '<div class="text-sm space-y-2">';
        $html .= '<div class="flex justify-between"><span class="font-medium">Vendor:</span> <span>' . e($po->partner->name) . '</span></div>';
        $html .= '<div class="flex justify-between"><span class="font-medium">Total Lines:</span> <span>' . $po->lines->count() . '</span></div>';
        $html .= '<div class="flex justify-between"><span class="font-medium">Pending Receipt:</span> <span>' . $pendingLines->count() . ' lines</span></div>';

        if ($pendingLines->isNotEmpty()) {
            $html .= '<div class="mt-3 border-t pt-2"><span class="font-medium text-xs uppercase text-gray-500">Expected Products:</span></div>';
            $html .= '<ul class="text-xs space-y-1 max-h-32 overflow-y-auto">';
            foreach ($pendingLines->take(10) as $line) {
                $remaining = $line->product_qty - ($line->qty_received ?? 0);
                $html .= '<li class="flex justify-between">';
                $html .= '<span class="truncate mr-2">' . e($line->product->name ?? 'Unknown') . '</span>';
                $html .= '<span class="font-mono whitespace-nowrap">' . number_format($remaining, 2) . '</span>';
                $html .= '</li>';
            }
            if ($pendingLines->count() > 10) {
                $html .= '<li class="text-gray-400 italic">... and ' . ($pendingLines->count() - 10) . ' more</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div>';

        return new \Illuminate\Support\HtmlString($html);
    }
}
