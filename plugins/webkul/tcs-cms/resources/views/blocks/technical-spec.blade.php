<section class="tcs-technical-spec py-16">
    <div class="container mx-auto px-4 max-w-4xl">
        @if(!empty($data['section_title']))
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">
                {{ $data['section_title'] }}
            </h2>
        @endif

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            @php $specs = $data['specifications'] ?? []; @endphp

            @if(count($specs) > 0)
                <table class="w-full">
                    <tbody class="divide-y divide-gray-200">
                        @foreach($specs as $spec)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900 bg-gray-50 w-1/3">
                                    {{ $spec['label'] ?? '' }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $spec['value'] ?? '' }}
                                    @if(!empty($spec['unit']))
                                        <span class="text-gray-400">{{ $spec['unit'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if(!empty($data['notes']))
            <div class="mt-6 p-4 bg-amber-50 rounded-lg border border-amber-200">
                <h4 class="font-semibold text-amber-800 mb-2">Notes</h4>
                <div class="text-amber-700 prose prose-sm">
                    {!! $data['notes'] !!}
                </div>
            </div>
        @endif
    </div>
</section>
