<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\PackagingResource\Pages;

use Webkul\Product\Filament\Resources\PackagingResource\Pages\ManagePackagings as BaseManagePackagings;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\PackagingResource;

class ManagePackagings extends BaseManagePackagings
{
    protected static string $resource = PackagingResource::class;
}
