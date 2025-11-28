<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources\TagResource\Pages;

use Webkul\Contact\Filament\Clusters\Configurations\Resources\TagResource;
use Webkul\Partner\Filament\Resources\TagResource\Pages\ManageTags as BaseManageTags;

/**
 * Manage Tags class
 *
 * @see \Filament\Resources\Resource
 */
class ManageTags extends BaseManageTags
{
    protected static string $resource = TagResource::class;
}
