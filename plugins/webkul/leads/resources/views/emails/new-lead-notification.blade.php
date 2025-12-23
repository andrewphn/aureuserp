<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Lead Notification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
            color: white;
            padding: 24px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header .badge {
            display: inline-block;
            background: #48bb78;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }
        .content {
            background: #f7fafc;
            padding: 24px;
            border: 1px solid #e2e8f0;
            border-top: none;
        }
        .section {
            background: white;
            padding: 16px;
            margin-bottom: 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .section-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #edf2f7;
        }
        .field {
            margin-bottom: 8px;
        }
        .field-label {
            font-weight: 500;
            color: #718096;
            font-size: 13px;
        }
        .field-value {
            color: #2d3748;
        }
        .message-box {
            background: #edf2f7;
            padding: 12px;
            border-radius: 6px;
            font-style: italic;
            color: #4a5568;
        }
        .cta-button {
            display: inline-block;
            background: #3182ce;
            color: white !important;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 16px;
        }
        .footer {
            text-align: center;
            padding: 16px;
            color: #718096;
            font-size: 12px;
        }
        .highlight {
            background: #fefcbf;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>New Lead Received</h1>
        <span class="badge">{{ $lead->source?->getLabel() ?? 'Website' }}</span>
    </div>

    <div class="content">
        {{-- Contact Information --}}
        <div class="section">
            <div class="section-title">Contact Information</div>

            <div class="field">
                <span class="field-label">Name:</span>
                <span class="field-value"><strong>{{ $lead->full_name }}</strong></span>
            </div>

            <div class="field">
                <span class="field-label">Email:</span>
                <span class="field-value">
                    <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>
                </span>
            </div>

            <div class="field">
                <span class="field-label">Phone:</span>
                <span class="field-value">
                    <a href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a>
                </span>
            </div>

            @if($lead->company_name)
            <div class="field">
                <span class="field-label">Company:</span>
                <span class="field-value">{{ $lead->company_name }}</span>
            </div>
            @endif

            @if($lead->preferred_contact_method)
            <div class="field">
                <span class="field-label">Preferred Contact:</span>
                <span class="field-value highlight">{{ ucfirst($lead->preferred_contact_method) }}</span>
            </div>
            @endif
        </div>

        {{-- Project Information --}}
        @if($lead->project_type || $lead->budget_range || $lead->project_description)
        <div class="section">
            <div class="section-title">Project Information</div>

            @if($lead->project_type)
            <div class="field">
                <span class="field-label">Project Type:</span>
                <span class="field-value">{{ $lead->project_type }}</span>
            </div>
            @endif

            @if($lead->budget_range)
            <div class="field">
                <span class="field-label">Budget Range:</span>
                <span class="field-value highlight">
                    @switch($lead->budget_range)
                        @case('under_10k') Under $10,000 @break
                        @case('10k_25k') $10,000 - $25,000 @break
                        @case('25k_50k') $25,000 - $50,000 @break
                        @case('50k_100k') $50,000 - $100,000 @break
                        @case('over_100k') Over $100,000 @break
                        @default {{ $lead->budget_range }}
                    @endswitch
                </span>
            </div>
            @endif

            @if($lead->timeline)
            <div class="field">
                <span class="field-label">Timeline:</span>
                <span class="field-value">{{ $lead->timeline }}</span>
            </div>
            @endif

            @if($lead->design_style)
            <div class="field">
                <span class="field-label">Design Style:</span>
                <span class="field-value">{{ $lead->design_style }}</span>
            </div>
            @endif

            @if($lead->wood_species)
            <div class="field">
                <span class="field-label">Wood Species:</span>
                <span class="field-value">{{ $lead->wood_species }}</span>
            </div>
            @endif
        </div>
        @endif

        {{-- Message --}}
        @if($lead->message || $lead->project_description)
        <div class="section">
            <div class="section-title">Message</div>
            <div class="message-box">
                {{ $lead->message ?? $lead->project_description }}
            </div>
        </div>
        @endif

        {{-- Address --}}
        @if($lead->city || $lead->street1)
        <div class="section">
            <div class="section-title">Project Location</div>
            <div class="field-value">
                @if($lead->street1){{ $lead->street1 }}<br>@endif
                @if($lead->street2){{ $lead->street2 }}<br>@endif
                {{ $lead->city }}@if($lead->state), {{ $lead->state }}@endif @if($lead->zip){{ $lead->zip }}@endif
            </div>
        </div>
        @endif

        {{-- CTA --}}
        <div style="text-align: center;">
            <a href="{{ $adminUrl }}" class="cta-button">
                View Lead in Admin Panel
            </a>
        </div>
    </div>

    <div class="footer">
        <p>This notification was sent from your TCS Woodwork ERP system.</p>
        <p>Submitted: {{ $lead->created_at->format('M d, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>
