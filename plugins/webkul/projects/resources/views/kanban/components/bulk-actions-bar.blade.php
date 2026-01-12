{{-- Bulk Actions Floating Bar - Shows when multiple cards selected --}}
<div
    x-show="selectedCards.length > 0"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    style="position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); z-index: 9999; display: none;"
    :style="selectedCards.length > 0 ? 'display: block !important;' : 'display: none !important;'"
>
    <x-filament::section compact class="!py-2 !px-3">
        <div class="flex items-center gap-3">
            {{-- Selection Count --}}
            <div class="flex items-center gap-2 pr-3 border-r border-gray-200 dark:border-gray-700">
                <x-filament::badge color="primary" size="lg">
                    <span x-text="selectedCards.length"></span>
                </x-filament::badge>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">selected</span>
            </div>

            {{-- Move to Stage Dropdown --}}
            <x-filament::dropdown placement="top-start">
                <x-slot name="trigger">
                    <x-filament::button
                        color="gray"
                        size="sm"
                        icon="heroicon-m-arrow-right-circle"
                    >
                        Move to Stage
                    </x-filament::button>
                </x-slot>

                <x-filament::dropdown.list>
                    @foreach(\Webkul\Project\Models\ProjectStage::where('is_active', true)->orderBy('sort')->get() as $stage)
                        <x-filament::dropdown.list.item
                            @click="bulkChangeStage({{ $stage->id }})"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="w-2 h-2 rounded-full flex-shrink-0"
                                    style="background-color: {{ $stage->color ?? '#6b7280' }};"
                                ></span>
                                {{ $stage->name }}
                            </div>
                        </x-filament::dropdown.list.item>
                    @endforeach
                </x-filament::dropdown.list>
            </x-filament::dropdown>

            {{-- Mark Blocked --}}
            <x-filament::button
                @click="bulkMarkBlocked()"
                color="warning"
                size="sm"
                icon="heroicon-m-no-symbol"
            >
                Block
            </x-filament::button>

            {{-- Unblock --}}
            <x-filament::button
                @click="bulkUnblock()"
                color="success"
                size="sm"
                icon="heroicon-m-check-circle"
            >
                Unblock
            </x-filament::button>

            {{-- Divider --}}
            <div class="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>

            {{-- Clear Selection --}}
            <x-filament::icon-button
                @click="clearSelection()"
                icon="heroicon-m-x-mark"
                color="gray"
                size="sm"
                label="Clear selection"
            />
        </div>
    </x-filament::section>
</div>
