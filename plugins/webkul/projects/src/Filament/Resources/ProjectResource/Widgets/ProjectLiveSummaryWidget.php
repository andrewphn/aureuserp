<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use App\Filament\Components\LiveSummaryPanel;

/**
 * Project Live Summary Widget Filament widget
 *
 * @see \Filament\Resources\Resource
 */
class ProjectLiveSummaryWidget extends LiveSummaryPanel
{
    public string $heading = 'Project Summary';
    public ?string $description = 'Quick preview of your project details (updates live as you fill the form)';
    public bool $collapsed = true;
    public string $gridCols = 'grid-cols-2 md:grid-cols-3 lg:grid-cols-5';

    protected function getSummaryFields(): array
    {
        return [
            [
                'label' => 'Project Number',
                'key' => 'project_number',
                'default' => 'Will be assigned on save',
                'formatter' => <<<'JS'
                function(formData, defaultValue) {
                    // Project number is generated on save, so always show default
                    return defaultValue;
                }
                JS,
            ],
            [
                'label' => 'Location',
                'key' => 'location',
                'default' => '<span class="text-gray-400">Not selected</span>',
                'icon' => '📍',
                'isHtml' => true,
                'formatter' => <<<'JS'
                function(formData, defaultValue) {
                    // Get the auto-populated project name (which is the address)
                    if (formData.name && formData.name.trim()) {
                        return formData.name;
                    }

                    // Fallback to building address from project_address fields
                    if (formData.project_address) {
                        const addr = formData.project_address;
                        const parts = [];

                        if (addr.street1) parts.push(addr.street1);
                        if (addr.city) parts.push(addr.city);

                        if (parts.length > 0) {
                            return parts.join(', ');
                        }
                    }

                    return defaultValue;
                }
                JS,
            ],
            [
                'label' => 'Customer',
                'key' => 'customer',
                'default' => 'Not selected',
                'icon' => '👤',
                'formatter' => <<<'JS'
                function(formData, defaultValue) {
                    if (!formData.partner_id) return defaultValue;

                    // Try to find the selected partner name from the Filament select component
                    const partnerSelect = document.querySelector('[wire\\:model="data.partner_id"]');
                    if (partnerSelect) {
                        const selectedOption = partnerSelect.querySelector('option:checked');
                        if (selectedOption && selectedOption.textContent) {
                            return selectedOption.textContent.trim();
                        }
                    }

                    return 'Selected (ID: ' + formData.partner_id + ')';
                }
                JS,
            ],
            [
                'label' => 'Company',
                'key' => 'company',
                'default' => 'Not selected',
                'icon' => '🏢',
                'formatter' => <<<'JS'
                function(formData, defaultValue) {
                    if (!formData.company_id) return defaultValue;

                    // Try to find the selected company name from the Filament select component
                    const companySelect = document.querySelector('[wire\\:model="data.company_id"]');
                    if (companySelect) {
                        const selectedOption = companySelect.querySelector('option:checked');
                        if (selectedOption && selectedOption.textContent) {
                            return selectedOption.textContent.trim();
                        }
                    }

                    return 'Selected (ID: ' + formData.company_id + ')';
                }
                JS,
            ],
            [
                'label' => 'Type',
                'key' => 'type',
                'default' => 'Not selected',
                'icon' => '🏷️',
                'formatter' => <<<'JS'
                function(formData, defaultValue) {
                    // Check for project_type field
                    if (formData.project_type) {
                        // If "Other" is selected, show the custom type
                        if (formData.project_type === 'Other' && formData.project_type_other) {
                            return formData.project_type_other;
                        }
                        return formData.project_type;
                    }

                    return defaultValue;
                }
                JS,
            ],
        ];
    }
}
