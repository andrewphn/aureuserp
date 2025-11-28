<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\IncoTermResource\Pages;

use Webkul\Account\Filament\Resources\IncoTermResource\Pages\ListIncoTerms as BaseListIncoTerms;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\IncoTermResource;

/**
 * List Inco Terms class
 *
 * @see \Filament\Resources\Resource
 */
class ListIncoTerms extends BaseListIncoTerms
{
    protected static string $resource = IncoTermResource::class;
}
