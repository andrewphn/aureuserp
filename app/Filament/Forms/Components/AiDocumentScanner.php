<?php

namespace App\Filament\Forms\Components;

use Filament\Schemas\Components\Component;

/**
 * AI-Powered Document Scanner Component
 *
 * Provides an interface to scan invoices, packing slips, and product labels
 * using Gemini AI vision capabilities. Extracts data and verifies against
 * existing POs and products in the database.
 */
class AiDocumentScanner extends Component
{
    protected string $view = 'filament.forms.components.ai-document-scanner';

    /**
     * Document type being scanned
     */
    protected string $documentType = 'invoice';

    /**
     * Whether to show the camera capture option
     */
    protected bool $showCamera = true;

    /**
     * Whether to auto-apply results to form
     */
    protected bool $autoApply = false;

    /**
     * Field mappings for receiving forms
     *
     * Maps AI scan result keys to form field names.
     * Format: 'ai_result_key' => 'form_field_name'
     */
    protected array $receivingFieldMappings = [
        // Vendor mapping
        'vendor_id' => 'partner_id',
        'vendor_match.id' => 'partner_id',

        // Document/reference fields
        'po_reference' => 'origin',
        'document.po_reference' => 'origin',
        'po_id' => 'purchase_order_id',
        'po_match.id' => 'purchase_order_id',

        // New AI tracking fields
        'document.slip_number' => 'packing_slip_number',
        'slip_number' => 'packing_slip_number',
        'document.tracking_number' => 'tracking_number',
        'tracking_number' => 'tracking_number',
        'package_info.carrier' => 'carrier_name',

        // Scheduling
        'document.ship_date' => 'scheduled_at',
        'ship_date' => 'scheduled_at',

        // AI confidence tracking
        'confidence' => 'ai_scan_confidence',
    ];

    /**
     * Field mappings for move/line items in receiving forms
     *
     * Maps AI line item result keys to move form field names.
     */
    protected array $receivingLineMappings = [
        'product_match.product_id' => 'product_id',
        'quantity_shipped' => 'product_uom_qty',
        'quantity' => 'product_uom_qty',
        'sku' => 'ai_source_sku',
        'product_match.confidence' => 'ai_confidence',
        'product_match.match_method' => 'ai_matched_by',
        'requires_review' => 'requires_review',
    ];

    /**
     * Field mappings for invoice forms
     */
    protected array $invoiceFieldMappings = [
        'vendor_id' => 'partner_id',
        'vendor_match.id' => 'partner_id',
        'document.invoice_number' => 'reference',
        'invoice_number' => 'reference',
        'document.invoice_date' => 'invoice_date',
        'invoice_date' => 'invoice_date',
        'document.due_date' => 'invoice_date_due',
        'due_date' => 'invoice_date_due',
        'document.po_reference' => 'invoice_origin',
        'po_reference' => 'invoice_origin',
        'totals.total' => 'amount_total',
        'total' => 'amount_total',
    ];

    /**
     * Create a new component instance
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Set the document type (invoice, packing_slip, product_label, quote)
     */
    public function type(string $type): static
    {
        $this->documentType = $type;

        return $this;
    }

    /**
     * Set for receiving/packing slip scanning
     */
    public function forReceiving(): static
    {
        $this->documentType = 'packing_slip';

        return $this;
    }

    /**
     * Set for invoice scanning
     */
    public function forInvoice(): static
    {
        $this->documentType = 'invoice';

        return $this;
    }

    /**
     * Set for product label scanning
     */
    public function forProduct(): static
    {
        $this->documentType = 'product_label';

        return $this;
    }

    /**
     * Enable or disable camera capture
     */
    public function camera(bool $enabled = true): static
    {
        $this->showCamera = $enabled;

        return $this;
    }

    /**
     * Enable auto-apply of results to form
     */
    public function autoApply(bool $enabled = true): static
    {
        $this->autoApply = $enabled;

        return $this;
    }

    /**
     * Configure custom field mappings
     */
    public function fieldMappings(array $mappings): static
    {
        if ($this->documentType === 'packing_slip') {
            $this->receivingFieldMappings = array_merge($this->receivingFieldMappings, $mappings);
        } else {
            $this->invoiceFieldMappings = array_merge($this->invoiceFieldMappings, $mappings);
        }

        return $this;
    }

    /**
     * Get the document type
     */
    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    /**
     * Get whether camera is enabled
     */
    public function getShowCamera(): bool
    {
        return $this->showCamera;
    }

    /**
     * Get whether auto-apply is enabled
     */
    public function getAutoApply(): bool
    {
        return $this->autoApply;
    }

    /**
     * Get the field mappings for the current document type
     */
    public function getFieldMappings(): array
    {
        return $this->documentType === 'packing_slip'
            ? $this->receivingFieldMappings
            : $this->invoiceFieldMappings;
    }

    /**
     * Get the line item field mappings for receiving forms
     */
    public function getLineMappings(): array
    {
        return $this->receivingLineMappings;
    }

    /**
     * Get the confidence threshold for auto-apply
     */
    public function getConfidenceThreshold(): float
    {
        return (float) config('ai.scan_confidence_threshold', 0.70);
    }

    /**
     * Get the auto-apply confidence threshold
     */
    public function getAutoApplyThreshold(): float
    {
        return (float) config('ai.scan_auto_apply_threshold', 0.95);
    }

    /**
     * Get the API endpoint URL based on document type
     */
    public function getApiEndpoint(): string
    {
        return match ($this->documentType) {
            'packing_slip' => url('/admin/document-scanner/scan-receiving'),
            'product_label' => url('/admin/document-scanner/scan-product'),
            default => url('/admin/document-scanner/scan'),
        };
    }

    /**
     * Get document type label for UI
     */
    public function getDocumentTypeLabel(): string
    {
        return match ($this->documentType) {
            'invoice' => 'Invoice / Bill',
            'packing_slip' => 'Packing Slip',
            'product_label' => 'Product Label',
            'quote' => 'Quote / Estimate',
            default => 'Document',
        };
    }

    /**
     * Get document type description for UI
     */
    public function getDocumentTypeDescription(): string
    {
        return match ($this->documentType) {
            'invoice' => 'Upload a vendor invoice to extract line items, totals, and verify against PO',
            'packing_slip' => 'Scan packing slip to auto-populate receiving quantities',
            'product_label' => 'Scan product label or barcode for quick product lookup',
            'quote' => 'Upload vendor quote to compare pricing',
            default => 'Upload a document to extract data using AI',
        };
    }

    /**
     * Get accepted file types
     */
    public function getAcceptedFileTypes(): string
    {
        return 'image/jpeg,image/png,image/webp,application/pdf';
    }

    /**
     * Get max file size in MB
     */
    public function getMaxFileSize(): int
    {
        return 10;
    }
}
