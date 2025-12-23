<section class="tcs-faq-section py-16 bg-gray-50" x-data="{ openFaq: null }">
    <div class="container mx-auto px-4 max-w-4xl">
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

        @php
            $displayStyle = $data['display_style'] ?? 'accordion';
            $faqs = $data['faqs'] ?? [];
        @endphp

        @if(count($faqs) > 0)
            @if($displayStyle === 'accordion')
                <div class="space-y-4">
                    @foreach($faqs as $index => $faq)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden {{ $faq['css_classes'] ?? '' }}">
                            <button
                                @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                                class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors"
                            >
                                <span class="font-semibold text-gray-900">{{ $faq['question'] }}</span>
                                <svg
                                    class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                    :class="{ 'rotate-180': openFaq === {{ $index }} }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div
                                x-show="openFaq === {{ $index }}"
                                x-collapse
                                class="px-6 pb-4"
                            >
                                <div class="prose prose-sm text-gray-600">
                                    {!! $faq['answer'] !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif($displayStyle === 'list')
                <div class="space-y-6">
                    @foreach($faqs as $faq)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 {{ $faq['css_classes'] ?? '' }}">
                            <h3 class="font-semibold text-gray-900 mb-3">{{ $faq['question'] }}</h3>
                            <div class="prose prose-sm text-gray-600">
                                {!! $faq['answer'] !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif($displayStyle === 'cards')
                <div class="grid md:grid-cols-2 gap-6">
                    @foreach($faqs as $faq)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 {{ $faq['css_classes'] ?? '' }}">
                            <h3 class="font-semibold text-gray-900 mb-3">{{ $faq['question'] }}</h3>
                            <div class="prose prose-sm text-gray-600">
                                {!! $faq['answer'] !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</section>
