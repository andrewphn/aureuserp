<?php

namespace App\Livewire;

use Livewire\Component;

/**
 * Project Summary Panel class
 *
 */
class ProjectSummaryPanel extends Component
{
    /**
     * Render
     *
     */
    public function render()
    {
        $fields = $this->getSummaryFields();

        return view('livewire.project-summary-panel', [
            'fields' => $fields,
            'heading' => 'Project Summary',
            'description' => 'Quick preview of your project details',
            'collapsed' => true,
            'gridCols' => 'grid-cols-2 md:grid-cols-3 lg:grid-cols-5',
        ]);
    }

    protected function getSummaryFields(): array
    {
        return [
            [
                'label' => 'Project Number',
                'key' => 'project_number',
                'default' => 'Will be assigned on save',
                'formatter' => <<<'JS'
                function(formData, defaultValue) {
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
                    if (formData.name && formData.name.trim()) {
                        return formData.name;
                    }

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
                    if (formData.project_type) {
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
