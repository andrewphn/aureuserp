<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $record->state == \Webkul\Sale\Enums\OrderState::SALE ? 'Order' : 'Quotation' }} {{ $record->name }} | The Carpenter's Son</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap');

        @media print {
            @page {
                size: letter;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 9pt;
            }

            .container {
                width: 8.5in;
                height: 11in;
                padding: 0.3in 0.4in;
                margin: 0;
                overflow: hidden;
                page-break-after: avoid;
            }

            * {
                page-break-inside: avoid !important;
                page-break-before: avoid !important;
                page-break-after: avoid !important;
            }

            h2 { margin: 6px 0 4px 0 !important; font-size: 11px !important; }
            h3 { margin: 4px 0 3px 0 !important; font-size: 10px !important; }
            .header { padding-bottom: 6px !important; margin-bottom: 6px !important; }
            .info-section { padding: 6px !important; margin: 6px 0 !important; }
            .footer-section { margin-top: 5px !important; padding-top: 4px !important; }
            .quote-table { margin: 5px 0 !important; }
            .quote-table td { padding: 3px 0 !important; }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.4;
            color: #333;
            background: #fff;
            font-size: 10pt;
        }

        .container {
            width: 8.5in;
            max-width: 100%;
            margin: 0 auto;
            padding: 0.3in 0.4in;
            background: #fff;
        }

        /* Header */
        .header {
            text-align: center;
            padding-bottom: 10px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .company-name {
            font-size: 20px;
            font-weight: 300;
            letter-spacing: 3px;
            color: #000;
            margin-bottom: 2px;
        }

        .tagline {
            font-size: 9px;
            font-weight: 400;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #666;
        }

        /* Section Headers */
        h2 {
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #000;
            margin: 8px 0 5px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #e0e0e0;
        }

        h3 {
            font-size: 11px;
            font-weight: 500;
            color: #333;
            margin: 5px 0 3px 0;
        }

        /* Info Section */
        .info-section {
            background: #f8f8f8;
            border-radius: 3px;
            padding: 8px;
            margin: 8px 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .info-item label {
            display: block;
            font-size: 8px;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 1px;
        }

        .info-item p {
            font-size: 10px;
            color: #333;
            line-height: 1.2;
        }

        /* Quote Table */
        .quote-table {
            width: 100%;
            margin: 5px 0;
            border-collapse: collapse;
        }

        .quote-table th {
            font-size: 9px;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #666;
            text-align: left;
            padding: 4px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .quote-table td {
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
            font-size: 10px;
        }

        .quote-table .description {
            font-size: 10px;
            line-height: 1.3;
            color: #444;
        }

        .quote-table .price {
            text-align: right;
            font-size: 10px;
            color: #333;
        }

        .quote-table .total td {
            border-top: 2px solid #333;
            border-bottom: none;
            padding-top: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #000;
        }

        /* Footer */
        .footer-section {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }

        .terms {
            font-size: 8px;
            color: #666;
            line-height: 1.2;
            margin: 3px 0;
        }

        .terms strong {
            color: #333;
            font-weight: 500;
        }

        .terms p {
            margin: 2px 0;
        }

        .contact-section {
            text-align: center;
            margin: 10px 0;
        }

        .contact-section h3 {
            font-size: 10px;
            font-weight: 400;
            margin-bottom: 3px;
        }

        .contact-info {
            font-size: 8px;
            color: #666;
            line-height: 1.2;
        }

        .status-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #d4a574;
            color: white;
            padding: 8px 15px;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header" style="position: relative;">
            <p style="font-size: 10px; color: #666; position: absolute; top: 0; right: 0;">
                {{ $record->state == \Webkul\Sale\Enums\OrderState::SALE ? 'Order' : 'Quotation' }} {{ $record->name }}
            </p>
            <div style="display: flex; align-items: center; justify-content: flex-start; gap: 15px;">
                @if($record->company->partner?->avatar)
                    @php
                        // Try both with and without 'public/' prefix
                        $logoPath = storage_path('app/public/' . $record->company->partner->avatar);
                        if (!file_exists($logoPath)) {
                            $logoPath = storage_path('app/' . $record->company->partner->avatar);
                        }
                        $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
                        $logoMimeType = file_exists($logoPath) ? mime_content_type($logoPath) : 'image/png';
                    @endphp
                    @if($logoData)
                    <img src="data:{{ $logoMimeType }};base64,{{ $logoData }}" alt="Company Logo" style="height:40px; width:auto;" />
                    @endif
                @endif
                <div style="text-align: left;">
                    <h1 class="company-name" style="font-size: 18px; margin-bottom: 2px;">{{ strtoupper($record->company->name) }}</h1>
                    <p class="tagline" style="margin: 0; letter-spacing: 2.2px;">Custom Cabinetry, Fine Millwork & Bespoke Furniture</p>
                </div>
            </div>
        </div>

        <!-- Quotation Info -->
        <div class="info-section">
            <div class="info-grid">
                <div class="info-item">
                    <label>Bill To</label>
                    <p>
                        {{ $record->partner->name }}<br>
                        @if($record->partner->street){{ $record->partner->street }}<br>@endif
                        @if($record->partner->city){{ $record->partner->city }}, @endif
                        @if($record->partner->state){{ $record->partner->state->code }} @endif
                        @if($record->partner->zip){{ $record->partner->zip }}@endif
                    </p>
                </div>
                <div class="info-item">
                    <label>Project Location</label>
                    <p>
                        @if($record->partner_shipping_id && $record->partnerShipping)
                            {{ $record->partnerShipping->street ?? '' }}<br>
                            {{ $record->partnerShipping->city ?? '' }}, {{ $record->partnerShipping->state->code ?? '' }}
                        @else
                            Same as billing
                        @endif
                    </p>
                </div>
                <div class="info-item">
                    <label>{{ $record->state == \Webkul\Sale\Enums\OrderState::SALE ? 'Order' : 'Quotation' }} Date</label>
                    <p>{{ $record->date_order ? (is_string($record->date_order) ? \Carbon\Carbon::parse($record->date_order)->format('F d, Y') : $record->date_order->format('F d, Y')) : '' }}<br>
                    @if($record->validity_date)
                        Exp: {{ is_string($record->validity_date) ? \Carbon\Carbon::parse($record->validity_date)->format('M d, Y') : $record->validity_date->format('M d, Y') }}
                    @endif
                    </p>
                </div>
                <div class="info-item">
                    <label>Status</label>
                    <p><strong style="color: #d4a574;">{{ strtoupper(str_replace('_', ' ', $record->state->value)) }}</strong></p>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <h2>{{ $record->state == \Webkul\Sale\Enums\OrderState::SALE ? 'Order' : 'Quotation' }} Details</h2>
        <table class="quote-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: center; width: 80px;">Qty</th>
                    <th style="text-align: right; width: 100px;">Unit Price</th>
                    <th style="text-align: right; width: 100px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($record->lines as $line)
                <tr>
                    <td class="description">
                        <strong>{{ $line->product->name ?? 'Product' }}</strong>
                        @if($line->name)
                            <br>{{ $line->name }}
                        @endif
                    </td>
                    <td class="price" style="text-align: center;">{{ number_format($line->product_uom_qty, 2) }}</td>
                    <td class="price">{{ $record->currency->symbol }}{{ number_format($line->price_unit, 2) }}</td>
                    <td class="price">{{ $record->currency->symbol }}{{ number_format($line->price_subtotal, 2) }}</td>
                </tr>
                @endforeach
                <tr class="total">
                    <td colspan="3"><strong>Subtotal</strong></td>
                    <td class="price"><strong>{{ $record->currency->symbol }}{{ number_format($record->amount_untaxed, 2) }}</strong></td>
                </tr>
                @if($record->amount_tax > 0)
                <tr>
                    <td colspan="3">Tax</td>
                    <td class="price">{{ $record->currency->symbol }}{{ number_format($record->amount_tax, 2) }}</td>
                </tr>
                @endif
                <tr class="total">
                    <td colspan="3"><strong>Total Amount</strong></td>
                    <td class="price"><strong>{{ $record->currency->symbol }}{{ number_format($record->amount_total, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        @if($record->note && !empty(strip_tags($record->note)))
        <!-- Notes -->
        <div class="section">
            <h2>Notes</h2>
            <div style="background: #f8f8f8; border-radius: 3px; padding: 8px; margin: 8px 0;">
                <p style="font-size: 9px; color: #666; line-height: 1.4;">
                    {!! nl2br(e($record->note)) !!}
                </p>
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer-section">
            <div class="terms">
                <p><strong>Payment Terms:</strong> {{ $record->paymentTerm->name ?? 'As agreed' }}</p>
                @if($record->company->email || $record->company->phone)
                <p><strong>Contact:</strong>
                    @if($record->company->phone){{ $record->company->phone }}@endif
                    @if($record->company->email) • {{ $record->company->email }}@endif
                </p>
                @endif
                @if($record->company->street)
                <p><strong>Address:</strong> {{ $record->company->street }}, {{ $record->company->city ?? '' }}, {{ $record->company->state->code ?? '' }} {{ $record->company->zip ?? '' }}</p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
