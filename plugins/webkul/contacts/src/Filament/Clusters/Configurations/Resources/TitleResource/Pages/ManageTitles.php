<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources\TitleResource\Pages;

use Webkul\Contact\Filament\Clusters\Configurations\Resources\TitleResource;
use Webkul\Partner\Filament\Resources\TitleResource\Pages\ManageTitles as BaseManageTitles;

/**
 * Manage Titles class
 *
 * @see \Filament\Resources\Resource
 */
class ManageTitles extends BaseManageTitles
{
    protected static string $resource = TitleResource::class;
}
