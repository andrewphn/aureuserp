<?php

namespace Webkul\Sale\Livewire;

use Livewire\Component;
use Webkul\Sale\Models\DocumentTemplate;
use Webkul\Sale\Services\TemplateRenderer;

/**
 * Quotation Preview Panel class
 *
 */
class QuotationPreviewPanel extends Component
{
    public $documentTemplateId = null;

    protected $listeners = ['template-changed' => 'updateTemplate'];

    /**
     * Mount
     *
     * @param mixed $documentTemplateId
     */
    public function mount($documentTemplateId = null)
    {
        $this->documentTemplateId = $documentTemplateId;
    }

    /**
     * Update Template
     *
     * @param mixed $data The data array
     */
    public function updateTemplate($data)
    {
        $this->documentTemplateId = $data['templateId'] ?? null;
    }

    /**
     * Get the rendered template property
     *
     * @return string
     */
    public function getRenderedTemplateProperty()
    {
        // Get template ID
        $templateId = $this->documentTemplateId ?? 3; // Default to Watchtower template

        try {
            $template = DocumentTemplate::find($templateId);

            if (!$template) {
                return '<div class="flex items-center justify-center h-64 text-gray-500">
                            <p>Template not found</p>
                        </div>';
            }

            $content = $template->getContent();

            if (!$content) {
                return '<div class="flex items-center justify-center h-64 text-gray-500">
                            <p>Template has no content</p>
                        </div>';
            }

            // Create a sample order object for preview
            $mockOrder = $this->createSampleOrder();

            // Use the template renderer to replace variables
            $renderer = new TemplateRenderer();
            $rendered = $renderer->render($template, $mockOrder);

            return $rendered;

        } catch (\Exception $e) {
            return '<div class="flex items-center justify-center h-64 text-red-500">
                        <p>Error rendering template: ' . htmlspecialchars($e->getMessage()) . '</p>
                    </div>';
        }
    }

    /**
     * Create Sample Order
     *
     * @return \Webkul\Sale\Models\Order
     */
    protected function createSampleOrder()
    {
        // Create a sample order object with example data for preview
        $order = new \Webkul\Sale\Models\Order();

        $order->name = 'Q/PREVIEW-001';
        $order->date_order = now();
        $order->validity_date = now()->addDays(30);
        $order->note = 'Sample project notes and specifications will appear here.';
        $order->amount_total = 5250.00;
        $order->amount_untaxed = 5000.00;
        $order->amount_tax = 250.00;
        $order->prepayment_percent = 30;
        $order->state = \Webkul\Sale\Enums\OrderState::DRAFT;

        // Create sample partner
        $partner = new \Webkul\Partner\Models\Partner();
        $partner->name = 'Sample Client Company';
        $partner->email = 'client@example.com';
        $partner->phone = '(555) 123-4567';
        $partner->street1 = '123 Main Street';
        $partner->city = 'Sample City';
        $partner->zip = '12345';
        $order->setRelation('partner', $partner);

        // Create sample order lines
        $lines = collect();

        $line1 = new \Webkul\Sale\Models\OrderLine();
        $line1->name = 'Custom cherry dining table with matching chairs';
        $line1->product_uom_qty = 1;
        $line1->price_unit = 5000.00;
        $line1->price_subtotal = 5000.00;
        $lines->push($line1);

        $order->setRelation('lines', $lines);

        return $order;
    }

    /**
     * Render
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('sales::livewire.quotation-preview-panel');
    }
}
