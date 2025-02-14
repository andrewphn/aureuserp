<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources;

use Webkul\Invoice\Filament\Clusters\Configuration;
use Webkul\Account\Filament\Clusters\Configuration\Resources\JournalResource as BaseJournalResource;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\JournalResource\Pages;

class JournalResource extends BaseJournalResource
{
    protected static ?string $cluster = Configuration::class;

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
