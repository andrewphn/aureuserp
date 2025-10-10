<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Sale\Filament\Clusters\Configuration;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\QuotationTemplateResource\Pages\CreateQuotationTemplate;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\QuotationTemplateResource\Pages\EditQuotationTemplate;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\QuotationTemplateResource\Pages\ListQuotationTemplates;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\QuotationTemplateResource\Pages\ViewQuotationTemplate;
use Webkul\Sale\Models\OrderTemplate;

class QuotationTemplateResource extends Resource
{
    protected static ?string $model = OrderTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $cluster = Configuration::class;

    protected static bool $shouldRegisterNavigation = true;

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function getModelLabel(): string
    {
        return __('sales::filament/clusters/configurations/resources/quotation-template.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/configurations/resources/quotation-template.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('sales::filament/clusters/configurations/resources/quotation-template.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.form.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('Template Name')),
                TextInput::make('number_of_days')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.form.fields.number_of_days'))
                    ->numeric()
                    ->default(0)
                    ->helperText(__('Number of days the quotation remains valid')),
                Select::make('journal_id')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.form.fields.journal'))
                    ->relationship('journal', 'name')
                    ->searchable()
                    ->preload(),
                Textarea::make('note')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.form.fields.note'))
                    ->rows(3)
                    ->columnSpanFull(),
                Checkbox::make('is_active')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.form.fields.is_active'))
                    ->default(true),
                Checkbox::make('require_signature')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.form.fields.require_signature'))
                    ->default(false),
                Checkbox::make('require_payment')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.form.fields.require_payment'))
                    ->default(false),
                TextInput::make('prepayment_percentage')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.form.fields.prepayment_percentage'))
                    ->numeric()
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(0)
                    ->visible(fn ($get) => $get('require_payment')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.table.columns.name')),
                TextColumn::make('number_of_days')
                    ->sortable()
                    ->suffix(' days')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.table.columns.number_of_days')),
                TextColumn::make('journal.name')
                    ->searchable()
                    ->sortable()
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.table.columns.journal')),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.table.columns.is_active')),
                IconColumn::make('require_signature')
                    ->boolean()
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.table.columns.require_signature')),
                IconColumn::make('require_payment')
                    ->boolean()
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.table.columns.require_payment')),
                TextColumn::make('createdBy.name')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.table.columns.created-by')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('sales::filament/clusters/configurations/resources/quotation-template.table.actions.edit.notification.title'))
                            ->body(__('sales::filament/clusters/configurations/resources/quotation-template.table.actions.edit.notification.body'))
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('sales::filament/clusters/configurations/resources/quotation-template.table.actions.delete.notification.title'))
                            ->body(__('sales::filament/clusters/configurations/resources/quotation-template.table.actions.delete.notification.body'))
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('sales::filament/clusters/configurations/resources/quotation-template.table.bulk-actions.delete.notification.title'))
                                ->body(__('sales::filament/clusters/configurations/resources/quotation-template.table.bulk-actions.delete.notification.body'))
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListQuotationTemplates::route('/'),
            'create' => CreateQuotationTemplate::route('/create'),
            'view'   => ViewQuotationTemplate::route('/{record}'),
            'edit'   => EditQuotationTemplate::route('/{record}/edit'),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.infolist.entries.name')),
                TextEntry::make('number_of_days')
                    ->suffix(' days')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.infolist.entries.number_of_days')),
                TextEntry::make('journal.name')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.infolist.entries.journal')),
                TextEntry::make('note')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.infolist.entries.note'))
                    ->placeholder('-'),
                TextEntry::make('is_active')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.infolist.entries.is_active'))
                    ->formatStateUsing(fn ($state) => $state ? __('Active') : __('Inactive')),
                TextEntry::make('require_signature')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.infolist.entries.require_signature'))
                    ->formatStateUsing(fn ($state) => $state ? __('Yes') : __('No')),
                TextEntry::make('require_payment')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.infolist.entries.require_payment'))
                    ->formatStateUsing(fn ($state) => $state ? __('Yes') : __('No')),
                TextEntry::make('prepayment_percentage')
                    ->suffix('%')
                    ->label(__('sales::filament/clusters/configurations/resources/quotation-template.infolist.entries.prepayment_percentage'))
                    ->visible(fn ($record) => $record->require_payment),
            ]);
    }
}
