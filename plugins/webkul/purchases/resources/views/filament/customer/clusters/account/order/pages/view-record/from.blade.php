<?php $record = $this->getRecord() ?>

<div class="flex items-center justify-between gap-x-3">
    <dt class="fi-in-entry-wrp-label inline-flex items-center gap-x-3">
        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
            {{ __('purchases::filament/customer/clusters/account/resources/order.infolist.general.entries.from') }}
        </span>
    </dt>
</div>

<div class="">
    <p class="mt-2 text-sm leading-6 text-gray-950 dark:text-white">
        {{ $record->company->name }}

        @if ($record->company->address)
            ({{ $record->company->address->city }})
        @endif
    </p>

    @if ($record->company->address)
        <p class="mt-2 text-sm leading-6 text-gray-950 dark:text-white">
            {{ $record->company->address->street1 }}

            @if ($record->company->address->street2)
                ,{{ $record->company->address->street2 }}
            @endif
        </p>
        
        <p class="text-sm leading-6 text-gray-950 dark:text-white">
            {{ $record->company->address->city }},

            @if ($record->company->address->state)
                {{ $record->company->address->state->name }},
            @endif
            
            {{ $record->company->address->zip }}
        </p>
        
        @if ($record->company->address->country)
            <p class="text-sm leading-6 text-gray-950 dark:text-white">
                {{ $record->company->address->country->name }}
            </p>
        @endif
    @endif
</div>