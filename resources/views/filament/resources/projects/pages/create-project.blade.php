<x-filament-panels::page>
    {{-- Mobile Summary (collapsed by default) --}}
    <div class="lg:hidden mb-6">
        <details class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <summary class="px-4 py-3 cursor-pointer font-medium text-gray-900 dark:text-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Project Summary
                </div>
                <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </summary>
            <div class="px-4 pb-4 pt-2 space-y-3 text-sm border-t border-gray-200 dark:border-gray-700">
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Project Number</div>
                    <div class="font-mono text-sm font-semibold">{{ $this->projectNumberPreview }}</div>
                </div>
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">📍 Location</div>
                    <div>{!! $this->locationSummary !!}</div>
                </div>
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">👤 Customer</div>
                    <div>{{ $this->customerName }}</div>
                </div>
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">🏢 Company</div>
                    <div>{{ $this->companyName }}</div>
                </div>
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">🏷️ Type</div>
                    <div>{{ $this->projectTypeDisplay }}</div>
                </div>
            </div>
        </details>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- Left Column: Form (65% width on desktop) --}}
        <div class="lg:col-span-8">
            {{ $this->form }}
        </div>

        {{-- Right Column: Sticky Summary Panel (35% width on desktop, hidden on mobile) --}}
        <div class="hidden lg:block lg:col-span-4">
            <div class="lg:sticky lg:top-6 space-y-4">
                {{-- Project Summary Card --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Project Summary
                        </div>
                    </x-slot>

                    <div class="space-y-4 text-sm">
                        {{-- Project Number --}}
                        <div>
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                Project Number
                            </div>
                            <div class="font-mono text-base font-semibold text-gray-900 dark:text-gray-100">
                                {{ $this->projectNumberPreview }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Number assigned on save
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700"></div>

                        {{-- Location --}}
                        <div>
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                📍 Location
                            </div>
                            <div class="text-gray-900 dark:text-gray-100">
                                {!! $this->locationSummary !!}
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700"></div>

                        {{-- Customer --}}
                        <div>
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                👤 Customer
                            </div>
                            <div class="text-gray-900 dark:text-gray-100">
                                {{ $this->customerName }}
                            </div>
                        </div>

                        {{-- Company --}}
                        <div>
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                🏢 Company
                            </div>
                            <div class="text-gray-900 dark:text-gray-100">
                                {{ $this->companyName }}
                            </div>
                        </div>

                        {{-- Project Type --}}
                        <div>
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                🏷️ Type
                            </div>
                            <div class="text-gray-900 dark:text-gray-100">
                                {{ $this->projectTypeDisplay }}
                            </div>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Helper info card --}}
                <x-filament::section class="bg-blue-50 dark:bg-blue-950/20">
                    <div class="text-xs text-blue-600 dark:text-blue-400">
                        <div class="font-semibold mb-1">💡 Quick Tip</div>
                        <p>This summary updates automatically as you fill out the form. Review it before continuing to ensure all details are correct.</p>
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>

    <x-filament-panels::unsaved-action-changes-alert />
</x-filament-panels::page>
