<div>
    <div class="my-8 grid grid-cols-1 gap-8 md:grid-cols-3">
        @foreach ($blogs as $blog)
            <div class="mx-auto max-w-md overflow-hidden rounded-xl bg-white shadow-md md:max-w-2xl">
                <div class="md:shrink-0" style="max-height: 200px; overflow: hidden;">
                    <img class="h-48 w-full object-cover md:h-full md:w-48" src="{{$blog->url}}" alt="Blog post featured image" />
                </div>

                <div class="p-8">
                    <div class="text-sm font-semibold uppercase tracking-wide text-primary-500">Blog Category</div>

                    <a href="#" class="mt-1 block text-lg font-medium leading-tight text-black hover:underline">
                        {{ $blog->title }}
                    </a>

                    <p class="mt-2 text-gray-500">
                        {{ \Illuminate\Support\Str::limit($blog->content, 150, $end='...') }}
                    </p>

                    <div class="mt-4 flex items-center">
                        <x-filament-panels::avatar.user
                            class="mr-4"
                            :user="$blog->creator"
                        />

                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ $blog->creator->name }}
                            </p>
                            
                            <p class="text-sm text-gray-500">
                                {{ $blog->created_at->format('F j, Y').' · '.$blog->reading_time }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <x-filament::pagination :paginator="$blogs" />
</div>