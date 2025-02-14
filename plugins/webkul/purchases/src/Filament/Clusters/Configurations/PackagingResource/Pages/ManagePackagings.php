<?php

namespace Webkul\Purchase\Filament\Clusters\Configurations\Resources\PackagingResource\Pages;

use Webkul\Product\Filament\Resources\PackagingResource\Pages\ManagePackagings as BaseManagePackagings;
use Webkul\Purchase\Filament\Clusters\Configurations\Resources\PackagingResource;

class ManagePackagings extends BaseManagePackagings
{
    protected static string $resource = PackagingResource::class;
}
