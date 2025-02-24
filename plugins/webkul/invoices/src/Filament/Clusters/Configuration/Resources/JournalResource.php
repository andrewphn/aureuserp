<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources;

use Webkul\Account\Filament\Resources\JournalResource as BaseJournalResource;
use Webkul\Invoice\Filament\Clusters\Configuration;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\JournalResource\Pages;

class JournalResource extends BaseJournalResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Configuration::class;

    public static function getModelLabel(): string
    {
        return __('invoices::filament/clusters/configurations/resources/journal.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/clusters/configurations/resources/journal.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('invoices::filament/clusters/configurations/resources/journal.navigation.group');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'view'   => Pages\ViewJournal::route('/{record}'),
            'edit'   => Pages\EditJournal::route('/{record}/edit'),
        ];
    }
}
