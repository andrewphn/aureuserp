<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\JournalResource\Pages;

use Webkul\Account\Filament\Resources\JournalResource\Pages\ViewJournal as BaseViewJournal;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\JournalResource;

class ViewJournal extends BaseViewJournal
{
    protected static string $resource = JournalResource::class;
}
