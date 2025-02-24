<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources;

use Webkul\Account\Filament\Resources\BankAccountResource as BaseBankAccountResource;
use Webkul\Invoice\Filament\Clusters\Configuration;

class BankAccountResource extends BaseBankAccountResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Configuration::class;
}
