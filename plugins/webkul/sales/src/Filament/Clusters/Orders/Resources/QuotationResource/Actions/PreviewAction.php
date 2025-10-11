<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Filament\Actions\Action;
use Illuminate\Support\HtmlString;
use Webkul\Sale\Services\TemplateRenderer;

class PreviewAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orders.sales.preview-quotation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('sales::filament/clusters/orders/resources/quotation/actions/preview.title'))
            ->modalFooterActions(fn ($record) => [])
            ->modalContent(function ($record) {
                // If the order has a document template, use the template renderer
                if ($record->document_template_id && $record->documentTemplate) {
                    $renderer = new TemplateRenderer();
                    $html = $renderer->render($record->documentTemplate, $record);

                    return new HtmlString($html);
                }

                // Fall back to the default blade view
                return view('sales::sales.quotation', ['record' => $record]);
            })
            ->modalWidth('7xl')
            ->color('gray');
    }
}
