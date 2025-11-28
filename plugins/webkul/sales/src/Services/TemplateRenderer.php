<?php

namespace Webkul\Sale\Services;

use Webkul\Sale\Models\DocumentTemplate;
use Webkul\Sale\Models\Order;
use Carbon\Carbon;

/**
 * Template Renderer class
 *
 */
class TemplateRenderer
{
    /**
     * Render a template with order data
     *
     * @param DocumentTemplate $template
     * @param Order $order
     * @return string
     */
    public function render(DocumentTemplate $template, Order $order): string
    {
        $content = $template->getContent();

        if (! $content) {
            return '';
        }

        // Process line items loop first
        $content = $this->processLineItemsLoop($content, $order);

        $variables = $this->extractVariables($order);

        return $this->replaceVariables($content, $variables);
    }

    /**
     * Extract all variables from an order
     *
     * @param Order $order
     * @return array
     */
    protected function extractVariables(Order $order): array
    {
        $partner = $order->partner;
        $project = $order->project;
        $lines = $order->lines;

        // Calculate deposit and balance
        $depositPercent = $order->prepayment_percent ?? 30;
        $depositAmount = ($order->amount_total * $depositPercent) / 100;
        $balanceAmount = $order->amount_total - $depositAmount;

        $variables = [
            // Order Information
            'PROPOSAL_NUMBER' => $order->name,
            'ORDER_NUMBER' => $order->name,
            'PROJECT_DATE' => $order->date_order ? Carbon::parse($order->date_order)->format('F j, Y') : '',

            // Client Information
            'CLIENT_NAME' => $partner?->name ?? '',
            'CLIENT_COMPANY' => $partner?->name ?? '',
            'CLIENT_DEPARTMENT' => $partner?->job_title ?? '',
            'CLIENT_ACCOUNT' => $partner?->reference ?? '',
            'CLIENT_EMAIL' => $partner?->email ?? '',
            'CLIENT_PHONE' => $partner?->phone ?? '',
            'CLIENT_STREET' => $partner?->street1 ?? '',
            'CLIENT_CITY' => $partner?->city ?? '',
            'CLIENT_STATE' => $partner?->state?->name ?? '',
            'CLIENT_ZIP' => $partner?->zip ?? '',

            // Project Information
            'PROJECT_NUMBER' => $project?->project_number ?? '',
            'PROJECT_NAME' => $project?->name ?? '',
            'PROJECT_TYPE' => $project?->name ?? 'Custom Furniture',
            'PROJECT_SUBTITLE' => $project?->description ?? '',
            'PROJECT_NOTES' => $order->note ?? '',

            // Financial Information
            'TOTAL_PRICE' => number_format($order->amount_total, 2),
            'SUBTOTAL' => number_format($order->amount_untaxed, 2),
            'TAX_AMOUNT' => number_format($order->amount_tax, 2),
            'DEPOSIT_AMOUNT' => number_format($depositAmount, 2),
            'DEPOSIT_PERCENT' => $depositPercent,
            'BALANCE_AMOUNT' => number_format($balanceAmount, 2),
            'BALANCE_PERCENT' => 100 - $depositPercent,

            // Company Information
            'COMPANY_NAME' => 'The Carpenter\'s Son',
            'COMPANY_ADDRESS' => '392 N Montgomery St, Building B',
            'COMPANY_CITY' => 'Newburgh',
            'COMPANY_STATE' => 'NY',
            'COMPANY_ZIP' => '12550',
            'COMPANY_PHONE' => '(845) 816-2388',
            'COMPANY_EMAIL' => 'info@tcswoodwork.com',
            'COMPANY_OWNER' => 'Bryan Patton',

            // Timeline (default values - can be customized per project)
            'TIMELINE_DAYS' => '15',
            'VALIDITY_DAYS' => $order->validity_date ?
                Carbon::parse($order->validity_date)->diffInDays(Carbon::parse($order->date_order)) :
                '30',
        ];

        // Add project location (for job site address separate from billing)
        $projectLocation = $this->getProjectLocation($project, $partner);
        $variables = array_merge($variables, $projectLocation);

        // Add invoice status label
        $variables['INVOICE_STATUS'] = $order->state?->getLabel() ?? '';
        $variables['INVOICE_STATUS_COLOR'] = match($order->state?->value) {
            'draft' => '#999',
            'sent' => '#4a90e2',
            'sale' => '#2e7d2e',
            'cancel' => '#d32f2f',
            default => '#d4a574',
        };

        // Add line item variables (up to 10 items for complex projects)
        for ($i = 0; $i < 10; $i++) {
            $line = $lines->get($i);
            $itemNum = $i + 1;

            $variables["ITEM_{$itemNum}_NAME"] = $line?->product?->name ?? '';
            $variables["ITEM_{$itemNum}_DESC"] = $line?->name ?? '';
            $variables["ITEM_{$itemNum}_QTY"] = $line?->product_uom_qty ?? '';
            $variables["ITEM_{$itemNum}_PRICE"] = $line ? number_format($line->price_subtotal, 2) : '';
            $variables["ITEM_{$itemNum}_UNIT_PRICE"] = $line ? number_format($line->price_unit, 2) : '';
        }

        // Add product-specific variables from first line item
        $firstLine = $lines->first();
        if ($firstLine && $firstLine->product) {
            $product = $firstLine->product;

            // Try to extract specifications from product description or custom fields
            $variables['WOOD_SPECIES'] = $this->extractSpec($product, 'wood_species', 'Cherry');
            $variables['DIMENSIONS'] = $this->extractSpec($product, 'dimensions', '');
            $variables['STAIN_NAME'] = $this->extractSpec($product, 'stain', '');
            $variables['FINISH_TYPE'] = $this->extractSpec($product, 'finish', 'Polyurethane');
            $variables['CONSTRUCTION'] = $this->extractSpec($product, 'construction', 'Mortise & Tenon');
            $variables['FEATURES'] = $this->extractSpec($product, 'features', '');
            $variables['TOP_THICKNESS'] = $this->extractSpec($product, 'top_thickness', '');
            $variables['LEG_DESIGN'] = $this->extractSpec($product, 'leg_design', '');
            $variables['FEET_TYPE'] = $this->extractSpec($product, 'feet_type', '');
        }

        // Add invoice-specific aliases for backward compatibility with invoice templates
        $variables['INVOICE_NUMBER'] = $variables['PROPOSAL_NUMBER'];
        $variables['ORDER_DATE'] = $variables['PROJECT_DATE'];
        $variables['INVOICE_DATE'] = $variables['PROJECT_DATE'];
        $variables['DUE_DATE'] = Carbon::parse($order->date_order)->addDays(30)->format('F j, Y');
        $variables['CLIENT_ADDRESS'] = $variables['CLIENT_STREET'];
        $variables['CLIENT_CITY_STATE_ZIP'] = trim($variables['CLIENT_CITY'] . ', ' . $variables['CLIENT_STATE'] . ' ' . $variables['CLIENT_ZIP']);
        $variables['TAX_RATE'] = number_format(($order->amount_tax / ($order->amount_untaxed ?: 1)) * 100, 2);
        $variables['TOTAL_AMOUNT'] = $variables['TOTAL_PRICE'];
        $variables['AMOUNT_PAID'] = number_format($order->amount_paid ?? 0, 2);
        $variables['BALANCE_DUE'] = $variables['BALANCE_AMOUNT'];
        $variables['PAYMENT_TERMS'] = 'Net 30 days';
        $variables['NOTES'] = $variables['PROJECT_NOTES'];
        $variables['PO_NUMBER'] = $order->client_order_ref ?? '';

        return $variables;
    }

    /**
     * Get project location address variables
     *
     * @param mixed $project
     * @param mixed $partner
     * @return array
     */
    protected function getProjectLocation($project, $partner): array
    {
        // If project uses customer address or no project exists, use partner address
        if (!$project || ($project->use_customer_address ?? false)) {
            return [
                'PROJECT_LOCATION_STREET' => $partner?->street1 ?? '',
                'PROJECT_LOCATION_CITY' => $partner?->city ?? '',
                'PROJECT_LOCATION_STATE' => $partner?->state?->name ?? '',
                'PROJECT_LOCATION_ZIP' => $partner?->zip ?? '',
            ];
        }

        // Try to get project address from addresses relationship
        if ($project && method_exists($project, 'addresses')) {
            $address = $project->addresses()->first();
            if ($address) {
                return [
                    'PROJECT_LOCATION_STREET' => $address->street ?? $address->street1 ?? '',
                    'PROJECT_LOCATION_CITY' => $address->city ?? '',
                    'PROJECT_LOCATION_STATE' => $address->state?->name ?? '',
                    'PROJECT_LOCATION_ZIP' => $address->zip ?? '',
                ];
            }
        }

        // Default to partner address if no project address found
        return [
            'PROJECT_LOCATION_STREET' => $partner?->street1 ?? '',
            'PROJECT_LOCATION_CITY' => $partner?->city ?? '',
            'PROJECT_LOCATION_STATE' => $partner?->state?->name ?? '',
            'PROJECT_LOCATION_ZIP' => $partner?->zip ?? '',
        ];
    }

    /**
     * Extract specification from product custom fields or description
     *
     * @param mixed $product
     * @param string $field
     * @param string $default
     * @return string
     */
    protected function extractSpec($product, string $field, string $default = ''): string
    {
        // Try custom fields first
        if (method_exists($product, 'getCustomFieldValue')) {
            $value = $product->getCustomFieldValue($field);
            if ($value) {
                return $value;
            }
        }

        // Try product attributes
        if (isset($product->$field)) {
            return $product->$field;
        }

        return $default;
    }

    /**
     * Replace all {{VARIABLE}} placeholders with values
     *
     * @param string $content
     * @param array $variables
     * @return string
     */
    protected function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }

        return $content;
    }

    /**
     * Process the ITEM_LINES loop in the template
     *
     * @param string $content
     * @param Order $order
     * @return string
     */
    protected function processLineItemsLoop(string $content, Order $order): string
    {
        // Find the loop section
        $pattern = '/<!-- LOOP START: \{\{ITEM_LINES\}\} -->(.*?)<!-- LOOP END -->/s';

        if (!preg_match($pattern, $content, $matches)) {
            return $content; // No loop found, return content unchanged
        }

        $loopTemplate = $matches[1];
        $generatedRows = '';

        // Get order lines
        $lines = $order->lines;

        foreach ($lines as $index => $line) {
            $rowHtml = $loopTemplate;

            // Replace item variables in this row
            $product = $line->product;
            $rowHtml = str_replace('{{ITEM_NAME}}', $product?->name ?? 'Item', $rowHtml);

            // For description, use line name (which includes attribute selections)
            // Fall back to product name only if line name is empty
            // Avoid showing generic product descriptions like "Custom cabinet - configure..."
            $description = $line->name ?: ($product?->name ?? '');
            $rowHtml = str_replace('{{ITEM_DESCRIPTION}}', $description, $rowHtml);

            // Format values
            $qty = number_format($line->product_uom_qty ?? 0, 0);
            $rate = number_format($line->price_unit ?? 0, 2);
            $amount = number_format($line->price_total ?? 0, 2);

            $rowHtml = str_replace('{{ITEM_QTY}}', $qty, $rowHtml);
            $rowHtml = str_replace('{{ITEM_RATE}}', $rate, $rowHtml);
            $rowHtml = str_replace('{{ITEM_AMOUNT}}', $amount, $rowHtml);

            $generatedRows .= $rowHtml;
        }

        // Replace the entire loop section with generated rows
        return preg_replace($pattern, $generatedRows, $content);
    }

    /**
     * Get all available variables for a template type
     *
     * @param string $templateType
     * @return array
     */
    public static function getAvailableVariables(string $templateType): array
    {
        $common = [
            'PROPOSAL_NUMBER' => 'Proposal/Order number (e.g., Q/1, SO/1)',
            'ORDER_NUMBER' => 'Same as PROPOSAL_NUMBER',
            'PROJECT_DATE' => 'Order date (formatted)',
            'CLIENT_NAME' => 'Client company name',
            'CLIENT_COMPANY' => 'Same as CLIENT_NAME',
            'CLIENT_DEPARTMENT' => 'Client job title',
            'CLIENT_ACCOUNT' => 'Client reference/account number',
            'CLIENT_EMAIL' => 'Client email',
            'CLIENT_PHONE' => 'Client phone',
            'CLIENT_STREET' => 'Client street address',
            'CLIENT_CITY' => 'Client city',
            'CLIENT_STATE' => 'Client state',
            'CLIENT_ZIP' => 'Client ZIP code',
            'PROJECT_NUMBER' => 'Project number',
            'PROJECT_NAME' => 'Project name',
            'PROJECT_TYPE' => 'Project type/name',
            'PROJECT_SUBTITLE' => 'Project description',
            'PROJECT_NOTES' => 'Order notes/terms',
            'PROJECT_LOCATION_STREET' => 'Project site street address',
            'PROJECT_LOCATION_CITY' => 'Project site city',
            'PROJECT_LOCATION_STATE' => 'Project site state',
            'PROJECT_LOCATION_ZIP' => 'Project site ZIP code',
            'INVOICE_STATUS' => 'Invoice status label (Draft, Sent, Sale, etc.)',
            'INVOICE_STATUS_COLOR' => 'Hex color code for invoice status',
            'TOTAL_PRICE' => 'Total price (formatted)',
            'SUBTOTAL' => 'Subtotal before tax',
            'TAX_AMOUNT' => 'Tax amount',
            'DEPOSIT_AMOUNT' => 'Deposit amount (formatted)',
            'DEPOSIT_PERCENT' => 'Deposit percentage',
            'BALANCE_AMOUNT' => 'Balance amount (formatted)',
            'BALANCE_PERCENT' => 'Balance percentage',
            'COMPANY_NAME' => 'TCS company name',
            'COMPANY_ADDRESS' => 'TCS address',
            'COMPANY_CITY' => 'TCS city',
            'COMPANY_STATE' => 'TCS state',
            'COMPANY_ZIP' => 'TCS ZIP',
            'COMPANY_PHONE' => 'TCS phone',
            'COMPANY_EMAIL' => 'TCS email',
            'COMPANY_OWNER' => 'Bryan Patton',
            'TIMELINE_DAYS' => 'Project timeline in days',
            'VALIDITY_DAYS' => 'Quote validity in days',
        ];

        $items = [];
        for ($i = 1; $i <= 10; $i++) {
            $items["ITEM_{$i}_NAME"] = "Item {$i} product name";
            $items["ITEM_{$i}_DESC"] = "Item {$i} description";
            $items["ITEM_{$i}_QTY"] = "Item {$i} quantity";
            $items["ITEM_{$i}_PRICE"] = "Item {$i} total price";
            $items["ITEM_{$i}_UNIT_PRICE"] = "Item {$i} unit price";
        }

        $specs = [
            'WOOD_SPECIES' => 'Wood species (Cherry, Walnut, Oak, etc.)',
            'DIMENSIONS' => 'Product dimensions',
            'STAIN_NAME' => 'Stain color and code',
            'FINISH_TYPE' => 'Finish type (Polyurethane, Lacquer, etc.)',
            'CONSTRUCTION' => 'Construction method',
            'FEATURES' => 'Product features',
            'TOP_THICKNESS' => 'Top thickness',
            'LEG_DESIGN' => 'Leg design type',
            'FEET_TYPE' => 'Feet/base type',
        ];

        return array_merge($common, $items, $specs);
    }
}
