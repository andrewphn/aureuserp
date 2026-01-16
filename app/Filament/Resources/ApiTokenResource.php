<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiTokenResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * API Token Resource for managing Sanctum tokens
 *
 * Allows admins to create and manage API tokens for n8n and external integrations.
 */
class ApiTokenResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'API Tokens';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    protected static ?string $modelLabel = 'API Token';

    protected static ?string $pluralModelLabel = 'API Tokens';

    /**
     * Available token abilities/scopes
     */
    public static function getAvailableAbilities(): array
    {
        return [
            // Projects module
            'projects:read' => 'Read projects',
            'projects:write' => 'Create/update projects',
            'projects:delete' => 'Delete projects',
            'rooms:read' => 'Read rooms',
            'rooms:write' => 'Create/update rooms',
            'rooms:delete' => 'Delete rooms',
            'cabinets:read' => 'Read cabinets',
            'cabinets:write' => 'Create/update cabinets',
            'cabinets:delete' => 'Delete cabinets',
            'cabinet-runs:read' => 'Read cabinet runs',
            'cabinet-runs:write' => 'Create/update cabinet runs',
            'cabinet-runs:delete' => 'Delete cabinet runs',
            'drawers:read' => 'Read drawers',
            'drawers:write' => 'Create/update drawers',
            'drawers:delete' => 'Delete drawers',
            'doors:read' => 'Read doors',
            'doors:write' => 'Create/update doors',
            'doors:delete' => 'Delete doors',
            'tasks:read' => 'Read tasks',
            'tasks:write' => 'Create/update tasks',
            'tasks:delete' => 'Delete tasks',
            'milestones:read' => 'Read milestones',
            'milestones:write' => 'Create/update milestones',
            'milestones:delete' => 'Delete milestones',

            // Employees module
            'employees:read' => 'Read employees',
            'employees:write' => 'Create/update employees',
            'employees:delete' => 'Delete employees',
            'departments:read' => 'Read departments',
            'departments:write' => 'Create/update departments',

            // Inventory module
            'products:read' => 'Read products',
            'products:write' => 'Create/update products',
            'products:delete' => 'Delete products',
            'warehouses:read' => 'Read warehouses',
            'warehouses:write' => 'Create/update warehouses',
            'inventory-moves:read' => 'Read inventory moves',
            'inventory-moves:write' => 'Create inventory moves',

            // Partners module
            'partners:read' => 'Read partners/customers',
            'partners:write' => 'Create/update partners',
            'partners:delete' => 'Delete partners',

            // Webhooks
            'webhooks:manage' => 'Manage webhook subscriptions',

            // Full access
            '*' => 'Full access (all permissions)',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Token Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., n8n-integration, mobile-app')
                            ->helperText('A descriptive name to identify this token'),

                        Forms\Components\Select::make('tokenable_id')
                            ->label('User')
                            ->relationship('tokenable', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => Auth::id())
                            ->required()
                            ->helperText('The user this token belongs to'),

                        Forms\Components\CheckboxList::make('abilities')
                            ->label('Permissions')
                            ->options(static::getAvailableAbilities())
                            ->columns(3)
                            ->required()
                            ->helperText('Select the permissions for this token. Use "Full access" for complete API access.')
                            ->default(['*']),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expiration')
                            ->nullable()
                            ->helperText('Leave empty for no expiration'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Token Details')
                    ->schema([
                        Forms\Components\Placeholder::make('token_info')
                            ->label('')
                            ->content('The token will be displayed after creation. Make sure to copy it - you won\'t be able to see it again!')
                            ->visible(fn ($record) => $record === null),

                        Forms\Components\Placeholder::make('created_at_display')
                            ->label('Created')
                            ->content(fn ($record) => $record?->created_at?->diffForHumans())
                            ->visible(fn ($record) => $record !== null),

                        Forms\Components\Placeholder::make('last_used_at_display')
                            ->label('Last Used')
                            ->content(fn ($record) => $record?->last_used_at?->diffForHumans() ?? 'Never')
                            ->visible(fn ($record) => $record !== null),
                    ])
                    ->visible(fn ($record) => $record !== null || true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tokenable.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('abilities')
                    ->label('Permissions')
                    ->badge()
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' permissions' : $state)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never')
                    ->color(fn ($record) => $record->expires_at && $record->expires_at->isPast() ? 'danger' : null),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tokenable_id')
                    ->label('User')
                    ->relationship('tokenable', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('expired')
                    ->label('Status')
                    ->placeholder('All tokens')
                    ->trueLabel('Expired')
                    ->falseLabel('Active')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('expires_at')->where('expires_at', '<', now()),
                        false: fn ($query) => $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now())),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Revoke')
                    ->modalHeading('Revoke API Token')
                    ->modalDescription('Are you sure you want to revoke this token? Any applications using it will immediately lose access.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Revoke Selected'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiTokens::route('/'),
            'create' => Pages\CreateApiToken::route('/create'),
            'view' => Pages\ViewApiToken::route('/{record}'),
        ];
    }
}
