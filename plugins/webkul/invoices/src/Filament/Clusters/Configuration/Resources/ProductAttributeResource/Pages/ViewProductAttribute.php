<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductAttributeResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductAttributeResource;
use Webkul\Product\Filament\Resources\AttributeResource\Pages\EditAttribute;

class ViewProductAttribute extends EditAttribute
{
    protected static string $resource = ProductAttributeResource::class;
}
