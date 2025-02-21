<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\JournalResource\Pages;

use Webkul\Account\Filament\Clusters\Configuration\Resources\JournalResource\Pages\CreateJournal as BaseCreateJournal;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\JournalResource;

class CreateJournal extends BaseCreateJournal
{
    protected static string $resource = JournalResource::class;
}
