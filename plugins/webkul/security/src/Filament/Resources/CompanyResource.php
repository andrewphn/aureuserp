<?php

namespace Webkul\Security\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Webkul\Field\Filament\Traits\HasCustomFields;
use Webkul\Security\Enums\CompanyStatus;
use Webkul\Security\Filament\Resources\CompanyResource\Pages\CreateCompany;
use Webkul\Security\Filament\Resources\CompanyResource\Pages\EditCompany;
use Webkul\Security\Filament\Resources\CompanyResource\Pages\ListCompanies;
use Webkul\Security\Filament\Resources\CompanyResource\Pages\ViewCompany;
use Webkul\Security\Filament\Resources\CompanyResource\RelationManagers\BranchesRelationManager;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Country;
use Webkul\Support\Models\Currency;

/**
 * Company Resource Filament resource
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @see \Filament\Resources\Resource
 */
class CompanyResource extends Resource
{
    use HasCustomFields;

    protected static ?string $model = Company::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('security::filament/resources/company.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('security::filament/resources/company.navigation.group');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    /**
     * Get Global Search Result Details
     *
     * @param Model $record The model record
     * @return array
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('security::filament/resources/company.global-search.name')  => $record->name ?? '—',
            __('security::filament/resources/company.global-search.email') => $record->email ?? '—',
        ];
    }

    /**
     * Define the form schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Group::make()
                            ->schema([
                                Section::make(__('security::filament/resources/company.form.sections.company-information.title'))
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('security::filament/resources/company.form.sections.company-information.fields.name'))
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true),
                                        TextInput::make('acronym')
                                            ->label(__('security::filament/resources/company.form.sections.company-information.fields.acronym'))
                                            ->maxLength(10)
                                            ->placeholder('e.g., TCS')
                                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Short abbreviation used in project numbers and identifiers'),
                                        TextInput::make('project_number_start')
                                            ->label('Project Number Start')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->step(1)
                                            ->placeholder('e.g., 1 or 500')
                                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Starting number for project numbering. E.g., 1 means TCS-001, 500 means TCS-500'),
                                        TextInput::make('registration_number')
                                            ->label(__('security::filament/resources/company.form.sections.company-information.fields.registration-number'))
                                            ->maxLength(255),
                                        TextInput::make('company_id')
                                            ->label(__('security::filament/resources/company.form.sections.company-information.fields.company-id'))
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'The Company ID is a unique identifier for your company.'),
                                        TextInput::make('tax_id')
                                            ->label(__('security::filament/resources/company.form.sections.company-information.fields.tax-id'))
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('security::filament/resources/company.form.sections.company-information.fields.tax-id-tooltip')),
                                        TextInput::make('website')
                                            ->url()
                                            ->prefixIcon('heroicon-o-globe-alt')
                                            ->maxLength(255)
                                            ->label(__('security::filament/resources/company.form.sections.company-information.fields.website'))
                                            ->unique(ignoreRecord: true),
                                    ])
                                    ->columns(2),
                                Section::make(__('security::filament/resources/company.form.sections.address-information.title'))
                                    ->schema([
                                        Group::make()
                                            ->schema([
                                                TextInput::make('street1')
                                                    ->label(__('security::filament/resources/company.form.sections.address-information.fields.street1'))
                                                    ->maxLength(255),
                                                TextInput::make('street2')
                                                    ->label(__('security::filament/resources/company.form.sections.address-information.fields.street2')),
                                                TextInput::make('city')
                                                    ->maxLength(255),
                                                TextInput::make('zip')
                                                    ->live()
                                                    ->label(__('security::filament/resources/company.form.sections.address-information.fields.zipcode'))
                                                    ->maxLength(255),
                                                Select::make('country_id')
                                                    ->label(__('security::filament/resources/company.form.sections.address-information.fields.country'))
                                                    ->relationship(name: 'country', titleAttribute: 'name')
                                                    ->afterStateUpdated(fn (Set $set) => $set('state_id', null))
                                                    ->searchable()
                                                    ->preload()
                                                    ->live(),
                                                Select::make('state_id')
                                                    ->label(__('security::filament/resources/company.form.sections.address-information.fields.state'))
                                                    ->relationship(
                                                        name: 'state',
                                                        titleAttribute: 'name',
                                                        modifyQueryUsing: fn (Get $get, Builder $query) => $query->where('country_id', $get('country_id')),
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->createOptionForm(function (Schema $schema, Get $get, Set $set) {
                                                        return $schema
                                                            ->components([
                                                                TextInput::make('name')
                                                                    ->label(__('security::filament/resources/company.form.sections.address-information.fields.state-name'))
                                                                    ->required(),
                                                                TextInput::make('code')
                                                                    ->label(__('security::filament/resources/company.form.sections.address-information.fields.state-code'))
                                                                    ->required()
                                                                    ->unique('states'),
                                                                Select::make('country_id')
                                                                    ->label(__('security::filament/resources/company.form.sections.address-information.fields.country'))
                                                                    ->relationship('country', 'name')
                                                                    ->searchable()
                                                                    ->preload()
                                                                    ->live()
                                                                    ->default($get('country_id'))
                                                                    ->afterStateUpdated(function (Get $get) use ($set) {
                                                                        $set('country_id', $get('country_id'));
                                                                    }),
                                                            ]);
                                                    })
                                                    ->createOptionUsing(function (array $data): int {
                                                        // Check if state already exists with same name + country_id
                                                        $existing = \Webkul\Support\Models\State::where('name', $data['name'])
                                                            ->where('country_id', $data['country_id'])
                                                            ->first();
                                                        
                                                        if ($existing) {
                                                            return $existing->id;
                                                        }
                                                        
                                                        // Create new state if it doesn't exist
                                                        return \Webkul\Support\Models\State::create($data)->id;
                                                    }),
                                            ])
                                            ->columns(2),
                                    ]),
                                Section::make(__('security::filament/resources/company.form.sections.additional-information.title'))
                                    ->schema([
                                        Select::make('currency_id')
                                            ->relationship('currency', 'full_name')
                                            ->label(__('security::filament/resources/company.form.sections.additional-information.fields.default-currency'))
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->preload()
                                            ->default(Currency::first()?->id)
                                            ->createOptionForm([
                                                Section::make()
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label(__('security::filament/resources/company.form.sections.additional-information.fields.currency-name'))
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->unique('currencies', 'name', ignoreRecord: true),
                                                        TextInput::make('full_name')
                                                            ->label(__('security::filament/resources/company.form.sections.additional-information.fields.currency-full-name'))
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->unique('currencies', 'full_name', ignoreRecord: true),
                                                        TextInput::make('symbol')
                                                            ->label(__('security::filament/resources/company.form.sections.additional-information.fields.currency-symbol'))
                                                            ->maxLength(255)
                                                            ->required(),
                                                        TextInput::make('iso_numeric')
                                                            ->label(__('security::filament/resources/company.form.sections.additional-information.fields.currency-iso-numeric'))
                                                            ->numeric()
                                                            ->required(),
                                                        TextInput::make('decimal_places')
                                                            ->numeric()
                                                            ->label(__('security::filament/resources/company.form.sections.additional-information.fields.currency-decimal-places'))
                                                            ->required()
                                                            ->rules('min:0', 'max:10'),
                                                        TextInput::make('rounding')
                                                            ->numeric()
                                                            ->label(__('security::filament/resources/company.form.sections.additional-information.fields.currency-rounding'))
                                                            ->required(),
                                                        Toggle::make('active')
                                                            ->label(__('security::filament/resources/company.form.sections.additional-information.fields.currency-status'))
                                                            ->default(true),
                                                    ])->columns(2),
                                            ])
                                            ->createOptionAction(
                                                fn (Action $action) => $action
                                                    ->modalHeading(__('security::filament/resources/company.form.sections.additional-information.fields.currency-create'))
                                                    ->modalSubmitActionLabel(__('security::filament/resources/company.form.sections.additional-information.fields.currency-create'))
                                                    ->modalWidth('xl')
                                            ),
                                        DatePicker::make('founded_date')
                                            ->native(false)
                                            ->label(__('security::filament/resources/company.form.sections.additional-information.fields.company-foundation-date')),
                                        TextInput::make('shop_capacity_per_hour')
                                            ->label('Shop Capacity (LF/Hour)')
                                            ->numeric()
                                            ->suffix('LF/hour')
                                            ->step(0.01)
                                            ->helperText('Auto-calculated from daily capacity ÷ working hours')
                                            ->disabled()
                                            ->dehydrated()
                                            ->rules(['nullable', 'numeric', 'min:0']),
                                        TextInput::make('shop_capacity_per_day')
                                            ->label('Shop Capacity (LF/Day)')
                                            ->numeric()
                                            ->suffix('LF/day')
                                            ->step(0.1)
                                            ->helperText('Linear feet production capacity per day (e.g., 13.6)')
                                            ->reactive()
                                            ->rules(['nullable', 'numeric', 'min:0']),
                                        TextInput::make('shop_capacity_per_month')
                                            ->label('Shop Capacity (LF/Month)')
                                            ->numeric()
                                            ->suffix('LF/month')
                                            ->step(0.1)
                                            ->helperText('Auto-calculated from daily capacity × working days/month')
                                            ->disabled()
                                            ->dehydrated()
                                            ->rules(['nullable', 'numeric', 'min:0']),
                                        TextInput::make('working_hours_per_day')
                                            ->label('Working Hours Per Day')
                                            ->numeric()
                                            ->suffix('hours')
                                            ->default(8)
                                            ->step(0.5)
                                            ->helperText('Hours worked per day (default: 8)')
                                            ->rules(['nullable', 'numeric', 'min:0']),
                                        TextInput::make('working_days_per_month')
                                            ->label('Working Days Per Month')
                                            ->numeric()
                                            ->suffix('days')
                                            ->default(17)
                                            ->step(1)
                                            ->helperText('Working days per month (default: 17)')
                                            ->rules(['nullable', 'integer', 'min:0']),
                                        Toggle::make('is_active')
                                            ->label(__('security::filament/resources/company.form.sections.additional-information.fields.status'))
                                            ->default(true),
                                        ...static::getCustomFormFields(),
                                    ])->columns(2),
                            ])
                            ->columnSpan(['lg' => 2]),
                        Group::make()
                            ->schema([
                                Section::make(__('security::filament/resources/company.form.sections.branding.title'))
                                    ->schema([
                                        Group::make()
                                            ->relationship('partner', 'avatar')
                                            ->schema([
                                                FileUpload::make('avatar')
                                                    ->label(__('security::filament/resources/company.form.sections.branding.fields.company-logo'))
                                                    ->image()
                                                    ->directory('company-logos')
                                                    ->visibility('public'),
                                            ]),
                                        ColorPicker::make('color')
                                            ->label(__('security::filament/resources/company.form.sections.branding.fields.color'))
                                            ->hexColor(),
                                    ]),
                                Section::make(__('security::filament/resources/company.form.sections.contact-information.title'))
                                    ->schema([
                                        TextInput::make('phone')
                                            ->label(__('security::filament/resources/company.form.sections.contact-information.fields.phone'))
                                            ->maxLength(255)
                                            ->tel(),
                                        TextInput::make('mobile')
                                            ->label(__('security::filament/resources/company.form.sections.contact-information.fields.mobile'))
                                            ->maxLength(255)
                                            ->tel(),
                                        TextInput::make('email')
                                            ->label(__('security::filament/resources/company.form.sections.contact-information.fields.email'))
                                            ->maxLength(255)
                                            ->email(),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 1]),
                    ])
                    ->columns(3),
            ])
            ->columns(1);
    }

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::mergeCustomTableColumns([
                ImageColumn::make('partner.avatar')
                    ->circular()
                    ->imageSize(50)
                    ->label(__('security::filament/resources/company.table.columns.logo')),
                TextColumn::make('name')
                    ->label(__('security::filament/resources/company.table.columns.company-name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('acronym')
                    ->label(__('security::filament/resources/company.table.columns.acronym'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('branches.name')
                    ->label(__('security::filament/resources/company.table.columns.branches'))
                    ->placeholder('-')
                    ->badge()
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('security::filament/resources/company.table.columns.email'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('city')
                    ->label(__('security::filament/resources/company.table.columns.city'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('country.name')
                    ->label(__('security::filament/resources/company.table.columns.country'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('currency.full_name')
                    ->label(__('security::filament/resources/company.table.columns.currency'))
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->sortable()
                    ->label(__('security::filament/resources/company.table.columns.status'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('security::filament/resources/company.table.columns.created-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('security::filament/resources/company.table.columns.updated-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]))
            ->columnManagerColumns(2)
            ->groups([
                Tables\Grouping\Group::make('name')
                    ->label(__('security::filament/resources/company.table.groups.company-name'))
                    ->collapsible(),
                Tables\Grouping\Group::make('city')
                    ->label(__('security::filament/resources/company.table.groups.city'))
                    ->collapsible(),
                Tables\Grouping\Group::make('country.name')
                    ->label(__('security::filament/resources/company.table.groups.country'))
                    ->collapsible(),
                Tables\Grouping\Group::make('state.name')
                    ->label(__('security::filament/resources/company.table.groups.state'))
                    ->collapsible(),
                Tables\Grouping\Group::make('email')
                    ->label(__('security::filament/resources/company.table.groups.email'))
                    ->collapsible(),
                Tables\Grouping\Group::make('phone')
                    ->label(__('security::filament/resources/company.table.groups.phone'))
                    ->collapsible(),
                Tables\Grouping\Group::make('currency_id')
                    ->label(__('security::filament/resources/company.table.groups.currency'))
                    ->collapsible(),
                Tables\Grouping\Group::make('created_at')
                    ->label(__('security::filament/resources/company.table.groups.created-at'))
                    ->collapsible(),
                Tables\Grouping\Group::make('updated_at')
                    ->label(__('security::filament/resources/company.table.groups.updated-at'))
                    ->date()
                    ->collapsible(),
            ])
            ->filters(static::mergeCustomTableFilters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label(__('security::filament/resources/company.table.filters.status'))
                    ->options(CompanyStatus::options()),
                SelectFilter::make('country')
                    ->label(__('security::filament/resources/company.table.filters.country'))
                    ->multiple()
                    ->options(function () {
                        return Country::pluck('name', 'name');
                    }),
            ]))
            ->filtersFormColumns(2)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title((__('security::filament/resources/company.table.actions.edit.notification.title')))
                                ->body(__('security::filament/resources/company.table.actions.edit.notification.body')),
                        ),
                    DeleteAction::make()
                        ->hidden(fn ($record) => User::where('default_company_id', $record->id)->exists())
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title((__('security::filament/resources/company.table.actions.delete.notification.title')))
                                ->body(__('security::filament/resources/company.table.actions.delete.notification.body')),
                        ),
                    RestoreAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title((__('security::filament/resources/company.table.actions.restore.notification.title')))
                                ->body(__('security::filament/resources/company.table.actions.restore.notification.body')),
                        ),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title((__('security::filament/resources/company.table.bulk-actions.delete.notification.title')))
                                ->body(__('security::filament/resources/company.table.bulk-actions.delete.notification.body')),
                        ),
                    ForceDeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title((__('security::filament/resources/company.table.bulk-actions.force-delete.notification.title')))
                                ->body(__('security::filament/resources/company.table.bulk-actions.force-delete.notification.body')),
                        ),
                    RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title((__('security::filament/resources/company.table.bulk-actions.restore.notification.title')))
                                ->body(__('security::filament/resources/company.table.bulk-actions.restore.notification.body')),
                        ),
                ]),
            ])->modifyQueryUsing(function (Builder $query) {
                $query
                    ->where('creator_id', Auth::user()->id)
                    ->whereNull('parent_id');
            })
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => ! User::where('default_company_id', $record->id)->exists()
            )
            ->reorderable('sort');
    }

    /**
     * Define the infolist schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 3])
                    ->schema([
                        Group::make()
                            ->schema([
                                Section::make(__('security::filament/resources/company.infolist.sections.company-information.title'))
                                    ->schema([
                                        TextEntry::make('name')
                                            ->icon('heroicon-o-building-office')
                                            ->placeholder('—')
                                            ->label(__('security::filament/resources/company.infolist.sections.company-information.entries.name')),
                                        TextEntry::make('acronym')
                                            ->icon('heroicon-o-hashtag')
                                            ->placeholder('—')
                                            ->badge()
                                            ->label(__('security::filament/resources/company.infolist.sections.company-information.entries.acronym')),
                                        TextEntry::make('project_number_start')
                                            ->icon('heroicon-o-queue-list')
                                            ->placeholder('1')
                                            ->label('Project Number Start')
                                            ->numeric(decimalPlaces: 0),
                                        TextEntry::make('registration_number')
                                            ->icon('heroicon-o-document-text')
                                            ->placeholder('—')
                                            ->label(__('security::filament/resources/company.infolist.sections.company-information.entries.registration-number')),
                                        TextEntry::make('company_id')
                                            ->icon('heroicon-o-identification')
                                            ->placeholder('—')
                                            ->label(__('security::filament/resources/company.infolist.sections.company-information.entries.company-id')),
                                        TextEntry::make('tax_id')
                                            ->icon('heroicon-o-currency-dollar')
                                            ->placeholder('—')
                                            ->label(__('security::filament/resources/company.infolist.sections.company-information.entries.tax-id')),
                                        TextEntry::make('website')
                                            ->icon('heroicon-o-globe-alt')
                                            ->placeholder('—')
                                            ->label(__('security::filament/resources/company.infolist.sections.company-information.entries.website')),
                                    ])
                                    ->columns(2),

                                Section::make(__('security::filament/resources/company.infolist.sections.address-information.title'))
                                    ->schema([
                                        TextEntry::make('street1')
                                            ->icon('heroicon-o-map-pin')
                                            ->placeholder('—')
                                            ->label(__('security::filament/resources/company.infolist.sections.address-information.entries.street1')),
                                        TextEntry::make('street2')
                                            ->placeholder('—')
                                            ->label(__('security::filament/resources/company.infolist.sections.address-information.entries.street2')),
                                        TextEntry::make('city')
                                            ->label(__('security::filament/resources/company.infolist.sections.address-information.entries.city'))
                                            ->icon('heroicon-o-building-library')
                                            ->placeholder('—'),
                                        TextEntry::make('zip')
                                            ->placeholder('—')
                                            ->label(__('security::filament/resources/company.infolist.sections.address-information.entries.zipcode')),
                                        TextEntry::make('country.name')
                                            ->icon('heroicon-o-globe-alt')
                                            ->placeholder('—')
                                            ->label(__('security::filament/resources/company.infolist.sections.address-information.entries.country')),
                                        TextEntry::make('state.name')
                                            ->placeholder('—')
                                            ->label(__('security::filament/resources/company.infolist.sections.address-information.entries.state')),
                                    ])
                                    ->columns(2),

                                Section::make(__('security::filament/resources/company.infolist.sections.additional-information.title'))
                                    ->schema([
                                        TextEntry::make('currency.full_name')
                                            ->icon('heroicon-o-currency-dollar')
                                            ->placeholder('—')
                                            ->label(__('security::filament/resources/company.infolist.sections.additional-information.entries.default-currency')),
                                        TextEntry::make('founded_date')
                                            ->icon('heroicon-o-calendar')
                                            ->placeholder('—')
                                            ->date()
                                            ->label(__('security::filament/resources/company.infolist.sections.additional-information.entries.company-foundation-date')),
                                        TextEntry::make('shop_capacity_per_hour')
                                            ->label('Shop Capacity (LF/Hour)')
                                            ->icon('heroicon-o-clock')
                                            ->placeholder('—')
                                            ->suffix(' LF/hour')
                                            ->numeric(decimalPlaces: 2),
                                        TextEntry::make('shop_capacity_per_day')
                                            ->label('Shop Capacity (LF/Day)')
                                            ->icon('heroicon-o-calendar-days')
                                            ->placeholder('—')
                                            ->suffix(' LF/day')
                                            ->numeric(decimalPlaces: 2),
                                        TextEntry::make('shop_capacity_per_month')
                                            ->label('Shop Capacity (LF/Month)')
                                            ->icon('heroicon-o-calendar')
                                            ->placeholder('—')
                                            ->suffix(' LF/month')
                                            ->numeric(decimalPlaces: 2),
                                        TextEntry::make('working_hours_per_day')
                                            ->label('Working Hours Per Day')
                                            ->icon('heroicon-o-clock')
                                            ->placeholder('—')
                                            ->suffix(' hours')
                                            ->numeric(decimalPlaces: 1),
                                        TextEntry::make('working_days_per_month')
                                            ->label('Working Days Per Month')
                                            ->icon('heroicon-o-calendar-days')
                                            ->placeholder('—')
                                            ->suffix(' days')
                                            ->numeric(decimalPlaces: 0),
                                        IconEntry::make('is_active')
                                            ->label(__('security::filament/resources/company.infolist.sections.additional-information.entries.status'))
                                            ->boolean(),
                                        ...static::getCustomInfolistEntries(),
                                    ])
                                    ->columns(2),
                            ])
                            ->columnSpan(2),

                        Group::make([
                            Section::make(__('security::filament/resources/company.infolist.sections.branding.title'))
                                ->schema([
                                    ImageEntry::make('partner.avatar')
                                        ->label(__('security::filament/resources/company.infolist.sections.branding.entries.company-logo'))
                                        ->circular()
                                        ->placeholder('—'),
                                    ColorEntry::make('color')
                                        ->placeholder('—')
                                        ->label(__('security::filament/resources/company.infolist.sections.branding.entries.color')),
                                ]),

                            Section::make(__('security::filament/resources/company.infolist.sections.contact-information.title'))
                                ->schema([
                                    TextEntry::make('phone')
                                        ->icon('heroicon-o-phone')
                                        ->placeholder('—')
                                        ->label(__('security::filament/resources/company.infolist.sections.contact-information.entries.phone')),
                                    TextEntry::make('mobile')
                                        ->icon('heroicon-o-device-phone-mobile')
                                        ->placeholder('—')
                                        ->label(__('security::filament/resources/company.infolist.sections.contact-information.entries.mobile')),
                                    TextEntry::make('email')
                                        ->icon('heroicon-o-envelope')
                                        ->placeholder('—')
                                        ->label(__('security::filament/resources/company.infolist.sections.contact-information.entries.email'))
                                        ->copyable()
                                        ->copyMessage('Email address copied')
                                        ->copyMessageDuration(1500),
                                ]),
                        ])->columnSpan(1),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            BranchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'view'   => ViewCompany::route('/{record}'),
            'edit'   => EditCompany::route('/{record}/edit'),
        ];
    }
}
