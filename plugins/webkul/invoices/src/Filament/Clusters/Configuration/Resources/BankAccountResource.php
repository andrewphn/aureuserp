<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources;

use Webkul\Invoice\Filament\Clusters\Configuration;
use Webkul\Account\Filament\Resources\BankAccountResource as BaseBankAccountResource;

class BankAccountResource extends BaseBankAccountResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Configuration::class;
}
