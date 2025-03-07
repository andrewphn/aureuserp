<x-filament-panels::page>
    <div class="md:shrink-0">
        <img class="h-48 w-full rounded-md object-cover md:h-full md:w-48" src="{{$record->image_url}}" alt="Blog post featured image" style="aspect-ratio: 3 / 1"/>
    </div>

    <p>
        {!! $record->content !!}
    </p>

    @if ($record->tags->count())
        <div class="flex gap-4">
            @foreach ($record->tags as $tag)
                <x-filament::badge
                    :color="$tag->color ? \Filament\Support\Colors\Color::hex($tag->color) : 'primary'"
                >
                    {{ $tag->name }}
                </x-filament::badge>
            @endforeach
        </div>
    @endif

    <div class="flex items-center">
        <x-filament-panels::avatar.user
            class="mr-4"
            :user="$record->creator"
        />

        <div>
            <p class="text-sm font-medium text-gray-900">
                {{ $record->creator->name }}
            </p>
            
            <p class="text-sm text-gray-500">
                {{ $record->published_at->format('F j, Y').' · '.$record->reading_time }}
            </p>
        </div>
    </div>
</x-filament-panels::page>