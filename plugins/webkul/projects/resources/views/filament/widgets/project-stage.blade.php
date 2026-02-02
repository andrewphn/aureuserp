<x-filament-widgets::widget>
    @php
        $data = $this->getStageData();

        // Stage-specific colors using inline styles for reliability
        $stageStyles = [
            'discovery' => [
                'bg' => 'background-color: rgb(243 244 246);', // gray-100 - more visible
                'border' => 'box-shadow: 0 0 0 2px rgb(156 163 175);', // gray-400 - stronger border
                'icon_bg' => 'background-color: rgb(209 213 219);', // gray-300
                'icon_color' => 'color: rgb(75 85 99);', // gray-600
                'progress_bg' => 'background-color: rgb(107 114 128);', // gray-500
                'text_color' => 'color: rgb(55 65 81);', // gray-700
            ],
            'design' => [
                'bg' => 'background-color: rgb(219 234 254);', // blue-200 - more visible
                'border' => 'box-shadow: 0 0 0 2px rgb(59 130 246);', // blue-500 - stronger border
                'icon_bg' => 'background-color: rgb(191 219 254);', // blue-200
                'icon_color' => 'color: rgb(37 99 235);', // blue-600
                'progress_bg' => 'background-color: rgb(59 130 246);', // blue-500
                'text_color' => 'color: rgb(30 64 175);', // blue-800
            ],
            'sourcing' => [
                'bg' => 'background-color: rgb(254 243 199);', // amber-100 - more visible
                'border' => 'box-shadow: 0 0 0 2px rgb(245 158 11);', // amber-500 - stronger border
                'icon_bg' => 'background-color: rgb(253 230 138);', // amber-200
                'icon_color' => 'color: rgb(217 119 6);', // amber-600
                'progress_bg' => 'background-color: rgb(245 158 11);', // amber-500
                'text_color' => 'color: rgb(146 64 14);', // amber-800
            ],
            'production' => [
                'bg' => 'background-color: rgb(233 213 255);', // purple-200 - distinct from design
                'border' => 'box-shadow: 0 0 0 2px rgb(147 51 234);', // purple-600 - stronger border
                'icon_bg' => 'background-color: rgb(216 180 254);', // purple-300
                'icon_color' => 'color: rgb(126 34 206);', // purple-700
                'progress_bg' => 'background-color: rgb(147 51 234);', // purple-600
                'text_color' => 'color: rgb(88 28 135);', // purple-800
            ],
            'delivery' => [
                'bg' => 'background-color: rgb(187 247 208);', // green-200 - more visible
                'border' => 'box-shadow: 0 0 0 2px rgb(34 197 94);', // green-500 - stronger border
                'icon_bg' => 'background-color: rgb(134 239 172);', // green-300
                'icon_color' => 'color: rgb(22 163 74);', // green-600
                'progress_bg' => 'background-color: rgb(34 197 94);', // green-500
                'text_color' => 'color: rgb(21 128 61);', // green-700
            ],
        ];

        $styles = $stageStyles[$data['stage']] ?? $stageStyles['discovery'];
    @endphp

    <div class="fi-wi-stats-overview-stat relative rounded-xl p-6 shadow-sm" style="{{ $styles['bg'] }} {{ $styles['border'] }}">
        {{-- Header with stage name and icon --}}
        <div class="flex items-center gap-x-2">
            <span class="fi-wi-stats-overview-stat-icon flex items-center justify-center rounded-full p-1.5" style="{{ $styles['icon_bg'] }}">
                @svg($data['icon'], 'w-5 h-5')
            </span>
            <span class="text-sm font-medium" style="{{ $styles['text_color'] }}">Stage</span>
            <span class="ml-auto text-xs opacity-75" style="{{ $styles['text_color'] }}">{{ $data['completed'] }}/{{ $data['total'] }}</span>
        </div>

        {{-- Stage label --}}
        <div class="mt-2 text-2xl font-semibold tracking-tight" style="{{ $styles['text_color'] }}">
            {{ $data['label'] }}
        </div>

        {{-- Progress bar --}}
        <div class="mt-3 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
            <div class="h-1.5 rounded-full transition-all duration-300"
                 style="width: {{ $data['progress'] }}%; {{ $data['progress'] >= 100 ? 'background-color: rgb(34 197 94);' : $styles['progress_bg'] }}"></div>
        </div>

        {{-- Gates checklist --}}
        <div class="mt-3 space-y-1">
            @foreach ($data['gates'] as $gate)
                <div class="flex items-center gap-x-2">
                    @if ($gate['completed'])
                        <svg class="w-4 h-4 flex-shrink-0" style="color: rgb(34 197 94);" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-xs line-through" style="color: rgb(107 114 128);">{{ $gate['label'] }}</span>
                    @else
                        <svg class="w-4 h-4 opacity-50 flex-shrink-0" style="{{ $styles['icon_color'] }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <circle cx="12" cy="12" r="9" />
                        </svg>
                        <span class="text-xs opacity-75" style="{{ $styles['text_color'] }}">{{ $gate['label'] }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
