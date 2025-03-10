<?php

namespace Webkul\Website\Filament\Admin\Resources\PartnerResource\Pages;

use Webkul\Website\Filament\Admin\Resources\PartnerResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ManageAddresses as BaseManageAddresses;

class ManageAddresses extends BaseManageAddresses
{
    protected static string $resource = PartnerResource::class;
}
