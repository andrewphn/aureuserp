<?php

namespace Webkul\Contact\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Webkul\Partner\Filament\Resources\AddressResource as BaseAddressResource;
use Webkul\Partner\Models\Partner;

class AddressResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return BaseAddressResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return BaseAddressResource::table($table);
    }
}
