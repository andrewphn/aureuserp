<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;

class ProjectSummaryWidget extends Widget
{
    public function render(): View
    {
        return view('filament.widgets.project-summary');
    }

    protected int | string | array $columnSpan = 'full';

    // Make widget poll for updates every 1 second (1000ms)
    protected static ?string $pollingInterval = '1s';

    // Get the parent page (CreateProject)
    protected function getOwnerPage()
    {
        return $this->getLivewire()->getParentComponent();
    }

    // Access computed properties from CreateProject page
    public function getProjectNumber(): string
    {
        try {
            return $this->getOwnerPage()->projectNumberPreview ?? 'TCS-###-Address';
        } catch (\Exception $e) {
            return 'TCS-###-Address';
        }
    }

    public function getLocationSummary(): string
    {
        try {
            return $this->getOwnerPage()->locationSummary ?? '<span class="text-gray-400">Not selected</span>';
        } catch (\Exception $e) {
            return '<span class="text-gray-400">Not selected</span>';
        }
    }

    public function getCustomerName(): string
    {
        try {
            return $this->getOwnerPage()->customerName ?? 'Not selected';
        } catch (\Exception $e) {
            return 'Not selected';
        }
    }

    public function getCompanyName(): string
    {
        try {
            return $this->getOwnerPage()->companyName ?? 'Not selected';
        } catch (\Exception $e) {
            return 'Not selected';
        }
    }

    public function getProjectType(): string
    {
        try {
            return $this->getOwnerPage()->projectTypeDisplay ?? 'Not selected';
        } catch (\Exception $e) {
            return 'Not selected';
        }
    }
}
