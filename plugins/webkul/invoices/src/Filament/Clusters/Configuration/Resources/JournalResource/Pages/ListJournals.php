<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\JournalResource\Pages;

use Webkul\Account\Filament\Clusters\Configuration\Resources\JournalResource\Pages\ListJournals as BaseListJournals;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\JournalResource;

class ListJournals extends BaseListJournals
{
    protected static string $resource = JournalResource::class;
}
