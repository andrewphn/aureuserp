<?php

namespace App\Services\Footer\Contexts;

use App\Services\Footer\Contracts\ContextProviderInterface;
use App\Services\Footer\ContextFieldBuilder;
use Filament\Actions\Action;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\DB;

/**
 * Project Context Provider
 *
 * Provides context-specific data and field definitions for Project entities.
 */
class ProjectContextProvider implements ContextProviderInterface
{
    public function getContextType(): string
    {
        return 'project';
    }

    public function getContextName(): string
    {
        return 'Project';
    }

    public function getEmptyLabel(): string
    {
        return 'No Project Selected';
    }

    public function getBorderColor(): string
    {
        return 'rgb(59, 130, 246)'; // Blue
    }

    public function getIconPath(): string
    {
        // Folder icon
        return 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z';
    }

    public function loadContext(int|string $entityId): array
    {
        // Load project data from database
        $project = DB::table('projects_projects')
            ->where('id', $entityId)
            ->first();

        if (!$project) {
            return [];
        }

        // Convert to array
        $data = (array) $project;

        // Load related data
        if ($project->partner_id) {
            $partner = DB::table('partners_partners')
                ->where('id', $project->partner_id)
                ->first();

            $data['_customerName'] = $partner->name ?? '—';
        }

        // Calculate estimates if linear feet available
        if ($project->estimated_linear_feet && $project->company_id) {
            $data['_estimate'] = $this->calculateEstimate(
                $project->estimated_linear_feet,
                $project->company_id
            );
        }

        // Load tags
        $data['_tags'] = $this->loadProjectTags($entityId);

        return $data;
    }

    public function getFieldSchema(array $data, bool $isMinimized = false): array
    {
        // For minimized view, return only most important fields
        if ($isMinimized) {
            return [
                ContextFieldBuilder::copyable('project_number', 'Project #')
                    ->state($data['project_number'] ?? '—'),

                ContextFieldBuilder::text('_customerName', 'Customer')
                    ->state($data['_customerName'] ?? '—')
                    ->weight(FontWeight::SemiBold),
            ];
        }

        // For expanded view, return all fields
        return $this->getExpandedSchema($data);
    }

    /**
     * Get expanded field schema (all important fields)
     */
    protected function getExpandedSchema(array $data): array
    {
        $fields = [
            ContextFieldBuilder::copyable('project_number', 'Project #')
                ->state($data['project_number'] ?? '—'),

            ContextFieldBuilder::text('_customerName', 'Customer')
                ->state($data['_customerName'] ?? '—')
                ->weight(FontWeight::SemiBold),

            ContextFieldBuilder::badge('project_type', 'Type', 'info')
                ->state($this->formatProjectType($data['project_type'] ?? null)),
        ];

        // Add linear feet if available
        if (!empty($data['estimated_linear_feet'])) {
            $fields[] = ContextFieldBuilder::number('estimated_linear_feet', 'Linear Feet', ' LF')
                ->state($data['estimated_linear_feet']);
        }

        // Add estimates if available
        if (!empty($data['_estimate'])) {
            $estimate = $data['_estimate'];

            if (!empty($estimate['days'])) {
                $fields[] = ContextFieldBuilder::metric('_estimate_days', 'days', 'heroicon-o-calendar', 'info')
                    ->state(number_format($estimate['days'], 1));
            }

            if (!empty($estimate['weeks'])) {
                $fields[] = ContextFieldBuilder::metric('_estimate_weeks', 'wks', 'heroicon-o-chart-bar', 'warning')
                    ->state(number_format($estimate['weeks'], 1));
            }

            if (!empty($estimate['months'])) {
                $fields[] = ContextFieldBuilder::metric('_estimate_months', 'mos', 'heroicon-o-calendar-days', 'success')
                    ->state(number_format($estimate['months'], 1));
            }
        }

        // Add completion date if available
        if (!empty($data['desired_completion_date'])) {
            $fields[] = ContextFieldBuilder::date('desired_completion_date', 'Due Date')
                ->state($data['desired_completion_date']);
        }

        // Add tags if available
        if (!empty($data['_tags']) && count($data['_tags']) > 0) {
            $fields[] = $this->createTagsField($data['_tags']);
        }

        return $fields;
    }

    /**
     * Create tags field with modal trigger
     */
    protected function createTagsField(array $tags): TextEntry
    {
        $tagCount = count($tags);

        return TextEntry::make('_tags')
            ->label('Tags')
            ->state($tagCount . ' ' . \Illuminate\Support\Str::plural('tag', $tagCount))
            ->badge()
            ->color('primary')
            ->icon('heroicon-o-tag')
            ->extraAttributes([
                'class' => 'cursor-pointer',
                '@click' => 'tagsModalOpen = true',
            ]);
    }

    public function getDefaultPreferences(): array
    {
        return [
            'minimized_fields' => ['project_number', '_customerName'],
            'expanded_fields' => [
                'project_number',
                '_customerName',
                'project_type',
                'estimated_linear_feet',
                '_estimate_days',
                '_estimate_weeks',
                '_estimate_months',
                'desired_completion_date',
                '_tags',
            ],
            'field_order' => [],
        ];
    }

    public function getApiEndpoints(): array
    {
        return [
            'fetch' => fn($id) => "/api/projects/{$id}",
            'tags' => fn($id) => "/api/projects/{$id}/tags",
        ];
    }

    public function supportsFeature(string $feature): bool
    {
        return in_array($feature, ['tags', 'timeline_alerts', 'estimates']);
    }

    public function getActions(array $data): array
    {
        $actions = [];

        // Edit action (only if not on edit page)
        if (!empty($data['id']) && !request()->is('*/edit')) {
            $actions[] = Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->color('gray')
                ->url(route('filament.admin.resources.project.projects.edit', ['record' => $data['id']]));
        }

        return $actions;
    }

    /**
     * Calculate production estimate
     */
    protected function calculateEstimate(float $linearFeet, int $companyId): array
    {
        // Get company production rate from database
        $company = DB::table('companies_companies')
            ->where('id', $companyId)
            ->first();

        $shopCapacityPerDay = $company->shop_capacity_per_day ?? 8.0; // Default 8 hours/day

        // Calculate based on linear feet
        // Assuming 1 linear foot = 1 hour of production time (adjust as needed)
        $hours = $linearFeet;
        $days = $hours / $shopCapacityPerDay;
        $weeks = $days / 5; // 5 working days per week
        $months = $weeks / 4.33; // Average weeks per month

        return [
            'hours' => round($hours, 1),
            'days' => round($days, 1),
            'weeks' => round($weeks, 1),
            'months' => round($months, 2),
            'shop_capacity_per_day' => $shopCapacityPerDay,
        ];
    }

    /**
     * Load project tags
     */
    protected function loadProjectTags(int|string $projectId): array
    {
        try {
            // Load tags from database (if tags system is installed)
            $tags = DB::table('tags_relations')
                ->join('tags_tags', 'tags_relations.tag_id', '=', 'tags_tags.id')
                ->where('tags_relations.entity_type', 'project')
                ->where('tags_relations.entity_id', $projectId)
                ->select('tags_tags.*')
                ->get()
                ->toArray();

            return array_map(fn($tag) => (array) $tag, $tags);
        } catch (\Exception $e) {
            // Tags table doesn't exist or error loading tags
            // Return empty array to gracefully handle missing tags functionality
            return [];
        }
    }

    /**
     * Format project type for display
     */
    protected function formatProjectType(?string $type): string
    {
        if (!$type) {
            return '—';
        }

        return str($type)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
