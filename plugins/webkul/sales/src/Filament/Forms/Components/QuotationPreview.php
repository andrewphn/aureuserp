<?php

namespace Webkul\Sale\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Contracts\HasExtraAlpineAttributes;

/**
 * Quotation Preview class
 *
 * @see \Filament\Resources\Resource
 */
class QuotationPreview extends Field implements HasExtraAlpineAttributes
{
    protected string $view = 'sales::filament.forms.components.quotation-preview';

    /**
     * Configure the form component
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false); // Don't save this to the database
    }

    /**
     * Get extra Alpine.js attributes for reactive preview updates
     *
     * @return array<string, string>
     */
    public function getExtraAlpineAttributes(): array
    {
        return [
            'x-data' => '{
                templateId: null,
                formData: {},
                updatePreview() {
                    this.formData = $wire.$get("data");
                    this.templateId = this.formData.document_template_id;
                    this.$wire.refreshPreview(this.formData);
                }
            }',
            'x-init' => '$watch("$wire.data.document_template_id", () => updatePreview())',
        ];
    }
}
