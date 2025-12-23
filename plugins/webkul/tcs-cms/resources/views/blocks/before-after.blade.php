<section class="tcs-before-after py-16">
    <div class="container mx-auto px-4">
        @if(!empty($data['section_title']))
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-4">
                {{ $data['section_title'] }}
            </h2>
        @endif

        @php $comparisons = $data['comparisons'] ?? []; @endphp

        @if(count($comparisons) > 0)
            <div class="grid md:grid-cols-2 gap-8 mt-8">
                @foreach($comparisons as $comparison)
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden" x-data="{ showAfter: false }">
                        <div class="relative aspect-video">
                            @if(!empty($comparison['before_image']))
                                <img
                                    src="{{ Storage::url($comparison['before_image']) }}"
                                    alt="Before"
                                    class="absolute inset-0 w-full h-full object-cover transition-opacity"
                                    :class="{ 'opacity-0': showAfter }"
                                >
                            @endif
                            @if(!empty($comparison['after_image']))
                                <img
                                    src="{{ Storage::url($comparison['after_image']) }}"
                                    alt="After"
                                    class="absolute inset-0 w-full h-full object-cover transition-opacity"
                                    :class="{ 'opacity-0': !showAfter }"
                                >
                            @endif
                            <div class="absolute top-4 left-4 bg-black/70 text-white px-3 py-1 rounded text-sm">
                                <span x-text="showAfter ? 'After' : 'Before'"></span>
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">{{ $comparison['title'] ?? '' }}</h3>
                            <button
                                @click="showAfter = !showAfter"
                                class="w-full py-2 bg-amber-500 hover:bg-amber-600 text-white rounded transition-colors"
                            >
                                <span x-text="showAfter ? 'Show Before' : 'Show After'"></span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
