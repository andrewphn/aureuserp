<section class="tcs-workshop-tip py-12 bg-amber-50">
    <div class="container mx-auto px-4 max-w-3xl">
        <div class="bg-white rounded-lg shadow-md p-8 border-l-4 border-amber-500">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <div>
                    @if(!empty($data['tip_title']))
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $data['tip_title'] }}</h3>
                    @endif
                    @if(!empty($data['tip_content']))
                        <div class="prose prose-sm text-gray-600">
                            {!! $data['tip_content'] !!}
                        </div>
                    @endif
                    @if(!empty($data['tip_category']))
                        <span class="inline-block mt-4 px-3 py-1 bg-amber-100 text-amber-800 text-xs font-medium rounded-full">
                            {{ ucfirst($data['tip_category']) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
