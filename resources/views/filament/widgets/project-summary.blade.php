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

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
            {{-- Company --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    Company
                </div>
                <div class="text-sm text-gray-900 dark:text-gray-100">
                    {{ $this->getCompanyName() }}
                </div>
            </div>

            {{-- Customer --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    Customer
                </div>
                <div class="text-sm text-gray-900 dark:text-gray-100">
                    {{ $this->getCustomerName() }}
                </div>
            </div>

            {{-- Project Type --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    Type
                </div>
                <div class="text-sm text-gray-900 dark:text-gray-100">
                    {{ $this->getProjectType() }}
                </div>
            </div>

            {{-- Linear Feet --}}
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    Linear Feet
                </div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ $this->getLinearFeet() }} LF
                </div>
            </div>

            {{-- Production Estimate --}}
            @php
                $estimate = $this->getProductionEstimate();
            @endphp
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    Est. Production
                </div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    @if($estimate)
                        {{ $estimate['estimatedWeeks'] }} weeks
                    @else
                        —
                    @endif
                </div>
            </div>

            {{-- Actions placeholder --}}
            <div class="flex items-end justify-end">
                <div class="text-xs text-gray-400">
                    Actions appear here →
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
