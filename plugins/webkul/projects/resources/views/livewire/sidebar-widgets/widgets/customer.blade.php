{{-- Customer Widget --}}
<div class="space-y-2">
    <div class="flex items-center justify-between">
        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</h4>

        {{-- Add/Edit Button - changes based on whether customer is selected --}}
        @if($this->customerName)
            {{-- Edit button when customer is selected --}}
            <button
                type="button"
                wire:click="$dispatch('open-customer-modal', { partnerId: {{ $this->data['partner_id'] ?? 'null' }} })"
                class="p-1 rounded-md text-gray-400 hover:text-primary-500 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                title="Edit customer"
            >
                <x-heroicon-o-pencil-square class="h-4 w-4" />
            </button>
        @else
            {{-- Add button when no customer is selected --}}
            <button
                type="button"
                wire:click="$dispatch('open-customer-modal', { partnerId: null })"
                class="p-1 rounded-md text-gray-400 hover:text-success-500 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                title="Add new customer"
            >
                <x-heroicon-o-plus-circle class="h-4 w-4" />
            </button>
        @endif
    </div>

    @if($this->customerName)
        <div class="flex items-center gap-2">
            <x-heroicon-o-user class="h-4 w-4 text-gray-400 flex-shrink-0" />
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $this->customerName }}</span>
        </div>

        @if($this->customer?->email)
            <div class="flex items-center gap-2 pl-6">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $this->customer->email }}</span>
            </div>
        @endif

        @if($this->customer?->phone)
            <div class="flex items-center gap-2 pl-6">
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $this->customer->phone }}</span>
            </div>
        @endif
    @else
        <p class="text-sm text-gray-400 italic">Not selected</p>
    @endif
</div>
