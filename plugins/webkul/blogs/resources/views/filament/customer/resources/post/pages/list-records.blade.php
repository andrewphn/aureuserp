<x-filament-panels::page>
    <?php
        $records = $this->getRecords();
    ?>
    
    <div class="my-8 grid grid-cols-1 gap-8 md:grid-cols-3">
        @foreach ($records as $record)
            <div class="mx-auto max-w-md overflow-hidden rounded-xl bg-white shadow-md md:max-w-2xl">
                <div class="md:shrink-0">
                    <img class="h-48 w-full object-cover md:h-full md:w-48" src="{{$record->image_url}}" alt="Blog post featured image" style="aspect-ratio: 2 / 1" />
                </div>

                <div class="p-6">
                    <div class="text-sm font-semibold uppercase tracking-wide text-primary-500">
                        {{ $record->category?->name }}
                    </div>
                    
                    <a href="{{ self::getResource()::getUrl('view', ['record' => $record->slug]) }}" class="mt-1 block text-lg font-medium leading-tight text-black hover:underline">
                        {{ $record->title }}
                    </a>

                    <p class="mt-2 text-gray-500">
                        {{ \Illuminate\Support\Str::limit($record->sub_title ?? $record->content, 150, $end='...') }}
                    </p>

                    @if ($record->tags->count())
                        <div class="mt-4 flex gap-4">
                            @foreach ($record->tags as $tag)
                                <x-filament::badge
                                    :color="$tag->color ? \Filament\Support\Colors\Color::hex($tag->color) : 'primary'"
                                >
                                    {{ $tag->name }}
                                </x-filament::badge>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 flex items-center">
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
                </div>
            </div>
        @endforeach
    </div>

    <x-filament::pagination :paginator="$records" />
</x-filament-panels::page>
