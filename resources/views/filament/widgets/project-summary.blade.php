<x-filament-widgets::widget>
    <x-filament::section
        collapsible
        :collapsed="true"
    >
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="font-semibold">Project Summary</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Quick preview of your project details (updates live as you fill the form)
        </x-slot>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
            {{-- Project Number --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    Project Number
                </div>
                <div class="font-mono text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ $this->getProjectNumber() }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Assigned on save
                </div>
            </div>

            {{-- Location --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    📍 Location
                </div>
                <div class="text-sm text-gray-900 dark:text-gray-100">
                    {!! $this->getLocationSummary() !!}
                </div>
            </div>

            {{-- Customer --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    👤 Customer
                </div>
                <div class="text-sm text-gray-900 dark:text-gray-100">
                    {{ $this->getCustomerName() }}
                </div>
            </div>

            {{-- Company --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    🏢 Company
                </div>
                <div class="text-sm text-gray-900 dark:text-gray-100">
                    {{ $this->getCompanyName() }}
                </div>
            </div>

            {{-- Project Type --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    🏷️ Type
                </div>
                <div class="text-sm text-gray-900 dark:text-gray-100">
                    {{ $this->getProjectType() }}
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
