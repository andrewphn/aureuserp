<?php

namespace Webkul\Contact\Filament\Resources\PartnerResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Contact\Filament\Resources\PartnerResource;
use Webkul\Partner\Filament\Resources\PartnerResource\Pages\ViewPartner as BaseViewPartner;

class ViewPartner extends BaseViewPartner
{
    protected static string $resource = PartnerResource::class;
}
