{{--
    Global Context Footer Widget - FilamentPHP v4

    A universal, context-aware footer that displays active entity information
    across all admin pages. Supports project, sale, inventory, and production contexts.

    Features:
    - Context-aware display (shows different fields for different entity types)
    - Customizable fields (user preferences)
    - Real-time updates (via Livewire events)
    - Minimizable/Expandable
    - Plugin-extensible architecture
--}}

<div
    x-data="contextFooter({
        contextType: @js($contextType),
        contextId: @js($contextId),
        contextData: @js($contextData),
        contextConfigs: @js($jsContextConfigs),
        isMinimized: @entangle('isMinimized'),
        hasActiveContext: @js($hasActiveContext),
    })"
    x-cloak
    @active-context-changed.window="handleContextChange($event.detail)"
    @entity-updated.window="handleEntityUpdate($event.detail)"
    class="fi-section rounded-t-xl shadow-lg ring-1 ring-gray-950/10 dark:ring-white/10 transition-all duration-300 ease-in-out"
    :style="`position: fixed; bottom: 0; left: 0; right: 0; z-index: 50; backdrop-filter: blur(8px); background: linear-gradient(to right, rgb(249, 250, 251), rgb(243, 244, 246)); border-top: 3px solid ${contextConfig.borderColor}; transform: translateY(${isMinimized ? 'calc(100% - 44px)' : '0'}); padding: 0; margin: 0;`"
>
        {{-- Toggle Button Bar --}}
        <div
            class="flex items-center justify-between px-3 sm:px-4 md:px-6 py-2 sm:py-2.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            @click="$wire.toggleMinimized()"
        >
            {{-- Context Info (Left side) --}}
            <div class="flex items-center gap-2 sm:gap-3 overflow-hidden min-w-0 flex-1">
                {{-- Dynamic Icon --}}
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 dark:text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="contextConfig.iconPath"></path>
                </svg>

                {{-- No Context State --}}
                <span x-show="!hasActiveContext" class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400" x-text="contextConfig.emptyLabel"></span>

                {{-- Active Context - Minimized Preview --}}
                <template x-if="hasActiveContext">
                    <div class="flex items-center gap-1.5 sm:gap-2 min-w-0 flex-1">
                        <span class="text-xs sm:text-sm md:text-base font-semibold text-gray-900 dark:text-gray-100 truncate" x-text="getMinimizedPreview()"></span>
                    </div>
                </template>
            </div>

            {{-- Toggle Chevron (Right) --}}
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 transition-transform duration-300 flex-shrink-0 ml-2 sm:ml-3" :class="{'rotate-180': !isMinimized}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
            </svg>
        </div>

        {{-- Expanded Content --}}
        <div
            class="fi-section-content px-3 pt-3 pb-0"
            x-show="!isMinimized"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            {{-- No Context Selected State --}}
            <div x-show="!hasActiveContext" class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="contextConfig.iconPath"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="'No ' + contextConfig.name + ' Selected'"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="'Select a ' + contextConfig.name.toLowerCase() + ' to view context and details'"></p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="$wire.openProjectSelector()"
                    class="fi-btn fi-btn-size-md fi-btn-color-primary inline-flex items-center justify-center gap-2 font-semibold rounded-lg px-4 py-2 text-sm bg-primary-600 text-white hover:bg-primary-700"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="contextConfig.iconPath"></path>
                    </svg>
                    <span x-text="'Select ' + contextConfig.name"></span>
                </button>
            </div>

            {{-- Active Context State - Schema Fields --}}
            @if($hasActiveContext)
                <div class="flex items-center justify-between gap-4">
                    {{-- Schema Display --}}
                    <div class="flex-1">
                        {{ $this->contextInfolist }}
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-row gap-2 flex-shrink-0 items-center">
                        {{-- Context-aware Save button --}}
                        @if($isOnEditPage)
                            <button
                                type="button"
                                x-on:click="saveCurrentForm()"
                                x-data="{ formDisabled: false }"
                                x-init="$nextTick(() => {
                                    const checkDisabled = () => {
                                        const allButtons = document.querySelectorAll('button');
                                        for (const btn of allButtons) {
                                            const text = btn.textContent.trim();
                                            const isSubmit = btn.type === 'submit';
                                            if (isSubmit && (text === 'Save' || text === 'Save changes') && !btn.closest('[x-data*=contextFooter]')) {
                                                formDisabled = btn.disabled;
                                                return;
                                            }
                                        }
                                        formDisabled = false;
                                    };
                                    checkDisabled();
                                    setInterval(checkDisabled, 500);
                                })"
                                :disabled="formDisabled"
                                :class="{
                                    'opacity-50 cursor-not-allowed': formDisabled,
                                    'hover:bg-green-600': !formDisabled
                                }"
                                class="fi-btn fi-btn-size-sm fi-btn-color-success inline-flex items-center justify-center gap-2 font-semibold rounded-lg px-3 py-1.5 text-xs bg-green-500 text-white transition-all"
                                style="background-color: rgb(34, 197, 94); color: white;"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Save
                            </button>
                        @endif

                        {{-- Switch Context Button --}}
                        <button
                            type="button"
                            @click="$wire.openProjectSelector()"
                            class="fi-btn fi-btn-size-sm fi-btn-color-primary inline-flex items-center justify-center gap-2 font-semibold rounded-lg px-3 py-1.5 text-xs bg-primary-600 text-white hover:bg-primary-700"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12M8 12h12M8 17h12M3 7h.01M3 12h.01M3 17h.01"></path>
                            </svg>
                            Switch
                        </button>

                        {{-- Edit Button (when not on edit page) --}}
                        @if(!$isOnEditPage && $contextType && $contextId)
                            <a
                                href="{{ url("/admin/{$contextType}/".\Illuminate\Support\Str::plural($contextType)."/{$contextId}/edit") }}"
                                class="fi-btn fi-btn-size-sm fi-btn-color-gray inline-flex items-center justify-center font-semibold rounded-lg px-3 py-1.5 text-xs bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                            >
                                Edit
                            </a>
                        @endif

                        {{-- Clear Context Button --}}
                        <button
                            type="button"
                            @click="if (confirm('Clear active {{ $contextConfig['name'] ?? 'context' }}?')) { $wire.clearContext() }"
                            class="fi-btn fi-btn-size-sm fi-btn-color-gray inline-flex items-center justify-center font-semibold rounded-lg px-3 py-1.5 text-xs bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            @endif
        </div>
</div>
