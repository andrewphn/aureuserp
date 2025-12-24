<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\VendorResource\Pages;

use App\Filament\Forms\Components\AiVendorLookup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\VendorResource\Pages\CreateVendor as BaseCreateVendor;
use Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\VendorResource;

/**
 * Create Vendor class
 *
 * Enhanced with AI-powered vendor lookup capability using Gemini AI.
 * Users can search for vendor information by name or website URL
 * and auto-fill the form fields.
 *
 * @see \Filament\Resources\Resource
 */
class CreateVendor extends BaseCreateVendor
{
    protected static string $resource = VendorResource::class;

    /**
     * Override form to inject AI Vendor Lookup component at the top
     */
    public function form(Schema $schema): Schema
    {
        // Get the parent form schema
        $parentSchema = parent::form($schema);

        // Create the AI lookup section
        $aiLookupSection = Section::make('AI Vendor Lookup')
            ->description('Use AI to automatically look up and fill vendor information')
            ->icon('heroicon-o-sparkles')
            ->collapsible()
            ->collapsed(false)
            ->schema([
                AiVendorLookup::make(),
            ])
            ->columnSpanFull();

        // Get existing components and prepend the AI section
        $existingComponents = $parentSchema->getComponents();

        return $schema->components([
            $aiLookupSection,
            ...$existingComponents,
        ]);
    }

    /**
     * Mutate Form Data Before Create
     *
     * @param array $data The data array
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sub_type'] = 'supplier';

        return $data;
    }
}
