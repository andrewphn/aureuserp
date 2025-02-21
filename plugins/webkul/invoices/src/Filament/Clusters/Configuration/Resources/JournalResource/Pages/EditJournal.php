<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\JournalResource\Pages;

use Webkul\Account\Filament\Clusters\Configuration\Resources\JournalResource\Pages\EditJournal as BaseEditJournal;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\JournalResource;

class EditJournal extends BaseEditJournal
{
    protected static string $resource = JournalResource::class;
}
