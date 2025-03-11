<?php

namespace Webkul\Purchase\Livewire\Customer;

use Webkul\Purchase\Models\OrderLine;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Contracts\HasForms;  
use Filament\Tables\Table;
use Livewire\Component;

class ListProducts extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;
    
    public $recordId;
    
    public function mount($recordId)
    {
        $this->recordId = $recordId;
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderLine::query()->where('order_id', $this->recordId)
            )
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('product_qty')
                    ->formatStateUsing(fn (string $state): string => $state . ' Units'),
                TextColumn::make('price_unit')
                    ->money(fn (OrderLine $record) => $record->currency->code),
                TextColumn::make('taxes.name')
                    ->badge(),
                TextColumn::make('discount')
                    ->suffix('%'),
                TextColumn::make('price_subtotal')
                    ->money(fn (OrderLine $record) => $record->currency->code),
            ])
            ->paginated(false);
    }
    
    public function render()
    {
        return view('purchases::livewire.customer.products');
    }
}