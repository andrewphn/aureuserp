<section class="tcs-craftsman-quote py-16 bg-amber-50">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="text-center">
            @if(!empty($data['quote']))
                <blockquote class="text-2xl md:text-3xl font-serif text-gray-800 italic leading-relaxed mb-6">
                    "{{ $data['quote'] }}"
                </blockquote>
            @endif

            @if(!empty($data['craftsman_name']))
                <div class="flex items-center justify-center gap-4">
                    @if(!empty($data['craftsman_image']))
                        <img
                            src="{{ Storage::url($data['craftsman_image']) }}"
                            alt="{{ $data['craftsman_name'] }}"
                            class="w-16 h-16 rounded-full object-cover"
                        >
                    @endif
                    <div class="text-left">
                        <p class="font-semibold text-gray-900">{{ $data['craftsman_name'] }}</p>
                        @if(!empty($data['craftsman_title']))
                            <p class="text-sm text-gray-600">{{ $data['craftsman_title'] }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
