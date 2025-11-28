<?php

namespace App\Services\Footer\Contexts;

use App\Services\Footer\Contracts\ContextProviderInterface;
use App\Services\Footer\ContextFieldBuilder;
use Filament\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\DB;

/**
 * Production Context Provider
 *
 * Provides context-specific data and field definitions for Production Job entities.
 */
class ProductionContextProvider implements ContextProviderInterface
{
    public function getContextType(): string
    {
        return 'production';
    }

    public function getContextName(): string
    {
        return 'Production Job';
    }

    public function getEmptyLabel(): string
    {
        return 'No Job Selected';
    }

    public function getBorderColor(): string
    {
        return 'rgb(249, 115, 22)'; // Orange
    }

    public function getIconPath(): string
    {
        // Beaker/Production icon
        return 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z';
    }

    /**
     * Load Context
     *
     * @param int|string $entityId
     * @return array
     */
    public function loadContext(int|string $entityId): array
    {
        // Load production job data
        $job = DB::table('production_jobs')
            ->where('id', $entityId)
            ->first();

        if (!$job) {
            return [];
        }

        $data = (array) $job;

        // Load project name if linked
        if ($job->project_id) {
            $project = DB::table('projects_projects')
                ->where('id', $job->project_id)
                ->first();

            $data['_projectName'] = $project->project_number ?? '—';
        }

        // Load customer name if linked
        if ($job->partner_id) {
            $partner = DB::table('partners_partners')
                ->where('id', $job->partner_id)
                ->first();

            $data['_customerName'] = $partner->name ?? '—';
        }

        return $data;
    }

    /**
     * Get Field Schema
     *
     * @param array $data The data array
     * @param bool $isMinimized
     * @return array
     */
    public function getFieldSchema(array $data, bool $isMinimized = false): array
    {
        if ($isMinimized) {
            return $this->getMinimizedSchema($data);
        }

        return $this->getExpandedSchema($data);
    }

    /**
     * Get Minimized Schema
     *
     * @param array $data The data array
     * @return array
     */
    protected function getMinimizedSchema(array $data): array
    {
        return [
            ContextFieldBuilder::prominentText('job_number', 'Job #')
                ->state($data['job_number'] ?? '—'),

            ContextFieldBuilder::text('_projectName', 'Project')
                ->state($data['_projectName'] ?? $data['_customerName'] ?? '—'),
        ];
    }

    /**
     * Get Expanded Schema
     *
     * @param array $data The data array
     * @return array
     */
    protected function getExpandedSchema(array $data): array
    {
        $fields = [
            ContextFieldBuilder::copyable('job_number', 'Job #')
                ->state($data['job_number'] ?? '—'),
        ];

        // Project name
        if (!empty($data['_projectName'])) {
            $fields[] = ContextFieldBuilder::text('_projectName', 'Project')
                ->state($data['_projectName'])
                ->weight(FontWeight::SemiBold);
        }

        // Customer name
        if (!empty($data['_customerName'])) {
            $fields[] = ContextFieldBuilder::text('_customerName', 'Customer')
                ->state($data['_customerName']);
        }

        // Production status
        if (!empty($data['production_status'])) {
            $fields[] = ContextFieldBuilder::badge('production_status', 'Status', $this->getStatusColor($data['production_status']))
                ->state(str($data['production_status'])->title()->toString());
        }

        // Assigned to
        if (!empty($data['assigned_to'])) {
            $fields[] = ContextFieldBuilder::iconText('assigned_to', 'Assigned To', 'heroicon-o-user')
                ->state($data['assigned_to']);
        }

        // Start date
        if (!empty($data['start_date'])) {
            $fields[] = ContextFieldBuilder::date('start_date', 'Start Date')
                ->state($data['start_date']);
        }

        // Due date
        if (!empty($data['due_date'])) {
            $fields[] = ContextFieldBuilder::date('due_date', 'Due Date')
                ->state($data['due_date']);
        }

        return $fields;
    }

    public function getDefaultPreferences(): array
    {
        return [
            'minimized_fields' => ['job_number', '_projectName'],
            'expanded_fields' => [
                'job_number',
                '_projectName',
                '_customerName',
                'production_status',
                'assigned_to',
                'start_date',
                'due_date',
            ],
            'field_order' => [],
        ];
    }

    public function getApiEndpoints(): array
    {
        return [
            'fetch' => fn($id) => "/api/production/jobs/{$id}",
        ];
    }

    /**
     * Supports Feature
     *
     * @param string $feature
     * @return bool
     */
    public function supportsFeature(string $feature): bool
    {
        return false;
    }

    /**
     * Get Actions
     *
     * @param array $data The data array
     * @return array
     */
    public function getActions(array $data): array
    {
        $actions = [];

        if (!empty($data['id']) && !request()->is('*/edit')) {
            $actions[] = Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->color('gray')
                ->url(route('filament.admin.resources.production.jobs.edit', ['record' => $data['id']]));
        }

        return $actions;
    }

    /**
     * Get Status Color
     *
     * @param string $status
     * @return string
     */
    protected function getStatusColor(string $status): string
    {
        return match(strtolower($status)) {
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'on_hold' => 'gray',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
}
