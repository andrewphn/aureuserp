<?php

namespace Webkul\Website\Filament\Admin\Resources\PartnerResource\Pages;

use Webkul\Website\Filament\Admin\Resources\PartnerResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ManageContacts as BaseManageContacts;

class ManageContacts extends BaseManageContacts
{
    protected static string $resource = PartnerResource::class;
}
