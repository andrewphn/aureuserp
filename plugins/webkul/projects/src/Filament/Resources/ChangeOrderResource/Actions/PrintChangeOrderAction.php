<?php

namespace Webkul\Project\Filament\Resources\ChangeOrderResource\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Webkul\Project\Models\ChangeOrder;

/**
 * Print Change Order Action
 *
 * Generates a printable PDF document for change orders
 * using the TCS invoice-style template.
 */
class PrintChangeOrderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'print_change_order';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('projects::filament/resources/change-order.actions.print.label'))
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->action(function (ChangeOrder $record) {
                $html = $this->renderChangeOrderHtml($record);

                $pdf = Pdf::loadHTML($html)
                    ->setPaper('letter', 'portrait')
                    ->setOption('defaultFont', 'Arial')
                    ->setOption('isHtml5ParserEnabled', true)
                    ->setOption('isRemoteEnabled', true);

                $filename = sprintf(
                    'Change-Order-%s-%s.pdf',
                    $record->change_order_number,
                    now()->format('Y-m-d')
                );

                return response()->streamDownload(function () use ($pdf) {
                    echo $pdf->output();
                }, $filename);
            });
    }

    /**
     * Render the change order HTML from template.
     */
    protected function renderChangeOrderHtml(ChangeOrder $record): string
    {
        $record->load(['project.partner', 'requester', 'approver', 'rejecter', 'lines']);

        $templatePath = base_path('templates/change-orders/tcs-change-order-template.html');

        if (!file_exists($templatePath)) {
            // Fallback to blade view if template doesn't exist
            return view('projects::filament.pages.print-change-order', [
                'record' => $record,
            ])->render();
        }

        $template = file_get_contents($templatePath);

        // Prepare variables
        $variables = $this->prepareTemplateVariables($record);

        // Replace all variables in template
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value ?? '', $template);
        }

        return $template;
    }

    /**
     * Prepare template variables from change order.
     */
    protected function prepareTemplateVariables(ChangeOrder $record): array
    {
        $project = $record->project;
        $partner = $project?->partner;

        return [
            // Change Order Info
            'CHANGE_ORDER_NUMBER' => $record->change_order_number,
            'CHANGE_ORDER_TITLE' => $record->title,
            'DESCRIPTION' => $record->description ?? 'No description provided.',
            'REASON' => $record->reason,
            'REASON_LABEL' => ChangeOrder::getReasons()[$record->reason] ?? ucfirst($record->reason),
            'STATUS' => $record->status,
            'STATUS_LABEL' => ChangeOrder::getStatuses()[$record->status] ?? ucfirst($record->status),

            // Project Info
            'PROJECT_NAME' => $project?->name ?? 'N/A',
            'AFFECTED_STAGE' => $record->affected_stage ? ucfirst($record->affected_stage) : 'Not specified',

            // Client Info
            'CLIENT_NAME' => $partner?->name ?? 'N/A',
            'CLIENT_ADDRESS' => $this->formatAddress($partner),
            'CLIENT_PHONE' => $partner?->phone ?? $partner?->mobile ?? 'N/A',

            // Dates & People
            'REQUESTED_DATE' => $record->requested_at?->format('M j, Y') ?? now()->format('M j, Y'),
            'REQUESTED_BY' => $record->requester?->name ?? 'N/A',
            'EFFECTIVE_DATE' => $record->applied_at?->format('M j, Y') ?? 'Pending',

            // Financial Impact
            'PRICE_DELTA' => $this->formatMoney($record->price_delta),
            'PRICE_DELTA_CLASS' => $record->price_delta > 0 ? 'positive' : ($record->price_delta < 0 ? 'negative' : ''),
            'LABOR_HOURS_DELTA' => $this->formatHours($record->labor_hours_delta),

            // Dynamic Sections
            'CHANGES_TABLE' => $this->renderChangesTable($record),
            'APPROVAL_SECTION' => $this->renderApprovalSection($record),
            'REJECTION_SECTION' => $this->renderRejectionSection($record),

            // Company Logo (placeholder - can be replaced with base64 encoded image)
            'COMPANY_LOGO_BASE64' => '',
        ];
    }

    /**
     * Format partner address.
     */
    protected function formatAddress($partner): string
    {
        if (!$partner) {
            return 'N/A';
        }

        $parts = array_filter([
            $partner->street,
            $partner->street2,
            implode(', ', array_filter([
                $partner->city,
                $partner->state?->name ?? $partner->state_id,
                $partner->zip,
            ])),
        ]);

        return implode('<br>', $parts) ?: 'N/A';
    }

    /**
     * Format money value.
     */
    protected function formatMoney($value): string
    {
        if ($value === null || $value == 0) {
            return '$0.00';
        }

        $prefix = $value > 0 ? '+' : '';
        return $prefix . '$' . number_format(abs($value), 2);
    }

    /**
     * Format hours value.
     */
    protected function formatHours($value): string
    {
        if ($value === null || $value == 0) {
            return '0 hrs';
        }

        $prefix = $value > 0 ? '+' : '';
        return $prefix . number_format($value, 1) . ' hrs';
    }

    /**
     * Render changes table HTML.
     */
    protected function renderChangesTable(ChangeOrder $record): string
    {
        $lines = $record->lines;

        if ($lines->isEmpty()) {
            return '<div class="no-changes">No specific change lines recorded.</div>';
        }

        $rows = '';
        foreach ($lines as $line) {
            $priceClass = '';
            $priceFormatted = '$0.00';

            if ($line->price_impact > 0) {
                $priceClass = 'price-positive';
                $priceFormatted = '+$' . number_format($line->price_impact, 2);
            } elseif ($line->price_impact < 0) {
                $priceClass = 'price-negative';
                $priceFormatted = '-$' . number_format(abs($line->price_impact), 2);
            }

            $rows .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td class="old-value">%s</td>
                    <td class="new-value">%s</td>
                    <td style="text-align: right;" class="%s">%s</td>
                </tr>',
                e($line->entity_type),
                e($line->entity_id),
                e($line->field_name),
                e($line->old_value ?? '(empty)'),
                e($line->new_value ?? '(empty)'),
                $priceClass,
                $priceFormatted
            );
        }

        return sprintf(
            '<table class="changes-table">
                <thead>
                    <tr>
                        <th style="width: 15%%;">Item Type</th>
                        <th style="width: 10%%;">Item ID</th>
                        <th style="width: 15%%;">Field</th>
                        <th style="width: 20%%;">Previous Value</th>
                        <th style="width: 20%%;">New Value</th>
                        <th style="width: 20%%; text-align: right;">Price Impact</th>
                    </tr>
                </thead>
                <tbody>%s</tbody>
            </table>',
            $rows
        );
    }

    /**
     * Render approval section if approved.
     */
    protected function renderApprovalSection(ChangeOrder $record): string
    {
        if (!$record->approved_at) {
            return '';
        }

        $notes = $record->approval_notes
            ? '<p><strong>Notes:</strong> ' . e($record->approval_notes) . '</p>'
            : '';

        return sprintf(
            '<div class="approval-section">
                <h4>Approved</h4>
                <p><strong>Approved By:</strong> %s</p>
                <p><strong>Date:</strong> %s</p>
                %s
            </div>',
            e($record->approver?->name ?? 'N/A'),
            $record->approved_at->format('M j, Y g:i A'),
            $notes
        );
    }

    /**
     * Render rejection section if rejected.
     */
    protected function renderRejectionSection(ChangeOrder $record): string
    {
        if (!$record->rejected_at) {
            return '';
        }

        return sprintf(
            '<div class="rejection-section">
                <h4>Rejected</h4>
                <p><strong>Rejected By:</strong> %s</p>
                <p><strong>Date:</strong> %s</p>
                <p><strong>Reason:</strong> %s</p>
            </div>',
            e($record->rejecter?->name ?? 'N/A'),
            $record->rejected_at->format('M j, Y g:i A'),
            e($record->rejection_reason ?? 'No reason provided.')
        );
    }
}
