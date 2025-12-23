<section class="tcs-material-showcase py-16">
    <div class="container mx-auto px-4">
        @if(!empty($data['section_title']))
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-4">
                {{ $data['section_title'] }}
            </h2>
        @endif

        @if(!empty($data['section_intro']))
            <div class="text-gray-600 text-center mb-8 prose max-w-2xl mx-auto">
                {!! $data['section_intro'] !!}
            </div>
        @endif

        @php $materials = $data['materials'] ?? []; @endphp

        @if(count($materials) > 0)
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($materials as $material)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        @if(!empty($material['image']))
                            <div class="aspect-square overflow-hidden">
                                <img
                                    src="{{ Storage::url($material['image']) }}"
                                    alt="{{ $material['name'] ?? '' }}"
                                    class="w-full h-full object-cover"
                                >
                            </div>
                        @endif
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900">{{ $material['name'] ?? '' }}</h3>
                            @if(!empty($material['origin']))
                                <p class="text-sm text-gray-500">{{ $material['origin'] }}</p>
                            @endif
                            @if(!empty($material['description']))
                                <p class="text-sm text-gray-600 mt-2">{{ Str::limit($material['description'], 100) }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
