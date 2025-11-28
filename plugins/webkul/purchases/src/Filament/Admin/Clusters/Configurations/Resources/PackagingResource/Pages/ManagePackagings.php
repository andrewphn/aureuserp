<?php

namespace Webkul\Purchase\Filament\Admin\Clusters\Configurations\Resources\PackagingResource\Pages;

use Webkul\Product\Filament\Resources\PackagingResource\Pages\ManagePackagings as BaseManagePackagings;
use Webkul\Purchase\Filament\Admin\Clusters\Configurations\Resources\PackagingResource;

/**
 * Manage Packagings class
 *
 * @see \Filament\Resources\Resource
 */
class ManagePackagings extends BaseManagePackagings
{
    protected static string $resource = PackagingResource::class;
}
