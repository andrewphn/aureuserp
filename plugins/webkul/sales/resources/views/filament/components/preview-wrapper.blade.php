<div class="w-full h-full overflow-auto bg-gray-100" id="preview-container">
    @if ($documentTemplateId)
        @php
            // Get the template and render it directly
            $template = \Webkul\Sale\Models\DocumentTemplate::find($documentTemplateId);

            if ($template) {
                // Get template content
                $content = $template->getContent();

                if ($content) {
                    // Get actual data from form
                    $partner = null;
                    if (!empty($formData['partner_id'])) {
                        $partner = \Webkul\Partner\Models\Partner::find($formData['partner_id']);
                    }

                    $project = null;
                    if (!empty($formData['project_id'])) {
                        $project = \Webkul\Project\Models\Project::find($formData['project_id']);
                    }

                    // Use actual form data or fallback to sample data
                    $variables = [
                        // Order Information
                        'PROPOSAL_NUMBER' => 'QUOT-' . date('Y') . '-NEW',
                        'ORDER_NUMBER' => 'QUOT-' . date('Y') . '-NEW',
                        'PROJECT_DATE' => !empty($formData['date_order']) ?
                            \Carbon\Carbon::parse($formData['date_order'])->format('F j, Y') :
                            now()->format('F j, Y'),

                        // Client Information
                        'CLIENT_NAME' => $partner?->name ?? 'Customer Name',
                        'CLIENT_COMPANY' => $partner?->name ?? 'Customer Name',
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
                        'PROJECT_NAME' => $project?->name ?? 'New Project',
                        'PROJECT_TYPE' => $project?->name ?? 'Custom Cabinetry',
                        'PROJECT_SUBTITLE' => $project?->description ?? '',
                        'PROJECT_NOTES' => is_string($formData['note'] ?? '') ? $formData['note'] : '',

                        // Project Location (use customer address if no project)
                        'PROJECT_LOCATION_STREET' => $partner?->street1 ?? '',
                        'PROJECT_LOCATION_CITY' => $partner?->city ?? '',
                        'PROJECT_LOCATION_STATE' => $partner?->state?->name ?? '',
                        'PROJECT_LOCATION_ZIP' => $partner?->zip ?? '',

                        // Financial Information (will be calculated from products)
                        'TOTAL_PRICE' => '0.00',
                        'SUBTOTAL' => '0.00',
                        'TAX_AMOUNT' => '0.00',
                        'DEPOSIT_AMOUNT' => '0.00',
                        'DEPOSIT_PERCENT' => '30',
                        'BALANCE_AMOUNT' => '0.00',
                        'BALANCE_PERCENT' => '70',

                        // Company Information (TCS)
                        'COMPANY_NAME' => 'The Carpenter\'s Son',
                        'COMPANY_ADDRESS' => '392 N Montgomery St, Building B',
                        'COMPANY_CITY' => 'Newburgh',
                        'COMPANY_STATE' => 'NY',
                        'COMPANY_ZIP' => '12550',
                        'COMPANY_PHONE' => '(845) 816-2388',
                        'COMPANY_EMAIL' => 'info@tcswoodwork.com',
                        'COMPANY_OWNER' => 'Bryan Patton',

                        // Timeline
                        'TIMELINE_DAYS' => '15',
                        'VALIDITY_DAYS' => !empty($formData['validity_date']) && !empty($formData['date_order']) ?
                            \Carbon\Carbon::parse($formData['validity_date'])->diffInDays(\Carbon\Carbon::parse($formData['date_order'])) :
                            '30',

                        // Invoice Status
                        'INVOICE_STATUS' => 'Draft',
                        'INVOICE_STATUS_COLOR' => '#999',

                        // Product Specifications (sample defaults)
                        'WOOD_SPECIES' => 'Cherry',
                        'DIMENSIONS' => '',
                        'STAIN_NAME' => 'Natural',
                        'FINISH_TYPE' => 'Polyurethane',
                        'CONSTRUCTION' => 'Mortise & Tenon',
                        'FEATURES' => '',
                        'TOP_THICKNESS' => '1.5"',
                        'LEG_DESIGN' => 'Tapered',
                        'FEET_TYPE' => 'Adjustable levelers',
                    ];

                    // Add line items from form products
                    $products = $formData['products'] ?? [];
                    for ($i = 0; $i < 10; $i++) {
                        if (isset($products[$i])) {
                            $product = $products[$i];
                            $itemNum = $i + 1;

                            $variables["ITEM_{$itemNum}_NAME"] = $product['product_name'] ?? '';
                            $variables["ITEM_{$itemNum}_DESC"] = $product['name'] ?? '';
                            $variables["ITEM_{$itemNum}_QTY"] = $product['product_uom_qty'] ?? '';
                            $variables["ITEM_{$itemNum}_PRICE"] = isset($product['price_subtotal']) ? number_format($product['price_subtotal'], 2) : '';
                            $variables["ITEM_{$itemNum}_UNIT_PRICE"] = isset($product['price_unit']) ? number_format($product['price_unit'], 2) : '';
                        } else {
                            $itemNum = $i + 1;
                            $variables["ITEM_{$itemNum}_NAME"] = '';
                            $variables["ITEM_{$itemNum}_DESC"] = '';
                            $variables["ITEM_{$itemNum}_QTY"] = '';
                            $variables["ITEM_{$itemNum}_PRICE"] = '';
                            $variables["ITEM_{$itemNum}_UNIT_PRICE"] = '';
                        }
                    }

                    // Replace variables in template content
                    $renderedHtml = $content;
                    foreach ($variables as $key => $value) {
                        $renderedHtml = str_replace("{{" . $key . "}}", $value, $renderedHtml);
                    }
                } else {
                    $renderedHtml = '<div class="flex items-center justify-center h-64 text-red-500"><p>Template has no content</p></div>';
                }
            } else {
                $renderedHtml = '<div class="flex items-center justify-center h-64 text-red-500"><p>Template not found</p></div>';
            }
        @endphp

        {{-- Print-ready document preview with paper dimensions --}}
        <style>
            .print-preview-page {
                width: 8.5in;
                min-height: 11in;
                margin: 1rem auto;
                background: white;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                padding: 0.5in;
            }

            @media print {
                /* Hide everything except the preview content */
                body * {
                    visibility: hidden;
                }

                #preview-container,
                #preview-container * {
                    visibility: visible;
                }

                #preview-container {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    background: white;
                }

                .print-preview-page {
                    margin: 0;
                    padding: 0.5in;
                    box-shadow: none;
                    width: 100%;
                }
            }
        </style>

        <div class="print-preview-page">
            {!! $renderedHtml !!}
        </div>
    @else
        <div class="text-center py-12 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <p>Select a proposal template to preview</p>
        </div>
    @endif
</div>
