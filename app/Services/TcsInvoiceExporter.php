<?php

namespace App\Services;

use Webkul\Sale\Models\Order;
use Illuminate\Support\Facades\File;

/**
 * Tcs Invoice Exporter data exporter
 *
 */
class TcsInvoiceExporter
{
    /**
     * Export quotation/order to TCS HTML invoice format
     */
    public function export(Order $order): string
    {
        // Load the invoice template
        $templatePath = base_path('templates/invoices/invoice-TCS-template.html');

        if (!File::exists($templatePath)) {
            throw new \Exception('Invoice template not found at: ' . $templatePath);
        }

        $template = File::get($templatePath);

        // Get customer/partner data
        $customer = $order->partner;
        $lines = $order->products; // Order lines

        // Replace template placeholders with actual data
        $html = $this->populateTemplate($template, $order, $customer, $lines);

        return $html;
    }

    /**
     * Populate template with order data
     */
    protected function populateTemplate(string $template, Order $order, $customer, $lines): string
    {
        // Basic information
        $replacements = [
            '{{invoice_number}}' => $order->name ?? 'QUOTE-' . $order->id,
            '{{invoice_date}}' => $order->date_order->format('F d, Y'),
            '{{due_date}}' => $order->validity_date ? $order->validity_date->format('F d, Y') : 'Upon Receipt',

            // Customer information
            '{{customer_name}}' => $customer->name ?? '',
            '{{customer_street}}' => $customer->street ?? '',
            '{{customer_street2}}' => $customer->street2 ?? '',
            '{{customer_city}}' => $customer->city ?? '',
            '{{customer_state}}' => $customer->state->name ?? '',
            '{{customer_zip}}' => $customer->zip ?? '',

            // Project information
            '{{project_location}}' => $order->notes ?? '',

            // Status
            '{{status}}' => strtoupper($order->state),

            // Line items (will be handled separately)
            '{{line_items}}' => $this->generateLineItems($lines),

            // Totals
            '{{subtotal}}' => '$' . number_format($order->amount_untaxed, 2),
            '{{tax}}' => '$' . number_format($order->amount_tax, 2),
            '{{total}}' => '$' . number_format($order->amount_total, 2),

            // Payment info
            '{{amount_paid}}' => '$0.00', // TODO: Connect to payment records
            '{{balance_due}}' => '$' . number_format($order->amount_total, 2),
        ];

        // Replace all placeholders
        foreach ($replacements as $placeholder => $value) {
            $template = str_replace($placeholder, $value, $template);
        }

        return $template;
    }

    /**
     * Generate HTML for line items table
     */
    protected function generateLineItems($lines): string
    {
        $html = '';

        foreach ($lines as $line) {
            $html .= '<tr>';
            $html .= '<td class="description">';
            $html .= '<strong>' . htmlspecialchars($line->product?->name ?? 'Product') . '</strong><br>';
            $html .= htmlspecialchars($line->name ?? ''); // Description
            $html .= '</td>';
            $html .= '<td class="price">' . number_format($line->product_uom_qty, 2) . '</td>';
            $html .= '<td class="price">$' . number_format($line->price_unit, 2) . '</td>';
            $html .= '<td class="price">$' . number_format($line->price_subtotal, 2) . '</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * Save HTML to file
     */
    public function saveToFile(string $html, string $filename): string
    {
        $exportPath = storage_path('app/exports/invoices');

        if (!File::exists($exportPath)) {
            File::makeDirectory($exportPath, 0755, true);
        }

        $filepath = $exportPath . '/' . $filename;
        File::put($filepath, $html);

        return $filepath;
    }

    /**
     * Export and download
     */
    public function exportAndDownload(Order $order): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $html = $this->export($order);
        $filename = 'invoice-' . $order->name . '.html';

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, $filename, [
            'Content-Type' => 'text/html',
        ]);
    }
}
