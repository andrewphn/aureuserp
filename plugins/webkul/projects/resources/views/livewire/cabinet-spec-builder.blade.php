<div class="cabinet-spec-builder" wire:key="cabinet-spec-builder">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2.5">
            <div class="p-2 bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                <x-heroicon-o-squares-2x2 class="w-5 h-5 text-gray-500 dark:text-gray-400" />
            </div>
            <div>
                <span class="font-semibold text-gray-900 dark:text-white">Cabinet Specification</span>
                @if(count($specData) > 0)
                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ count($specData) }} room{{ count($specData) !== 1 ? 's' : '' }}</span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3 sm:gap-4">
            @if($totalLinearFeet > 0)
                <div class="flex items-center gap-4 text-sm">
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-400">Total:</span>
                        <strong class="text-blue-600 dark:text-blue-400 tabular-nums">{{ number_format($totalLinearFeet, 1) }} LF</strong>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 dark:text-gray-400">Est:</span>
                        <strong class="text-green-600 dark:text-green-400 tabular-nums">${{ number_format($totalPrice, 0) }}</strong>
                    </div>
                </div>
            @endif
            <button
                wire:click="openCreate('room', null)"
                type="button"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 rounded-lg transition-colors shadow-sm"
            >
                <x-heroicon-m-plus class="w-4 h-4" />
                Add Room
            </button>
        </div>
    </div>

    {{-- Empty State --}}
    @if(empty($specData))
        <div class="text-center py-16 px-6 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50/50 dark:bg-gray-800/50">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                <x-heroicon-o-home class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-1">No rooms added yet</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5 max-w-sm mx-auto">
                Start building your cabinet specification by adding your first room
            </p>
            <button
                wire:click="openCreate('room', null)"
                type="button"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-primary-600 hover:text-primary-700 border border-primary-300 hover:border-primary-400 bg-white dark:bg-gray-800 rounded-lg transition-colors shadow-sm"
            >
                <x-heroicon-m-plus class="w-4 h-4" />
                Add First Room
            </button>
        </div>
    @else
        {{-- Tree View --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-800 shadow-sm">
            {{-- Tree container with max height and scroll --}}
            <div class="max-h-[600px] overflow-y-auto overscroll-contain">
                <div class="divide-y divide-gray-100 dark:divide-gray-700/50 py-1">
                    @foreach($specData as $index => $room)
                        @include('webkul-project::livewire.partials.spec-tree-node', [
                            'node' => $room,
                            'path' => (string) $index,
                            'level' => 0,
                        ])
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Summary Footer --}}
        @if($totalLinearFeet > 0)
            <div class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-green-50 dark:from-blue-900/20 dark:to-green-900/20 rounded-xl border border-blue-200/50 dark:border-blue-800/50 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Project Summary</span>
                    <div class="flex items-center gap-6">
                        <div class="text-right">
                            <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-medium">Total Linear Feet</div>
                            <div class="text-xl font-bold text-blue-600 dark:text-blue-400 tabular-nums">{{ number_format($totalLinearFeet, 1) }} LF</div>
                        </div>
                        <div class="w-px h-10 bg-gray-200 dark:bg-gray-700 hidden sm:block"></div>
                        <div class="text-right">
                            <div class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-medium">Estimated Price</div>
                            <div class="text-xl font-bold text-green-600 dark:text-green-400 tabular-nums">${{ number_format($totalPrice, 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Modal --}}
    @if($showModal)
        @include('webkul-project::livewire.partials.spec-modal')
    @endif
</div>
