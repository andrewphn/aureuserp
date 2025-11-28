<?php

namespace Webkul\Contact\Filament\Resources\PartnerResource\Pages;

use Webkul\Contact\Filament\Resources\PartnerResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ManageContacts as BaseManageContacts;

/**
 * Manage Contacts class
 *
 * @see \Filament\Resources\Resource
 */
class ManageContacts extends BaseManageContacts
{
    protected static string $resource = PartnerResource::class;
}
