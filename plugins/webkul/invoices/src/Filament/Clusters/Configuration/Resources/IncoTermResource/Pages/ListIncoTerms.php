<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\IncoTermResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\IncoTermResource;
use Webkul\Account\Filament\Resources\IncoTermResource\Pages\ListIncoTerms as BaseListIncoTerms;

class ListIncoTerms extends BaseListIncoTerms
{
    protected static string $resource = IncoTermResource::class;
}
