<section class="tcs-project-gallery py-16">
    <div class="container mx-auto px-4">
        @if(!empty($data['section_title']))
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">
                {{ $data['section_title'] }}
            </h2>
        @endif

        @php $images = $data['images'] ?? []; @endphp

        @if(count($images) > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" x-data="{ lightbox: null }">
                @foreach($images as $index => $image)
                    <div
                        class="aspect-square overflow-hidden rounded-lg cursor-pointer group"
                        @click="lightbox = {{ $index }}"
                    >
                        <img
                            src="{{ Storage::url($image['url'] ?? $image) }}"
                            alt="{{ $image['caption'] ?? '' }}"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                        >
                    </div>
                @endforeach

                {{-- Lightbox --}}
                <div
                    x-show="lightbox !== null"
                    x-cloak
                    class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center"
                    @keydown.escape.window="lightbox = null"
                    @click.self="lightbox = null"
                >
                    <button @click="lightbox = null" class="absolute top-4 right-4 text-white text-4xl">&times;</button>
                    <button @click="lightbox = (lightbox - 1 + {{ count($images) }}) % {{ count($images) }}" class="absolute left-4 text-white text-4xl">&larr;</button>
                    <button @click="lightbox = (lightbox + 1) % {{ count($images) }}" class="absolute right-4 text-white text-4xl">&rarr;</button>
                    <template x-for="(image, index) in {{ json_encode($images) }}" :key="index">
                        <img
                            x-show="lightbox === index"
                            :src="'/storage/' + (image.url || image)"
                            class="max-h-[90vh] max-w-[90vw] object-contain"
                        >
                    </template>
                </div>
            </div>
        @endif
    </div>
</section>
