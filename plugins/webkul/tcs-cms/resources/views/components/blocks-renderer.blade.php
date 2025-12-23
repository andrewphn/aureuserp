@props(['blocks' => []])

@foreach($blocks as $block)
    @php
        $type = $block['type'] ?? '';
        $data = $block['data'] ?? [];
        $viewName = 'tcs-cms::blocks.' . str_replace('_', '-', $type);
    @endphp

    @if(View::exists($viewName))
        @include($viewName, ['data' => $data])
    @else
        {{-- Fallback for unknown block types --}}
        @if(config('app.debug'))
            <div class="p-4 bg-yellow-100 border border-yellow-300 rounded my-4">
                <p class="text-yellow-800">Unknown block type: {{ $type }}</p>
                <pre class="text-xs mt-2">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif
    @endif
@endforeach
