<section class="tcs-video-tutorial py-16 bg-gray-50">
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

        @if(!empty($data['video_url']))
            @php
                $videoUrl = $data['video_url'];
                $embedUrl = $videoUrl;

                // Convert YouTube URLs to embed format
                if (str_contains($videoUrl, 'youtube.com/watch')) {
                    parse_str(parse_url($videoUrl, PHP_URL_QUERY), $params);
                    $embedUrl = 'https://www.youtube.com/embed/' . ($params['v'] ?? '');
                } elseif (str_contains($videoUrl, 'youtu.be/')) {
                    $videoId = basename(parse_url($videoUrl, PHP_URL_PATH));
                    $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
                } elseif (str_contains($videoUrl, 'vimeo.com/')) {
                    $videoId = basename(parse_url($videoUrl, PHP_URL_PATH));
                    $embedUrl = 'https://player.vimeo.com/video/' . $videoId;
                }
            @endphp

            <div class="aspect-video rounded-lg overflow-hidden shadow-lg">
                <iframe
                    src="{{ $embedUrl }}"
                    class="w-full h-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </div>
        @endif

        @if(!empty($data['video_description']))
            <div class="mt-6 prose max-w-none">
                {!! $data['video_description'] !!}
            </div>
        @endif
    </div>
</section>
