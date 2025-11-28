<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\PackagingResource\Pages;

use Webkul\Inventory\Filament\Clusters\Configurations\Resources\PackagingResource;
use Webkul\Product\Filament\Resources\PackagingResource\Pages\ManagePackagings as BaseManagePackagings;

/**
 * Manage Packagings class
 *
 * @see \Filament\Resources\Resource
 */
class ManagePackagings extends BaseManagePackagings
{
    protected static string $resource = PackagingResource::class;
}
