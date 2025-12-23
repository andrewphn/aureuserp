<section class="tcs-project-block py-16">
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

        @php $projects = $data['projects'] ?? []; @endphp

        @if(count($projects) > 0)
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($projects as $project)
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden group">
                        @if(!empty($project['image']))
                            <div class="aspect-video overflow-hidden">
                                <img
                                    src="{{ Storage::url($project['image']) }}"
                                    alt="{{ $project['title'] ?? '' }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                >
                            </div>
                        @endif
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                {{ $project['title'] ?? '' }}
                            </h3>
                            @if(!empty($project['description']))
                                <p class="text-gray-600">{{ $project['description'] }}</p>
                            @endif
                            @if(!empty($project['category']))
                                <span class="inline-block mt-3 px-3 py-1 bg-amber-100 text-amber-800 text-sm rounded-full">
                                    {{ $project['category'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
