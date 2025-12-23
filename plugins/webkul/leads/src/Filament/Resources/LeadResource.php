<?php

namespace Webkul\Lead\Filament\Resources;

use BackedEnum;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use Webkul\Lead\Enums\LeadSource;
use Webkul\Lead\Enums\LeadStatus;
use Webkul\Lead\Filament\Resources\LeadResource\Pages;
use Webkul\Lead\Models\Lead;
use Webkul\Security\Models\User;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::inbox()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->label('First Name')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('last_name')
                                    ->label('Last Name')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(20),

                                Forms\Components\TextInput::make('company_name')
                                    ->label('Company')
                                    ->maxLength(255),

                                Forms\Components\Select::make('preferred_contact_method')
                                    ->label('Preferred Contact Method')
                                    ->options([
                                        'email' => 'Email',
                                        'phone' => 'Phone',
                                        'text' => 'Text Message',
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Lead Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options(LeadStatus::class)
                                    ->default(LeadStatus::NEW)
                                    ->required(),

                                Forms\Components\Select::make('source')
                                    ->label('Source')
                                    ->options(LeadSource::class),

                                Forms\Components\Select::make('assigned_user_id')
                                    ->label('Assigned To')
                                    ->relationship('assignedUser', 'name')
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\TextInput::make('disqualification_reason')
                                    ->label('Disqualification Reason')
                                    ->maxLength(255)
                                    ->visible(fn ($get) => $get('status') === LeadStatus::DISQUALIFIED->value),
                            ]),

                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Project Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('project_type')
                                    ->label('Project Type')
                                    ->maxLength(255),

                                Forms\Components\Select::make('budget_range')
                                    ->label('Budget Range')
                                    ->options([
                                        'under_10k' => 'Under $10,000',
                                        '10k_25k' => '$10,000 - $25,000',
                                        '25k_50k' => '$25,000 - $50,000',
                                        '50k_100k' => '$50,000 - $100,000',
                                        'over_100k' => 'Over $100,000',
                                        'unsure' => 'Not Sure',
                                    ]),

                                Forms\Components\TextInput::make('timeline')
                                    ->label('Timeline')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('design_style')
                                    ->label('Design Style')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('wood_species')
                                    ->label('Wood Species')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Textarea::make('project_description')
                            ->label('Project Description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Address')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('street1')
                                    ->label('Street Address')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('street2')
                                    ->label('Street Address 2')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('city')
                                    ->label('City')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('state')
                                    ->label('State')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('zip')
                                    ->label('ZIP Code')
                                    ->maxLength(20),

                                Forms\Components\TextInput::make('country')
                                    ->label('Country')
                                    ->maxLength(255)
                                    ->default('United States'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('CRM Integration')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('hubspot_contact_id')
                                    ->label('HubSpot Contact ID')
                                    ->disabled(),

                                Forms\Components\TextInput::make('hubspot_deal_id')
                                    ->label('HubSpot Deal ID')
                                    ->disabled(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record && ($record->hubspot_contact_id || $record->hubspot_deal_id)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name']),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge(),

                Tables\Columns\TextColumn::make('project_type')
                    ->label('Project Type')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('budget_range')
                    ->label('Budget')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'under_10k' => 'Under $10k',
                        '10k_25k' => '$10k-$25k',
                        '25k_50k' => '$25k-$50k',
                        '50k_100k' => '$50k-$100k',
                        'over_100k' => 'Over $100k',
                        'unsure' => 'Not Sure',
                        default => $state,
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(LeadStatus::class)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('source')
                    ->options(LeadSource::class)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('assigned_user_id')
                    ->label('Assigned To')
                    ->relationship('assignedUser', 'name'),

                Tables\Filters\Filter::make('unassigned')
                    ->label('Unassigned')
                    ->query(fn (Builder $query) => $query->whereNull('assigned_user_id')),

                Tables\Filters\Filter::make('new_today')
                    ->label('New Today')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Forms\Components\Select::make('assigned_user_id')
                            ->label('Assign To')
                            ->options(User::pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (Lead $record, array $data) {
                        $record->update(['assigned_user_id' => $data['assigned_user_id']]);
                    })
                    ->visible(fn (Lead $record) => ! $record->is_converted),

                Tables\Actions\Action::make('convert')
                    ->label('Convert to Project')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Convert Lead to Project')
                    ->modalDescription('This will create a Partner and Project from this lead. The lead will be marked as converted.')
                    ->action(function (Lead $record) {
                        // This will be handled by LeadConversionService
                        app(\Webkul\Lead\Services\LeadConversionService::class)->convert($record);
                    })
                    ->visible(fn (Lead $record) => $record->canConvert()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('bulk_assign')
                        ->label('Assign to User')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Forms\Components\Select::make('assigned_user_id')
                                ->label('Assign To')
                                ->options(User::pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(fn ($record) => $record->update([
                                'assigned_user_id' => $data['assigned_user_id'],
                            ]));
                        }),

                    Tables\Actions\BulkAction::make('bulk_status')
                        ->label('Change Status')
                        ->icon('heroicon-o-tag')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options(LeadStatus::class)
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(fn ($record) => $record->update([
                                'status' => $data['status'],
                            ]));
                        }),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Infolists\Components\Section::make('Contact Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('full_name')
                                    ->label('Name'),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('phone')
                                    ->label('Phone')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('company_name')
                                    ->label('Company'),

                                Infolists\Components\TextEntry::make('preferred_contact_method')
                                    ->label('Preferred Contact'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Lead Details')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('source')
                                    ->label('Source')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('assignedUser.name')
                                    ->label('Assigned To'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Submitted')
                                    ->dateTime(),

                                Infolists\Components\TextEntry::make('days_since_submission')
                                    ->label('Days Since Submission')
                                    ->suffix(' days'),
                            ]),

                        Infolists\Components\TextEntry::make('message')
                            ->label('Message')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Project Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('project_type')
                                    ->label('Project Type'),

                                Infolists\Components\TextEntry::make('budget_range')
                                    ->label('Budget Range'),

                                Infolists\Components\TextEntry::make('timeline')
                                    ->label('Timeline'),

                                Infolists\Components\TextEntry::make('design_style')
                                    ->label('Design Style'),

                                Infolists\Components\TextEntry::make('wood_species')
                                    ->label('Wood Species'),
                            ]),

                        Infolists\Components\TextEntry::make('project_description')
                            ->label('Project Description')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Address')
                    ->schema([
                        Infolists\Components\TextEntry::make('street1')
                            ->label('Street'),

                        Infolists\Components\TextEntry::make('city')
                            ->label('City'),

                        Infolists\Components\TextEntry::make('state')
                            ->label('State'),

                        Infolists\Components\TextEntry::make('zip')
                            ->label('ZIP'),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
