<?php

namespace Webkul\Website\Filament\Admin\Resources\PartnerResource\Pages;

use Webkul\Website\Filament\Admin\Resources\PartnerResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\CreatePartner as BaseCreatePartner;

class CreatePartner extends BaseCreatePartner
{
    protected static string $resource = PartnerResource::class;
}
