{{-- Project Summary Sidebar Container --}}
{{-- A flexible widget-based sidebar that can be customized per stage --}}

<div
    x-data="{ collapsed: @entangle('collapsed') }"
    class="project-summary-sidebar"
>
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        {{-- Header --}}
        @if($showHeader)
            @php
                $namePreview = $this->projectNamePreview;
            @endphp
            <div class="bg-primary-50 dark:bg-primary-500/10 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-semibold text-primary-900 dark:text-primary-100 flex items-center gap-2">
                            @if($headerIcon)
                                <x-dynamic-component :component="$headerIcon" class="h-4 w-4 flex-shrink-0" />
                            @endif
                            <span class="truncate">{{ $namePreview['value'] ?? $headerTitle }}</span>
                        </h3>
                    </div>

                    @if($collapsible)
                        <button
                            type="button"
                            @click="collapsed = !collapsed"
                            class="lg:hidden p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 flex-shrink-0 ml-2"
                        >
                            <x-heroicon-o-chevron-down
                                class="h-4 w-4 transition-transform"
                                x-bind:class="{ 'rotate-180': !collapsed }"
                            />
                        </button>
                    @endif
                </div>

                {{-- Progress Bar --}}
                @if($this->completionPercentage > 0)
                    <div class="mt-2">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-gray-600 dark:text-gray-400">Completion</span>
                            <span class="font-medium text-primary-600 dark:text-primary-400">{{ $this->completionPercentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                            <div
                                class="bg-primary-600 dark:bg-primary-400 h-1.5 rounded-full transition-all duration-300"
                                style="width: {{ $this->completionPercentage }}%"
                            ></div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Widget Container --}}
        <div
            class="p-4 space-y-4"
            x-show="!collapsed"
            x-collapse
        >
            {{-- Render Active Widgets - hide empty ones unless showEmptyFields is true --}}
            @php
                $visibleWidgets = [];
                $hiddenWidgets = [];
                foreach($this->activeWidgets as $widget) {
                    if ($widget !== $footerWidget && $this->getWidgetView($widget)) {
                        if ($this->widgetHasData($widget) || $showEmptyFields) {
                            $visibleWidgets[] = $widget;
                        } else {
                            $hiddenWidgets[] = $widget;
                        }
                    }
                }
            @endphp

            @foreach($visibleWidgets as $widget)
                @php
                    $widgetView = $this->getWidgetView($widget);
                @endphp

                <div class="sidebar-widget" data-widget="{{ $widget }}">
                    @include($widgetView)
                </div>

                @if(!$loop->last)
                    <hr class="border-gray-200 dark:border-gray-700" />
                @endif
            @endforeach

            {{-- Show/Hide Empty Fields Toggle --}}
            @if(count($hiddenWidgets) > 0 || $showEmptyFields)
                <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="toggleShowEmptyFields"
                        class="w-full flex items-center justify-center gap-1.5 text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 py-1.5 px-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                    >
                        @if($showEmptyFields)
                            <x-heroicon-o-eye-slash class="h-3.5 w-3.5" />
                            <span>Hide empty fields</span>
                        @else
                            <x-heroicon-o-eye class="h-3.5 w-3.5" />
                            <span>Show {{ count($hiddenWidgets) }} more fields</span>
                        @endif
                    </button>
                </div>
            @endif

            {{-- Custom Slot for Additional Widgets --}}
            {{ $slot ?? '' }}
        </div>

        {{-- Footer Widget (typically estimate) --}}
        @if($showFooter && $footerWidget)
            @php
                $footerWidgetView = $this->getWidgetView($footerWidget);
            @endphp

            @if($footerWidgetView)
                <div
                    class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700"
                    x-show="!collapsed"
                    x-collapse
                >
                    @include($footerWidgetView)
                </div>
            @endif
        @endif
    </div>
</div>
