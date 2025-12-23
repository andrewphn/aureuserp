<section class="tcs-wood-species py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        @if(!empty($data['section_title']))
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-4">
                {{ $data['section_title'] }}
            </h2>
        @endif

        @if(!empty($data['section_intro']))
            <div class="text-gray-600 text-center mb-12 prose max-w-2xl mx-auto">
                {!! $data['section_intro'] !!}
            </div>
        @endif

        @php $species = $data['species'] ?? []; @endphp

        @if(count($species) > 0)
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($species as $wood)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        @if(!empty($wood['image']))
                            <div class="aspect-[4/3] overflow-hidden">
                                <img
                                    src="{{ Storage::url($wood['image']) }}"
                                    alt="{{ $wood['name'] ?? '' }}"
                                    class="w-full h-full object-cover"
                                >
                            </div>
                        @endif
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-1">{{ $wood['name'] ?? '' }}</h3>
                            @if(!empty($wood['scientific_name']))
                                <p class="text-sm text-gray-500 italic mb-3">{{ $wood['scientific_name'] }}</p>
                            @endif
                            @if(!empty($wood['description']))
                                <p class="text-gray-600 text-sm mb-4">{{ $wood['description'] }}</p>
                            @endif

                            @if(!empty($wood['properties']))
                                <div class="space-y-2">
                                    @foreach($wood['properties'] as $property => $value)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">{{ ucfirst($property) }}</span>
                                            <span class="font-medium text-gray-900">{{ $value }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if(!empty($wood['applications']))
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($wood['applications'] as $application)
                                        <span class="px-2 py-1 bg-amber-100 text-amber-800 text-xs rounded-full">
                                            {{ $application }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
