@props(['section'])

@php
    $settings = $section->settings ?? [];
    $bgColor = $settings['background_color'] ?? 'bg-neutral-50';
    // Get latest journals from the database
    $latestJournals = \Webkul\TcsCms\Models\Journal::where('is_published', true)
        ->orderBy('published_at', 'desc')
        ->take(3)
        ->get();
@endphp

<section id="journal" class="section-padding {{ $bgColor }}">
    <div class="container-tcs">
        <div class="text-center mb-16">
            <h2 class="section-subtitle">
                {{ $section->title ?? 'Craftsmanship Insights' }}
            </h2>
        </div>

        <div class="grid grid-cols-1 gap-12 md:grid-cols-2 lg:grid-cols-3 lg:gap-16">
            @if($latestJournals->count() > 0)
                @foreach($latestJournals as $index => $journal)
                    <a href="/journal/{{ $journal->slug }}" class="block portfolio-card group">
                        <div class="relative mb-6 overflow-hidden aspect-[4/3] rounded-lg">
                            @if($journal->featured_image_url)
                                <img
                                    src="{{ $journal->featured_image_url }}"
                                    alt="{{ $journal->title }}"
                                    class="object-cover w-full h-full transition-transform duration-700 group-hover:scale-105"
                                    loading="lazy"
                                >
                            @else
                                <div class="w-full h-full bg-neutral-200 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <p class="content-tertiary mb-2">
                            {{ $journal->published_at ? $journal->published_at->format('F d, Y') : 'Draft' }}
                        </p>
                        <h3 class="text-xl md:text-2xl font-serif font-normal leading-tight">
                            {{ $journal->title }}
                        </h3>
                        <p class="mt-3 content-secondary pr-4">
                            {{ $journal->excerpt ?? Str::limit(strip_tags($journal->content), 120) }}
                        </p>
                        <span class="btn-text mt-4">
                            Read More
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 ml-1">
                                <path d="M5 12h14"></path>
                                <path d="M12 5l7 7-7 7"></path>
                            </svg>
                        </span>
                    </a>
                @endforeach
            @else
                <div class="col-span-3 text-center py-12 content-tertiary">
                    <p>No journal entries available.</p>
                </div>
            @endif
        </div>

        <div class="mt-20 text-center">
            <a href="{{ $section->cta_link ?? '/journal' }}" class="btn-text">
                {{ $section->cta_text ?? 'View All Journal Entries' }}
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 ml-2">
                    <path d="M5 12h14"></path>
                    <path d="M12 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>
</section>
