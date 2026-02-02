<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Order {{ $record->change_order_number }} | The Carpenter's Son</title>
    <style>
        @media print {
            @page {
                size: letter;
                margin: 0.5in;
            }
            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .container {
                width: 100%;
                max-width: 100%;
                padding: 0;
                box-shadow: none;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.5;
            color: #333;
            background: #f5f5f5;
            font-size: 10pt;
        }

        .container {
            width: 8.5in;
            max-width: 100%;
            margin: 0 auto;
            padding: 0.5in;
            background: #fff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 3px solid #d4a574;
        }

        .company-info h2 {
            font-size: 18px;
            font-weight: 300;
            letter-spacing: 2px;
            color: #000;
            margin-bottom: 5px;
        }

        .company-info p {
            font-size: 9px;
            color: #666;
            line-height: 1.4;
        }

        .document-title {
            text-align: right;
        }

        .document-title h1 {
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 1px;
            color: #000;
            margin-bottom: 5px;
        }

        .document-title .subtitle {
            font-size: 11px;
            color: #666;
            margin-bottom: 10px;
        }

        .document-meta {
            font-size: 10px;
            color: #666;
            line-height: 1.6;
        }

        .document-meta strong {
            color: #333;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 8px;
        }

        .status-draft { background: #e0e0e0; color: #666; }
        .status-pending_approval { background: #fff3e0; color: #e65100; }
        .status-approved { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }
        .status-applied { background: #e3f2fd; color: #1565c0; }
        .status-cancelled { background: #f5f5f5; color: #9e9e9e; }

        .reason-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .reason-client_request { background: #e3f2fd; color: #1565c0; }
        .reason-field_condition { background: #fff3e0; color: #e65100; }
        .reason-design_error { background: #ffebee; color: #c62828; }
        .reason-material_substitution { background: #f5f5f5; color: #616161; }
        .reason-scope_addition { background: #e8f5e9; color: #2e7d32; }
        .reason-scope_removal { background: #ffebee; color: #c62828; }
        .reason-other { background: #f5f5f5; color: #666; }

        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .info-box {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 4px;
        }

        .info-box h3 {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 5px;
        }

        .info-box p {
            font-size: 11px;
            color: #333;
            line-height: 1.6;
            margin-bottom: 3px;
        }

        .info-box .label { color: #666; }
        .info-box .value { font-weight: 500; }

        .section-header {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #d4a574;
            padding-bottom: 8px;
        }

        .change-title {
            font-size: 14px;
            font-weight: 600;
            color: #000;
            margin-bottom: 10px;
        }

        .change-description {
            font-size: 11px;
            color: #333;
            line-height: 1.6;
            padding: 15px;
            background: #fafafa;
            border-left: 3px solid #d4a574;
            margin-bottom: 15px;
        }

        .changes-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }

        .changes-table th {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #666;
            text-align: left;
            padding: 10px 8px;
            border-bottom: 2px solid #d4a574;
            background: #fafafa;
        }

        .changes-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 10px;
            color: #333;
            vertical-align: top;
        }

        .changes-table tr:nth-child(even) { background: #fafafa; }

        .old-value {
            color: #c62828;
            text-decoration: line-through;
        }

        .new-value {
            color: #2e7d32;
            font-weight: 500;
        }

        .price-positive { color: #2e7d32; }
        .price-negative { color: #c62828; }

        .impact-section {
            margin: 25px 0;
            padding: 20px;
            background: #f8f8f8;
            border-radius: 4px;
        }

        .impact-section h3 {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #333;
            margin-bottom: 15px;
        }

        .impact-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .impact-item {
            text-align: center;
            padding: 15px;
            background: #fff;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }

        .impact-label {
            font-size: 9px;
            font-weight: 500;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
        }

        .impact-value {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .impact-value.positive { color: #2e7d32; }
        .impact-value.negative { color: #c62828; }

        .approval-section {
            margin: 20px 0;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 4px;
            border-left: 4px solid #2e7d32;
        }

        .approval-section h4 {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            color: #2e7d32;
            margin-bottom: 8px;
        }

        .rejection-section {
            margin: 20px 0;
            padding: 15px;
            background: #ffebee;
            border-radius: 4px;
            border-left: 4px solid #c62828;
        }

        .rejection-section h4 {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            color: #c62828;
            margin-bottom: 8px;
        }

        .signature-section {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .signature-box h4 {
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 30px;
        }

        .signature-line {
            border-top: 1px solid #333;
            padding-top: 8px;
            font-size: 9px;
            color: #666;
        }

        .signature-name { font-weight: 500; color: #333; }
        .signature-date { font-size: 9px; color: #666; margin-top: 5px; }

        .document-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
        }

        .footer-notice {
            font-size: 9px;
            color: #666;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .footer-contact {
            font-size: 9px;
            color: #999;
        }

        .no-changes {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h2>THE CARPENTER'S SON</h2>
                <p>
                    392 N Montgomery St, Building B<br>
                    Newburgh, NY 12550<br>
                    (845) 816-2388 | info@tcswoodwork.com<br>
                    www.tcswoodwork.com
                </p>
            </div>

            <div class="document-title">
                <h1>CHANGE ORDER</h1>
                <div class="subtitle">Project Modification Request</div>
                <div class="document-meta">
                    <p><strong>Change Order #:</strong> {{ $record->change_order_number }}</p>
                    <p><strong>Date:</strong> {{ $record->requested_at?->format('M j, Y') ?? now()->format('M j, Y') }}</p>
                    <p><strong>Project:</strong> {{ $record->project?->name ?? 'N/A' }}</p>
                </div>
                <span class="status-badge status-{{ $record->status }}">
                    {{ \Webkul\Project\Models\ChangeOrder::getStatuses()[$record->status] ?? ucfirst($record->status) }}
                </span>
            </div>
        </div>

        <!-- Project & Client Info -->
        <div class="info-section">
            <div class="info-box">
                <h3>Project Information</h3>
                <p><span class="label">Project Name:</span> <span class="value">{{ $record->project?->name ?? 'N/A' }}</span></p>
                <p><span class="label">Affected Stage:</span> <span class="value">{{ $record->affected_stage ? ucfirst($record->affected_stage) : 'Not specified' }}</span></p>
                <p><span class="label">Requested By:</span> <span class="value">{{ $record->requester?->name ?? 'N/A' }}</span></p>
            </div>

            <div class="info-box">
                <h3>Client Information</h3>
                @php
                    $partner = $record->project?->partner;
                @endphp
                <p><span class="label">Client:</span> <span class="value">{{ $partner?->name ?? 'N/A' }}</span></p>
                <p><span class="label">Address:</span> <span class="value">
                    @if($partner)
                        {{ implode(', ', array_filter([$partner->street, $partner->city, $partner->state?->name ?? $partner->state_id, $partner->zip])) }}
                    @else
                        N/A
                    @endif
                </span></p>
                <p><span class="label">Contact:</span> <span class="value">{{ $partner?->phone ?? $partner?->mobile ?? 'N/A' }}</span></p>
            </div>
        </div>

        <!-- Change Order Details -->
        <h2 class="section-header">Change Order Details</h2>

        <div class="change-title">{{ $record->title }}</div>

        <div style="margin-bottom: 15px;">
            <span class="reason-badge reason-{{ $record->reason }}">
                {{ \Webkul\Project\Models\ChangeOrder::getReasons()[$record->reason] ?? ucfirst($record->reason) }}
            </span>
        </div>

        <div class="change-description">
            {{ $record->description ?? 'No description provided.' }}
        </div>

        <!-- Changes Table -->
        <h2 class="section-header">Itemized Changes</h2>

        @if($record->lines->isEmpty())
            <div class="no-changes">No specific change lines recorded.</div>
        @else
            <table class="changes-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Item Type</th>
                        <th style="width: 10%;">Item ID</th>
                        <th style="width: 15%;">Field</th>
                        <th style="width: 20%;">Previous Value</th>
                        <th style="width: 20%;">New Value</th>
                        <th style="width: 20%; text-align: right;">Price Impact</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($record->lines as $line)
                        <tr>
                            <td>{{ $line->entity_type }}</td>
                            <td>{{ $line->entity_id }}</td>
                            <td>{{ $line->field_name }}</td>
                            <td class="old-value">{{ $line->old_value ?? '(empty)' }}</td>
                            <td class="new-value">{{ $line->new_value ?? '(empty)' }}</td>
                            <td style="text-align: right;" class="{{ $line->price_impact > 0 ? 'price-positive' : ($line->price_impact < 0 ? 'price-negative' : '') }}">
                                @if($line->price_impact > 0)
                                    +${{ number_format($line->price_impact, 2) }}
                                @elseif($line->price_impact < 0)
                                    -${{ number_format(abs($line->price_impact), 2) }}
                                @else
                                    $0.00
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- Impact Summary -->
        <div class="impact-section">
            <h3>Financial & Schedule Impact</h3>
            <div class="impact-grid">
                <div class="impact-item">
                    <div class="impact-label">Price Adjustment</div>
                    <div class="impact-value {{ $record->price_delta > 0 ? 'positive' : ($record->price_delta < 0 ? 'negative' : '') }}">
                        @if($record->price_delta > 0)
                            +${{ number_format($record->price_delta, 2) }}
                        @elseif($record->price_delta < 0)
                            -${{ number_format(abs($record->price_delta), 2) }}
                        @else
                            $0.00
                        @endif
                    </div>
                </div>
                <div class="impact-item">
                    <div class="impact-label">Labor Hours</div>
                    <div class="impact-value">
                        @if($record->labor_hours_delta > 0)
                            +{{ number_format($record->labor_hours_delta, 1) }} hrs
                        @elseif($record->labor_hours_delta < 0)
                            {{ number_format($record->labor_hours_delta, 1) }} hrs
                        @else
                            0 hrs
                        @endif
                    </div>
                </div>
                <div class="impact-item">
                    <div class="impact-label">Effective Date</div>
                    <div class="impact-value" style="font-size: 14px;">
                        {{ $record->applied_at?->format('M j, Y') ?? 'Pending' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Notes (if approved) -->
        @if($record->approved_at)
            <div class="approval-section">
                <h4>Approved</h4>
                <p><strong>Approved By:</strong> {{ $record->approver?->name ?? 'N/A' }}</p>
                <p><strong>Date:</strong> {{ $record->approved_at->format('M j, Y g:i A') }}</p>
                @if($record->approval_notes)
                    <p><strong>Notes:</strong> {{ $record->approval_notes }}</p>
                @endif
            </div>
        @endif

        <!-- Rejection Notes (if rejected) -->
        @if($record->rejected_at)
            <div class="rejection-section">
                <h4>Rejected</h4>
                <p><strong>Rejected By:</strong> {{ $record->rejecter?->name ?? 'N/A' }}</p>
                <p><strong>Date:</strong> {{ $record->rejected_at->format('M j, Y g:i A') }}</p>
                <p><strong>Reason:</strong> {{ $record->rejection_reason ?? 'No reason provided.' }}</p>
            </div>
        @endif

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <h4>Client Authorization</h4>
                <div class="signature-line">
                    <span class="signature-name">Signature</span>
                </div>
                <div class="signature-date">Date: _________________</div>
            </div>

            <div class="signature-box">
                <h4>TCS Representative</h4>
                <div class="signature-line">
                    <span class="signature-name">{{ $record->requester?->name ?? 'N/A' }}</span>
                </div>
                <div class="signature-date">Date: {{ $record->requested_at?->format('M j, Y') ?? now()->format('M j, Y') }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="document-footer">
            <div class="footer-notice">
                <strong>IMPORTANT:</strong> This change order becomes binding upon client signature.
                All changes will be applied to the project scope and may affect the original contract amount and timeline.
                Payment for additional work is due upon completion unless otherwise specified in the original contract.
            </div>
            <div class="footer-contact">
                Questions? Contact us at (845) 816-2388 or info@tcswoodwork.com
            </div>
        </div>
    </div>
</body>
</html>
