<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Project\Filament\Resources\ProjectResource;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected static bool $formActionsAreSticky = true;

    protected function getFooterWidgets(): array
    {
        return [
            \Webkul\Project\Filament\Resources\ProjectResource\Widgets\ProjectSummaryWidget::class,
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('projects::filament/resources/project/pages/create-project.notification.title'))
            ->body(__('projects::filament/resources/project/pages/create-project.notification.body'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creator_id'] = Auth::id();

        // Auto-generate project number if not provided
        if (empty($data['project_number'])) {
            $data['project_number'] = $this->generateProjectNumber($data);
        }

        // Auto-populate allocated_hours from production estimate if available
        if (!empty($data['estimated_linear_feet']) && !empty($data['company_id'])) {
            $estimate = \App\Services\ProductionEstimatorService::calculate(
                $data['estimated_linear_feet'],
                $data['company_id']
            );

            if ($estimate && empty($data['allocated_hours'])) {
                $data['allocated_hours'] = $estimate['hours'];
            }
        }

        return $data;
    }

    protected function generateProjectNumber(array $data): string
    {
        // Get company acronym
        $companyAcronym = 'UNK';
        if (!empty($data['company_id'])) {
            $company = \Webkul\Support\Models\Company::find($data['company_id']);
            $companyAcronym = $company?->acronym ?? strtoupper(substr($company?->name ?? 'UNK', 0, 3));
        }

        // Get next sequential number for this company
        $lastProject = \Webkul\Project\Models\Project::where('company_id', $data['company_id'])
            ->where('project_number', 'like', "{$companyAcronym}-%")
            ->orderBy('id', 'desc')
            ->first();

        $sequentialNumber = 1;
        if ($lastProject && $lastProject->project_number) {
            // Extract number from format: TCS-0001-Street
            preg_match('/-(\d+)-/', $lastProject->project_number, $matches);
            if (!empty($matches[1])) {
                $sequentialNumber = intval($matches[1]) + 1;
            }
        }

        // Get street address (remove spaces and special chars)
        $streetAbbr = '';
        if (!empty($data['project_address']['street1'])) {
            $street = preg_replace('/[^a-zA-Z0-9]/', '', $data['project_address']['street1']);
            $streetAbbr = $street;
        }

        // Format: TCS-0001-15BCorreiaLane
        return sprintf(
            '%s-%04d%s',
            $companyAcronym,
            $sequentialNumber,
            $streetAbbr ? "-{$streetAbbr}" : ''
        );
    }

    protected function afterCreate(): void
    {
        $project = $this->record;
        $data = $this->form->getState();

        // Save project address if provided
        if (!empty($data['project_address'])) {
            $addressData = $data['project_address'];

            if (!empty($addressData['street1']) || !empty($addressData['city'])) {
                $project->addresses()->create([
                    'type' => 'project',
                    'street1' => $addressData['street1'] ?? null,
                    'street2' => $addressData['street2'] ?? null,
                    'city' => $addressData['city'] ?? null,
                    'zip' => $addressData['zip'] ?? null,
                    'state_id' => null, // We're storing state as text, not ID
                    'country_id' => null,
                    'is_primary' => true,
                ]);
            }
        }

        // Save production estimate if linear feet and company are provided
        if (!empty($data['estimated_linear_feet']) && !empty($data['company_id'])) {
            $estimate = \App\Services\ProductionEstimatorService::calculate(
                $data['estimated_linear_feet'],
                $data['company_id']
            );

            if ($estimate) {
                \App\Models\ProductionEstimate::createFromEstimate(
                    $project->id,
                    $data['company_id'],
                    $data['estimated_linear_feet'],
                    $estimate
                );
            }
        }
    }
}
