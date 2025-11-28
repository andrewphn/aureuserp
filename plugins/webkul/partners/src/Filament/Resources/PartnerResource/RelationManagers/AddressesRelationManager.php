<?php

namespace Webkul\Partner\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Partner\Filament\Resources\AddressResource;

/**
 * Addresses Relation Manager class
 *
 * @see \Filament\Resources\Resource
 */
class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    /**
     * Define the form schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public function form(Schema $schema): Schema
    {
        return AddressResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return AddressResource::table($table);
    }
}
