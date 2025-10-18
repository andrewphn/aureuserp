<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FooterTemplateResource\Pages;
use App\Models\FooterTemplate;
use App\Services\FooterFieldRegistry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class FooterTemplateResource extends Resource
{
    protected static ?string $model = FooterTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Footer Templates';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        $fieldRegistry = app(FooterFieldRegistry::class);

        return $form
            ->schema([
                Forms\Components\Section::make('Template Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state)))
                            ->placeholder('e.g., Owner, Project Manager, Sales'),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Auto-generated from name'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Brief description of this template'),

                        Forms\Components\Select::make('icon')
                            ->options([
                                'heroicon-o-star' => 'Star',
                                'heroicon-o-briefcase' => 'Briefcase',
                                'heroicon-o-shopping-cart' => 'Shopping Cart',
                                'heroicon-o-cube' => 'Cube',
                                'heroicon-o-cog' => 'Cog',
                                'heroicon-o-user-circle' => 'User Circle',
                                'heroicon-o-clipboard-document-list' => 'Clipboard',
                            ])
                            ->default('heroicon-o-adjustments-horizontal'),

                        Forms\Components\Select::make('color')
                            ->options([
                                'gray' => 'Gray',
                                'amber' => 'Amber',
                                'blue' => 'Blue',
                                'green' => 'Green',
                                'purple' => 'Purple',
                                'red' => 'Red',
                            ])
                            ->default('gray'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\Toggle::make('is_system')
                            ->label('System Template')
                            ->helperText('System templates cannot be deleted')
                            ->default(false)
                            ->visible(fn () => auth()->user()->hasRole('admin')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Project Context')
                    ->description('Configure fields for project pages')
                    ->schema([
                        Forms\Components\CheckboxList::make('contexts.project.minimized_fields')
                            ->label('Minimized View Fields')
                            ->options(self::getFieldOptions($fieldRegistry, 'project'))
                            ->columns(2)
                            ->gridDirection('row')
                            ->bulkToggleable(),

                        Forms\Components\CheckboxList::make('contexts.project.expanded_fields')
                            ->label('Expanded View Fields')
                            ->options(self::getFieldOptions($fieldRegistry, 'project'))
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable(),
                    ]),

                Forms\Components\Section::make('Sales Context')
                    ->description('Configure fields for sales pages')
                    ->schema([
                        Forms\Components\CheckboxList::make('contexts.sale.minimized_fields')
                            ->label('Minimized View Fields')
                            ->options(self::getFieldOptions($fieldRegistry, 'sale'))
                            ->columns(2)
                            ->gridDirection('row')
                            ->bulkToggleable(),

                        Forms\Components\CheckboxList::make('contexts.sale.expanded_fields')
                            ->label('Expanded View Fields')
                            ->options(self::getFieldOptions($fieldRegistry, 'sale'))
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable(),
                    ]),

                Forms\Components\Section::make('Inventory Context')
                    ->description('Configure fields for inventory pages')
                    ->schema([
                        Forms\Components\CheckboxList::make('contexts.inventory.minimized_fields')
                            ->label('Minimized View Fields')
                            ->options(self::getFieldOptions($fieldRegistry, 'inventory'))
                            ->columns(2)
                            ->gridDirection('row')
                            ->bulkToggleable(),

                        Forms\Components\CheckboxList::make('contexts.inventory.expanded_fields')
                            ->label('Expanded View Fields')
                            ->options(self::getFieldOptions($fieldRegistry, 'inventory'))
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable(),
                    ]),

                Forms\Components\Section::make('Production Context')
                    ->description('Configure fields for production pages')
                    ->schema([
                        Forms\Components\CheckboxList::make('contexts.production.minimized_fields')
                            ->label('Minimized View Fields')
                            ->options(self::getFieldOptions($fieldRegistry, 'production'))
                            ->columns(2)
                            ->gridDirection('row')
                            ->bulkToggleable(),

                        Forms\Components\CheckboxList::make('contexts.production.expanded_fields')
                            ->label('Expanded View Fields')
                            ->options(self::getFieldOptions($fieldRegistry, 'production'))
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable(),
                    ]),
            ]);
    }

    protected static function getFieldOptions(FooterFieldRegistry $registry, string $contextType): array
    {
        $fields = $registry->getAvailableFields($contextType);

        return collect($fields)->mapWithKeys(function ($field, $key) {
            return [$key => $field['label'] . ' (' . $field['type'] . ')'];
        })->toArray();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\IconColumn::make('icon')
                    ->icon(fn ($record) => $record->icon),

                Tables\Columns\BadgeColumn::make('color')
                    ->colors([
                        'gray' => 'gray',
                        'amber' => 'amber',
                        'blue' => 'blue',
                        'green' => 'green',
                        'purple' => 'purple',
                        'red' => 'red',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_system')
                    ->label('System')
                    ->boolean(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Only')
                    ->placeholder('All templates')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\TernaryFilter::make('is_system')
                    ->label('System Templates')
                    ->placeholder('All templates')
                    ->trueLabel('System only')
                    ->falseLabel('User-created only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->is_system),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->reject(fn ($record) => $record->is_system)->each->delete();
                        }),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFooterTemplates::route('/'),
            'create' => Pages\CreateFooterTemplate::route('/create'),
            'edit' => Pages\EditFooterTemplate::route('/{record}/edit'),
        ];
    }
}
