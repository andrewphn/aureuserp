{{-- Project Summary Sidebar Container --}}
{{-- A flexible widget-based sidebar using Filament section components --}}

<div
    x-data="{ collapsed: @entangle('collapsed') }"
    class="project-summary-sidebar"
>
    <x-filament::section
        :collapsible="$collapsible"
        :collapsed="false"
        :compact="true"
    >
        @if($showHeader)
            @php
                $namePreview = $this->projectNamePreview;
            @endphp
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @if($headerIcon)
                        <x-filament::icon :icon="$headerIcon" class="h-4 w-4 text-primary-500" />
                    @endif
                    <span class="truncate text-sm">{{ $namePreview['value'] ?? $headerTitle }}</span>
                </div>
            </x-slot>

            @if($this->completionPercentage > 0)
                <x-slot name="description">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Completion</span>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                            <div
                                class="bg-primary-500 h-1.5 rounded-full transition-all duration-300"
                                style="width: {{ $this->completionPercentage }}%"
                            ></div>
                        </div>
                        <span class="font-medium text-primary-600 dark:text-primary-400">{{ $this->completionPercentage }}%</span>
                    </div>
                </x-slot>
            @endif
        @endif

        {{-- Widget Container --}}
        <div class="space-y-3">
            {{-- Render Active Widgets --}}
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
                    <hr class="border-gray-200 dark:border-white/10" />
                @endif
            @endforeach

            {{-- Show/Hide Empty Fields Toggle --}}
            @if(count($hiddenWidgets) > 0 || $showEmptyFields)
                <div class="pt-2">
                    <x-filament::link
                        tag="button"
                        wire:click="toggleShowEmptyFields"
                        color="gray"
                        size="sm"
                        :icon="$showEmptyFields ? 'heroicon-m-eye-slash' : 'heroicon-m-eye'"
                        class="w-full justify-center"
                    >
                        @if($showEmptyFields)
                            Hide empty fields
                        @else
                            Show {{ count($hiddenWidgets) }} more fields
                        @endif
                    </x-filament::link>
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
                <x-slot name="footerActions">
                    <div class="w-full">
                        @include($footerWidgetView)
                    </div>
                </x-slot>
            @endif
        @endif
    </x-filament::section>
</div>
