<?php

namespace Webkul\Support\Filament\Forms\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\Support\Filament\Forms\Components\AddressAutocomplete;
use Webkul\Support\Filament\Forms\Schemas\Components\CountrySelect;
use Webkul\Support\Filament\Forms\Schemas\Components\CurrencySelect;
use Webkul\Support\Filament\Forms\Schemas\Components\StateSelect;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Country;
use Webkul\Support\Models\Currency;
use Webkul\Support\Models\State;

/**
 * Reusable Company form schema following FilamentPHP 4.x patterns
 * Can be used for Create/Edit modals across different resources
 *
 * @see https://filamentphp.com/docs/4.x/resources/code-quality-tips
 */
class CompanyFormSchema
{
    /**
     * Configure the full company form schema
     * Use this for complete company forms in slideOver modals
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            ...static::getEssentialFields(),
            static::getAddressSection(),
            static::getBusinessDetailsSection(),
            static::getProductionCapacitySection(),
            static::getBrandingSection(),
            static::getStatusToggle(),
        ]);
    }

    /**
     * Configure simplified company form (essential + address only)
     */
    public static function configureSimplified(Schema $schema): Schema
    {
        return $schema->components([
            ...static::getEssentialFields(),
            static::getAddressSection(),
            static::getStatusToggle(),
        ]);
    }

    /**
     * Configure branch form schema (company with parent_id)
     */
    public static function configureBranch(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('parent_id'),
            ...static::getEssentialFieldsForBranch(),
            static::getAddressSection(collapsed: true, description: 'Branch physical location'),
            static::getBusinessDetailsSection(),
            static::getProductionCapacitySection(),
            static::getBrandingSection(),
            static::getStatusToggle(),
        ]);
    }

    // ========================================
    // REUSABLE COMPONENT METHODS
    // ========================================

    /**
     * Name input field for company
     */
    public static function getNameInput(): TextInput
    {
        return TextInput::make('name')
            ->label('Company Name')
            ->required()
            ->maxLength(255)
            ->placeholder('e.g. TCS Woodwork')
            ->autofocus();
    }

    /**
     * Acronym input field
     */
    public static function getAcronymInput(): TextInput
    {
        return TextInput::make('acronym')
            ->label('Acronym')
            ->maxLength(10)
            ->placeholder('e.g. TCS')
            ->helperText('Used in project numbers');
    }

    /**
     * Phone input field
     */
    public static function getPhoneInput(): TextInput
    {
        return TextInput::make('phone')
            ->label('Phone')
            ->tel()
            ->maxLength(255)
            ->placeholder('(508) 555-1234')
            ->prefixIcon('heroicon-o-phone');
    }

    /**
     * Email input field
     */
    public static function getEmailInput(): TextInput
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->maxLength(255)
            ->placeholder('info@example.com')
            ->prefixIcon('heroicon-o-envelope');
    }

    /**
     * Mobile input field
     */
    public static function getMobileInput(): TextInput
    {
        return TextInput::make('mobile')
            ->label('Mobile')
            ->tel()
            ->maxLength(255)
            ->placeholder('(508) 555-5678');
    }

    /**
     * Website input field
     */
    public static function getWebsiteInput(): TextInput
    {
        return TextInput::make('website')
            ->label('Website')
            ->url()
            ->maxLength(255)
            ->placeholder('https://example.com');
    }

    // ========================================
    // FIELD GROUPS
    // ========================================

    /**
     * Get essential company fields (always visible)
     */
    public static function getEssentialFields(): array
    {
        return [
            Grid::make(2)->schema([
                static::getNameInput()->columnSpan(1),
                static::getAcronymInput()->columnSpan(1),
            ]),

            Grid::make(2)->schema([
                static::getPhoneInput(),
                static::getEmailInput(),
            ]),

            Grid::make(2)->schema([
                static::getMobileInput(),
                static::getWebsiteInput(),
            ]),
        ];
    }

    /**
     * Get essential branch fields (with Branch-specific labels)
     */
    public static function getEssentialFieldsForBranch(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('name')
                    ->label('Branch Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Nantucket Branch')
                    ->autofocus()
                    ->columnSpan(1),

                static::getAcronymInput()->columnSpan(1),
            ]),

            Grid::make(2)->schema([
                static::getPhoneInput(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255)
                    ->placeholder('branch@example.com')
                    ->prefixIcon('heroicon-o-envelope'),
            ]),

            Grid::make(2)->schema([
                static::getMobileInput(),
                TextInput::make('website')
                    ->label('Website')
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://branch.example.com'),
            ]),
        ];
    }

    // ========================================
    // COLLAPSIBLE SECTIONS
    // ========================================

    /**
     * Get address section (collapsible)
     */
    public static function getAddressSection(bool $collapsed = true, string $description = 'Company headquarters location'): Section
    {
        return Section::make('Address')
            ->description($description)
            ->icon('heroicon-o-map-pin')
            ->schema([
                AddressAutocomplete::make('street1')
                    ->label('Street Address')
                    ->cityField('city')
                    ->stateField('state_id')
                    ->zipField('zip')
                    ->countryField('country_id')
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('street2')
                    ->label('Suite/Unit')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    TextInput::make('city')
                        ->label('City')
                        ->maxLength(255),
                    TextInput::make('zip')
                        ->label('Zip')
                        ->maxLength(255),
                ]),

                Grid::make(2)->schema([
                    \Filament\Forms\Components\Select::make('state_id')
                        ->label('State')
                        ->options(fn () => State::where('country_id', 233)
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),

                    \Filament\Forms\Components\Select::make('country_id')
                        ->label('Country')
                        ->options(fn () => Country::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->default(233),
                ]),
            ])
            ->compact()
            ->collapsible()
            ->collapsed($collapsed);
    }

    /**
     * Get business details section (collapsible)
     */
    public static function getBusinessDetailsSection(bool $collapsed = true): Section
    {
        return Section::make('Business Details')
            ->description('Tax ID, registration, and project settings')
            ->icon('heroicon-o-building-office')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('tax_id')
                        ->label('Tax ID / EIN')
                        ->maxLength(255)
                        ->placeholder('12-3456789'),

                    TextInput::make('registration_number')
                        ->label('Registration Number')
                        ->maxLength(255)
                        ->placeholder('Company registry #'),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('project_number_start')
                        ->label('Project Number Start')
                        ->numeric()
                        ->minValue(1)
                        ->placeholder('e.g. 501')
                        ->helperText('Starting number for projects'),

                    \Filament\Forms\Components\Select::make('currency_id')
                        ->label('Currency')
                        ->options(fn () => Currency::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->helperText('Default currency'),
                ]),

                DatePicker::make('founded_date')
                    ->label('Founded Date')
                    ->native(false)
                    ->suffixIcon('heroicon-o-calendar')
                    ->columnSpanFull(),
            ])
            ->compact()
            ->collapsible()
            ->collapsed($collapsed);
    }

    /**
     * Get production capacity section (collapsible)
     */
    public static function getProductionCapacitySection(bool $collapsed = true): Section
    {
        return Section::make('Production Capacity')
            ->description('Shop capacity and working hours settings')
            ->icon('heroicon-o-chart-bar')
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('shop_capacity_per_hour')
                        ->label('LF Per Hour')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->placeholder('e.g. 2.5')
                        ->helperText('Linear feet per hour'),

                    TextInput::make('shop_capacity_per_day')
                        ->label('LF Per Day')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->placeholder('e.g. 20')
                        ->helperText('Linear feet per day'),

                    TextInput::make('shop_capacity_per_month')
                        ->label('LF Per Month')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->placeholder('e.g. 340')
                        ->helperText('Linear feet per month'),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('working_hours_per_day')
                        ->label('Working Hours/Day')
                        ->numeric()
                        ->step(0.5)
                        ->minValue(0)
                        ->maxValue(24)
                        ->default(8)
                        ->placeholder('e.g. 8'),

                    TextInput::make('working_days_per_month')
                        ->label('Working Days/Month')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(31)
                        ->default(17)
                        ->placeholder('e.g. 17'),
                ]),
            ])
            ->compact()
            ->collapsible()
            ->collapsed($collapsed);
    }

    /**
     * Get branding section (collapsible)
     */
    public static function getBrandingSection(bool $collapsed = true): Section
    {
        return Section::make('Branding')
            ->description('Logo and color settings')
            ->icon('heroicon-o-paint-brush')
            ->schema([
                Grid::make(2)->schema([
                    FileUpload::make('logo')
                        ->label('Company Logo')
                        ->image()
                        ->imageResizeMode('cover')
                        ->imageEditor()
                        ->directory('companies/logos')
                        ->visibility('public'),

                    ColorPicker::make('color')
                        ->label('Brand Color')
                        ->hexColor(),
                ]),
            ])
            ->compact()
            ->collapsible()
            ->collapsed($collapsed);
    }

    /**
     * Get status toggle
     */
    public static function getStatusToggle(): Toggle
    {
        return Toggle::make('is_active')
            ->label('Active')
            ->default(true)
            ->inline();
    }

    // ========================================
    // ARRAY-BASED METHODS FOR BACKWARD COMPATIBILITY
    // ========================================

    /**
     * Get full company form schema as array
     * Use when you need array instead of Schema object
     */
    public static function getFullSchema(): array
    {
        return [
            ...static::getEssentialFields(),
            static::getAddressSection(),
            static::getBusinessDetailsSection(),
            static::getProductionCapacitySection(),
            static::getBrandingSection(),
            static::getStatusToggle(),
        ];
    }

    /**
     * Get simplified form schema as array
     */
    public static function getSimplifiedSchema(): array
    {
        return [
            ...static::getEssentialFields(),
            static::getAddressSection(),
            static::getStatusToggle(),
        ];
    }

    /**
     * Get branch form schema as array
     */
    public static function getBranchSchema(): array
    {
        return [
            Hidden::make('parent_id'),
            ...static::getEssentialFieldsForBranch(),
            static::getAddressSection(collapsed: true, description: 'Branch physical location'),
            static::getBusinessDetailsSection(),
            static::getProductionCapacitySection(),
            static::getBrandingSection(),
            static::getStatusToggle(),
        ];
    }

    // ========================================
    // DATA LOADING HELPERS
    // ========================================

    /**
     * Get form defaults for new company
     */
    public static function getDefaults(): array
    {
        return [
            'country_id' => 233,
            'is_active' => true,
            'working_hours_per_day' => 8,
            'working_days_per_month' => 17,
        ];
    }

    /**
     * Load company data for form fill
     */
    public static function loadFormData(?int $companyId): array
    {
        if (!$companyId) {
            return static::getDefaults();
        }

        $company = Company::find($companyId);
        if (!$company) {
            return static::getDefaults();
        }

        return [
            // Essential info
            'name' => $company->name,
            'acronym' => $company->acronym,
            // Contact info
            'email' => $company->email,
            'phone' => $company->phone,
            'mobile' => $company->mobile,
            'website' => $company->website,
            // Address
            'street1' => $company->street1,
            'street2' => $company->street2,
            'city' => $company->city,
            'state_id' => $company->state_id,
            'zip' => $company->zip,
            'country_id' => $company->country_id ?? 233,
            // Business details
            'tax_id' => $company->tax_id,
            'registration_number' => $company->registration_number,
            'project_number_start' => $company->project_number_start,
            'currency_id' => $company->currency_id,
            'founded_date' => $company->founded_date,
            // Production capacity
            'shop_capacity_per_hour' => $company->shop_capacity_per_hour,
            'shop_capacity_per_day' => $company->shop_capacity_per_day,
            'shop_capacity_per_month' => $company->shop_capacity_per_month,
            'working_hours_per_day' => $company->working_hours_per_day ?? 8,
            'working_days_per_month' => $company->working_days_per_month ?? 17,
            // Branding
            'logo' => $company->logo,
            'color' => $company->color,
            // Status
            'is_active' => $company->is_active ?? true,
        ];
    }

    /**
     * Load branch data for form fill
     */
    public static function loadBranchFormData(?int $branchId, ?int $parentId = null): array
    {
        $defaults = static::getDefaults();
        $defaults['parent_id'] = $parentId;

        if (!$branchId) {
            return $defaults;
        }

        $branch = Company::find($branchId);
        if (!$branch) {
            return $defaults;
        }

        return [
            // Essential info
            'name' => $branch->name,
            'acronym' => $branch->acronym,
            'parent_id' => $branch->parent_id ?? $parentId,
            // Contact info
            'email' => $branch->email,
            'phone' => $branch->phone,
            'mobile' => $branch->mobile,
            'website' => $branch->website,
            // Address
            'street1' => $branch->street1,
            'street2' => $branch->street2,
            'city' => $branch->city,
            'state_id' => $branch->state_id,
            'zip' => $branch->zip,
            'country_id' => $branch->country_id ?? 233,
            // Business details
            'tax_id' => $branch->tax_id,
            'registration_number' => $branch->registration_number,
            'project_number_start' => $branch->project_number_start,
            'currency_id' => $branch->currency_id,
            'founded_date' => $branch->founded_date,
            // Production capacity
            'shop_capacity_per_hour' => $branch->shop_capacity_per_hour,
            'shop_capacity_per_day' => $branch->shop_capacity_per_day,
            'shop_capacity_per_month' => $branch->shop_capacity_per_month,
            'working_hours_per_day' => $branch->working_hours_per_day ?? 8,
            'working_days_per_month' => $branch->working_days_per_month ?? 17,
            // Branding
            'logo' => $branch->logo,
            'color' => $branch->color,
            // Status
            'is_active' => $branch->is_active ?? true,
        ];
    }
}
