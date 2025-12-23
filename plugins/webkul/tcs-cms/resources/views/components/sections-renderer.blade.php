@props(['sections' => []])

@foreach($sections as $section)
    @php
        $sectionType = $section->section_type ?? 'custom';
        $viewName = 'tcs-cms::home-sections.' . str_replace('_', '-', $sectionType);
    @endphp

    @if(View::exists($viewName))
        @include($viewName, ['section' => $section])
    @else
        {{-- Fallback for unknown section types --}}
        @if(config('app.debug'))
            <div class="p-4 bg-yellow-100 border border-yellow-300 rounded my-4">
                <p class="text-yellow-800">Unknown section type: {{ $sectionType }}</p>
            </div>
        @endif
    @endif
@endforeach
