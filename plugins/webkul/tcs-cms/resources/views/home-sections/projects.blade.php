@props(['section'])

@php
    $settings = $section->settings ?? [];
    $bgColor = $settings['background_color'] ?? 'bg-white';
    // Get featured projects from the database
    $featuredProjects = \Webkul\TcsCms\Models\PortfolioProject::where('featured', true)
        ->where('is_published', true)
        ->orderBy('portfolio_order')
        ->take(4)
        ->get();
@endphp

<section id="featured-projects" class="section-padding {{ $bgColor }}">
    <div class="container-tcs">
        <div class="text-center mb-16">
            <h2 class="section-subtitle">
                {{ $section->title ?? 'Featured Work' }}
            </h2>
        </div>

        <div class="grid grid-cols-1 gap-12 md:grid-cols-2 lg:gap-16">
            @if($featuredProjects->count() > 0)
                @foreach($featuredProjects as $index => $project)
                    <div class="portfolio-card group">
                        <a href="/work/{{ $project->slug }}">
                            <div class="relative mb-6 overflow-hidden aspect-[4/3] lg:aspect-video rounded-lg">
                                @if($project->featured_image_url)
                                    <img
                                        src="{{ $project->featured_image_url }}"
                                        alt="{{ $project->title }}"
                                        class="object-cover w-full h-full transition-transform duration-700 group-hover:scale-105"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="w-full h-full bg-neutral-200 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="content-tertiary mb-2">{{ $project->client_name ?? 'Featured Work' }}</p>
                                    <h3 class="text-xl md:text-2xl font-serif font-normal leading-tight">
                                        {{ $project->title }}
                                    </h3>
                                    <p class="mt-3 content-secondary max-w-2xl">
                                        {{ $project->summary ?? 'Custom woodworking project showcasing our precision craftsmanship and attention to detail.' }}
                                    </p>
                                </div>
                                <div class="transition-transform duration-300 group-hover:translate-x-2 ml-4 flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12h14"></path>
                                        <path d="M12 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            @else
                <div class="col-span-2 text-center py-12 content-tertiary">
                    <p>No featured projects available.</p>
                </div>
            @endif
        </div>

        <div class="mt-16 text-center">
            <a href="{{ $section->cta_link ?? '/work' }}" class="btn-secondary">
                {{ $section->cta_text ?? 'Explore Our Portfolio' }}
            </a>
        </div>
    </div>
</section>
