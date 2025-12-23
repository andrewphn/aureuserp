<section class="tcs-process-timeline py-16 bg-gray-50">
    <div class="container mx-auto px-4 max-w-4xl">
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

        @php $steps = $data['steps'] ?? []; @endphp

        @if(count($steps) > 0)
            <div class="relative">
                {{-- Timeline line --}}
                <div class="absolute left-1/2 transform -translate-x-1/2 w-0.5 h-full bg-amber-300 hidden md:block"></div>

                <div class="space-y-12">
                    @foreach($steps as $index => $step)
                        <div class="relative flex items-center {{ $index % 2 == 0 ? 'md:flex-row' : 'md:flex-row-reverse' }}">
                            {{-- Timeline dot --}}
                            <div class="absolute left-1/2 transform -translate-x-1/2 w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center text-white font-bold text-sm hidden md:flex">
                                {{ $index + 1 }}
                            </div>

                            {{-- Content --}}
                            <div class="w-full md:w-5/12 {{ $index % 2 == 0 ? 'md:pr-16 md:text-right' : 'md:pl-16' }}">
                                <div class="bg-white p-6 rounded-lg shadow-md">
                                    <div class="md:hidden w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center text-white font-bold text-sm mb-4">
                                        {{ $index + 1 }}
                                    </div>
                                    <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $step['title'] ?? '' }}</h3>
                                    @if(!empty($step['duration']))
                                        <span class="text-sm text-amber-600 font-medium">{{ $step['duration'] }}</span>
                                    @endif
                                    @if(!empty($step['description']))
                                        <p class="text-gray-600 mt-2">{{ $step['description'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
