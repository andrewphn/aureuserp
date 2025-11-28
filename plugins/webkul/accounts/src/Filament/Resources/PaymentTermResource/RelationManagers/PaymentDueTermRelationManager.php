<?php

namespace Webkul\Account\Filament\Resources\PaymentTermResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Webkul\Account\Traits\PaymentDueTerm;

/**
 * Payment Due Term Relation Manager class
 *
 * @see \Filament\Resources\Resource
 */
class PaymentDueTermRelationManager extends RelationManager
{
    use PaymentDueTerm;

    protected static string $relationship = 'dueTerm';

    protected static ?string $title = 'Due Terms';
}
